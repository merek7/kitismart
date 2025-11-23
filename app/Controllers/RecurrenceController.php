<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ExpenseRecurrence;
use App\Models\Budget;
use App\Models\Categorie;
use App\Utils\Csrf;

class RecurrenceController extends Controller
{
    /**
     * Afficher la page de gestion des récurrences
     */
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        try {
            // Récupérer le budget actif
            $activeBudget = Budget::getCurrentBudget($_SESSION['user_id']);

            if (!$activeBudget) {
                return $this->redirect('/budget/create');
            }

            // Récupérer toutes les récurrences du budget
            $recurrences = ExpenseRecurrence::getAllByBudget($activeBudget->id);

            // Récupérer les catégories disponibles
            $categories = Categorie::getDefaultCategories();

            // Enrichir les récurrences avec les infos de catégorie
            $enrichedRecurrences = [];
            foreach ($recurrences as $recurrence) {
                $categorie = Categorie::findById($recurrence->categorie_id);
                $recurrence->categorie_name = $categorie ? ucfirst($categorie->type) : 'Autre';
                $recurrence->frequency_label = ExpenseRecurrence::getFrequencyLabel($recurrence->frequency);
                $enrichedRecurrences[] = $recurrence;
            }

            return $this->view('dashboard/recurrences', [
                'title' => 'Dépenses Récurrentes - KitiSmart',
                'currentPage' => 'recurrences',
                'layout' => 'dashboard',
                'activeBudget' => $activeBudget,
                'recurrences' => $enrichedRecurrences,
                'categories' => $categories,
                'frequencies' => ExpenseRecurrence::getFrequencies(),
                'csrfToken' => Csrf::generateToken()
            ]);

        } catch (\Exception $e) {
            error_log("Erreur récurrences: " . $e->getMessage());
            return $this->redirect('/dashboard');
        }
    }

    /**
     * Créer une nouvelle récurrence (API)
     */
    public function create()
    {
        if (!$this->isPostRequest()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
        }

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            // Récupérer le budget actif
            $activeBudget = Budget::getCurrentBudget($_SESSION['user_id']);

            if (!$activeBudget) {
                return $this->jsonResponse(['success' => false, 'message' => 'Aucun budget actif'], 400);
            }

            // Récupérer la catégorie
            $categorie = Categorie::findByType($data['category_type']);

            if (!$categorie) {
                return $this->jsonResponse(['success' => false, 'message' => 'Catégorie invalide'], 400);
            }

            // Créer la récurrence
            $recurrence = ExpenseRecurrence::create([
                'budget_id' => $activeBudget->id,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'categorie_id' => $categorie->id,
                'frequency' => $data['frequency'],
                'start_date' => $data['start_date'] ?? date('Y-m-d')
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Récurrence créée avec succès',
                'recurrence' => [
                    'id' => $recurrence->id,
                    'description' => $recurrence->description,
                    'amount' => $recurrence->amount,
                    'frequency' => $recurrence->frequency,
                    'frequency_label' => ExpenseRecurrence::getFrequencyLabel($recurrence->frequency),
                    'next_execution_date' => $recurrence->next_execution_date
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur création récurrence: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver une récurrence (API)
     */
    public function toggle($id)
    {
        if (!$this->isPostRequest()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
        }

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $recurrence = ExpenseRecurrence::findById($id);

            if (!$recurrence || !$recurrence->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Récurrence introuvable'], 404);
            }

            // Vérifier que la récurrence appartient au budget de l'utilisateur
            $activeBudget = Budget::getCurrentBudget($_SESSION['user_id']);
            if ($recurrence->budget_id != $activeBudget->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            $newStatus = !$recurrence->is_active;
            ExpenseRecurrence::setActive($id, $newStatus);

            return $this->jsonResponse([
                'success' => true,
                'message' => $newStatus ? 'Récurrence activée' : 'Récurrence désactivée',
                'is_active' => $newStatus
            ]);

        } catch (\Exception $e) {
            error_log("Erreur toggle récurrence: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Mettre à jour une récurrence (API)
     */
    public function update($id)
    {
        if (!$this->isPostRequest()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
        }

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $recurrence = ExpenseRecurrence::findById($id);

            if (!$recurrence || !$recurrence->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Récurrence introuvable'], 404);
            }

            // Vérifier ownership
            $activeBudget = Budget::getCurrentBudget($_SESSION['user_id']);
            if ($recurrence->budget_id != $activeBudget->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            // Préparer les données à mettre à jour
            $updateData = [];

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['amount'])) {
                $updateData['amount'] = $data['amount'];
            }

            if (isset($data['frequency'])) {
                $updateData['frequency'] = $data['frequency'];
            }

            if (isset($data['category_type'])) {
                $categorie = Categorie::findByType($data['category_type']);
                if ($categorie) {
                    $updateData['categorie_id'] = $categorie->id;
                }
            }

            ExpenseRecurrence::update($id, $updateData);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Récurrence mise à jour'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur update récurrence: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Supprimer une récurrence (API)
     */
    public function delete($id)
    {
        if (!$this->isPostRequest()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
        }

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token CSRF invalide'], 403);
            }

            $recurrence = ExpenseRecurrence::findById($id);

            if (!$recurrence || !$recurrence->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Récurrence introuvable'], 404);
            }

            // Vérifier ownership
            $activeBudget = Budget::getCurrentBudget($_SESSION['user_id']);
            if ($recurrence->budget_id != $activeBudget->id) {
                return $this->jsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            ExpenseRecurrence::delete($id);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Récurrence supprimée'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur delete récurrence: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }
}
