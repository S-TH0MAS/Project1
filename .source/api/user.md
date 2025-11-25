# Documentation API - Routes Utilisateur

## 1. Création d'un client

### Route
```
POST /user/create
```

### Méthode
`POST`

### Description
Cette route permet de créer un nouveau client dans le système. Un client est un type d'utilisateur qui hérite de l'entité `User`.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | Oui | Adresse email du client (doit être unique) |
| `password` | string | Oui | Mot de passe en clair (sera hashé automatiquement) |
| `name` | string | Oui | Nom du client |

#### Exemple de requête
```json
{
  "email": "client@example.com",
  "password": "motdepasse123",
  "name": "Jean Dupont"
}
```

### Retour

#### Succès (201 Created)
```json
{
  "message": "Client created successfully",
  "client": {
    "id": 1,
    "email": "client@example.com",
    "name": "Jean Dupont",
    "roles": ["ROLE_USER"]
  }
}
```

#### Erreurs possibles

**400 Bad Request** - Données manquantes ou validation échouée
```json
{
  "error": "Email, password and name are required"
}
```

**400 Bad Request** - Erreur de validation
```json
{
  "error": "Validation failed",
  "details": [
    "email: This value is not a valid email address.",
    "name: This value should not be blank."
  ]
}
```

**409 Conflict** - Email déjà utilisé
```json
{
  "error": "User with this email already exists"
}
```

### Contraintes

- **Sécurité** : Cette route est publique (pas d'authentification requise)
- **Email unique** : L'email doit être unique dans la base de données (contrainte au niveau de l'entité `User`)
- **Validation** : 
  - L'email doit être une adresse email valide
  - Le mot de passe sera automatiquement hashé avec l'algorithme configuré dans Symfony
  - Le nom ne doit pas être vide
- **Type d'utilisateur** : Cette route crée toujours un `Client` (qui hérite de `User`)
- **Rôles par défaut** : Si aucun rôle n'est spécifié, le client aura au minimum le rôle `ROLE_USER`

---

## 2. Connexion / Authentification

### Route
```
POST /user/login
```

### Méthode
`POST`

### Description
Cette route permet à un utilisateur (client) de s'authentifier et d'obtenir un token JWT pour accéder aux routes protégées de l'API.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | Oui | Adresse email de l'utilisateur |
| `password` | string | Oui | Mot de passe en clair |

#### Exemple de requête
```json
{
  "email": "client@example.com",
  "password": "motdepasse123"
}
```

### Retour

#### Succès (200 OK)
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0..."
}
```

Le token JWT contient :
- `iat` : Date d'émission (issued at)
- `exp` : Date d'expiration
- `roles` : Rôles de l'utilisateur
- `username` : Email de l'utilisateur

#### Erreurs possibles

**400 Bad Request** - Données manquantes
```json
{
  "code": 400,
  "message": "Bad Request"
}
```

**401 Unauthorized** - Identifiants incorrects
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

**401 Unauthorized** - Utilisateur inexistant
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

### Contraintes

- **Sécurité** : Cette route est gérée par le firewall Symfony avec `json_login`
- **Authentification** : 
  - L'email doit correspondre à un utilisateur existant dans la base de données
  - Le mot de passe doit correspondre au mot de passe hashé stocké en base
- **Token JWT** : 
  - Le token est généré par Lexik JWT Authentication Bundle
  - Le token a une durée de validité limitée (configurée dans la configuration JWT)
  - Le token doit être inclus dans l'en-tête `Authorization` pour les requêtes suivantes : `Authorization: Bearer <token>`
- **Format de réponse** : Le token est retourné dans un objet JSON avec la clé `token`
- **Stateless** : L'authentification est stateless (pas de session), chaque requête doit inclure le token JWT
- **Provider** : L'authentification utilise le provider `app_user_provider` qui recherche les utilisateurs par email dans l'entité `User`

### Utilisation du token

Pour utiliser le token obtenu, incluez-le dans l'en-tête `Authorization` de vos requêtes :

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON cohérent avec un champ `error` ou `message`
- **Validation** : Les validations sont effectuées à la fois au niveau du contrôleur et au niveau de l'entité via les validators Symfony

