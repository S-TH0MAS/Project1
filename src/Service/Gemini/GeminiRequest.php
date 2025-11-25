<?php

namespace App\Service\Gemini;

use App\Service\ProxyAwareClient\ProxyAwareClient;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiRequest
{
    // URL de base de l'API Google Generative Language
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    private const DEFAULT_MODEL = "gemini-2.5-flash";

    public function __construct(
        private readonly ProxyAwareClient $proxyAwareClient,
        #[Autowire('%env(GEMINI_KEY)%')]
        private readonly string $geminiKey
    ) {
    }


    /**
     * @throws Exception
     */
    public function generateContent(array $contents, string $model = self::DEFAULT_MODEL, array $configParams = []): array
    {
        // Construction de l'URL complète avec la clé API en query param
        $endpoint = self::BASE_URL . $model . ':generateContent?key=' . $this->geminiKey;

        // Préparation du payload JSON
        // On fusionne le body principal avec les configurations optionnelles
        $body = array_merge([
            'contents' => $contents
        ], $configParams);

        // Envoi via votre ProxyAwareClient
        // Note: La méthode send() de votre client gère déjà le 'json' => $body et le proxy
        return $this->proxyAwareClient->send($endpoint, $body, 'POST');
    }

    /**
     * @throws Exception
     */
    public function ask(string $prompt, string $model = 'gemini-2.5-flash'): string
    {
        $payload = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];

        $response = $this->generateContent($payload, $model);

        // Extraction sécurisée du texte de la réponse
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}