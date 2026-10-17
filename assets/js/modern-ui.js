/**
 * Interface utilisateur moderne pour Audit Accessibilité
 * Animations, interactions et micro-feedback
 * 
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Configuration globale
    const AuditAccessibiliteUI = {
        animations: {
            duration: 300,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)'
        },
        
        // Initialisation
        init: function() {
            this.setupAnimations();
            this.setupInteractions();
            this.setupProgressIndicators();
            this.setupTooltips();
            this.setupCardHovers();
            this.setupFormEnhancements();
            this.loadIcons();
        },

        // Charger les icônes SVG
        loadIcons: function() {
            if (!document.querySelector('#aa-icons-sprite')) {
                $('body').append('<div id="aa-icons-sprite" style="display: none;"></div>');
                $('#aa-icons-sprite').load('/wp-content/plugins/audit-accessibilite/assets/images/icons.svg');
            }
        },

        // Configuration des animations d'entrée
        setupAnimations: function() {
            // Observer d'intersection pour les animations au scroll
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('aa-animate-in');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '50px'
                });

                // Observer tous les éléments avec la classe d'animation
                $('.audit-accessibilite-box, .audit-accessibilite-issue, .audit-accessibilite-stat-box').each(function(index) {
                    $(this).attr('data-delay', index * 100);
                    observer.observe(this);
                });
            }

            // Animation de comptage pour les statistiques
            this.animateCounters();
        },

        // Animation des compteurs de statistiques
        animateCounters: function() {
            $('.audit-accessibilite-stat-value').each(function() {
                const $this = $(this);
                const target = parseInt($this.text().replace(/\D/g, '')) || 0;
                const duration = 1500;
                const start = 0;
                const startTime = performance.now();

                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const current = Math.floor(start + (target - start) * this.easeOutCubic(progress));
                    
                    $this.text(current.toLocaleString());
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };

                // Observer pour déclencher l'animation quand visible
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                requestAnimationFrame(animate);
                                observer.unobserve(entry.target);
                            }
                        });
                    });
                    observer.observe(this);
                }
            });
        },

        // Fonction d'easing
        easeOutCubic: function(t) {
            return 1 - Math.pow(1 - t, 3);
        },

        // Configuration des interactions
        setupInteractions: function() {
            // Effet de ripple sur les boutons
            this.setupRippleEffect();
            
            // Smooth scrolling pour les ancres
            this.setupSmoothScrolling();
            
            // Gestion des états de focus améliorés
            this.setupFocusStates();
        },

        // Effet ripple moderne sur les boutons
        setupRippleEffect: function() {
            $('.button-primary, .audit-accessibilite-admin .button').on('click', function(e) {
                const $button = $(this);
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const $ripple = $('<span class="aa-ripple"></span>');
                $ripple.css({
                    left: x + 'px',
                    top: y + 'px'
                });
                
                $button.append($ripple);
                
                setTimeout(() => {
                    $ripple.remove();
                }, 600);
            });

            // Ajouter les styles CSS pour l'effet ripple
            if (!$('#aa-ripple-styles').length) {
                $('head').append(`
                    <style id="aa-ripple-styles">
                        .button-primary, .audit-accessibilite-admin .button {
                            position: relative;
                            overflow: hidden;
                        }
                        .aa-ripple {
                            position: absolute;
                            border-radius: 50%;
                            background: rgba(255, 255, 255, 0.4);
                            transform: scale(0);
                            animation: aa-ripple-animation 0.6s ease-out;
                            pointer-events: none;
                            width: 20px;
                            height: 20px;
                            margin-left: -10px;
                            margin-top: -10px;
                        }
                        @keyframes aa-ripple-animation {
                            to {
                                transform: scale(4);
                                opacity: 0;
                            }
                        }
                    </style>
                `);
            }
        },

        // Smooth scrolling pour navigation
        setupSmoothScrolling: function() {
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800, 'easeInOutCubic');
                }
            });
        },

        // États de focus améliorés
        setupFocusStates: function() {
            // Améliorer la visibilité du focus
            $('input, select, textarea, button, .button').on('focus', function() {
                $(this).addClass('aa-focused');
            }).on('blur', function() {
                $(this).removeClass('aa-focused');
            });
        },

        // Indicateurs de progression
        setupProgressIndicators: function() {
            // Barre de progression pour les audits
            if ($('.audit-accessibilite-loading').length) {
                this.createProgressBar();
            }
        },

        // Créer une barre de progression moderne
        createProgressBar: function() {
            const progressHTML = `
                <div class="aa-progress-container">
                    <div class="aa-progress-bar">
                        <div class="aa-progress-fill"></div>
                    </div>
                    <div class="aa-progress-text">Analyse en cours...</div>
                </div>
            `;
            
            $('.audit-accessibilite-loading').prepend(progressHTML);
            
            // Simulation de progression (à remplacer par la vraie progression)
            this.simulateProgress();
        },

        // Simulation de progression (exemple)
        simulateProgress: function() {
            let progress = 0;
            const $progressFill = $('.aa-progress-fill');
            const $progressText = $('.aa-progress-text');
            
            const steps = [
                { percent: 20, text: 'Analyse des éléments HTML...' },
                { percent: 40, text: 'Vérification de l\'accessibilité...' },
                { percent: 60, text: 'Test des contrastes...' },
                { percent: 80, text: 'Génération du rapport...' },
                { percent: 100, text: 'Finalisation...' }
            ];
            
            steps.forEach((step, index) => {
                setTimeout(() => {
                    $progressFill.css('width', step.percent + '%');
                    $progressText.text(step.text);
                }, index * 2000);
            });
        },

        // Tooltips modernes
        setupTooltips: function() {
            // Créer des tooltips personnalisés
            $('[data-tooltip]').hover(
                function(e) {
                    const tooltipText = $(this).data('tooltip');
                    const $tooltip = $(`<div class="aa-tooltip">${tooltipText}</div>`);
                    
                    $('body').append($tooltip);
                    
                    const updatePosition = (e) => {
                        $tooltip.css({
                            left: e.pageX + 10,
                            top: e.pageY - $tooltip.outerHeight() - 10
                        });
                    };
                    
                    updatePosition(e);
                    $(this).on('mousemove.tooltip', updatePosition);
                    
                    setTimeout(() => $tooltip.addClass('aa-tooltip-visible'), 10);
                },
                function() {
                    $(this).off('mousemove.tooltip');
                    $('.aa-tooltip').removeClass('aa-tooltip-visible');
                    setTimeout(() => $('.aa-tooltip').remove(), 200);
                }
            );
        },

        // Effets de survol pour les cartes
        setupCardHovers: function() {
            $('.audit-accessibilite-box, .audit-accessibilite-issue').hover(
                function() {
                    $(this).addClass('aa-card-hover');
                },
                function() {
                    $(this).removeClass('aa-card-hover');
                }
            );
        },

        // Améliorations des formulaires
        setupFormEnhancements: function() {
            // Labels flottants
            this.setupFloatingLabels();
            
            // Validation en temps réel
            this.setupRealTimeValidation();
            
            // Indicateurs de force pour les mots de passe
            this.setupPasswordStrength();
        },

        // Labels flottants pour les champs
        setupFloatingLabels: function() {
            $('.audit-accessibilite-form input, .audit-accessibilite-form textarea').each(function() {
                const $input = $(this);
                const $wrapper = $('<div class="aa-input-wrapper"></div>');
                const label = $input.attr('placeholder') || $input.prev('label').text();
                
                if (label) {
                    $input.wrap($wrapper);
                    $input.after(`<label class="aa-floating-label">${label}</label>`);
                    
                    $input.on('focus blur input', function() {
                        const $wrapper = $(this).parent();
                        if ($(this).val() || $(this).is(':focus')) {
                            $wrapper.addClass('aa-has-content');
                        } else {
                            $wrapper.removeClass('aa-has-content');
                        }
                    });
                }
            });
        },

        // Validation en temps réel
        setupRealTimeValidation: function() {
            $('input[type="email"]').on('blur', function() {
                const email = $(this).val();
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                
                $(this).toggleClass('aa-invalid', email && !isValid);
                $(this).toggleClass('aa-valid', email && isValid);
            });
            
            $('input[type="url"]').on('blur', function() {
                const url = $(this).val();
                const isValid = /^https?:\/\/.+/.test(url);
                
                $(this).toggleClass('aa-invalid', url && !isValid);
                $(this).toggleClass('aa-valid', url && isValid);
            });
        },

        // Indicateur de force pour les mots de passe
        setupPasswordStrength: function() {
            $('input[type="password"]').each(function() {
                const $input = $(this);
                const $strength = $('<div class="aa-password-strength"><div class="aa-strength-bar"></div></div>');
                
                $input.after($strength);
                
                $input.on('input', function() {
                    const password = $(this).val();
                    const strength = calculatePasswordStrength(password);
                    
                    $strength.removeClass('weak medium strong')
                            .addClass(strength.class)
                            .find('.aa-strength-bar')
                            .css('width', strength.width + '%');
                });
            });
            
            function calculatePasswordStrength(password) {
                let score = 0;
                if (password.length >= 8) score += 25;
                if (/[a-z]/.test(password)) score += 25;
                if (/[A-Z]/.test(password)) score += 25;
                if (/[0-9]/.test(password)) score += 25;
                
                if (score < 50) return { class: 'weak', width: score };
                if (score < 75) return { class: 'medium', width: score };
                return { class: 'strong', width: score };
            }
        },

        // Notification toast moderne
        showToast: function(message, type = 'info', duration = 4000) {
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const $toast = $(`
                <div class="aa-toast aa-toast-${type}">
                    <span class="aa-toast-icon">${icons[type] || icons.info}</span>
                    <span class="aa-toast-message">${message}</span>
                    <button class="aa-toast-close">&times;</button>
                </div>
            `);
            
            $('body').append($toast);
            
            setTimeout(() => $toast.addClass('aa-toast-visible'), 10);
            
            // Auto-hide
            setTimeout(() => {
                $toast.removeClass('aa-toast-visible');
                setTimeout(() => $toast.remove(), 300);
            }, duration);
            
            // Manual close
            $toast.find('.aa-toast-close').on('click', function() {
                $toast.removeClass('aa-toast-visible');
                setTimeout(() => $toast.remove(), 300);
            });
        },

        // Créer un modal moderne
        createModal: function(title, content, options = {}) {
            const defaults = {
                closable: true,
                size: 'medium',
                backdrop: true
            };
            
            const opts = { ...defaults, ...options };
            
            const $modal = $(`
                <div class="aa-modal-overlay">
                    <div class="aa-modal aa-modal-${opts.size}">
                        <div class="aa-modal-header">
                            <h3 class="aa-modal-title">${title}</h3>
                            ${opts.closable ? '<button class="aa-modal-close">&times;</button>' : ''}
                        </div>
                        <div class="aa-modal-content">
                            ${content}
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append($modal);
            
            setTimeout(() => $modal.addClass('aa-modal-visible'), 10);
            
            // Event handlers
            if (opts.closable) {
                $modal.find('.aa-modal-close').on('click', () => this.closeModal($modal));
                if (opts.backdrop) {
                    $modal.on('click', (e) => {
                        if (e.target === $modal[0]) {
                            this.closeModal($modal);
                        }
                    });
                }
            }
            
            return $modal;
        },

        // Fermer un modal
        closeModal: function($modal) {
            $modal.removeClass('aa-modal-visible');
            setTimeout(() => $modal.remove(), 300);
        }
    };

    // Styles CSS modernes pour les composants JS
    const modernStyles = `
        <style id="aa-modern-ui-styles">
            /* Animations d'entrée */
            @keyframes aa-fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .aa-animate-in {
                animation: aa-fadeInUp 0.6s ease-out;
            }

            /* Progress bar */
            .aa-progress-container {
                margin-bottom: var(--aa-space-lg);
            }

            .aa-progress-bar {
                width: 100%;
                height: 8px;
                background: var(--aa-gray-200);
                border-radius: var(--aa-radius-sm);
                overflow: hidden;
            }

            .aa-progress-fill {
                height: 100%;
                background: var(--aa-gradient-primary);
                width: 0%;
                transition: width 0.3s ease;
                border-radius: var(--aa-radius-sm);
            }

            .aa-progress-text {
                margin-top: var(--aa-space-sm);
                font-size: 0.875rem;
                color: var(--aa-gray-600);
                text-align: center;
            }

            /* Tooltips */
            .aa-tooltip {
                position: absolute;
                background: var(--aa-gray-900);
                color: var(--aa-white);
                padding: var(--aa-space-xs) var(--aa-space-sm);
                border-radius: var(--aa-radius-sm);
                font-size: 0.75rem;
                z-index: 1000;
                opacity: 0;
                transform: translateY(10px);
                transition: var(--aa-transition);
                pointer-events: none;
            }

            .aa-tooltip-visible {
                opacity: 1;
                transform: translateY(0);
            }

            /* Card hover effects */
            .aa-card-hover {
                transform: translateY(-4px) !important;
                box-shadow: var(--aa-shadow-xl) !important;
            }

            /* Floating labels */
            .aa-input-wrapper {
                position: relative;
                margin-bottom: var(--aa-space-lg);
            }

            .aa-floating-label {
                position: absolute;
                top: 50%;
                left: var(--aa-space-md);
                transform: translateY(-50%);
                background: var(--aa-white);
                padding: 0 var(--aa-space-xs);
                color: var(--aa-gray-500);
                transition: var(--aa-transition);
                pointer-events: none;
                font-size: 1rem;
            }

            .aa-input-wrapper.aa-has-content .aa-floating-label {
                top: 0;
                transform: translateY(-50%);
                font-size: 0.75rem;
                color: var(--aa-primary);
            }

            /* Validation states */
            .aa-invalid {
                border-color: var(--aa-error) !important;
                box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
            }

            .aa-valid {
                border-color: var(--aa-success) !important;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
            }

            /* Password strength */
            .aa-password-strength {
                margin-top: var(--aa-space-xs);
                height: 4px;
                background: var(--aa-gray-200);
                border-radius: var(--aa-radius-sm);
                overflow: hidden;
            }

            .aa-strength-bar {
                height: 100%;
                width: 0%;
                transition: var(--aa-transition);
                border-radius: var(--aa-radius-sm);
            }

            .aa-password-strength.weak .aa-strength-bar {
                background: var(--aa-error);
            }

            .aa-password-strength.medium .aa-strength-bar {
                background: var(--aa-warning);
            }

            .aa-password-strength.strong .aa-strength-bar {
                background: var(--aa-success);
            }

            /* Toast notifications */
            .aa-toast {
                position: fixed;
                top: var(--aa-space-lg);
                right: var(--aa-space-lg);
                background: var(--aa-white);
                border-radius: var(--aa-radius-md);
                box-shadow: var(--aa-shadow-lg);
                padding: var(--aa-space-md);
                display: flex;
                align-items: center;
                gap: var(--aa-space-sm);
                z-index: 10000;
                min-width: 300px;
                opacity: 0;
                transform: translateX(100%);
                transition: var(--aa-transition);
            }

            .aa-toast-visible {
                opacity: 1;
                transform: translateX(0);
            }

            .aa-toast-icon {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--aa-white);
                font-weight: bold;
            }

            .aa-toast-success .aa-toast-icon {
                background: var(--aa-success);
            }

            .aa-toast-error .aa-toast-icon {
                background: var(--aa-error);
            }

            .aa-toast-warning .aa-toast-icon {
                background: var(--aa-warning);
            }

            .aa-toast-info .aa-toast-icon {
                background: var(--aa-info);
            }

            .aa-toast-message {
                flex: 1;
                color: var(--aa-gray-800);
            }

            .aa-toast-close {
                background: none;
                border: none;
                font-size: 1.25rem;
                color: var(--aa-gray-400);
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .aa-toast-close:hover {
                color: var(--aa-gray-600);
            }

            /* Modal */
            .aa-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                opacity: 0;
                transition: var(--aa-transition);
            }

            .aa-modal-visible {
                opacity: 1;
            }

            .aa-modal {
                background: var(--aa-white);
                border-radius: var(--aa-radius-lg);
                box-shadow: var(--aa-shadow-xl);
                max-height: 90vh;
                overflow-y: auto;
                transform: scale(0.9);
                transition: var(--aa-transition);
            }

            .aa-modal-visible .aa-modal {
                transform: scale(1);
            }

            .aa-modal-small { width: 400px; }
            .aa-modal-medium { width: 600px; }
            .aa-modal-large { width: 800px; }

            .aa-modal-header {
                padding: var(--aa-space-lg);
                border-bottom: 1px solid var(--aa-gray-200);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .aa-modal-title {
                margin: 0;
                color: var(--aa-gray-900);
            }

            .aa-modal-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                color: var(--aa-gray-400);
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--aa-radius-sm);
            }

            .aa-modal-close:hover {
                background: var(--aa-gray-100);
                color: var(--aa-gray-600);
            }

            .aa-modal-content {
                padding: var(--aa-space-lg);
            }

            /* Focus states améliorés */
            .aa-focused {
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2) !important;
            }
        </style>
    `;

    // Initialisation au chargement du DOM
    $(document).ready(function() {
        // Ajouter les styles modernes
        $('head').append(modernStyles);
        
        // Initialiser l'interface moderne
        AuditAccessibiliteUI.init();
        
        // Exposer l'API globalement pour utilisation externe
        window.AuditAccessibiliteUI = AuditAccessibiliteUI;
    });

})(jQuery); 