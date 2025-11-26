<?php

namespace App\Controller\ApiController;

use App\Entity\Client;
use App\Entity\ClientItem;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/items')]
class ItemController extends AbstractController
{
    // Constante pour la taille max (2 Mo en octets)
    private const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;

    #[Route('/add', name: 'api_items_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof Client) {
            return new JsonResponse(['error' => 'User must be a client'], Response::HTTP_FORBIDDEN);
        }

        // --- 1. Récupération des données ---
        $jsonString = $request->request->get('data');
        $uploadedFile = $request->files->get('image');

        if (!$jsonString) {
            return new JsonResponse(['error' => 'Missing "data" field'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // --- 2. Validation Logique Métier (Nom & Catégorie) ---
        if (!isset($data['name']) || !isset($data['category'])) {
            return new JsonResponse(['error' => 'name and category are required'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);
        if (empty($name)) {
            return new JsonResponse(['error' => 'name cannot be empty'], Response::HTTP_BAD_REQUEST);
        }

        $categoryInput = $data['category'];
        $category = is_numeric($categoryInput)
            ? $categoryRepository->find((int) $categoryInput)
            : $categoryRepository->findOneBy(['name' => $categoryInput]);

        if (!$category) {
            return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        // --- 3. Traitement de l'Image (Optionnelle mais stricte) ---
        $newFilename = null;

        if ($uploadedFile) {
            // >>>> CORRECTION ICI : Vérifier si l'upload s'est bien passé <<<<
            // Si PHP a rejeté le fichier (trop gros), getError() ne sera pas 0 (UPLOAD_ERR_OK)
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                // On gère spécifiquement le cas "Fichier trop lourd côté serveur"
                if ($uploadedFile->getError() === UPLOAD_ERR_INI_SIZE || $uploadedFile->getError() === UPLOAD_ERR_FORM_SIZE) {
                    return new JsonResponse([
                        'error' => 'File too large (server limit exceeded).'
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Autres erreurs d'upload génériques
                return new JsonResponse([
                    'error' => 'Upload failed with error code: ' . $uploadedFile->getError()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            // >>>> FIN CORRECTION <<<<

            // A. Vérification de la taille (Validation "Métier")
            // (Ce code s'exécutera seulement si le fichier est passé sous la barre du php.ini)
            if ($uploadedFile->getSize() > self::MAX_UPLOAD_SIZE) {
                return new JsonResponse([
                    'error' => 'File too large. Maximum size allowed is 2MB.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // B. Vérification des types MIME
            // MAINTENANT c'est sécurisé, car on sait que le fichier existe physiquement
            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif'
            ];

            // Le crash arrivait ici avant la correction :
            if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
                return new JsonResponse([
                    'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'
                ], Response::HTTP_BAD_REQUEST);
            }

            // C. Renommage et Upload
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

            try {
                $uploadedFile->move(
                    $this->getParameter('items_uploads_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Failed to upload image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // --- 4. Enregistrement en base ---
        $clientItem = new ClientItem();
        $clientItem->setName($name);
        $clientItem->setCategory($category);
        $clientItem->setClient($user);
        $clientItem->setImg($newFilename); // Null ou nom du fichier

        $entityManager->persist($clientItem);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Item created successfully',
                'item' => [
                    'id' => $clientItem->getId(),
                    'name' => $clientItem->getName(),
                    'category' => $category->getName(),
                    'img' => $clientItem->getImg(),
                ]
            ],
            Response::HTTP_CREATED
        );
    }
}