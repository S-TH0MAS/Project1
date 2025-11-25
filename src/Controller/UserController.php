<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
        // Cette méthode ne sera jamais exécutée si json_login fonctionne.
        // Le gestionnaire de sécurité intercepte la requête avant.
        // Si vous arrivez ici, c'est que la config de sécurité a échoué.
        throw new \Exception('Should not be reached');
    }

    #[Route('/create', name: 'user_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            return new JsonResponse(
                ['error' => 'Email, password and name are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Vérifier si un utilisateur (ou client) existe déjà avec cet email
        $existingUser = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return new JsonResponse(
                ['error' => 'User with this email already exists'],
                Response::HTTP_CONFLICT
            );
        }

        // Créer un nouveau client
        $client = new Client();
        $client->setEmail($data['email']);
        $client->setName($data['name']);
        
        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($client, $data['password']);
        $client->setPassword($hashedPassword);

        // Valider l'entité
        $errors = $validator->validate($client);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new JsonResponse(
                ['error' => 'Validation failed', 'details' => $errorMessages],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Sauvegarder le client
        $entityManager->persist($client);
        $entityManager->flush();

        // Retourner une réponse de succès
        return new JsonResponse(
            [
                'message' => 'Client created successfully',
                'client' => [
                    'id' => $client->getId(),
                    'email' => $client->getEmail(),
                    'name' => $client->getName(),
                    'roles' => $client->getRoles(),
                ]
            ],
            Response::HTTP_CREATED
        );
    }
}


