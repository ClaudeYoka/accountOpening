# Architecture — Partie « Ouverture de compte », RIB et Formulaire digital

## Résumé 🎯
Ce document décrit l'architecture, le flux et les composants principaux de la **partie ouverture de compte**, **générateur de RIB** et **formulaire digital** de votre projet (dossier `cso/`). Il explique où se trouvent les pages, les endpoints back-end, les tables de base de données et le flux de données (soumission / recherche / génération PDF/RIB).

---

## 1) Emplacement des fichiers principaux 🔧
- UI / impressions / formulaires :
  - `cso/Ecobank Account Opening Form Customer.html` — modèle HTML du formulaire d'ouverture
  - `cso/ecobank_account_form.php` — wrapper PHP qui injecte JS et gère l'interface d'enregistrement et l'envoi
  - `cso/formulaire_produits.html` — modèle imprimable du formulaire digital (récupère via `rib_lookup.php`)
  - `cso/formulaire_produits.php` — page d'accès (sélecteur + redirection)
  - `cso/rib.php` — interface pour générer un RIB (saisie compte + clé)
  - `cso/rib_ecobank.html` — modèle imprimable du RIB (utilise `rib_lookup.php`)

- Endpoints (API interne) :
  - `cso/save_ecobank_form.php` — accepte POST (JSON ou form-encoded), normalise et enregistre la soumission
  - `cso/search_compte.php` — cherche un compte dans `tblCompte` par `q` (POST/GET)
  - `cso/rib_lookup.php` — recherche les données d'un compte et compose l'IBAN/RIB en JSON

- Utilitaires / migrations / scripts :
  - `cso/ensure_ecobank_columns.php` — ajoute colonnes manquantes à `ecobank_form_submissions`
  - `cso/backfill_account_numbers.php` — script de backfill pour populater `account_number`

- Tables / DB :
  - `ecobank_form_submissions` (crée par `save_ecobank_form.php`) — snapshot JSON et colonnes utiles
  - `tblCompte` — table de comptes utilisée pour recherche & mise à jour (upsert depuis enregistrement)

---

## 2) Flux de données (cas d'utilisation typique) 🔁
1. L'utilisateur ouvre le formulaire (ex: `ecobank_account_form.php`).
2. Il peut **générer**/pré-remplir à partir d'un numéro via `search_compte.php` (POST `q`) qui renvoie une ligne de `tblCompte`.
3. L'utilisateur modifie/complète et clique sur **Enregistrer** — le navigateur envoie un POST JSON à `save_ecobank_form.php`.
4. `save_ecobank_form.php` :
   - vérifie la session (auth)
   - normalise les champs et dates
   - crée la table `ecobank_form_submissions` si nécessaire et ajoute les colonnes manquantes
   - enregistre une ligne (colonnes utiles + champ `data` en JSON)
   - fait un upsert basique dans `tblCompte` (recherche par email/mobile/emp_id)
   - retourne JSON {status: 'ok', submission_id, ...}
5. Pour le RIB, `rib.php` / `rib_ecobank.html` appellent `rib_lookup.php?account=...` qui retourne :
   - `account` (country_code, bank_code, branch_code, account_number, rib_key, iban)
   - `customer` (nom, email, mobile)
   - `bank` (nom, adresse)
   - `correspondents` (liste de Nostro/Correspondants)
   - La page imprime ensuite un RIB formaté.

---

## 3) Schéma de stockage et points importants 🗄️
- Table principale : `ecobank_form_submissions` (colonnes créées dynamiquement par le script). Colonnes fréquemment utilisées :
  - `id`, `customer_id`, `account_number`, `bank_account_number`, `customer_name`, `mobile`, `email`, `data` (JSON), `created_at`.
  - Le script ajoute un grand nombre de colonnes (noms, dates, id_type, branch_code, account_type, services, etc.) pour faciliter les recherches.
- Table `tblCompte` est utilisée pour retrouver et garder un index des comptes (recherches rapides via `search_compte.php`).
- Les sauvegardes écrivent aussi des logs locaux (`save_ecobank_form_debug.log`) et gèrent proprement les erreurs JSON.

---

## 4) Points techniques / sécurité ⚠️
- L'authentification est faite via `session.php` (vérification dans `save_ecobank_form.php` et autres pages). Les endpoints renvoient 401 si la session manque.
- `save_ecobank_form.php` accepte JSON ou form-encoded et normalise beaucoup de variantes de noms de champs (robuste pour différents frontends).
- Les requêtes DB utilisent des requêtes préparées (mysqli_prepare) dans la majorité des cas — bonne pratique contre l'injection SQL.
- Attention : certains scripts écrivent des ALTER TABLE automatiquement. Testez sur un environnement de staging avant production.

---

## 5) Comment générer / tester (mode opératoire) 🧭
- Pour pré-remplir un formulaire : ouvrir `cso/ecobank_account_form.php` → saisir numéro → **Générer** (envoie à `search_compte.php`).
- Pour enregistrer une soumission : remplir le formulaire → cliquer sur le bouton **Save** (JS envoie JSON à `save_ecobank_form.php`).
- Pour générer un RIB imprimable : ouvrir `cso/rib.php` → saisir `account` + `rib_key` (optionnel) → **Générer** → s'ouvre `cso/rib_ecobank.html` pré-rempli.
- Pour le formulaire digital : `cso/formulaire_produits.php` → saisir account + branch → ouvre `cso/formulaire_produits.html?account=...` qui fait `GET rib_lookup.php`.

---

## 6) Recommandations / améliorations possibles 💡
- Ajouter des tests automatisés (unit / integration) pour `save_ecobank_form.php` (vérifier normalisation et insert fallback).
- Centraliser la logique de migrations (au lieu d'ALTER TABLE exécutés en temps réel) et versionner les migrations.
- Ajouter une API REST documentée (OpenAPI) pour `search_compte`, `save_ecobank_form`, `rib_lookup` pour faciliter intégrations externes.
- Ajouter une politique d'archivage/purge pour les anciens JSON stockés dans `data`.

---

## 7) Fichiers à consulter en priorité 📁
- `cso/save_ecobank_form.php` — logique d'enregistrement
- `cso/rib_lookup.php` — logique de composition RIB/IBAN
- `cso/ecobank_account_form.php` & `cso/Ecobank Account Opening Form Customer.html` — UI + mapping JS
- `cso/formulaire_produits.html` / `cso/rib_ecobank.html` — modèles imprimables

---

## Diagrammes visuels 🗺️

### 1) Diagramme de flux (ouverture → enregistrement → génération RIB)

<svg width="700" height="220" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 700 220">
  <style> .box{fill:#f4f8fb;stroke:#007eb6;stroke-width:1.6;} .t{font:14px/1.2 Arial, Helvetica, sans-serif; fill:#033b56;} .arrow{stroke:#555;stroke-width:2;fill:none;marker-end:url(#arr);} </style>
  <defs>
    <marker id="arr" markerWidth="10" markerHeight="10" refX="8" refY="5" orient="auto"><path d="M0,0 L10,5 L0,10 z" fill="#555"/></marker>
  </defs>
  <rect x="20" y="20" rx="6" ry="6" width="160" height="40" class="box"/>
  <text x="100" y="45" text-anchor="middle" class="t">Formulaire UI</text>

  <rect x="240" y="20" rx="6" ry="6" width="180" height="40" class="box"/>
  <text x="330" y="45" text-anchor="middle" class="t">search_compte.php</text>

  <rect x="490" y="20" rx="6" ry="6" width="160" height="40" class="box"/>
  <text x="570" y="45" text-anchor="middle" class="t">Remplir Formulaire</text>

  <path class="arrow" d="M180,40 L240,40" />
  <path class="arrow" d="M420,40 L490,40" />

  <rect x="240" y="90" rx="6" ry="6" width="200" height="40" class="box"/>
  <text x="340" y="115" text-anchor="middle" class="t">save_ecobank_form.php</text>
  <path class="arrow" d="M330,60 L330,90" />

  <rect x="470" y="90" rx="6" ry="6" width="180" height="40" class="box"/>
  <text x="560" y="115" text-anchor="middle" class="t">ecobank_form_submissions</text>
  <path class="arrow" d="M440,110 L470,110" />

  <rect x="20" y="150" rx="6" ry="6" width="220" height="40" class="box"/>
  <text x="130" y="175" text-anchor="middle" class="t">tblCompte (upsert)</text>
  <path class="arrow" d="M350,130 L250,170" />

  <rect x="470" y="150" rx="6" ry="6" width="200" height="40" class="box"/>
  <text x="570" y="175" text-anchor="middle" class="t">rib_lookup.php → RIB/IBAN</text>
  <path class="arrow" d="M650,110 L570,150" />
</svg>

*Légende :* le formulaire interroge `search_compte.php`, puis `save_ecobank_form.php` enregistre un snapshot et met à jour `tblCompte`. `rib_lookup.php` compose le RIB pour impression.


### 2) Diagramme Schéma (tables & relations simplifiées)

<svg width="700" height="220" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 700 220">
  <style>.tbl{fill:#fff9e6;stroke:#d49b00;stroke-width:1.2;} .hdr{font-weight:700;font:13px/1.2 Arial, Helvetica, sans-serif; fill:#5a3700;} .cell{font:12px/1.2 Arial, Helvetica, sans-serif; fill:#333;} .link{stroke:#666;stroke-width:1.6;fill:none;marker-end:url(#arr2);} </style>
  <defs><marker id="arr2" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 z" fill="#666"/></marker></defs>

  <rect x="30" y="20" width="260" height="80" rx="6" class="tbl"/>
  <text x="40" y="38" class="hdr">ecobank_form_submissions</text>
  <text x="40" y="58" class="cell">id, customer_id, account_number, bank_account_number</text>
  <text x="40" y="74" class="cell">customer_name, mobile, email, data(JSON), created_at</text>

  <rect x="360" y="20" width="300" height="80" rx="6" class="tbl"/>
  <text x="370" y="38" class="hdr">tblCompte</text>
  <text x="370" y="58" class="cell">id, account_number, noms, mobile1, email</text>
  <text x="370" y="74" class="cell">emp_id, services, type_compte</text>

  <path class="link" d="M290,60 L360,60" />
  <text x="310" y="50" class="cell">(recherche / upsert)</text>

  <rect x="30" y="120" width="230" height="60" rx="6" class="tbl"/>
  <text x="40" y="138" class="hdr">Audit / Logs</text>
  <text x="40" y="156" class="cell">save_ecobank_form_debug.log</text>

  <path class="link" d="M160,100 L160,120" />
</svg>

*Légende :* relations de recherche et d'upsert entre `ecobank_form_submissions` et `tblCompte`. Les logs aident au débogage.

---

Si tu veux, je peux :
- Générer un PDF à partir de ce Markdown pour que tu télécharges immédiatement ✅
- Ajouter un schéma visuel (diagramme ASCII ou image) dans le même fichier ✅


---

Fichier généré : `docs/architecture_ouverture_compte.md`

AC_OPEN_DATE
P_NATIONAL_ID
PASSPORT_NO

