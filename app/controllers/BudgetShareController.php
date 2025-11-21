<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\BudgetShare;
use App\Models\Expense;
use App\Utils\Csrf;
use App\Exceptions\TokenInvalidOrExpiredException;

class BudgetShareController extends Controller
{
    /**
     * Afficher le formulaire de création de partage
     */
    public function showShareForm($id)
    {
        // Vérifier l'authentification
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté";
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];

        // Vérifier que le budget appartient à l'utilisateur
        $budget = Budget::findById($id, $userId);
        if (!$budget) {
            $_SESSION['error'] = "Budget non trouvé";
            header('Location: /dashboard');
            exit;
        }

        // Récupérer les partages existants
        $existingShares = BudgetShare::getSharesByBudget($id, $userId);

        $csrfToken = Csrf::generateToken();

        $this->view('budget/share_form', [
            'title' => 'Partager le budget',
            'currentPage' => 'budget',
            'budget' => $budget,
            'existingShares' => $existingShares,
            'csrfToken' => $csrfToken,
            'layout' => 'dashboard'
        ]);
    }

    /**
     * Créer un nouveau partage
     */
    public function createShare($id)
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $userId = (int)$_SESSION['user_id'];

            // Vérifier que le budget appartient à l'utilisateur
            $budget = Budget::findById($id, $userId);
            if (!$budget) {
                return $this->jsonResponse(['success' => false, 'message' => 'Budget non trouvé'], 404);
            }

            // Préparer les données
            $shareData = [
                'budget_id' => $id,
                'created_by_user_id' => $userId,
                'password' => $data['password'],
                'permissions' => [
                    'can_view' => isset($data['can_view']) && $data['can_view'] === true,
                    'can_add' => isset($data['can_add']) && $data['can_add'] === true,
                    'can_edit' => isset($data['can_edit']) && $data['can_edit'] === true,
                    'can_delete' => isset($data['can_delete']) && $data['can_delete'] === true,
                    'can_view_stats' => isset($data['can_view_stats']) && $data['can_view_stats'] === true,
                ]
            ];

            // Expiration optionnelle
            if (!empty($data['expires_at'])) {
                $shareData['expires_at'] = $data['expires_at'];
            }

            // Nombre max d'utilisations optionnel
            if (!empty($data['max_uses']) && is_numeric($data['max_uses'])) {
                $shareData['max_uses'] = (int)$data['max_uses'];
            }

            // Créer le partage
            $share = BudgetShare::create($shareData);

            // Générer l'URL complète
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $shareUrl = "$protocol://$host/budget/shared/{$share->share_token}";

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Partage créé avec succès',
                'share' => [
                    'id' => (int)$share->id,
                    'token' => $share->share_token,
                    'url' => $shareUrl,
                    'expires_at' => $share->expires_at,
                    'max_uses' => $share->max_uses
                ]
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            error_log("Erreur création partage: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher la page d'accès invité (formulaire de mot de passe)
     */
    public function showGuestAccess($token)
    {
        // Vérifier si le partage existe et est valide
        $share = BudgetShare::findByToken($token);
        if (!$share || !BudgetShare::isValid($share)) {
            $this->view('budget/shared_access', [
                'title' => 'Lien Invalide',
                'token' => $token,
                'error' => 'Ce lien de partage est invalide, expiré ou a été désactivé.',
                'invalid' => true,
                'csrfToken' => '',
                'layout' => 'guest'
            ]);
            return;
        }

        // Si déjà authentifié comme invité avec ce token, rediriger vers le dashboard
        if (isset($_SESSION['guest_share_token']) && $_SESSION['guest_share_token'] === $token) {
            header('Location: /budget/shared/dashboard');
            exit;
        }

        // Si authentifié comme utilisateur normal, déconnecter
        if (isset($_SESSION['user_id'])) {
            $_SESSION['info'] = "Vous avez été déconnecté pour accéder au budget partagé";
            session_destroy();
            session_start();
        }

        $csrfToken = Csrf::generateToken();

        $this->view('budget/shared_access', [
            'title' => 'Accès Budget Partagé',
            'token' => $token,
            'csrfToken' => $csrfToken,
            'layout' => 'guest'
        ]);
    }

    /**
     * Authentifier un invité
     */
    public function authenticateGuest($token)
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $password = $data['password'] ?? '';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // Authentifier
            $result = BudgetShare::authenticate($token, $password, $ipAddress);

            if (!$result['success']) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ], 401);
            }

            // Créer la session invité
            $share = $result['share'];
            $permissions = $result['permissions'];

            $_SESSION['guest_authenticated'] = true;
            $_SESSION['guest_share_id'] = (int)$share->id;
            $_SESSION['guest_share_token'] = $token;
            $_SESSION['guest_budget_id'] = (int)$share->budget_id;
            $_SESSION['guest_permissions'] = $permissions;
            $_SESSION['guest_authenticated_at'] = time();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Authentification réussie',
                'redirect' => '/budget/shared/dashboard'
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            error_log("Erreur authentification invité: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'authentification'
            ], 500);
        }
    }

    /**
     * Dashboard invité - Afficher le budget partagé
     */
    public function guestDashboard()
    {
        // Vérifier l'authentification invité
        if (!$this->isGuestAuthenticated()) {
            $_SESSION['error'] = "Session expirée";
            header('Location: /budget/shared/' . ($_SESSION['guest_share_token'] ?? ''));
            exit;
        }

        $budgetId = (int)$_SESSION['guest_budget_id'];
        $permissions = $_SESSION['guest_permissions'];

        // Vérifier la permission de vue
        if (!BudgetShare::hasPermission($permissions, 'view')) {
            $_SESSION['error'] = "Accès non autorisé";
            $this->guestLogout();
            exit;
        }

        // Récupérer le budget
        $budget = \RedBeanPHP\R::load('budget', $budgetId);
        if (!$budget->id) {
            $_SESSION['error'] = "Budget non trouvé";
            $this->guestLogout();
            exit;
        }

        // Récupérer les dépenses
        $expenses = Expense::getExpensesByUser($budgetId, null); // null car invité

        // Calculer les statistiques si permission
        $stats = null;
        if (BudgetShare::hasPermission($permissions, 'view_stats')) {
            $stats = [
                'total' => 0,
                'pending' => 0,
                'paid' => 0
            ];

            foreach ($expenses as $expense) {
                $stats['total'] += $expense->amount;
                if ($expense->status === 'pending') {
                    $stats['pending'] += $expense->amount;
                } else {
                    $stats['paid'] += $expense->amount;
                }
            }
        }

        $csrfToken = Csrf::generateToken();

        $this->view('budget/shared_dashboard', [
            'title' => 'Budget Partagé',
            'currentPage' => 'shared_budget',
            'budget' => $budget,
            'expenses' => $expenses,
            'stats' => $stats,
            'permissions' => $permissions,
            'csrfToken' => $csrfToken,
            'layout' => 'guest'
        ]);
    }

    /**
     * Créer une dépense en tant qu'invité
     */
    public function guestCreateExpense()
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            // Vérifier l'authentification invité
            if (!$this->isGuestAuthenticated()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $permissions = $_SESSION['guest_permissions'];
            $budgetId = (int)$_SESSION['guest_budget_id'];
            $shareId = (int)$_SESSION['guest_share_id'];

            // Vérifier la permission d'ajout
            if (!BudgetShare::hasPermission($permissions, 'add')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            // Ajouter le budget_id
            $data['budget_id'] = $budgetId;
            $data['user_id'] = null; // Dépense créée par un invité

            // Créer la dépense
            $expense = Expense::create($data);

            // Logger l'action
            BudgetShare::logAccess(
                $shareId,
                BudgetShare::ACTION_EXPENSE_CREATED,
                $_SERVER['REMOTE_ADDR'] ?? null,
                ['expense_id' => (int)$expense->id, 'amount' => (float)$expense->amount]
            );

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense créée avec succès',
                'expense' => [
                    'id' => (int)$expense->id,
                    'description' => $expense->description,
                    'amount' => (float)$expense->amount,
                    'payment_date' => $expense->payment_date,
                    'status' => $expense->status
                ]
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            error_log("Erreur création dépense invité: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Révoquer un partage
     */
    public function revokeShare($id)
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $userId = (int)$_SESSION['user_id'];

            BudgetShare::revoke($id, $userId);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Partage révoqué avec succès'
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            error_log("Erreur révocation partage: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gérer les partages (liste avec statistiques)
     */
    public function manageShares()
    {
        // Vérifier l'authentification
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté";
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];

        // Récupérer tous les partages (actifs et inactifs)
        $shares = BudgetShare::getAllSharesByUser($userId);

        // Enrichir avec les informations du budget
        $sharesData = [];
        foreach ($shares as $share) {
            $budget = \RedBeanPHP\R::load('budget', $share->budget_id);
            $sharesData[] = [
                'share' => $share,
                'budget' => $budget,
                'permissions' => json_decode($share->permissions, true),
                'is_expired' => $share->expires_at && strtotime($share->expires_at) < time(),
                'is_max_uses_reached' => $share->max_uses && $share->use_count >= $share->max_uses
            ];
        }

        $csrfToken = Csrf::generateToken();

        $this->view('budget/manage_shares', [
            'title' => 'Gérer les partages',
            'currentPage' => 'shares',
            'shares' => $sharesData,
            'csrfToken' => $csrfToken,
            'layout' => 'dashboard'
        ]);
    }

    /**
     * Déconnexion invité
     */
    public function guestLogout()
    {
        // Nettoyer les variables de session invité
        unset($_SESSION['guest_authenticated']);
        unset($_SESSION['guest_share_id']);
        unset($_SESSION['guest_share_token']);
        unset($_SESSION['guest_budget_id']);
        unset($_SESSION['guest_permissions']);
        unset($_SESSION['guest_authenticated_at']);

        $_SESSION['success'] = "Vous avez été déconnecté";
        header('Location: /');
        exit;
    }

    /**
     * Vérifier si l'invité est authentifié et sa session est valide
     */
    private function isGuestAuthenticated(): bool
    {
        error_log("isGuestAuthenticated() - Checking...");
        error_log("isGuestAuthenticated() - SESSION: " . json_encode($_SESSION));

        if (!isset($_SESSION['guest_authenticated']) || $_SESSION['guest_authenticated'] !== true) {
            error_log("isGuestAuthenticated() - FAIL: guest_authenticated not set or not true");
            return false;
        }

        // Vérifier le timeout de session (2 heures par défaut)
        $sessionTimeout = 2 * 60 * 60; // 2 heures en secondes
        if (isset($_SESSION['guest_authenticated_at'])) {
            if ((time() - $_SESSION['guest_authenticated_at']) > $sessionTimeout) {
                error_log("isGuestAuthenticated() - FAIL: session timeout");
                return false;
            }
        }

        // Vérifier que le partage est toujours valide
        if (isset($_SESSION['guest_share_id'])) {
            error_log("isGuestAuthenticated() - Loading share id: " . $_SESSION['guest_share_id']);
            $share = \RedBeanPHP\R::load('budgetshare', $_SESSION['guest_share_id']);
            if (!BudgetShare::isValid($share)) {
                error_log("isGuestAuthenticated() - FAIL: share not valid");
                return false;
            }
        }

        error_log("isGuestAuthenticated() - SUCCESS");
        return true;
    }
}
