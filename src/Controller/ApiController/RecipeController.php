<?php

namespace App\Controller\ApiController;

use App\Entity\Client;
use App\Repository\InventoryRepository;
use App\Service\Gemini\RequestFormat\RecipeRequestFormat;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/generate_recipes')]
class RecipeController extends AbstractController
{
    #[Route('', name: 'api_generate_recipes', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateRecipes(
        Request $request,
        InventoryRepository $inventoryRepository,
        RecipeRequestFormat $recipeRequestFormat
    ): JsonResponse {
        // Récupérer l'utilisateur connecté (qui doit être un Client)
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return new JsonResponse(
                ['error' => 'User must be a client'],
                Response::HTTP_FORBIDDEN
            );
        }

        // Récupérer le prompt de l'utilisateur depuis le body
        $data = json_decode($request->getContent(), true);
        $userRequest = $data['prompt'] ?? '';

        // Validation du prompt
        if (empty($userRequest)) {
            return new JsonResponse(
                ['error' => 'Prompt is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Récupérer tous les items du client avec leurs quantités
        $clientInventories = $inventoryRepository->findBy(['client' => $user]);

        // Construire la liste au format {item_name: quantity}
        $stock = [];
        
        foreach ($clientInventories as $inventory) {
            $item = $inventory->getItem();
            $itemName = $item->getName();
            $quantity = $inventory->getQuantity();
            
            // Utiliser le nom de l'item comme clé et la quantité comme valeur
            $stock[$itemName] = $quantity;
        }

        // Si le stock est vide, retourner une erreur
        if (empty($stock)) {
            return new JsonResponse(
                ['error' => 'No ingredients available in inventory'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Générer la recette via le service Gemini
            $recipe = $recipeRequestFormat->generateRecipe($stock, $userRequest);

            return new JsonResponse(
                $recipe,
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return new JsonResponse(
                [
                    'error' => 'Failed to generate recipe',
                    'message' => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

