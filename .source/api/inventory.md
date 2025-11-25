# Documentation API - Routes Inventaire

## 1. Liste des inventaires

### Route
```
GET /api/inventories
```

### Méthode
`GET`

### Description
Cette route permet d'obtenir la liste complète des items disponibles pour un client, ainsi que son inventaire personnel. Elle retourne :
- Tous les items par défaut (qui n'appartiennent à aucun client)
- Tous les items du client connecté (via la table Inventory)
- Les quantités de chaque item dans l'inventaire du client

### Paramètres

Aucun paramètre requis.

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

#### Exemple de requête
```http
GET /api/inventories
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json
```

### Retour

#### Succès (200 OK)
```json
{
  "items": [
    {
      "id": 1,
      "name": "Pomme",
      "category": {
        "id": 1,
        "name": "Fruits"
      },
      "img": "pomme.jpg"
    },
    {
      "id": 2,
      "name": "Carotte",
      "category": {
        "id": 2,
        "name": "Légumes"
      },
      "img": "carotte.jpg"
    },
    {
      "id": 3,
      "name": "Item personnalisé du client",
      "category": {
        "id": 1,
        "name": "Fruits"
      },
      "img": "item_perso.jpg"
    }
  ],
  "inventory": [
    {
      "item_id": 2,
      "quantity": 5
    },
    {
      "item_id": 3,
      "quantity": 10
    }
  ]
}
```

**Structure de la réponse :**

- `items` : Tableau contenant tous les items disponibles (items par défaut + items du client)
  - `id` : Identifiant unique de l'item (integer)
  - `name` : Nom de l'item (string)
  - `category` : Objet contenant les informations de la catégorie
    - `id` : Identifiant unique de la catégorie (integer)
    - `name` : Nom de la catégorie (string)
  - `img` : URL ou chemin de l'image de l'item (string, nullable)
  
- `inventory` : Tableau contenant les items du client avec leurs quantités
  - `item_id` : Identifiant de l'item (integer) - correspond à un `id` dans le tableau `items`
  - `quantity` : Quantité de l'item dans l'inventaire du client (integer)

#### Cas particuliers

**Inventaire vide** : Si le client n'a aucun item dans son inventaire, `inventory` sera un tableau vide mais `items` contiendra toujours les items par défaut :
```json
{
  "items": [
    {
      "id": 1,
      "name": "Pomme",
      "category": {
        "id": 1,
        "name": "Fruits"
      },
      "img": "pomme.jpg"
    }
  ],
  "inventory": []
}
```

**Aucun item par défaut** : Si aucun item par défaut n'existe et que le client n'a pas d'inventaire :
```json
{
  "items": [],
  "inventory": []
}
```

#### Erreurs possibles

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
- **Déduplication** : Les items sont automatiquement dédupliqués. Si un item par défaut existe aussi dans l'inventaire du client, il n'apparaît qu'une seule fois dans le tableau `items`
- **Ordre** : Les items sont retournés dans l'ordre de récupération depuis la base de données (pas de tri spécifique)

### Logique métier

1. **Items par défaut** : La route récupère tous les items où `discr = 'item'` (items de base qui n'appartiennent à aucun client)
2. **Items du client** : La route récupère tous les items du client connecté via la table `Inventory` qui contient la relation entre un `Client` et un `Item` avec une quantité
3. **Combinaison** : Les deux listes sont combinées sans doublons dans le tableau `items`
4. **Inventory** : Le tableau `inventory` contient uniquement les items qui appartiennent au client avec leur quantité respective


### Relations entre les données

- **Items par défaut** : Ces items sont stockés dans la table `item` avec `discr = 'item'`. Ils sont disponibles pour tous les clients mais n'appartiennent à personne
- **Items du client** : Ces items sont liés au client via la table `Inventory` qui fait le lien entre un `Client` et un `Item` avec une quantité
- **Catégories** : Chaque item appartient à une catégorie (relation ManyToOne avec `Category`)

---

## 2. Ajouter un item à l'inventaire

### Route
```
POST /api/inventories/add
```

### Méthode
`POST`

### Description
Cette route permet d'ajouter un item à l'inventaire du client connecté. Si l'inventory existe déjà pour cet item, la quantité spécifiée est ajoutée à la quantité existante. Si l'inventory n'existe pas, un nouvel inventory est créé avec la quantité spécifiée.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `itemId` | integer | Oui | Identifiant de l'item à ajouter |
| `quantity` | integer | Oui | Quantité à ajouter (doit être > 0) |

#### Exemple de requête
```json
{
  "itemId": 1,
  "quantity": 5
}
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Retour

#### Succès (201 Created) - Inventory créé
```json
{
  "message": "Inventory created successfully",
  "inventory": {
    "item_id": 1,
    "quantity": 5
  }
}
```

#### Succès (200 OK) - Inventory mis à jour
Si l'inventory existait déjà, la quantité est ajoutée à la quantité existante :
```json
{
  "message": "Inventory updated successfully",
  "inventory": {
    "item_id": 1,
    "quantity": 8
  }
}
```

#### Erreurs possibles

**400 Bad Request** - Données manquantes
```json
{
  "error": "itemId and quantity are required"
}
```

**400 Bad Request** - Quantité invalide
```json
{
  "error": "Quantity must be greater than 0"
}
```

**404 Not Found** - Item non trouvé
```json
{
  "error": "Item not found"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

**403 Forbidden** - L'utilisateur n'est pas un Client
```json
{
  "error": "User must be a client"
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
- **Quantité** : La quantité doit être strictement positive (supérieure à 0)
- **Logique métier** : 
  - Si l'inventory existe déjà pour cet item et ce client, la quantité est ajoutée à la quantité existante
  - Si l'inventory n'existe pas, un nouvel inventory est créé avec la quantité spécifiée
- **Validation** : L'item doit exister dans la base de données

---

## 3. Retirer une quantité d'un item

### Route
```
POST /api/inventories/remove
```

### Méthode
`POST`

### Description
Cette route permet de retirer une quantité d'un item de l'inventaire du client connecté. Si l'inventory existe, la quantité spécifiée est retirée de la quantité existante. Si la quantité finale est inférieure ou égale à 0, l'inventory est supprimé automatiquement. Si l'inventory n'existe pas, la route retourne un succès sans effectuer d'action.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `itemId` | integer | Oui | Identifiant de l'item dont on veut retirer une quantité |
| `quantity` | integer | Oui | Quantité à retirer (doit être > 0) |

#### Exemple de requête
```json
{
  "itemId": 1,
  "quantity": 3
}
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Retour

#### Succès (200 OK) - Inventory mis à jour
```json
{
  "message": "Inventory updated successfully",
  "inventory": {
    "item_id": 1,
    "quantity": 2
  }
}
```

#### Succès (200 OK) - Inventory supprimé
Si la quantité finale est <= 0, l'inventory est supprimé :
```json
{
  "message": "Inventory removed successfully",
  "inventory": {
    "item_id": 1,
    "quantity": 0
  }
}
```

#### Succès (200 OK) - Inventory non trouvé
Si l'inventory n'existe pas, la route retourne un succès sans erreur :
```json
{
  "message": "Inventory not found, nothing to remove"
}
```

#### Erreurs possibles

**400 Bad Request** - Données manquantes
```json
{
  "error": "itemId and quantity are required"
}
```

**400 Bad Request** - Quantité invalide
```json
{
  "error": "Quantity must be greater than 0"
}
```

**404 Not Found** - Item non trouvé
```json
{
  "error": "Item not found"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

**403 Forbidden** - L'utilisateur n'est pas un Client
```json
{
  "error": "User must be a client"
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
- **Quantité** : La quantité à retirer doit être strictement positive (supérieure à 0)
- **Logique métier** : 
  - Si l'inventory existe, la quantité est retirée de la quantité existante
  - Si la quantité finale est <= 0, l'inventory est supprimé automatiquement
  - Si l'inventory n'existe pas, aucune action n'est effectuée et la route retourne 200 OK avec un message informatif
- **Validation** : L'item doit exister dans la base de données (même si l'inventory n'existe pas)

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON cohérent avec un champ `code` et `message` ou `error`
- **Performance** : La route retourne tous les items en une seule requête. Pour de grandes quantités de données, une pagination pourrait être ajoutée dans le futur
- **Relations** : 
  - Les items sont liés aux catégories via une relation ManyToOne
  - Les items du client sont liés via la table `Inventory` qui contient la quantité
  - Les items par défaut n'ont pas de relation avec un client spécifique

