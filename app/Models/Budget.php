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
        $required = ['user_id', 'start_date', 'initial_amount'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Le champ $field est requis");
            }
        }
        if (!is_numeric($data['initial_amount']) || $data['initial_amount'] <= 0) {
            throw new \Exception("Le montant initial doit être un nombre positif");
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
        $fixedCharges = R::find('expense', 'budget_id = ? AND is_fixed = TRUE', [$oldBudget->id]);

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
} 