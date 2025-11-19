/**
 * Dashboard Charts with Chart.js
 * KitiSmart - Gestion de Budget
 */

// Configuration globale de Chart.js
Chart.defaults.font.family = "'Segoe UI', 'Roboto', 'Arial', sans-serif";
Chart.defaults.color = '#666';

/**
 * Graphique en camembert : Répartition des dépenses par catégorie
 */
function createCategoryPieChart(categories) {
    const ctx = document.getElementById('categoryPieChart');
    if (!ctx) return;

    const labels = categories.map(cat => cat.name);
    const data = categories.map(cat => parseFloat(cat.total));
    const colors = [
        '#FF6384', // Rose
        '#36A2EB', // Bleu
        '#FFCE56', // Jaune
        '#4BC0C0', // Turquoise
        '#9966FF', // Violet
        '#FF9F40', // Orange
        '#FF6384', // Rose (repeat)
        '#C9CBCF'  // Gris
    ];

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value.toFixed(2)} € (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Graphique en barres : Dépenses par type
 */
function createTypeBarChart(categories) {
    const ctx = document.getElementById('typeBarChart');
    if (!ctx) return;

    // Regrouper par type
    const types = {
        'Charges fixes': 0,
        'Divers': 0,
        'Épargne': 0
    };

    categories.forEach(cat => {
        if (cat.type_label === 'Charges fixes') {
            types['Charges fixes'] += parseFloat(cat.total);
        } else if (cat.type_label === 'Divers') {
            types['Divers'] += parseFloat(cat.total);
        } else if (cat.type_label === 'Épargne') {
            types['Épargne'] += parseFloat(cat.total);
        }
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(types),
            datasets: [{
                label: 'Montant (€)',
                data: Object.values(types),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56'
                ],
                borderColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56'
                ],
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.parsed.y.toFixed(2)} €`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    },
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Graphique de progression du budget
 */
function createBudgetProgressChart(initialAmount, remainingAmount) {
    const ctx = document.getElementById('budgetProgressChart');
    if (!ctx) return;

    const spent = initialAmount - remainingAmount;
    const percentUsed = (spent / initialAmount) * 100;

    // Déterminer la couleur selon le pourcentage
    let color = '#28a745'; // Vert
    if (percentUsed >= 80) {
        color = '#dc3545'; // Rouge
    } else if (percentUsed >= 60) {
        color = '#ffc107'; // Orange
    }

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Dépensé', 'Restant'],
            datasets: [{
                data: [spent, remainingAmount],
                backgroundColor: [color, '#e9ecef'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed || 0;
                            const percentage = ((value / initialAmount) * 100).toFixed(1);
                            return `${context.label}: ${value.toFixed(2)} € (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Mini graphique de tendance (sparkline)
 */
function createTrendSparkline(containerId, data, color) {
    const ctx = document.getElementById(containerId);
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map((_, i) => i),
            datasets: [{
                data: data,
                borderColor: color,
                backgroundColor: color + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false
                }
            }
        }
    });
}

/**
 * Initialisation de tous les graphiques
 */
document.addEventListener('DOMContentLoaded', function() {
    // Les données sont injectées depuis PHP via des attributs data-*
    const categoriesData = document.getElementById('categoryPieChart');
    if (categoriesData && categoriesData.dataset.categories) {
        try {
            const categories = JSON.parse(categoriesData.dataset.categories);
            createCategoryPieChart(categories);
            createTypeBarChart(categories);
        } catch (e) {
            console.error('Erreur parsing categories:', e);
        }
    }

    const budgetData = document.getElementById('budgetProgressChart');
    if (budgetData) {
        const initial = parseFloat(budgetData.dataset.initial || 0);
        const remaining = parseFloat(budgetData.dataset.remaining || 0);
        createBudgetProgressChart(initial, remaining);
    }
});
