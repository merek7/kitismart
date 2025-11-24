<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AppUpdate;

class AppUpdateController extends Controller
{
    /**
     * Vérifie si une mise à jour doit être affichée
     */
    public function check()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['show' => false]);
            }

            $userId = (int)$_SESSION['user_id'];

            // Récupérer toutes les versions non vues
            $unseenUpdates = AppUpdate::getUnseenUpdates($userId);

            if (empty($unseenUpdates)) {
                return $this->jsonResponse(['show' => false]);
            }

            return $this->jsonResponse([
                'show' => true,
                'version' => AppUpdate::getCurrentVersion(),
                'updates' => $unseenUpdates
            ]);

        } catch (\Exception $e) {
            error_log("Erreur check update: " . $e->getMessage());
            return $this->jsonResponse(['show' => false]);
        }
    }

    /**
     * Marque toutes les mises à jour comme vues
     */
    public function markSeen()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false], 401);
            }

            $userId = (int)$_SESSION['user_id'];

            // Marquer toutes les versions comme vues
            AppUpdate::markAllAsSeen($userId);

            return $this->jsonResponse(['success' => true]);

        } catch (\Exception $e) {
            error_log("Erreur mark seen: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
