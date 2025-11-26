# Documentation API - Routes Catégorie

## 1. Liste des catégories

### Route
```
GET /api/categories
```

### Méthode
`GET`

### Description
Cette route permet d'obtenir la liste de toutes les catégories disponibles dans le système. Les catégories sont utilisées pour classer les items.

### Paramètres

Aucun paramètre requis.

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

#### Exemple de requête
```http
GET /api/categories
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json
```

### Retour

#### Succès (200 OK)
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Fruits"
    },
    {
      "id": 2,
      "name": "Légumes"
    },
    {
      "id": 3,
      "name": "Viandes"
    }
  ],
  "count": 3
}
```

**Structure de la réponse :**
- `categories` : Tableau contenant toutes les catégories
  - `id` : Identifiant unique de la catégorie (integer)
  - `name` : Nom de la catégorie (string)
- `count` : Nombre total de catégories retournées (integer)

#### Cas particulier : Liste vide
Si aucune catégorie n'existe dans le système, la réponse sera :
```json
{
  "categories": [],
  "count": 0
}
```

#### Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](error-responses.md).

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

**401 Unauthorized** - Token invalide
```json
{
  "code": 401,
  "message": "Invalid JWT Token"
}
```

**403 Forbidden** - Accès refusé (si des restrictions de rôles sont appliquées)
```json
{
  "code": 403,
  "message": "Access Denied."
}
```

### Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Authentification** : 
  - Un token JWT valide doit être fourni dans l'en-tête `Authorization`
  - Le token doit être obtenu via `POST /user/login`
  - Le token doit être au format `Bearer <token>`
  - Le token ne doit pas être expiré
- **Provider** : L'authentification utilise le provider `app_user_provider` qui recherche les utilisateurs par email dans l'entité `User`
- **Stateless** : L'authentification est stateless (pas de session), chaque requête doit inclure le token JWT
- **Format de réponse** : Toutes les réponses sont au format JSON
- **Ordre** : Les catégories sont retournées dans l'ordre de récupération depuis la base de données (pas de tri spécifique)

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

# 2. Utiliser le token pour obtenir les catégories
curl -X GET http://localhost:8000/api/categories \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json"
```

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](error-responses.md) pour plus de détails
- **Performance** : La route retourne toutes les catégories en une seule requête. Pour de grandes quantités de données, une pagination pourrait être ajoutée dans le futur
- **Relations** : Les catégories sont liées aux items via une relation OneToMany. Les items ne sont pas inclus dans cette réponse pour des raisons de performance

