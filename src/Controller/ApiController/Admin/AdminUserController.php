<?php

namespace App\Controller\ApiController\Admin;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Trait\ApiResponseTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be an admin');
        }

        $users = $userRepository->findAll();

        $usersData = array_map(function ($user) {
            $data = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'type' => $user instanceof Client ? 'client' : 'user',
            ];

            // Si c'est un Client, ajouter le nom
            if ($user instanceof Client) {
                $data['name'] = $user->getName();
            }

            return $data;
        }, $users);

        return new JsonResponse(
            [
                'users' => $usersData,
                'count' => count($usersData)
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/delete/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if (!$isAdmin) {
            return $this->jsonError(Response::HTTP_FORBIDDEN, 'Forbidden', 'User must be an admin');
        }

        // Empêcher la suppression de soi-même
        if ($user->getId() === $id) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
                'Cannot delete your own account'
            );
        }

        $userToDelete = $userRepository->find($id);

        if (!$userToDelete) {
            return $this->jsonError(Response::HTTP_NOT_FOUND, 'Not Found', 'User not found');
        }

        $entityManager->remove($userToDelete);
        $entityManager->flush();

        return new JsonResponse(
            [
                'message' => 'User deleted successfully'
            ],
            Response::HTTP_OK
        );
    }
}

