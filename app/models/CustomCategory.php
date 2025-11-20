<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class CustomCategory {

    /**
     * Créer une catégorie personnalisée
     */
    public static function create(array $data) {
        try {
            $category = R::dispense('customcategory');
            $category->import([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'icon' => $data['icon'] ?? 'fa-tag',
                'color' => $data['color'] ?? '#0d9488',
                'description' => $data['description'] ?? null,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            R::store($category);
            return $category;
        } catch(\Exception $e) {
            throw new \Exception('Erreur lors de la création de la catégorie personnalisée: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour une catégorie personnalisée
     */
    public static function update(int $id, array $data, int $userId) {
        try {
            $category = R::load('customcategory', $id);

            // Vérifier que la catégorie appartient à l'utilisateur
            if (!$category->id || $category->user_id != $userId) {
                throw new \Exception('Catégorie non trouvée ou accès non autorisé');
            }

            $category->import([
                'name' => $data['name'] ?? $category->name,
                'icon' => $data['icon'] ?? $category->icon,
                'color' => $data['color'] ?? $category->color,
                'description' => $data['description'] ?? $category->description,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            R::store($category);
            return $category;
        } catch(\Exception $e) {
            throw new \Exception('Erreur lors de la mise à jour de la catégorie: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete d'une catégorie
     */
    public static function delete(int $id, int $userId) {
        try {
            $category = R::load('customcategory', $id);

            if (!$category->id || $category->user_id != $userId) {
                throw new \Exception('Catégorie non trouvée ou accès non autorisé');
            }

            $category->is_active = 0;
            $category->updated_at = date('Y-m-d H:i:s');
            R::store($category);

            return true;
        } catch(\Exception $e) {
            throw new \Exception('Erreur lors de la suppression de la catégorie: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer toutes les catégories actives d'un utilisateur
     */
    public static function findByUser(int $userId) {
        return R::find('customcategory', 'user_id = ? AND is_active = 1 ORDER BY created_at DESC', [$userId]);
    }

    /**
     * Récupérer une catégorie par ID
     */
    public static function findById(int $id, int $userId) {
        return R::findOne('customcategory', 'id = ? AND user_id = ? AND is_active = 1', [$id, $userId]);
    }

    /**
     * Vérifier si une catégorie existe déjà pour cet utilisateur
     */
    public static function existsByName(string $name, int $userId, ?int $excludeId = null) {
        if ($excludeId) {
            return R::findOne('customcategory', 'name = ? AND user_id = ? AND is_active = 1 AND id != ?',
                [$name, $userId, $excludeId]);
        }
        return R::findOne('customcategory', 'name = ? AND user_id = ? AND is_active = 1', [$name, $userId]);
    }

    /**
     * Compter les catégories d'un utilisateur
     */
    public static function countByUser(int $userId): int {
        return R::count('customcategory', 'user_id = ? AND is_active = 1', [$userId]);
    }

    /**
     * Récupérer les icônes disponibles
     */
    public static function getAvailableIcons(): array {
        return [
            'fa-home' => 'Maison',
            'fa-car' => 'Transport',
            'fa-utensils' => 'Nourriture',
            'fa-shopping-cart' => 'Courses',
            'fa-heart' => 'Santé',
            'fa-graduation-cap' => 'Éducation',
            'fa-gamepad' => 'Loisirs',
            'fa-gift' => 'Cadeaux',
            'fa-phone' => 'Téléphone',
            'fa-bolt' => 'Électricité',
            'fa-tint' => 'Eau',
            'fa-wifi' => 'Internet',
            'fa-shirt' => 'Vêtements',
            'fa-book' => 'Livres',
            'fa-film' => 'Divertissement',
            'fa-dumbbell' => 'Sport',
            'fa-paw' => 'Animaux',
            'fa-baby' => 'Enfants',
            'fa-briefcase' => 'Travail',
            'fa-tag' => 'Autre'
        ];
    }

    /**
     * Récupérer les couleurs prédéfinies
     */
    public static function getAvailableColors(): array {
        return [
            '#0d9488' => 'Teal',
            '#14b8a6' => 'Light Teal',
            '#facc15' => 'Yellow',
            '#f59e0b' => 'Orange',
            '#ef4444' => 'Red',
            '#ec4899' => 'Pink',
            '#8b5cf6' => 'Purple',
            '#3b82f6' => 'Blue',
            '#10b981' => 'Green',
            '#6b7280' => 'Gray'
        ];
    }
}
