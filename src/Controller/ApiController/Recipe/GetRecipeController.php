<?php

namespace App\Controller\ApiController\Recipe;

use App\DTO\Api\Recipe\GetRecipesDto;
use App\Entity\Client;
use App\Repository\RecipeRepository;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/recipe')]
class GetRecipeController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/get', name: 'api_recipe_get', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function getRecipes(
        Request $request,
        RequestValidator $requestValidator,
        RecipeRepository $recipeRepository
    ): JsonResponse {
        try {
            $dto = $requestValidator->validate($request->getContent(), GetRecipesDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $quantity = $dto->getQuantity();
        $offset = $dto->getOffset();
        $mode = $dto->getMode();

        $user = $this->getUser();

        // Gérer les différents modes
        switch ($mode) {
            case 'favorite':
                if (!$user instanceof Client) {
                    return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
                }

                // Récupérer les recettes favorites avec pagination optimisée
                $recipes = $recipeRepository->findFavoriteRecipes($user, $quantity, $offset);
                break;

            case 'author':
                if (!$user instanceof Client) {
                    return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be a client');
                }

                // Récupérer les recettes dont le client est l'auteur avec pagination optimisée
                $recipes = $recipeRepository->findRecipesByAuthor($user, $quantity, $offset);
                break;

            case 'all':
            default:
                // Récupérer les recettes avec limit et offset depuis la base de données
                // Utilisation de findRecipesWithAuthors pour optimiser les requêtes (évite le problème N+1)
                $recipes = $recipeRepository->findRecipesWithAuthors($quantity, $offset);
                break;
        }

        // Transformer les entités Recipe en tableaux pour la réponse JSON
        $recipesArray = [];
        foreach ($recipes as $recipe) {
            $author = $recipe->getAuthor();
            $recipesArray[] = [
                'id' => $recipe->getId(),
                'name' => $recipe->getName(),
                'description' => $recipe->getDescription(),
                'matching' => $recipe->getMatching(),
                'preparation_time' => $recipe->getPreparationTime(),
                'ingredients' => $recipe->getIngredients(),
                'steps' => $recipe->getSteps(),
                'date' => $recipe->getDate(),
                'image' => $recipe->getImage(),
                'author' => $author ? ['id' => $author->getId(), 'name' => $author->getName()] : null,
            ];
        }

        return new JsonResponse(
            [
                'recipes' => $recipesArray,
            ],
            Response::HTTP_OK
        );
    }
}

