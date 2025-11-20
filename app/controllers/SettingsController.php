<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Utils\Csrf;
use RedBeanPHP\R;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index()
    {
        $user = User::findById((int)$_SESSION['user_id']);

        if (!$user) {
            $_SESSION['error'] = "Utilisateur non trouvé";
            return $this->redirect('/dashboard');
        }

        $this->render('dashboard/settings', [
            'user' => $user,
            'csrf_token' => Csrf::generateToken(),
            'currentPage' => 'settings'
        ]);
    }

    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/settings');
        }

        // Vérification CSRF
        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "Token de sécurité invalide";
            return $this->redirect('/settings');
        }

        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validation
        if (empty($nom) || empty($email)) {
            $_SESSION['error'] = "Le nom et l'email sont obligatoires";
            return $this->redirect('/settings');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Email invalide";
            return $this->redirect('/settings');
        }

        $user = User::findById((int)$_SESSION['user_id']);

        if (!$user) {
            $_SESSION['error'] = "Utilisateur non trouvé";
            return $this->redirect('/settings');
        }

        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        if ($email !== $user->email) {
            $existingUser = R::findOne('users', 'email = ? AND id != ?', [$email, $user->id]);
            if ($existingUser) {
                $_SESSION['error'] = "Cet email est déjà utilisé";
                return $this->redirect('/settings');
            }
        }

        try {
            $user->nom = $nom;
            $user->email = $email;
            $user->updated_at = date('Y-m-d H:i:s');
            R::store($user);

            $_SESSION['user_name'] = $nom;
            $_SESSION['success'] = "Profil mis à jour avec succès";
        } catch (\Exception $e) {
            error_log("Erreur mise à jour profil: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour";
        }

        return $this->redirect('/settings');
    }

    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/settings');
        }

        // Vérification CSRF
        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "Token de sécurité invalide";
            return $this->redirect('/settings');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = "Tous les champs sont obligatoires";
            return $this->redirect('/settings');
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas";
            return $this->redirect('/settings');
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
            return $this->redirect('/settings');
        }

        $user = User::findById((int)$_SESSION['user_id']);

        if (!$user) {
            $_SESSION['error'] = "Utilisateur non trouvé";
            return $this->redirect('/settings');
        }

        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $user->password)) {
            $_SESSION['error'] = "Mot de passe actuel incorrect";
            return $this->redirect('/settings');
        }

        try {
            $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
            $user->updated_at = date('Y-m-d H:i:s');
            R::store($user);

            $_SESSION['success'] = "Mot de passe modifié avec succès";
        } catch (\Exception $e) {
            error_log("Erreur changement mot de passe: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors du changement de mot de passe";
        }

        return $this->redirect('/settings');
    }

    public function deleteAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/settings');
        }

        // Vérification CSRF
        if (!Csrf::verifyToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "Token de sécurité invalide";
            return $this->redirect('/settings');
        }

        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        if ($confirmation !== 'SUPPRIMER') {
            $_SESSION['error'] = "Veuillez taper 'SUPPRIMER' pour confirmer";
            return $this->redirect('/settings');
        }

        $user = User::findById((int)$_SESSION['user_id']);

        if (!$user) {
            $_SESSION['error'] = "Utilisateur non trouvé";
            return $this->redirect('/settings');
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $user->password)) {
            $_SESSION['error'] = "Mot de passe incorrect";
            return $this->redirect('/settings');
        }

        try {
            R::begin();

            // Supprimer toutes les données associées
            $budgets = R::find('budget', 'user_id = ?', [$user->id]);
            foreach ($budgets as $budget) {
                // Supprimer les dépenses
                $expenses = R::find('expense', 'budget_id = ?', [$budget->id]);
                R::trashAll($expenses);

                // Supprimer les catégories
                $categories = R::find('categorie', 'budget_id = ?', [$budget->id]);
                R::trashAll($categories);
            }
            R::trashAll($budgets);

            // Supprimer les audits
            $audits = R::find('useraudit', 'user_id = ?', [$user->id]);
            R::trashAll($audits);

            // Supprimer l'utilisateur
            R::trash($user);

            R::commit();

            // Détruire la session
            session_destroy();

            // Rediriger vers la page d'accueil
            header('Location: /?message=Compte supprimé avec succès');
            exit;
        } catch (\Exception $e) {
            R::rollback();
            error_log("Erreur suppression compte: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors de la suppression du compte";
            return $this->redirect('/settings');
        }
    }
}
