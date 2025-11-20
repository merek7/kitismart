#!/usr/bin/env php
<?php
/**
 * Script Cron - Traitement des récurrences
 *
 * Ce script doit être exécuté quotidiennement pour créer automatiquement
 * les dépenses récurrentes dont la date d'exécution est arrivée.
 *
 * Installation crontab:
 * 0 2 * * * /usr/bin/php /path/to/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
 *
 * (Exécution tous les jours à 2h du matin)
 */

// Charger l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialiser RedBeanPHP
use RedBeanPHP\R;
use App\Core\Database;
use App\Models\ExpenseRecurrence;

// Connexion à la base de données
try {
    Database::setup();
    echo "[" . date('Y-m-d H:i:s') . "] ✅ Connexion BD réussie\n";
} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ Erreur connexion BD: " . $e->getMessage() . "\n";
    exit(1);
}

// ===================================
// TRAITEMENT DES RÉCURRENCES
// ===================================

echo "[" . date('Y-m-d H:i:s') . "] 🔄 Début du traitement des récurrences...\n";

try {
    // Récupérer toutes les récurrences à exécuter aujourd'hui
    $dueRecurrences = ExpenseRecurrence::getDueRecurrences();

    echo "[" . date('Y-m-d H:i:s') . "] 📋 " . count($dueRecurrences) . " récurrence(s) à traiter\n";

    if (empty($dueRecurrences)) {
        echo "[" . date('Y-m-d H:i:s') . "] ℹ️  Aucune récurrence à exécuter aujourd'hui\n";
        exit(0);
    }

    $successCount = 0;
    $errorCount = 0;

    foreach ($dueRecurrences as $recurrence) {
        echo "\n[" . date('Y-m-d H:i:s') . "] 🔄 Traitement récurrence #{$recurrence->id}: {$recurrence->description}\n";
        echo "   - Montant: " . number_format($recurrence->amount, 2) . " FCFA\n";
        echo "   - Fréquence: {$recurrence->frequency}\n";
        echo "   - Date prévue: {$recurrence->next_execution_date}\n";

        // Exécuter la récurrence
        $expense = ExpenseRecurrence::execute($recurrence->id);

        if ($expense) {
            $successCount++;
            echo "   ✅ Dépense créée avec succès (ID: {$expense->id})\n";

            // Récupérer la récurrence mise à jour
            $updatedRecurrence = ExpenseRecurrence::findById($recurrence->id);
            echo "   📅 Prochaine exécution: {$updatedRecurrence->next_execution_date}\n";
        } else {
            $errorCount++;
            echo "   ❌ Échec de création de la dépense\n";
        }
    }

    // Résumé
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] 📊 RÉSUMÉ DU TRAITEMENT\n";
    echo "   ✅ Succès: {$successCount}\n";
    echo "   ❌ Erreurs: {$errorCount}\n";
    echo "   📝 Total traité: " . count($dueRecurrences) . "\n";
    echo str_repeat("=", 60) . "\n";

    if ($errorCount > 0) {
        exit(1); // Code d'erreur pour monitoring cron
    }

    exit(0); // Succès

} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
