<?php

namespace App\Models;

use RedBeanPHP\R as R;

class ExpenseAttachment
{
    // Types MIME autorisés
    public const ALLOWED_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    // Taille max : 5MB
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    // Qualité de compression JPEG (0-100)
    public const JPEG_QUALITY = 80;

    // Dimension max pour les images (largeur ou hauteur)
    public const MAX_IMAGE_DIMENSION = 1920;

    /**
     * Créer une nouvelle pièce jointe
     */
    public static function create(array $data)
    {
        $attachment = R::dispense('expenseattachment');
        $attachment->expense_id = $data['expense_id'];
        $attachment->filename = $data['filename'];
        $attachment->original_filename = $data['original_filename'];
        $attachment->file_path = $data['file_path'];
        $attachment->file_size = $data['file_size'];
        $attachment->mime_type = $data['mime_type'];
        $attachment->uploaded_by = $data['uploaded_by'];
        $attachment->uploaded_at = date('Y-m-d H:i:s');

        R::store($attachment);
        return $attachment;
    }

    /**
     * Récupérer toutes les pièces jointes d'une dépense
     */
    public static function findByExpense(int $expenseId): array
    {
        return R::find('expenseattachment', 'expense_id = ? ORDER BY uploaded_at DESC', [$expenseId]);
    }

    /**
     * Supprimer une pièce jointe
     */
    public static function delete(int $id, int $userId): bool
    {
        return self::deleteWithGuestSupport($id, $userId, false, null);
    }

    /**
     * Supprimer une pièce jointe avec support des guests
     */
    public static function deleteWithGuestSupport(int $id, ?int $userId, bool $isGuest = false, ?int $guestBudgetId = null): bool
    {
        $attachment = R::load('expenseattachment', $id);

        if (!$attachment->id) {
            return false;
        }

        // Vérifier que l'utilisateur a le droit (vérifie via la dépense)
        $expense = R::load('expense', $attachment->expense_id);
        if (!$expense->id) {
            return false;
        }

        // Vérifier que l'utilisateur a accès au budget (propriétaire OU invité)
        $budget = R::load('budget', $expense->budget_id);
        if (!$budget->id) {
            return false;
        }

        $hasAccess = false;

        if ($isGuest && $guestBudgetId) {
            // Pour un guest, vérifier qu'il a accès au budget
            if ($expense->budget_id == $guestBudgetId) {
                $hasAccess = true;
            }
        } elseif ($userId) {
            if ($budget->user_id == $userId) {
                // Utilisateur est propriétaire
                $hasAccess = true;
            } else {
                // Vérifier si l'utilisateur est invité sur ce budget
                $sharedBudget = R::findOne('shared_budgets', 'budget_id = ? AND guest_user_id = ?',
                    [$expense->budget_id, $userId]);
                if ($sharedBudget) {
                    $hasAccess = true;
                }
            }
        }

        if (!$hasAccess) {
            return false;
        }

        // Supprimer le fichier physique
        $filePath = __DIR__ . '/../../public/' . $attachment->file_path;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Supprimer l'entrée en base
        R::trash($attachment);
        return true;
    }

    /**
     * Supprimer toutes les pièces jointes d'une dépense (pour suppression en cascade)
     */
    public static function deleteByExpense(int $expenseId): void
    {
        $attachments = self::findByExpense($expenseId);
        
        foreach ($attachments as $attachment) {
            // Supprimer le fichier physique
            $filePath = __DIR__ . '/../../public/' . $attachment->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Supprimer l'entrée en base
            R::trash($attachment);
        }
    }

    /**
     * Valider un fichier uploadé
     */
    public static function validateFile(array $file): array
    {
        $errors = [];

        // Vérifier qu'il n'y a pas d'erreur d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors de l\'upload du fichier';
            return $errors;
        }

        // Vérifier la taille
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'Le fichier est trop volumineux (max 5 MB)';
        }

        // Vérifier le type MIME
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } else {
            // Fallback si fileinfo n'est pas disponible
            $mimeType = $file['type'];
        }

        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            $errors[] = 'Type de fichier non autorisé';
        }

        return $errors;
    }

    /**
     * Sauvegarder un fichier uploadé
     */
    public static function saveFile(array $file, int $userId, int $expenseId): array
    {
        // Valider le fichier
        $errors = self::validateFile($file);
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        // Créer le répertoire si nécessaire
        $uploadDir = __DIR__ . '/../../public/uploads/expenses/' . $userId . '/' . $expenseId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déterminer le type MIME avant le déplacement
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } else {
            $mimeType = $file['type'];
        }

        // Pour les images, on va compresser en JPEG (sauf PNG avec transparence ou GIF)
        $isCompressibleImage = in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
        
        // Générer un nom de fichier unique
        $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Compresser les images
        if ($isCompressibleImage && function_exists('imagecreatefromstring')) {
            // On garde le format JPEG pour la compression (sauf si c'est un PNG qu'on veut garder)
            $extension = ($originalExtension === 'png') ? 'png' : 'jpg';
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filePath = $uploadDir . '/' . $filename;
            
            $compressed = self::compressImage($file['tmp_name'], $filePath, $mimeType);
            
            if (!$compressed) {
                // Fallback: copier le fichier original si la compression échoue
                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    throw new \Exception('Erreur lors de la sauvegarde du fichier');
                }
            }
            
            // Mettre à jour le type MIME après compression
            $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';
        } else {
            // Fichier non-image ou compression non disponible
            $extension = $originalExtension;
            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filePath = $uploadDir . '/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \Exception('Erreur lors de la sauvegarde du fichier');
            }
        }

        // Obtenir la taille finale du fichier
        $fileSize = filesize($filePath);

        // Retourner les métadonnées
        return [
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_path' => 'uploads/expenses/' . $userId . '/' . $expenseId . '/' . $filename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Compresser une image
     */
    private static function compressImage(string $sourcePath, string $destPath, string $mimeType): bool
    {
        try {
            // Charger l'image source
            $imageData = file_get_contents($sourcePath);
            $sourceImage = imagecreatefromstring($imageData);
            
            if (!$sourceImage) {
                return false;
            }

            // Obtenir les dimensions originales
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calculer les nouvelles dimensions si nécessaire
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;

            if ($originalWidth > self::MAX_IMAGE_DIMENSION || $originalHeight > self::MAX_IMAGE_DIMENSION) {
                if ($originalWidth > $originalHeight) {
                    $newWidth = self::MAX_IMAGE_DIMENSION;
                    $newHeight = (int)($originalHeight * (self::MAX_IMAGE_DIMENSION / $originalWidth));
                } else {
                    $newHeight = self::MAX_IMAGE_DIMENSION;
                    $newWidth = (int)($originalWidth * (self::MAX_IMAGE_DIMENSION / $originalHeight));
                }
            }

            // Redimensionner si nécessaire
            if ($newWidth !== $originalWidth || $newHeight !== $originalHeight) {
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Préserver la transparence pour PNG
                if (pathinfo($destPath, PATHINFO_EXTENSION) === 'png') {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                    $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                    imagefill($resizedImage, 0, 0, $transparent);
                }
                
                imagecopyresampled(
                    $resizedImage, $sourceImage,
                    0, 0, 0, 0,
                    $newWidth, $newHeight,
                    $originalWidth, $originalHeight
                );
                
                imagedestroy($sourceImage);
                $sourceImage = $resizedImage;
            }

            // Sauvegarder l'image compressée
            $extension = pathinfo($destPath, PATHINFO_EXTENSION);
            
            if ($extension === 'png') {
                // PNG: compression niveau 6 (0-9, 9 = max compression)
                $result = imagepng($sourceImage, $destPath, 6);
            } else {
                // JPEG: qualité définie par constante
                $result = imagejpeg($sourceImage, $destPath, self::JPEG_QUALITY);
            }

            imagedestroy($sourceImage);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Erreur compression image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer une pièce jointe par ID
     */
    public static function findById(int $id)
    {
        return R::load('expenseattachment', $id);
    }

    /**
     * Formater la taille en unités lisibles
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtenir l'icône Font Awesome selon le type MIME
     */
    public static function getFileIcon(string $mimeType): string
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'fa-image';
        } elseif ($mimeType === 'application/pdf') {
            return 'fa-file-pdf';
        } elseif (strpos($mimeType, 'word') !== false) {
            return 'fa-file-word';
        } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'spreadsheet') !== false) {
            return 'fa-file-excel';
        }

        return 'fa-file';
    }
}
