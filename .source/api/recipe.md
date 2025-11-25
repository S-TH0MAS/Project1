# Documentation API - Routes Recettes

## 1. Génération de recette

### Route
```
POST /api/generate_recipes
```

### Méthode
`POST`

### Description
Cette route permet de générer une recette personnalisée basée sur les ingrédients disponibles dans l'inventaire du client connecté et sa demande. La génération utilise l'API Gemini (Google Generative AI) pour créer une recette adaptée au stock disponible et aux préférences de l'utilisateur.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `prompt` | string | Oui | La demande de l'utilisateur pour la recette (ex: "Donne-moi une recette simple", "Je veux un dessert") |

#### Exemple de requête
```json
{
  "prompt": "Donne-moi une recette simple et rapide avec mes ingrédients"
}
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

#### Exemple de requête complète
```http
POST /api/generate_recipes
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json

{
  "prompt": "Donne-moi une recette végétarienne"
}
```

### Retour

#### Succès (200 OK)
```json
{
  "recipe_name": "Tarte aux pommes",
  "matching_score": 95,
  "preparation_time_minutes": 45,
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
  ]
}
```

**Structure de la réponse :**

- `recipe_name` : Nom de la recette générée (string)
- `matching_score` : Score de pertinence entre 0 et 100 (integer)
  - **0** : La recette ne correspond pas du tout à la demande ou il manque trop d'ingrédients
  - **100** : La recette utilise parfaitement le stock et correspond exactement à l'envie de l'utilisateur
- `preparation_time_minutes` : Temps de préparation estimé en minutes (integer)
- `ingredients` : Liste des ingrédients nécessaires avec leurs quantités (array de string)
- `steps` : Étapes de préparation de la recette (array de string)

#### Erreurs possibles

**400 Bad Request** - Prompt manquant ou vide
```json
{
  "error": "Prompt is required"
}
```

**400 Bad Request** - Inventaire vide
```json
{
  "error": "No ingredients available in inventory"
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
  "error": "User must be a client"
}
```

**500 Internal Server Error** - Erreur lors de l'appel à Gemini
```json
{
  "error": "Failed to generate recipe",
  "message": "Empty response from Gemini"
}
```

### Contraintes

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
  - Un proxy peut être nécessaire selon la localisation (voir `.source/env/README.md`)
- **Format de réponse** : La réponse de Gemini est structurée selon un schéma JSON strict
- **Gestion des ingrédients** :
  - Les ingrédients non quantifiables (sel, farine, huile, épices) sont considérés comme disponibles à volonté
  - Les ingrédients dénombrables (pommes, œufs) ne peuvent pas dépasser le stock disponible

### Logique métier

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

### Exemples d'utilisation

#### Exemple 1 : Recette simple
```json
{
  "prompt": "Donne-moi une recette simple et rapide"
}
```

#### Exemple 2 : Recette végétarienne
```json
{
  "prompt": "Je veux une recette végétarienne avec mes ingrédients disponibles"
}
```

#### Exemple 3 : Dessert
```json
{
  "prompt": "Propose-moi un dessert"
}
```

#### Exemple 4 : Recette spécifique
```json
{
  "prompt": "Je veux faire une tarte avec mes pommes"
}
```

### Utilisation du token

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

### Exemple d'utilisation complète

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
curl -X POST http://localhost:8000/api/generate_recipes \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"prompt": "Donne-moi une recette simple"}'
```

### Relations entre les données

- **Inventaire** : La route utilise l'inventaire du client connecté (table `Inventory`) pour récupérer les ingrédients disponibles
- **Items** : Les items sont récupérés via la relation `Inventory -> Item` pour obtenir les noms des ingrédients
- **Gemini API** : Le service `RecipeRequestFormat` utilise `GeminiRequest` pour communiquer avec l'API Google Gemini
- **Schéma JSON** : La réponse de Gemini est contrainte par un schéma JSON strict pour garantir la cohérence des données

### Notes importantes

- **Performance** : L'appel à Gemini peut prendre plusieurs secondes selon la complexité de la demande
- **Coûts** : Chaque génération de recette consomme des crédits de l'API Gemini
- **Stock requis** : Il est recommandé d'avoir au moins quelques ingrédients dans l'inventaire pour obtenir des recettes pertinentes
- **Score de pertinence** : Le `matching_score` permet d'évaluer la qualité de la recette générée par rapport à la demande et au stock disponible
- **Ingrédients à volonté** : Les ingrédients de base (sel, farine, huile, épices) sont toujours considérés comme disponibles même s'ils ne sont pas dans l'inventaire

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON cohérent avec un champ `error` ou `message`
- **Dépendances externes** : Cette route dépend de l'API Gemini qui doit être accessible et correctement configurée
- **Configuration requise** : 
  - Variable d'environnement `GEMINI_KEY` doit être définie
  - Variable `HTTP_PROXY` peut être nécessaire selon la localisation (voir `.source/env/README.md`)

