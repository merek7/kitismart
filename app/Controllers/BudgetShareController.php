<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\BudgetShare;
use App\Models\Expense;
use App\Models\Categorie;
use App\Models\CustomCategory;
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
            // Nettoyer la session invité si elle existe
            $this->clearGuestSession();
            
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

        // Si déjà authentifié comme invité avec ce token ET session valide, rediriger vers le dashboard
        if (isset($_SESSION['guest_share_token']) && $_SESSION['guest_share_token'] === $token && $this->isGuestAuthenticated()) {
            header('Location: /budget/shared/dashboard');
            exit;
        }

        // Nettoyer toute session invité expirée ou pour un autre token
        if (isset($_SESSION['guest_authenticated'])) {
            $this->clearGuestSession();
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
     * Nettoyer la session invité
     */
    private function clearGuestSession(): void
    {
        unset($_SESSION['guest_authenticated']);
        unset($_SESSION['guest_share_id']);
        unset($_SESSION['guest_share_token']);
        unset($_SESSION['guest_budget_id']);
        unset($_SESSION['guest_permissions']);
        unset($_SESSION['guest_authenticated_at']);
        unset($_SESSION['guest_name']);
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

            // Validation du nom de l'invité
            $guestName = trim($data['guest_name'] ?? '');
            if (strlen($guestName) < 2 || strlen($guestName) > 100) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Veuillez entrer un nom valide (2-100 caractères)'
                ], 400);
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
            $_SESSION['guest_name'] = $guestName;

            // Logger l'accès avec le nom de l'invité
            BudgetShare::logAccess(
                (int)$share->id,
                BudgetShare::ACTION_ACCESS_SUCCESS,
                $ipAddress,
                ['guest_name' => $guestName]
            );

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
            $token = $_SESSION['guest_share_token'] ?? null;
            $this->clearGuestSession();
            
            if ($token) {
                $_SESSION['error'] = "Session expirée, veuillez vous reconnecter";
                header('Location: /budget/shared/' . $token);
            } else {
                $_SESSION['error'] = "Session invalide";
                header('Location: /');
            }
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

        // Ajouter le comptage des pièces jointes pour chaque dépense
        foreach ($expenses as $expense) {
            $attachments = \App\Models\ExpenseAttachment::findByExpense($expense->id);
            $expense->attachments_count = count($attachments);
        }

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

        // Récupérer les catégories si permission d'ajouter
        $categories = [];
        $customCategories = [];
        if (BudgetShare::hasPermission($permissions, 'add')) {
            $categories = Categorie::getDefaultCategories();
            // Récupérer les catégories personnalisées du propriétaire du budget
            $share = \RedBeanPHP\R::load('budgetshare', $_SESSION['guest_share_id']);
            if ($share->created_by_user_id) {
                $customCategories = CustomCategory::findByUser((int)$share->created_by_user_id);
            }
        }

        $this->view('budget/shared_dashboard', [
            'title' => 'Budget Partagé',
            'currentPage' => 'shared_budget',
            'budget' => $budget,
            'expenses' => $expenses,
            'stats' => $stats,
            'permissions' => $permissions,
            'categories' => $categories,
            'customCategories' => $customCategories,
            'csrfToken' => $csrfToken,
            'guestName' => $_SESSION['guest_name'] ?? 'Invité',
            'layout' => 'guest',
            'styles' => ['budget/shared_dashboard.css'],
            'pageScripts' => ['budget/shared_dashboard.js']
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

            // Récupérer le share pour obtenir le user_id du propriétaire
            $share = \RedBeanPHP\R::load('budgetshare', $shareId);
            if (!$share || !$share->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Partage non trouvé'], 404);
            }

            // Ajouter le budget_id et le user_id du propriétaire (requis pour la validation)
            $data['budget_id'] = $budgetId;
            $data['user_id'] = (int)$share->created_by_user_id;

            // Ajouter les informations de l'invité pour traçabilité
            $data['guest_name'] = $_SESSION['guest_name'] ?? 'Invité';
            $data['guest_share_id'] = $shareId;

            // Créer la dépense
            $expense = Expense::create($data);

            // Logger l'action
            BudgetShare::logAccess(
                $shareId,
                BudgetShare::ACTION_EXPENSE_CREATED,
                $_SERVER['REMOTE_ADDR'] ?? null,
                [
                    'expense_id' => (int)$expense->id,
                    'amount' => (float)$expense->amount,
                    'guest_name' => $_SESSION['guest_name'] ?? 'Invité'
                ]
            );

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense créée avec succès',
                'expense_id' => (int)$expense->id, // Pour l'upload des pièces jointes
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
     * Gérer les partages (liste avec statistiques et pagination)
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

        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Récupérer les partages avec pagination
        $shares = BudgetShare::getAllSharesByUserPaginated($userId, $perPage, $offset);
        $totalShares = BudgetShare::countAllSharesByUser($userId);
        $totalPages = ceil($totalShares / $perPage);

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
            'layout' => 'dashboard',
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalShares,
                'per_page' => $perPage
            ]
        ]);
    }

    /**
     * Modifier une dépense en tant qu'invité
     */
    public function guestUpdateExpense($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!$this->isGuestAuthenticated()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $permissions = $_SESSION['guest_permissions'];
            $budgetId = (int)$_SESSION['guest_budget_id'];
            $shareId = (int)$_SESSION['guest_share_id'];

            if (!BudgetShare::hasPermission($permissions, 'edit')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            // Vérifier que la dépense appartient au budget partagé
            $expense = Expense::findById((int)$id);
            if (!$expense || $expense->budget_id != $budgetId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Dépense non trouvée'], 404);
            }

            // Mettre à jour la dépense
            $updatedExpense = Expense::update((int)$id, $data);

            // Logger l'action
            BudgetShare::logAccess(
                $shareId,
                BudgetShare::ACTION_EXPENSE_UPDATED,
                $_SERVER['REMOTE_ADDR'] ?? null,
                [
                    'expense_id' => (int)$id,
                    'guest_name' => $_SESSION['guest_name'] ?? 'Invité'
                ]
            );

            // Notifier le propriétaire
            $this->notifyOwner($shareId, 'expense_updated', [
                'expense_id' => (int)$id,
                'expense_description' => $updatedExpense->description,
                'guest_name' => $_SESSION['guest_name'] ?? 'Invité'
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense modifiée avec succès'
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log("Erreur modification dépense invité: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une dépense en tant qu'invité
     */
    public function guestDeleteExpense($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!$this->isGuestAuthenticated()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $permissions = $_SESSION['guest_permissions'];
            $budgetId = (int)$_SESSION['guest_budget_id'];
            $shareId = (int)$_SESSION['guest_share_id'];

            if (!BudgetShare::hasPermission($permissions, 'delete')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            }

            // Vérifier que la dépense appartient au budget partagé
            $expense = Expense::findById((int)$id);
            if (!$expense || $expense->budget_id != $budgetId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Dépense non trouvée'], 404);
            }

            $expenseDescription = $expense->description;

            // Supprimer la dépense
            Expense::delete((int)$id);

            // Logger l'action
            BudgetShare::logAccess(
                $shareId,
                BudgetShare::ACTION_EXPENSE_DELETED,
                $_SERVER['REMOTE_ADDR'] ?? null,
                [
                    'expense_id' => (int)$id,
                    'guest_name' => $_SESSION['guest_name'] ?? 'Invité'
                ]
            );

            // Notifier le propriétaire
            $this->notifyOwner($shareId, 'expense_deleted', [
                'expense_description' => $expenseDescription,
                'guest_name' => $_SESSION['guest_name'] ?? 'Invité'
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur suppression dépense invité: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Marquer une dépense comme payée en tant qu'invité
     */
    public function guestMarkExpensePaid($id)
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!$this->isGuestAuthenticated()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $permissions = $_SESSION['guest_permissions'];
            $budgetId = (int)$_SESSION['guest_budget_id'];
            $shareId = (int)$_SESSION['guest_share_id'];

            if (!BudgetShare::hasPermission($permissions, 'edit')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Permission refusée'], 403);
            }

            // Vérifier que la dépense appartient au budget partagé
            $expense = Expense::findById((int)$id);
            if (!$expense || $expense->budget_id != $budgetId) {
                return $this->jsonResponse(['success' => false, 'message' => 'Dépense non trouvée'], 404);
            }

            // Marquer comme payée
            Expense::markAsPaid((int)$id);

            // Logger l'action
            BudgetShare::logAccess(
                $shareId,
                BudgetShare::ACTION_EXPENSE_UPDATED,
                $_SERVER['REMOTE_ADDR'] ?? null,
                [
                    'expense_id' => (int)$id,
                    'action' => 'mark_paid',
                    'guest_name' => $_SESSION['guest_name'] ?? 'Invité'
                ]
            );

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense marquée comme payée'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur mark-paid invité: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour les paramètres d'un partage
     */
    public function updateShare($id)
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $userId = (int)$_SESSION['user_id'];

            $updateData = [
                'name' => $data['name'] ?? null,
                'permissions' => $data['permissions'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'max_uses' => $data['max_uses'] ?? null
            ];

            $share = BudgetShare::updateShare((int)$id, $userId, $updateData);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Partage mis à jour avec succès',
                'share' => [
                    'id' => (int)$share->id,
                    'name' => $share->name,
                    'permissions' => json_decode($share->permissions, true)
                ]
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log("Erreur update partage: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Régénérer le mot de passe d'un partage
     */
    public function regeneratePassword($id)
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $userId = (int)$_SESSION['user_id'];
            $newPassword = $data['password'] ?? '';

            if (strlen($newPassword) < 6) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Le mot de passe doit contenir au moins 6 caractères'
                ], 400);
            }

            BudgetShare::regeneratePassword((int)$id, $userId, $newPassword);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Mot de passe régénéré avec succès'
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            error_log("Erreur regenerate password: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtenir les logs d'un partage
     */
    public function getShareLogs($id)
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;

            $logs = BudgetShare::getShareLogs((int)$id, $userId, $limit, ($page - 1) * $limit);
            $totalLogs = BudgetShare::countShareLogs((int)$id, $userId);

            $logsData = [];
            foreach ($logs as $log) {
                $logsData[] = [
                    'id' => (int)$log->id,
                    'action' => $log->action,
                    'ip_address' => $log->ip_address,
                    'success' => (bool)$log->success,
                    'metadata' => $log->metadata ? json_decode($log->metadata, true) : null,
                    'created_at' => $log->created_at
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'logs' => $logsData,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalLogs / $limit),
                    'total_items' => $totalLogs
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur get logs: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Générer un QR Code pour un partage
     */
    public function generateQRCode($id)
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                header('HTTP/1.0 401 Unauthorized');
                exit;
            }

            $userId = (int)$_SESSION['user_id'];
            $share = \RedBeanPHP\R::load('budgetshare', (int)$id);

            if (!$share->id || $share->created_by_user_id != $userId) {
                header('HTTP/1.0 404 Not Found');
                exit;
            }

            // Paramètres de personnalisation
            $size = isset($_GET['size']) ? min(500, max(100, (int)$_GET['size'])) : 300;
            $format = isset($_GET['format']) && $_GET['format'] === 'png' ? 'png' : 'svg';
            $color = isset($_GET['color']) ? ltrim($_GET['color'], '#') : '0d9488'; // Couleur teal par défaut
            $bgColor = isset($_GET['bg']) ? ltrim($_GET['bg'], '#') : 'ffffff';
            $download = isset($_GET['download']) && $_GET['download'] === '1';

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $shareUrl = "$protocol://$host/budget/shared/{$share->share_token}";

            // Générer le QR code avec endroid/qr-code v6
            $foregroundColor = new \Endroid\QrCode\Color\Color(
                hexdec(substr($color, 0, 2)),
                hexdec(substr($color, 2, 2)),
                hexdec(substr($color, 4, 2))
            );
            $backgroundColor = new \Endroid\QrCode\Color\Color(
                hexdec(substr($bgColor, 0, 2)),
                hexdec(substr($bgColor, 2, 2)),
                hexdec(substr($bgColor, 4, 2))
            );

            // Récupérer le nom du budget
            $budget = \RedBeanPHP\R::load('budget', $share->budget_id);
            $budgetName = $share->name ?: ($budget->name ?? 'Budget partagé');
            $appName = 'KitiSmart';
            $fileName = 'qrcode-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $budgetName);

            // Générer le QR code de base en SVG
            $builder = new \Endroid\QrCode\Builder\Builder(
                writer: new \Endroid\QrCode\Writer\SvgWriter(),
                data: $shareUrl,
                size: $size,
                margin: 10,
                foregroundColor: $foregroundColor,
                backgroundColor: $backgroundColor
            );

            $result = $builder->build();
            $qrSvg = $result->getString();

            // Créer un SVG avec le header (nom app + budget) et le QR code
            $headerHeight = 60;
            $padding = 20;
            $totalWidth = $size + ($padding * 2);
            $totalHeight = $size + $headerHeight + ($padding * 2);

            // Extraire le contenu SVG du QR code
            preg_match('/<svg[^>]*>(.*?)<\/svg>/s', $qrSvg, $matches);
            $qrContent = $matches[1] ?? '';
            
            preg_match('/viewBox="([^"]*)"/', $qrSvg, $viewBoxMatch);
            $originalViewBox = $viewBoxMatch[1] ?? "0 0 $size $size";

            $finalSvg = '<?xml version="1.0" encoding="UTF-8"?>';
            $finalSvg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalWidth . '" height="' . $totalHeight . '" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '">';
            $finalSvg .= '<rect width="100%" height="100%" fill="#' . $bgColor . '"/>';
            $finalSvg .= '<text x="' . ($totalWidth / 2) . '" y="28" font-family="Arial, sans-serif" font-size="18" font-weight="bold" fill="#' . $color . '" text-anchor="middle">' . htmlspecialchars($appName) . '</text>';
            $finalSvg .= '<text x="' . ($totalWidth / 2) . '" y="48" font-family="Arial, sans-serif" font-size="12" fill="#666666" text-anchor="middle">' . htmlspecialchars($budgetName) . '</text>';
            $finalSvg .= '<g transform="translate(' . $padding . ', ' . ($headerHeight + 10) . ')">';
            $finalSvg .= '<svg width="' . $size . '" height="' . $size . '" viewBox="' . $originalViewBox . '">' . $qrContent . '</svg>';
            $finalSvg .= '</g>';
            $finalSvg .= '</svg>';

            // Format PNG (nécessite GD)
            if ($format === 'png' && extension_loaded('gd')) {
                // Convertir SVG en PNG avec GD
                $image = imagecreatetruecolor($totalWidth, $totalHeight);
                
                // Couleurs
                $bgColorGd = imagecolorallocate($image, 
                    hexdec(substr($bgColor, 0, 2)),
                    hexdec(substr($bgColor, 2, 2)),
                    hexdec(substr($bgColor, 4, 2))
                );
                $fgColorGd = imagecolorallocate($image,
                    hexdec(substr($color, 0, 2)),
                    hexdec(substr($color, 2, 2)),
                    hexdec(substr($color, 4, 2))
                );
                $grayColor = imagecolorallocate($image, 102, 102, 102);
                
                imagefill($image, 0, 0, $bgColorGd);
                
                // Textes (approximatif sans TTF)
                imagestring($image, 5, (int)(($totalWidth - strlen($appName) * 9) / 2), 10, $appName, $fgColorGd);
                imagestring($image, 3, (int)(($totalWidth - strlen($budgetName) * 7) / 2), 35, $budgetName, $grayColor);
                
                // Générer QR en PNG séparément
                $pngBuilder = new \Endroid\QrCode\Builder\Builder(
                    writer: new \Endroid\QrCode\Writer\PngWriter(),
                    data: $shareUrl,
                    size: $size,
                    margin: 10,
                    foregroundColor: $foregroundColor,
                    backgroundColor: $backgroundColor
                );
                $pngResult = $pngBuilder->build();
                
                $qrImage = imagecreatefromstring($pngResult->getString());
                imagecopy($image, $qrImage, $padding, $headerHeight + 10, 0, 0, $size, $size);
                imagedestroy($qrImage);
                
                header('Content-Type: image/png');
                if ($download) {
                    header('Content-Disposition: attachment; filename="' . $fileName . '.png"');
                }
                imagepng($image);
                imagedestroy($image);
                exit;
            }

            // Format SVG (par défaut)
            header('Content-Type: image/svg+xml');
            if ($download) {
                header('Content-Disposition: attachment; filename="' . $fileName . '.svg"');
            }
            echo $finalSvg;
            exit;

        } catch (\Throwable $e) {
            error_log("Erreur génération QR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            header('HTTP/1.0 500 Internal Server Error');
            header('Content-Type: text/plain');
            echo "Error: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Notifier le propriétaire d'une action sur son budget partagé
     */
    private function notifyOwner(int $shareId, string $action, array $data)
    {
        try {
            $share = \RedBeanPHP\R::load('budgetshare', $shareId);
            if (!$share->id) return;

            $owner = \RedBeanPHP\R::load('users', $share->created_by_user_id);
            if (!$owner->id) return;

            $budget = \RedBeanPHP\R::load('budget', $share->budget_id);

            // Créer une notification en base
            $notification = \RedBeanPHP\R::dispense('notification');
            $notification->user_id = $owner->id;
            $notification->type = 'budget_share_activity';
            $notification->title = $this->getNotificationTitle($action);
            $notification->message = $this->getNotificationMessage($action, $data, $budget->name ?? 'Budget');
            $notification->data = json_encode([
                'share_id' => $shareId,
                'budget_id' => $share->budget_id,
                'action' => $action,
                'details' => $data
            ]);
            $notification->is_read = 0;
            $notification->created_at = date('Y-m-d H:i:s');

            \RedBeanPHP\R::store($notification);

        } catch (\Exception $e) {
            error_log("Erreur notification propriétaire: " . $e->getMessage());
        }
    }

    /**
     * Obtenir le titre de notification selon l'action
     */
    private function getNotificationTitle(string $action): string
    {
        $titles = [
            'access' => 'Nouvel accès à votre budget',
            'expense_created' => 'Nouvelle dépense ajoutée',
            'expense_updated' => 'Dépense modifiée',
            'expense_deleted' => 'Dépense supprimée'
        ];
        return $titles[$action] ?? 'Activité sur votre budget partagé';
    }

    /**
     * Obtenir le message de notification selon l'action
     */
    private function getNotificationMessage(string $action, array $data, string $budgetName): string
    {
        $guestName = $data['guest_name'] ?? 'Un invité';

        switch ($action) {
            case 'access':
                return "$guestName a accédé à votre budget \"$budgetName\"";
            case 'expense_created':
                $desc = $data['expense_description'] ?? 'une dépense';
                return "$guestName a ajouté \"$desc\" sur \"$budgetName\"";
            case 'expense_updated':
                $desc = $data['expense_description'] ?? 'une dépense';
                return "$guestName a modifié \"$desc\" sur \"$budgetName\"";
            case 'expense_deleted':
                $desc = $data['expense_description'] ?? 'une dépense';
                return "$guestName a supprimé \"$desc\" de \"$budgetName\"";
            default:
                return "Activité de $guestName sur \"$budgetName\"";
        }
    }

    /**
     * Déconnexion invité
     */
    public function guestLogout()
    {
        $this->clearGuestSession();
        $_SESSION['success'] = "Vous avez été déconnecté";
        header('Location: /');
        exit;
    }

    /**
     * Vérifier si l'invité est authentifié et sa session est valide
     */
    private function isGuestAuthenticated(): bool
    {
        if (!isset($_SESSION['guest_authenticated']) || $_SESSION['guest_authenticated'] !== true) {
            return false;
        }

        // Vérifier le timeout de session (8 heures)
        $sessionTimeout = 8 * 60 * 60; // 8 heures en secondes
        if (isset($_SESSION['guest_authenticated_at'])) {
            if ((time() - $_SESSION['guest_authenticated_at']) > $sessionTimeout) {
                return false;
            }
        }

        // Vérifier que le partage est toujours valide
        if (isset($_SESSION['guest_share_id'])) {
            $share = \RedBeanPHP\R::load('budgetshare', $_SESSION['guest_share_id']);
            if (!BudgetShare::isValid($share)) {
                return false;
            }
        }

        return true;
    }
}
