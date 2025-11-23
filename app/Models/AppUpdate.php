<?php

namespace App\Models;

use RedBeanPHP\R as R;

class AppUpdate
{
    // Version actuelle de l'application - à incrémenter à chaque mise à jour importante
    public const CURRENT_VERSION = '1.1.0';

    // Changelog des versions
    public const CHANGELOG = [
        '1.1.0' => [
            'title' => 'Multi-budgets & Export PDF',
            'date' => '2025-11-23',
            'features' => [
                [
                    'icon' => 'fa-exchange-alt',
                    'title' => 'Switch de budget',
                    'description' => 'Gérez plusieurs budgets en parallèle ! Créez un budget annexe pour vos projets (travaux, voyage...) et basculez facilement entre eux.'
                ],
                [
                    'icon' => 'fa-file-pdf',
                    'title' => 'Export PDF',
                    'description' => 'Exportez l\'historique de vos budgets et le détail des dépenses en PDF pour vos archives.'
                ],
                [
                    'icon' => 'fa-palette',
                    'title' => 'Couleurs personnalisées',
                    'description' => 'Attribuez une couleur à chaque budget pour les identifier rapidement.'
                ],
                [
                    'icon' => 'fa-mobile-alt',
                    'title' => 'Session persistante mobile',
                    'description' => 'Sur mobile, restez connecté plus longtemps comme une vraie application.'
                ]
            ]
        ]
    ];

    /**
     * Vérifie si l'utilisateur a vu la dernière mise à jour
     */
    public static function hasSeenUpdate(int $userId): bool
    {
        $record = R::findOne('userappupdate', 'user_id = ? AND version = ?', [$userId, self::CURRENT_VERSION]);
        return $record !== null;
    }

    /**
     * Marque la mise à jour comme vue par l'utilisateur
     */
    public static function markAsSeen(int $userId): void
    {
        // Vérifier si déjà marqué
        if (self::hasSeenUpdate($userId)) {
            return;
        }

        $record = R::dispense('userappupdate');
        $record->user_id = $userId;
        $record->version = self::CURRENT_VERSION;
        $record->seen_at = date('Y-m-d H:i:s');
        R::store($record);
    }

    /**
     * Récupère les infos de la version actuelle
     */
    public static function getCurrentUpdateInfo(): ?array
    {
        return self::CHANGELOG[self::CURRENT_VERSION] ?? null;
    }

    /**
     * Récupère la version actuelle
     */
    public static function getCurrentVersion(): string
    {
        return self::CURRENT_VERSION;
    }
}
