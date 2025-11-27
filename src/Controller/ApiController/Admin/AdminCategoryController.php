<?php

namespace App\Controller\ApiController\Admin;

use App\DTO\Api\Category\AddCategoryDto;
use App\DTO\Api\Category\UpdateCategoryDto;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/category')]
#[IsGranted('ROLE_ADMIN')]
class AdminCategoryController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/add', name: 'api_admin_categories_add', methods: ['POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be an admin');
        }

        try {
            $dto = $requestValidator->validate($request->getContent(), AddCategoryDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        // Vérifier si une catégorie avec le même nom existe déjà
        $existingCategory = $entityManager->getRepository(Category::class)
            ->findOneBy(['name' => $dto->getName()]);

        if ($existingCategory) {
            return $this->jsonError(
                Response::HTTP_CONFLICT,
                'Conflict',
                'A category with this name already exists'
            );
        }

        $category = new Category();
        $category->setName($dto->getName());

        $entityManager->persist($category);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Category created successfully',
                'category' => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ]
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/update/{id}', name: 'api_admin_categories_update', methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        RequestValidator $requestValidator
    ): JsonResponse {
        $user = $this->getUser();
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be an admin');
        }

        $category = $categoryRepository->find($id);

        if (!$category) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Category not found');
        }

        try {
            $dto = $requestValidator->validate($request->getContent(), UpdateCategoryDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        // Si le nom est fourni, vérifier qu'il n'existe pas déjà
        if ($dto->getName() !== null) {
            $existingCategory = $entityManager->getRepository(Category::class)
                ->findOneBy(['name' => $dto->getName()]);

            if ($existingCategory && $existingCategory->getId() !== $id) {
                return $this->jsonError(
                    Response::HTTP_CONFLICT,
                    'Conflict',
                    'A category with this name already exists'
                );
            }

            $category->setName($dto->getName());
        }

        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Category updated successfully',
                'category' => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ]
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/delete/{id}', name: 'api_admin_categories_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be an admin');
        }

        $category = $categoryRepository->find($id);

        if (!$category) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'Category not found');
        }

        // Vérifier si la catégorie a des items associés
        if ($category->getItems()->count() > 0) {
            return $this->jsonError(
                Response::HTTP_CONFLICT,
                'Conflict',
                'Cannot delete category: it has associated items'
            );
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'Category deleted successfully'
            ],
            Response::HTTP_OK
        );
    }
}

