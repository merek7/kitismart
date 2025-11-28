<?php

namespace App\Models;

use DateInterval;
use DateTime;
use RedBeanPHP\R as R;
use App\Exceptions\BudgetNotFoundException;

class Budget {

    const STATUS_ACTIVE = 'actif';
    const STATUS_CLOSED = 'cloturer';

    const TYPE_PRIMARY = 'principal';
    const TYPE_SECONDARY = 'secondaire';

    // Couleurs prédéfinies pour les budgets
    const COLORS = [
        '#0d9488' => 'Teal',
        '#3b82f6' => 'Bleu',
        '#8b5cf6' => 'Violet',
        '#ec4899' => 'Rose',
        '#f59e0b' => 'Orange',
        '#10b981' => 'Vert',
        '#ef4444' => 'Rouge',
        '#6366f1' => 'Indigo',
    ];

    public static function create(array $data) {

        self::validateBudgetData($data);

        // Déterminer le type de budget (principal par défaut)
        $type = $data['type'] ?? self::TYPE_PRIMARY;

        R::begin();
        try {
            $previousBudget = null;

            // Seulement clôturer l'ancien si c'est un budget principal
            if ($type === self::TYPE_PRIMARY) {
                $previousBudget = self::getActivePrimaryBudget($data['user_id']);
                if($previousBudget) {
                    self::closeBudget($previousBudget, $data['start_date']);
                }
            }

            $budget = R::dispense('budget');
            $budget->user_id = $data['user_id'];
            $budget->name = $data['name'] ?? 'Budget';
            $budget->description = $data['description'] ?? null;
            $budget->color = $data['color'] ?? '#0d9488';
            $budget->type = $type;
            $budget->start_date = $data['start_date'];
            $budget->end_date = null;
            $budget->initial_amount = $data['initial_amount'];
            $budget->remaining_amount = $data['initial_amount'];
            $budget->status = self::STATUS_ACTIVE;
            $budget->created_at = date('Y-m-d H:i:s');

            R::store($budget);

            // Répliquer les charges fixes seulement pour les budgets principaux
            if ($previousBudget && $type === self::TYPE_PRIMARY) {
                self::replicateFixedCharges($previousBudget, $budget);
                ExpenseAudit::log(
                    'Budget',[
                        'ancien budget' => $previousBudget->id,
                        'nouveau budget' => $budget->id,
                    ],
                    'Budget clôturé et nouvelle période commencée'
                );
            }

            R::commit();
            return $budget;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la création du budget: ' . $e->getMessage());
        }
    }

    private static function validateBudgetData(array $data) {
        // Vérifier les champs requis de base
        $required = ['user_id', 'start_date'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Le champ $field est requis");
            }
        }

        // Si le budget n'est pas illimité, valider le montant
        $isUnlimited = isset($data['is_unlimited']) && $data['is_unlimited'] === true;
        if (!$isUnlimited) {
            if (!isset($data['initial_amount'])) {
                throw new \Exception("Le champ initial_amount est requis");
            }
            if (!is_numeric($data['initial_amount']) || $data['initial_amount'] <= 0) {
                throw new \Exception("Le montant initial doit être un nombre positif");
            }
        }
    }

    /**
     * Récupère le budget actuellement sélectionné (via session) ou le budget actif par défaut
     * C'est LA méthode à utiliser partout pour respecter le switch de budget
     */
    public static function getCurrentBudget($userId) {
        // Si un budget est sélectionné en session, le retourner
        if (isset($_SESSION['current_budget_id'])) {
            $budget = self::getById($_SESSION['current_budget_id'], $userId);
            // Vérifier que le budget existe et est actif
            if ($budget && $budget->status === self::STATUS_ACTIVE) {
                return $budget;
            }
            // Si le budget n'est plus valide, nettoyer la session
            unset($_SESSION['current_budget_id']);
        }

        // Par défaut, retourner le budget principal actif
        return self::getActiveBudget($userId);
    }

    /**
     * Récupère le budget actif (pour rétrocompatibilité - retourne le principal ou le premier actif)
     */
    public static function getActiveBudget($userId) {
        // D'abord chercher un budget principal actif
        $primary = self::getActivePrimaryBudget($userId);
        if ($primary) {
            return $primary;
        }
        // Sinon retourner n'importe quel budget actif
        return R::findOne('budget', 'user_id = ? AND status = ? ORDER BY start_date DESC', [$userId, self::STATUS_ACTIVE]);
    }

    /**
     * Récupère le budget principal actif
     */
    public static function getActivePrimaryBudget($userId) {
        return R::findOne('budget',
            'user_id = ? AND status = ? AND (type = ? OR type IS NULL) ORDER BY start_date DESC',
            [$userId, self::STATUS_ACTIVE, self::TYPE_PRIMARY]
        );
    }

    /**
     * Récupère tous les budgets actifs d'un utilisateur (principal + secondaires)
     */
    public static function getAllActiveBudgets($userId) {
        return R::find('budget',
            'user_id = ? AND status = ? ORDER BY type ASC, start_date DESC',
            [$userId, self::STATUS_ACTIVE]
        );
    }

    /**
     * Récupère un budget par son ID (avec vérification user)
     */
    public static function getById($budgetId, $userId) {
        return R::findOne('budget', 'id = ? AND user_id = ?', [$budgetId, $userId]);
    }

    /**
     * Clôturer manuellement un budget secondaire
     */
    public static function closeSecondaryBudget($budgetId, $userId) {
        $budget = self::getById($budgetId, $userId);
        if (!$budget) {
            throw new \Exception('Budget non trouvé');
        }
        if ($budget->type === self::TYPE_PRIMARY) {
            throw new \Exception('Impossible de clôturer manuellement un budget principal');
        }
        self::closeBudget($budget, date('Y-m-d'));
        return $budget;
    }

    private static function closeBudget($budget, $endDate) {
        $budget->end_date = $endDate;
        $budget->status = self::STATUS_CLOSED;
        $budget->closed_at = date('Y-m-d H:i:s');
        R::store($budget);
    }

    private static function replicateFixedCharges($oldBudget, $newBudget) {
        // Récupérer les charges fixes (is_fixed = 1 ou true) de l'ancien budget
        $fixedCharges = R::find('expense', 'budget_id = ? AND is_fixed = 1', [$oldBudget->id]);

        foreach ($fixedCharges as $charge) {
            $newDate = self::calculateNewPaymentDate(
                $charge->payment_date,
                $oldBudget->start_date,
                $newBudget->start_date
            );

            $newCharge = R::dispense('expense');
            $newCharge->import([
                'budget_id' => $newBudget->id,
                'payment_date' => $newDate,
                'amount' => $charge->amount,
                'description' => $charge->description,
                'is_fixed' => true,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'is_replicated' => true
            ]);
            R::store($newCharge);
        }
    }

    private static function calculateNewPaymentDate($originalDate, $oldStartDate, $newStartDate) {
        try {
            $originalDateTime = new DateTime($originalDate);
            $oldStartDate = new DateTime($oldStartDate);
            $newStartDate = new DateTime($newStartDate);
            
            $daysDifference = $originalDateTime->diff($oldStartDate)->days;
            return $newStartDate->add(new DateInterval("P{$daysDifference}D"))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors du calcul de la nouvelle date de paiement: ' . $e->getMessage());
        }
    }

    public static function getBudgetSummary($budgetId) {
        $budget = R::load('budget', $budgetId);
        if(!$budget->id) {
            throw new \Exception('Budget non trouvé');
        }

        // Récupérer les dépenses groupées par catégorie par défaut (fixe, diver, epargne)
        $defaultCategories = R::getAll(
            'SELECT c.type as category, SUM(e.amount) as total, COUNT(*) as count
            FROM expense e
            INNER JOIN categorie c ON e.categorie_id = c.id
            WHERE e.budget_id = ? AND e.categorie_id IS NOT NULL
            GROUP BY c.type',
            [$budgetId]
        );

        // Récupérer les dépenses par catégorie personnalisée
        $customCategories = R::getAll(
            'SELECT cc.name as category, SUM(e.amount) as total, COUNT(*) as count
            FROM expense e
            INNER JOIN customcategory cc ON e.custom_category_id = cc.id
            WHERE e.budget_id = ? AND e.custom_category_id IS NOT NULL
            GROUP BY cc.id, cc.name',
            [$budgetId]
        );

        // Construire le résultat avec les types par défaut
        $result = [
            'fixe' => 0,
            'diver' => 0,
            'epargne' => 0,
            'total' => 0
        ];

        foreach ($defaultCategories as $cat) {
            $type = $cat['category'] ?? 'diver';
            $result[$type] = (float)$cat['total'];
            $result['total'] += (float)$cat['total'];
        }

        // Ajouter les catégories personnalisées (on les groupe dans "diver" pour le graphique principal)
        // mais on les retourne aussi séparément pour une utilisation détaillée
        $customCategoriesResult = [];
        foreach ($customCategories as $cat) {
            $customCategoriesResult[$cat['category']] = (float)$cat['total'];
            $result['diver'] += (float)$cat['total']; // Les catégories perso comptent dans "diver"
            $result['total'] += (float)$cat['total'];
        }

        return [
            'budget' => $budget,
            'expenses_categories' => $result,
            'custom_categories' => $customCategoriesResult,
            'montant_restant' => $budget->remaining_amount,
            // Format simplifié pour le graphique (clés directes)
            'fixe' => $result['fixe'],
            'diver' => $result['diver'],
            'epargne' => $result['epargne'],
            'total' => $result['total']
        ];
    }

    /**
     * Récupère les budgets précédents d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum de budgets à retourner (défaut: 10)
     * @return array Liste des budgets triés par date de début décroissante
     */
    public static function getPreviousBudgets($userId, $limit = 10) {
        return R::find('budget',
            'user_id = ? ORDER BY start_date DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    /**
     * Récupérer l'historique des budgets avec filtres
     */
    public static function getHistory(int $userId, ?int $year = null, ?int $month = null, ?string $status = null) {
        $sql = 'user_id = ?';
        $params = [$userId];

        if ($year) {
            // PostgreSQL: utiliser EXTRACT au lieu de YEAR()
            $sql .= ' AND EXTRACT(YEAR FROM start_date) = ?';
            $params[] = $year;
        }

        if ($month) {
            // PostgreSQL: utiliser EXTRACT au lieu de MONTH()
            $sql .= ' AND EXTRACT(MONTH FROM start_date) = ?';
            $params[] = $month;
        }

        if ($status && in_array($status, [self::STATUS_ACTIVE, self::STATUS_CLOSED])) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY start_date DESC';

        return R::find('budget', $sql, $params);
    }

    /**
     * Récupérer les statistiques de l'historique
     */
    public static function getHistoryStats(int $userId, ?int $year = null, ?int $month = null) {
        $budgets = self::getHistory($userId, $year, $month);

        $stats = [
            'total_budgets' => count($budgets),
            'active_budgets' => 0,
            'closed_budgets' => 0,
            'total_initial' => 0,
            'total_spent' => 0,
            'total_remaining' => 0,
            'average_usage' => 0
        ];

        foreach ($budgets as $budget) {
            if ($budget->status === self::STATUS_ACTIVE) {
                $stats['active_budgets']++;
            } else {
                $stats['closed_budgets']++;
            }

            $stats['total_initial'] += $budget->initial_amount;
            $stats['total_remaining'] += $budget->remaining_amount;
            $spent = $budget->initial_amount - $budget->remaining_amount;
            $stats['total_spent'] += $spent;
        }

        if ($stats['total_budgets'] > 0 && $stats['total_initial'] > 0) {
            $stats['average_usage'] = round(($stats['total_spent'] / $stats['total_initial']) * 100, 2);
        }

        return $stats;
    }

    /**
     * Récupérer les données d'évolution pour le graphique
     */
    public static function getEvolutionData(int $userId, int $months = 12) {
        $budgets = R::find('budget',
            'user_id = ? ORDER BY start_date DESC LIMIT ?',
            [$userId, $months]
        );

        $data = [
            'labels' => [],
            'initial' => [],
            'spent' => [],
            'remaining' => []
        ];

        // Inverser pour avoir l'ordre chronologique
        $budgets = array_reverse($budgets);

        foreach ($budgets as $budget) {
            $date = new \DateTime($budget->start_date);
            $data['labels'][] = $date->format('M Y');
            $data['initial'][] = $budget->initial_amount;
            $spent = $budget->initial_amount - $budget->remaining_amount;
            $data['spent'][] = $spent;
            $data['remaining'][] = $budget->remaining_amount;
        }

        return $data;
    }

    /**
     * Récupérer les années disponibles pour le filtre
     */
    public static function getAvailableYears(int $userId) {
        // PostgreSQL: utiliser EXTRACT au lieu de YEAR()
        $years = R::getCol(
            'SELECT DISTINCT EXTRACT(YEAR FROM start_date) as year FROM budget
            WHERE user_id = ?
            ORDER BY year DESC',
            [$userId]
        );

        return $years;
    }

    /**
     * Trouver un budget par ID et user ID
     */
    public static function findById(int $id, int $userId) {
        return R::findOne('budget', 'id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * Comparer plusieurs budgets
     * @param array $budgetIds IDs des budgets à comparer
     * @param int $userId ID de l'utilisateur
     * @param mixed $defaultCategories Catégories par défaut
     * @param mixed $customCategories Catégories personnalisées de l'utilisateur
     * @return array Données de comparaison
     */
    public static function compareBudgets(array $budgetIds, int $userId, $defaultCategories = [], $customCategories = []): array {
        $budgets = [];
        $comparisonData = [];

        foreach ($budgetIds as $budgetId) {
            $budget = self::findById((int)$budgetId, $userId);
            if (!$budget) {
                continue;
            }

            $summary = self::getBudgetSummary($budget->id);
            $spent = $budget->initial_amount - $budget->remaining_amount;
            $usagePercent = $budget->initial_amount > 0
                ? round(($spent / $budget->initial_amount) * 100, 1)
                : 0;

            $budgets[] = $budget;
            $comparisonData[] = [
                'budget' => $budget,
                'initial' => (float)$budget->initial_amount,
                'spent' => $spent,
                'remaining' => (float)$budget->remaining_amount,
                'usage_percent' => $usagePercent,
                'categories' => [
                    'fixe' => $summary['fixe'],
                    'diver' => $summary['diver'],
                    'epargne' => $summary['epargne']
                ],
                'custom_categories' => $summary['custom_categories'] ?? [],
                'expense_count' => self::getExpenseCount($budget->id),
                'period' => self::formatPeriod($budget)
            ];
        }

        // Calculer les différences si on compare 2 budgets
        $differences = [];
        if (count($comparisonData) === 2) {
            $differences = self::calculateDifferences($comparisonData[0], $comparisonData[1]);
        }

        return [
            'budgets' => $budgets,
            'data' => $comparisonData,
            'differences' => $differences,
            'chart_data' => self::prepareComparisonChartData($comparisonData, $defaultCategories, $customCategories)
        ];
    }

    /**
     * Calculer les différences entre deux budgets
     */
    private static function calculateDifferences(array $budget1, array $budget2): array {
        $calcDiff = function($val1, $val2) {
            $diff = $val2 - $val1;
            $percent = $val1 > 0 ? round((($val2 - $val1) / $val1) * 100, 1) : 0;
            return ['value' => $diff, 'percent' => $percent];
        };

        return [
            'initial' => $calcDiff($budget1['initial'], $budget2['initial']),
            'spent' => $calcDiff($budget1['spent'], $budget2['spent']),
            'remaining' => $calcDiff($budget1['remaining'], $budget2['remaining']),
            'usage_percent' => [
                'value' => round($budget2['usage_percent'] - $budget1['usage_percent'], 1),
                'percent' => 0
            ],
            'categories' => [
                'fixe' => $calcDiff($budget1['categories']['fixe'], $budget2['categories']['fixe']),
                'diver' => $calcDiff($budget1['categories']['diver'], $budget2['categories']['diver']),
                'epargne' => $calcDiff($budget1['categories']['epargne'], $budget2['categories']['epargne'])
            ]
        ];
    }

    /**
     * Préparer les données pour les graphiques de comparaison
     */
    private static function prepareComparisonChartData(array $comparisonData, $defaultCategories = [], $customCategories = []): array {
        $labels = [];
        $initialData = [];
        $spentData = [];
        $remainingData = [];

        // Préparer les données pour les catégories par défaut
        $defaultCatData = [];
        if (!empty($defaultCategories)) {
            foreach ($defaultCategories as $cat) {
                $type = $cat->type;
                $defaultCatData[$type] = [
                    'id' => $cat->id,
                    'name' => $cat->name ?? ucfirst($type),
                    'type' => $type,
                    'values' => []
                ];
            }
        } else {
            // Fallback si pas de catégories en base
            $defaultCatData = [
                'fixe' => ['id' => 1, 'name' => 'Charges Fixes', 'type' => 'fixe', 'values' => []],
                'diver' => ['id' => 2, 'name' => 'Divers', 'type' => 'diver', 'values' => []],
                'epargne' => ['id' => 3, 'name' => 'Épargne', 'type' => 'epargne', 'values' => []]
            ];
        }

        // Préparer les données pour les catégories personnalisées
        $customCatData = [];
        if (!empty($customCategories)) {
            foreach ($customCategories as $cat) {
                $customCatData[$cat->id] = [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon ?? 'fa-tag',
                    'color' => $cat->color ?? '#6b7280',
                    'values' => []
                ];
            }
        }

        foreach ($comparisonData as $data) {
            $labels[] = $data['budget']->name;
            $initialData[] = $data['initial'];
            $spentData[] = $data['spent'];
            $remainingData[] = $data['remaining'];

            // Ajouter les valeurs des catégories par défaut
            foreach ($defaultCatData as $type => &$catInfo) {
                $catInfo['values'][] = $data['categories'][$type] ?? 0;
            }

            // Ajouter les valeurs des catégories personnalisées
            foreach ($customCatData as $catId => &$catInfo) {
                $catName = $catInfo['name'];
                $catInfo['values'][] = $data['custom_categories'][$catName] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'overview' => [
                    'initial' => $initialData,
                    'spent' => $spentData,
                    'remaining' => $remainingData
                ],
                'default_categories' => $defaultCatData,
                'custom_categories' => $customCatData
            ]
        ];
    }

    /**
     * Compter le nombre de dépenses d'un budget
     */
    private static function getExpenseCount(int $budgetId): int {
        return (int) R::count('expense', 'budget_id = ?', [$budgetId]);
    }

    /**
     * Formater la période d'un budget
     */
    private static function formatPeriod($budget): string {
        $start = (new \DateTime($budget->start_date))->format('d/m/Y');
        if ($budget->end_date) {
            $end = (new \DateTime($budget->end_date))->format('d/m/Y');
            return "$start - $end";
        }
        return "$start - En cours";
    }

    /**
     * Récupérer tous les budgets d'un utilisateur pour la sélection
     */
    public static function getAllBudgetsForComparison(int $userId, int $limit = 24): array {
        $budgets = R::find('budget',
            'user_id = ? ORDER BY start_date DESC LIMIT ?',
            [$userId, $limit]
        );

        $result = [];
        foreach ($budgets as $budget) {
            $spent = $budget->initial_amount - $budget->remaining_amount;
            $result[] = [
                'id' => $budget->id,
                'name' => $budget->name,
                'type' => $budget->type,
                'status' => $budget->status,
                'color' => $budget->color,
                'period' => self::formatPeriod($budget),
                'initial' => (float)$budget->initial_amount,
                'spent' => $spent,
                'start_date' => $budget->start_date
            ];
        }

        return $result;
    }
} 