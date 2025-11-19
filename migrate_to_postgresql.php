<?php
/**
 * Script de migration des données de MySQL vers PostgreSQL
 *
 * Ce script transfère toutes les données de votre base MySQL vers PostgreSQL
 * tout en préservant l'intégrité référentielle.
 *
 * IMPORTANT: Exécutez ce script une seule fois après avoir:
 * 1. Créé la base de données PostgreSQL
 * 2. Configuré vos credentials dans ce fichier
 */

declare(strict_types=1);

// Configuration de la base MySQL source
$mysqlConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'kiti',
    'user' => 'admin',
    'pass' => ''
];

// Configuration de la base PostgreSQL destination
$postgresConfig = [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'kiti',
    'user' => 'postgres',
    'pass' => ''  // Remplacez par votre mot de passe PostgreSQL
];

// Liste des tables à migrer dans l'ordre (respect des dépendances)
$tables = [
    'users',
    'useraudit',
    'budget',
    'categorie',
    'expense',
    'expense_audit'
];

echo "=================================================\n";
echo "  MIGRATION MYSQL vers POSTGRESQL - KitiSmart\n";
echo "=================================================\n\n";

try {
    // Connexion à MySQL
    echo "📡 Connexion à MySQL...\n";
    $mysqlDsn = "mysql:host={$mysqlConfig['host']};port={$mysqlConfig['port']};dbname={$mysqlConfig['dbname']};charset=utf8mb4";
    $mysqlPdo = new PDO($mysqlDsn, $mysqlConfig['user'], $mysqlConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Connecté à MySQL\n\n";

    // Connexion à PostgreSQL
    echo "📡 Connexion à PostgreSQL...\n";
    $postgresDsn = "pgsql:host={$postgresConfig['host']};port={$postgresConfig['port']};dbname={$postgresConfig['dbname']}";
    $postgresPdo = new PDO($postgresDsn, $postgresConfig['user'], $postgresConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $postgresPdo->exec("SET NAMES 'UTF8'");
    echo "✅ Connecté à PostgreSQL\n\n";

    // Vérifier si des tables existent déjà dans PostgreSQL
    echo "🔍 Vérification des tables existantes...\n";
    $existingTables = $postgresPdo->query(
        "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($existingTables)) {
        echo "⚠️  ATTENTION: Des tables existent déjà dans PostgreSQL:\n";
        foreach ($existingTables as $table) {
            echo "   - $table\n";
        }
        echo "\nVoulez-vous continuer et écraser ces tables? (yes/no): ";
        $confirm = trim(fgets(STDIN));
        if (strtolower($confirm) !== 'yes') {
            echo "❌ Migration annulée.\n";
            exit(0);
        }
    }
    echo "\n";

    // Commencer la migration
    echo "🚀 Début de la migration des données...\n\n";

    foreach ($tables as $table) {
        echo "📋 Migration de la table '$table'...\n";

        // Vérifier si la table existe dans MySQL
        $stmt = $mysqlPdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "   ⚠️  Table '$table' non trouvée dans MySQL, passage à la suivante.\n\n";
            continue;
        }

        // Compter les lignes dans MySQL
        $count = $mysqlPdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "   📊 Nombre de lignes à migrer: $count\n";

        if ($count == 0) {
            echo "   ℹ️  Table vide, rien à migrer.\n\n";
            continue;
        }

        // Récupérer toutes les données
        $data = $mysqlPdo->query("SELECT * FROM $table")->fetchAll();

        if (empty($data)) {
            echo "   ℹ️  Aucune donnée à migrer.\n\n";
            continue;
        }

        // Récupérer les colonnes
        $columns = array_keys($data[0]);
        $columnsStr = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        // Préparer la requête d'insertion pour PostgreSQL
        $insertQuery = "INSERT INTO $table ($columnsStr) VALUES ($placeholders)";
        $stmt = $postgresPdo->prepare($insertQuery);

        // Désactiver temporairement les contraintes
        $postgresPdo->exec("ALTER TABLE $table DISABLE TRIGGER ALL");

        // Insérer les données ligne par ligne
        $postgresPdo->beginTransaction();
        $inserted = 0;

        foreach ($data as $row) {
            try {
                $values = array_values($row);
                $stmt->execute($values);
                $inserted++;
            } catch (PDOException $e) {
                echo "   ⚠️  Erreur lors de l'insertion d'une ligne: " . $e->getMessage() . "\n";
                // Continuer avec les autres lignes
            }
        }

        $postgresPdo->commit();

        // Réactiver les contraintes
        $postgresPdo->exec("ALTER TABLE $table ENABLE TRIGGER ALL");

        // Mettre à jour la séquence pour la colonne id (si elle existe)
        if (in_array('id', $columns)) {
            try {
                $maxId = $postgresPdo->query("SELECT MAX(id) FROM $table")->fetchColumn();
                if ($maxId) {
                    $postgresPdo->exec("SELECT setval('{$table}_id_seq', $maxId)");
                    echo "   🔢 Séquence mise à jour: {$table}_id_seq = $maxId\n";
                }
            } catch (PDOException $e) {
                echo "   ⚠️  Impossible de mettre à jour la séquence: " . $e->getMessage() . "\n";
            }
        }

        echo "   ✅ $inserted lignes migrées avec succès\n\n";
    }

    echo "=================================================\n";
    echo "  ✅ MIGRATION TERMINÉE AVEC SUCCÈS!\n";
    echo "=================================================\n\n";

    // Statistiques finales
    echo "📊 Statistiques de la migration:\n\n";
    foreach ($tables as $table) {
        try {
            $count = $postgresPdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo sprintf("   %-20s : %d lignes\n", $table, $count);
        } catch (PDOException $e) {
            echo sprintf("   %-20s : Table non trouvée\n", $table);
        }
    }

    echo "\n✅ Vous pouvez maintenant mettre à jour votre .env avec DB_DRIVER=pgsql\n";

} catch (PDOException $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📝 Détails: " . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
