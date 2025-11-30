<?php

namespace App\Models;

use RedBeanPHP\R as R;

class AppUpdate
{
    // Version actuelle de l'application - à incrémenter à chaque mise à jour importante
    public const CURRENT_VERSION = '1.5.0';

    // Changelog des versions
    public const CHANGELOG = [
        '1.5.0' => [
            'title' => 'Pièces jointes sur les dépenses',
            'date' => '2025-01-30',
            'features' => [
                [
                    'icon' => 'fa-paperclip',
                    'title' => 'Pièces jointes',
                    'description' => 'Ajoutez des photos, factures ou reçus à vos dépenses pour garder une trace de vos achats.'
                ],
                [
                    'icon' => 'fa-camera',
                    'title' => 'Capture photo mobile',
                    'description' => 'Prenez directement une photo depuis votre téléphone pour l\'ajouter à une dépense.'
                ],
                [
                    'icon' => 'fa-users',
                    'title' => 'Pièces jointes partagées',
                    'description' => 'Les invités d\'un budget partagé peuvent aussi ajouter des pièces jointes à leurs dépenses.'
                ],
                [
                    'icon' => 'fa-eye',
                    'title' => 'Visualisation intégrée',
                    'description' => 'Consultez vos images et PDF directement dans l\'application sans téléchargement.'
                ]
            ]
        ],
        '1.4.0' => [
            'title' => 'Objectifs d\'épargne & Budgets flexibles',
            'date' => '2025-01-27',
            'features' => [
                [
                    'icon' => 'fa-bullseye',
                    'title' => 'Objectifs d\'épargne',
                    'description' => 'Définissez vos objectifs financiers (voyage, voiture, maison...) et suivez votre progression.'
                ],
                [
                    'icon' => 'fa-piggy-bank',
                    'title' => 'Suivi des contributions',
                    'description' => 'Ajoutez facilement de l\'épargne à vos objectifs et visualisez l\'historique de vos contributions.'
                ],
                [
                    'icon' => 'fa-calculator',
                    'title' => 'Suggestions mensuelles',
                    'description' => 'L\'application calcule automatiquement combien épargner par mois pour atteindre vos objectifs.'
                ],
                [
                    'icon' => 'fa-infinity',
                    'title' => 'Budgets indéfinis',
                    'description' => 'Créez des budgets sans montant fixe pour vos projets à coût variable (rénovations, projets évolutifs...).'
                ],
                [
                    'icon' => 'fa-mobile-alt',
                    'title' => 'Navigation mobile améliorée',
                    'description' => 'Le menu hamburger mobile est maintenant scrollable pour un accès facile à toutes les fonctionnalités.'
                ],
                [
                    'icon' => 'fa-file-pdf',
                    'title' => 'Export PDF comparaison',
                    'description' => 'Exportez vos rapports de comparaison de budgets en PDF pour vos archives.'
                ]
            ]
        ],
        '1.3.0' => [
            'title' => 'Comparaison de budgets',
            'date' => '2025-11-24',
            'features' => [
                [
                    'icon' => 'fa-balance-scale',
                    'title' => 'Comparez vos budgets',
                    'description' => 'Nouvelle fonctionnalité pour comparer jusqu\'à 4 budgets côte à côte avec graphiques et statistiques.'
                ],
                [
                    'icon' => 'fa-chart-bar',
                    'title' => 'Graphiques comparatifs',
                    'description' => 'Visualisez les différences entre budgets avec des graphiques en barres, par catégorie et radar.'
                ],
                [
                    'icon' => 'fa-exchange-alt',
                    'title' => 'Analyse des évolutions',
                    'description' => 'Voyez l\'évolution de vos dépenses entre deux périodes avec les pourcentages de variation.'
                ],
                [
                    'icon' => 'fa-spider',
                    'title' => 'Analyse radar',
                    'description' => 'Comparez vos habitudes de dépenses sur plusieurs critères avec le graphique radar.'
                ]
            ]
        ],
        '1.2.0' => [
            'title' => 'Partage amélioré & UX mobile',
            'date' => '2025-11-24',
            'features' => [
                [
                    'icon' => 'fa-share-alt',
                    'title' => 'Partage de budget corrigé',
                    'description' => 'Les dépenses du budget partagé sont maintenant correctement affichées pour vos invités.'
                ],
                [
                    'icon' => 'fa-mobile-alt',
                    'title' => 'Interface mobile améliorée',
                    'description' => 'Les filtres et la liste des dépenses sont désormais mieux adaptés aux petits écrans.'
                ],
                [
                    'icon' => 'fa-scroll',
                    'title' => 'Liste des dépenses scrollable',
                    'description' => 'Naviguez facilement dans vos dépenses avec un scroll fluide et un compteur visible.'
                ],
                [
                    'icon' => 'fa-graduation-cap',
                    'title' => 'Onboarding enrichi',
                    'description' => 'Le guide de démarrage explique maintenant le switch de budget et permet de revenir en arrière.'
                ]
            ]
        ],
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
     * Vérifie si l'utilisateur a vu une version spécifique
     */
    public static function hasSeenVersion(int $userId, string $version): bool
    {
        $record = R::findOne('userappupdate', 'user_id = ? AND version = ?', [$userId, $version]);
        return $record !== null;
    }

    /**
     * Récupère toutes les versions non vues par l'utilisateur
     */
    public static function getUnseenUpdates(int $userId): array
    {
        $unseenUpdates = [];

        // Parcourir toutes les versions du changelog
        foreach (self::CHANGELOG as $version => $info) {
            if (!self::hasSeenVersion($userId, $version)) {
                $unseenUpdates[$version] = $info;
            }
        }

        return $unseenUpdates;
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
     * Marque toutes les versions comme vues par l'utilisateur
     */
    public static function markAllAsSeen(int $userId): void
    {
        foreach (array_keys(self::CHANGELOG) as $version) {
            if (!self::hasSeenVersion($userId, $version)) {
                $record = R::dispense('userappupdate');
                $record->user_id = $userId;
                $record->version = $version;
                $record->seen_at = date('Y-m-d H:i:s');
                R::store($record);
            }
        }
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
