<?php

namespace App\Controller\ApiController\Recipe;

use App\DTO\Api\Recipe\SaveRecipeDto;
use App\Entity\Client;
use App\Entity\Recipe;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route('/recipe')]
class SaveRecipeController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/save', name: 'api_recipe_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveRecipe(
        Request $request,
        RequestValidator $requestValidator,
        CacheInterface $cache,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        
        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        try {
            $dto = $requestValidator->validate($request->getContent(), SaveRecipeDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $cacheKey = $dto->getCacheKey();

        // Récupérer la recette depuis le cache
        try {
            $cachedRecipe = $cache->get($cacheKey, function (ItemInterface $item) {
                // Si la recette n'existe pas dans le cache, lever une exception
                throw new Exception('Recipe not found in cache');
            });
        } catch (InvalidArgumentException $e) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'Invalid cache key: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            return $this->jsonError(
                Response::HTTP_NOT_FOUND,
                'Not Found',
                'Recipe not found in cache or cache expired'
            );
        }

        // Vérifier que les données de la recette sont valides
        $requiredFields = ['name', 'description', 'matching', 'preparation_time', 'ingredients', 'steps'];
        foreach ($requiredFields as $field) {
            if (!isset($cachedRecipe[$field])) {
                return $this->jsonError(
                    Response::HTTP_BAD_REQUEST,
                    'Bad Request',
                    "Missing required field in cached recipe: {$field}"
                );
            }
        }

        // Créer une nouvelle entité Recipe
        $recipe = new Recipe();
        $recipe->setName($cachedRecipe['name']);
        $recipe->setDescription($cachedRecipe['description']);
        $recipe->setMatching($cachedRecipe['matching']);
        $recipe->setPreparationTime($cachedRecipe['preparation_time']);
        $recipe->setIngredients($cachedRecipe['ingredients']);
        $recipe->setSteps($cachedRecipe['steps']);
        $recipe->setDate(time()); // Timestamp actuel
        $recipe->setAuthor($user);

        // Persister la recette
        $entityManager->persist($recipe);
        $entityManager->flush();

        // Ajouter la recette aux favoris du client
        $user->addFavorite($recipe);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Recipe saved successfully',
                'recipe_id' => $recipe->getId(),
            ],
            Response::HTTP_CREATED
        );
    }
}

