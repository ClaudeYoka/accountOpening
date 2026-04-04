# Documentation - Structure en Sections du Formulaire Ecobank

## 📋 Vue d'ensemble

Le formulaire d'ouverture de compte Ecobank a été réorganisé avec une navigation claire en **4 sections** pour une meilleure expérience utilisateur lors du remplissage du formulaire.

## 🎯 Structure des Sections

### **Section 1: Vos besoins bancaires**
- Services financiers d'intérêt (Assurance, Épargne, Paiements, Prêts)
- Type de compte souhaité (Courant, Épargne)
- Objectif principal du compte
- Devise préférée
- Méthodes d'accès aux services bancaires

### **Section 2: Informations personnelles**
- Genre et titre civil
- Nom, prénom, deuxième prénom
- Informations sur les parents
- Nationalité et pays de résidence
- Date de naissance
- Pièce d'identité et numéro du document
- Informations fiscales
- **Contact:** Téléphone, email, adresse
- Personne à contacter en cas d'urgence

### **Section 3: Votre activité professionnelle**
- Salarié (employeur, conditions d'emploi, revenu)
- Entreprise/ONG (raison sociale, RCCM, NIF, nature des activités)
- Étudiant (établissement, identifiant)
- Autre activité

### **Section 4: Signatures et informations bancaires**
- Engagements et conditions générales
- Confirmation pour demandes assistées
- Empreinte digitale et signatures
- Informations réservées à la banque (numéro de compte, ID client, codes agence)

## 🎨 Interface d'affichage

### Navigation en haut (Sticky)
- Barre de navigation flottante avec 4 boutons "Section 1, 2, 3, 4"
- **Section active** = bouton en bleu (#007eb6)
- Clic sur un bouton = affichage instantané de cette section

### Boutons de navigation entre sections
Chaque section dispose de boutons pour naviguer:
- **Section 1:** Bouton "Suivant" (→ Section 2)
- **Section 2:** Boutons "Précédent" et "Suivant"
- **Section 3:** Boutons "Précédent" et "Suivant"
- **Section 4:** Boutons "Précédent" et "Terminer & Imprimer"

## 🖨️ Impression

**Comportement spécial à l'impression:**
- ✅ La barre de navigation **disparaît**
- ✅ Les boutons "Suivant/Précédent" **disparaissent**
- ✅ **Toutes les 4 sections** s'affichent complètement sur le document imprimé
- ✅ Les sections s'impriment avec leurs en-têtes ("Section 1", etc.)
- ✅ Le formulaire conserve son style visuel lors de l'impression

## 💻 Code technique

### CSS Classes
```css
.section-navigation          /* Barre de navigation principale */
.section-nav-item           /* Boutons de section */
.section-nav-buttons        /* Boutons Suivant/Précédent */
.form-section-group         /* Groupe de formulaire (une section) */
.section-number-header      /* En-tête avec numéro de section */
.section-number             /* Numéro de section en badge */
.section-title-main         /* Titre de section */
```

### JavaScript Functions
```javascript
window.showSection(num)      /* Afficher une section spécifique */
window.nextSection()         /* Aller à la section suivante */
window.prevSection()         /* Aller à la section précédente */
window.currentFormSection()  /* Retourne le numéro de section actuelle */
```

### Points clés d'impression
- `@media print` - Masque navigation et boutons
- `page-break-inside: avoid` - Évite les coupures de contenu
- Redimensionnement automatique du formulaire pour ≤ 5 pages

## 🚀 Fonctionnalités

✨ **Pour le remplissage du formulaire:**
- Navigation claire et intuitive entre sections
- Barre sticky (reste visible en haut)
- Indicateur visuel de la section active
- Boutons "Précédent" et "Suivant" pour un flux naturel
- Scroll automatique vers la section sélectionnée

📄 **À l'impression:**
- Vue complète du formulaire (toutes sections visibles)
- Aucun élément de navigation n'apparaît
- Mise en page optimisée pour l'impression
- Conserve tous les styles de formulaire

## 📱 Responsive Design

La navigation et les sections s'adaptent sur mobile:
- Boutons de navigation réduisent leur taille
- Layout flexible
- Adaptation au-dessus de 768px

## ⚙️ Configuration

Tous les paramètres sont configurables dans le CSS:
```css
/* Couleur Ecobank primary */
background-color: #007eb6;

/* Nombre maximum de sections */
const maxSection = 4;

/* Animations */
animation: fadeIn 0.3s ease-in;
```

## 🔧 Maintenance

Si vous ajoutez/supprimez des sections:
1. Mettre à jour `const maxSection` dans le JavaScript
2. Ajouter/retirer les divs `form-section-group`
3. Ajouter/retirer les boutons dans `.section-nav-container`

---

**Dernière mise à jour:** 23 janvier 2026  
**Version:** 1.0  
**Compatibilité:** Tous les navigateurs modernes + impression
