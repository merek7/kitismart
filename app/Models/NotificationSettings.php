<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class NotificationSettings {

    /**
     * Créer les paramètres de notification par défaut pour un utilisateur
     */
    public static function createDefault(int $userId) {
        try {
            // Vérifier si les paramètres existent déjà
            $existing = self::findByUser($userId);
            if ($existing) {
                return $existing;
            }

            $settings = R::dispense('notificationsettings');
            $settings->import([
                'user_id' => $userId,
                'budget_alert_80' => 1,  // Activé par défaut
                'budget_alert_100' => 1, // Activé par défaut
                'expense_alert_threshold' => 50000, // 50 000 FCFA
                'expense_alert_enabled' => 1,
                'monthly_summary' => 1, // Activé par défaut
                'summary_day' => 1, // Premier jour du mois
                'email_enabled' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => null
            ]);

            R::store($settings);
            return $settings;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la création des paramètres de notification: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer les paramètres de notification d'un utilisateur
     */
    public static function findByUser(int $userId) {
        return R::findOne('notificationsettings', 'user_id = ?', [$userId]);
    }

    /**
     * Mettre à jour les paramètres
     */
    public static function update(int $userId, array $data) {
        try {
            $settings = self::findByUser($userId);

            if (!$settings) {
                // Créer les paramètres s'ils n'existent pas
                $settings = self::createDefault($userId);
            }

            // Mettre à jour les champs autorisés
            $allowedFields = [
                'budget_alert_80',
                'budget_alert_100',
                'expense_alert_threshold',
                'expense_alert_enabled',
                'monthly_summary',
                'summary_day',
                'email_enabled'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $settings->$field = $data[$field];
                }
            }

            $settings->updated_at = date('Y-m-d H:i:s');
            R::store($settings);

            return $settings;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si l'alerte à 80% est activée
     */
    public static function isAlert80Enabled(int $userId): bool {
        $settings = self::findByUser($userId);
        return $settings && $settings->budget_alert_80 == 1;
    }

    /**
     * Vérifier si l'alerte à 100% est activée
     */
    public static function isAlert100Enabled(int $userId): bool {
        $settings = self::findByUser($userId);
        return $settings && $settings->budget_alert_100 == 1;
    }

    /**
     * Vérifier si les alertes de dépenses sont activées
     */
    public static function isExpenseAlertEnabled(int $userId): bool {
        $settings = self::findByUser($userId);
        return $settings && $settings->expense_alert_enabled == 1;
    }

    /**
     * Récupérer le seuil d'alerte pour les dépenses
     */
    public static function getExpenseThreshold(int $userId): float {
        $settings = self::findByUser($userId);
        return $settings ? (float)$settings->expense_alert_threshold : 50000;
    }

    /**
     * Vérifier si le récapitulatif mensuel est activé
     */
    public static function isMonthlySummaryEnabled(int $userId): bool {
        $settings = self::findByUser($userId);
        return $settings && $settings->monthly_summary == 1;
    }

    /**
     * Vérifier si les emails sont activés globalement
     */
    public static function isEmailEnabled(int $userId): bool {
        $settings = self::findByUser($userId);
        return $settings && $settings->email_enabled == 1;
    }

    /**
     * Récupérer le jour du récapitulatif mensuel
     */
    public static function getSummaryDay(int $userId): int {
        $settings = self::findByUser($userId);
        return $settings ? (int)$settings->summary_day : 1;
    }

    /**
     * Récupérer tous les utilisateurs avec le récapitulatif mensuel activé
     */
    public static function getUsersForMonthlySummary(): array {
        return R::find('notificationsettings', 'monthly_summary = 1 AND email_enabled = 1');
    }
}
