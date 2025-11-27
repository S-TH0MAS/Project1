# Documentation API - Routes Admin Utilisateurs

## 1. Lister tous les utilisateurs

### Route
```
GET /api/admin/user
```

### Méthode
`GET`

### Description
Cette route permet à un administrateur d'obtenir la liste complète de tous les utilisateurs du système. La réponse distingue les utilisateurs de type `User` et les utilisateurs de type `Client` (qui héritent de `User`). Les clients ont un champ `name` supplémentaire dans la réponse.

### Paramètres

Aucun paramètre requis.

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

#### Exemple de requête
```http
GET /api/admin/user
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6ImFkbWluQGV4YW1wbGUuY29tIn0...
Content-Type: application/json
```

### Retour

#### Succès (200 OK)
```json
{
  "users": [
    {
      "id": 1,
      "email": "user@example.com",
      "roles": ["ROLE_USER"],
      "type": "user"
    },
    {
      "id": 2,
      "email": "client@example.com",
      "roles": ["ROLE_USER"],
      "type": "client",
      "name": "Jean Dupont"
    },
    {
      "id": 3,
      "email": "admin@example.com",
      "roles": ["ROLE_USER", "ROLE_ADMIN"],
      "type": "user"
    }
  ],
  "count": 3
}
```

**Structure de la réponse :**

- `users` : Tableau contenant tous les utilisateurs
  - `id` : Identifiant unique de l'utilisateur (integer)
  - `email` : Adresse email de l'utilisateur (string)
  - `roles` : Tableau des rôles de l'utilisateur (array<string>)
  - `type` : Type d'utilisateur - `"user"` ou `"client"` (string)
  - `name` : Nom du client (string, uniquement présent si `type === "client"`)
- `count` : Nombre total d'utilisateurs (integer)

#### Cas particuliers

**Liste vide** : Si aucun utilisateur n'existe dans le système :
```json
{
  "users": [],
  "count": 0
}
```

**Utilisateurs mixtes** : La réponse peut contenir à la fois des `User` et des `Client` :
```json
{
  "users": [
    {
      "id": 1,
      "email": "admin@example.com",
      "roles": ["ROLE_USER", "ROLE_ADMIN"],
      "type": "user"
    },
    {
      "id": 2,
      "email": "client1@example.com",
      "roles": ["ROLE_USER"],
      "type": "client",
      "name": "Client 1"
    },
    {
      "id": 3,
      "email": "client2@example.com",
      "roles": ["ROLE_USER"],
      "type": "client",
      "name": "Client 2"
    }
  ],
  "count": 3
}
```

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

**403 Forbidden** - L'utilisateur n'est pas un admin
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be an admin"
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
- **Rôle requis** : L'utilisateur doit avoir le rôle `ROLE_ADMIN`
- **Provider** : L'authentification utilise le provider `app_user_provider` qui recherche les utilisateurs par email dans l'entité `User`
- **Stateless** : L'authentification est stateless (pas de session), chaque requête doit inclure le token JWT
- **Format de réponse** : Toutes les réponses sont au format JSON
- **Rôles** : 
  - Les rôles retournés incluent toujours au minimum `ROLE_USER` (ajouté automatiquement par Symfony)
  - Les admins ont également le rôle `ROLE_ADMIN`
- **Type d'utilisateur** : 
  - Les utilisateurs de type `User` n'ont pas de champ `name`
  - Les utilisateurs de type `Client` (qui héritent de `User`) ont un champ `name` supplémentaire
  - Le champ `type` permet de distinguer les deux types d'utilisateurs

### Logique métier

1. **Récupération de l'utilisateur** : L'utilisateur connecté est récupéré via `$this->getUser()`
2. **Vérification du rôle admin** : Vérification si l'utilisateur a le rôle `ROLE_ADMIN` dans ses rôles
3. **Récupération des utilisateurs** : Récupération de tous les utilisateurs depuis le `UserRepository`
4. **Transformation des données** : Pour chaque utilisateur :
   - Extraction des informations de base (id, email, roles)
   - Détermination du type (`user` ou `client`)
   - Ajout du champ `name` si c'est un `Client`
5. **Retour** : Retour de la liste avec le nombre total d'utilisateurs

### Relations entre les données

- **User** : Entité de base avec `email`, `password` et `roles`
- **Client** : Hérite de `User` et possède un champ `name` supplémentaire
- **Héritage** : L'héritage est géré via Doctrine avec `JOINED` inheritance type
- **Discriminator** : Le champ `discr` dans la table `user` permet de distinguer les `User` (`discr = 'user'`) des `Client` (`discr = 'client'`)

---

## 2. Supprimer un utilisateur

### Route
```
DELETE /api/admin/user/delete/{id}
```

### Méthode
`DELETE`

### Description
Cette route permet à un administrateur de supprimer un utilisateur du système. Un administrateur ne peut pas supprimer son propre compte. La suppression d'un utilisateur supprime également toutes ses relations (ClientItems, Inventories, etc.) grâce à la configuration `orphanRemoval: true` dans les entités.

### Paramètres

#### URL
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant de l'utilisateur à supprimer |

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |

### Retour

#### Succès (200 OK)
```json
{
  "message": "User deleted successfully"
}
```

#### Erreurs possibles

**400 Bad Request** - Tentative de suppression de son propre compte
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Cannot delete your own account"
}
```

**404 Not Found** - Utilisateur non trouvé
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "User not found"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

**403 Forbidden** - L'utilisateur n'est pas un admin
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be an admin"
}
```

### Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Rôle requis** : L'utilisateur doit avoir le rôle `ROLE_ADMIN`
- **Auto-suppression** : Un administrateur ne peut pas supprimer son propre compte
- **Suppression en cascade** : 
  - La suppression d'un `Client` supprime automatiquement ses `ClientItem` (grâce à `orphanRemoval: true`)
  - La suppression d'un `Client` supprime automatiquement ses `Inventory` (grâce à `orphanRemoval: true`)
- **Héritage** : La suppression fonctionne pour les deux types d'utilisateurs (`User` et `Client`)

### Logique métier

1. **Vérification du rôle admin** : Vérification que l'utilisateur connecté est un admin
2. **Vérification d'auto-suppression** : Vérification que l'utilisateur ne tente pas de supprimer son propre compte
3. **Recherche de l'utilisateur** : Recherche de l'utilisateur à supprimer par son ID
4. **Vérification d'existence** : Vérification que l'utilisateur existe
5. **Suppression** : Suppression de l'entité `User` (ou `Client`) de la base de données
6. **Suppression en cascade** : Doctrine supprime automatiquement les relations grâce à `orphanRemoval: true`

### Relations entre les données

- **User** : Entité de base qui peut être supprimée
- **Client** : Hérite de `User`, la suppression d'un `Client` supprime également :
  - Ses `ClientItem` (relation `OneToMany` avec `orphanRemoval: true`)
  - Ses `Inventory` (relation `OneToMany` avec `orphanRemoval: true`)
- **Sécurité** : La protection contre l'auto-suppression empêche un admin de se bloquer accidentellement

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](error-responses.md) pour plus de détails
- **Héritage User/Client** : 
  - Les `Client` héritent de `User` via l'héritage Doctrine `JOINED`
  - Le discriminator `discr` permet de distinguer les types dans la base de données
  - Les deux types sont stockés dans la même table `user` avec des tables séparées pour les champs spécifiques
- **Rôles** : 
  - Tous les utilisateurs ont au minimum le rôle `ROLE_USER`
  - Les admins ont également le rôle `ROLE_ADMIN`
  - Les rôles sont stockés dans un tableau JSON dans la base de données
- **Sécurité** : 
  - Toutes les routes nécessitent le rôle `ROLE_ADMIN`
  - Un admin ne peut pas supprimer son propre compte pour éviter de se bloquer
  - Les tokens JWT doivent être valides et non expirés
- **Suppression en cascade** : 
  - La suppression d'un utilisateur supprime automatiquement toutes ses données associées
  - Cette opération est irréversible
  - Les relations sont configurées avec `orphanRemoval: true` pour garantir la cohérence des données
- **Différence avec POST /user/create** : 
  - `POST /user/create` : Route publique pour créer un compte client (inscription)
  - `DELETE /api/admin/user/delete/{id}` : Route admin pour supprimer un utilisateur existant
  - Il n'y a pas de route POST pour créer un utilisateur via l'admin (les utilisateurs s'inscrivent eux-mêmes)

