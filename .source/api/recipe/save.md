# Sauvegarde de recette

## Route
```
POST /api/recipe/save
```

## Méthode
`POST`

## Description
Cette route permet de sauvegarder une recette générée précédemment via `/api/recipe/generate` en base de données. La recette est récupérée depuis le cache à l'aide de la clé `cache_key` fournie dans la réponse de génération. Une fois sauvegardée, la recette est automatiquement ajoutée aux favoris du client connecté et l'auteur de la recette est défini comme étant le client actuel.

## Paramètres

### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `cache_key` | string | Oui | La clé de cache obtenue depuis la réponse de `/api/recipe/generate` (format: `recipe_<uuid>`) |

### Exemple de requête
```json
{
  "cache_key": "recipe_550e8400-e29b-41d4-a716-446655440000"
}
```

## Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Exemple de requête complète
```http
POST /api/recipe/save
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json

{
  "cache_key": "recipe_550e8400-e29b-41d4-a716-446655440000"
}
```

## Retour

### Succès (201 Created)
```json
{
  "message": "Recipe saved successfully",
  "recipe_id": 1
}
```

**Structure de la réponse :**

- `message` : Message de confirmation (string)
- `recipe_id` : Identifiant unique de la recette sauvegardée en base de données (integer)

> **Note** : La recette est sauvegardée avec les données suivantes :
> - `name`, `description`, `matching`, `preparation_time`, `ingredients`, `steps` : Récupérés depuis le cache
> - `author` : Le client connecté
> - `date` : Timestamp actuel (date de création)
> - `image` : `null` par défaut
> 
> La recette est automatiquement ajoutée aux favoris du client via la relation ManyToMany.

## Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](../error-responses.md).

**400 Bad Request** - Cache key manquant ou vide
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "cache_key: This value should not be blank."
  ]
}
```

**400 Bad Request** - Cache key invalide
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Invalid cache key: ..."
}
```

**400 Bad Request** - Données de recette incomplètes dans le cache
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Missing required field in cached recipe: name"
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

**404 Not Found** - Recette non trouvée dans le cache ou cache expiré
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Recipe not found in cache or cache expired"
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
- **Cache requis** : La recette doit avoir été générée précédemment via `/api/recipe/generate` et être toujours présente dans le cache
  - Le cache expire après **1 heure** (3600 secondes)
  - Le `cache_key` doit être valide et correspondre à une recette existante dans le cache
- **Format du cache_key** : Doit commencer par `recipe_` suivi d'un UUID v4

## Logique métier

1. **Validation du cache_key** : Le `cache_key` est validé pour s'assurer qu'il est présent et non vide
2. **Récupération depuis le cache** : La recette est récupérée depuis le cache Symfony à l'aide de la clé fournie
3. **Vérification des données** : Tous les champs requis (`name`, `description`, `matching`, `preparation_time`, `ingredients`, `steps`) sont vérifiés
4. **Création de l'entité** : Une nouvelle instance de `Recipe` est créée avec les données du cache
5. **Définition de l'auteur** : Le client connecté est défini comme auteur de la recette
6. **Date de création** : Le timestamp actuel est assigné à la date de création
7. **Persistance** : La recette est sauvegardée en base de données
8. **Ajout aux favoris** : La recette est automatiquement ajoutée aux favoris du client via la relation ManyToMany

## Exemples d'utilisation

### Exemple 1 : Sauvegarder une recette générée
```bash
# 1. Générer une recette
curl -X POST http://localhost:8000/api/recipe/generate \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"prompt": "Donne-moi une recette simple"}'

# Réponse: {"name": "...", "cache_key": "recipe_550e8400-e29b-41d4-a716-446655440000", ...}

# 2. Sauvegarder la recette
curl -X POST http://localhost:8000/api/recipe/save \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"cache_key": "recipe_550e8400-e29b-41d4-a716-446655440000"}'

# Réponse: {"message": "Recipe saved successfully", "recipe_id": 1}
```

## Relations entre les données

- **Cache** : La recette est récupérée depuis le cache Symfony (clé: `cache_key`)
- **Entité Recipe** : Une nouvelle instance de `Recipe` est créée et persistée en base de données
- **Client (Author)** : Le client connecté est défini comme auteur de la recette via la relation `Recipe -> Client` (ManyToOne)
- **Favoris** : La recette est ajoutée aux favoris du client via la relation ManyToMany `Client <-> Recipe`

## Notes importantes

- **Workflow recommandé** : 
  1. Générer une recette via `/api/recipe/generate`
  2. Récupérer le `cache_key` de la réponse
  3. Sauvegarder la recette via `/api/recipe/save` avec le `cache_key`
- **Expiration du cache** : Le cache expire après 1 heure. Si vous tentez de sauvegarder une recette après expiration, vous obtiendrez une erreur 404
- **Favoris automatiques** : La recette est automatiquement ajoutée aux favoris du client lors de la sauvegarde
- **Auteur** : Le client qui sauvegarde la recette devient automatiquement l'auteur de celle-ci
- **Duplication** : Chaque appel à `/api/recipe/save` crée une nouvelle entité Recipe, même si le `cache_key` est le même. Il est possible de sauvegarder plusieurs fois la même recette générée

