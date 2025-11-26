# Documentation API - Routes Client

## 1. Obtenir les informations du client

### Route
```
GET /api/client
```

### Méthode
`GET`

### Description
Cette route permet d'obtenir les informations de l'utilisateur connecté. Le format de la réponse varie selon le type d'utilisateur :
- **Client** : Retourne le nom, l'email et les rôles
- **Admin** (utilisateur avec `ROLE_ADMIN`) : Retourne uniquement l'email et les rôles (pas de nom)

### Paramètres

Aucun paramètre requis.

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

#### Exemple de requête
```http
GET /api/client
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json
```

### Retour

#### Succès (200 OK) - Client
Pour un utilisateur de type `Client` :
```json
{
  "name": "Jean Dupont",
  "email": "client@example.com",
  "roles": ["ROLE_USER"]
}
```

**Structure de la réponse (Client) :**
- `name` : Nom du client (string)
- `email` : Adresse email du client (string)
- `roles` : Tableau des rôles du client (array<string>)

#### Succès (200 OK) - Admin
Pour un utilisateur avec le rôle `ROLE_ADMIN` :
```json
{
  "email": "admin@example.com",
  "roles": ["ROLE_USER", "ROLE_ADMIN"]
}
```

**Structure de la réponse (Admin) :**
- `email` : Adresse email de l'admin (string)
- `roles` : Tableau des rôles de l'admin (array<string>)
- **Note** : Le champ `name` n'est **pas** retourné pour les admins

#### Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](error-responses.md).

**401 Unauthorized** - Token manquant
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

**403 Forbidden** - L'utilisateur n'est pas un Client (et n'est pas admin)
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be a client"
}
```

**403 Forbidden** - Accès refusé (rôle insuffisant)
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
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Provider** : L'authentification utilise le provider `app_user_provider` qui recherche les utilisateurs par email dans l'entité `User`
- **Stateless** : L'authentification est stateless (pas de session), chaque requête doit inclure le token JWT
- **Format de réponse** : Toutes les réponses sont au format JSON
- **Rôles** : 
  - Les rôles retournés incluent toujours au minimum `ROLE_USER` (ajouté automatiquement par Symfony)
  - Si l'utilisateur a le rôle `ROLE_ADMIN`, le champ `name` n'est pas retourné
- **Type d'utilisateur** : 
  - Si l'utilisateur est une instance de `Client`, le champ `name` est retourné
  - Si l'utilisateur a le rôle `ROLE_ADMIN`, le champ `name` n'est pas retourné même s'il est un `Client`
  - Si l'utilisateur n'est ni un `Client` ni un admin, une erreur 403 est retournée

### Logique métier

1. **Récupération de l'utilisateur** : L'utilisateur connecté est récupéré via `$this->getUser()`
2. **Vérification du rôle admin** : Vérification si l'utilisateur a le rôle `ROLE_ADMIN` dans ses rôles
3. **Réponse conditionnelle** :
   - Si admin : Retourne uniquement `email` et `roles`
   - Si Client (non-admin) : Retourne `name`, `email` et `roles`
   - Sinon : Retourne une erreur 403

### Relations entre les données

- **Client** : Hérite de `User` et possède un champ `name` supplémentaire
- **User** : Entité de base avec `email`, `password` et `roles`
- **Admin** : Un utilisateur (peut être un `User` ou un `Client`) avec le rôle `ROLE_ADMIN` dans son tableau de rôles

### Exemple d'utilisation complète

```bash
# 1. Obtenir un token
curl -X POST http://localhost:8000/user/login \
  -H "Content-Type: application/json" \
  -d '{"email": "client@example.com", "password": "motdepasse123"}'

# Réponse: {"token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}

# 2. Obtenir les informations du client
curl -X GET http://localhost:8000/api/client \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json"
```

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](error-responses.md) pour plus de détails
- **Différence Client/Admin** : La principale différence dans la réponse est la présence ou l'absence du champ `name`. Les admins n'ont pas de nom retourné, même s'ils sont techniquement des `Client` en base de données
- **Rôles** : Le système garantit que tous les utilisateurs ont au minimum le rôle `ROLE_USER`, même si ce rôle n'est pas explicitement stocké en base de données

