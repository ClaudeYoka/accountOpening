<script>
    // Configuration des notifications
    let lastChecked = '<?php echo date('Y-m-d H:i:s'); ?>';
    let notificationSoundPlayed = false;

    function playNotificationSound() {
        // Utilisation d'un son de notification basique intégré
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(800, audioCtx.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(400, audioCtx.currentTime + 0.3);
        
        gainNode.gain.setValueAtTime(1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.1, audioCtx.currentTime + 0.3);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.5);
    }

    function showDesktopNotification(message) {
        const notification = document.getElementById('desktop-notification');
        document.getElementById('notification-text').textContent = message;
        notification.style.display = 'block';
        
        // Animation d'apparition
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        }, 100);
        
        // Disparaît après 5 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(20px)';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 300);
        }, 5000);
    }

    // Vérifie les nouvelles notifications périodiquement (toutes les 30 secondes)
    setInterval(() => {
        fetch(`check_new_notifications.php?emp_id=<?php echo $session_id; ?>&last_checked=${lastChecked}`)
            .then(response => response.json())
            .then(data => {
                if(data.new_notifications > 0) {
                    // Jouer le son uniquement pour la première notification non lue
                    if(!notificationSoundPlayed) {
                        playNotificationSound();
                        notificationSoundPlayed = true;
                    }
                    
                    // Afficher la notification la plus récente
                    showDesktopNotification(data.latest_message);
                    
                    // Mettre à jour le compteur UI
                    document.querySelector('.notification-active').textContent = data.unread_count;
                    
                    // Mettre à jour la date de dernière vérification
                    lastChecked = data.last_created_at;
                }
            })
            .catch(error => console.error('Erreur:', error));
    }, 30000); // 30 secondes

    // Réinitialiser le son après interaction utilisateur
    document.querySelector('.dropdown-toggle').addEventListener('click', function() {
        notificationSoundPlayed = false;
    });

    // Style initial pour l'animation
    document.addEventListener('DOMContentLoaded', function() {
        const notification = document.getElementById('desktop-notification');
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(20px)';
        notification.style.transition = 'all 0.3s ease-in-out';
    });
</script>


<style>
    /* Toast notification styles */
    .notification-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #fff;
        border-left: 4px solid #1b55e2;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 15px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        max-width: 350px;
        transform: translateX(calc(100% + 20px));
        transition: transform 0.3s ease-in-out;
        z-index: 9999;
    }
    
    .notification-toast.show {
        transform: translateX(0);
    }
    
    .toast-icon {
        margin-right: 15px;
        font-size: 24px;
        color: #1b55e2;
    }
    
    .toast-content {
        flex: 1;
    }
    
    .toast-title {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #1a173b;
    }
    
    .toast-message {
        margin: 0;
        font-size: 14px;
        color: #6c757d;
    }
</style>

