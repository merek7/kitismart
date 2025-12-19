<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\SavingsGoal;
use App\Models\Expense;
use RedBeanPHP\R;

class FinancialPlannerController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Afficher le planificateur financier
     */
    public function index()
    {
        $userId = (int)$_SESSION['user_id'];

        try {
            // Récupérer les budgets par source de revenu (tagués)
            $budgetsBySource = $this->getBudgetsBySource($userId);
            
            // Récupérer le budget principal si pas de budgets tagués
            $mainBudget = null;
            $needsTagging = empty($budgetsBySource);
            if ($needsTagging) {
                $mainBudget = Budget::getMainBudget($userId);
            }
            
            // Calculer les charges fixes réelles depuis les dépenses
            $fixedExpenses = $this->getFixedExpensesDetails($userId);
            
            // Calculer le résumé financier
            $financialSummary = $this->calculateFinancialSummary($userId, $budgetsBySource, $fixedExpenses);
            
            // Objectifs d'épargne actifs
            $savingsGoals = SavingsGoal::findActiveByUser($userId);
            
            // Propositions d'objectifs automatiques
            $suggestions = $this->generateGoalSuggestions($financialSummary);

            $this->view('dashboard/financial_planner', [
                'title' => 'Planificateur Financier',
                'currentPage' => 'planner',
                'layout' => 'dashboard',
                'budgetsBySource' => $budgetsBySource,
                'fixedExpenses' => $fixedExpenses,
                'summary' => $financialSummary,
                'savingsGoals' => $savingsGoals,
                'suggestions' => $suggestions,
                'sources' => Budget::getAvailableSources(),
                'goalIcons' => SavingsGoal::getAvailableIcons(),
                'needsTagging' => $needsTagging,
                'mainBudget' => $mainBudget
            ]);
        } catch (\Exception $e) {
            error_log("Erreur planificateur financier: " . $e->getMessage());
            $this->view('dashboard/financial_planner', [
                'title' => 'Planificateur Financier',
                'currentPage' => 'planner',
                'layout' => 'dashboard',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer les budgets groupés par source
     */
    private function getBudgetsBySource(int $userId): array
    {
        $sources = Budget::getAvailableSources();
        $result = [];
        
        foreach ($sources as $sourceKey => $sourceLabel) {
            $budgets = R::find('budget',
                'user_id = ? AND source_type = ? AND status = ? ORDER BY start_date DESC LIMIT 6',
                [$userId, $sourceKey, Budget::STATUS_ACTIVE]
            );
            
            if (!empty($budgets)) {
                $total = 0;
                $budgetList = [];
                foreach ($budgets as $budget) {
                    $total += (float)$budget->initial_amount;
                    $budgetList[] = [
                        'id' => $budget->id,
                        'name' => $budget->name,
                        'amount' => (float)$budget->initial_amount,
                        'date' => $budget->start_date
                    ];
                }
                $result[$sourceKey] = [
                    'label' => $sourceLabel,
                    'total' => $total,
                    'count' => count($budgetList),
                    'budgets' => $budgetList
                ];
            }
        }
        
        return $result;
    }

    /**
     * Récupérer le détail des charges fixes
     */
    private function getFixedExpensesDetails(int $userId): array
    {
        $result = [];
        $total = 0;
        
        // 1. Récupérer les dépenses récurrentes actives (via les budgets de l'utilisateur)
        $recurrences = R::getAll(
            'SELECT er.* FROM expenserecurrence er
             JOIN budget b ON er.budget_id = b.id
             WHERE b.user_id = ? AND er.is_active = 1',
            [$userId]
        );
        
        foreach ($recurrences as $rec) {
            $amount = (float)$rec['amount'];
            // Convertir en mensuel selon la fréquence
            switch ($rec['frequency']) {
                case 'weekly':
                    $amount = $amount * 4.33; // ~4.33 semaines par mois
                    break;
                case 'monthly':
                    // Déjà mensuel
                    break;
                case 'quarterly':
                    $amount = $amount / 3;
                    break;
                case 'yearly':
                    $amount = $amount / 12;
                    break;
            }
            $result[] = [
                'description' => $rec['description'],
                'amount' => round($amount, 0),
                'source' => 'recurrence'
            ];
            $total += round($amount, 0);
        }
        
        // 2. Chercher aussi les dépenses avec catégorie type "fixe"
        $startDate = (new \DateTime())->modify('-3 months')->format('Y-m-d');
        
        // Chercher les dépenses avec catégorie type "fixe"
        $fixedExpenses = R::getAll(
            'SELECT e.description, SUM(e.amount) as total_amount, COUNT(*) as occurrences
             FROM expense e
             JOIN budget b ON e.budget_id = b.id
             JOIN categorie c ON e.categorie_id = c.id
             WHERE b.user_id = ? AND e.payment_date >= ? AND LOWER(c.type) = ?
             GROUP BY e.description
             ORDER BY total_amount DESC',
            [$userId, $startDate, 'fixe']
        );
        
        foreach ($fixedExpenses as $expense) {
            // Pour les charges fixes, prendre le montant moyen par occurrence (pas divisé par 3 mois)
            $occurrences = max(1, (int)$expense['occurrences']);
            $avgAmount = round((float)$expense['total_amount'] / $occurrences, 0);
            
            $result[] = [
                'description' => $expense['description'],
                'amount' => $avgAmount,
                'source' => 'fixed_category'
            ];
            $total += $avgAmount;
        }
        
        // 3. Si toujours vide, chercher les dépenses qui se répètent
        if (empty($result)) {
            $expenses = R::getAll(
                'SELECT e.description, AVG(e.amount) as avg_amount, COUNT(*) as occurrences
                 FROM expense e
                 JOIN budget b ON e.budget_id = b.id
                 WHERE b.user_id = ? AND e.payment_date >= ?
                 GROUP BY e.description
                 HAVING COUNT(*) >= 2
                 ORDER BY avg_amount DESC
                 LIMIT 10',
                [$userId, $startDate]
            );
            
            foreach ($expenses as $expense) {
                $avgAmount = round((float)$expense['avg_amount'], 0);
                $result[] = [
                    'description' => $expense['description'],
                    'amount' => $avgAmount,
                    'source' => 'recurring_expense'
                ];
                $total += $avgAmount;
            }
        }
        
        return [
            'items' => $result,
            'total' => $total
        ];
    }

    /**
     * Calculer le résumé financier
     */
    private function calculateFinancialSummary(int $userId, array $budgetsBySource, array $fixedExpenses): array
    {
        // Revenu mensuel régulier (salaires)
        $monthlyIncome = 0;
        if (isset($budgetsBySource[Budget::SOURCE_SALARY])) {
            // Prendre le dernier salaire comme référence
            $salaryBudgets = $budgetsBySource[Budget::SOURCE_SALARY]['budgets'];
            if (!empty($salaryBudgets)) {
                $monthlyIncome = $salaryBudgets[0]['amount'];
            }
        }
        
        // Revenus exceptionnels (primes, bonus, etc.) sur l'année
        $exceptionalIncome = 0;
        $exceptionalSources = [Budget::SOURCE_BONUS, Budget::SOURCE_FREELANCE, Budget::SOURCE_GIFT, Budget::SOURCE_RENTAL];
        foreach ($exceptionalSources as $source) {
            if (isset($budgetsBySource[$source])) {
                $exceptionalIncome += $budgetsBySource[$source]['total'];
            }
        }
        
        // Charges fixes mensuelles
        $monthlyExpenses = $fixedExpenses['total'];
        
        // Disponible mensuel
        $monthlyAvailable = $monthlyIncome - $monthlyExpenses;
        
        // Disponible annuel (avec revenus exceptionnels)
        $yearlyAvailable = ($monthlyAvailable * 12) + $exceptionalIncome;
        
        return [
            'monthly_income' => $monthlyIncome,
            'exceptional_income' => $exceptionalIncome,
            'monthly_expenses' => $monthlyExpenses,
            'monthly_available' => $monthlyAvailable,
            'yearly_available' => $yearlyAvailable,
            'can_save' => $monthlyAvailable > 0
        ];
    }

    /**
     * Générer des suggestions d'objectifs automatiques
     */
    private function generateGoalSuggestions(array $summary): array
    {
        $suggestions = [];
        $monthlyAvailable = $summary['monthly_available'];
        $yearlyAvailable = $summary['yearly_available'];
        
        if ($monthlyAvailable <= 0) {
            return $suggestions;
        }
        
        // Objectifs prédéfinis avec montants typiques
        $goals = [
            ['name' => 'Fonds d\'urgence', 'icon' => 'fa-shield-alt', 'amount' => $summary['monthly_expenses'] * 6, 'priority' => 1],
            ['name' => 'Voiture', 'icon' => 'fa-car', 'amount' => 5000000, 'priority' => 2],
            ['name' => 'Terrain', 'icon' => 'fa-map', 'amount' => 4500000, 'priority' => 3],
            ['name' => 'Mariage', 'icon' => 'fa-ring', 'amount' => 3000000, 'priority' => 4],
            ['name' => 'Voyage', 'icon' => 'fa-plane', 'amount' => 1500000, 'priority' => 5],
            ['name' => 'Électronique', 'icon' => 'fa-laptop', 'amount' => 800000, 'priority' => 6],
        ];
        
        foreach ($goals as $goal) {
            if ($goal['amount'] <= 0) continue;
            
            // Calculer avec épargne mensuelle seulement
            $monthsNeeded = ceil($goal['amount'] / $monthlyAvailable);
            $yearsNeeded = round($monthsNeeded / 12, 1);
            
            // Calculer avec revenus exceptionnels inclus
            $monthsWithBonus = $yearlyAvailable > 0 ? ceil($goal['amount'] / ($yearlyAvailable / 12)) : $monthsNeeded;
            $yearsWithBonus = round($monthsWithBonus / 12, 1);
            
            $suggestions[] = [
                'name' => $goal['name'],
                'icon' => $goal['icon'],
                'amount' => $goal['amount'],
                'monthly_savings' => min($monthlyAvailable, ceil($goal['amount'] / 36)), // Max 3 ans
                'months_needed' => $monthsNeeded,
                'years_needed' => $yearsNeeded,
                'months_with_bonus' => $monthsWithBonus,
                'years_with_bonus' => $yearsWithBonus,
                'is_realistic' => $monthsNeeded <= 60, // Réaliste si moins de 5 ans
                'priority' => $goal['priority']
            ];
        }
        
        return $suggestions;
    }

    /**
     * API: Simuler un objectif d'épargne
     */
    public function simulate()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        // Parser les montants (enlever espaces et virgules)
        $targetAmountRaw = str_replace([' ', ','], ['', '.'], (string)($data['target_amount'] ?? '0'));
        $additionalIncomeRaw = str_replace([' ', ','], ['', '.'], (string)($data['additional_income'] ?? '0'));
        
        $targetAmount = (float)$targetAmountRaw;
        $targetMonths = (int)($data['target_months'] ?? 0);
        $additionalIncomeValue = (float)$additionalIncomeRaw;
        $additionalPeriod = $data['additional_period'] ?? 'month';
        $projectName = $data['project_name'] ?? 'Objectif';
        
        // Convertir le revenu additionnel en mensuel selon la période
        $additionalIncome = 0;
        $additionalOnce = 0; // Pour les revenus uniques
        if ($additionalIncomeValue > 0) {
            switch ($additionalPeriod) {
                case 'month':
                    $additionalIncome = $additionalIncomeValue;
                    break;
                case 'quarter':
                    $additionalIncome = $additionalIncomeValue / 3;
                    break;
                case 'year':
                    $additionalIncome = $additionalIncomeValue / 12;
                    break;
                case 'once':
                    // Revenu unique : garde séparément pour le calcul
                    $additionalOnce = $additionalIncomeValue;
                    break;
            }
        }

        if ($targetAmount <= 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Montant cible invalide'], 400);
        }

        // Récupérer les données financières de l'utilisateur
        $budgetsBySource = $this->getBudgetsBySource($userId);
        $fixedExpenses = $this->getFixedExpensesDetails($userId);
        $summary = $this->calculateFinancialSummary($userId, $budgetsBySource, $fixedExpenses);

        // Calculer la capacité d'épargne
        $monthlyAvailable = $summary['monthly_available'];
        
        // Le revenu additionnel récurrent augmente la capacité mensuelle
        $monthlyWithAdditional = $monthlyAvailable + $additionalIncome;
        
        // Le revenu unique réduit le montant à épargner
        $effectiveTarget = $targetAmount - $additionalOnce;
        if ($effectiveTarget < 0) $effectiveTarget = 0;

        $result = [
            'project_name' => $projectName,
            'target_amount' => $targetAmount,
            'effective_target' => $effectiveTarget,
            'monthly_available' => $monthlyAvailable,
            'additional_income' => $additionalIncome,
            'additional_once' => $additionalOnce,
            'monthly_with_additional' => $monthlyWithAdditional
        ];

        // Utiliser le montant effectif (après déduction du revenu unique)
        $amountToSave = $effectiveTarget > 0 ? $effectiveTarget : $targetAmount;
        
        // Calculer selon le délai souhaité ou automatiquement
        if ($targetMonths > 0) {
            // Délai spécifié par l'utilisateur
            $monthlyNeeded = ceil($amountToSave / $targetMonths);
            $result['target_months'] = $targetMonths;
            $result['monthly_needed'] = $monthlyNeeded;
            $result['target_date'] = (new \DateTime())->modify("+$targetMonths months")->format('Y-m-d');
            $result['is_realistic'] = $monthlyNeeded <= $monthlyWithAdditional;
            $result['percent_of_capacity'] = $monthlyWithAdditional > 0 
                ? round(($monthlyNeeded / $monthlyWithAdditional) * 100, 1) 
                : 0;

            // Proposer une alternative si pas réaliste
            if (!$result['is_realistic'] && $monthlyWithAdditional > 0) {
                $alternativeMonths = ceil($amountToSave / $monthlyWithAdditional);
                $result['alternative'] = [
                    'months' => $alternativeMonths,
                    'years' => round($alternativeMonths / 12, 1),
                    'monthly' => round($monthlyWithAdditional, 0),
                    'target_date' => (new \DateTime())->modify("+$alternativeMonths months")->format('Y-m-d')
                ];
            }
        } else {
            // Calcul automatique - proposer plusieurs scénarios
            
            // Si pas de capacité d'épargne du tout
            if ($monthlyWithAdditional <= 0 && $effectiveTarget > 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Capacité d\'épargne insuffisante. Ajoutez un revenu additionnel ou taguez vos budgets (salaire, prime...).'
                ], 400);
            }
            
            // Si le revenu unique couvre tout l'objectif
            if ($effectiveTarget <= 0) {
                $result['scenarios'] = [];
                $result['is_realistic'] = true;
                $result['covered_by_once'] = true;
            } else {
                $scenarios = [];
                
                // Scénario 1 : Épargne prudente (50% du disponible)
                $prudentMonthly = $monthlyWithAdditional * 0.5;
                if ($prudentMonthly > 0) {
                    $prudentMonths = ceil($amountToSave / $prudentMonthly);
                    $scenarios['prudent'] = [
                        'label' => 'Prudent (50%)',
                        'monthly' => round($prudentMonthly, 0),
                        'months' => $prudentMonths,
                        'years' => round($prudentMonths / 12, 1),
                        'target_date' => (new \DateTime())->modify("+$prudentMonths months")->format('Y-m-d')
                    ];
                }

                // Scénario 2 : Épargne modérée (75% du disponible)
                $moderateMonthly = $monthlyWithAdditional * 0.75;
                if ($moderateMonthly > 0) {
                    $moderateMonths = ceil($amountToSave / $moderateMonthly);
                    $scenarios['moderate'] = [
                        'label' => 'Modéré (75%)',
                        'monthly' => round($moderateMonthly, 0),
                        'months' => $moderateMonths,
                        'years' => round($moderateMonths / 12, 1),
                        'target_date' => (new \DateTime())->modify("+$moderateMonths months")->format('Y-m-d')
                    ];
                }

                // Scénario 3 : Épargne intensive (100% du disponible)
                $intensiveMonths = ceil($amountToSave / $monthlyWithAdditional);
                $scenarios['intensive'] = [
                    'label' => 'Intensif (100%)',
                    'monthly' => round($monthlyWithAdditional, 0),
                    'months' => $intensiveMonths,
                    'years' => round($intensiveMonths / 12, 1),
                    'target_date' => (new \DateTime())->modify("+$intensiveMonths months")->format('Y-m-d')
                ];

                $result['scenarios'] = $scenarios;
                $result['is_realistic'] = true;
            }
        }

        // Impact du revenu additionnel (récurrent)
        if ($additionalIncome > 0 && $monthlyWithAdditional > 0) {
            $monthsWithBonus = ceil($amountToSave / $monthlyWithAdditional);
            
            if ($monthlyAvailable > 0) {
                $monthsWithoutBonus = ceil($amountToSave / $monthlyAvailable);
                $result['bonus_impact'] = [
                    'months_saved' => $monthsWithoutBonus - $monthsWithBonus,
                    'without_bonus' => $monthsWithoutBonus,
                    'with_bonus' => $monthsWithBonus
                ];
            } else {
                $result['bonus_impact'] = [
                    'months_saved' => 0,
                    'without_bonus' => 0,
                    'with_bonus' => $monthsWithBonus,
                    'only_source' => true
                ];
            }
        }
        
        // Impact du revenu unique
        if ($additionalOnce > 0) {
            $result['once_impact'] = [
                'amount' => $additionalOnce,
                'remaining' => $effectiveTarget,
                'covered' => $effectiveTarget <= 0
            ];
        }

        return $this->jsonResponse([
            'success' => true,
            'simulation' => $result
        ]);
    }

    /**
     * API: Obtenir les données du planificateur
     */
    public function getData()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $userId = (int)$_SESSION['user_id'];
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

        try {
            $incomeStats = Budget::getIncomeStatsBySource($userId, $year);
            $savingsCapacity = Budget::getEstimatedSavingsCapacity($userId);
            $savingsStats = SavingsGoal::getUserStats($userId);

            // Calculer le total des revenus
            $totalIncome = 0;
            foreach ($incomeStats as $stat) {
                $totalIncome += $stat['total'];
            }

            return $this->jsonResponse([
                'success' => true,
                'year' => $year,
                'income_stats' => $incomeStats,
                'total_income' => $totalIncome,
                'savings_capacity' => $savingsCapacity,
                'savings_stats' => $savingsStats
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Créer un objectif d'épargne depuis le planificateur
     */
    public function createGoal()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        $name = trim($data['name'] ?? '');
        $targetAmount = (float)str_replace([' ', ','], ['', '.'], (string)($data['target_amount'] ?? '0'));
        $monthlyContribution = (float)str_replace([' ', ','], ['', '.'], (string)($data['monthly_contribution'] ?? '0'));
        $targetDate = $data['target_date'] ?? null;
        $icon = $data['icon'] ?? 'fa-piggy-bank';
        $color = $data['color'] ?? '#0d9488';

        if (empty($name)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Le nom est requis'], 400);
        }

        if ($targetAmount <= 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Le montant cible est invalide'], 400);
        }

        try {
            $goal = SavingsGoal::create([
                'user_id' => $userId,
                'name' => $name,
                'target_amount' => $targetAmount,
                'current_amount' => 0,
                'target_date' => $targetDate,
                'monthly_contribution' => $monthlyContribution,
                'icon' => $icon,
                'color' => $color,
                'status' => 'active'
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Objectif d\'épargne créé avec succès',
                'goal' => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => (float)$goal->target_amount,
                    'monthly_contribution' => (float)$goal->monthly_contribution
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Taguer un budget avec une source
     */
    public function tagBudget()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        $budgetId = (int)($data['budget_id'] ?? 0);
        $sourceType = $data['source_type'] ?? null;

        if ($budgetId <= 0) {
            return $this->jsonResponse(['success' => false, 'message' => 'Budget invalide'], 400);
        }

        if (empty($sourceType)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Source requise'], 400);
        }

        $validSources = array_keys(Budget::getAvailableSources());
        if (!in_array($sourceType, $validSources)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Source invalide'], 400);
        }

        $success = Budget::updateSourceType($budgetId, $userId, $sourceType);

        if ($success) {
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Budget tagué avec succès'
            ]);
        } else {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Impossible de taguer ce budget'
            ], 400);
        }
    }

    /**
     * Obtenir des conseils IA personnalisés
     */
    public function getAIAdvice()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $userId = (int)$_SESSION['user_id'];
        $data = json_decode(file_get_contents('php://input'), true);

        // Vérifier si le service est disponible
        $geminiService = new \App\Services\GeminiService();
        
        // Vérifier les limites avant de faire la requête
        $canRequest = $geminiService->canUserMakeRequest($userId);
        if (!$canRequest['allowed']) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $canRequest['reason'],
                'remaining' => $canRequest['remaining'] ?? 0,
                'cooldown' => $canRequest['cooldown'] ?? null
            ]);
        }

        // Préparer les données financières
        $fixedExpenses = $this->getFixedExpensesDetails($userId);
        $budgetsBySource = $this->getBudgetsBySource($userId);
        
        $monthlyIncome = 0;
        foreach ($budgetsBySource as $source) {
            $monthlyIncome += $source['total'];
        }

        $financialData = [
            'monthly_income' => $monthlyIncome,
            'fixed_expenses' => $fixedExpenses['total'],
            'available' => $monthlyIncome - $fixedExpenses['total'],
            'expenses_details' => $fixedExpenses['items'],
            'savings_goal' => $data['goal_name'] ?? '',
            'target_amount' => $data['target_amount'] ?? 0,
            'prompt_type' => $data['prompt_type'] ?? 'general',
            'custom_question' => $data['custom_question'] ?? ''
        ];

        $result = $geminiService->getFinancialAdvice($userId, $financialData);

        return $this->jsonResponse($result);
    }

    /**
     * Vérifier le statut des requêtes IA disponibles
     */
    public function checkAIStatus()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $userId = (int)$_SESSION['user_id'];
        $geminiService = new \App\Services\GeminiService();
        $status = $geminiService->canUserMakeRequest($userId);

        return $this->jsonResponse([
            'success' => true,
            'can_request' => $status['allowed'],
            'remaining' => $status['remaining'] ?? 0,
            'reason' => $status['reason'] ?? null,
            'cooldown' => $status['cooldown'] ?? null
        ]);
    }
}
