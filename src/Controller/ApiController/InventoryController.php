<?php

namespace App\Controller\ApiController;

use App\DTO\Api\Inventory\AddInventoryDto;
use App\DTO\Api\Inventory\RemoveInventoryDto;
use App\Entity\Client;
use App\Entity\Inventory;
use App\Repository\CategoryRepository;
use App\Repository\ClientItemRepository;
use App\Repository\InventoryRepository;
use App\Repository\ItemRepository;
use App\Service\Gemini\RequestFormat\IngredientRequestFormat;
use App\Service\Validator\DocumentValidator;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventories')]
class InventoryController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_inventories_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(
        ItemRepository $itemRepository,
        ClientItemRepository $clientItemRepository,
        InventoryRepository $inventoryRepository
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        // --- Étape 1 : Récupérer tous les items (Item non client)
        $defaultItems = $itemRepository->createQueryBuilder('i')
            ->where('i INSTANCE OF App\Entity\Item')
            ->getQuery()
            ->getResult();

        // --- Étape 2 : Récupérer tous les items du client (ClientItem)
        $clientItems = $clientItemRepository->findBy(['client' => $user]);

        // --- Étape 3 : Récupérer l'inventaire du client
        $clientInventories = $inventoryRepository->findBy(['client' => $user]);

        // --- Étape 4 : Concaténer les deux listes d'items pour avoir tous les items
        $allItems = [];
        $itemIds = []; // Pour éviter les doublons

        // Ajouter les items par défaut (Item non client)
        foreach ($defaultItems as $item) {
            $itemId = $item->getId();
            $allItems[] = [
                'id' => $itemId,
                'name' => $item->getName(),
                'category' => [
                    'id' => $item->getCategory()->getId(),
                    'name' => $item->getCategory()->getName(),
                ],
                'img' => $item->getImg(),
            ];
            $itemIds[$itemId] = true;
        }

        // Ajouter les items du client (ClientItem)
        $clientItemIds = [];
        foreach ($clientItems as $clientItem) {
            $itemId = $clientItem->getId();
            $clientItemIds[] = $itemId;
            
            // Ajouter l'item à la liste si pas déjà présent
            if (!isset($itemIds[$itemId])) {
                $allItems[] = [
                    'id' => $itemId,
                    'name' => $clientItem->getName(),
                    'category' => [
                        'id' => $clientItem->getCategory()->getId(),
                        'name' => $clientItem->getCategory()->getName(),
                    ],
                    'img' => $clientItem->getImg(),
                ];
                $itemIds[$itemId] = true;
            }
        }

        // --- Étape 5 : Préparer l'array de retour pour l'inventory
        $inventoryData = [];
        foreach ($clientInventories as $inventory) {
            $item = $inventory->getItem();
            $itemId = $item->getId();
            
            // S'assurer que l'item est dans la liste si pas déjà présent
            if (!isset($itemIds[$itemId])) {
                $allItems[] = [
                    'id' => $itemId,
                    'name' => $item->getName(),
                    'category' => [
                        'id' => $item->getCategory()->getId(),
                        'name' => $item->getCategory()->getName(),
                    ],
                    'img' => $item->getImg(),
                ];
                $itemIds[$itemId] = true;
            }

            // Ajouter à l'inventory avec la quantité
            $inventoryData[] = [
                'item_id' => $itemId,
                'quantity' => $inventory->getQuantity(),
            ];
        }

        return new JsonResponse(
            [
                'items' => $allItems,
                'inventory' => $inventoryData,
                'client_items' => $clientItemIds,
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/add', name: 'api_inventories_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        Request $request,
        ItemRepository $itemRepository,
        InventoryRepository $inventoryRepository,
        EntityManagerInterface $entityManager,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        try {
            $dto = $requestValidator->validate($request->getContent(), AddInventoryDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $itemId = $dto->getItemId();
        $quantity = $dto->getQuantity();

        $item = $itemRepository->find($itemId);
        if (!$item) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Item not found');
        }
        $existingInventory = $inventoryRepository->findOneBy([
            'client' => $user,
            'item' => $item
        ]);

        if ($existingInventory) {
            // Ajouter la quantité à l'inventory existant
            $newQuantity = $existingInventory->getQuantity() + $quantity;
            $existingInventory->setQuantity($newQuantity);
            $entityManager->flush();

            return new JsonResponse(
                [
                    'message' => 'Inventory updated successfully',
                    'inventory' => [
                        'item_id' => $itemId,
                        'quantity' => $newQuantity
                    ]
                ],
                Response::HTTP_OK
            );
        } else {
            // Créer un nouvel inventory
            $inventory = new Inventory();
            $inventory->setClient($user);
            $inventory->setItem($item);
            $inventory->setQuantity($quantity);

            $entityManager->persist($inventory);
            $entityManager->flush();

            return new JsonResponse(
                [
                    'message' => 'Inventory created successfully',
                    'inventory' => [
                        'item_id' => $itemId,
                        'quantity' => $quantity
                    ]
                ],
                Response::HTTP_CREATED
            );
        }
    }

    #[Route('/remove', name: 'api_inventories_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function remove(
        Request $request,
        ItemRepository $itemRepository,
        InventoryRepository $inventoryRepository,
        EntityManagerInterface $entityManager,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        try {
            $dto = $requestValidator->validate($request->getContent(), RemoveInventoryDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $itemId = $dto->getItemId();
        $quantity = $dto->getQuantity();

        $item = $itemRepository->find($itemId);
        if (!$item) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Item not found');
        }
        $existingInventory = $inventoryRepository->findOneBy([
            'client' => $user,
            'item' => $item
        ]);

        if (!$existingInventory) {
            // Si l'inventory n'existe pas, on ne fait rien
            return new JsonResponse(
                [
                    'message' => 'Inventory not found, nothing to remove'
                ],
                Response::HTTP_OK
            );
        }

        // Retirer la quantité
        $currentQuantity = $existingInventory->getQuantity();
        $newQuantity = $currentQuantity - $quantity;

        // Si la nouvelle quantité est inférieure ou égale à 0, supprimer l'inventory
        if ($newQuantity <= 0) {
            $entityManager->remove($existingInventory);
            $entityManager->flush();

            return new JsonResponse(
                [
                    'message' => 'Inventory removed successfully',
                    'inventory' => [
                        'item_id' => $itemId,
                        'quantity' => 0
                    ]
                ],
                Response::HTTP_OK
            );
        }

        // Sinon, mettre à jour la quantité
        $existingInventory->setQuantity($newQuantity);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Inventory updated successfully',
                'inventory' => [
                    'item_id' => $itemId,
                    'quantity' => $newQuantity
                ]
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/add-by-doc', name: 'api_inventories_add_by_doc', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addByDoc(
        Request $request,
        DocumentValidator $documentValidator,
        CategoryRepository $categoryRepository,
        ItemRepository $itemRepository,
        ClientItemRepository $clientItemRepository,
        IngredientRequestFormat $ingredientRequestFormat
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        // Récupérer le fichier uploadé
        $uploadedFile = $request->files->get('document');

        // Types MIME autorisés : images + PDF
        $allowedMimeTypes = [
            // Images
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/heic', // Utile pour iPhone
            'image/heif',

            // Documents
            'application/pdf',

            // Audio (Gemini supporte : WAV, MP3, AAC, FLAC, OGG, M4A)
            'audio/mpeg',    // MP3 standard
            'audio/mp3',     // Variante MP3
            'audio/wav',     // WAV standard
            'audio/x-wav',   // Variante WAV
            'audio/ogg',     // OGG
            'audio/aac',     // AAC
            'audio/m4a',     // Apple Voice Memos (iPhone)
            'audio/x-m4a',   // Variante M4A
            'audio/mp4',     // Parfois l'audio M4A est détecté comme MP4 container
            'audio/flac'     // Haute qualité
        ];

        // Taille maximale : 10 Mo
        $maxSize = 10 * 1024 * 1024;

        // Valider le document
        try {
            $documentValidator->validate($uploadedFile, $allowedMimeTypes, $maxSize);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        // Récupérer toutes les catégories
        $categories = $categoryRepository->findAll();

        // Récupérer tous les items (Item non client)
        $defaultItems = $itemRepository->createQueryBuilder('i')
            ->where('i INSTANCE OF App\Entity\Item')
            ->getQuery()
            ->getResult();

        // Récupérer tous les items du client (ClientItem)
        $clientItems = $clientItemRepository->findBy(['client' => $user]);

        // Concaténer les deux listes d'items
        $allItems = array_merge($defaultItems, $clientItems);

        // Convertir le fichier en base64
        $mimeType = $uploadedFile->getMimeType();
        $fileContent = file_get_contents($uploadedFile->getPathname());
        $base64Data = base64_encode($fileContent);

        // Appeler IngredientRequestFormat pour analyser le document
        try {
            $ingredients = $ingredientRequestFormat->getIngredientList(
                $categories,
                $allItems,
                $mimeType,
                $base64Data
            );
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        return new JsonResponse(
            [
                'ingredients' => $ingredients
            ],
            Response::HTTP_OK
        );
    }
}

