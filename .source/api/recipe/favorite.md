# Gestion des favoris de recettes

## Routes
```
GET /api/recipe/favorite/add/{id}
GET /api/recipe/favorite/remove/{id}
```

## Méthode
`GET`

## Description
Ces routes permettent d'ajouter ou de retirer une recette des favoris du client connecté. Seuls les clients authentifiés peuvent utiliser ces routes.

## Paramètres

### Path Parameters
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant unique de la recette à ajouter ou retirer des favoris |

### Exemple de requête - Ajouter aux favoris
```http
GET /api/recipe/favorite/add/1
```

### Exemple de requête - Retirer des favoris
```http
GET /api/recipe/favorite/remove/1
```

## Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |

### Exemple de requête complète - Ajouter aux favoris
```http
GET /api/recipe/favorite/add/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
```

### Exemple de requête complète - Retirer des favoris
```http
GET /api/recipe/favorite/remove/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
```

## Retour

### Succès - Ajouter aux favoris (200 OK)
```json
{
  "message": "Recipe added to favorites successfully"
}
```

### Succès - Retirer des favoris (200 OK)
```json
{
  "message": "Recipe removed from favorites successfully"
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

**400 Bad Request** - Recette déjà en favoris (pour `/add/{id}`)
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Recipe is already in favorites"
}
```

**400 Bad Request** - Recette non en favoris (pour `/remove/{id}`)
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Recipe is not in favorites"
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

**404 Not Found** - Recette non trouvée
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Recipe not found"
}
```

## Contraintes

- **Sécurité** : Ces routes sont protégées et nécessitent une authentification JWT
- **Authentification** : 
  - Un token JWT valide doit être fourni dans l'en-tête `Authorization`
  - Le token doit être obtenu via `POST /user/login`
  - Le token doit être au format `Bearer <token>`
  - Le token ne doit pas être expiré
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Type d'utilisateur** : L'utilisateur connecté doit être une instance de `Client` (hérite de `User`)
- **Duplication** : Une recette ne peut pas être ajoutée deux fois aux favoris
- **Suppression** : On ne peut retirer des favoris qu'une recette qui y est déjà

## Logique métier

### Ajouter aux favoris (`/add/{id}`)

1. **Validation de l'utilisateur** : Vérification que l'utilisateur est authentifié et est une instance de `Client`
2. **Récupération de la recette** : Recherche de la recette par son ID dans la base de données
3. **Vérification de l'existence** : Si la recette n'existe pas, retour d'une erreur 404
4. **Vérification de la duplication** : Si la recette est déjà dans les favoris, retour d'une erreur 400
5. **Ajout aux favoris** : Ajout de la recette aux favoris du client via la relation ManyToMany
6. **Retour de confirmation** : Retour d'un message de succès

### Retirer des favoris (`/remove/{id}`)

1. **Validation de l'utilisateur** : Vérification que l'utilisateur est authentifié et est une instance de `Client`
2. **Récupération de la recette** : Recherche de la recette par son ID dans la base de données
3. **Vérification de l'existence** : Si la recette n'existe pas, retour d'une erreur 404
4. **Vérification de la présence** : Si la recette n'est pas dans les favoris, retour d'une erreur 400
5. **Retrait des favoris** : Retrait de la recette des favoris du client via la relation ManyToMany
6. **Retour de confirmation** : Retour d'un message de succès

## Exemples d'utilisation

### Exemple 1 : Ajouter une recette aux favoris
```bash
curl -X GET http://localhost:8000/api/recipe/favorite/add/1 \
  -H "Authorization: Bearer <token>"
```

### Exemple 2 : Retirer une recette des favoris
```bash
curl -X GET http://localhost:8000/api/recipe/favorite/remove/1 \
  -H "Authorization: Bearer <token>"
```

## Relations entre les données

- **Entité Recipe** : La recette est récupérée depuis la table `Recipe` en base de données
- **Client (Favoris)** : La relation ManyToMany `Client <-> Recipe` est utilisée pour gérer les favoris
- **Bidirectionnelle** : La relation est bidirectionnelle, permettant de naviguer depuis le Client vers ses favoris et depuis la Recipe vers les clients qui l'ont mise en favoris

## Notes importantes

- **Idempotence** : 
  - Ajouter une recette déjà en favoris retournera une erreur 400
  - Retirer une recette non en favoris retournera une erreur 400
- **Pas de limite** : Un client peut avoir autant de recettes en favoris qu'il le souhaite
- **Indépendance** : Les favoris sont indépendants pour chaque client (un client ne peut pas voir les favoris d'un autre)
- **ID valide** : L'ID doit être un entier positif. Les valeurs non numériques retourneront une erreur 400
- **Méthode GET** : Bien que ces routes utilisent la méthode GET, elles modifient l'état (ajout/retrait de favoris). C'est une exception pour simplifier l'utilisation côté client

