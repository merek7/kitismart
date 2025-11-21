#!/usr/bin/env php
<?php
/**
 * CRON: Exécuter les dépenses récurrentes
 *
 * Ce script doit être exécuté quotidiennement (cron)
 * Pour l'ajouter au cron:
 * crontab -e
 * 0 6 * * * cd /path/to/kitismart && php scripts/execute_recurrences.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\ExpenseRecurrence;

// Initialiser la base de données
Database::init();

echo "═══════════════════════════════════════════════\n";
echo "  KITISMART - Exécution des Récurrences\n";
echo "  Date: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════\n\n";

try {
    // Récupérer toutes les récurrences dues
    $dueRecurrences = ExpenseRecurrence::getDueRecurrences();

    if (empty($dueRecurrences)) {
        echo "✅ Aucune récurrence à exécuter aujourd'hui.\n";
        exit(0);
    }

    echo "📋 " . count($dueRecurrences) . " récurrence(s) à exécuter...\n\n";

    $successCount = 0;
    $errorCount = 0;

    foreach ($dueRecurrences as $recurrence) {
        echo "─────────────────────────────────────────────\n";
        echo "  ID: {$recurrence->id}\n";
        echo "  Description: {$recurrence->description}\n";
        echo "  Montant: " . number_format($recurrence->amount, 0, ',', ' ') . " FCFA\n";
        echo "  Fréquence: " . ExpenseRecurrence::getFrequencyLabel($recurrence->frequency) . "\n";
        echo "  Date prévue: {$recurrence->next_execution_date}\n";

        // Exécuter la récurrence
        $expense = ExpenseRecurrence::execute($recurrence->id);

        if ($expense) {
            echo "  ✅ Dépense créée avec succès (ID: {$expense->id})\n";
            $successCount++;

            // Récupérer la récurrence mise à jour pour voir la prochaine date
            $updatedRecurrence = ExpenseRecurrence::findById($recurrence->id);
            echo "  🔄 Prochaine exécution: {$updatedRecurrence->next_execution_date}\n";
        } else {
            echo "  ❌ Erreur lors de la création de la dépense\n";
            $errorCount++;
        }

        echo "\n";
    }

    echo "═══════════════════════════════════════════════\n";
    echo "  RÉSUMÉ:\n";
    echo "  ✅ Succès: {$successCount}\n";
    echo "  ❌ Erreurs: {$errorCount}\n";
    echo "  📊 Total: " . count($dueRecurrences) . "\n";
    echo "═══════════════════════════════════════════════\n";

    // Code de sortie
    exit($errorCount > 0 ? 1 : 0);

} catch (\Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
