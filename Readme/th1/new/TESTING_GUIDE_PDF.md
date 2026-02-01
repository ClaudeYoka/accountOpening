# Guide de Test - Fonctionnalité PDF pour les Comptes

## Résumé des changements implémentés

Trois fichiers ont été créés/modifiés pour implémenter la fonctionnalité de génération PDF :

1. **`staff/view_compte.php`** - Page de détails d'un dossier
2. **`staff/generate_pdf.php`** - Générateur PDF pour les dossiers
3. **`db/create_tblCompte.sql`** - Schéma de la table (à exécuter si pas encore fait)

---

## Étape 1 : Vérifier la base de données

### 1.1 Créer la table `tblCompte` (si pas encore fait)

Exécutez le fichier SQL dans votre gestionnaire de base de données (phpMyAdmin ou MySQL CLI) :

```bash
# Via MySQL CLI
mysql -u root -p ecoleaves < db/create_tblCompte.sql
```

Ou copiez-collez le contenu de `db/create_tblCompte.sql` dans phpMyAdmin.

**La table doit contenir 33 colonnes :** id, emp_id, firstname, services, type_compte, devise_pref, objectif, access, titre, noms, prenom2, nationalite, lieu_naiss, pays, dob, genre, situation, id_type, id_num, date_deliv, date_exp, fiscal_pays, nip, mobile1, mobile2, email, adr_rue, ville, adr_pays, employeur, cond, revenu, etabliss, ident_etud, date_enregistrement.

---

## Étape 2 : Tester le formulaire d'application

### 2.1 Accédez au formulaire de suivi

1. Connectez-vous en tant qu'employé
2. Allez à **`staff/account_part.php`** (ou via le menu si disponible)
3. Remplissez TOUS les champs du formulaire (les champs obligatoires et optionnels)
4. Cliquez sur **"Soumettre"**

### 2.2 Vérifier la soumission

- Un message de succès s'affiche : **"Dossier créé avec succès"** + redirection vers account_part.php
- **OU** un message d'erreur de validation s'affiche (ex. : "Adresse email invalide")

### 2.3 Vérifier les données dans la base

Depuis phpMyAdmin ou MySQL, vérifiez que le dossier a été inséré dans `tblCompte` :

```sql
SELECT * FROM tblCompte ORDER BY id DESC LIMIT 1;
```

---

## Étape 3 : Accéder à la liste des dossiers (Admin)

### 3.1 Consultez la liste

1. Allez à **`staff/list_tblCompte.php`**
2. Vous devriez voir une table avec les colonnes : ID, Emp ID, Nom, Email, Mobile, Ville, Date Enregistrement
3. Les 200 dossiers les plus récents s'affichent

### 3.2 Filtrer/Trier

- Les dossiers sont triés par date d'enregistrement (plus récents d'abord)
- Chaque ligne a un bouton **"Voir"** qui ouvre le détail du dossier

---

## Étape 4 : Consulter les détails d'un dossier

### 4.1 Cliquez sur "Voir"

1. Depuis la liste (`list_tblCompte.php`), cliquez sur **"Voir"** pour un dossier
2. Vous êtes redirigé vers **`staff/view_compte.php?id=<ID>`**
3. La page affiche **TOUS les champs** du dossier organisés en sections :
   - Informations Principales
   - Informations Personnelles
   - Adresse et Document d'Identité
   - Services et Compte
   - Informations Fiscales & Emploi
   - Autres Informations

### 4.2 Boutons d'action

Trois boutons sont disponibles en haut à droite :
- **Imprimer** : Ouvre la boîte de dialogue d'impression du navigateur
- **Télécharger PDF** : Génère et télécharge un fichier PDF du dossier
- **Retour** : Revient à la liste des dossiers

---

## Étape 5 : Générer et télécharger le PDF

### 5.1 Cliquez sur "Télécharger PDF"

1. Depuis la page de détails (`view_compte.php`), cliquez sur **"Télécharger PDF"**
2. Votre navigateur télécharge un fichier nommé : `compte_<ID>_<EMP_ID>.pdf`
   - Exemple : `compte_5_101.pdf`

### 5.2 Vérifier le contenu du PDF

Le PDF généré doit contenir :
- Titre : "FORMULAIRE D'OUVERTURE DE COMPTE"
- Sous-titre : "Ecotracking Banking Services"
- **6 sections** avec tous les champs préremplis des données du dossier
- Zones de signature en bas
- Date/heure de génération et ID du dossier en pied de page

### 5.3 Tester l'impression

1. Depuis la page de détails, cliquez sur **"Imprimer"**
2. La boîte de dialogue d'impression du navigateur s'ouvre
3. Vous pouvez imprimer la page ou l'exporter en PDF

---

## Étape 6 : Vérifier les validations serveur

### 6.1 Tester des données invalides

Remplissez le formulaire avec des données invalides et soumettez :

| Champ | Données Invalides | Résultat Attendu |
|-------|-------------------|------------------|
| Email | `invalidemail` | Erreur : "Adresse email invalide" |
| Mobile 1 | `123` | Erreur : "Numéro mobile invalide" |
| Mobile 2 | `abc123` | Erreur : "Numéro mobile invalide" |
| Date de Naissance | `01/01/1990` (format MM/DD/YYYY) | Erreur : "Format de date invalide" |
| Exp. Document | `2020-01-01` Deliv. `2025-01-01` | Erreur : "La date d'expiration doit être ≥ date de délivrance" |

### 6.2 Données valides

| Champ | Données Valides |
|-------|-----------------|
| Email | `john.doe@example.com` |
| Mobile | `+33612345678` ou `0612345678` |
| Date | `1990-01-15` (format YYYY-MM-DD) |

---

## Structure des Fichiers

```
staff/
├── account_part.php           ← Formulaire (déjà en place)
├── trackingController.php       ← Contrôleur (validations + insertion)
├── list_tblCompte.php           ← Liste des dossiers
├── view_compte.php              ← Détails du dossier (NOUVEAU)
└── generate_pdf.php             ← Générateur PDF (NOUVEAU)

db/
└── create_tblCompte.sql         ← Schéma de la table (NOUVEAU)

vendor/
└── dompdf/                       ← Bibliothèque PDF (déjà installée)
```

---

## Dépannage

### Erreur : "ID invalide"
- Vérifiez que l'URL contient un paramètre `?id=<numéro>`
- L'ID doit être un entier positif

### Erreur : "Dossier non trouvé"
- Le dossier avec cet ID n'existe pas dans la base
- Vérifiez le dossier dans `list_tblCompte.php`

### Erreur : "Column count doesn't match"
- Vérifiez que le schéma SQL de `tblCompte` a exactement 33 colonnes
- Le contrôleur attend exactement 33 paramètres pour le `bind_param`

### PDF ne génère pas
- Vérifiez que DOMPDF est installé : `vendor/dompdf/dompdf` doit exister
- Vérifiez que `require_once('../vendor/autoload.php');` est bien dans `generate_pdf.php`

### Données ne s'insèrent pas
- Vérifiez que le formulaire POST envoie tous les champs attendus
- Vérifiez les logs PHP pour les erreurs SQL
- Testez manuellement : `SELECT * FROM tblCompte;`

---

## Workflow Complet

```
1. Employé remplit account_part.php
                ↓
2. Soumet le formulaire (POST à trackingController.php)
                ↓
3. Contrôleur valide les données (email, téléphone, dates)
                ↓
4. Données insérées dans tblCompte (si valides)
                ↓
5. Succès : redirection avec alert
                ↓
6. Admin accède list_tblCompte.php pour voir tous les dossiers
                ↓
7. Admin clique "Voir" → view_compte.php
                ↓
8. Affichage des détails du dossier
                ↓
9. Admin clique "Télécharger PDF" ou "Imprimer"
                ↓
10. PDF généré avec dompdf et prêt à télécharger/imprimer
```

---

## Notes Importantes

- **Localisations :** Les sections et messages sont en **français**
- **Sécurité :** Toutes les données sont échappées avec `htmlspecialchars()` et `mysqli_real_escape_string()`
- **PDF :** Généré avec **DOMPDF** (déjà dans `vendor/`)
- **Format de date :** Le formulaire et les validations utilisent **YYYY-MM-DD** (ISO 8601)
- **CSV Storage :** Les checkboxes (arrays) sont stockées en CSV (ex. : `service1,service2,service3`)

---

## Résumé de l'Implémentation

✅ **Créé :** `staff/view_compte.php` - Page de détails avec boutons d'action  
✅ **Créé :** `staff/generate_pdf.php` - Générateur PDF avec DOMPDF  
✅ **Créé :** `db/create_tblCompte.sql` - Schéma complet de 33 colonnes  
✅ **Modifié :** `staff/trackingController.php` - Validations et insertion (déjà fait avant)  
✅ **Disponible :** `staff/list_tblCompte.php` - Liste des dossiers (déjà créé)  

La fonctionnalité **complète** de capture, stockage, affichage et export PDF des dossiers de compte est maintenant opérationnelle.
