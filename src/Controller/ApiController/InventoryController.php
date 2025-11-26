<?php

namespace App\Controller\ApiController;

use App\Entity\Client;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Repository\ClientItemRepository;
use App\Repository\InventoryRepository;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#TODO: Factorise le code avec un service de validation de body

#[Route('/inventories')]
class InventoryController extends AbstractController
{
    #[Route('', name: 'api_inventories_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(
        ItemRepository $itemRepository,
        ClientItemRepository $clientItemRepository,
        InventoryRepository $inventoryRepository
    ): JsonResponse {
        // Récupérer l'utilisateur connecté (qui doit être un Client)
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return new JsonResponse(
                ['error' => 'User must be a client'],
                Response::HTTP_FORBIDDEN
            );
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
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupérer l'utilisateur connecté (qui doit être un Client)
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return new JsonResponse(
                ['error' => 'User must be a client'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['itemId']) || !isset($data['quantity'])) {
            return new JsonResponse(
                ['error' => 'itemId and quantity are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $itemId = (int) $data['itemId'];
        $quantity = (int) $data['quantity'];

        // Vérifier que la quantité est positive
        if ($quantity <= 0) {
            return new JsonResponse(
                ['error' => 'Quantity must be greater than 0'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier que l'item existe
        $item = $itemRepository->find($itemId);
        if (!$item) {
            return new JsonResponse(
                ['error' => 'Item not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier si l'inventory existe déjà pour ce client et cet item
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
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Récupérer l'utilisateur connecté (qui doit être un Client)
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return new JsonResponse(
                ['error' => 'User must be a client'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['itemId']) || !isset($data['quantity'])) {
            return new JsonResponse(
                ['error' => 'itemId and quantity are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $itemId = (int) $data['itemId'];
        $quantity = (int) $data['quantity'];

        // Vérifier que la quantité est positive
        if ($quantity <= 0) {
            return new JsonResponse(
                ['error' => 'Quantity must be greater than 0'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier que l'item existe
        $item = $itemRepository->find($itemId);
        if (!$item) {
            return new JsonResponse(
                ['error' => 'Item not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier si l'inventory existe pour ce client et cet item
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
}

