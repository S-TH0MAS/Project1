<?php

namespace App\Service\Gemini\RequestFormat;

use App\Service\Gemini\GeminiRequest;
use Exception;

class RecipeRequestFormat
{
    private const DEFAULT_MODEL = "gemini-2.5-flash";

    private const RESPONSE_FORMAT = [
        'type' => 'OBJECT',
        'properties' => [
            'recipe_name' => ['type' => 'STRING'],
            'matching_score' => [
                'type' => 'INTEGER',
                'description' => 'Score de pertinence entre 0 et 100'
            ],
            'preparation_time_minutes' => ['type' => 'INTEGER'],
            'ingredients' => [
                'type' => 'ARRAY',
                'items' => ['type' => 'STRING']
            ],
            'steps' => [
                'type' => 'ARRAY',
                'items' => ['type' => 'STRING']
            ]
        ],
        'required' => ['recipe_name', 'matching_score', 'preparation_time_minutes','ingredients', 'steps']
    ];

    public function __construct(
        private readonly GeminiRequest $geminiRequest
    )
    {
    }

    /**
     * Génère une recette basée sur les ingrédients disponibles et la demande de l'utilisateur
     *
     * @param array $ingredients Liste des ingrédients au format {item_name: quantity}
     * @param string $userRequest Prompt de l'utilisateur
     * @return array Réponse de Gemini contenant la recette
     * @throws Exception
     */
    public function generateRecipe(array $ingredients, string $userRequest): array
    {
        // Construire le prompt
        $prompt = $this->buildRecipePrompt($userRequest, $ingredients);

            // Préparer le contenu pour Gemini
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];

        // Configuration pour forcer le format JSON avec le schéma
        $configParams = [
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => self::RESPONSE_FORMAT
            ]
        ];

        // Appel à Gemini avec le schéma JSON
        $response = $this->geminiRequest->generateContent($contents, self::DEFAULT_MODEL, $configParams);

        // Extraire et décoder la réponse JSON
        $responseText = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($responseText)) {
            throw new Exception('Empty response from Gemini');
        }

        $decodedResponse = json_decode($responseText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Gemini: ' . json_last_error_msg());
        }

        return $decodedResponse;
    }

    /**
     * Construit le prompt pour la génération de recette
     *
     * @param string $userRequest Demande de l'utilisateur
     * @param array $ingredients Liste des ingrédients au format {item_name: quantity}
     * @return string Prompt formaté pour Gemini
     */
    private function buildRecipePrompt(string $userRequest, array $ingredients): string
    {
        // 1. Préparation des données hybrides
        $dataPayload = [
            'stock_disponible' => $ingredients,
            'demande_utilisateur' => $userRequest
        ];
        $jsonString = json_encode($dataPayload, JSON_UNESCAPED_UNICODE);

        // 2. Construction du Prompt mis à jour
        return <<<PROMPT
# RÔLE

Tu es un assistant culinaire expert. Analyse les données JSON ci-dessous pour proposer une recette.

# DONNÉES D'ENTRÉE (Format JSON)

<input_data>

$jsonString

</input_data>

# RÈGLES DE GESTION DES INGRÉDIENTS

- Les nombres indiquent la quantité en stock.

- **EXCEPTION** : Pour les ingrédients non quantifiables à l'unité (ex: "Sel", "Farine", "Huile", "Epices") : même si une quantité est indiquée (ex: 5), considère que tu en as **à volonté**.

- Pour les ingrédients dénombrables (ex: "Pommes", "Oeufs"), tu ne dois jamais dépasser le stock indiqué.

# CALCUL DU SCORE

Tu dois attribuer un **matching_score** (entier de 0 à 100) :

- **0** : La recette ne correspond pas du tout à la demande ou il manque trop d'ingrédients.

- **100** : La recette utilise parfaitement le stock et correspond exactement à l'envie de l'utilisateur.

# TÂCHE

Génère la recette en JSON. Ne mentionne **pas** le niveau de difficulté.

PROMPT;
    }
}

