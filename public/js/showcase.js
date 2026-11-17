/**
 * ============================================================================
 * VITRINE PLEIN ÉCRAN - SHOWCASE CAROUSEL
 * Vanilla JavaScript - Zéro dépendance - Accessibilité WCAG AA
 * ============================================================================
 */
(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Constante facilement modifiable : Durée d'affichage (en millisecondes)
    // -------------------------------------------------------------------------
    const AUTOPLAY_DURATION = 6000;

    document.addEventListener('DOMContentLoaded', initShowcase);

    function initShowcase() {
        const viewport = document.querySelector('.showcase-viewport');
        if (!viewport) {
            return;
        }

        const slides = Array.from(viewport.querySelectorAll('.showcase-slide'));
        const totalSlides = slides.length;

        // Cas limites : aucun ou 1 seul article en stock
        if (totalSlides <= 1) {
            viewport.classList.add('is-single');
            return;
        }

        // Éléments d'interface
        const prevBtn = viewport.querySelector('.showcase-arrow-btn.prev');
        const nextBtn = viewport.querySelector('.showcase-arrow-btn.next');
        const dots = Array.from(viewport.querySelectorAll('.showcase-dot'));
        const playbackBtn = viewport.querySelector('.showcase-btn-playback');
        const progressBar = viewport.querySelector('.showcase-progress-bar');
        const counterCurrent = viewport.querySelector('.showcase-counter .current');
        const liveRegion = viewport.querySelector('.showcase-live-region');

        // Détection de la préférence utilisateur pour la réduction des animations
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Variables d'état
        let currentIndex = 0;
        let timer = null;
        let isUserPaused = prefersReducedMotion;
        let isInteracting = false;
        let touchStartX = 0;
        let touchStartY = 0;

        // Ajustement dynamique de la hauteur de la barre de navigation
        adjustNavbarHeight();
        window.addEventListener('resize', adjustNavbarHeight);

        // Initialisation de la première diapositive
        updateState(0, false);

        if (!prefersReducedMotion) {
            startAutoplay();
        } else {
            setPausedState(true);
        }

        // ---------------------------------------------------------------------
        // Gestion des événements Utilisateur
        // ---------------------------------------------------------------------

        // Flèche Précédent
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                goToSlide(currentIndex - 1, true);
            });
        }

        // Flèche Suivant
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                goToSlide(currentIndex + 1, true);
            });
        }

        // Points de pagination
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                const targetIndex = parseInt(dot.getAttribute('data-index'), 10);
                if (!isNaN(targetIndex) && targetIndex !== currentIndex) {
                    goToSlide(targetIndex, true);
                }
            });
        });

        // Bouton Pause / Lecture (Exigence accessibilité WCAG 2.2.2)
        if (playbackBtn) {
            playbackBtn.addEventListener('click', function () {
                if (isUserPaused) {
                    isUserPaused = false;
                    setPausedState(false);
                    startAutoplay();
                } else {
                    isUserPaused = true;
                    setPausedState(true);
                    stopAutoplay();
                }
            });
        }

        // Pause au survol souris
        viewport.addEventListener('mouseenter', function () {
            if (!isUserPaused) {
                isInteracting = true;
                setPausedState(true);
                stopAutoplay();
            }
        });

        viewport.addEventListener('mouseleave', function () {
            if (!isUserPaused) {
                isInteracting = false;
                setPausedState(false);
                startAutoplay();
            }
        });

        // Pause au focus clavier (tabulation) à l'intérieur du composant
        viewport.addEventListener('focusin', function () {
            if (!isUserPaused) {
                isInteracting = true;
                setPausedState(true);
                stopAutoplay();
            }
        });

        viewport.addEventListener('focusout', function (event) {
            // Reprendre seulement si le nouveau focus est hors du viewport
            if (!viewport.contains(event.relatedTarget) && !isUserPaused) {
                isInteracting = false;
                setPausedState(false);
                startAutoplay();
            }
        });

        // Navigation au clavier (Flèches Gauche/Droite)
        viewport.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                goToSlide(currentIndex - 1, true);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                goToSlide(currentIndex + 1, true);
            }
        });

        // Geste tactile sur mobile (Swipe)
        viewport.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
            if (!isUserPaused) {
                stopAutoplay();
            }
        }, { passive: true });

        viewport.addEventListener('touchend', function (e) {
            const touchEndX = e.changedTouches[0].screenX;
            const touchEndY = e.changedTouches[0].screenY;
            const diffX = touchEndX - touchStartX;
            const diffY = touchEndY - touchStartY;

            // Détection du glissement horizontal significatif (> 40px)
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 40) {
                if (diffX < 0) {
                    goToSlide(currentIndex + 1, true);
                } else {
                    goToSlide(currentIndex - 1, true);
                }
            }

            if (!isUserPaused && !isInteracting) {
                startAutoplay();
            }
        }, { passive: true });

        // ---------------------------------------------------------------------
        // Fonctions Métier du Carrousel
        // ---------------------------------------------------------------------

        function goToSlide(newIndex, fromManualInteraction) {
            // Gestion de la boucle infinie
            if (newIndex >= totalSlides) {
                newIndex = 0;
            } else if (newIndex < 0) {
                newIndex = totalSlides - 1;
            }

            updateState(newIndex, fromManualInteraction);

            // Redémarrage de la jauge et du compte à rebours
            if (!isUserPaused && !isInteracting) {
                restartAutoplay();
            }
        }

        function updateState(index, announcedByScreenReader) {
            currentIndex = index;

            // Mise à jour des classes, d'aria-hidden et d'inert
            slides.forEach(function (slide, i) {
                if (i === currentIndex) {
                    slide.classList.add('is-active');
                    slide.removeAttribute('aria-hidden');
                    slide.removeAttribute('inert');
                } else {
                    slide.classList.remove('is-active');
                    slide.setAttribute('aria-hidden', 'true');
                    slide.setAttribute('inert', '');
                }
            });

            // Mise à jour des points de pagination
            dots.forEach(function (dot, i) {
                if (i === currentIndex) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });

            // Mise à jour du compteur textuel (ex : "02")
            if (counterCurrent) {
                const formattedNumber = String(currentIndex + 1).padStart(2, '0');
                counterCurrent.textContent = formattedNumber;
            }

            // Réinitialisation de l'animation de la barre de progression
            if (progressBar) {
                progressBar.classList.remove('is-animating');
                void progressBar.offsetWidth; // Force le reflow CSS
                if (!isUserPaused && !isInteracting) {
                    progressBar.classList.add('is-animating');
                }
            }

            // Annonce pour les lecteurs d'écran lors d'une interaction manuelle
            if (liveRegion && announcedByScreenReader) {
                const activeSlide = slides[currentIndex];
                const titleElement = activeSlide.querySelector('.showcase-title');
                const priceElement = activeSlide.querySelector('.showcase-price');
                const title = titleElement ? titleElement.textContent.trim() : '';
                const price = priceElement ? priceElement.textContent.trim() : '';
                liveRegion.setAttribute('aria-live', 'polite');
                liveRegion.textContent = `Article ${currentIndex + 1} sur ${totalSlides} : ${title}, ${price}.`;

                // Éteindre le aria-live après l'annonce pour ne pas perturber l'autoplay
                setTimeout(function () {
                    liveRegion.setAttribute('aria-live', 'off');
                }, 1000);
            }
        }

        function startAutoplay() {
            stopAutoplay();
            viewport.classList.remove('is-paused');
            viewport.classList.add('is-playing');

            if (progressBar) {
                progressBar.classList.add('is-animating');
            }

            timer = setInterval(function () {
                goToSlide(currentIndex + 1, false);
            }, AUTOPLAY_DURATION);
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function restartAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        function setPausedState(isPaused) {
            if (isPaused) {
                viewport.classList.remove('is-playing');
                viewport.classList.add('is-paused');
                if (playbackBtn) {
                    playbackBtn.setAttribute('aria-label', 'Reprendre le défilement automatique');
                    playbackBtn.setAttribute('title', 'Reprendre le défilement');
                    playbackBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                    `;
                }
            } else {
                viewport.classList.remove('is-paused');
                viewport.classList.add('is-playing');
                if (playbackBtn) {
                    playbackBtn.setAttribute('aria-label', 'Mettre en pause le défilement automatique');
                    playbackBtn.setAttribute('title', 'Mettre en pause');
                    playbackBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                    `;
                }
            }
        }

        function adjustNavbarHeight() {
            const header = document.querySelector('.site-header');
            if (header) {
                const height = header.offsetHeight;
                document.documentElement.style.setProperty('--navbar-h', height + 'px');
            }
        }
    }
})();
