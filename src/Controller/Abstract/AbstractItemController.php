<?php

namespace App\Controller\Abstract;

use App\DTO\Api\Item\AddItemDto;
use App\DTO\Api\Item\UpdateItemDto;
use App\Entity\Item;
use App\Repository\CategoryRepository;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

abstract class AbstractItemController extends AbstractController
{
    use ApiResponseTrait;

    // Constante pour la taille max (2 Mo en octets)
    protected const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;

    public function add(
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();

        // Vérifier l'utilisateur selon le type de contrôleur
        $userValidation = $this->validateUser($user);
        if ($userValidation !== null) {
            return $userValidation;
        }

        $jsonString = $request->request->get('data');
        $uploadedFile = $request->files->get('image');

        if (!$jsonString) {
            return $this->jsonError(Response::HTTP_BAD_REQUEST, 'Bad Request', 'Missing "data" field');
        }

        try {
            $dto = $requestValidator->validate($jsonString, AddItemDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $categoryInput = $dto->getCategory();
        $category = is_numeric($categoryInput)
            ? $categoryRepository->find((int) $categoryInput)
            : $categoryRepository->findOneBy(['name' => $categoryInput]);

        if (!$category) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Category not found');
        }

        $newFilename = $this->handleFileUpload($uploadedFile, $slugger);
        if ($newFilename instanceof JsonResponse) {
            return $newFilename; // Erreur lors de l'upload
        }

        // Créer l'instance d'item appropriée
        $item = $this->createItemInstance();
        $item->setName($dto->getName());
        $item->setCategory($category);
        $item->setImg($newFilename); // Null ou nom du fichier

        // Configurer l'item selon le type (ClientItem ou Item)
        $this->configureItem($item, $user);

        $entityManager->persist($item);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Item created successfully',
                'item' => [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'category' => $category->getName(),
                    'img' => $item->getImg(),
                ]
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * Valide l'utilisateur selon le type de contrôleur
     * Retourne null si valide, sinon retourne une JsonResponse d'erreur
     */
    abstract protected function validateUser($user): ?JsonResponse;

    /**
     * Crée une instance d'item appropriée (Item ou ClientItem)
     */
    abstract protected function createItemInstance(): Item;

    /**
     * Configure l'item selon le type (ex: setClient pour ClientItem)
     */
    abstract protected function configureItem(Item $item, $user): void;

    /**
     * Trouve un item par son ID et vérifie les permissions
     * Retourne l'item ou null si non trouvé ou non autorisé
     */
    abstract protected function findItemById(int $id, $user, EntityManagerInterface $entityManager): ?Item;

    public function update(
        int $id,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();

        // Vérifier l'utilisateur selon le type de contrôleur
        $userValidation = $this->validateUser($user);
        if ($userValidation !== null) {
            return $userValidation;
        }

        // Trouver l'item et vérifier les permissions
        $item = $this->findItemById($id, $user, $entityManager);
        if ($item === null) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Item not found or access denied');
        }

        $jsonString = $request->request->get('data');
        $uploadedFile = $request->files->get('image');

        // Si aucun data et aucune image, erreur
        if (!$jsonString && !$uploadedFile) {
            return $this->jsonError(Response::HTTP_BAD_REQUEST, 'Bad Request', 'Missing "data" field or "image" file');
        }

        // Traiter les données JSON si présentes
        if ($jsonString) {
            try {
                $dto = $requestValidator->validate($jsonString, UpdateItemDto::class);
            } catch (\Exception $e) {
                return $this->jsonException($e);
            }

            // Mettre à jour le nom si fourni
            if ($dto->getName() !== null) {
                $item->setName($dto->getName());
            }

            // Mettre à jour la catégorie si fournie
            if ($dto->getCategory() !== null) {
                $categoryInput = $dto->getCategory();
                $category = is_numeric($categoryInput)
                    ? $categoryRepository->find((int) $categoryInput)
                    : $categoryRepository->findOneBy(['name' => $categoryInput]);

                if (!$category) {
                    return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Category not found');
                }

                $item->setCategory($category);
            }
        }

        // Traiter l'upload d'image si présent
        if ($uploadedFile) {
            // Supprimer l'ancienne image si elle existe
            $oldImage = $item->getImg();
            if ($oldImage) {
                $oldImagePath = $this->getParameter('items_uploads_directory') . '/' . $oldImage;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $newFilename = $this->handleFileUpload($uploadedFile, $slugger);
            if ($newFilename instanceof JsonResponse) {
                return $newFilename; // Erreur lors de l'upload
            }

            $item->setImg($newFilename);
        }

        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Item updated successfully',
                'item' => [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'category' => $item->getCategory()->getName(),
                    'img' => $item->getImg(),
                ]
            ],
            Response::HTTP_OK
        );
    }

    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        // Vérifier l'utilisateur selon le type de contrôleur
        $userValidation = $this->validateUser($user);
        if ($userValidation !== null) {
            return $userValidation;
        }

        // Trouver l'item et vérifier les permissions
        $item = $this->findItemById($id, $user, $entityManager);
        if ($item === null) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Item not found or access denied');
        }

        // Supprimer l'image associée si elle existe
        $image = $item->getImg();
        if ($image) {
            $imagePath = $this->getParameter('items_uploads_directory') . '/' . $image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($item);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Item deleted successfully'
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Gère l'upload du fichier image
     * Retourne le nom du fichier ou une JsonResponse en cas d'erreur
     */
    protected function handleFileUpload($uploadedFile, SluggerInterface $slugger): string|JsonResponse|null
    {
        if (!$uploadedFile) {
            return null;
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            if ($uploadedFile->getError() === UPLOAD_ERR_INI_SIZE || $uploadedFile->getError() === UPLOAD_ERR_FORM_SIZE) {
                return $this->jsonError(
                    Response::HTTP_BAD_REQUEST,
                    'Bad Request',
                    'File too large (server limit exceeded).'
                );
            }

            return $this->jsonError(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Internal Server Error',
                'Upload failed with error code: ' . $uploadedFile->getError()
            );
        }

        if ($uploadedFile->getSize() > self::MAX_UPLOAD_SIZE) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'File too large. Maximum size allowed is 2MB.'
            );
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif'
        ];

        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'
            );
        }

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

        try {
            $uploadedFile->move(
                $this->getParameter('items_uploads_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            return $this->jsonError(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Internal Server Error',
                'Failed to upload image'
            );
        }

        return $newFilename;
    }
}

