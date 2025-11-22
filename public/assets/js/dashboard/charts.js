// ===================================
// DASHBOARD CHARTS - Chart.js
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    // Détecter le mode sombre
    const isDarkMode = () => document.documentElement.getAttribute('data-theme') === 'dark';

    // Configuration globale Chart.js
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = isDarkMode() ? '#9CA3AF' : '#6B7280';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.padding = 15;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    // Couleurs cohérentes avec le thème
    const colors = {
        primary: isDarkMode() ? '#14B8A6' : '#0D9488',
        secondary: isDarkMode() ? '#0D9488' : '#14B8A6',
        success: '#10B981',
        warning: '#F59E0B',
        danger: '#EF4444',
        info: '#3B82F6',
        purple: '#8B5CF6',
        pink: '#EC4899',
        gray: isDarkMode() ? '#9CA3AF' : '#6B7280',
        gridColor: isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
        borderColor: isDarkMode() ? '#374151' : '#fff'
    };

    // ===================================
    // 1. GRAPHIQUE RÉPARTITION PAR CATÉGORIE (Donut)
    // ===================================
    const categoryCtx = document.getElementById('categoryChart');

    if (categoryCtx && chartData.categories) {
        const categories = chartData.categories;

        // Extraire les données par catégorie
        const categoryLabels = [];
        const categoryValues = [];
        const categoryColors = [colors.primary, colors.warning, colors.success];

        // Map des catégories
        const categoryMap = {
            'fixe': 'Charges Fixes',
            'diver': 'Divers',
            'epargne': 'Épargne'
        };

        Object.keys(categories).forEach((key, index) => {
            if (key !== 'total' && categories[key] > 0) {
                categoryLabels.push(categoryMap[key] || key);
                categoryValues.push(categories[key]);
            }
        });

        // Gérer le cas où il n'y a pas de dépenses
        if (categoryValues.length === 0) {
            categoryLabels.push('Aucune dépense');
            categoryValues.push(1);
            categoryColors[0] = colors.gray;
        }

        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: categoryColors.slice(0, categoryLabels.length),
                    borderWidth: 3,
                    borderColor: colors.borderColor,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value.toLocaleString('fr-FR')} FCFA (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // ===================================
    // 2. GRAPHIQUE BUDGET VS DÉPENSÉ (Bar)
    // ===================================
    const budgetComparisonCtx = document.getElementById('budgetComparisonChart');

    if (budgetComparisonCtx && chartData.budget) {
        const budget = chartData.budget;

        new Chart(budgetComparisonCtx, {
            type: 'bar',
            data: {
                labels: ['Budget Initial', 'Dépensé', 'Restant'],
                datasets: [{
                    label: 'Montant (FCFA)',
                    data: [
                        budget.initial,
                        budget.spent,
                        budget.remaining
                    ],
                    backgroundColor: [
                        colors.primary,
                        budget.spent > budget.initial * 0.8 ? colors.danger : colors.warning,
                        budget.remaining < 0 ? colors.danger : colors.success
                    ],
                    borderWidth: 0,
                    borderRadius: 8,
                    barThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y || 0;
                                return `${value.toLocaleString('fr-FR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })} FCFA`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' FCFA';
                            },
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: colors.gridColor
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    }
                }
            }
        });
    }

    // ===================================
    // 3. GRAPHIQUE STATUT DES DÉPENSES (Horizontal Bar)
    // ===================================
    const statusCtx = document.getElementById('statusChart');

    if (statusCtx && chartData.budget) {
        const budget = chartData.budget;
        const paid = budget.spent - budget.pending;
        const pending = budget.pending;

        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Payées', 'En attente'],
                datasets: [{
                    label: 'Montant (FCFA)',
                    data: [paid, pending],
                    backgroundColor: [colors.success, colors.warning],
                    borderWidth: 0,
                    borderRadius: 8,
                    barThickness: 50
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.x || 0;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${value.toLocaleString('fr-FR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })} FCFA (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' FCFA';
                            },
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: colors.gridColor
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 13,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    }

    // Animation d'apparition des graphiques
    const chartCards = document.querySelectorAll('.chart-card');
    chartCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});
