<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\Expense;

class BudgetHistoryController extends Controller
{
    /**
     * Afficher l'historique des budgets
     */
    public function index()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];

            // Récupérer les filtres
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;

            // Récupérer l'historique des budgets
            error_log("Budget History: Fetching history for user $userId");
            $budgets = Budget::getHistory($userId, $year, $month, $status);
            error_log("Budget History: Found " . count($budgets) . " budgets");

            // Calculer les statistiques globales
            error_log("Budget History: Calculating stats");
            $stats = Budget::getHistoryStats($userId, $year, $month);

            // Récupérer les données pour le graphique d'évolution
            error_log("Budget History: Fetching evolution data");
            $chartData = Budget::getEvolutionData($userId);

            // Récupérer les années disponibles pour le filtre
            error_log("Budget History: Fetching available years");
            $availableYears = Budget::getAvailableYears($userId);
            error_log("Budget History: All data fetched successfully");

            $this->view('dashboard/budget_history', [
                'title' => 'Historique des Budgets',
                'currentPage' => 'budgets',
                'budgets' => $budgets,
                'stats' => $stats,
                'chartData' => $chartData,
                'availableYears' => $availableYears,
                'selectedYear' => $year,
                'selectedMonth' => $month,
                'selectedStatus' => $status,
                'styles' => ['dashboard/budget_history.css'],
                'pageScripts' => ['dashboard/budget_history.js'],
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            // Log détaillé de l'erreur avec stack trace
            error_log("=== ERREUR Budget History ===");
            error_log("Message: " . $e->getMessage());
            error_log("File: " . $e->getFile() . " (line " . $e->getLine() . ")");
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("=============================");

            $_SESSION['error'] = "Erreur historique: " . $e->getMessage();
            $this->redirect('/dashboard');
        }
    }

    /**
     * Afficher les détails d'un budget spécifique
     */
    public function show($id)
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $budget = Budget::findById((int)$id, $userId);

            if (!$budget) {
                return $this->jsonResponse(['success' => false, 'message' => 'Budget non trouvé'], 404);
            }

            // Récupérer les dépenses de ce budget
            $expenses = Expense::getExpensesByBudget($budget->id);

            // Calculer les stats
            $totalExpenses = 0;
            $paidExpenses = 0;
            $pendingExpenses = 0;

            foreach ($expenses as $expense) {
                $totalExpenses += $expense->amount;
                if ($expense->status === 'paid') {
                    $paidExpenses += $expense->amount;
                } else {
                    $pendingExpenses += $expense->amount;
                }
            }

            $budgetData = [
                'id' => $budget->id,
                'start_date' => $budget->start_date,
                'end_date' => $budget->end_date,
                'initial_amount' => $budget->initial_amount,
                'remaining_amount' => $budget->remaining_amount,
                'status' => $budget->status,
                'total_expenses' => $totalExpenses,
                'paid_expenses' => $paidExpenses,
                'pending_expenses' => $pendingExpenses,
                'expense_count' => count($expenses),
                'usage_percent' => round(($totalExpenses / $budget->initial_amount) * 100, 2)
            ];

            return $this->jsonResponse([
                'success' => true,
                'budget' => $budgetData,
                'expenses' => $expenses
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération du budget: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les données au format JSON pour AJAX
     */
    public function getData()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;

            $budgets = Budget::getHistory($userId, $year, $month, $status);
            $stats = Budget::getHistoryStats($userId, $year, $month);

            return $this->jsonResponse([
                'success' => true,
                'budgets' => $budgets,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter l'historique en CSV
     */
    public function exportCsv()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['error'] = "Non authentifié";
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;

            // Récupérer les budgets
            $budgets = Budget::getHistory($userId, $year, $month, $status);

            // Préparer les données CSV
            $csvData = [];
            $csvData[] = ['ID', 'Date début', 'Date fin', 'Budget initial', 'Dépensé', 'Restant', 'Utilisation (%)', 'Statut'];

            foreach ($budgets as $budget) {
                $spent = $budget->initial_amount - $budget->remaining_amount;
                $usagePercent = round(($spent / $budget->initial_amount) * 100, 2);

                $csvData[] = [
                    $budget->id,
                    $budget->start_date,
                    $budget->end_date ?? 'En cours',
                    number_format($budget->initial_amount, 2, ',', ' '),
                    number_format($spent, 2, ',', ' '),
                    number_format($budget->remaining_amount, 2, ',', ' '),
                    $usagePercent,
                    $budget->status === 'actif' ? 'Actif' : 'Clôturé'
                ];
            }

            // Générer le fichier CSV
            $filename = 'historique_budgets_' . date('Y-m-d_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Créer le fichier CSV
            $output = fopen('php://output', 'w');

            // Ajouter le BOM UTF-8 pour Excel
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Écrire les données
            foreach ($csvData as $row) {
                fputcsv($output, $row, ';');
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            error_log("Erreur lors de l'export CSV: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'export";
            $this->redirect('/budgets/history');
        }
    }
}
