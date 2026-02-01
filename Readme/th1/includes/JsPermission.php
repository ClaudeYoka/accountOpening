<script>
    const picker = document.getElementById('date_from');
    picker.addEventListener('input', function(e){
        var day = new Date(this.value).getUTCDay();
        if([6,0].includes(day)){
            e.preventDefault();
            this.value = '';
            alert('Weekends non pris en charge');
        } else {
            calc();
        }
    });

    const pickers = document.getElementById('date_to');
    pickers.addEventListener('input', function(e){
        var day = new Date(this.value).getUTCDay();
        if([6,0].includes(day)){
            e.preventDefault();
            this.value = '';
            alert('Weekends non pris en charge');
        } else {
            calc();
        }
    });

    function calc() {
        const date_to = document.getElementById('date_to');
        const date_from = document.getElementById('date_from');
        const startDate = new Date(date_from.value);
        const endDate = new Date(date_to.value);
        
        // Réinitialiser les champs
        document.getElementById("requested_days").value = '';
        document.getElementById("requested_hours").value = '';

        if (startDate.toDateString() === endDate.toDateString()) {
            // Si les dates sont les mêmes, calculer les heures
            const startTime = startDate.getHours() + startDate.getMinutes() / 60; // Convertir en heures
            const endTime = endDate.getHours() + endDate.getMinutes() / 60; // Convertir en heures
            const hoursRequested = endTime - startTime;
            document.getElementById("requested_hours").value = hoursRequested > 0 ? hoursRequested : 0; // Assurez-vous que le résultat est positif
            document.getElementById("requested_days").value = 0;
        } else {
            // Si les dates sont différentes, calculer les jours
            const result = getBusinessDateCount(startDate, endDate);
            document.getElementById("requested_days").value = result;
        }
        
    
    }

    function getBusinessDateCount(startDate, endDate) {
        // Commencer à compter à partir du jour suivant
        startDate.setDate(startDate.getDate() + 1); // Ajout de 1 pour commencer à partir du jour suivant

        let count = 0;

        // Boucle à travers les jours entre startDate et endDate
        while (startDate <= endDate) {
            const day = startDate.getUTCDay();
            // Compter uniquement les jours de semaine (lundi à vendredi)
            if (day !== 0 && day !== 6) {
                count++;
            }
            startDate.setDate(startDate.getDate() + 1); // Passer au jour suivant
        }

        return count;
    }

</script>
