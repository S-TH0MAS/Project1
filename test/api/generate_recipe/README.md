# Tests API - Route Génération de Recettes

Ce dossier contient les fichiers de test pour la route de génération de recettes via Gemini.

## Fichiers disponibles

- **generate-recipe.http** : Tests pour générer une recette (`POST /api/generate_recipes`)

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

2. **S'authentifier** avant de tester la génération de recettes :
   - Exécutez d'abord une requête dans `../user/login.http` pour obtenir un token JWT
   - La réponse contiendra un objet JSON avec le champ `token` :
     ```json
     {
       "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
     }
     ```
   - Copiez la valeur du token (sans les guillemets) et remplacez `{{token}}` dans le fichier `generate-recipe.http`
   - **Important** : Le token expire après un certain temps, vous devrez peut-être vous reconnecter si vous obtenez une erreur 401

3. **Avoir des items dans l'inventaire** :
   - Utilisez la route `POST /api/inventories/add` (voir `../inventory/add-inventory.http`) pour ajouter des items à votre inventaire
   - La route nécessite au moins un item dans l'inventaire pour fonctionner

4. **Configuration Gemini** :
   - La clé API Gemini doit être configurée dans le fichier `.env` avec la variable `GEMINI_KEY`
   - Voir `../../.source/env/README.md` pour plus d'informations

## Réponses attendues

### Génération de recette (POST /api/generate_recipes)
- **200 OK** : Recette générée avec succès
  ```json
  {
    "recipe_name": "Tarte aux pommes",
    "matching_score": 95,
    "preparation_time_minutes": 45,
    "ingredients": [
      "5 Pommes",
      "200g de farine",
      "100g de beurre",
      "50g de sucre"
    ],
    "steps": [
      "Étape 1 : Préparer la pâte...",
      "Étape 2 : Éplucher et couper les pommes...",
      "Étape 3 : Assembler la tarte..."
    ]
  }
  ```

**Structure de la réponse :**
- `recipe_name` : Nom de la recette (string)
- `matching_score` : Score de pertinence entre 0 et 100 (integer)
- `preparation_time_minutes` : Temps de préparation en minutes (integer)
- `ingredients` : Liste des ingrédients nécessaires (array de string)
- `steps` : Étapes de préparation (array de string)

- **400 Bad Request** : Prompt manquant ou vide, ou inventaire vide
  ```json
  {
    "error": "Prompt is required"
  }
  ```
  ou
  ```json
  {
    "error": "No ingredients available in inventory"
  }
  ```

- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client
- **500 Internal Server Error** : Erreur lors de l'appel à Gemini
  ```json
  {
    "error": "Failed to generate recipe",
    "message": "Description de l'erreur"
  }
  ```

## Paramètres

### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `prompt` | string | Oui | La demande de l'utilisateur pour la recette (ex: "Donne-moi une recette simple") |

### Exemples de requêtes

**Prompt simple :**
```json
{
  "prompt": "Donne-moi une recette simple et rapide"
}
```

**Prompt détaillé :**
```json
{
  "prompt": "Je veux une recette végétarienne avec mes ingrédients disponibles"
}
```

**Prompt pour un type de plat spécifique :**
```json
{
  "prompt": "Propose-moi un dessert"
}
```

## Notes importantes

- Cette route est **protégée** et nécessite une authentification JWT
- La route utilise l'inventaire du client connecté pour générer la recette
- Le service Gemini analyse les ingrédients disponibles et génère une recette adaptée
- Le `matching_score` indique à quel point la recette correspond à la demande et utilise bien le stock disponible
- Les ingrédients non quantifiables (sel, farine, huile, épices) sont considérés comme disponibles à volonté
- Les ingrédients dénombrables (pommes, œufs) ne peuvent pas dépasser le stock disponible
- Si l'inventaire est vide, la route retournera une erreur 400

## Variables d'environnement

Assurez-vous que les variables d'environnement suivantes sont configurées dans votre fichier `.env` :
- `GEMINI_KEY` : Clé API pour accéder à l'API Google Gemini
- `HTTP_PROXY` : (Optionnel) Proxy pour accéder à Gemini si nécessaire

Pour plus d'informations, consultez `../../.source/env/README.md`.

