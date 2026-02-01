<script>
// Fonction pour formater un nombre avec des séparateurs de milliers (espace)
function formatNumber(value) {
    if (!value) return '';  // Si la valeur est vide, retourner une chaîne vide
    // Convertir en nombre, formater et ajouter les espaces
    return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

// Fonction pour gérer l'événement d'entrée
function handleInput(event) {
    let input = event.target;
    let value = input.value.replace(/\s/g, '');  // Enlever les espaces existants
    value = value.replace(/\D/g, '');  // Enlever tout ce qui n'est pas un chiffre (pour éviter les erreurs)
    
    if (value) {
        let formattedValue = formatNumber(parseInt(value));  // Formater le nombre
        input.value = formattedValue;  // Mettre à jour la valeur avec le formatage
    } else {
        input.value = '';  // Si vide, laisser vide
    }
}

// Sélectionner les champs et appliquer les changements
document.addEventListener('DOMContentLoaded', function() {
    const mdoField = document.getElementById('mdo');
    const medField = document.getElementById('med');
    
    if (mdoField) {
        mdoField.type = 'text';  // Changer en type text pour permettre le formatage
        mdoField.addEventListener('input', handleInput);  // Ajouter l'écouteur d'événement
    }
    
    if (medField) {
        medField.type = 'text';  // Changer en type text
        medField.addEventListener('input', handleInput);  // Ajouter l'écouteur d'événement
    }
    
    // Nettoyer les valeurs avant la soumission du formulaire
    const form = document.querySelector('form');  // Sélectionner le formulaire
    if (form) {
        form.addEventListener('submit', function(event) {
            if (mdoField && mdoField.value) {
                mdoField.value = mdoField.value.replace(/\s/g, '');  // Enlever les espaces
            }
            if (medField && medField.value) {
                medField.value = medField.value.replace(/\s/g, '');  // Enlever les espaces
            }
        });
    }
});
</script>
