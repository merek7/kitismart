<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SavingsGoal;
use App\Utils\Csrf;

class SavingsGoalController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    /**
     * Afficher la liste des objectifs d'épargne
     */
    public function index()
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            // Récupérer tous les objectifs (actifs et complétés)
            $goals = SavingsGoal::findAllByUser($userId);

            // Calculer les stats et données supplémentaires pour chaque objectif
            $goalsData = [];
            foreach ($goals as $goal) {
                $goalsData[] = [
                    'goal' => $goal,
                    'progress' => SavingsGoal::getProgressPercent($goal),
                    'remaining' => SavingsGoal::getRemainingAmount($goal),
                    'monthly_suggestion' => SavingsGoal::getSuggestedMonthlySavings($goal)
                ];
            }

            // Statistiques globales
            $stats = SavingsGoal::getUserStats($userId);

            $this->view('dashboard/savings_goals', [
                'title' => 'Objectifs d\'Épargne',
                'currentPage' => 'savings',
                'goalsData' => $goalsData,
                'stats' => $stats,
                'csrfToken' => Csrf::generateToken(),
                'availableIcons' => SavingsGoal::getAvailableIcons(),
                'availableColors' => SavingsGoal::getAvailableColors(),
                'styles' => ['dashboard/savings_goals.css'],
                'pageScripts' => ['dashboard/savings_goals.js'],
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur objectifs d'épargne: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors du chargement des objectifs";
            $this->redirect('/dashboard');
        }
    }

    /**
     * Créer un nouvel objectif
     */
    public function create()
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            // Vérification CSRF
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }

            if (!isset($data['csrf_token']) || !Csrf::validateToken($data['csrf_token'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            // Validation
            if (empty($data['name']) || empty($data['target_amount'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Le nom et le montant cible sont requis'
                ], 400);
            }

            $targetAmount = (float)$data['target_amount'];
            if ($targetAmount <= 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Le montant cible doit être positif'
                ], 400);
            }

            $goal = SavingsGoal::create([
                'user_id' => $userId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'target_amount' => $targetAmount,
                'current_amount' => (float)($data['current_amount'] ?? 0),
                'target_date' => !empty($data['target_date']) ? $data['target_date'] : null,
                'icon' => $data['icon'] ?? 'fa-piggy-bank',
                'color' => $data['color'] ?? '#0d9488',
                'priority' => $data['priority'] ?? 'normale'
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Objectif créé avec succès',
                'goal' => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => $goal->target_amount,
                    'current_amount' => $goal->current_amount,
                    'progress' => SavingsGoal::getProgressPercent($goal)
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur création objectif: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création'
            ], 500);
        }
    }

    /**
     * Mettre à jour un objectif
     */
    public function update(int $id)
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }

            if (!isset($data['csrf_token']) || !Csrf::validateToken($data['csrf_token'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $goal = SavingsGoal::update($id, [
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'target_amount' => isset($data['target_amount']) ? (float)$data['target_amount'] : null,
                'target_date' => $data['target_date'] ?? null,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'priority' => $data['priority'] ?? null
            ], $userId);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Objectif mis à jour',
                'goal' => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'progress' => SavingsGoal::getProgressPercent($goal)
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur mise à jour objectif: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter de l'épargne à un objectif
     */
    public function addSavings(int $id)
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }

            if (!isset($data['csrf_token']) || !Csrf::validateToken($data['csrf_token'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $amount = (float)($data['amount'] ?? 0);
            if ($amount <= 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Le montant doit être positif'
                ], 400);
            }

            $goal = SavingsGoal::addSavings($id, $amount, $userId, $data['note'] ?? null);

            $isCompleted = $goal->status === SavingsGoal::STATUS_COMPLETED;

            return $this->jsonResponse([
                'success' => true,
                'message' => $isCompleted ? 'Félicitations ! Objectif atteint !' : 'Épargne ajoutée avec succès',
                'goal' => [
                    'id' => $goal->id,
                    'current_amount' => $goal->current_amount,
                    'progress' => SavingsGoal::getProgressPercent($goal),
                    'remaining' => SavingsGoal::getRemainingAmount($goal),
                    'is_completed' => $isCompleted
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur ajout épargne: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retirer de l'épargne d'un objectif
     */
    public function withdraw(int $id)
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }

            if (!isset($data['csrf_token']) || !Csrf::validateToken($data['csrf_token'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $amount = (float)($data['amount'] ?? 0);
            if ($amount <= 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Le montant doit être positif'
                ], 400);
            }

            $goal = SavingsGoal::withdrawSavings($id, $amount, $userId, $data['note'] ?? null);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Retrait effectué',
                'goal' => [
                    'id' => $goal->id,
                    'current_amount' => $goal->current_amount,
                    'progress' => SavingsGoal::getProgressPercent($goal),
                    'remaining' => SavingsGoal::getRemainingAmount($goal)
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur retrait: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer/annuler un objectif
     */
    public function delete(int $id)
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }

            if (!isset($data['csrf_token']) || !Csrf::validateToken($data['csrf_token'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            SavingsGoal::delete($id, $userId);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Objectif supprimé'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur suppression objectif: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'historique des contributions
     */
    public function getHistory(int $id)
    {
        try {
            $userId = (int)$_SESSION['user_id'];

            $goal = SavingsGoal::findById($id, $userId);
            if (!$goal) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Objectif non trouvé'
                ], 404);
            }

            $contributions = SavingsGoal::getContributions($id, $userId);
            $history = [];

            foreach ($contributions as $contribution) {
                $history[] = [
                    'id' => $contribution->id,
                    'amount' => (float)$contribution->amount,
                    'note' => $contribution->note,
                    'date' => date('d/m/Y H:i', strtotime($contribution->created_at))
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'goal' => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'current_amount' => (float)$goal->current_amount,
                    'target_amount' => (float)$goal->target_amount
                ],
                'history' => $history
            ]);

        } catch (\Exception $e) {
            error_log("Erreur historique: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'historique'
            ], 500);
        }
    }
}
