<?php

namespace App\Controller\Test\Gemini;

use App\Service\Gemini\GeminiRequest;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GeminiTestController extends AbstractController
{
    #[Route('/test/gemini', name: 'test_gemini_connectivity', methods: ['POST'])]
    public function index(GeminiRequest $geminiRequest, Request $request): JsonResponse
    {
        // 1. Récupération du body JSON
        try {
            // toArray() lance une exception si le body n'est pas du JSON valide ou est vide
            $payload = $request->toArray();
        } catch (Exception $e) {
            // Si pas de JSON valide, on part sur un tableau vide pour déclencher le défaut
            $payload = [];
        }

        // 2. Extraction du prompt avec "hello" par défaut
        $prompt = $payload['prompt'] ?? 'hello';

        try {
            // Appel au service Gemini
            $responseContent = $geminiRequest->ask($prompt);

            return $this->json([
                'status' => 'success',
                'input_prompt' => $prompt,
                'gemini_response' => $responseContent
            ]);

        } catch (Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}