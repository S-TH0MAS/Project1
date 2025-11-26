<?php

namespace App\Controller\ApiController;

use App\Entity\Client;
use App\Entity\ClientItem;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

# TODO: Factoriser la verification du body avec un verificateur

#[Route('/items')]
class ItemController extends AbstractController
{
    #[Route('/add', name: 'api_items_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        Request $request,
        CategoryRepository $categoryRepository,
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
        if (!isset($data['name']) || !isset($data['category'])) {
            return new JsonResponse(
                ['error' => 'name and category are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $name = trim($data['name']);
        $categoryInput = $data['category'];

        // Vérifier que le nom n'est pas vide
        if (empty($name)) {
            return new JsonResponse(
                ['error' => 'name cannot be empty'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Rechercher la catégorie par ID ou par nom
        $category = null;
        if (is_numeric($categoryInput)) {
            // Si c'est un nombre, chercher par ID
            $category = $categoryRepository->find((int) $categoryInput);
        } else {
            // Sinon, chercher par nom
            $category = $categoryRepository->findOneBy(['name' => $categoryInput]);
        }

        if (!$category) {
            return new JsonResponse(
                ['error' => 'Category not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Créer le ClientItem
        $clientItem = new ClientItem();
        $clientItem->setName($name);
        $clientItem->setCategory($category);
        $clientItem->setClient($user);
        $clientItem->setImg(null); // L'image reste null pour l'instant

        $entityManager->persist($clientItem);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Item created successfully',
                'item' => [
                    'id' => $clientItem->getId(),
                    'name' => $clientItem->getName(),
                    'category' => [
                        'id' => $category->getId(),
                        'name' => $category->getName(),
                    ],
                    'img' => $clientItem->getImg(),
                ]
            ],
            Response::HTTP_CREATED
        );
    }
}

