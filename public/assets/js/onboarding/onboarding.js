/**
 * KitiSmart - Système d'Onboarding Interactif
 * Utilise Shepherd.js pour les tours guidés
 */

class KitiSmartOnboarding {
    constructor(config = {}) {
        this.config = config;
        this.currentTour = null;
        this.steps = {};

        // Vérifier si Shepherd est disponible
        if (typeof Shepherd === 'undefined') {
            console.warn('Shepherd.js n\'est pas chargé');
            return;
        }

        this.initSteps();
    }

    /**
     * Initialise les définitions des étapes
     */
    initSteps() {
        // Tour de bienvenue
        this.steps.welcome = [
            {
                id: 'welcome-intro',
                title: '👋 Bienvenue sur KitiSmart !',
                text: `
                    <div class="onboarding-welcome">
                        <p>Nous allons vous guider à travers les principales fonctionnalités pour gérer votre budget efficacement.</p>
                        <div class="onboarding-features">
                            <div class="feature-item"><i class="fas fa-wallet"></i> Gérer plusieurs budgets</div>
                            <div class="feature-item"><i class="fas fa-chart-pie"></i> Suivre vos dépenses</div>
                            <div class="feature-item"><i class="fas fa-sync"></i> Automatiser les récurrences</div>
                        </div>
                        <p class="onboarding-hint">Ce guide dure environ 2 minutes.</p>
                    </div>
                `,
                buttons: [
                    {
                        text: 'Passer le tour',
                        action: () => this.skipTour('welcome'),
                        classes: 'btn-skip'
                    },
                    {
                        text: 'Commencer <i class="fas fa-arrow-right"></i>',
                        action: () => this.currentTour.next(),
                        classes: 'btn-primary'
                    }
                ]
            }
        ];

        // Tour du switch de budget
        this.steps.budget_switch = [
            {
                id: 'budget-switcher',
                attachTo: {
                    element: '.budget-switcher',
                    on: 'bottom'
                },
                title: 'Changer de budget',
                text: 'Vous pouvez gérer plusieurs budgets ! Cliquez ici pour basculer entre votre budget principal et vos budgets annexes (projets, famille...).',
                buttons: this.getNavButtons()
            },
            {
                id: 'budget-types',
                attachTo: {
                    element: '.budget-switcher',
                    on: 'bottom'
                },
                title: 'Types de budgets',
                text: '<strong>Budget Principal</strong> : votre budget mensuel (se clôture automatiquement).<br><br><strong>Budget Annexe</strong> : pour des projets ponctuels (clôture manuelle).',
                buttons: this.getNavButtons(true)
            }
        ];

        // Tour du dashboard
        this.steps.dashboard_tour = [
            {
                id: 'dashboard-stats',
                attachTo: {
                    element: '.stat-card:first-child',
                    on: 'bottom'
                },
                title: 'Vue d\'ensemble du budget',
                text: 'Ici vous voyez le résumé de votre budget : montant initial, dépenses, et ce qu\'il vous reste.',
                buttons: this.getNavButtons()
            },
            {
                id: 'dashboard-progress',
                attachTo: {
                    element: '.budget-progress',
                    on: 'bottom'
                },
                title: 'Progression du budget',
                text: 'Cette barre montre combien de votre budget a été utilisé. Elle change de couleur selon le niveau.',
                buttons: this.getNavButtons()
            },
            {
                id: 'dashboard-actions',
                attachTo: {
                    element: '.dashboard-actions',
                    on: 'top'
                },
                title: 'Actions rapides',
                text: 'Utilisez ces boutons pour créer un budget, ajouter des dépenses ou voir votre historique.',
                buttons: this.getNavButtons()
            },
            {
                id: 'dashboard-charts',
                attachTo: {
                    element: '.charts-section',
                    on: 'top'
                },
                title: 'Visualisations',
                text: 'Ces graphiques vous montrent la répartition de vos dépenses par catégorie et dans le temps.',
                buttons: this.getNavButtons(true)
            }
        ];

        // Tour création de budget
        this.steps.budget_creation = [
            {
                id: 'budget-name',
                attachTo: {
                    element: '#budget-name, input[name="name"]',
                    on: 'bottom'
                },
                title: 'Nom du budget',
                text: 'Donnez un nom significatif à votre budget, par exemple "Novembre 2024" ou "Budget Famille".',
                buttons: this.getNavButtons()
            },
            {
                id: 'budget-amount',
                attachTo: {
                    element: '#initial-amount, input[name="initial_amount"]',
                    on: 'bottom'
                },
                title: 'Montant initial',
                text: 'Entrez le montant total que vous souhaitez allouer à ce budget.',
                buttons: this.getNavButtons()
            },
            {
                id: 'budget-dates',
                attachTo: {
                    element: '.date-inputs, input[name="start_date"]',
                    on: 'bottom'
                },
                title: 'Période du budget',
                text: 'Définissez les dates de début et de fin de votre budget.',
                buttons: this.getNavButtons(true)
            }
        ];

        // Tour création de dépense
        this.steps.expense_creation = [
            {
                id: 'expense-category',
                attachTo: {
                    element: 'select[name="category[]"], .category-select',
                    on: 'bottom'
                },
                title: 'Type de dépense',
                text: 'Choisissez une catégorie pour organiser vos dépenses. Vous pouvez créer vos propres catégories.',
                buttons: this.getNavButtons()
            },
            {
                id: 'expense-amount',
                attachTo: {
                    element: 'input[name="amount[]"], .amount-input',
                    on: 'bottom'
                },
                title: 'Montant',
                text: 'Entrez le montant de la dépense en FCFA.',
                buttons: this.getNavButtons()
            },
            {
                id: 'expense-status',
                attachTo: {
                    element: 'select[name="status[]"]',
                    on: 'bottom'
                },
                title: 'Statut',
                text: '"En attente" pour les dépenses prévues, "Payé" pour celles déjà effectuées.',
                buttons: this.getNavButtons(true)
            }
        ];

        // Tour des catégories
        this.steps.categories = [
            {
                id: 'categories-list',
                attachTo: {
                    element: '.categories-grid, .category-card:first-child',
                    on: 'bottom'
                },
                title: 'Vos catégories',
                text: 'Voici vos catégories personnalisées. Vous pouvez les modifier ou en créer de nouvelles.',
                buttons: this.getNavButtons()
            },
            {
                id: 'categories-create',
                attachTo: {
                    element: '.btn-create-category, a[href*="categories/create"]',
                    on: 'bottom'
                },
                title: 'Créer une catégorie',
                text: 'Cliquez ici pour créer une nouvelle catégorie avec une icône et une couleur personnalisées.',
                buttons: this.getNavButtons(true)
            }
        ];

        // Tour fonctionnalités avancées
        this.steps.advanced_features = [
            {
                id: 'advanced-recurrences',
                attachTo: {
                    element: 'a[href*="recurrences"], .nav-item-recurrences',
                    on: 'bottom'
                },
                title: 'Dépenses récurrentes',
                text: 'Automatisez vos dépenses régulières (loyer, abonnements...) pour qu\'elles soient ajoutées automatiquement.',
                buttons: this.getNavButtons()
            },
            {
                id: 'advanced-sharing',
                attachTo: {
                    element: 'a[href*="share"], .nav-item-share',
                    on: 'bottom'
                },
                title: 'Partage de budget',
                text: 'Partagez votre budget avec votre famille ou collègues via un lien sécurisé.',
                buttons: this.getNavButtons()
            },
            {
                id: 'advanced-export',
                attachTo: {
                    element: 'a[href*="export"], .btn-export',
                    on: 'bottom'
                },
                title: 'Export des données',
                text: 'Exportez vos dépenses en CSV ou PDF pour les analyser ou les archiver.',
                buttons: this.getNavButtons(true)
            }
        ];
    }

    /**
     * Retourne les boutons de navigation standard
     */
    getNavButtons(isLast = false, isFirst = false) {
        const buttons = [];

        // Bouton Passer (toujours présent)
        buttons.push({
            text: 'Passer le tour',
            action: () => this.skipCurrentTour(),
            classes: 'btn-skip'
        });

        // Bouton Précédent (si pas première étape)
        if (!isFirst) {
            buttons.push({
                text: '<i class="fas fa-arrow-left"></i> Précédent',
                action: () => this.currentTour.back(),
                classes: 'btn-secondary'
            });
        }

        // Bouton Suivant ou Terminer
        if (!isLast) {
            buttons.push({
                text: 'Suivant <i class="fas fa-arrow-right"></i>',
                action: () => this.currentTour.next(),
                classes: 'btn-primary'
            });
        } else {
            buttons.push({
                text: '<i class="fas fa-check"></i> Terminer',
                action: () => this.completeTour(),
                classes: 'btn-success'
            });
        }

        return buttons;
    }

    /**
     * Retourne les boutons pour la première étape
     */
    getFirstStepButtons() {
        return this.getNavButtons(false, true);
    }

    /**
     * Retourne les boutons pour la dernière étape
     */
    getLastStepButtons() {
        return this.getNavButtons(true, false);
    }

    /**
     * Démarre un tour spécifique
     */
    startTour(tourName) {
        if (!this.steps[tourName]) {
            console.warn(`Tour "${tourName}" non trouvé`);
            return;
        }

        // Filtrer les étapes dont les éléments existent
        const validSteps = this.steps[tourName].filter(step => {
            if (!step.attachTo || !step.attachTo.element) return true;
            return document.querySelector(step.attachTo.element) !== null;
        });

        if (validSteps.length === 0) {
            console.warn(`Aucune étape valide pour le tour "${tourName}"`);
            return;
        }

        this.currentTourName = tourName;
        this.totalSteps = validSteps.length;

        this.currentTour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                classes: 'kitismart-onboarding',
                scrollTo: { behavior: 'smooth', block: 'center' },
                cancelIcon: {
                    enabled: true
                },
                when: {
                    show: () => this.updateStepIndicator()
                }
            }
        });

        // Ajouter les étapes avec indicateur
        validSteps.forEach((step, index) => {
            // Ajouter l'indicateur d'étapes au texte
            const stepIndicator = `<div class="step-indicator">Étape ${index + 1} sur ${validSteps.length}</div>`;
            step.text = stepIndicator + (step.text || '');
            this.currentTour.addStep(step);
        });

        this.currentTour.on('complete', () => this.onTourComplete());
        this.currentTour.on('cancel', () => this.onTourCancel());

        this.currentTour.start();
    }

    /**
     * Met à jour l'indicateur d'étapes
     */
    updateStepIndicator() {
        const currentStep = this.currentTour.getCurrentStep();
        if (currentStep) {
            const index = this.currentTour.steps.indexOf(currentStep);
            const progress = ((index + 1) / this.totalSteps) * 100;

            // Mettre à jour la barre de progression si elle existe
            const progressBar = document.querySelector('.onboarding-step-progress');
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
        }
    }

    /**
     * Termine le tour et enregistre la complétion
     */
    async completeTour() {
        if (!this.currentTourName) return;

        try {
            await this.markStepComplete(this.currentTourName);
            this.currentTour.complete();
        } catch (e) {
            console.error('Erreur lors de la complétion du tour:', e);
            this.currentTour.complete();
        }
    }

    /**
     * Ignore le tour actuel
     */
    async skipCurrentTour() {
        if (!this.currentTourName) return;

        try {
            await this.markStepSkipped(this.currentTourName);
            this.currentTour.cancel();
        } catch (e) {
            console.error('Erreur lors du skip du tour:', e);
            this.currentTour.cancel();
        }
    }

    /**
     * Ignore un tour spécifique
     */
    async skipTour(tourName) {
        try {
            await this.markStepSkipped(tourName);
            if (this.currentTour) {
                this.currentTour.cancel();
            }
        } catch (e) {
            console.error('Erreur lors du skip du tour:', e);
        }
    }

    /**
     * Callback quand le tour est complété
     */
    onTourComplete() {
        this.showCompletionMessage();
        this.checkNextTour();
    }

    /**
     * Callback quand le tour est annulé
     */
    onTourCancel() {
        // Optionnel: afficher un message
    }

    /**
     * Affiche un message de complétion
     */
    showCompletionMessage() {
        const toast = document.createElement('div');
        toast.className = 'onboarding-toast success';
        toast.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>Étape complétée !</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Vérifie s'il y a un tour suivant à démarrer
     */
    checkNextTour() {
        const stepsToShow = this.config.stepsToShow || [];
        const currentIndex = stepsToShow.indexOf(this.currentTourName);

        if (currentIndex >= 0 && currentIndex < stepsToShow.length - 1) {
            const nextTour = stepsToShow[currentIndex + 1];
            setTimeout(() => {
                this.startTour(nextTour);
            }, 1000);
        }
    }

    /**
     * Marque une étape comme complétée via l'API
     */
    async markStepComplete(stepName) {
        const response = await fetch(`/api/onboarding/complete/${stepName}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        return response.json();
    }

    /**
     * Marque une étape comme ignorée via l'API
     */
    async markStepSkipped(stepName) {
        const response = await fetch(`/api/onboarding/skip/${stepName}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        return response.json();
    }

    /**
     * Récupère le statut de l'onboarding
     */
    async getStatus() {
        const response = await fetch('/api/onboarding/status');
        return response.json();
    }

    /**
     * Initialise l'onboarding automatiquement selon la config
     */
    autoStart() {
        const stepsToShow = this.config.stepsToShow || [];

        if (stepsToShow.length > 0) {
            // Petit délai pour laisser la page se charger
            setTimeout(() => {
                this.startTour(stepsToShow[0]);
            }, 500);
        }
    }
}

// Initialisation globale
window.KitiSmartOnboarding = KitiSmartOnboarding;

// Auto-init si config disponible
document.addEventListener('DOMContentLoaded', function() {
    if (window.onboardingConfig && window.onboardingConfig.stepsToShow && window.onboardingConfig.stepsToShow.length > 0) {
        const onboarding = new KitiSmartOnboarding(window.onboardingConfig);
        onboarding.autoStart();
        window.kitismartOnboarding = onboarding;
    }
});
