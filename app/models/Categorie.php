<?php

namespace App\Models;

use RedBeanPHP\R;

class Categorie {

    const TYPE_FIXE = 'fixe';
    const TYPE_DIVER = 'diver';
    const TYPE_EPARGNE = 'epargne';

    public static function getDefaultCategories() {
        return [
            self::TYPE_FIXE,
            self::TYPE_DIVER,
            self::TYPE_EPARGNE
        ];
    }

    public static function create(array $data) {
        try {
            // Vérifier si le type est valide
            if (!in_array($data['type'], self::getDefaultCategories())) {
                throw new \Exception('Type de catégorie invalide');
            }

            $categorie = R::dispense('categorie');
            $categorie->import([
                'type' => $data['type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'budget_id' => $data['budget_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            R::store($categorie);
            return $categorie;
        } catch(\Exception $e) {
            throw new \Exception('Erreur lors de la création de la catégorie: ' . $e->getMessage());
        }
    }

    public static function update($id, array $data){
        try{
            $categorie = R::load('categorie', $id);
            $categorie->import([
                'name'=>$data['name'],
                'description'=>$data['description'],
                'expense_id'=>$data['expense_id'],
                'updated_at'=>date('Y-m-d H:i:s')
            ]);

            R::store($categorie);
            return $categorie;
        }catch(\Exception $e){
            throw new \Exception('Erreur lors de la mise à jour de la catégorie: ' . $e->getMessage());
        }
    }

    public static function delete($id){
        try{
            $categorie = R::load('categorie', $id);
            R::trash($categorie);
            return true;
        }catch(\Exception $e){
            throw new \Exception('Erreur lors de la suppression de la catégorie: ' . $e->getMessage());
        }
    }

    public static function findByType($type) {
        if (!in_array($type, self::getDefaultCategories())) {
            throw new \Exception('Type de catégorie invalide');
        }
        return R::findOne('categorie', 'type = ?', [$type]);
    }

    public static function findByBudget($budgetId) {
        return R::find('categorie', 'budget_id = ?', [$budgetId]);
    }

    public static function findById($id) {
        return R::load('categorie', $id);
    }

    public static function findAll(){
        return R::findAll('categorie');
    }
}