<?php

namespace App\Models;

use RedBeanPHP\R;

/**
 * Modèle pour gérer l'onboarding des utilisateurs
 */
class UserOnboarding
{
    // Étapes de l'onboarding
    const STEP_WELCOME = 'welcome';
    const STEP_BUDGET_CREATION = 'budget_creation';
    const STEP_BUDGET_SWITCH = 'budget_switch';
    const STEP_DASHBOARD_TOUR = 'dashboard_tour';
    const STEP_EXPENSE_CREATION = 'expense_creation';
    const STEP_CATEGORIES = 'categories';
    const STEP_ADVANCED_FEATURES = 'advanced_features';

    /**
     * Vérifie si une étape est complétée
     */
    public static function isCompleted(int $userId, string $stepName): bool
    {
        $record = R::findOne('useronboarding',
            'user_id = ? AND step_name = ? AND completed_at IS NOT NULL',
            [$userId, $stepName]
        );
        return $record !== null;
    }

    /**
     * Vérifie si une étape a été ignorée
     */
    public static function isSkipped(int $userId, string $stepName): bool
    {
        $record = R::findOne('useronboarding',
            'user_id = ? AND step_name = ? AND skipped = 1',
            [$userId, $stepName]
        );
        return $record !== null;
    }

    /**
     * Vérifie si on doit afficher une étape
     */
    public static function shouldShowStep(int $userId, string $stepName): bool
    {
        return !self::isCompleted($userId, $stepName) && !self::isSkipped($userId, $stepName);
    }

    /**
     * Marque une étape comme complétée
     */
    public static function markCompleted(int $userId, string $stepName): void
    {
        $record = R::findOne('useronboarding',
            'user_id = ? AND step_name = ?',
            [$userId, $stepName]
        );

        if (!$record) {
            $record = R::dispense('useronboarding');
            $record->user_id = $userId;
            $record->step_name = $stepName;
        }

        $record->completed_at = date('Y-m-d H:i:s');
        $record->skipped = 0;
        R::store($record);
    }

    /**
     * Marque une étape comme ignorée
     */
    public static function markSkipped(int $userId, string $stepName): void
    {
        $record = R::findOne('useronboarding',
            'user_id = ? AND step_name = ?',
            [$userId, $stepName]
        );

        if (!$record) {
            $record = R::dispense('useronboarding');
            $record->user_id = $userId;
            $record->step_name = $stepName;
        }

        $record->skipped = 1;
        $record->skipped_at = date('Y-m-d H:i:s');
        R::store($record);
    }

    /**
     * Récupère le statut complet de l'onboarding pour un utilisateur
     */
    public static function getOnboardingStatus(int $userId): array
    {
        $steps = [
            self::STEP_WELCOME,
            self::STEP_BUDGET_CREATION,
            self::STEP_BUDGET_SWITCH,
            self::STEP_DASHBOARD_TOUR,
            self::STEP_EXPENSE_CREATION,
            self::STEP_CATEGORIES,
            self::STEP_ADVANCED_FEATURES
        ];

        $status = [];
        foreach ($steps as $step) {
            $status[$step] = [
                'show' => self::shouldShowStep($userId, $step),
                'completed' => self::isCompleted($userId, $step),
                'skipped' => self::isSkipped($userId, $step)
            ];
        }

        return $status;
    }

    /**
     * Récupère les étapes à afficher pour la page courante
     */
    public static function getStepsForPage(int $userId, string $page): array
    {
        $pageSteps = [
            'dashboard' => [self::STEP_WELCOME, self::STEP_BUDGET_SWITCH, self::STEP_DASHBOARD_TOUR],
            'budget_create' => [self::STEP_BUDGET_CREATION],
            'expense_create' => [self::STEP_EXPENSE_CREATION],
            'categories' => [self::STEP_CATEGORIES],
            'settings' => [self::STEP_ADVANCED_FEATURES]
        ];

        $stepsToShow = [];
        if (isset($pageSteps[$page])) {
            foreach ($pageSteps[$page] as $step) {
                if (self::shouldShowStep($userId, $step)) {
                    $stepsToShow[] = $step;
                }
            }
        }

        return $stepsToShow;
    }

    /**
     * Calcule le pourcentage de complétion de l'onboarding
     */
    public static function getCompletionPercentage(int $userId): int
    {
        $totalSteps = 7;
        $completedCount = R::count('useronboarding',
            'user_id = ? AND (completed_at IS NOT NULL OR skipped = 1)',
            [$userId]
        );

        return (int)round(($completedCount / $totalSteps) * 100);
    }

    /**
     * Réinitialise l'onboarding pour un utilisateur
     */
    public static function reset(int $userId): void
    {
        R::exec('DELETE FROM useronboarding WHERE user_id = ?', [$userId]);
    }
}
