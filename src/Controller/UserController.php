<?php

namespace App\Controller;

use App\DTO\User\CreateUserDto;
use App\Entity\Client;
use App\Entity\User;
use App\Service\Validator\RequestValidator;
use App\Trait\ApiResponseTrait;
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
    use ApiResponseTrait;

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
        ValidatorInterface $validator,
        RequestValidator $requestValidator
    ): JsonResponse {
        try {
            $dto = $requestValidator->validate($request->getContent(), CreateUserDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        $existingUser = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $dto->getEmail()]);

        if ($existingUser) {
            return $this->jsonError(
                Response::HTTP_CONFLICT,
                'Conflict',
                'User with this email already exists'
            );
        }

        // Créer un nouveau client
        $client = new Client();
        $client->setEmail($dto->getEmail());
        $client->setName($dto->getName());
        
        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($client, $dto->getPassword());
        $client->setPassword($hashedPassword);

        $errors = $validator->validate($client);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST,
                'Validation Error',
                'Validation failed',
                $errorMessages
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


