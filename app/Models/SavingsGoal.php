<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class SavingsGoal
{
    const STATUS_ACTIVE = 'actif';
    const STATUS_COMPLETED = 'atteint';
    const STATUS_CANCELLED = 'annule';

    // Icônes prédéfinies pour les objectifs
    const ICONS = [
        'fa-plane' => 'Voyage',
        'fa-car' => 'Voiture',
        'fa-home' => 'Maison',
        'fa-graduation-cap' => 'Études',
        'fa-laptop' => 'Électronique',
        'fa-ring' => 'Mariage',
        'fa-baby' => 'Enfant',
        'fa-heartbeat' => 'Santé',
        'fa-briefcase' => 'Business',
        'fa-umbrella-beach' => 'Vacances',
        'fa-piggy-bank' => 'Épargne',
        'fa-gift' => 'Cadeau',
        'fa-tools' => 'Rénovation',
        'fa-motorcycle' => 'Moto',
        'fa-couch' => 'Mobilier',
        'fa-shield-alt' => 'Sécurité'
    ];

    // Couleurs prédéfinies
    const COLORS = [
        '#0d9488' => 'Teal',
        '#3b82f6' => 'Bleu',
        '#8b5cf6' => 'Violet',
        '#ec4899' => 'Rose',
        '#f59e0b' => 'Orange',
        '#10b981' => 'Vert',
        '#ef4444' => 'Rouge',
        '#6366f1' => 'Indigo'
    ];

    /**
     * Créer un objectif d'épargne
     */
    public static function create(array $data)
    {
        try {
            $goal = R::dispense('savingsgoal');
            $goal->import([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'target_amount' => $data['target_amount'],
                'current_amount' => $data['current_amount'] ?? 0,
                'target_date' => $data['target_date'] ?? null,
                'icon' => $data['icon'] ?? 'fa-piggy-bank',
                'color' => $data['color'] ?? '#0d9488',
                'status' => self::STATUS_ACTIVE,
                'priority' => $data['priority'] ?? 'normale',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            R::store($goal);
            return $goal;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la création de l\'objectif: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour un objectif
     */
    public static function update(int $id, array $data, int $userId)
    {
        try {
            $goal = R::load('savingsgoal', $id);

            if (!$goal->id || $goal->user_id != $userId) {
                throw new \Exception('Objectif non trouvé ou accès non autorisé');
            }

            if (isset($data['name'])) $goal->name = $data['name'];
            if (isset($data['description'])) $goal->description = $data['description'];
            if (isset($data['target_amount'])) $goal->target_amount = $data['target_amount'];
            if (isset($data['current_amount'])) $goal->current_amount = $data['current_amount'];
            if (isset($data['target_date'])) $goal->target_date = $data['target_date'];
            if (isset($data['icon'])) $goal->icon = $data['icon'];
            if (isset($data['color'])) $goal->color = $data['color'];
            if (isset($data['priority'])) $goal->priority = $data['priority'];

            // Vérifier si l'objectif est atteint
            if ($goal->current_amount >= $goal->target_amount) {
                $goal->status = self::STATUS_COMPLETED;
                $goal->completed_at = date('Y-m-d H:i:s');
            }

            $goal->updated_at = date('Y-m-d H:i:s');
            R::store($goal);
            return $goal;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la mise à jour de l\'objectif: ' . $e->getMessage());
        }
    }

    /**
     * Ajouter de l'épargne à un objectif
     */
    public static function addSavings(int $id, float $amount, int $userId, ?string $note = null)
    {
        try {
            R::begin();

            $goal = R::load('savingsgoal', $id);

            if (!$goal->id || $goal->user_id != $userId) {
                throw new \Exception('Objectif non trouvé ou accès non autorisé');
            }

            if ($goal->status !== self::STATUS_ACTIVE) {
                throw new \Exception('Impossible d\'ajouter à un objectif non actif');
            }

            // Enregistrer la contribution
            $contribution = R::dispense('savingscontribution');
            $contribution->import([
                'goal_id' => $id,
                'user_id' => $userId,
                'amount' => $amount,
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            R::store($contribution);

            // Mettre à jour le montant actuel
            $goal->current_amount = (float)$goal->current_amount + $amount;

            // Vérifier si l'objectif est atteint
            if ($goal->current_amount >= $goal->target_amount) {
                $goal->status = self::STATUS_COMPLETED;
                $goal->completed_at = date('Y-m-d H:i:s');
            }

            $goal->updated_at = date('Y-m-d H:i:s');
            R::store($goal);

            R::commit();
            return $goal;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de l\'ajout d\'épargne: ' . $e->getMessage());
        }
    }

    /**
     * Retirer de l'épargne d'un objectif
     */
    public static function withdrawSavings(int $id, float $amount, int $userId, ?string $note = null)
    {
        try {
            R::begin();

            $goal = R::load('savingsgoal', $id);

            if (!$goal->id || $goal->user_id != $userId) {
                throw new \Exception('Objectif non trouvé ou accès non autorisé');
            }

            if ($amount > $goal->current_amount) {
                throw new \Exception('Montant insuffisant dans l\'objectif');
            }

            // Enregistrer le retrait (montant négatif)
            $contribution = R::dispense('savingscontribution');
            $contribution->import([
                'goal_id' => $id,
                'user_id' => $userId,
                'amount' => -$amount,
                'note' => $note ?? 'Retrait',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            R::store($contribution);

            // Mettre à jour le montant actuel
            $goal->current_amount = (float)$goal->current_amount - $amount;

            // Si l'objectif était complété, le remettre actif
            if ($goal->status === self::STATUS_COMPLETED && $goal->current_amount < $goal->target_amount) {
                $goal->status = self::STATUS_ACTIVE;
                $goal->completed_at = null;
            }

            $goal->updated_at = date('Y-m-d H:i:s');
            R::store($goal);

            R::commit();
            return $goal;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors du retrait: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un objectif (soft delete ou annulation)
     */
    public static function delete(int $id, int $userId)
    {
        try {
            $goal = R::load('savingsgoal', $id);

            if (!$goal->id || $goal->user_id != $userId) {
                throw new \Exception('Objectif non trouvé ou accès non autorisé');
            }

            $goal->status = self::STATUS_CANCELLED;
            $goal->updated_at = date('Y-m-d H:i:s');
            R::store($goal);

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer tous les objectifs actifs d'un utilisateur
     */
    public static function findActiveByUser(int $userId)
    {
        return R::find('savingsgoal',
            'user_id = ? AND status = ? ORDER BY priority DESC, target_date ASC',
            [$userId, self::STATUS_ACTIVE]
        );
    }

    /**
     * Récupérer tous les objectifs d'un utilisateur (y compris complétés)
     */
    public static function findAllByUser(int $userId, bool $includeArchived = false)
    {
        if ($includeArchived) {
            return R::find('savingsgoal',
                'user_id = ? ORDER BY status ASC, updated_at DESC',
                [$userId]
            );
        }
        return R::find('savingsgoal',
            'user_id = ? AND status != ? ORDER BY status ASC, updated_at DESC',
            [$userId, self::STATUS_CANCELLED]
        );
    }

    /**
     * Récupérer un objectif par ID
     */
    public static function findById(int $id, int $userId)
    {
        return R::findOne('savingsgoal', 'id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * Récupérer les contributions d'un objectif
     */
    public static function getContributions(int $goalId, int $userId, int $limit = 20)
    {
        $goal = self::findById($goalId, $userId);
        if (!$goal) {
            return [];
        }

        return R::find('savingscontribution',
            'goal_id = ? ORDER BY created_at DESC LIMIT ?',
            [$goalId, $limit]
        );
    }

    /**
     * Calculer le pourcentage de progression
     */
    public static function getProgressPercent($goal): float
    {
        if (!$goal || $goal->target_amount <= 0) {
            return 0;
        }
        return min(100, round(($goal->current_amount / $goal->target_amount) * 100, 1));
    }

    /**
     * Calculer le montant restant
     */
    public static function getRemainingAmount($goal): float
    {
        if (!$goal) {
            return 0;
        }
        return max(0, $goal->target_amount - $goal->current_amount);
    }

    /**
     * Calculer l'épargne mensuelle suggérée
     */
    public static function getSuggestedMonthlySavings($goal): ?float
    {
        if (!$goal || !$goal->target_date || $goal->status !== self::STATUS_ACTIVE) {
            return null;
        }

        $remaining = self::getRemainingAmount($goal);
        $targetDate = new \DateTime($goal->target_date);
        $now = new \DateTime();

        if ($targetDate <= $now) {
            return $remaining; // Tout de suite si la date est passée
        }

        $diff = $now->diff($targetDate);
        $months = ($diff->y * 12) + $diff->m + ($diff->d > 0 ? 1 : 0);

        if ($months <= 0) {
            return $remaining;
        }

        return round($remaining / $months, 0);
    }

    /**
     * Obtenir les statistiques globales d'épargne de l'utilisateur
     */
    public static function getUserStats(int $userId): array
    {
        $activeGoals = self::findActiveByUser($userId);
        $completedGoals = R::find('savingsgoal',
            'user_id = ? AND status = ?',
            [$userId, self::STATUS_COMPLETED]
        );

        $totalTarget = 0;
        $totalSaved = 0;

        foreach ($activeGoals as $goal) {
            $totalTarget += (float)$goal->target_amount;
            $totalSaved += (float)$goal->current_amount;
        }

        foreach ($completedGoals as $goal) {
            $totalSaved += (float)$goal->current_amount;
        }

        return [
            'active_count' => count($activeGoals),
            'completed_count' => count($completedGoals),
            'total_target' => $totalTarget,
            'total_saved' => $totalSaved,
            'overall_progress' => $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100, 1) : 0
        ];
    }

    /**
     * Obtenir les icônes disponibles
     */
    public static function getAvailableIcons(): array
    {
        return self::ICONS;
    }

    /**
     * Obtenir les couleurs disponibles
     */
    public static function getAvailableColors(): array
    {
        return self::COLORS;
    }
}
