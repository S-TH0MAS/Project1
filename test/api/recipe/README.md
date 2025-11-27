# Tests API - Routes Recettes

Ce dossier contient les fichiers de test pour les routes de gestion des recettes.

## Fichiers disponibles

- **generate-recipes.http** : Tests pour générer une recette (`POST /api/recipe/generate`)
- **save-recipe.http** : Tests pour sauvegarder une recette (`POST /api/recipe/save`)
- **recipe-get.http** : Tests pour récupérer des recettes (`POST /api/recipe/get`) - inclut les recettes favorites
- **delete-recipe.http** : Tests pour supprimer une recette (`DELETE /api/recipe/{id}`)

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

3. **Avoir des items dans l'inventaire** (pour `/api/recipe/generate`) :
   - Utilisez la route `POST /api/inventories/add` (voir `../inventory/add-inventory.http`) pour ajouter des items à votre inventaire
   - La route nécessite au moins un item dans l'inventaire pour fonctionner

4. **Avoir une recette sauvegardée** (pour `/api/recipe/{id}`) :
   - Utilisez les routes `POST /api/recipe/generate` et `POST /api/recipe/save` pour créer une recette
   - La recette doit être créée par le client connecté pour pouvoir être supprimée

5. **Configuration Gemini** (pour `/api/recipe/generate`) :
   - La clé API Gemini doit être configurée dans le fichier `.env` avec la variable `GEMINI_KEY`
   - Voir `../../.source/env/README.md` pour plus d'informations

## Réponses attendues

### Génération de recette (POST /api/recipe/generate)
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

### Sauvegarde de recette (POST /api/recipe/save)
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

### Récupération de recettes (POST /api/recipe/get)
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
        "author": {
          "id": 1,
          "name": "Jean Dupont"
        }
      }
    ]
  }
  ```

- **400 Bad Request** : Paramètres manquants, invalides, négatifs ou quantity supérieur à 100
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client (si mode = favorite ou author)

## Paramètres

### POST /api/recipe/generate

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `prompt` | string | Oui | La demande de l'utilisateur pour la recette (ex: "Donne-moi une recette simple") |

### POST /api/recipe/save

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cache_key` | string | Oui | La clé de cache obtenue depuis la réponse de `/generate_recipes` (ex: "recipe_12345678-1234-1234-1234-123456789abc") |

### POST /api/recipe/get

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `quantity` | integer | Oui | Nombre de recettes à récupérer (doit être un entier positif, maximum 100) |
| `offset` | integer | Non | Nombre de recettes à ignorer avant de commencer à récupérer (doit être >= 0, défaut: 0) |
| `mode` | string | Non | Mode de récupération : `all` (toutes les recettes), `favorite` (recettes favorites du client), ou `author` (recettes dont le client est l'auteur). Défaut: `all` |

### DELETE /api/recipe/{id}

#### Path Parameters
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant unique de la recette à supprimer |

## Notes importantes

### Génération de recette (POST /api/recipe/generate)
- Cette route est **protégée** et nécessite une authentification JWT
- La route utilise l'inventaire du client connecté pour générer la recette
- Le service Gemini analyse les ingrédients disponibles et génère une recette adaptée
- Le `matching` indique à quel point la recette correspond à la demande et utilise bien le stock disponible
- Les ingrédients non quantifiables (sel, farine, huile, épices) sont considérés comme disponibles à volonté
- Les ingrédients dénombrables (pommes, œufs) ne peuvent pas dépasser le stock disponible
- Si l'inventaire est vide, la route retournera une erreur 400
- La réponse contient un `cache_key` qui peut être utilisé pour sauvegarder la recette via `/api/recipe/save`

### Sauvegarde de recette (POST /api/recipe/save)
- Cette route est **protégée** et nécessite une authentification JWT
- **Important** : Vous devez d'abord générer une recette via `/api/recipe/generate` pour obtenir un `cache_key` valide
- Le `cache_key` est valide pendant 1 heure après la génération de la recette
- La recette est sauvegardée en base de données avec le client actuel comme auteur
- La recette est automatiquement ajoutée aux favoris du client
- Si le cache a expiré ou si la clé est invalide, la route retournera une erreur 404

### Récupération de recettes (POST /api/recipe/get)
- Cette route est **protégée** et nécessite une authentification JWT
- **Pagination** : Utilisez `quantity` pour limiter le nombre de résultats et `offset` pour la pagination
  - La pagination est effectuée au niveau SQL pour une meilleure performance
- **Offset optionnel** : Si `offset` n'est pas fourni, il est défini à 0 par défaut
- **Mode optionnel** : Si `mode` n'est pas fourni, il est défini à `all` par défaut
- **Modes disponibles** :
  - `all` : Retourne toutes les recettes triées par ID croissant (défaut)
  - `favorite` : Retourne uniquement les recettes favorites du client connecté avec pagination SQL
  - `author` : Retourne uniquement les recettes dont le client connecté est l'auteur avec pagination SQL
- **Client requis** : Si `mode` est `favorite` ou `author`, l'utilisateur connecté doit être une instance de `Client`
- **Tri** : Tous les modes trient les recettes par ID croissant pour garantir un ordre cohérent
- **Optimisations** :
  - Les auteurs sont préchargés pour éviter le problème N+1
  - La pagination est effectuée au niveau SQL (pas de chargement en mémoire de toutes les recettes)
- Si aucune recette n'est trouvée, la réponse contiendra un tableau vide `{"recipes": []}`
- **Exemples d'utilisation** :
  - `{"quantity": 10}` : Récupère les 10 premières recettes (offset = 0, mode = all par défaut)
  - `{"quantity": 10, "offset": 10}` : Récupère les 10 recettes suivantes (page 2)
  - `{"quantity": 5, "mode": "favorite"}` : Récupère les 5 premières recettes favorites
  - `{"quantity": 10, "offset": 5, "mode": "favorite"}` : Récupère 10 recettes favorites à partir de l'offset 5
  - `{"quantity": 10, "mode": "author"}` : Récupère les 10 premières recettes dont le client est l'auteur

### Suppression de recette (DELETE /api/recipe/{id})
- Cette route est **protégée** et nécessite une authentification JWT
- **Autorisation** : Seul l'auteur de la recette peut la supprimer
- **Suppression définitive** : La suppression est définitive et ne peut pas être annulée
- **Favoris** : Si la recette était dans les favoris d'autres utilisateurs, elle sera automatiquement retirée (cascade Doctrine)
- **Exemple d'utilisation** :
  - `DELETE /api/recipe/1` : Supprime la recette avec l'ID 1 (si vous en êtes l'auteur)

## Variables d'environnement

Assurez-vous que les variables d'environnement suivantes sont configurées dans votre fichier `.env` :
- `GEMINI_KEY` : Clé API pour accéder à l'API Google Gemini (requis pour `/api/recipe/generate`)
- `HTTP_PROXY` : (Optionnel) Proxy pour accéder à Gemini si nécessaire

Pour plus d'informations, consultez `../../.source/env/README.md`.

