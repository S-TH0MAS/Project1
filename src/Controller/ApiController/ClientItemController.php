<?php

namespace App\Controller\ApiController;

use App\Controller\Abstract\AbstractItemController;
use App\Entity\Client;
use App\Entity\ClientItem;
use App\Entity\Item;
use App\Repository\CategoryRepository;
use App\Service\Validator\RequestValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/items')]
#[IsGranted('ROLE_USER')]
class ClientItemController extends AbstractItemController
{
    #[Route('/add', name: 'api_items_add', methods: ['POST'])]
    public function add(
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        return parent::add($request, $categoryRepository, $entityManager, $slugger, $requestValidator);
    }

    #[Route('/update/{id}', name: 'api_items_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        return parent::update($id, $request, $categoryRepository, $entityManager, $slugger, $requestValidator);
    }

    #[Route('/delete/{id}', name: 'api_items_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        return parent::delete($id, $entityManager);
    }
    protected function validateUser($user): ?JsonResponse
    {
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        return null;
    }

    protected function createItemInstance(): Item
    {
        return new ClientItem();
    }

    protected function configureItem(Item $item, $user): void
    {
        if ($item instanceof ClientItem && $user instanceof Client) {
            $item->setClient($user);
        }
    }

    protected function findItemById(int $id, $user, EntityManagerInterface $entityManager): ?Item
    {
        if (!$user instanceof Client) {
            return null;
        }

        $clientItemRepository = $entityManager->getRepository(ClientItem::class);
        $item = $clientItemRepository->find($id);

        // Vérifier que l'item existe et qu'il appartient au client
        if ($item instanceof ClientItem && $item->getClient() === $user) {
            return $item;
        }

        return null;
    }
}

