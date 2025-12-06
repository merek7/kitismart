<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\ExpenseAttachment;

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
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = 12;

            // Récupérer l'historique des budgets avec pagination
            error_log("Budget History: Fetching history for user $userId");
            $budgets = Budget::getHistory($userId, $year, $month, $status, $page, $perPage);
            $totalBudgets = Budget::countHistory($userId, $year, $month, $status);
            $totalPages = ceil($totalBudgets / $perPage);
            error_log("Budget History: Found " . count($budgets) . " budgets (page $page/$totalPages)");

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
                'page' => $page,
                'totalPages' => $totalPages,
                'totalBudgets' => $totalBudgets,
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

            // Calculer les stats ET enrichir les dépenses avec les catégories
            $totalExpenses = 0;
            $paidExpenses = 0;
            $pendingExpenses = 0;
            $enrichedExpenses = [];

            foreach ($expenses as $expense) {
                $totalExpenses += $expense->amount;
                if ($expense->status === 'paid') {
                    $paidExpenses += $expense->amount;
                } else {
                    $pendingExpenses += $expense->amount;
                }

                // Enrichir avec les informations de catégorie
                $categoryName = 'Autre';

                // Vérifier d'abord si c'est une catégorie personnalisée
                $customCatId = $expense->custom_category_id ?? null;
                $catId = $expense->categorie_id ?? null;

                if (!empty($customCatId)) {
                    $customCat = \App\Models\CustomCategory::findById(
                        (int)$customCatId,
                        $userId
                    );
                    if ($customCat && isset($customCat->id) && $customCat->id) {
                        $categoryName = $customCat->name ?? 'Autre';
                    }
                }
                // Sinon, récupérer la catégorie par défaut
                elseif (!empty($catId)) {
                    $categorie = \App\Models\Categorie::findById((int)$catId);
                    if ($categorie && isset($categorie->id) && $categorie->id) {
                        $categoryName = $categorie->type ?? 'Autre';
                    }
                }

                // Récupérer les pièces jointes de cette dépense
                $attachments = ExpenseAttachment::findByExpense((int)$expense->id);
                $attachmentData = [];
                foreach ($attachments as $attachment) {
                    $attachmentData[] = [
                        'id' => $attachment->id,
                        'original_name' => $attachment->original_name,
                        'file_type' => $attachment->file_type,
                        'file_size' => $attachment->file_size,
                        'uploaded_at' => $attachment->uploaded_at
                    ];
                }

                $enrichedExpenses[] = [
                    'id' => $expense->id,
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'payment_date' => $expense->payment_date,
                    'status' => $expense->status,
                    'category' => $categoryName,
                    'attachments' => $attachmentData,
                    'attachments_count' => count($attachmentData)
                ];
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
                'usage_percent' => $budget->initial_amount > 0 
                    ? round(($totalExpenses / $budget->initial_amount) * 100, 2) 
                    : 0
            ];

            return $this->jsonResponse([
                'success' => true,
                'budget' => $budgetData,
                'expenses' => $enrichedExpenses
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
                $initialAmount = (float)$budget->initial_amount;
                $remainingAmount = (float)$budget->remaining_amount;
                $spent = $initialAmount - $remainingAmount;
                $usagePercent = $initialAmount > 0 ? round(($spent / $initialAmount) * 100, 2) : 0;

                $csvData[] = [
                    $budget->id,
                    $budget->start_date,
                    $budget->end_date ?? 'En cours',
                    number_format($initialAmount, 2, ',', ' '),
                    number_format($spent, 2, ',', ' '),
                    number_format($remainingAmount, 2, ',', ' '),
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

    /**
     * Exporter l'historique en PDF (page HTML imprimable)
     */
    public function exportPdf()
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
            $stats = Budget::getHistoryStats($userId, $year, $month);

            // Préparer le titre avec les filtres
            $filterText = '';
            if ($year) {
                $filterText .= $year;
                if ($month) {
                    $months = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                              'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                    $filterText = $months[$month] . ' ' . $filterText;
                }
            }

            // Générer le HTML
            $html = $this->generatePdfHtml($budgets, $stats, $filterText);

            // Afficher directement le HTML (l'utilisateur peut imprimer en PDF)
            echo $html;
            exit;

        } catch (\Exception $e) {
            error_log("Erreur lors de l'export PDF: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'export";
            $this->redirect('/budgets/history');
        }
    }

    /**
     * Générer le HTML pour l'export PDF
     */
    private function generatePdfHtml(array $budgets, array $stats, string $filterText): string
    {
        $totalBudget = number_format((float)($stats['total_initial'] ?? 0), 0, ',', ' ');
        $totalSpent = number_format((float)($stats['total_spent'] ?? 0), 0, ',', ' ');
        $avgUsage = number_format((float)($stats['average_usage'] ?? 0), 1, ',', ' ');

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Budgets - KitiSmart</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0d9488;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #666;
            font-size: 14px;
        }
        .header .date {
            color: #999;
            font-size: 11px;
            margin-top: 10px;
        }
        .stats-grid {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            gap: 15px;
        }
        .stat-box {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px 25px;
            text-align: center;
            flex: 1;
        }
        .stat-box .value {
            font-size: 20px;
            font-weight: bold;
            color: #0d9488;
        }
        .stat-box .label {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 10px 8px;
            text-align: left;
        }
        th {
            background: #0d9488;
            color: white;
            font-weight: 600;
            font-size: 11px;
        }
        tr:nth-child(even) { background: #f8f9fa; }
        tr:hover { background: #e6fffa; }
        .amount { text-align: right; font-family: monospace; }
        .percent { text-align: center; }
        .status { text-align: center; }
        .status-actif {
            background: #d1fae5;
            color: #065f46;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
        }
        .status-cloture {
            background: #e5e7eb;
            color: #4b5563;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0d9488;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .print-btn:hover { background: #0f766e; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        Imprimer / Enregistrer PDF
    </button>

    <div class="header">
        <h1>Historique des Budgets</h1>
        <div class="subtitle">' . ($filterText ?: 'Tous les budgets') . '</div>
        <div class="date">Généré le ' . date('d/m/Y à H:i') . '</div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="value">' . count($budgets) . '</div>
            <div class="label">Budgets</div>
        </div>
        <div class="stat-box">
            <div class="value">' . $totalBudget . ' F</div>
            <div class="label">Total budgété</div>
        </div>
        <div class="stat-box">
            <div class="value">' . $totalSpent . ' F</div>
            <div class="label">Total dépensé</div>
        </div>
        <div class="stat-box">
            <div class="value">' . $avgUsage . '%</div>
            <div class="label">Utilisation moyenne</div>
        </div>
    </div>';

        if (empty($budgets)) {
            $html .= '<div class="no-data">Aucun budget trouvé pour cette période.</div>';
        } else {
            $html .= '
    <table>
        <thead>
            <tr>
                <th>Période</th>
                <th class="amount">Budget initial</th>
                <th class="amount">Dépensé</th>
                <th class="amount">Restant</th>
                <th class="percent">Utilisation</th>
                <th class="status">Statut</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($budgets as $budget) {
                $initialAmount = (float)$budget->initial_amount;
                $remainingAmount = (float)$budget->remaining_amount;
                $spent = $initialAmount - $remainingAmount;
                $usagePercent = $initialAmount > 0 ? round(($spent / $initialAmount) * 100, 1) : 0;

                $startDate = date('d/m/Y', strtotime($budget->start_date));
                $endDate = $budget->end_date ? date('d/m/Y', strtotime($budget->end_date)) : 'En cours';
                $statusClass = $budget->status === 'actif' ? 'status-actif' : 'status-cloture';
                $statusText = $budget->status === 'actif' ? 'Actif' : 'Clôturé';

                $html .= '
            <tr>
                <td>' . $startDate . ' - ' . $endDate . '</td>
                <td class="amount">' . number_format($initialAmount, 0, ',', ' ') . ' F</td>
                <td class="amount">' . number_format($spent, 0, ',', ' ') . ' F</td>
                <td class="amount">' . number_format($remainingAmount, 0, ',', ' ') . ' F</td>
                <td class="percent">' . $usagePercent . '%</td>
                <td class="status"><span class="' . $statusClass . '">' . $statusText . '</span></td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>';
        }

        $html .= '
    <div class="footer">
        KitiSmart - Gestion de budget intelligente<br>
        Document généré automatiquement
    </div>

    <script>
        // Auto-print si paramètre présent
        if (window.location.search.includes("autoprint=1")) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Exporter les détails d'un budget en PDF
     */
    public function exportBudgetPdf($id)
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['error'] = "Non authentifié";
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $budget = Budget::findById((int)$id, $userId);

            if (!$budget) {
                $_SESSION['error'] = "Budget non trouvé";
                $this->redirect('/budgets/history');
                return;
            }

            // Récupérer les dépenses de ce budget
            $expenses = Expense::getExpensesByBudget($budget->id);

            // Enrichir les dépenses avec les catégories
            $enrichedExpenses = [];
            $totalPaid = 0;
            $totalPending = 0;

            foreach ($expenses as $expense) {
                $categoryName = 'Autre';

                if (!empty($expense->custom_category_id)) {
                    $customCat = \App\Models\CustomCategory::findById(
                        (int)$expense->custom_category_id,
                        $userId
                    );
                    if ($customCat && $customCat->id) {
                        $categoryName = $customCat->name;
                    }
                } elseif (!empty($expense->categorie_id)) {
                    $categorie = \App\Models\Categorie::findById((int)$expense->categorie_id);
                    if ($categorie && $categorie->id) {
                        $categoryName = $categorie->type;
                    }
                }

                $amount = (float)$expense->amount;
                if ($expense->status === 'paid') {
                    $totalPaid += $amount;
                } else {
                    $totalPending += $amount;
                }

                $enrichedExpenses[] = [
                    'description' => $expense->description,
                    'amount' => $amount,
                    'category' => $categoryName,
                    'payment_date' => $expense->payment_date,
                    'status' => $expense->status
                ];
            }

            // Générer le HTML
            $html = $this->generateBudgetDetailPdfHtml($budget, $enrichedExpenses, $totalPaid, $totalPending);

            echo $html;
            exit;

        } catch (\Exception $e) {
            error_log("Erreur lors de l'export PDF du budget: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'export";
            $this->redirect('/budgets/history');
        }
    }

    /**
     * Générer le HTML pour l'export PDF d'un budget détaillé
     */
    private function generateBudgetDetailPdfHtml($budget, array $expenses, float $totalPaid, float $totalPending): string
    {
        $initialAmount = (float)$budget->initial_amount;
        $remainingAmount = (float)$budget->remaining_amount;
        $spent = $initialAmount - $remainingAmount;
        $usagePercent = $initialAmount > 0 ? round(($spent / $initialAmount) * 100, 1) : 0;

        $startDate = date('d/m/Y', strtotime($budget->start_date));
        $endDate = $budget->end_date ? date('d/m/Y', strtotime($budget->end_date)) : 'En cours';
        $statusText = $budget->status === 'actif' ? 'Actif' : 'Clôturé';

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Budget - KitiSmart</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #0d9488;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .header .period {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .header .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 8px;
        }
        .header .status-actif {
            background: #d1fae5;
            color: #065f46;
        }
        .header .status-cloture {
            background: #e5e7eb;
            color: #4b5563;
        }
        .header .date {
            color: #999;
            font-size: 10px;
            margin-top: 10px;
        }
        .summary-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 10px;
        }
        .summary-box {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 15px;
            text-align: center;
            flex: 1;
        }
        .summary-box .value {
            font-size: 18px;
            font-weight: bold;
            color: #0d9488;
        }
        .summary-box .value.spent { color: #ef4444; }
        .summary-box .value.remaining { color: #10b981; }
        .summary-box .label {
            color: #666;
            font-size: 10px;
            margin-top: 3px;
        }
        .progress-container {
            margin-bottom: 25px;
            background: #e5e7eb;
            border-radius: 8px;
            height: 20px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #0d9488, #10b981);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }
        .progress-bar.warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .progress-bar.danger { background: linear-gradient(90deg, #ef4444, #f87171); }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background: #0d9488;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) { background: #f8f9fa; }
        .amount { text-align: right; font-family: monospace; }
        .status-cell { text-align: center; }
        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
        }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .totals {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-row .label { color: #666; }
        .totals-row .value { font-weight: bold; }
        .footer {
            margin-top: 25px;
            text-align: center;
            color: #999;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .print-btn {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #0d9488;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .print-btn:hover { background: #0f766e; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        Imprimer / PDF
    </button>

    <div class="header">
        <h1>Détails du Budget</h1>
        <div class="period">' . $startDate . ' - ' . $endDate . '</div>
        <span class="status status-' . ($budget->status === 'actif' ? 'actif' : 'cloture') . '">' . $statusText . '</span>
        <div class="date">Généré le ' . date('d/m/Y à H:i') . '</div>
    </div>

    <div class="summary-grid">
        <div class="summary-box">
            <div class="value">' . number_format($initialAmount, 0, ',', ' ') . ' F</div>
            <div class="label">Budget initial</div>
        </div>
        <div class="summary-box">
            <div class="value spent">' . number_format($spent, 0, ',', ' ') . ' F</div>
            <div class="label">Dépensé</div>
        </div>
        <div class="summary-box">
            <div class="value remaining">' . number_format($remainingAmount, 0, ',', ' ') . ' F</div>
            <div class="label">Restant</div>
        </div>
        <div class="summary-box">
            <div class="value">' . count($expenses) . '</div>
            <div class="label">Dépenses</div>
        </div>
    </div>

    <div class="progress-container">
        <div class="progress-bar ' . ($usagePercent > 100 ? 'danger' : ($usagePercent > 80 ? 'warning' : '')) . '" style="width: ' . min($usagePercent, 100) . '%">
            ' . $usagePercent . '% utilisé
        </div>
    </div>

    <h3 class="section-title">Liste des dépenses</h3>';

        if (empty($expenses)) {
            $html .= '<div class="no-data">Aucune dépense enregistrée pour ce budget.</div>';
        } else {
            $html .= '
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Catégorie</th>
                <th>Date</th>
                <th class="amount">Montant</th>
                <th class="status-cell">Statut</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($expenses as $expense) {
                $expenseDate = $expense['payment_date'] ? date('d/m/Y', strtotime($expense['payment_date'])) : '-';
                $statusClass = $expense['status'] === 'paid' ? 'badge-paid' : 'badge-pending';
                $statusLabel = $expense['status'] === 'paid' ? 'Payé' : 'En attente';

                $html .= '
            <tr>
                <td>' . htmlspecialchars($expense['description']) . '</td>
                <td>' . htmlspecialchars($expense['category']) . '</td>
                <td>' . $expenseDate . '</td>
                <td class="amount">' . number_format($expense['amount'], 0, ',', ' ') . ' F</td>
                <td class="status-cell"><span class="badge ' . $statusClass . '">' . $statusLabel . '</span></td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span class="label">Total payé</span>
            <span class="value" style="color: #10b981;">' . number_format($totalPaid, 0, ',', ' ') . ' F</span>
        </div>
        <div class="totals-row">
            <span class="label">Total en attente</span>
            <span class="value" style="color: #f59e0b;">' . number_format($totalPending, 0, ',', ' ') . ' F</span>
        </div>
        <div class="totals-row">
            <span class="label">Total général</span>
            <span class="value">' . number_format($totalPaid + $totalPending, 0, ',', ' ') . ' F</span>
        </div>
    </div>';
        }

        $html .= '
    <div class="footer">
        KitiSmart - Gestion de budget intelligente<br>
        Document généré automatiquement
    </div>
</body>
</html>';

        return $html;
    }
}
