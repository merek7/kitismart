<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\Categorie;
use App\Models\CustomCategory;

class BudgetComparisonController extends Controller
{
    /**
     * Afficher la page de comparaison de budgets
     */
    public function index()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];

            // Récupérer tous les budgets disponibles pour la comparaison
            $availableBudgets = Budget::getAllBudgetsForComparison($userId);

            // Récupérer les catégories par défaut
            $defaultCategories = Categorie::findAll();

            // Récupérer les catégories personnalisées de l'utilisateur
            $customCategories = CustomCategory::findByUser($userId);

            // Vérifier si des budgets sont sélectionnés
            $selectedIds = [];
            if (isset($_GET['budgets']) && is_array($_GET['budgets'])) {
                $selectedIds = array_map('intval', $_GET['budgets']);
            }

            $comparisonResult = null;
            if (!empty($selectedIds)) {
                $comparisonResult = Budget::compareBudgets($selectedIds, $userId, $defaultCategories, $customCategories);
            }

            $this->view('dashboard/budget_comparison', [
                'title' => 'Comparaison de Budgets',
                'currentPage' => 'comparison',
                'availableBudgets' => $availableBudgets,
                'selectedIds' => $selectedIds,
                'comparison' => $comparisonResult,
                'defaultCategories' => $defaultCategories,
                'customCategories' => $customCategories,
                'styles' => ['dashboard/budget_comparison.css'],
                'pageScripts' => ['dashboard/budget_comparison.js'],
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur comparaison budgets: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la comparaison: " . $e->getMessage();
            $this->redirect('/dashboard');
        }
    }

    /**
     * Export PDF de la comparaison
     */
    public function exportPdf()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];

            // Récupérer les IDs des budgets depuis GET
            $selectedIds = [];
            if (isset($_GET['budgets']) && is_array($_GET['budgets'])) {
                $selectedIds = array_map('intval', $_GET['budgets']);
            }

            if (count($selectedIds) < 2) {
                $_SESSION['error'] = "Veuillez sélectionner au moins 2 budgets à comparer";
                $this->redirect('/budget/comparison');
                return;
            }

            // Récupérer les catégories
            $defaultCategories = Categorie::findAll();
            $customCategories = CustomCategory::findByUser($userId);

            // Obtenir les données de comparaison
            $comparisonResult = Budget::compareBudgets($selectedIds, $userId, $defaultCategories, $customCategories);

            // Générer le HTML du rapport
            $html = $this->generateComparisonPdfHtml($comparisonResult, $userId);

            // Headers pour l'affichage HTML print-friendly
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;

        } catch (\Exception $e) {
            error_log("Erreur export PDF comparaison: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de l'export PDF";
            $this->redirect('/budget/comparison');
        }
    }

    /**
     * Générer le HTML pour le PDF de comparaison
     */
    private function generateComparisonPdfHtml(array $comparison, int $userId): string
    {
        $user = \RedBeanPHP\R::load('users', $userId);
        $data = $comparison['data'];
        $differences = $comparison['differences'] ?? null;

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Comparaison de Budgets - KitiSmart</title>
    <style>
        @media print {
            body { margin: 0; padding: 15px; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .report-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0d9488;
        }
        .header h1 {
            margin: 0;
            color: #0d9488;
            font-size: 24px;
        }
        .header p { margin: 5px 0; color: #666; }
        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(' . count($data) . ', 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .budget-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            background: #f9fafb;
        }
        .budget-card h3 {
            margin: 0 0 10px 0;
            color: #0d9488;
            font-size: 16px;
            border-bottom: 2px solid currentColor;
            padding-bottom: 8px;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { color: #666; font-size: 13px; }
        .stat-value { font-weight: bold; color: #333; }
        .stat-value.positive { color: #10b981; }
        .stat-value.negative { color: #ef4444; }
        .category-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #d1d5db;
        }
        .category-section h4 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .differences-section {
            margin-top: 30px;
            padding: 20px;
            background: #f0fdfa;
            border-radius: 8px;
            border: 1px solid #99f6e4;
        }
        .differences-section h2 {
            margin: 0 0 15px 0;
            color: #0d9488;
            font-size: 18px;
        }
        .diff-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .diff-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            border-radius: 6px;
        }
        .usage-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        .usage-fill {
            height: 100%;
            border-radius: 4px;
        }
        .usage-low { background: #10b981; }
        .usage-medium { background: #f59e0b; }
        .usage-high { background: #ef4444; }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .print-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px 30px;
            background: #0d9488;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }
        .print-btn:hover { background: #0f766e; }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="header">
            <h1>📊 Comparaison de Budgets</h1>
            <p><strong>' . htmlspecialchars($user->nom ?? 'Utilisateur') . '</strong></p>
            <p>Comparaison de ' . count($data) . ' budgets</p>
            <p>Généré le ' . date('d/m/Y à H:i') . '</p>
        </div>

        <div class="comparison-grid">';

        foreach ($data as $budgetData) {
            $usageClass = $budgetData['usage_percent'] < 50 ? 'usage-low' : ($budgetData['usage_percent'] < 80 ? 'usage-medium' : 'usage-high');

            $html .= '
            <div class="budget-card">
                <h3>' . htmlspecialchars($budgetData['budget']->name ?? 'Budget') . '</h3>
                <p style="font-size: 12px; color: #666; margin: 0 0 15px 0;">' . htmlspecialchars($budgetData['period'] ?? '') . '</p>

                <div class="stat-row">
                    <span class="stat-label">Budget Initial</span>
                    <span class="stat-value">' . number_format($budgetData['initial'], 0, ',', ' ') . ' FCFA</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total Dépensé</span>
                    <span class="stat-value negative">' . number_format($budgetData['spent'], 0, ',', ' ') . ' FCFA</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Restant</span>
                    <span class="stat-value positive">' . number_format($budgetData['remaining'], 0, ',', ' ') . ' FCFA</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Utilisation</span>
                    <span class="stat-value">' . $budgetData['usage_percent'] . '%</span>
                </div>

                <div class="usage-bar">
                    <div class="usage-fill ' . $usageClass . '" style="width: ' . min($budgetData['usage_percent'], 100) . '%"></div>
                </div>

                <div class="category-section">
                    <h4>Répartition par catégorie</h4>
                    <div class="stat-row">
                        <span class="stat-label">Charges Fixes</span>
                        <span class="stat-value">' . number_format($budgetData['categories']['fixe'] ?? 0, 0, ',', ' ') . '</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Divers</span>
                        <span class="stat-value">' . number_format($budgetData['categories']['diver'] ?? 0, 0, ',', ' ') . '</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Épargne</span>
                        <span class="stat-value">' . number_format($budgetData['categories']['epargne'] ?? 0, 0, ',', ' ') . '</span>
                    </div>';

            // Catégories personnalisées
            if (!empty($budgetData['custom_categories'])) {
                foreach ($budgetData['custom_categories'] as $catName => $catTotal) {
                    $html .= '
                    <div class="stat-row">
                        <span class="stat-label">' . htmlspecialchars($catName ?? '') . '</span>
                        <span class="stat-value">' . number_format($catTotal ?? 0, 0, ',', ' ') . '</span>
                    </div>';
                }
            }

            $html .= '
                </div>

                <p style="margin-top: 15px; font-size: 12px; color: #666;">
                    📝 ' . $budgetData['expense_count'] . ' dépense(s)
                </p>
            </div>';
        }

        $html .= '</div>';

        // Section des différences (pour 2 budgets)
        if ($differences) {
            $html .= '
        <div class="differences-section">
            <h2>📈 Évolution entre les deux budgets</h2>
            <div class="diff-grid">';

            $diffItems = [
                ['label' => 'Budget Initial', 'key' => 'initial'],
                ['label' => 'Dépenses', 'key' => 'spent'],
                ['label' => 'Restant', 'key' => 'remaining'],
                ['label' => 'Taux d\'utilisation', 'key' => 'usage_percent', 'isPercent' => true]
            ];

            foreach ($diffItems as $item) {
                $value = $differences[$item['key']]['value'];
                $percent = $differences[$item['key']]['percent'];
                $isPositive = $value >= 0;
                $isPercent = isset($item['isPercent']) && $item['isPercent'];
                $colorClass = $isPositive ? 'positive' : 'negative';
                $arrow = $isPositive ? '↑' : '↓';

                $html .= '
                <div class="diff-item">
                    <span class="stat-label">' . $item['label'] . '</span>
                    <span class="stat-value ' . $colorClass . '">
                        ' . $arrow . ' ' . ($isPositive ? '+' : '') . ($isPercent ? $value . ' pts' : number_format($value, 0, ',', ' ') . ' FCFA');
                if (!$isPercent && $percent != 0) {
                    $html .= ' <small>(' . ($isPositive ? '+' : '') . $percent . '%)</small>';
                }
                $html .= '</span>
                </div>';
            }

            $html .= '
            </div>
        </div>';
        }

        $html .= '
        <div class="footer">
            <p><strong>KitiSmart</strong> - Gestion de Budget Personnel</p>
            <p>Ce rapport a été généré automatiquement le ' . date('d/m/Y à H:i:s') . '</p>
        </div>

        <button onclick="window.print()" class="no-print print-btn">🖨️ Imprimer ce rapport</button>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * API pour comparer les budgets (AJAX)
     */
    public function compare()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non autorisé'], 401);
            }

            $userId = (int)$_SESSION['user_id'];

            // Récupérer les IDs des budgets depuis POST ou GET
            $budgetIds = [];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $budgetIds = $data['budget_ids'] ?? [];
            } else {
                $budgetIds = isset($_GET['budgets']) ? (array)$_GET['budgets'] : [];
            }

            if (count($budgetIds) < 2) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Veuillez sélectionner au moins 2 budgets à comparer'
                ]);
            }

            if (count($budgetIds) > 4) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Vous pouvez comparer maximum 4 budgets'
                ]);
            }

            $comparison = Budget::compareBudgets($budgetIds, $userId);

            return $this->jsonResponse([
                'success' => true,
                'data' => $comparison
            ]);

        } catch (\Exception $e) {
            error_log("Erreur API comparaison: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la comparaison'
            ], 500);
        }
    }
}
