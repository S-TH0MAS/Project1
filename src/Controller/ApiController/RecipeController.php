<?php

namespace App\Controller\ApiController;

use App\DTO\Api\Recipe\GenerateRecipeDto;
use App\Entity\Client;
use App\Repository\InventoryRepository;
use App\Service\Gemini\RequestFormat\RecipeRequestFormat;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
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
    use ApiResponseTrait;

    #[Route('', name: 'api_generate_recipes', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateRecipes(
        Request $request,
        InventoryRepository $inventoryRepository,
        RecipeRequestFormat $recipeRequestFormat,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        try {
            $dto = $requestValidator->validate($request->getContent(), GenerateRecipeDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $userRequest = $dto->getPrompt();

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

        if (empty($stock)) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'No ingredients available in inventory'
            );
        }

        try {
            $recipe = $recipeRequestFormat->generateRecipe($stock, $userRequest);

            return new JsonResponse(
                $recipe,
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return $this->jsonError(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Internal Server Error',
                'Failed to generate recipe: ' . $e->getMessage()
            );
        }
    }
}

