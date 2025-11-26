<?php

namespace App\Controller\ApiController\Admin;

use App\Controller\ApiController\AbstractItemController;
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

#[Route('/admin/items')]
#[IsGranted('ROLE_ADMIN')]
class AdminItemController extends AbstractItemController
{
    #[Route('/add', name: 'api_admin_items_add', methods: ['POST'])]
    public function add(
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestValidator $requestValidator
    ): JsonResponse {
        return parent::add($request, $categoryRepository, $entityManager, $slugger, $requestValidator);
    }

    #[Route('/update/{id}', name: 'api_admin_items_update', methods: ['POST'])]
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

    #[Route('/delete/{id}', name: 'api_admin_items_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        return parent::delete($id, $entityManager);
    }
    protected function validateUser($user): ?JsonResponse
    {
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be an admin');
        }

        return null;
    }

    protected function createItemInstance(): Item
    {
        return new Item();
    }

    protected function configureItem(Item $item, $user): void
    {
        // Pour les items admin, aucune configuration supplémentaire n'est nécessaire
        // Les items ne sont pas liés à un client
    }

    protected function findItemById(int $id, $user, EntityManagerInterface $entityManager): ?Item
    {
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return null;
        }

        $itemRepository = $entityManager->getRepository(Item::class);
        $item = $itemRepository->find($id);

        // L'admin peut modifier/supprimer uniquement les Item globaux (pas les ClientItem)
        // On vérifie que l'item n'est pas une instance de ClientItem
        if ($item instanceof Item && !($item instanceof ClientItem)) {
            return $item;
        }

        return null;
    }
}

