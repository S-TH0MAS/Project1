<?php

namespace App\Controller\ApiController;

use App\DTO\Api\Item\AddItemDto;
use App\Entity\Client;
use App\Entity\ClientItem;
use App\Repository\CategoryRepository;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
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

    use ApiResponseTrait;

    // Constante pour la taille max (2 Mo en octets)
    private const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;

    #[Route('/add', name: 'api_items_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof Client) {
            return new JsonResponse(['error' => 'User must be a client'], Response::HTTP_FORBIDDEN);
        }

        $jsonString = $request->request->get('data');
        $uploadedFile = $request->files->get('image');

        if (!$jsonString) {
            return new JsonResponse(['error' => 'Missing "data" field'], Response::HTTP_BAD_REQUEST);
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
            return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $newFilename = null;

        if ($uploadedFile) {
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                if ($uploadedFile->getError() === UPLOAD_ERR_INI_SIZE || $uploadedFile->getError() === UPLOAD_ERR_FORM_SIZE) {
                    return new JsonResponse([
                        'error' => 'File too large (server limit exceeded).'
                    ], Response::HTTP_BAD_REQUEST);
                }

                return new JsonResponse([
                    'error' => 'Upload failed with error code: ' . $uploadedFile->getError()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($uploadedFile->getSize() > self::MAX_UPLOAD_SIZE) {
                return new JsonResponse([
                    'error' => 'File too large. Maximum size allowed is 2MB.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif'
            ];

            if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
                return new JsonResponse([
                    'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'
                ], Response::HTTP_BAD_REQUEST);
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
                return new JsonResponse(['error' => 'Failed to upload image'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $clientItem = new ClientItem();
        $clientItem->setName($dto->getName());
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