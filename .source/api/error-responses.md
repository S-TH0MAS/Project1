# Documentation - Format uniformisé des réponses d'erreur

## Vue d'ensemble

Toutes les routes de l'API utilisent un format uniformisé pour les réponses d'erreur. Ce format garantit une cohérence dans la gestion des erreurs et facilite le traitement côté client.

## Format standard

Toutes les erreurs suivent le format JSON suivant :

```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Description détaillée de l'erreur",
  "details": {}
}
```

### Champs de la réponse

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `code` | integer | Oui | Code HTTP de l'erreur (400, 401, 403, 404, 409, 500, etc.) |
| `error` | string | Oui | Nom court de l'erreur (ex: "Bad Request", "Validation Error", "Not Found") |
| `message` | string | Non | Description plus détaillée de l'erreur (optionnel mais recommandé) |
| `details` | array/object | Non | Détails supplémentaires, souvent utilisés pour les erreurs de validation avec la liste des champs invalides |

## Types d'erreurs

### 1. Erreurs de validation (400 Bad Request)

Les erreurs de validation sont retournées lorsque les données de la requête ne respectent pas les contraintes définies dans les DTOs.

**Format standard :**
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "name: This value should not be blank.",
    "email: This value is not a valid email address."
  ]
}
```

**Cas particuliers :**
- Si le JSON est invalide ou malformé, le message sera "Invalid JSON" ou similaire
- Si des champs requis sont manquants, les détails listent les champs concernés

### 2. Erreurs d'authentification (401 Unauthorized)

Ces erreurs sont gérées par le système d'authentification JWT de Symfony et peuvent avoir un format légèrement différent :

**Token manquant :**
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

**Token expiré :**
```json
{
  "code": 401,
  "message": "Expired JWT Token"
}
```

**Token invalide :**
```json
{
  "code": 401,
  "message": "Invalid JWT Token"
}
```

**Identifiants incorrects (login) :**
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

### 3. Erreurs d'autorisation (403 Forbidden)

Ces erreurs indiquent que l'utilisateur n'a pas les permissions nécessaires.

**Format standard :**
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be a client"
}
```

**Accès refusé (rôle insuffisant) :**
```json
{
  "code": 403,
  "message": "Access Denied."
}
```

### 4. Erreurs de ressource non trouvée (404 Not Found)

Ces erreurs indiquent qu'une ressource demandée n'existe pas.

**Format standard :**
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Item not found"
}
```

### 5. Erreurs de conflit (409 Conflict)

Ces erreurs indiquent un conflit avec l'état actuel de la ressource (ex: email déjà utilisé).

**Format standard :**
```json
{
  "code": 409,
  "error": "Conflict",
  "message": "User with this email already exists"
}
```

### 6. Erreurs serveur (500 Internal Server Error)

Ces erreurs indiquent un problème côté serveur.

**Format standard :**
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

## Codes HTTP utilisés

| Code | Signification | Utilisation |
|------|---------------|-------------|
| 400 | Bad Request | Données invalides, validation échouée, JSON malformé |
| 401 | Unauthorized | Token manquant, expiré, invalide, identifiants incorrects |
| 403 | Forbidden | Permissions insuffisantes, utilisateur n'est pas un Client |
| 404 | Not Found | Ressource (item, catégorie, etc.) non trouvée |
| 409 | Conflict | Conflit avec l'état actuel (ex: email déjà utilisé) |
| 500 | Internal Server Error | Erreur serveur, échec d'appel API externe |

## Exemples par contexte

### Erreur de validation avec détails

**Requête :**
```json
POST /api/items/add
{
  "name": "",
  "category": "invalid"
}
```

**Réponse :**
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "name: This value should not be blank.",
    "category: Category not found"
  ]
}
```

### Erreur de ressource non trouvée

**Requête :**
```json
POST /api/inventories/add
{
  "itemId": 999,
  "quantity": 5
}
```

**Réponse :**
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Item not found"
}
```

### Erreur de conflit

**Requête :**
```json
POST /user/create
{
  "email": "existing@example.com",
  "password": "password123",
  "name": "New User"
}
```

**Réponse :**
```json
{
  "code": 409,
  "error": "Conflict",
  "message": "User with this email already exists"
}
```

## Implémentation technique

### Trait ApiResponseTrait

Tous les contrôleurs utilisent le trait `ApiResponseTrait` qui fournit deux méthodes principales :

1. **`jsonError(int $status, string $error, ?string $message = null, array $details = [])`**
   - Retourne une réponse d'erreur formatée
   - Utilisé pour les erreurs métier explicites

2. **`jsonException(\Throwable $exception)`**
   - Gère automatiquement les exceptions
   - Détecte les `ValidationException` et les erreurs HTTP de Symfony
   - Formate les erreurs génériques

### ValidationException

Les erreurs de validation sont levées via `ValidationException` qui contient :
- Un message d'erreur
- Un tableau de détails avec les violations de contraintes

### Exemple d'utilisation dans un contrôleur

```php
use App\Trait\ApiResponseTrait;

class ItemController extends AbstractController
{
    use ApiResponseTrait;

    public function add(Request $request, RequestValidator $requestValidator): JsonResponse
    {
        try {
            $dto = $requestValidator->validate($request->getContent(), AddItemDto::class);
        } catch (\Exception $e) {
            return $this->jsonException($e); // Formatage automatique
        }

        if (!$item) {
            return $this->jsonError(
                Response::HTTP_NOT_FOUND,
                'Not Found',
                'Item not found'
            );
        }
    }
}
```

## Bonnes pratiques

1. **Toujours inclure un message** : Même si optionnel, un message descriptif améliore l'expérience développeur
2. **Utiliser les détails pour la validation** : Les erreurs de validation doivent inclure la liste des violations dans `details`
3. **Cohérence des codes HTTP** : Utiliser les codes HTTP appropriés selon le type d'erreur
4. **Messages clairs** : Les messages doivent être compréhensibles sans connaissance du code source
5. **Détails structurés** : Pour les erreurs complexes, utiliser un objet dans `details` plutôt qu'un tableau simple

## Migration

Si vous rencontrez des erreurs avec l'ancien format (champ `error` seul), veuillez noter que toutes les routes ont été migrées vers ce format uniformisé. Les anciennes réponses avec uniquement `{"error": "message"}` ne sont plus utilisées.

## Références

- **Trait** : `src/Trait/ApiResponseTrait.php`
- **Exception de validation** : `src/Exception/ValidationException.php`
- **Service de validation** : `src/Service/Validator/RequestValidator.php`

