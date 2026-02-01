# ✨ Utilisation du Préremplissage Flexcube

## 🎯 Comment ça marche?

Ouvrez le formulaire d'ouverture de compte:
```
http://localhost/account opening/cso/ecobank_account_form.php
```

Saisissez un numéro de compte dans le champ "Bank Account Number":
```
37220020391
```

Cliquez le bouton **"Générer"** (ou appuyez Entrée):

Le système va:
1. ✅ Chercher le compte dans **Flexcube API** (Ecobank)
2. ✅ Si pas trouvé, chercher dans la **BD locale**
3. ✅ **Préremplir le formulaire** automatiquement

---

## 📊 Messages Affichés

| Message | Signification |
|---------|-------------|
| "Recherche en cours..." | En cours de recherche |
| "Recherche via API Flexcube..." | Appel API Flexcube |
| "✓ Compte trouvé via flexcube" | Trouvé dans Flexcube |
| "✓ Dossier trouvé et rempli." | Trouvé dans BD locale |
| "✓ Formulaire prérempli avec succès" | Rempli avec succès |
| "❌ Compte introuvable" | Pas trouvé nulle part |

---

## 🔑 Numéros de Test

Essayez avec ces numéros:

| Numéro | Source | Notes |
|--------|--------|-------|
| `37220020391` | Flexcube | Compte de test Ecobank |
| `37220020392` | Flexcube | Autre compte test |
| [Locaux] | BD | Dépend de vos données |

---

## ⌨️ Raccourcis

| Action | Raccourci |
|--------|----------|
| Déclencher recherche | Cliquer "Générer" |
| Déclencher recherche | Appuyer **Entrée** |

---

## 🎨 Personnalisation

### Changer les messages
Modifiez dans `ecobank_account_form.php`:
```javascript
showMessage('Votre message ici', false); // false = vert, true = rouge
```

### Changer les couleurs
Modifiez dans le HTML:
```css
#generateMessage {
  color: #099; /* vert = succès */
  color: #d00; /* rouge = erreur */
}
```

---

## 🐛 Problèmes Courants

### Q: "Compte non trouvé"
**R:** Le numéro n'existe pas dans Flexcube ou BD. Vérifiez le numéro.

### Q: "Erreur réseau"
**R:** Vérifiez votre connexion internet. Réessayez.

### Q: Formulaire ne se remplit pas
**R:** Ouvrez F12 > Console pour voir les erreurs JavaScript.

### Q: Seul le premier champ se remplit
**R:** Les IDs des champs doivent correspondre. Consultez `AUTO_FILL_GUIDE.md`.

---

## 📞 Besoin d'aide?

Consultez:
- **AUTO_FILL_GUIDE.md** - Documentation complète
- **FLEXCUBE_INTEGRATION.md** - API Flexcube
- **flexcube_test.php** - Pour tester l'API directement

---

## ✅ Checklist Utilisateur

- [ ] Formulaire ouvert
- [ ] Numéro de compte saisi
- [ ] Bouton "Générer" cliqué
- [ ] Messages affichés
- [ ] Formulaire prérempli ✓

---

**Status:** ✅ Opérationnel
