<?php

namespace App\Controller\ApiController\Recipe;

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
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/recipe')]
class GenerateRecipeController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/generate', name: 'api_recipe_generate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateRecipes(
        Request $request,
        InventoryRepository $inventoryRepository,
        RecipeRequestFormat $recipeRequestFormat,
        RequestValidator $requestValidator,
        CacheInterface $cache
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
            $decodedResponse = $recipeRequestFormat->generateRecipe($stock, $userRequest);
            $recipe = $recipeRequestFormat->parse($decodedResponse);

            // Générer une clé unique pour le cache
            $cacheKey = 'recipe_' . Uuid::v4()->toRfc4122();

            // Supprimer le cache avant le get par sécurité
            try {
                $cache->delete($cacheKey);
            } catch (InvalidArgumentException $e) {
                // Ignorer l'erreur si la clé n'existe pas
            }

            // Stocker la recette dans le cache pendant 1 heure (3600 secondes)
            try {
                $cache->get($cacheKey, function (ItemInterface $item) use ($recipe) {
                    $item->expiresAfter(3600); // 1 heure
                    return $recipe;
                });
            } catch (InvalidArgumentException $e) {
                return $this->jsonError(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    'Internal Server Error',
                    'Failed to cache recipe: ' . $e->getMessage()
                );
            }

            // Ajouter la clé de cache à la réponse
            $response = $recipe;
            $response['cache_key'] = $cacheKey;

            return new JsonResponse(
                $response,
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

