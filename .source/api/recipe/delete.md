# Suppression de recette

## Route
```
DELETE /api/recipe/{id}
```

## Méthode
`DELETE`

## Description
Cette route permet de supprimer une recette sauvegardée. Seul l'auteur de la recette peut la supprimer. La suppression est définitive et ne peut pas être annulée.

## Paramètres

### Path Parameters
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant unique de la recette à supprimer |

### Exemple de requête
```http
DELETE /api/recipe/1
```

## Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |

### Exemple de requête complète
```http
DELETE /api/recipe/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
```

## Retour

### Succès (200 OK)
```json
{
  "message": "Recipe deleted successfully"
}
```

## Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](../error-responses.md).

**400 Bad Request** - ID invalide (non numérique)
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Invalid ID format"
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

**403 Forbidden** - L'utilisateur n'est pas l'auteur de la recette
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "You can only delete your own recipes"
}
```

**404 Not Found** - Recette non trouvée
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Recipe not found"
}
```

## Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Authentification** : 
  - Un token JWT valide doit être fourni dans l'en-tête `Authorization`
  - Le token doit être obtenu via `POST /user/login`
  - Le token doit être au format `Bearer <token>`
  - Le token ne doit pas être expiré
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Type d'utilisateur** : L'utilisateur connecté doit être une instance de `Client` (hérite de `User`)
- **Autorisation** : Seul l'auteur de la recette peut la supprimer
- **Suppression définitive** : La suppression est définitive et ne peut pas être annulée
- **Cascade** : Si la recette était dans les favoris d'autres utilisateurs, elle sera automatiquement retirée (cascade Doctrine)

## Logique métier

1. **Validation de l'utilisateur** : Vérification que l'utilisateur est authentifié et est une instance de `Client`
2. **Récupération de la recette** : Recherche de la recette par son ID dans la base de données
3. **Vérification de l'existence** : Si la recette n'existe pas, retour d'une erreur 404
4. **Vérification des permissions** : Vérification que l'utilisateur connecté est l'auteur de la recette
5. **Suppression** : Suppression de la recette de la base de données
6. **Retour de confirmation** : Retour d'un message de succès

## Exemples d'utilisation

### Exemple 1 : Supprimer une recette
```bash
curl -X DELETE http://localhost:8000/api/recipe/1 \
  -H "Authorization: Bearer <token>"
```

### Exemple 2 : Supprimer une autre recette
```bash
curl -X DELETE http://localhost:8000/api/recipe/5 \
  -H "Authorization: Bearer <token>"
```

## Relations entre les données

- **Entité Recipe** : La recette est supprimée de la table `Recipe` en base de données
- **Client (Author)** : La relation `Recipe -> Client` (ManyToOne) est supprimée
- **Favoris** : Si la recette était dans les favoris d'autres utilisateurs, elle sera automatiquement retirée via la relation ManyToMany `Client <-> Recipe`

## Notes importantes

- **Autorisation stricte** : Seul l'auteur de la recette peut la supprimer. Si vous essayez de supprimer une recette créée par un autre utilisateur, vous recevrez une erreur 403
- **Suppression définitive** : La suppression est définitive et ne peut pas être annulée. Assurez-vous de vouloir vraiment supprimer la recette avant d'appeler cette route
- **Favoris** : Si la recette était dans les favoris d'autres utilisateurs, elle sera automatiquement retirée de leurs favoris lors de la suppression
- **ID valide** : L'ID doit être un entier positif. Les valeurs non numériques retourneront une erreur 400

