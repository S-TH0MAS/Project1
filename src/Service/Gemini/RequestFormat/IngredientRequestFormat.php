<?php

namespace App\Service\Gemini\RequestFormat;

use App\Entity\Category;
use App\Entity\Item;
use App\Service\Gemini\GeminiRequest;
use Exception;

class IngredientRequestFormat
{
    public function __construct(
        private readonly GeminiRequest $geminiRequest
    ) {
    }

    /**
     * Analyse un document pour extraire les ingrédients.
     *
     * @param Category[] $categories Liste d'objets Category
     * @param Item[] $existingItems Liste d'objets Item existants
     * @param string $mimeType
     * @param string $base64Data
     * @return array{name: string, category_id: int, existing_item_id: ?int, quantity: int, type: string}[]
     * @throws Exception
     */
    public function getIngredientList(array $categories, array $existingItems, string $mimeType, string $base64Data): array
    {
        // 1. Préparer les listes d'IDs en STRING pour satisfaire l'API Gemini
        // L'API exige des STRING pour les ENUMS

        $validCategoryIds = array_map(fn(Category $category) => (string) $category->getId(), $categories);

        $validItemIds = array_map(fn(Item $item) => (string) $item->getId(), $existingItems);
        // Note : le null est géré par la propriété "nullable: true" du schéma, pas besoin de l'ajouter dans l'enum string

        // 2. Générer le schéma dynamique
        $schema = $this->getResponseSchema($validCategoryIds, $validItemIds);

        // 3. Construire le prompt
        $prompt = $this->buildIngredientPrompt($categories, $existingItems);

        // 4. Préparer le payload
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $base64Data
                        ]
                    ]
                ]
            ]
        ];

        // 5. Configuration
        $configParams = [
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema
            ]
        ];

        // 6. Envoi
        $response = $this->geminiRequest->generateContent($contents, GeminiRequest::DEFAULT_MODEL, $configParams);

        // 7. Décodage
        $responseText = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($responseText)) {
            throw new Exception('Empty response from Gemini');
        }

        $decoded = json_decode($responseText, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        // 8. Parsing, conversion en INT et ajout du TYPE
        return $this->parse($decoded);
    }

    /**
     * Nettoie, convertit les types et ajoute le champ "type"
     */
    public function parse(array $decodedResponse): array
    {
        $parsedItems = [];

        foreach ($decodedResponse as $item) {
            // Vérification des clés
            if (!array_key_exists('existing_item_id', $item) || !isset($item['category_id'])) {
                continue;
            }

            // CONVERSION DE TYPE : String (API) -> Int (PHP)
            $existingId = $item['existing_item_id'] !== null ? (int) $item['existing_item_id'] : null;

            // Logique métier : Si ID existe, c'est un EXISTING_ITEM
            $type = ($existingId !== null) ? 'EXISTING_ITEM' : 'NEW_ITEM';

            $parsedItems[] = [
                'name' => (string) ($item['name'] ?? 'Inconnu'),
                'category_id' => (int) $item['category_id'], // Cast string -> int
                'existing_item_id' => $existingId,
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 1,
                'type' => $type // Champ ajouté pour votre logique
            ];
        }

        return $parsedItems;
    }

    /**
     * Génère le schéma JSON (CORRECTION ERREUR 400)
     */
    private function getResponseSchema(array $validCategoryIds, array $validItemIds): array
    {
        return [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'Nom de l\'ingrédient détecté.'
                    ],
                    'category_id' => [
                        'type' => 'STRING', // <--- CORRECTION ICI : STRING OBLIGATOIRE
                        'description' => 'ID de la catégorie',
                        'enum' => $validCategoryIds // ["1", "2", ...]
                    ],
                    'existing_item_id' => [
                        'type' => 'STRING', // <--- CORRECTION ICI : STRING OBLIGATOIRE
                        'nullable' => true,
                        'description' => 'ID de l\'item existant si trouvé, sinon null',
                        'enum' => $validItemIds // ["10", "15", ...]
                    ],
                    'quantity' => [
                        'type' => 'INTEGER',
                        'description' => 'Quantité estimée'
                    ]
                ],
                'required' => ['name', 'category_id', 'existing_item_id', 'quantity']
            ]
        ];
    }

    /**
     * Construit le prompt
     */
    private function buildIngredientPrompt(array $categories, array $existingItems): string
    {
        $cleanCategories = array_map(function(Category $category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }, $categories);

        $cleanItems = array_map(function(Item $item) {
            return [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'id_category' => $item->getCategory()?->getId()
            ];
        }, $existingItems);

        $categoriesJson = json_encode($cleanCategories, JSON_UNESCAPED_UNICODE);
        $itemsJson = json_encode($cleanItems, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
# RÔLE
Tu es un expert en inventaire culinaire. Analyse le document (Image/PDF/Audio) pour lister les ingrédients.

# CONTEXTE 1 : CATÉGORIES
Voici la liste des catégories disponibles au format JSON :
$categoriesJson

# CONTEXTE 2 : ITEMS EXISTANTS
Voici la liste des items déjà connus au format JSON :
$itemsJson

# TÂCHE
1. Détecte les produits visibles ou listés.
    - **SI IMAGE/PDF** : Détecte les produits visibles ou listés sur le ticket.
    - **SI AUDIO** : Écoute la voix, transcris la liste de courses ou l'inventaire dicté et extrais les produits mentionnés.

2. **FILTRE ALIMENTAIRE STRICT** :
   - Tu ne dois conserver **QUE** les produits comestibles (nourriture, boissons, épices).
   - **EXCLUS** tout ce qui n'est pas mangeable (produits ménagers, emballages, etc.).

3. **MATCHING (Crucial)** :
   - Pour chaque aliment, cherche s'il existe déjà dans "ITEMS EXISTANTS".
   - **SI OUI** (Correspondance trouvée) :
     - Remplis `existing_item_id` avec l'**id** de l'item trouvé (format string).
     - Remplis `name` avec un nom **clair, lisible et complet** basé sur le document (n'utilise **PAS** le nom interne de la liste "ITEMS EXISTANTS" s'il est moins précis).
     - Remplis `category_id` avec l'**id_category** de l'item trouvé (format string).
   - **SI NON** (Nouveau produit) :
     - Remplis `existing_item_id` avec `null`.
     - Invente un `name` clair.
     - Choisis le `category_id` le plus pertinent (format string).

4. **ESTIMATION DE LA QUANTITÉ (Unités Consommables)**
    Ton objectif est de définir une quantité en **nombre d'unités (pièces)** consommables.

    **POUR L'AUDIO (Spécifique)** :
    - Si l'utilisateur dit "Trois boîtes de tomates", quantity = 3.
    - Si l'utilisateur dit "Du lait" sans préciser, quantity = 1.
    - Si l'utilisateur dit "500 grammes de beurre", applique la règle de conversion poids ci-dessous.

    **RÈGLES DE CALCUL (Image & Audio)** :
    
    **CAS A : Produits au poids (Fruits, Légumes, Vrac)**
    - Si un poids est indiqué (ex: "1.2 kg"), tu dois **ESTIMER le nombre de pièces** que cela représente en te basant sur le poids moyen standard de cet aliment.
      - *Exemple : "Bananes 1.2 kg" (Moyenne ~150g/unité) -> quantity: 8*
      - *Exemple : "Tomates 500g" (Moyenne ~100g/unité) -> quantity: 5*
    - Si l'estimation est impossible (ex: "Viande hachée vrac"), mets `1`.
    
    **CAS B : Produits unitaires et Multipacks (Boîtes, Packs, Lots)**
    - **Détecte les lots** : Cherche des mentions comme "Pack", "Lot", "x6", "6x" (ex: "Pack 6 briques lait").
    - **Calcule le TOTAL** : Multiplie le nombre de packs achetés par le contenu du pack.
      - *Exemple "1 Pack de 6 laits" -> quantity: 6* (1 pack * 6 unités)
      - *Exemple "2 Packs de 6 laits" -> quantity: 12* (2 packs * 6 unités)
      - *Exemple "Lot de 4 yaourts" -> quantity: 4*
    - Si c'est un produit simple sans mention de lot (ex: "1 Bouteille d'huile"), la quantité est `1`.

# SORTIE
Uniquement le tableau JSON.
PROMPT;
    }
}