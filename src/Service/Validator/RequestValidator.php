<?php

namespace App\Service\Validator;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Exception;

class RequestValidator
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Valide les données par rapport à une collection de contraintes.
     *
     * @param array $data Les données à valider (généralement le json_decode du body)
     * @param array $constraints Un tableau associatif ['champ' => [Contraintes...]]
     * * @return array Les données validées (identiques à l'entrée, pratique pour le chaînage)
     * @throws Exception Si la validation échoue
     */
    public function check(array $data, array $constraints): array
    {
        // On utilise la contrainte "Collection" de Symfony pour valider un tableau associatif
        // allowExtraFields: true permet d'ignorer les champs non spécifiés dans le schéma
        // allowExtraFields: false (défaut) renverrait une erreur si un champ inconnu est envoyé
        $collectionConstraint = new Collection([
            'fields' => $constraints,
            'allowExtraFields' => true,
            'missingFieldsMessage' => 'Le champ {{ field }} est manquant.',
        ]);

        $violations = $this->validator->validate($data, $collectionConstraint);

        if (count($violations) > 0) {
            $errorMessage = $this->formatErrors($violations);
            throw new Exception($errorMessage);
        }

        return $data;
    }

    private function formatErrors(ConstraintViolationListInterface $violations): string
    {
        $errors = [];
        foreach ($violations as $violation) {
            // Format: "nom_du_champ: Le message d'erreur"
            // On nettoie le chemin de la propriété (ex: "[itemId]" devient "itemId")
            $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
            $errors[] = sprintf('%s: %s', $field, $violation->getMessage());
        }

        return implode(', ', $errors);
    }
}