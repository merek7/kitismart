<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserOnboarding;

/**
 * Contrôleur pour gérer l'onboarding des utilisateurs
 */
class OnboardingController extends Controller
{
    /**
     * Marque une étape comme complétée
     */
    public function completeStep($step)
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $validSteps = [
            UserOnboarding::STEP_WELCOME,
            UserOnboarding::STEP_BUDGET_CREATION,
            UserOnboarding::STEP_BUDGET_SWITCH,
            UserOnboarding::STEP_DASHBOARD_TOUR,
            UserOnboarding::STEP_EXPENSE_CREATION,
            UserOnboarding::STEP_CATEGORIES,
            UserOnboarding::STEP_ADVANCED_FEATURES
        ];

        if (!in_array($step, $validSteps)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Étape invalide'], 400);
        }

        try {
            UserOnboarding::markCompleted((int)$_SESSION['user_id'], $step);

            $percentage = UserOnboarding::getCompletionPercentage((int)$_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Étape complétée',
                'step' => $step,
                'completionPercentage' => $percentage
            ]);
        } catch (\Exception $e) {
            error_log("Erreur onboarding completeStep: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Ignore une étape
     */
    public function skipStep($step)
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $validSteps = [
            UserOnboarding::STEP_WELCOME,
            UserOnboarding::STEP_BUDGET_CREATION,
            UserOnboarding::STEP_BUDGET_SWITCH,
            UserOnboarding::STEP_DASHBOARD_TOUR,
            UserOnboarding::STEP_EXPENSE_CREATION,
            UserOnboarding::STEP_CATEGORIES,
            UserOnboarding::STEP_ADVANCED_FEATURES
        ];

        if (!in_array($step, $validSteps)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Étape invalide'], 400);
        }

        try {
            UserOnboarding::markSkipped((int)$_SESSION['user_id'], $step);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Étape ignorée',
                'step' => $step
            ]);
        } catch (\Exception $e) {
            error_log("Erreur onboarding skipStep: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Récupère le statut de l'onboarding
     */
    public function getStatus()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        try {
            $status = UserOnboarding::getOnboardingStatus((int)$_SESSION['user_id']);
            $percentage = UserOnboarding::getCompletionPercentage((int)$_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'status' => $status,
                'completionPercentage' => $percentage
            ]);
        } catch (\Exception $e) {
            error_log("Erreur onboarding getStatus: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Réinitialise l'onboarding (pour les tests ou à la demande de l'utilisateur)
     */
    public function reset()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        try {
            UserOnboarding::reset((int)$_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Onboarding réinitialisé'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur onboarding reset: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }
}
