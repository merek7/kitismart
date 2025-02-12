<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class ConfirmationController extends Controller
{
    public function confirm($token)
    {
        try {
            error_log("Début de la confirmation avec token: " . $token);
            
            $user = User::getByConfirmationToken($token);
            error_log("Recherche utilisateur par token: " . ($user ? "trouvé" : "non trouvé"));
            
            if (!$user) {
                error_log("Aucun utilisateur trouvé pour ce token");
                return $this->view('auth/confirmation', [
                    'title' => 'Confirmation - KitiSmart',
                    'success' => false,
                    'message' => 'Token invalide ou expiré'
                ]);
            }

            error_log("Utilisateur trouvé: " . print_r($user, true));
            
            if (strtotime($user->confirmation_expires) < time()) {
                return $this->view('auth/confirmation', [
                    'title' => 'Confirmation - KitiSmart',
                    'success' => false,
                    'message' => 'Le lien de confirmation a expiré'
                ]);
            }

            $updated = User::updateStatus($user->id, 'active');
            error_log("Résultat de la mise à jour: " . ($updated ? 'succès' : 'échec'));
            
            if (!$updated) {
                throw new \Exception('Échec de la mise à jour du statut');
            }
            
            return $this->view('auth/confirmation', [
                'title' => 'Confirmation - KitiSmart',
                'success' => true,
                'message' => 'Votre compte a été activé avec succès'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur dans confirm: " . $e->getMessage());
            return $this->view('auth/confirmation', [
                'title' => 'Confirmation - KitiSmart',
                'success' => false,
                'message' => 'Une erreur est survenue'
            ]);
        }
    }
}