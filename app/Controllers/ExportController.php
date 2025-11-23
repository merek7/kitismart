<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Expense;
use App\Models\Budget;
use RedBeanPHP\R;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function exportCsv()
    {
        try {
            $userId = $_SESSION['user_id'];

            // Récupérer le budget actif
            $activeBudget = Budget::getCurrentBudget($userId);

            if (!$activeBudget) {
                $_SESSION['error'] = "Aucun budget actif trouvé";
                return $this->redirect('/dashboard');
            }

            // Récupérer toutes les dépenses du budget actif
            $expenses = R::find('expense', 'budget_id = ? ORDER BY created_at DESC', [$activeBudget->id]);

            // Préparer les headers pour le téléchargement CSV
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="depenses_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Créer le fichier CSV en mémoire
            $output = fopen('php://output', 'w');

            // Ajouter le BOM UTF-8 pour Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes du CSV
            fputcsv($output, [
                'ID',
                'Date de paiement',
                'Description',
                'Montant (€)',
                'Catégorie',
                'Type',
                'Statut',
                'Charge fixe',
                'Date de création'
            ], ';');

            // Données
            foreach ($expenses as $expense) {
                $categorie = R::load('categorie', $expense->categorie_id);

                fputcsv($output, [
                    $expense->id,
                    $expense->payment_date ? date('d/m/Y', strtotime($expense->payment_date)) : '',
                    $expense->description,
                    number_format((float)$expense->amount, 2, ',', ' '),
                    $categorie->name ?? 'N/A',
                    $this->getCategoryTypeLabel($categorie->type ?? ''),
                    $expense->status === 'paid' ? 'Payé' : 'En attente',
                    $expense->is_fixed ? 'Oui' : 'Non',
                    date('d/m/Y H:i', strtotime($expense->created_at))
                ], ';');
            }

            fclose($output);
            exit;
        } catch (\Exception $e) {
            error_log("Erreur export CSV: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'export";
            return $this->redirect('/expenses/list');
        }
    }

    public function exportPdf()
    {
        try {
            $userId = $_SESSION['user_id'];

            // Récupérer le budget actif
            $activeBudget = Budget::getCurrentBudget($userId);

            if (!$activeBudget) {
                $_SESSION['error'] = "Aucun budget actif trouvé";
                return $this->redirect('/dashboard');
            }

            // Récupérer le résumé du budget
            $summary = Budget::getBudgetSummary($activeBudget->id);

            // Récupérer les dépenses
            $expenses = R::find('expense', 'budget_id = ? ORDER BY payment_date DESC', [$activeBudget->id]);

            // Créer le contenu HTML du rapport
            $html = $this->generatePdfHtml($activeBudget, $summary, $expenses);

            // Headers pour le PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="rapport_budget_' . date('Y-m-d') . '.pdf"');

            // Pour une vraie génération PDF, vous auriez besoin d'une bibliothèque comme TCPDF ou DomPDF
            // Pour l'instant, on génère un HTML print-friendly
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        } catch (\Exception $e) {
            error_log("Erreur export PDF: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de l'export PDF";
            return $this->redirect('/expenses/list');
        }
    }

    private function generatePdfHtml($budget, $summary, $expenses): string
    {
        $user = R::load('users', $_SESSION['user_id']);

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Budget - KitiSmart</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #007bff;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .summary-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        .summary-box p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.paid {
            background-color: #28a745;
            color: white;
        }
        .status.pending {
            background-color: #ffc107;
            color: #333;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .category-summary {
            margin-bottom: 30px;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8f9fa;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        @media print {
            .no-print { display: none; }
            .summary-box { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Rapport de Budget</h1>
        <p><strong>' . htmlspecialchars($user->nom) . '</strong></p>
        <p>Période: ' . date('d/m/Y', strtotime($budget->start_date)) . ' - ' .
            ($budget->end_date ? date('d/m/Y', strtotime($budget->end_date)) : 'En cours') . '</p>
        <p>Généré le ' . date('d/m/Y à H:i') . '</p>
    </div>

    <div class="summary">
        <div class="summary-box">
            <h3>💰 Budget Initial</h3>
            <p>' . number_format((float)$budget->initial_amount, 2, ',', ' ') . ' €</p>
        </div>
        <div class="summary-box">
            <h3>💸 Total Dépensé</h3>
            <p>' . number_format((float)$summary['total_spent'], 2, ',', ' ') . ' €</p>
        </div>
        <div class="summary-box">
            <h3>💵 Restant</h3>
            <p>' . number_format((float)$budget->remaining_amount, 2, ',', ' ') . ' €</p>
        </div>
    </div>

    <div class="category-summary">
        <h2>Répartition par catégorie</h2>';

        foreach ($summary['categories'] as $cat) {
            $html .= '<div class="category-item">
                <span><strong>' . htmlspecialchars($cat['name']) . '</strong> (' . $cat['type_label'] . ')</span>
                <span><strong>' . number_format((float)$cat['total'], 2, ',', ' ') . ' €</strong> (' . $cat['count'] . ' dépense(s))</span>
            </div>';
        }

        $html .= '</div>

    <h2>Détail des dépenses</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Catégorie</th>
                <th>Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($expenses as $expense) {
            $categorie = R::load('categorie', $expense->categorie_id);
            $statusClass = $expense->status === 'paid' ? 'paid' : 'pending';
            $statusLabel = $expense->status === 'paid' ? 'Payé' : 'En attente';

            $html .= '<tr>
                <td>' . date('d/m/Y', strtotime($expense->payment_date)) . '</td>
                <td>' . htmlspecialchars($expense->description) . '</td>
                <td>' . htmlspecialchars($categorie->name ?? 'N/A') . '</td>
                <td><strong>' . number_format((float)$expense->amount, 2, ',', ' ') . ' €</strong></td>
                <td><span class="status ' . $statusClass . '">' . $statusLabel . '</span></td>
            </tr>';
        }

        $html .= '</tbody>
    </table>

    <div class="footer">
        <p><strong>KitiSmart</strong> - Gestion de Budget Personnel</p>
        <p>Ce rapport a été généré automatiquement le ' . date('d/m/Y à H:i:s') . '</p>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            🖨️ Imprimer ce rapport
        </button>
    </div>
</body>
</html>';

        return $html;
    }

    private function getCategoryTypeLabel(string $type): string
    {
        $labels = [
            'fixe' => 'Charges fixes',
            'diver' => 'Divers',
            'epargne' => 'Épargne'
        ];

        return $labels[$type] ?? 'Inconnu';
    }
}
