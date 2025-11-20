<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CustomCategory;
use App\Utils\Csrf;
use App\Exceptions\TokenInvalidOrExpiredException;

class CategoryController extends Controller
{
    /**
     * Afficher la liste des catégories personnalisées
     */
    public function index()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $categories = CustomCategory::findByUser($userId);
            $categoriesCount = CustomCategory::countByUser($userId);

            $this->view('dashboard/categories_list', [
                'title' => 'Mes Catégories',
                'currentPage' => 'categories',
                'categories' => $categories,
                'categoriesCount' => $categoriesCount,
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage des catégories: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors du chargement des catégories";
            $this->redirect('/dashboard');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function showCreateForm()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $csrfToken = Csrf::generateToken();
            $availableIcons = CustomCategory::getAvailableIcons();
            $availableColors = CustomCategory::getAvailableColors();

            $this->view('dashboard/category_create', [
                'title' => 'Nouvelle Catégorie',
                'currentPage' => 'categories',
                'csrfToken' => $csrfToken,
                'availableIcons' => $availableIcons,
                'availableColors' => $availableColors,
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage du formulaire de catégorie: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue";
            $this->redirect('/categories');
        }
    }

    /**
     * Créer une nouvelle catégorie
     */
    public function create()
    {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            // Validation des données
            $this->validateCategoryData($data);

            $userId = (int)$_SESSION['user_id'];

            // Vérifier que le nom n'existe pas déjà
            if (CustomCategory::existsByName($data['name'], $userId)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Une catégorie avec ce nom existe déjà'
                ], 400);
            }

            // Ajouter l'ID utilisateur
            $data['user_id'] = $userId;

            // Créer la catégorie
            $category = CustomCategory::create($data);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'category' => $category
            ], 201);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide ou expiré'
            ], 400);
        } catch (\Exception $e) {
            error_log("Erreur création catégorie: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $category = CustomCategory::findById((int)$id, $userId);

            if (!$category) {
                $_SESSION['error'] = "Catégorie non trouvée";
                $this->redirect('/categories');
                return;
            }

            $csrfToken = Csrf::generateToken();
            $availableIcons = CustomCategory::getAvailableIcons();
            $availableColors = CustomCategory::getAvailableColors();

            $this->view('dashboard/category_edit', [
                'title' => 'Modifier la Catégorie',
                'currentPage' => 'categories',
                'category' => $category,
                'csrfToken' => $csrfToken,
                'availableIcons' => $availableIcons,
                'availableColors' => $availableColors,
                'layout' => 'dashboard'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur lors de l'affichage du formulaire d'édition: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue";
            $this->redirect('/categories');
        }
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)$_SESSION['user_id'];

            // Validation des données
            $this->validateCategoryData($data, false);

            // Vérifier que le nom n'existe pas déjà (sauf pour cette catégorie)
            if (isset($data['name']) && CustomCategory::existsByName($data['name'], $userId, (int)$id)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Une autre catégorie avec ce nom existe déjà'
                ], 400);
            }

            // Mettre à jour
            $category = CustomCategory::update((int)$id, $data, $userId);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'category' => $category
            ]);

        } catch (\Exception $e) {
            error_log("Erreur mise à jour catégorie: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une catégorie
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            CustomCategory::delete((int)$id, $userId);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur suppression catégorie: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les catégories en JSON (pour AJAX)
     */
    public function getAll()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $categories = CustomCategory::findByUser($userId);

            return $this->jsonResponse([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider les données de catégorie
     */
    private function validateCategoryData(array $data, bool $isCreate = true)
    {
        if ($isCreate) {
            if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
                throw new \Exception('Le nom de la catégorie doit contenir au moins 2 caractères');
            }
        } else {
            if (isset($data['name']) && strlen(trim($data['name'])) < 2) {
                throw new \Exception('Le nom de la catégorie doit contenir au moins 2 caractères');
            }
        }

        if (isset($data['color']) && !preg_match('/^#[0-9A-F]{6}$/i', $data['color'])) {
            throw new \Exception('La couleur doit être au format hexadécimal (ex: #0d9488)');
        }

        if (isset($data['icon']) && empty(trim($data['icon']))) {
            throw new \Exception('L\'icône est requise');
        }
    }
}
