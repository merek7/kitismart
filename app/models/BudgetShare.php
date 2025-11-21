<?php

namespace App\Models;

use RedBeanPHP\R as R;

class BudgetShare
{
    // Constantes pour les actions (logs)
    const ACTION_ACCESS_ATTEMPT = 'access_attempt';
    const ACTION_ACCESS_SUCCESS = 'access_success';
    const ACTION_ACCESS_DENIED = 'access_denied';
    const ACTION_EXPENSE_CREATED = 'expense_created';
    const ACTION_EXPENSE_UPDATED = 'expense_updated';
    const ACTION_EXPENSE_DELETED = 'expense_deleted';
    const ACTION_SHARE_REVOKED = 'share_revoked';

    // Rate limiting
    const MAX_ATTEMPTS = 5;
    const BLOCK_DURATION_MINUTES = 15;

    /**
     * Créer un nouveau partage de budget
     */
    public static function create(array $data)
    {
        self::validateShareData($data);

        R::begin();
        try {
            // Vérifier que le budget appartient à l'utilisateur
            $budget = R::load('budget', $data['budget_id']);
            if (!$budget->id || $budget->user_id != $data['created_by_user_id']) {
                throw new \Exception("Budget non trouvé ou accès non autorisé");
            }

            // Générer un token unique cryptographiquement sûr
            $token = bin2hex(random_bytes(32)); // 64 caractères hex

            // Créer le partage
            $share = R::dispense('budgetshare');
            $share->budget_id = $data['budget_id'];
            $share->created_by_user_id = $data['created_by_user_id'];
            $share->share_token = $token;
            $share->password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Permissions par défaut ou personnalisées
            $permissions = $data['permissions'] ?? [
                'can_view' => true,
                'can_add' => false,
                'can_edit' => false,
                'can_delete' => false,
                'can_view_stats' => true
            ];
            $share->permissions = json_encode($permissions);

            $share->expires_at = $data['expires_at'] ?? null;
            $share->max_uses = $data['max_uses'] ?? null;
            $share->use_count = 0;
            $share->is_active = 1;
            $share->created_at = date('Y-m-d H:i:s');

            R::store($share);
            R::commit();

            return $share;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la création du partage: ' . $e->getMessage());
        }
    }

    /**
     * Valider les données de création d'un partage
     */
    private static function validateShareData(array $data)
    {
        $required = ['budget_id', 'created_by_user_id', 'password'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("Le champ $field est requis");
            }
        }

        // Valider la force du mot de passe
        if (strlen($data['password']) < 6) {
            throw new \Exception("Le mot de passe doit contenir au moins 6 caractères");
        }
    }

    /**
     * Trouver un partage par token
     */
    public static function findByToken(string $token)
    {
        return R::findOne('budgetshare', 'share_token = ?', [$token]);
    }

    /**
     * Vérifier si un partage est valide et actif
     */
    public static function isValid($share): bool
    {
        if (!$share || !$share->id) {
            return false;
        }

        // Vérifier si actif
        if (!$share->is_active) {
            return false;
        }

        // Vérifier l'expiration
        if ($share->expires_at && strtotime($share->expires_at) < time()) {
            return false;
        }

        // Vérifier le nombre max d'utilisations
        if ($share->max_uses && $share->use_count >= $share->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Authentifier un invité avec le mot de passe
     */
    public static function authenticate(string $token, string $password, string $ipAddress): array
    {
        // Vérifier le rate limiting d'abord
        if (self::isRateLimited($token, $ipAddress)) {
            $blockedUntil = self::getBlockedUntil($token, $ipAddress);
            return [
                'success' => false,
                'message' => "Trop de tentatives échouées. Réessayez après " . date('H:i', strtotime($blockedUntil)),
                'blocked_until' => $blockedUntil
            ];
        }

        $share = self::findByToken($token);

        // Log de la tentative
        self::logAccess($share ? $share->id : null, self::ACTION_ACCESS_ATTEMPT, $ipAddress, null);

        if (!$share || !self::isValid($share)) {
            self::recordFailedAttempt($token, $ipAddress);
            return [
                'success' => false,
                'message' => 'Lien invalide, expiré ou désactivé'
            ];
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $share->password_hash)) {
            self::recordFailedAttempt($token, $ipAddress);
            self::logAccess($share->id, self::ACTION_ACCESS_DENIED, $ipAddress, null, false, 'Mot de passe incorrect');
            return [
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ];
        }

        // Authentification réussie - réinitialiser le compteur d'échecs
        self::resetFailedAttempts($token, $ipAddress);

        // Incrémenter le compteur d'utilisations
        $share->use_count = (int)$share->use_count + 1;
        R::store($share);

        // Log succès
        self::logAccess($share->id, self::ACTION_ACCESS_SUCCESS, $ipAddress, null);

        return [
            'success' => true,
            'share' => $share,
            'permissions' => json_decode($share->permissions, true)
        ];
    }

    /**
     * Vérifier si l'IP est rate limitée pour ce token
     */
    private static function isRateLimited(string $token, string $ipAddress): bool
    {
        $rateLimit = R::findOne('budgetshare_rate_limit',
            'share_token = ? AND ip_address = ?',
            [$token, $ipAddress]
        );

        if (!$rateLimit) {
            return false;
        }

        // Vérifier si bloqué
        if ($rateLimit->blocked_until && strtotime($rateLimit->blocked_until) > time()) {
            return true;
        }

        // Vérifier le nombre de tentatives dans la fenêtre de temps
        $timeWindow = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        if ($rateLimit->first_attempt_at > $timeWindow && $rateLimit->attempt_count >= self::MAX_ATTEMPTS) {
            // Bloquer
            $rateLimit->blocked_until = date('Y-m-d H:i:s', strtotime('+' . self::BLOCK_DURATION_MINUTES . ' minutes'));
            R::store($rateLimit);
            return true;
        }

        return false;
    }

    /**
     * Obtenir l'heure de déblocage
     */
    private static function getBlockedUntil(string $token, string $ipAddress): ?string
    {
        $rateLimit = R::findOne('budgetshare_rate_limit',
            'share_token = ? AND ip_address = ?',
            [$token, $ipAddress]
        );

        return $rateLimit ? $rateLimit->blocked_until : null;
    }

    /**
     * Enregistrer une tentative échouée
     */
    private static function recordFailedAttempt(string $token, string $ipAddress)
    {
        $rateLimit = R::findOne('budgetshare_rate_limit',
            'share_token = ? AND ip_address = ?',
            [$token, $ipAddress]
        );

        if (!$rateLimit) {
            $rateLimit = R::dispense('budgetshare_rate_limit');
            $rateLimit->share_token = $token;
            $rateLimit->ip_address = $ipAddress;
            $rateLimit->attempt_count = 1;
            $rateLimit->first_attempt_at = date('Y-m-d H:i:s');
        } else {
            // Réinitialiser si hors de la fenêtre de temps
            $timeWindow = date('Y-m-d H:i:s', strtotime('-15 minutes'));
            if ($rateLimit->first_attempt_at < $timeWindow) {
                $rateLimit->attempt_count = 1;
                $rateLimit->first_attempt_at = date('Y-m-d H:i:s');
            } else {
                $rateLimit->attempt_count = (int)$rateLimit->attempt_count + 1;
            }
        }

        $rateLimit->last_attempt_at = date('Y-m-d H:i:s');
        R::store($rateLimit);
    }

    /**
     * Réinitialiser le compteur d'échecs après une authentification réussie
     */
    private static function resetFailedAttempts(string $token, string $ipAddress)
    {
        $rateLimit = R::findOne('budgetshare_rate_limit',
            'share_token = ? AND ip_address = ?',
            [$token, $ipAddress]
        );

        if ($rateLimit) {
            R::trash($rateLimit);
        }
    }

    /**
     * Logger un accès ou une action
     */
    public static function logAccess(
        ?int $shareId,
        string $action,
        ?string $ipAddress,
        ?array $metadata = null,
        bool $success = true,
        ?string $errorMessage = null
    ) {
        if (!$shareId) {
            return; // Ne pas logger si pas de share_id
        }

        $log = R::dispense('budgetshare_log');
        $log->share_id = $shareId;
        $log->action = $action;
        $log->ip_address = $ipAddress;
        $log->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $log->success = $success ? 1 : 0;
        $log->error_message = $errorMessage;
        $log->metadata = $metadata ? json_encode($metadata) : null;
        $log->created_at = date('Y-m-d H:i:s');

        R::store($log);
    }

    /**
     * Révoquer un partage
     */
    public static function revoke(int $shareId, int $userId): bool
    {
        $share = R::load('budgetshare', $shareId);

        if (!$share->id || $share->created_by_user_id != $userId) {
            throw new \Exception("Partage non trouvé ou accès non autorisé");
        }

        $share->is_active = 0;
        $share->updated_at = date('Y-m-d H:i:s');
        R::store($share);

        self::logAccess($shareId, self::ACTION_SHARE_REVOKED, $_SERVER['REMOTE_ADDR'] ?? null);

        return true;
    }

    /**
     * Obtenir les partages actifs d'un utilisateur
     */
    public static function getActiveSharesByUser(int $userId)
    {
        return R::find('budgetshare',
            'created_by_user_id = ? AND is_active = 1 ORDER BY created_at DESC',
            [$userId]
        );
    }

    /**
     * Obtenir les partages d'un budget
     */
    public static function getSharesByBudget(int $budgetId, int $userId)
    {
        return R::find('budgetshare',
            'budget_id = ? AND created_by_user_id = ? ORDER BY created_at DESC',
            [$budgetId, $userId]
        );
    }

    /**
     * Obtenir les logs d'un partage
     */
    public static function getShareLogs(int $shareId, int $userId, int $limit = 50)
    {
        // Vérifier que le partage appartient à l'utilisateur
        $share = R::load('budgetshare', $shareId);
        if (!$share->id || $share->created_by_user_id != $userId) {
            throw new \Exception("Partage non trouvé ou accès non autorisé");
        }

        return R::find('budgetshare_log',
            'share_id = ? ORDER BY created_at DESC LIMIT ?',
            [$shareId, $limit]
        );
    }

    /**
     * Vérifier les permissions d'un invité
     */
    public static function hasPermission(array $permissions, string $action): bool
    {
        $permissionMap = [
            'view' => 'can_view',
            'add' => 'can_add',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            'view_stats' => 'can_view_stats'
        ];

        $permissionKey = $permissionMap[$action] ?? null;

        if (!$permissionKey) {
            return false;
        }

        return isset($permissions[$permissionKey]) && $permissions[$permissionKey] === true;
    }

    /**
     * Nettoyer les anciennes entrées de rate limiting (maintenance)
     */
    public static function cleanupRateLimits()
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 day'));
        R::exec('DELETE FROM budgetshare_rate_limit WHERE last_attempt_at < ? AND blocked_until IS NULL', [$cutoff]);
    }
}
