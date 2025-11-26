<?php

namespace App\Trait;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait ApiResponseTrait
{
    /**
     * Retourne une réponse JSON d'erreur formatée et harmonisée.
     *
     * @param int $status Le code HTTP (ex: 400, 404, 500)
     * @param string $error Le nom court de l'erreur (ex: "Validation Error", "Not Found")
     * @param string|null $message Une description plus longue (optionnel)
     * @param array $details Des détails techniques ou champs invalides (optionnel)
     */
    public function jsonError(int $status, string $error, ?string $message = null, array $details = []): JsonResponse
    {
        $response = [
            'code' => $status,
            'error' => $error,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return new JsonResponse($response, $status);
    }

    public function jsonException(\Throwable $exception): JsonResponse
    {
        // 1. Cas spécifique : Votre ValidationException
        if ($exception instanceof ValidationException) {
            return $this->jsonError(
                Response::HTTP_BAD_REQUEST, // 400
                'Validation Error',
                $exception->getMessage(),
                $exception->getDetails() // On récupère vos détails automatiquement
            );
        }

        // 2. Cas spécifique : Erreurs HTTP standard de Symfony (ex: 404 Not Found, 403 Access Denied)
        if ($exception instanceof HttpExceptionInterface) {
            return $this->jsonError(
                $exception->getStatusCode(),
                'Http Error',
                $exception->getMessage()
            );
        }

        // 3. Cas par défaut : Erreur générique (souvent 400 ou 500)
        // Ici on met 400 car si on l'attrape dans le controller, c'est souvent lié à la requête
        return $this->jsonError(
            Response::HTTP_BAD_REQUEST,
            'Bad Request',
            $exception->getMessage()
        );
    }
}