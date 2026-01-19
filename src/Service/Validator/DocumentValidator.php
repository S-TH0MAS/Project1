<?php

namespace App\Service\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class DocumentValidator
{
    /**
     * Valide un document uploadé
     * 
     * @param UploadedFile|null $uploadedFile Le fichier à valider
     * @param array $allowedMimeTypes Les types MIME autorisés (ex: ['image/jpeg', 'application/pdf'])
     * @param int $maxSize Taille maximale en octets (ex: 10 * 1024 * 1024 pour 10 Mo)
     * @throws \Exception Si la validation échoue
     * @return void
     */
    public function validate(UploadedFile|null $uploadedFile, array $allowedMimeTypes, int $maxSize): void
    {
        if (!$uploadedFile) {
            throw new \Exception('Aucun fichier fourni');
        }

        // Vérifier les erreurs d'upload PHP
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $this->handleUploadError($uploadedFile->getError());
        }

        // Vérifier la taille du fichier
        if ($uploadedFile->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            throw new \Exception("Le fichier est trop volumineux. Taille maximale autorisée : {$maxSizeMB} Mo");
        }

        // Vérifier le type MIME
        $mimeType = $uploadedFile->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $allowedTypes = implode(', ', $allowedMimeTypes);
            throw new \Exception("Type de fichier non autorisé. Types autorisés : {$allowedTypes}");
        }
    }

    /**
     * Gère les erreurs d'upload PHP
     * 
     * @param int $errorCode Code d'erreur PHP
     * @throws \Exception
     */
    private function handleUploadError(int $errorCode): void
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \Exception('Le fichier dépasse la taille maximale autorisée par le serveur');
            
            case UPLOAD_ERR_PARTIAL:
                throw new \Exception('Le fichier n\'a été que partiellement uploadé');
            
            case UPLOAD_ERR_NO_FILE:
                throw new \Exception('Aucun fichier n\'a été uploadé');
            
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new \Exception('Dossier temporaire manquant');
            
            case UPLOAD_ERR_CANT_WRITE:
                throw new \Exception('Échec de l\'écriture du fichier sur le disque');
            
            case UPLOAD_ERR_EXTENSION:
                throw new \Exception('Une extension PHP a arrêté l\'upload du fichier');
            
            default:
                throw new \Exception('Erreur lors de l\'upload du fichier (code : ' . $errorCode . ')');
        }
    }
}
