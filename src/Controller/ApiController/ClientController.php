<?php

namespace App\Controller\ApiController;

use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
class ClientController extends AbstractController
{
    #[Route('', name: 'api_client_info', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function info(): JsonResponse
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        // Vérifier si l'utilisateur est un admin
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        
        // Si c'est un admin, on ne renvoie pas le name
        if ($isAdmin) {
            return new JsonResponse(
                [
                    'email' => $user->getEmail(),
                    'roles' => $roles,
                ],
                Response::HTTP_OK
            );
        }
        
        // Sinon, vérifier que c'est un Client (pour avoir le name)
        if (!$user instanceof Client) {
            return new JsonResponse(
                ['error' => 'User must be a client'],
                Response::HTTP_FORBIDDEN
            );
        }

        return new JsonResponse(
            [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'roles' => $roles,
            ],
            Response::HTTP_OK
        );
    }
}

