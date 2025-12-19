/**
 * Financial Planner - KitiSmart
 */

$(document).ready(function() {
    
    // Utiliser une suggestion
    $('.use-suggestion').on('click', function() {
        const name = $(this).data('name');
        const amount = $(this).data('amount');
        
        $('#sim-name').val(name);
        $('#sim-target').val(amount);
        
        // Scroll vers le simulateur
        $('html, body').animate({
            scrollTop: $('.simulator-card').offset().top - 100
        }, 500);
    });

    // Helper pour parser un montant (gère les formats avec espaces/virgules)
    function parseAmount(fieldId) {
        // Essayer d'abord le champ hidden créé par amount-formatter
        const rawVal = $(`#${fieldId}_raw`).val();
        const visibleVal = $(`#${fieldId}`).val();
        
        let value = rawVal || visibleVal || '0';
        
        // Nettoyer la valeur (enlever espaces, remplacer virgule par point)
        value = value.toString().replace(/\s/g, '').replace(',', '.');
        
        return parseFloat(value) || 0;
    }

    // Simulateur d'objectif
    $('#btn-simulate').on('click', function() {
        const projectName = $('#sim-name').val().trim() || 'Mon objectif';
        
        // Parser les montants
        const targetAmount = parseAmount('sim-target');
        const additionalIncome = parseAmount('sim-additional');
        const additionalPeriod = $('#sim-additional-period').val() || 'month';
        const targetMonths = parseInt($('#sim-months').val()) || 0;

        if (targetAmount <= 0) {
            showError('Veuillez entrer un montant à atteindre.');
            return;
        }
        
        // Vérifier si l'utilisateur a une capacité d'épargne
        const plannerData = window.PLANNER_DATA || {};
        const hasCapacity = (plannerData.monthlyAvailable > 0) || (plannerData.yearlyAvailable > 0) || (additionalIncome > 0);
        
        if (!hasCapacity) {
            showError('Aucune capacité d\'épargne détectée. Créez un budget avec une source (salaire, prime...) ou ajoutez un revenu additionnel.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calcul en cours...');

        $.ajax({
            url: '/planner/simulate',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                project_name: projectName,
                target_amount: targetAmount,
                additional_income: additionalIncome,
                additional_period: additionalPeriod,
                target_months: targetMonths
            }),
            success: function(response) {
                if (response.success) {
                    displayResult(response.simulation);
                } else {
                    showError(response.message || 'Erreur lors de la simulation.');
                }
            },
            error: function() {
                showError('Erreur de connexion au serveur.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-calculator"></i> Calculer mes options');
            }
        });
    });

    function displayResult(sim) {
        const $result = $('#simulation-result');
        
        let statusClass = sim.is_realistic ? 'realistic' : 'ambitious';
        let statusIcon = sim.is_realistic ? 'fa-check-circle' : 'fa-exclamation-circle';
        let statusText = sim.is_realistic ? 'Objectif réalisable' : 'Objectif ambitieux';

        let html = `
            <div class="result-header ${statusClass}">
                <div class="result-icon">
                    <i class="fas ${statusIcon}"></i>
                </div>
                <div class="result-title">
                    <h4>${sim.project_name}</h4>
                    <p>${statusText}</p>
                </div>
                <div class="result-amount">
                    ${formatMoney(sim.target_amount)}
                </div>
            </div>
        `;

        // Cas où le revenu unique couvre tout l'objectif
        if (sim.covered_by_once) {
            html += `
                <div class="result-covered">
                    <i class="fas fa-trophy"></i>
                    <p>Votre revenu unique couvre la totalité de l'objectif. Aucune épargne supplémentaire nécessaire !</p>
                </div>
            `;
            $result.html(html).slideDown();
            return;
        }

        // Afficher les scénarios si pas de délai spécifié
        if (sim.scenarios && Object.keys(sim.scenarios).length > 0) {
            html += `<div class="scenarios-grid">`;
            
            for (const [key, scenario] of Object.entries(sim.scenarios)) {
                const isRecommended = key === 'moderate';
                html += `
                    <div class="scenario-card selectable ${isRecommended ? 'recommended' : ''}" 
                         data-monthly="${scenario.monthly}" 
                         data-months="${scenario.months}"
                         data-date="${scenario.target_date}">
                        ${isRecommended ? '<span class="recommended-badge">Recommandé</span>' : ''}
                        <div class="scenario-label">${scenario.label}</div>
                        <div class="scenario-monthly">
                            <strong>${formatMoney(scenario.monthly)}</strong>
                            <span>/mois</span>
                        </div>
                        <div class="scenario-duration">
                            <i class="fas fa-calendar"></i>
                            ${scenario.years} ans (${scenario.months} mois)
                        </div>
                        <div class="scenario-date">
                            Objectif atteint le ${formatDate(scenario.target_date)}
                        </div>
                        <div class="scenario-select">
                            <i class="fas fa-check-circle"></i> Sélectionner
                        </div>
                    </div>
                `;
            }
            
            html += `</div>`;
        }
        // Afficher le résultat si délai spécifié
        else if (sim.target_months) {
            html += `
                <div class="result-details">
                    <div class="detail-item">
                        <span class="detail-label">Épargne mensuelle requise</span>
                        <span class="detail-value">${formatMoney(sim.monthly_needed)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Durée</span>
                        <span class="detail-value">${sim.target_months} mois</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date cible</span>
                        <span class="detail-value">${formatDate(sim.target_date)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">% de votre capacité</span>
                        <span class="detail-value ${sim.percent_of_capacity > 100 ? 'text-danger' : ''}">
                            ${sim.percent_of_capacity > 1000 ? '> 1000%' : sim.percent_of_capacity + '%'}
                            ${sim.percent_of_capacity > 100 ? ' (irréaliste)' : ''}
                        </span>
                    </div>
                </div>
            `;

            // Alternative si pas réaliste
            if (sim.alternative) {
                html += `
                    <div class="result-alternative">
                        <h5><i class="fas fa-lightbulb"></i> Alternative suggérée</h5>
                        <p>Avec votre capacité d'épargne de <strong>${formatMoney(sim.alternative.monthly)}/mois</strong>, 
                        vous pourriez atteindre cet objectif en <strong>${sim.alternative.months} mois</strong> 
                        (${sim.alternative.years} ans), d'ici le <strong>${formatDate(sim.alternative.target_date)}</strong>.</p>
                    </div>
                `;
            }
        }

        // Impact du revenu unique
        if (sim.once_impact) {
            if (sim.once_impact.covered) {
                html += `
                    <div class="result-bonus-impact success">
                        <i class="fas fa-check-circle"></i>
                        <span>Votre revenu unique de <strong>${formatMoney(sim.once_impact.amount)}</strong> 
                        couvre la totalité de l'objectif !</span>
                    </div>
                `;
            } else {
                html += `
                    <div class="result-bonus-impact">
                        <i class="fas fa-coins"></i>
                        <span>Votre revenu unique de <strong>${formatMoney(sim.once_impact.amount)}</strong> 
                        réduit l'objectif à <strong>${formatMoney(sim.once_impact.remaining)}</strong> à épargner.</span>
                    </div>
                `;
            }
        }
        
        // Impact du revenu additionnel récurrent
        if (sim.bonus_impact) {
            if (sim.bonus_impact.only_source) {
                html += `
                    <div class="result-bonus-impact">
                        <i class="fas fa-info-circle"></i>
                        <span>Votre revenu additionnel de <strong>${formatMoney(sim.additional_income)}/mois</strong> 
                        est votre seule source d'épargne.</span>
                    </div>
                `;
            } else if (sim.bonus_impact.months_saved > 0) {
                html += `
                    <div class="result-bonus-impact">
                        <i class="fas fa-bolt"></i>
                        <span>Avec votre revenu additionnel, vous gagnez <strong>${sim.bonus_impact.months_saved} mois</strong> 
                        (${sim.bonus_impact.with_bonus} mois au lieu de ${sim.bonus_impact.without_bonus})</span>
                    </div>
                `;
            }
        }

        // Bouton créer objectif (masqué par défaut, affiché quand un scénario est sélectionné)
        html += `
            <div class="result-actions" id="create-goal-actions" style="display: none;">
                <button type="button" class="btn btn-primary" id="btn-open-create-goal">
                    <i class="fas fa-plus"></i> Créer cet objectif d'épargne
                </button>
            </div>
        `;
        
        // Stocker les données de simulation pour la création
        window.currentSimulation = sim;

        $result.html(html).slideDown();
    }

    function showError(message) {
        const $result = $('#simulation-result');
        $result.html(`
            <div class="result-header error">
                <div class="result-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="result-title">
                    <h4>${message}</h4>
                </div>
            </div>
        `).slideDown();
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'decimal',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount) + ' FCFA';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    // Animation au chargement
    $('.summary-card').each(function(i) {
        $(this).css('opacity', 0).delay(i * 100).animate({ opacity: 1 }, 300);
    });

    // Variables pour la création d'objectif
    let selectedScenario = null;
    let selectedIcon = 'fa-piggy-bank';
    let selectedColor = '#0d9488';

    // Sélection d'un scénario
    $(document).on('click', '.scenario-card.selectable', function() {
        $('.scenario-card.selectable').removeClass('selected');
        $(this).addClass('selected');
        
        selectedScenario = {
            monthly: parseFloat($(this).data('monthly')),
            months: parseInt($(this).data('months')),
            date: $(this).data('date')
        };
        
        // Afficher le bouton de création
        $('#create-goal-actions').slideDown();
    });

    // Ouvrir la modal de création
    $(document).on('click', '#btn-open-create-goal', function() {
        if (!selectedScenario || !window.currentSimulation) {
            showError('Veuillez d\'abord sélectionner un scénario.');
            return;
        }
        
        const sim = window.currentSimulation;
        
        // Remplir le récap
        $('#recap-name').text(sim.project_name);
        $('#recap-amount').text(formatMoney(sim.target_amount));
        $('#recap-monthly').text(formatMoney(selectedScenario.monthly) + '/mois');
        $('#recap-date').text(formatDate(selectedScenario.date));
        
        // Ouvrir la modal
        $('#create-goal-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer la modal
    $('#close-goal-modal, #cancel-goal-modal, .modal-backdrop').on('click', function() {
        $('#create-goal-modal').removeClass('active');
        $('body').css('overflow', '');
    });

    // Sélection d'icône
    $(document).on('click', '.icon-option', function() {
        $('.icon-option').removeClass('selected');
        $(this).addClass('selected');
        selectedIcon = $(this).data('icon');
    });

    // Sélection de couleur
    $(document).on('click', '.color-option', function() {
        $('.color-option').removeClass('selected');
        $(this).addClass('selected');
        selectedColor = $(this).data('color');
    });

    // Créer l'objectif
    $('#confirm-create-goal').on('click', function() {
        if (!selectedScenario || !window.currentSimulation) {
            return;
        }
        
        const sim = window.currentSimulation;
        const $btn = $(this);
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');
        
        $.ajax({
            url: '/planner/create-goal',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                name: sim.project_name,
                target_amount: sim.target_amount,
                monthly_contribution: selectedScenario.monthly,
                target_date: selectedScenario.date,
                icon: selectedIcon,
                color: selectedColor
            }),
            success: function(response) {
                if (response.success) {
                    // Fermer la modal
                    $('#create-goal-modal').removeClass('active');
                    $('body').css('overflow', '');
                    
                    // Afficher un message de succès
                    $('#simulation-result').html(`
                        <div class="result-success">
                            <i class="fas fa-check-circle"></i>
                            <h4>Objectif créé avec succès !</h4>
                            <p>Votre objectif "${response.goal.name}" a été créé. Vous pouvez le suivre dans la section Objectifs d'épargne.</p>
                            <a href="/savings/goals" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Voir mes objectifs
                            </a>
                        </div>
                    `);
                    
                    // Reset
                    selectedScenario = null;
                    window.currentSimulation = null;
                } else {
                    alert(response.message || 'Erreur lors de la création');
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Créer l\'objectif');
            }
        });
    });

    // Aperçu d'un objectif existant
    $(document).on('click', '.goal-card.clickable', function() {
        const $card = $(this);
        const goal = {
            id: $card.data('goal-id'),
            name: $card.data('goal-name'),
            target: parseFloat($card.data('goal-target')),
            current: parseFloat($card.data('goal-current')),
            monthly: parseFloat($card.data('goal-monthly')),
            date: $card.data('goal-date'),
            progress: parseFloat($card.data('goal-progress')),
            remaining: parseFloat($card.data('goal-remaining')),
            months: parseInt($card.data('goal-months')),
            icon: $card.data('goal-icon'),
            color: $card.data('goal-color')
        };
        
        // Remplir la modal
        $('#preview-icon').removeClass().addClass('fas ' + goal.icon).css('color', goal.color);
        $('#preview-title').text(goal.name);
        $('#preview-target').text(formatMoney(goal.target));
        $('#preview-current').text(formatMoney(goal.current));
        $('#preview-remaining').text(formatMoney(goal.remaining));
        $('#preview-monthly').text(goal.monthly > 0 ? formatMoney(goal.monthly) + '/mois' : 'Non définie');
        $('#preview-date').text(goal.date ? formatDate(goal.date) : 'Non définie');
        $('#preview-percent').text(goal.progress + '%');
        
        // Temps restant
        if (goal.months > 0) {
            const years = Math.floor(goal.months / 12);
            const months = goal.months % 12;
            let timeText = '';
            if (years > 0) timeText += years + ' an' + (years > 1 ? 's' : '') + ' ';
            if (months > 0) timeText += months + ' mois';
            $('#preview-time').text(timeText || 'Bientôt terminé');
        } else {
            $('#preview-time').text(goal.progress >= 100 ? 'Objectif atteint !' : 'À calculer');
        }
        
        // Cercle de progression
        const circumference = 2 * Math.PI * 45;
        const offset = circumference - (goal.progress / 100) * circumference;
        $('#preview-circle').css({
            'stroke-dasharray': circumference,
            'stroke-dashoffset': offset,
            'stroke': goal.color
        });
        $('#preview-progress-circle').css('--goal-color', goal.color);
        
        // Ouvrir la modal
        $('#goal-preview-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer la modal d'aperçu
    $('#close-preview-modal, #close-preview-btn, #goal-preview-modal .modal-backdrop').on('click', function() {
        $('#goal-preview-modal').removeClass('active');
        $('body').css('overflow', '');
    });

    // Taguer le budget principal
    $('#btn-tag-budget').on('click', function() {
        const sourceType = $('#tag-source').val();
        const budgetId = $(this).data('budget-id');
        
        if (!sourceType) {
            alert('Veuillez sélectionner une source');
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> En cours...');
        
        $.ajax({
            url: '/planner/tag-budget',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                budget_id: budgetId,
                source_type: sourceType
            }),
            success: function(response) {
                if (response.success) {
                    // Recharger la page pour mettre à jour les données
                    location.reload();
                } else {
                    alert(response.message || 'Erreur lors du tagging');
                    $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Taguer ce budget');
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur');
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Taguer ce budget');
            }
        });
    });

    // ==================== AI CHAT SECTION ====================
    
    const prompts = {
        general: { type: 'general', label: 'Analyser mes finances' },
        savings: { type: 'savings', label: 'Conseils épargne' },
        optimize: { type: 'optimize', label: 'Réduire mes dépenses' },
        goal: { type: 'goal', label: 'Atteindre mon objectif' }
    };

    // Charger le statut IA au démarrage
    function loadAIStatus() {
        $.get('/planner/ai-status', function(response) {
            if (response.success) {
                $('#ai-count').text(response.remaining);
                if (response.remaining <= 0) {
                    $('.suggestion-chip').prop('disabled', true).css('opacity', '0.5');
                    $('.status-dot').removeClass('online').addClass('offline');
                }
            }
        });
    }
    
    loadAIStatus();

    // Clic sur une suggestion
    $('.suggestion-chip').on('click', function() {
        const promptType = $(this).data('prompt');
        const label = $(this).text().trim();
        
        let additionalData = { prompt_type: promptType };
        
        // Si c'est le conseil sur objectif, ajouter les infos
        if (promptType === 'goal') {
            const goalName = $('#goal-name').val();
            const targetAmount = parseAmount($('#goal-amount').val());
            additionalData.goal_name = goalName;
            additionalData.target_amount = targetAmount;
        }
        
        // Ajouter le message utilisateur
        addUserMessage(label);
        
        // Cacher les suggestions
        $('#ai-suggestions').slideUp();
        
        // Envoyer la requête
        askAI(additionalData);
    });

    function addUserMessage(text) {
        const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        const html = `
            <div class="ai-message user">
                <div class="message-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="message-content">
                    <div class="message-bubble">${text}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
        $('#ai-typing').before(html);
        scrollToBottom();
    }

    function addBotMessage(text, isError = false) {
        const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        
        // Formater le texte
        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        
        const errorClass = isError ? ' error' : '';
        const html = `
            <div class="ai-message bot${errorClass}">
                <div class="message-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="message-content">
                    <div class="message-bubble">${formattedText}</div>
                    <div class="message-time">${time}</div>
                    ${!isError ? `
                    <div class="message-actions">
                        <button class="message-action-btn" title="Utile"><i class="fas fa-thumbs-up"></i></button>
                        <button class="message-action-btn" title="Pas utile"><i class="fas fa-thumbs-down"></i></button>
                        <button class="message-action-btn copy-btn" title="Copier"><i class="fas fa-copy"></i></button>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        $('#ai-typing').before(html);
        scrollToBottom();
        
        // Réafficher les suggestions après réponse
        setTimeout(() => {
            $('#ai-suggestions').slideDown();
        }, 500);
    }

    function scrollToBottom() {
        const chatBody = document.getElementById('ai-chat-body');
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function askAI(additionalData) {
        // Afficher le typing indicator
        $('#ai-typing').show();
        scrollToBottom();
        
        $.ajax({
            url: '/planner/ai-advice',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(additionalData),
            success: function(response) {
                $('#ai-typing').hide();
                
                if (response.success) {
                    addBotMessage(response.advice);
                    $('#ai-count').text(response.remaining);
                    
                    if (response.remaining <= 0) {
                        $('.suggestion-chip').prop('disabled', true).css('opacity', '0.5');
                        $('.status-dot').removeClass('online').addClass('offline');
                    }
                } else {
                    addBotMessage(response.error || 'Une erreur est survenue', true);
                    
                    if (response.cooldown) {
                        setTimeout(loadAIStatus, response.cooldown * 1000);
                    }
                }
            },
            error: function() {
                $('#ai-typing').hide();
                addBotMessage('Erreur de connexion au serveur. Veuillez réessayer.', true);
            }
        });
    }

    // Afficher le chip "Atteindre mon objectif" quand les champs sont remplis
    $('#goal-name, #goal-amount').on('input', function() {
        const hasGoal = $('#goal-name').val() && $('#goal-amount').val();
        $('#chip-goal').toggle(!!hasGoal);
    });

    // Copier le message
    $(document).on('click', '.copy-btn', function() {
        const text = $(this).closest('.message-content').find('.message-bubble').text();
        navigator.clipboard.writeText(text).then(() => {
            $(this).html('<i class="fas fa-check"></i>');
            setTimeout(() => {
                $(this).html('<i class="fas fa-copy"></i>');
            }, 2000);
        });
    });

    // Feedback thumbs
    $(document).on('click', '.message-action-btn:not(.copy-btn)', function() {
        $(this).addClass('active').siblings().removeClass('active');
    });

    // Envoi de question personnalisée
    $('#ai-send-btn').on('click', function() {
        sendCustomQuestion();
    });

    $('#ai-custom-question').on('keypress', function(e) {
        if (e.which === 13) {
            sendCustomQuestion();
        }
    });

    function sendCustomQuestion() {
        const question = $('#ai-custom-question').val().trim();
        
        if (!question) {
            return;
        }
        
        if (question.length < 10) {
            alert('Votre question est trop courte. Minimum 10 caractères.');
            return;
        }
        
        // Ajouter le message utilisateur
        addUserMessage(question);
        
        // Vider le champ
        $('#ai-custom-question').val('');
        
        // Cacher les suggestions
        $('#ai-suggestions').slideUp();
        
        // Envoyer la requête avec la question personnalisée
        askAI({
            prompt_type: 'custom',
            custom_question: question
        });
    }
});
