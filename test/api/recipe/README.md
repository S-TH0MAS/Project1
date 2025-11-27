# Tests API - Routes Recettes

Ce dossier contient les fichiers de test pour les routes de gestion des recettes.

## Fichiers disponibles

- **generate-recipes.http** : Tests pour générer une recette (`POST /api/generate_recipes`)
- **save-recipe.http** : Tests pour sauvegarder une recette (`POST /api/save_recipe`)
- **recipe-get.http** : Tests pour récupérer des recettes (`POST /api/recipe_get`)

## Utilisation dans PHPStorm

1. **Ouvrir un fichier .http** dans PHPStorm
2. **Configurer l'URL de base** :
   - Par défaut : `http://localhost:8000`
   - Modifiez la variable `@baseUrl` si votre serveur tourne sur un autre port
3. **Configurer le token JWT** :
   - Obtenez un token via `POST /user/login` (voir `../user/login.http`)
   - Remplacez `{{token}}` par votre token JWT dans les requêtes
4. **Exécuter une requête** :
   - Cliquez sur le bouton ▶️ à côté de la requête
   - Ou utilisez `Ctrl+Enter` (Windows/Linux) ou `Cmd+Enter` (Mac)

## Prérequis

1. **Démarrer le serveur Symfony** :
   ```bash
   php -S localhost:8000 -t public
   ```
   Ou avec Symfony CLI :
   ```bash
   symfony server:start
   ```

2. **S'authentifier** avant de tester les routes :
   - Exécutez d'abord une requête dans `../user/login.http` pour obtenir un token JWT
   - La réponse contiendra un objet JSON avec le champ `token` :
     ```json
     {
       "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
     }
     ```
   - Copiez la valeur du token (sans les guillemets) et remplacez `{{token}}` dans les fichiers de test
   - **Important** : Le token expire après un certain temps, vous devrez peut-être vous reconnecter si vous obtenez une erreur 401

3. **Avoir des items dans l'inventaire** (pour `/generate_recipes`) :
   - Utilisez la route `POST /api/inventories/add` (voir `../inventory/add-inventory.http`) pour ajouter des items à votre inventaire
   - La route nécessite au moins un item dans l'inventaire pour fonctionner

4. **Configuration Gemini** (pour `/generate_recipes`) :
   - La clé API Gemini doit être configurée dans le fichier `.env` avec la variable `GEMINI_KEY`
   - Voir `../../.source/env/README.md` pour plus d'informations

## Réponses attendues

### Génération de recette (POST /api/generate_recipes)
- **200 OK** : Recette générée avec succès
  ```json
  {
    "name": "Tarte aux pommes",
    "description": "Une délicieuse tarte aux pommes maison",
    "matching": 95,
    "preparation_time": 45,
    "ingredients": ["5 Pommes", "200g de farine", "100g de beurre"],
    "steps": ["Étape 1", "Étape 2"],
    "cache_key": "recipe_550e8400-e29b-41d4-a716-446655440000"
  }
  ```

- **400 Bad Request** : Prompt manquant ou vide, ou inventaire vide
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client
- **500 Internal Server Error** : Erreur lors de l'appel à Gemini

### Sauvegarde de recette (POST /api/save_recipe)
- **201 Created** : Recette sauvegardée avec succès
  ```json
  {
    "message": "Recipe saved successfully",
    "recipe_id": 1
  }
  ```

- **400 Bad Request** : Cache key manquant, vide ou invalide
- **404 Not Found** : Recette non trouvée dans le cache ou cache expiré
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

### Récupération de recettes (POST /api/recipe_get)
- **200 OK** : Recettes récupérées avec succès
  ```json
  {
    "recipes": [
      {
        "id": 1,
        "name": "Tarte aux pommes",
        "description": "Une délicieuse tarte aux pommes maison",
        "matching": 95,
        "preparation_time": 45,
        "ingredients": ["5 Pommes", "200g de farine", "100g de beurre"],
        "steps": ["Étape 1", "Étape 2"],
        "date": 1635876540,
        "image": null,
        "author_id": 1
      }
    ]
  }
  ```

- **400 Bad Request** : Paramètres manquants, invalides ou négatifs
- **401 Unauthorized** : Token manquant ou invalide

## Paramètres

### POST /api/generate_recipes

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `prompt` | string | Oui | La demande de l'utilisateur pour la recette (ex: "Donne-moi une recette simple") |

### POST /api/save_recipe

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cache_key` | string | Oui | La clé de cache obtenue depuis la réponse de `/generate_recipes` (ex: "recipe_12345678-1234-1234-1234-123456789abc") |

### POST /api/recipe_get

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `quantity` | integer | Oui | Nombre de recettes à récupérer (doit être un entier positif) |
| `offset` | integer | Oui | Nombre de recettes à ignorer avant de commencer à récupérer (doit être >= 0) |

## Notes importantes

### Génération de recette (POST /api/generate_recipes)
- Cette route est **protégée** et nécessite une authentification JWT
- La route utilise l'inventaire du client connecté pour générer la recette
- Le service Gemini analyse les ingrédients disponibles et génère une recette adaptée
- Le `matching` indique à quel point la recette correspond à la demande et utilise bien le stock disponible
- Les ingrédients non quantifiables (sel, farine, huile, épices) sont considérés comme disponibles à volonté
- Les ingrédients dénombrables (pommes, œufs) ne peuvent pas dépasser le stock disponible
- Si l'inventaire est vide, la route retournera une erreur 400
- La réponse contient un `cache_key` qui peut être utilisé pour sauvegarder la recette via `/save_recipe`

### Sauvegarde de recette (POST /api/save_recipe)
- Cette route est **protégée** et nécessite une authentification JWT
- **Important** : Vous devez d'abord générer une recette via `/generate_recipes` pour obtenir un `cache_key` valide
- Le `cache_key` est valide pendant 1 heure après la génération de la recette
- La recette est sauvegardée en base de données avec le client actuel comme auteur
- La recette est automatiquement ajoutée aux favoris du client
- Si le cache a expiré ou si la clé est invalide, la route retournera une erreur 404

### Récupération de recettes (POST /api/recipe_get)
- Cette route est **protégée** et nécessite une authentification JWT
- **Pagination** : Utilisez `quantity` pour limiter le nombre de résultats et `offset` pour la pagination
- Les recettes sont triées par ID croissant
- Si aucune recette n'est trouvée, la réponse contiendra un tableau vide `{"recipes": []}`
- **Exemples d'utilisation** :
  - `{"quantity": 10, "offset": 0}` : Récupère les 10 premières recettes
  - `{"quantity": 10, "offset": 10}` : Récupère les 10 recettes suivantes (page 2)
  - `{"quantity": 5, "offset": 0}` : Récupère les 5 premières recettes

## Variables d'environnement

Assurez-vous que les variables d'environnement suivantes sont configurées dans votre fichier `.env` :
- `GEMINI_KEY` : Clé API pour accéder à l'API Google Gemini (requis pour `/generate_recipes`)
- `HTTP_PROXY` : (Optionnel) Proxy pour accéder à Gemini si nécessaire

Pour plus d'informations, consultez `../../.source/env/README.md`.

