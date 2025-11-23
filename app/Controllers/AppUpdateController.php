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
            $hasSeen = AppUpdate::hasSeenUpdate($userId);

            if ($hasSeen) {
                return $this->jsonResponse(['show' => false]);
            }

            $updateInfo = AppUpdate::getCurrentUpdateInfo();
            if (!$updateInfo) {
                return $this->jsonResponse(['show' => false]);
            }

            return $this->jsonResponse([
                'show' => true,
                'version' => AppUpdate::getCurrentVersion(),
                'update' => $updateInfo
            ]);

        } catch (\Exception $e) {
            error_log("Erreur check update: " . $e->getMessage());
            return $this->jsonResponse(['show' => false]);
        }
    }

    /**
     * Marque la mise à jour comme vue
     */
    public function markSeen()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            AppUpdate::markAsSeen($userId);

            return $this->jsonResponse(['success' => true]);

        } catch (\Exception $e) {
            error_log("Erreur mark seen: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
