<?php

namespace App\Models;

use RedBeanPHP\R;

/**
 * ExpenseRecurrence - Gestion des dépenses récurrentes
 *
 * Permet de créer automatiquement des dépenses à intervalle régulier
 */
class ExpenseRecurrence
{
    /**
     * Fréquences disponibles
     */
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BIMONTHLY = 'bimonthly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_YEARLY = 'yearly';

    /**
     * Créer une nouvelle récurrence
     *
     * @param array $data Données de la récurrence
     * @return object Bean RedBean
     */
    public static function create(array $data): object
    {
        // Valider les données
        self::validate($data);

        $recurrence = R::dispense('expenserecurrence');

        $recurrence->budget_id = $data['budget_id'];
        $recurrence->description = $data['description'];
        $recurrence->amount = $data['amount'];
        $recurrence->categorie_id = $data['categorie_id'];
        $recurrence->frequency = $data['frequency'];
        $recurrence->next_execution_date = $data['start_date'] ?? date('Y-m-d');
        $recurrence->last_execution_date = null;
        $recurrence->is_active = 1;
        $recurrence->created_at = date('Y-m-d H:i:s');
        $recurrence->updated_at = date('Y-m-d H:i:s');

        $id = R::store($recurrence);

        error_log("✅ Récurrence créée (ID: {$id}) - {$data['description']} ({$data['frequency']})");

        return $recurrence;
    }

    /**
     * Récupérer toutes les récurrences actives d'un budget
     *
     * @param int $budgetId ID du budget
     * @return array Récurrences actives
     */
    public static function getActiveByBudget(int $budgetId): array
    {
        return R::find('expenserecurrence', 'budget_id = ? AND is_active = 1 ORDER BY created_at DESC', [$budgetId]);
    }

    /**
     * Récupérer toutes les récurrences (actives et inactives) d'un budget
     *
     * @param int $budgetId ID du budget
     * @return array Toutes les récurrences
     */
    public static function getAllByBudget(int $budgetId): array
    {
        return R::find('expenserecurrence', 'budget_id = ? ORDER BY is_active DESC, created_at DESC', [$budgetId]);
    }

    /**
     * Récupérer une récurrence par ID
     *
     * @param int $id ID de la récurrence
     * @return object|null Bean RedBean ou null
     */
    public static function findById(int $id): ?object
    {
        return R::load('expenserecurrence', $id);
    }

    /**
     * Récupérer les récurrences à exécuter aujourd'hui
     *
     * @return array Récurrences à exécuter
     */
    public static function getDueRecurrences(): array
    {
        $today = date('Y-m-d');

        return R::find('expenserecurrence',
            'is_active = 1 AND next_execution_date <= ? ORDER BY next_execution_date ASC',
            [$today]
        );
    }

    /**
     * Mettre à jour une récurrence
     *
     * @param int $id ID de la récurrence
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public static function update(int $id, array $data): bool
    {
        $recurrence = R::load('expenserecurrence', $id);

        if (!$recurrence->id) {
            return false;
        }

        // Mettre à jour les champs autorisés
        if (isset($data['description'])) {
            $recurrence->description = $data['description'];
        }

        if (isset($data['amount'])) {
            $recurrence->amount = $data['amount'];
        }

        if (isset($data['categorie_id'])) {
            $recurrence->categorie_id = $data['categorie_id'];
        }

        if (isset($data['frequency'])) {
            $recurrence->frequency = $data['frequency'];
        }

        $recurrence->updated_at = date('Y-m-d H:i:s');

        R::store($recurrence);

        error_log("✅ Récurrence mise à jour (ID: {$id})");

        return true;
    }

    /**
     * Activer/Désactiver une récurrence
     *
     * @param int $id ID de la récurrence
     * @param bool $active Activer (true) ou Désactiver (false)
     * @return bool Succès ou échec
     */
    public static function setActive(int $id, bool $active): bool
    {
        $recurrence = R::load('expenserecurrence', $id);

        if (!$recurrence->id) {
            return false;
        }

        $recurrence->is_active = $active ? 1 : 0;
        $recurrence->updated_at = date('Y-m-d H:i:s');

        R::store($recurrence);

        $status = $active ? 'activée' : 'désactivée';
        error_log("✅ Récurrence {$status} (ID: {$id})");

        return true;
    }

    /**
     * Supprimer une récurrence
     *
     * @param int $id ID de la récurrence
     * @return bool Succès ou échec
     */
    public static function delete(int $id): bool
    {
        $recurrence = R::load('expenserecurrence', $id);

        if (!$recurrence->id) {
            return false;
        }

        R::trash($recurrence);

        error_log("✅ Récurrence supprimée (ID: {$id})");

        return true;
    }

    /**
     * Exécuter une récurrence (créer la dépense et calculer prochaine date)
     *
     * @param int $id ID de la récurrence
     * @return object|null Dépense créée ou null si échec
     */
    public static function execute(int $id): ?object
    {
        $recurrence = R::load('expenserecurrence', $id);

        if (!$recurrence->id || !$recurrence->is_active) {
            return null;
        }

        try {
            // Créer la dépense
            $expense = Expense::create([
                'budget_id' => $recurrence->budget_id,
                'description' => $recurrence->description,
                'amount' => $recurrence->amount,
                'category_type' => self::getCategoryType($recurrence->categorie_id),
                'payment_date' => date('Y-m-d'),
                'is_fixed' => 1, // Les récurrences sont considérées comme fixes
                'status' => 'pending'
            ]);

            // Mettre à jour la récurrence
            $recurrence->last_execution_date = date('Y-m-d');
            $recurrence->next_execution_date = self::calculateNextDate($recurrence->frequency);
            $recurrence->updated_at = date('Y-m-d H:i:s');

            R::store($recurrence);

            error_log("✅ Récurrence exécutée (ID: {$id}) → Dépense créée (ID: {$expense->id})");

            return $expense;

        } catch (\Exception $e) {
            error_log("❌ Erreur exécution récurrence (ID: {$id}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculer la prochaine date d'exécution
     *
     * @param string $frequency Fréquence
     * @param string $currentDate Date de référence (par défaut: aujourd'hui)
     * @return string Prochaine date (Y-m-d)
     */
    public static function calculateNextDate(string $frequency, string $currentDate = null): string
    {
        $date = $currentDate ? new \DateTime($currentDate) : new \DateTime();

        switch ($frequency) {
            case self::FREQUENCY_DAILY:
                $date->modify('+1 day');
                break;

            case self::FREQUENCY_WEEKLY:
                $date->modify('+1 week');
                break;

            case self::FREQUENCY_BIMONTHLY:
                $date->modify('+15 days');
                break;

            case self::FREQUENCY_MONTHLY:
                $date->modify('+1 month');
                break;

            case self::FREQUENCY_YEARLY:
                $date->modify('+1 year');
                break;

            default:
                $date->modify('+1 month'); // Par défaut: mensuel
        }

        return $date->format('Y-m-d');
    }

    /**
     * Obtenir le type de catégorie depuis l'ID
     *
     * @param int $categorieId ID de la catégorie
     * @return string Type de catégorie
     */
    private static function getCategoryType(int $categorieId): string
    {
        $categorie = Categorie::findById($categorieId);
        return $categorie ? $categorie->type : 'diver';
    }

    /**
     * Valider les données de récurrence
     *
     * @param array $data Données à valider
     * @throws \Exception Si validation échoue
     */
    private static function validate(array $data): void
    {
        $required = ['budget_id', 'description', 'amount', 'categorie_id', 'frequency'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Le champ {$field} est requis");
            }
        }

        // Valider la fréquence
        $validFrequencies = [
            self::FREQUENCY_DAILY,
            self::FREQUENCY_WEEKLY,
            self::FREQUENCY_BIMONTHLY,
            self::FREQUENCY_MONTHLY,
            self::FREQUENCY_YEARLY
        ];

        if (!in_array($data['frequency'], $validFrequencies)) {
            throw new \Exception("Fréquence invalide: {$data['frequency']}");
        }

        // Valider le montant
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception("Le montant doit être un nombre positif");
        }
    }

    /**
     * Obtenir les fréquences disponibles avec labels
     *
     * @return array [frequency => label]
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Quotidien',
            self::FREQUENCY_WEEKLY => 'Hebdomadaire',
            self::FREQUENCY_BIMONTHLY => 'Bimensuel (tous les 15 jours)',
            self::FREQUENCY_MONTHLY => 'Mensuel',
            self::FREQUENCY_YEARLY => 'Annuel'
        ];
    }

    /**
     * Obtenir le label d'une fréquence
     *
     * @param string $frequency Fréquence
     * @return string Label
     */
    public static function getFrequencyLabel(string $frequency): string
    {
        $frequencies = self::getFrequencies();
        return $frequencies[$frequency] ?? 'Inconnu';
    }
}
