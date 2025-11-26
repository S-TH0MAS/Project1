# Documentation API - Routes Item

## 1. Créer un nouvel item utilisateur

### Route
```
POST /api/items/add
```

### Méthode
`POST`

### Description
Cette route permet à un client de créer un nouvel item personnalisé qui lui appartient. L'item créé est un `ClientItem` qui hérite de `Item` et est automatiquement lié au client connecté. Contrairement aux items par défaut, cet item est unique au client et n'est pas partagé avec les autres utilisateurs.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `name` | string | Oui | Nom de l'item (ne peut pas être vide ou contenir uniquement des espaces) |
| `category` | integer ou string | Oui | Catégorie de l'item. Peut être l'ID de la catégorie (integer) ou le nom de la catégorie (string) |

#### Exemple de requête avec category par ID
```json
{
  "name": "Apples",
  "category": 1
}
```

#### Exemple de requête avec category par nom
```json
{
  "name": "Bananes",
  "category": "Fruits"
}
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

#### Exemple de requête complète
```http
POST /api/items/add
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json

{
  "name": "Apples",
  "category": 1
}
```

### Retour

#### Succès (201 Created)
```json
{
  "message": "Item created successfully",
  "item": {
    "id": 1,
    "name": "Apples",
    "category": {
      "id": 1,
      "name": "Fruits"
    },
    "img": null
  }
}
```

**Structure de la réponse :**

- `message` : Message de confirmation (string)
- `item` : Objet contenant les informations de l'item créé
  - `id` : Identifiant unique de l'item créé (integer)
  - `name` : Nom de l'item (string)
  - `category` : Objet contenant les informations de la catégorie
    - `id` : Identifiant unique de la catégorie (integer)
    - `name` : Nom de la catégorie (string)
  - `img` : URL ou chemin de l'image de l'item (string, nullable, actuellement toujours `null`)

#### Erreurs possibles

**400 Bad Request** - Données manquantes
```json
{
  "error": "name and category are required"
}
```

**400 Bad Request** - Nom vide
```json
{
  "error": "name cannot be empty"
}
```

**404 Not Found** - Catégorie non trouvée
```json
{
  "error": "Category not found"
}
```

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

**403 Forbidden** - L'utilisateur n'est pas un Client
```json
{
  "error": "User must be a client"
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
- **Type d'utilisateur** : L'utilisateur connecté doit être une instance de `Client` (hérite de `User`)
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Provider** : L'authentification utilise le provider `app_user_provider` qui recherche les utilisateurs par email dans l'entité `User`
- **Stateless** : L'authentification est stateless (pas de session), chaque requête doit inclure le token JWT
- **Format de réponse** : Toutes les réponses sont au format JSON
- **Validation** :
  - Le nom de l'item ne peut pas être vide
  - Le nom de l'item ne peut pas contenir uniquement des espaces (les espaces en début et fin sont automatiquement supprimés)
  - La catégorie doit exister dans la base de données (recherche par ID ou par nom)
- **Catégorie** :
  - La catégorie peut être spécifiée soit par son ID (integer) soit par son nom (string)
  - Si un nombre est fourni, la recherche se fait par ID
  - Si une chaîne de caractères est fournie, la recherche se fait par nom
  - La recherche par nom est sensible à la casse et doit correspondre exactement
- **Image** : Le champ `img` est actuellement toujours `null`. La gestion des images sera implémentée ultérieurement

### Logique métier

1. **Création du ClientItem** : Un nouvel objet `ClientItem` est créé, qui hérite de `Item`
2. **Association au client** : L'item est automatiquement associé au client connecté via la relation `ManyToOne`
3. **Catégorie** : La catégorie est recherchée par ID ou par nom et associée à l'item
4. **Persistance** : L'item est sauvegardé dans la base de données avec le discriminator `client_item`
5. **Réponse** : L'item créé est retourné avec toutes ses informations, y compris la catégorie complète

### Relations entre les données

- **ClientItem** : Hérite de `Item` et est stocké dans la table `item` avec `discr = 'client_item'`
- **Client** : Chaque `ClientItem` appartient à un seul `Client` via une relation `ManyToOne`
- **Category** : Chaque `ClientItem` appartient à une seule `Category` via une relation `ManyToOne` (héritée de `Item`)
- **Inventory** : Un `ClientItem` peut être ajouté à l'inventaire du client via la route `POST /api/inventories/add`, mais cette opération est séparée de la création de l'item

### Différence avec les autres routes

- **POST /api/inventories/add** : Ajoute une quantité d'un item existant (par défaut ou personnalisé) à l'inventaire du client. L'item doit déjà exister.
- **POST /api/items/add** : Crée un nouvel item personnalisé pour le client. L'item n'existe pas encore et est créé lors de cette opération.

### Exemple d'utilisation complète

```bash
# 1. Obtenir un token
curl -X POST http://localhost:8000/user/login \
  -H "Content-Type: application/json" \
  -d '{"email": "client@example.com", "password": "motdepasse123"}'

# Réponse: {"token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}

# 2. Créer un nouvel item avec category par ID
curl -X POST http://localhost:8000/api/items/add \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"name": "Apples", "category": 1}'

# 3. Créer un nouvel item avec category par nom
curl -X POST http://localhost:8000/api/items/add \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"name": "Bananes", "category": "Fruits"}'
```

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON cohérent avec un champ `code` et `message` ou `error`
- **Performance** : La création d'un item est une opération simple et rapide. Aucune pagination n'est nécessaire
- **Unicité** : Les items créés par un client sont uniques à ce client. Plusieurs clients peuvent créer des items avec le même nom, mais ce seront des entités distinctes dans la base de données
- **Héritage** : Les `ClientItem` héritent de `Item`, ce qui permet de les traiter de manière uniforme avec les items par défaut dans certaines opérations
- **Images** : La gestion des images n'est pas encore implémentée. Le champ `img` est toujours `null` pour l'instant

