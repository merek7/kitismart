/**
 * Budget Comparison - JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Sélection des éléments
    const budgetCards = document.querySelectorAll('.budget-select-card');
    const compareBtn = document.getElementById('compare-btn');
    const clearBtn = document.getElementById('clear-selection');
    const selectionCount = document.querySelector('.selection-count');

    // Gestion de la sélection des budgets
    function updateSelection() {
        const selectedCards = document.querySelectorAll('.budget-select-card input:checked');
        const count = selectedCards.length;

        // Mettre à jour le compteur
        if (selectionCount) {
            selectionCount.textContent = `(${count} sélectionné${count > 1 ? 's' : ''})`;
        }

        // Activer/désactiver le bouton de comparaison
        if (compareBtn) {
            compareBtn.disabled = count < 2;

            if (count < 2) {
                compareBtn.innerHTML = '<i class="fas fa-chart-bar"></i> Sélectionnez au moins 2 budgets';
            } else if (count > 4) {
                compareBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Maximum 4 budgets';
                compareBtn.disabled = true;
            } else {
                compareBtn.innerHTML = `<i class="fas fa-chart-bar"></i> Comparer ${count} budgets`;
            }
        }

        // Mettre à jour les classes visuelles
        budgetCards.forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }

    // Événements sur les cartes - utiliser uniquement l'événement change du checkbox
    // car le label déclenche déjà le checkbox automatiquement
    budgetCards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.addEventListener('change', updateSelection);
        }
    });

    // Effacer la sélection
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            budgetCards.forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = false;
                }
                card.classList.remove('selected');
            });
            updateSelection();
        });
    }

    // Initialiser l'état
    updateSelection();

    // ==========================================
    // GRAPHIQUES DE COMPARAISON
    // ==========================================

    if (typeof window.comparisonData !== 'undefined' && window.comparisonData) {
        initCharts();
    }

    function initCharts() {
        const data = window.comparisonData;
        const details = window.comparisonDetails || [];

        // Couleurs
        const colors = {
            primary: '#0d9488',
            secondary: '#14b8a6',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6',
            purple: '#8b5cf6'
        };

        // Palette pour plusieurs budgets
        const budgetColors = ['#0d9488', '#3b82f6', '#f59e0b', '#8b5cf6'];

        // Format monétaire
        const formatMoney = (value) => {
            return new Intl.NumberFormat('fr-FR', {
                style: 'decimal',
                maximumFractionDigits: 0
            }).format(value) + ' FCFA';
        };

        // ==========================================
        // 1. GRAPHIQUE DES MONTANTS (Bar Chart)
        // ==========================================
        const amountsCtx = document.getElementById('amounts-chart');
        if (amountsCtx && data.datasets) {
            new Chart(amountsCtx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Budget Initial',
                            data: data.datasets.overview.initial,
                            backgroundColor: colors.primary,
                            borderRadius: 6
                        },
                        {
                            label: 'Dépensé',
                            data: data.datasets.overview.spent,
                            backgroundColor: colors.danger,
                            borderRadius: 6
                        },
                        {
                            label: 'Restant',
                            data: data.datasets.overview.remaining,
                            backgroundColor: colors.success,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatMoney(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return (value / 1000).toFixed(0) + 'k';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // 2. GRAPHIQUE PAR CATÉGORIE (Grouped Bar)
        // ==========================================
        const categoriesCtx = document.getElementById('categories-chart');
        if (categoriesCtx && data.datasets) {
            // Construire les labels avec les catégories par défaut depuis la base
            const categoryLabels = [];
            const defaultCats = data.datasets.default_categories || {};
            const defaultCatTypes = Object.keys(defaultCats);

            // Ajouter les catégories par défaut aux labels
            defaultCatTypes.forEach(type => {
                const cat = defaultCats[type];
                if (cat && cat.name) {
                    categoryLabels.push(cat.name);
                }
            });

            // Ajouter les catégories personnalisées aux labels
            const customCats = data.datasets.custom_categories || {};
            const customCatIds = Object.keys(customCats);
            customCatIds.forEach(catId => {
                const cat = customCats[catId];
                if (cat && cat.name) {
                    categoryLabels.push(cat.name);
                }
            });

            // Construire les datasets pour chaque budget
            const chartDatasets = data.labels.map((label, index) => {
                const budgetData = [];

                // Ajouter les valeurs des catégories par défaut
                defaultCatTypes.forEach(type => {
                    const cat = defaultCats[type];
                    if (cat && cat.values) {
                        budgetData.push(cat.values[index] || 0);
                    }
                });

                // Ajouter les valeurs des catégories personnalisées
                customCatIds.forEach(catId => {
                    const cat = customCats[catId];
                    if (cat && cat.values) {
                        budgetData.push(cat.values[index] || 0);
                    }
                });

                return {
                    label: label,
                    data: budgetData,
                    backgroundColor: budgetColors[index] || colors.primary,
                    borderRadius: 6
                };
            });

            new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: chartDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatMoney(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return (value / 1000).toFixed(0) + 'k';
                                    }
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // 3. GRAPHIQUE RADAR (Analyse comparative)
        // ==========================================
        const radarCtx = document.getElementById('radar-chart');
        if (radarCtx && details.length > 0) {
            // Normaliser les données pour le radar (0-100%)
            const maxInitial = Math.max(...details.map(d => d.initial));
            const maxSpent = Math.max(...details.map(d => d.spent));
            const maxRemaining = Math.max(...details.map(d => d.remaining));
            const maxExpenses = Math.max(...details.map(d => d.expense_count)) || 1;

            const radarDatasets = details.map((detail, index) => {
                // Calculer les scores normalisés
                const efficiency = detail.initial > 0 ? (detail.remaining / detail.initial) * 100 : 0;
                const savingsRate = detail.categories.epargne > 0 && detail.spent > 0
                    ? (detail.categories.epargne / detail.spent) * 100
                    : 0;

                return {
                    label: detail.budget.name,
                    data: [
                        (detail.initial / maxInitial) * 100, // Budget relatif
                        (detail.spent / (maxSpent || 1)) * 100, // Dépenses relatives
                        efficiency, // Efficacité (% restant)
                        Math.min(savingsRate * 2, 100), // Taux d'épargne (amplifié)
                        (detail.expense_count / maxExpenses) * 100 // Nombre de transactions
                    ],
                    backgroundColor: budgetColors[index] + '40',
                    borderColor: budgetColors[index],
                    borderWidth: 2,
                    pointBackgroundColor: budgetColors[index],
                    pointRadius: 4
                };
            });

            new Chart(radarCtx, {
                type: 'radar',
                data: {
                    labels: [
                        'Budget',
                        'Dépensé',
                        'Efficacité',
                        'Épargne',
                        'Transactions'
                    ],
                    datasets: radarDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw.toFixed(1) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 25,
                                display: false
                            },
                            pointLabels: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
