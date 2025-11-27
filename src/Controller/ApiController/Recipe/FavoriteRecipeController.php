<?php

namespace App\Controller\ApiController\Recipe;

use App\Entity\Client;
use App\Repository\RecipeRepository;
use App\Trait\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/recipe/favorite')]
class FavoriteRecipeController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/add/{id}', name: 'api_recipe_favorite_add', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function addFavorite(
        int $id,
        RecipeRepository $recipeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        $recipe = $recipeRepository->find($id);

        if (!$recipe) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Recipe not found');
        }

        // Vérifier si la recette est déjà dans les favoris
        if ($user->getFavorites()->contains($recipe)) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'Recipe is already in favorites'
            );
        }

        // Ajouter la recette aux favoris
        $user->addFavorite($recipe);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Recipe added to favorites successfully'
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/remove/{id}', name: 'api_recipe_favorite_remove', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function removeFavorite(
        int $id,
        RecipeRepository $recipeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof Client) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
        }

        $recipe = $recipeRepository->find($id);

        if (!$recipe) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Recipe not found');
        }

        // Vérifier si la recette est dans les favoris
        if (!$user->getFavorites()->contains($recipe)) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'Recipe is not in favorites'
            );
        }

        // Retirer la recette des favoris
        $user->removeFavorite($recipe);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Recipe removed from favorites successfully'
            ],
            Response::HTTP_OK
        );
    }
}

