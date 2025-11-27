# Génération de recette

## Route
```
POST /api/recipe/generate
```

## Méthode
`POST`

## Description
Cette route permet de générer une recette personnalisée basée sur les ingrédients disponibles dans l'inventaire du client connecté et sa demande. La génération utilise l'API Gemini (Google Generative AI) pour créer une recette adaptée au stock disponible et aux préférences de l'utilisateur.

## Paramètres

### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `prompt` | string | Oui | La demande de l'utilisateur pour la recette (ex: "Donne-moi une recette simple", "Je veux un dessert") |

### Exemple de requête
```json
{
  "prompt": "Donne-moi une recette simple et rapide avec mes ingrédients"
}
```

## Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Exemple de requête complète
```http
POST /api/recipe/generate
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json

{
  "prompt": "Donne-moi une recette végétarienne"
}
```

## Retour

### Succès (200 OK)
```json
{
  "name": "Tarte aux pommes",
  "description": "Une délicieuse tarte aux pommes maison",
  "matching": 95,
  "preparation_time": 45,
  "ingredients": [
    "5 Pommes",
    "200g de farine",
    "100g de beurre",
    "50g de sucre",
    "1 pincée de sel"
  ],
  "steps": [
    "Préparer la pâte en mélangeant la farine, le beurre et le sel",
    "Étaler la pâte dans un moule à tarte",
    "Éplucher et couper les pommes en lamelles",
    "Disposer les pommes sur la pâte",
    "Saupoudrer de sucre",
    "Enfourner à 180°C pendant 30 minutes"
  ],
  "cache_key": "recipe_550e8400-e29b-41d4-a716-446655440000"
}
```

**Structure de la réponse :**

- `name` : Nom de la recette générée (string)
- `description` : Description courte de la recette (string)
- `matching` : Score de pertinence entre 0 et 100 (integer)
  - **0** : La recette ne correspond pas du tout à la demande ou il manque trop d'ingrédients
  - **100** : La recette utilise parfaitement le stock et correspond exactement à l'envie de l'utilisateur
- `preparation_time` : Temps de préparation estimé en minutes (integer)
- `ingredients` : Liste des ingrédients nécessaires avec leurs quantités (array de string)
- `steps` : Étapes de préparation de la recette (array de string)
- `cache_key` : Clé unique permettant de récupérer la recette depuis le cache (string, UUID v4)
  - La recette est mise en cache pendant **1 heure** (3600 secondes)
  - Cette clé peut être utilisée pour récupérer la recette sans avoir à la régénérer

> **Note** : Le format de réponse correspond à la structure de l'entité `Recipe` pour faciliter la création directe d'une instance de recette. Les champs `id`, `author`, `date` et `image` ne sont pas inclus car ils doivent être définis lors de la persistance en base de données. La recette est automatiquement mise en cache avec une clé unique pour éviter de régénérer la même recette dans l'heure qui suit.

## Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](../error-responses.md).

**400 Bad Request** - Erreur de validation (prompt manquant ou vide)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "prompt: This value should not be blank."
  ]
}
```

**400 Bad Request** - Inventaire vide
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "No ingredients available in inventory"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

**401 Unauthorized** - Token expiré
```json
{
  "code": 401,
  "message": "Expired JWT Token"
}
```

**403 Forbidden** - L'utilisateur n'est pas un Client
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be a client"
}
```

**500 Internal Server Error** - Erreur lors de l'appel à Gemini
```json
{
  "code": 500,
  "error": "Internal Server Error",
  "message": "Failed to generate recipe",
  "details": {
    "message": "Empty response from Gemini"
  }
}
```

## Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Authentification** : 
  - Un token JWT valide doit être fourni dans l'en-tête `Authorization`
  - Le token doit être obtenu via `POST /user/login`
  - Le token doit être au format `Bearer <token>`
  - Le token ne doit pas être expiré
- **Type d'utilisateur** : L'utilisateur connecté doit être une instance de `Client` (hérite de `User`)
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Inventaire requis** : L'utilisateur doit avoir au moins un item dans son inventaire pour générer une recette
- **Service externe** : Cette route dépend de l'API Gemini (Google Generative AI)
  - La clé API `GEMINI_KEY` doit être configurée dans le fichier `.env`
  - Un proxy peut être nécessaire selon la localisation (voir `../../env/README.md`)
- **Format de réponse** : La réponse de Gemini est structurée selon un schéma JSON strict
- **Gestion des ingrédients** :
  - Les ingrédients non quantifiables (sel, farine, huile, épices) sont considérés comme disponibles à volonté
  - Les ingrédients dénombrables (pommes, œufs) ne peuvent pas dépasser le stock disponible

## Logique métier

1. **Récupération du stock** : La route récupère automatiquement tous les items de l'inventaire du client connecté
2. **Format du stock** : Les items sont convertis au format `{item_name: quantity}` où :
   - La clé est le nom de l'item
   - La valeur est la quantité disponible
3. **Construction du prompt** : Le service `RecipeRequestFormat` construit un prompt détaillé incluant :
   - Le stock disponible au format JSON
   - La demande de l'utilisateur
   - Les règles de gestion des ingrédients
   - Les instructions pour le calcul du score de pertinence
4. **Appel à Gemini** : Le prompt est envoyé à l'API Gemini avec un schéma JSON strict pour garantir un format de réponse cohérent
5. **Retour de la recette** : La recette générée est retournée directement au client

## Exemples d'utilisation

### Exemple 1 : Recette simple
```json
{
  "prompt": "Donne-moi une recette simple et rapide"
}
```

### Exemple 2 : Recette végétarienne
```json
{
  "prompt": "Je veux une recette végétarienne avec mes ingrédients disponibles"
}
```

### Exemple 3 : Dessert
```json
{
  "prompt": "Propose-moi un dessert"
}
```

### Exemple 4 : Recette spécifique
```json
{
  "prompt": "Je veux faire une tarte avec mes pommes"
}
```

## Utilisation du token

Pour utiliser cette route, vous devez d'abord obtenir un token JWT :

1. **Obtenir un token** via `POST /user/login` :
   ```json
   {
     "email": "client@example.com",
     "password": "motdepasse123"
   }
   ```

2. **Copier le token** de la réponse :
   ```json
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
   }
   ```

3. **Inclure le token** dans l'en-tête `Authorization` :
   ```
   Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
   ```

## Exemple d'utilisation complète

```bash
# 1. Obtenir un token
curl -X POST http://localhost:8000/user/login \
  -H "Content-Type: application/json" \
  -d '{"email": "client@example.com", "password": "motdepasse123"}'

# Réponse: {"token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}

# 2. Ajouter des items à l'inventaire (si nécessaire)
curl -X POST http://localhost:8000/api/inventories/add \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"itemId": 1, "quantity": 5}'

# 3. Générer une recette
curl -X POST http://localhost:8000/api/recipe/generate \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"prompt": "Donne-moi une recette simple"}'
```

## Relations entre les données

- **Inventaire** : La route utilise l'inventaire du client connecté (table `Inventory`) pour récupérer les ingrédients disponibles
- **Items** : Les items sont récupérés via la relation `Inventory -> Item` pour obtenir les noms des ingrédients
- **Gemini API** : Le service `RecipeRequestFormat` utilise `GeminiRequest` pour communiquer avec l'API Google Gemini
- **Schéma JSON** : La réponse de Gemini est contrainte par un schéma JSON strict pour garantir la cohérence des données

## Notes importantes

- **Performance** : L'appel à Gemini peut prendre plusieurs secondes selon la complexité de la demande
- **Coûts** : Chaque génération de recette consomme des crédits de l'API Gemini
- **Stock requis** : Il est recommandé d'avoir au moins quelques ingrédients dans l'inventaire pour obtenir des recettes pertinentes
- **Score de pertinence** : Le `matching` permet d'évaluer la qualité de la recette générée par rapport à la demande et au stock disponible
- **Ingrédients à volonté** : Les ingrédients de base (sel, farine, huile, épices) sont toujours considérés comme disponibles même s'ils ne sont pas dans l'inventaire

