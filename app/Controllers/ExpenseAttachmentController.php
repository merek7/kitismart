<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ExpenseAttachment;
use App\Models\Expense;

class ExpenseAttachmentController extends Controller
{
    public function __construct()
    {
        // Accepter les utilisateurs authentifiés OU les invités authentifiés
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['guest_authenticated'])) {
            $this->redirect('/login');
            exit;
        }
    }

    /**
     * Uploader une pièce jointe
     */
    public function upload()
    {
        try {
            if (!isset($_FILES['attachment']) || !isset($_POST['expense_id'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Fichier ou dépense manquant'
                ], 400);
            }

            $expenseId = (int)$_POST['expense_id'];

            // Récupérer l'ID utilisateur (user authentifié OU guest)
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $isGuest = isset($_SESSION['guest_authenticated']) && $_SESSION['guest_authenticated'] === true;

            // Vérifier que la dépense appartient à l'utilisateur
            $expense = Expense::findById($expenseId);
            if (!$expense) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dépense non trouvée'
                ], 404);
            }

            // Vérifier que l'utilisateur a accès au budget
            $hasAccess = false;
            $uploaderUserId = null;

            if ($isGuest) {
                // Pour un invité, vérifier qu'il a accès au budget via budgetshare
                $guestBudgetId = (int)$_SESSION['guest_budget_id'];
                if ($expense->budget_id == $guestBudgetId) {
                    $hasAccess = true;
                    // Pour un guest, utiliser l'ID du propriétaire du budget pour le stockage
                    $budget = \RedBeanPHP\R::load('budget', $guestBudgetId);
                    $uploaderUserId = (int)$budget->user_id;
                }
            } else {
                // Pour un utilisateur normal, vérifier propriétaire OU invité
                $budget = \App\Models\Budget::findById($expense->budget_id, $userId);

                if ($budget) {
                    // Utilisateur est propriétaire
                    $hasAccess = true;
                    $uploaderUserId = $userId;
                } else {
                    // Vérifier si l'utilisateur est invité sur ce budget
                    $sharedBudget = \RedBeanPHP\R::findOne('shared_budgets', 'budget_id = ? AND guest_user_id = ?',
                        [$expense->budget_id, $userId]);
                    if ($sharedBudget) {
                        $hasAccess = true;
                        $uploaderUserId = $userId;
                    }
                }
            }

            if (!$hasAccess) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            // Sauvegarder le fichier
            $fileData = ExpenseAttachment::saveFile($_FILES['attachment'], $uploaderUserId, $expenseId);

            // Créer l'entrée en base
            $attachment = ExpenseAttachment::create([
                'expense_id' => $expenseId,
                'filename' => $fileData['filename'],
                'original_filename' => $fileData['original_filename'],
                'file_path' => $fileData['file_path'],
                'file_size' => $fileData['file_size'],
                'mime_type' => $fileData['mime_type'],
                'uploaded_by' => $uploaderUserId
            ]);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Pièce jointe ajoutée avec succès',
                'attachment' => [
                    'id' => $attachment->id,
                    'filename' => $attachment->original_filename,
                    'size' => ExpenseAttachment::formatFileSize($attachment->file_size),
                    'icon' => ExpenseAttachment::getFileIcon($attachment->mime_type),
                    'url' => '/' . $attachment->file_path
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les pièces jointes d'une dépense
     */
    public function list($id)
    {
        try {
            // Récupérer l'ID utilisateur (user authentifié OU guest)
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $isGuest = isset($_SESSION['guest_authenticated']) && $_SESSION['guest_authenticated'] === true;
            $expenseId = (int)$id;

            // Vérifier que la dépense appartient à l'utilisateur
            $expense = Expense::findById($expenseId);
            if (!$expense) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dépense non trouvée'
                ], 404);
            }

            // Vérifier que l'utilisateur a accès au budget
            $hasAccess = false;

            if ($isGuest) {
                // Pour un invité, vérifier qu'il a accès au budget via budgetshare
                $guestBudgetId = (int)$_SESSION['guest_budget_id'];
                if ($expense->budget_id == $guestBudgetId) {
                    $hasAccess = true;
                }
            } else {
                // Pour un utilisateur normal, vérifier propriétaire OU invité
                $budget = \App\Models\Budget::findById($expense->budget_id, $userId);

                if ($budget) {
                    // Utilisateur est propriétaire
                    $hasAccess = true;
                } else {
                    // Vérifier si l'utilisateur est invité sur ce budget
                    $sharedBudget = \RedBeanPHP\R::findOne('shared_budgets', 'budget_id = ? AND guest_user_id = ?',
                        [$expense->budget_id, $userId]);
                    if ($sharedBudget) {
                        $hasAccess = true;
                    }
                }
            }

            if (!$hasAccess) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            // Récupérer les pièces jointes
            $attachments = ExpenseAttachment::findByExpense($expenseId);

            $result = [];
            foreach ($attachments as $attachment) {
                $result[] = [
                    'id' => $attachment->id,
                    'file_name' => $attachment->original_filename,
                    'file_path' => $attachment->file_path,
                    'file_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'uploaded_at' => date('d/m/Y H:i', strtotime($attachment->uploaded_at))
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'attachments' => $result
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une pièce jointe
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Méthode non autorisée'
                ], 405);
            }

            $userId = (int)$_SESSION['user_id'];
            $id = (int)$id;

            $deleted = ExpenseAttachment::delete($id, $userId);

            if (!$deleted) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Pièce jointe non trouvée ou non autorisée'
                ], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Pièce jointe supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger une pièce jointe
     */
    public function download($id)
    {
        try {
            // Récupérer l'ID utilisateur (user authentifié OU guest)
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $isGuest = isset($_SESSION['guest_authenticated']) && $_SESSION['guest_authenticated'] === true;
            $id = (int)$id;

            $attachment = ExpenseAttachment::findById($id);
            if (!$attachment->id) {
                header('HTTP/1.0 404 Not Found');
                echo 'Pièce jointe non trouvée';
                return;
            }

            // Vérifier les permissions
            $expense = Expense::findById($attachment->expense_id);
            if (!$expense) {
                header('HTTP/1.0 404 Not Found');
                echo 'Dépense non trouvée';
                return;
            }

            // Vérifier que l'utilisateur a accès au budget
            $hasAccess = false;

            if ($isGuest) {
                // Pour un invité, vérifier qu'il a accès au budget via budgetshare
                $guestBudgetId = (int)$_SESSION['guest_budget_id'];
                if ($expense->budget_id == $guestBudgetId) {
                    $hasAccess = true;
                }
            } else {
                // Pour un utilisateur normal, vérifier propriétaire OU invité
                $budget = \App\Models\Budget::findById($expense->budget_id, $userId);

                if ($budget) {
                    // Utilisateur est propriétaire
                    $hasAccess = true;
                } else {
                    // Vérifier si l'utilisateur est invité sur ce budget
                    $sharedBudget = \RedBeanPHP\R::findOne('shared_budgets', 'budget_id = ? AND guest_user_id = ?',
                        [$expense->budget_id, $userId]);
                    if ($sharedBudget) {
                        $hasAccess = true;
                    }
                }
            }

            if (!$hasAccess) {
                header('HTTP/1.0 403 Forbidden');
                echo 'Non autorisé';
                return;
            }

            // Télécharger le fichier
            $filePath = __DIR__ . '/../../public/' . $attachment->file_path;

            if (!file_exists($filePath)) {
                header('HTTP/1.0 404 Not Found');
                echo 'Fichier non trouvé';
                return;
            }

            header('Content-Type: ' . $attachment->mime_type);
            header('Content-Disposition: attachment; filename="' . $attachment->original_filename . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        } catch (\Exception $e) {
            header('HTTP/1.0 500 Internal Server Error');
            echo $e->getMessage();
        }
    }
}
