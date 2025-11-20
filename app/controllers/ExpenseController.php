<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Expense;
use App\Utils\Csrf;
use App\Exceptions\TokenInvalidOrExpiredException;
use App\Models\Budget;
use App\Models\Categorie;



$activeBudget = Budget::getActiveBudget(isset($_SESSION['user_id']));
if ($activeBudget && !defined('BUDGET')) {
    define('BUDGET', $activeBudget);
}

class ExpenseController extends Controller
{
    public function showCreateExpenseForm()
    {
        $csrfToken = Csrf::generateToken();
        $categories = Categorie::getDefaultCategories();
        try {
            $this->view('dashboard/expense_create', [
                'title' => 'Nouvelle Dépense',
                'currentPage' => 'expenses',
                'categories' => $categories,
                'layout' => 'dashboard',
                'budget' => BUDGET->remaining_amount,
                'csrfToken' => $csrfToken
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage du formulaire de dépense: " . $e->getMessage());
            $this->view('dashboard/expense_create', [
                'title' => 'Nouvelle Dépense',
                'csrfToken' => $csrfToken,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create()
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            error_log(print_r($data, true));
            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            if (isset($data['expenses']) && is_array($data['expenses'])) {

                $createdExpenses = [];
                $errors = [];

                foreach ($data['expenses'] as $index => $expenseData) {
                    // Ajouter l'ID utilisateur à chaque dépense
                    $expenseData['user_id'] = $_SESSION['user_id'];
                    $expenseData['budget_id'] = BUDGET->id;

                    try {
                        // Validation pour chaque dépense
                        $this->validateExpenseData($expenseData);

                        // Création de la dépense
                        $expense = Expense::create($expenseData);
                        $createdExpenses[] = $expense;
                        error_log("✅ Dépense #{$index} créée avec succès (ID: {$expense->id})");
                    } catch (\Exception $e) {
                        $errorMessage = "Erreur à l'index $index: " . $e->getMessage();
                        $errors[] = $errorMessage;
                        error_log("❌ " . $errorMessage);
                        error_log("Stack trace: " . $e->getTraceAsString());
                    }
                }
                return $this->jsonResponse([
                    'success' => count($createdExpenses) > 0,
                    'message' => count($errors) > 0 ? 'Création partielle avec erreurs' : 'Dépenses créées avec succès',
                    'created_count' => count($createdExpenses),
                    'expenses' => $createdExpenses,
                    'errors' => $errors
                ]);
            } else {
                // Traitement d'une seule dépense (code existant)
                $data['user_id'] = $_SESSION['user_id'];
                $this->validateExpenseData($data);
                $expense = Expense::create($data);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Dépense créée avec succès',
                    'expense' => $expense
                ], 200);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Vérification que la dépense appartient à l'utilisateur
            $expense = Expense::findById($id);

            if (!$expense) {
                return $this->jsonResponse(['success' => false, 'message' => 'Dépense non trouvée'], 404);
            }
            error_log(print_r($data, true));
            $updated = Expense::update($id, $data);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense mise à jour avec succès',
                'expense' => $updated
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            // Vérification que la dépense appartient à l'utilisateur
            $expense = Expense::findById($id);
            if (!$expense || $expense->user_id !== $_SESSION['user_id']) {
                return $this->jsonResponse(['success' => false, 'message' => 'Dépense non trouvée'], 404);
            }

            Expense::delete($id);
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsPaid($id)
    {
        try {
            // Vérification que la dépense appartient à l'utilisateur
            $expense = Expense::findById($id);
            error_log(print_r($expense, true));
            if (!$expense) {
                return $this->jsonResponse(['success' => false, 'message' => 'Dépense non trouvée'], 404);
            }

            $updated = Expense::markAsPaid($id, $_SESSION['user_id']);
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Dépense marquée comme payée',
                'expense' => $updated
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function list()
    {
        try {
            // Récupérer l'ID de l'utilisateur connecté
            $userId = $_SESSION['user_id'];

            // Récupérer le budget actif
            $activeBudget = Budget::getActiveBudget($userId);

            if (!$activeBudget) {
                throw new \Exception("Aucun budget actif trouvé");
            }

            // Récupérer les dépenses de l'utilisateur pour le budget actif
            $expenses = Expense::getExpensesByUser($activeBudget->id, $userId);

            // Récupérer les catégories pour le filtre
            $categories = Categorie::getDefaultCategories();
            error_log(print_r($categories, true));

            // Calculer les statistiques
            $stats = [
                'total' => 0,
                'pending' => 0,
                'paid' => 0,
                'categories' => []
            ];

            foreach ($expenses as $expense) {
                error_log(print_r($expense, true));
                $stats['total'] += $expense->amount;
                if ($expense->status === 'pending') {
                    $stats['pending'] += $expense->amount;
                } else {
                    $stats['paid'] += $expense->amount;
                }
                if (!isset($stats['categories'][$expense->categorie_id])) {
                    $stats['categories'][$expense->categorie_id] = 0;
                }
                $stats['categories'][$expense->categorie_id]++;
            }

            // Afficher la vue avec les données
            $this->view('dashboard/expense_list', [
                'title' => 'Liste des dépenses',
                'currentPage' => 'expenses',
                'expenses' => $expenses,
                'categories' => $categories,
                'stats' => $stats,
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            // Log l'erreur
            error_log("Erreur lors de l'affichage de la liste des dépenses: " . $e->getMessage());

            // Rediriger vers le tableau de bord avec un message d'erreur
            $_SESSION['error'] = "Une erreur est survenue lors du chargement des dépenses: " . $e->getMessage();

        }
    }

    public function listPaginated()
    {
        try {
            // Récupérer l'ID de l'utilisateur connecté
            $userId = $_SESSION['user_id'];

            // Récupérer le budget actif
            $activeBudget = Budget::getActiveBudget($userId);
            if (!$activeBudget) {
                throw new \Exception("Aucun budget actif trouvé");
            }

            // Récupérer le numéro de page depuis la requête
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

            // Récupérer les dépenses paginées
            $paginatedExpenses = Expense::getPaginatedExpensesByUser($activeBudget->id, $userId, $page);

            $categories = Categorie::getDefaultCategories();
            // Calculer les statistiques pour les dépenses de la page
            $stats = [
                'total' => 0,
                'paid' => 0,
                'pending' => 0,
                'categories' => []
            ];

            foreach ($paginatedExpenses['expenses'] as $expense) {
                $stats['total'] += $expense->amount;
                if ($expense->status === Expense::STATUS_PAID) {
                    $stats['paid'] += $expense->amount;
                } else {
                    $stats['pending'] += $expense->amount;
                }
            }
            $stats['categories'] = $categories;

            // Afficher la vue avec les données
            $this->view('dashboard/expense_list', [
                'title' => 'Liste des dépenses',
                'categories' => $categories,
                'currentPage' => $paginatedExpenses['current_page'],
                'lastPage' => $paginatedExpenses['last_page'],
                'nextPage' => $paginatedExpenses['next_page'],
                'previousPage' => $paginatedExpenses['previous_page'],
                'expenses' => $paginatedExpenses['expenses'],
                'stats' => $stats,
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage de la liste des dépenses: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors du chargement des dépenses: " . $e->getMessage();
        }
    }

    // Méthode utilitaire pour formater les montants
    private function formatAmount($amount)
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    private function validateExpenseData($data)
    {
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception('Le montant doit être un nombre positif');
        }
        if (empty($data['description'])) {
            throw new \Exception('La description est requise');
        }
        if (empty($data['category_type'])) {
            throw new \Exception('La catégorie est requise');
        }
        if (empty($data['budget_id'])) {
            throw new \Exception('Le budget associé est requis');
        }
    }
}
