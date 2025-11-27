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

#[Route('/recipe')]
class DeleteRecipeController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/{id}', name: 'api_recipe_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function deleteRecipe(
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

        // Vérifier que l'utilisateur est l'auteur de la recette
        if ($recipe->getAuthor() !== $user) {
            return $this->jsonError(
                Response::HTTP_FORBIDDEN,
                'Forbidden',
                'You can only delete your own recipes'
            );
        }

        $entityManager->remove($recipe);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Recipe deleted successfully'
            ],
            Response::HTTP_OK
        );
    }
}

