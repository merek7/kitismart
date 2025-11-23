<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NotificationSettings;
use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use App\Utils\Mailer;

class NotificationController extends Controller
{
    /**
     * Afficher la page des paramètres de notifications
     */
    public function index()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];

            // Récupérer ou créer les paramètres
            $settings = NotificationSettings::findByUser($userId);
            if (!$settings) {
                $settings = NotificationSettings::createDefault($userId);
            }

            $this->view('dashboard/notification_settings', [
                'title' => 'Paramètres de Notifications',
                'currentPage' => 'settings',
                'settings' => $settings,
                'styles' => ['dashboard/notification_settings.css'],
                'pageScripts' => ['dashboard/notification_settings.js'],
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage des paramètres de notification: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue";
            $this->redirect('/settings');
        }
    }

    /**
     * Mettre à jour les paramètres de notifications
     */
    public function update()
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)$_SESSION['user_id'];

            // Mettre à jour les paramètres
            $settings = NotificationSettings::update($userId, $data);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès',
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            error_log("Erreur mise à jour paramètres notification: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer une alerte de budget (80% ou 100%)
     */
    public static function sendBudgetAlert(int $userId, float $percentage, $budget)
    {
        try {
            // Vérifier si les notifications sont activées
            if ($percentage >= 100 && !NotificationSettings::isAlert100Enabled($userId)) {
                return;
            }
            if ($percentage >= 80 && $percentage < 100 && !NotificationSettings::isAlert80Enabled($userId)) {
                return;
            }
            if (!NotificationSettings::isEmailEnabled($userId)) {
                return;
            }

            // Récupérer l'utilisateur
            $user = User::findById($userId);
            if (!$user) {
                return;
            }

            // Préparer les données pour l'email (nombres bruts, le formatage se fait dans le template)
            $data = [
                'percentage' => round($percentage, 2),
                'budget_initial' => (float)$budget->initial_amount,
                'budget_remaining' => (float)$budget->remaining_amount,
                'budget_spent' => (float)$budget->initial_amount - (float)$budget->remaining_amount,
                'is_over_budget' => $percentage > 100
            ];

            // Envoyer l'email avec la bonne méthode
            $mailer = new Mailer();
            $result = $mailer->sendBudgetAlertEmail($user->email, $user->nom, $data);

            if ($result) {
                error_log("✅ Email d'alerte budget envoyé à {$user->email}");
            } else {
                error_log("❌ Échec envoi email alerte budget à {$user->email}");
            }

        } catch (\Exception $e) {
            error_log("❌ Erreur envoi alerte budget: " . $e->getMessage());
        }
    }

    /**
     * Envoyer une alerte pour une dépense importante
     */
    public static function sendExpenseAlert(int $userId, $expense)
    {
        try {
            // Vérifier si les alertes de dépenses sont activées
            if (!NotificationSettings::isExpenseAlertEnabled($userId)) {
                return;
            }
            if (!NotificationSettings::isEmailEnabled($userId)) {
                return;
            }

            $threshold = NotificationSettings::getExpenseThreshold($userId);
            if ($expense->amount < $threshold) {
                return;
            }

            // Récupérer l'utilisateur
            $user = User::findById($userId);
            if (!$user) {
                return;
            }

            // Récupérer le budget actif
            $budget = Budget::getCurrentBudget($userId);

            // Récupérer la catégorie
            $categoryName = null;
            if ($expense->categorie_id) {
                $categoryName = ucfirst($expense->categorie->type ?? 'Autre');
            } elseif ($expense->custom_category_id) {
                $categoryName = $expense->customcategory->name ?? 'Autre';
            }

            // Préparer les données
            $data = [
                'user_name' => $user->nom,
                'expense_amount' => $expense->amount,
                'expense_description' => $expense->description,
                'expense_date' => $expense->payment_date,
                'expense_category' => $categoryName,
                'threshold' => $threshold,
                'budget_remaining' => $budget ? $budget->remaining_amount : null
            ];

            // Envoyer l'email avec la bonne méthode
            $mailer = new Mailer();
            $result = $mailer->sendExpenseAlertEmail($user->email, $user->nom, $data);

            if ($result) {
                error_log("✅ Email de confirmation dépense envoyé à {$user->email}");
            } else {
                error_log("❌ Échec envoi email alerte dépense à {$user->email}");
            }

        } catch (\Exception $e) {
            error_log("❌ Erreur envoi alerte dépense: " . $e->getMessage());
        }
    }

    /**
     * Envoyer le récapitulatif mensuel
     */
    public static function sendMonthlySummary(int $userId)
    {
        try {
            // Vérifier si le récapitulatif est activé
            if (!NotificationSettings::isMonthlySummaryEnabled($userId)) {
                return;
            }
            if (!NotificationSettings::isEmailEnabled($userId)) {
                return;
            }

            // Récupérer l'utilisateur
            $user = User::findById($userId);
            if (!$user) {
                return;
            }

            // Récupérer le budget actif
            $budget = Budget::getCurrentBudget($userId);
            if (!$budget) {
                return;
            }

            // Récupérer toutes les dépenses du budget
            $expenses = Expense::getExpensesByBudget($budget->id);
            $totalSpent = $budget->initial_amount - $budget->remaining_amount;
            $usagePercentage = $budget->initial_amount > 0 ? ($totalSpent / $budget->initial_amount) * 100 : 0;

            // Calculer les dépenses par catégorie
            $categoriesData = [];
            $categoryTotals = [];

            foreach ($expenses as $expense) {
                $categoryName = $expense->categorie_id ?
                    ucfirst($expense->categorie->type ?? 'Autre') :
                    ($expense->custom_category_id ? $expense->customcategory->name ?? 'Autre' : 'Autre');

                if (!isset($categoryTotals[$categoryName])) {
                    $categoryTotals[$categoryName] = 0;
                }
                $categoryTotals[$categoryName] += $expense->amount;
            }

            // Trier par montant décroissant
            arsort($categoryTotals);
            foreach ($categoryTotals as $name => $total) {
                $categoriesData[] = ['name' => $name, 'total' => $total];
            }

            // Top 5 des dépenses
            $topExpenses = [];
            $sortedExpenses = $expenses;
            usort($sortedExpenses, function($a, $b) {
                return $b->amount <=> $a->amount;
            });

            foreach (array_slice($sortedExpenses, 0, 5) as $expense) {
                $topExpenses[] = [
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'date' => $expense->payment_date
                ];
            }

            // Générer des insights personnalisés
            $insights = [];

            if ($usagePercentage > 100) {
                $insights[] = "Votre budget a été dépassé de " . round($usagePercentage - 100, 1) . "%. Pensez à augmenter votre budget mensuel.";
            } elseif ($usagePercentage > 80) {
                $insights[] = "Vous avez utilisé plus de 80% de votre budget. Surveillez vos dépenses de près.";
            } else {
                $insights[] = "Excellente gestion ! Vous restez en dessous de 80% de votre budget.";
            }

            if (!empty($categoriesData)) {
                $topCategory = $categoriesData[0];
                $insights[] = "Votre catégorie la plus dépensière est '{$topCategory['name']}' avec " . number_format($topCategory['total'], 0, ',', ' ') . " FCFA.";
            }

            if (count($expenses) > 0) {
                $avgExpense = $totalSpent / count($expenses);
                $insights[] = "Votre dépense moyenne est de " . number_format($avgExpense, 0, ',', ' ') . " FCFA.";
            }

            // Préparer les données pour l'email
            $period = date('F Y');
            $data = [
                'user_name' => $user->nom,
                'period' => $period,
                'budget_initial' => $budget->initial_amount,
                'budget_remaining' => $budget->remaining_amount,
                'total_spent' => $totalSpent,
                'expense_count' => count($expenses),
                'usage_percentage' => $usagePercentage,
                'categories' => $categoriesData,
                'top_expenses' => $topExpenses,
                'insights' => $insights
            ];

            // Envoyer l'email avec la bonne méthode
            $mailer = new Mailer();
            $result = $mailer->sendMonthlySummaryEmail($user->email, $user->nom, $data);

            if ($result) {
                error_log("✅ Récapitulatif mensuel envoyé à {$user->email}");
            } else {
                error_log("❌ Échec envoi récapitulatif mensuel à {$user->email}");
            }

        } catch (\Exception $e) {
            error_log("❌ Erreur envoi récapitulatif mensuel: " . $e->getMessage());
        }
    }
}
