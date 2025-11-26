<?php

namespace App\Service\Validator;

use App\Exception\ValidationException; // Importez votre nouvelle exception
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Exception;

class RequestValidator
{
    private ValidatorInterface $validator;
    private SerializerInterface $serializer;

    public function __construct(ValidatorInterface $validator, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * @template T
     * @param string $jsonContent
     * @param class-string<T> $dtoClass
     * @return T
     * @throws Exception
     */
    public function validate(string $jsonContent, string $dtoClass)
    {
        // 1. Désérialisation
        try {
            $dto = $this->serializer->deserialize($jsonContent, $dtoClass, 'json');
        } catch (\Throwable $e) {
            // Ici, c'est une erreur de format JSON, on renvoie une erreur globale
            throw new Exception("Format de données invalide : " . $e->getMessage());
        }

        // 2. Validation
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            // On récupère les erreurs sous forme de tableau structuré
            $errors = $this->formatErrors($violations);

            // On lance notre exception personnalisée avec les détails
            throw new ValidationException($errors, "Erreur de validation des données");
        }

        return $dto;
    }

    /**
     * Retourne un tableau associatif [champ => message]
     */
    private function formatErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $message = $violation->getMessage();

            // Si plusieurs erreurs sur le même champ, on peut concaténer ou garder la dernière
            // Ici, exemple simple : on écrase ou on crée
            if (isset($errors[$field])) {
                $errors[$field] .= ' ' . $message;
            } else {
                $errors[$field] = $message;
            }
        }
        return $errors;
    }
}