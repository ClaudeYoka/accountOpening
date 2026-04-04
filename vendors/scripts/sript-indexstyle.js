// JavaScript pour une interface interactive
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des boutons de rôle
            const roleBtns = document.querySelectorAll('.select-role .btn');
            roleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    roleBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Animation du formulaire de connexion
            const loginForm = document.getElementById('loginForm');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');

            loginForm.addEventListener('submit', function(e) {
                if (btnText) {
                    btnText.style.display = 'none';
                }
                if (btnLoading) {
                    btnLoading.style.display = 'inline-block';
                }
                // Le formulaire se soumet normalement
            });

            // Effets visuels sur les inputs
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Animation de particules en arrière-plan (optionnel)
            function createParticle() {
                const particle = document.createElement('div');
                particle.style.position = 'absolute';
                particle.style.width = '4px';
                particle.style.height = '4px';
                particle.style.background = 'rgba(255, 255, 255, 0.3)';
                particle.style.borderRadius = '50%';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = '100%';
                particle.style.animation = 'floatUp ' + (Math.random() * 10 + 10) + 's linear infinite';
                document.body.appendChild(particle);

                setTimeout(() => {
                    particle.remove();
                }, 15000);
            }

            // Créer des particules toutes les 2 secondes
            setInterval(createParticle, 2000);

            // CSS pour l'animation des particules
            const style = document.createElement('style');
            style.textContent = `
                @keyframes floatUp {
                    to {
                        transform: translateY(-100vh);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });