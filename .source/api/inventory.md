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
- Tous les items par défaut (Item non client, qui n'appartiennent à aucun client)
- Tous les items du client connecté (ClientItem, items personnalisés créés par le client)
- Les quantités de chaque item dans l'inventaire du client
- La liste des IDs des items appartenant au client (ClientItem)

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
  ],
  "client_items": [3]
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

- `client_items` : Tableau contenant les IDs des items appartenant au client (ClientItem)
  - Liste d'entiers (integer[]) représentant les identifiants des items personnalisés créés par le client

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
  "inventory": [],
  "client_items": []
}
```

**Aucun item par défaut** : Si aucun item par défaut n'existe et que le client n'a pas d'inventaire :
```json
{
  "items": [],
  "inventory": [],
  "client_items": []
}
```

**Client avec items personnalisés mais sans inventaire** : Si le client a créé des items personnalisés mais n'a pas encore d'inventaire :
```json
{
  "items": [
    {
      "id": 10,
      "name": "Mon item personnalisé",
      "category": {
        "id": 1,
        "name": "Fruits"
      },
      "img": "mon-item.jpg"
    }
  ],
  "inventory": [],
  "client_items": [10]
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

**403 Forbidden** - L'utilisateur n'est pas un Client
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
- **Type d'utilisateur** : L'utilisateur connecté doit être une instance de `Client` (hérite de `User`)
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Provider** : L'authentification utilise le provider `app_user_provider` qui recherche les utilisateurs par email dans l'entité `User`
- **Stateless** : L'authentification est stateless (pas de session), chaque requête doit inclure le token JWT
- **Format de réponse** : Toutes les réponses sont au format JSON
- **Déduplication** : Les items sont automatiquement dédupliqués. Si un item par défaut existe aussi dans l'inventaire du client, il n'apparaît qu'une seule fois dans le tableau `items`
- **Ordre** : Les items sont retournés dans l'ordre de récupération depuis la base de données (pas de tri spécifique)

### Logique métier

La route suit les étapes suivantes :

1. **Récupération des items par défaut** : Récupère tous les items où `discr = 'item'` (Item non client, items de base qui n'appartiennent à aucun client)
2. **Récupération des items du client** : Récupère tous les items du client connecté via `ClientItemRepository` (ClientItem, items personnalisés créés par le client)
3. **Récupération de l'inventaire** : Récupère l'inventaire du client via `InventoryRepository` (table Inventory qui contient la relation entre un `Client` et un `Item` avec une quantité)
4. **Concaténation des items** : Concatène les deux listes d'items (items par défaut + ClientItems) pour avoir tous les items disponibles, en évitant les doublons
5. **Préparation de l'inventory** : Prépare l'array de retour pour l'inventory avec les quantités
6. **Liste des client_items** : Construit la liste des IDs des items appartenant au client (ClientItem) dans le champ `client_items`


### Relations entre les données

- **Items par défaut (Item)** : Ces items sont stockés dans la table `item` avec `discr = 'item'`. Ils sont disponibles pour tous les clients mais n'appartiennent à personne
- **Items du client (ClientItem)** : Ces items sont stockés dans la table `item` avec `discr = 'client_item'` et sont liés au client via une relation `ManyToOne`. Ce sont des items personnalisés créés par le client via `POST /api/items/add`
- **Inventory** : La table `Inventory` fait le lien entre un `Client` et un `Item` (peut être un Item par défaut ou un ClientItem) avec une quantité. Elle permet de gérer le stock du client
- **Catégories** : Chaque item (Item ou ClientItem) appartient à une catégorie (relation ManyToOne avec `Category`)

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

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](error-responses.md).

**400 Bad Request** - Erreur de validation (données manquantes, quantité invalide, etc.)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "itemId: This value should not be null.",
    "quantity: This value should be greater than 0."
  ]
}
```

**404 Not Found** - Item non trouvé
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Item not found"
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
  "code": 403,
  "error": "Forbidden",
  "message": "User must be a client"
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

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](error-responses.md).

**400 Bad Request** - Erreur de validation (données manquantes, quantité invalide, etc.)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "itemId: This value should not be null.",
    "quantity: This value should be greater than 0."
  ]
}
```

**404 Not Found** - Item non trouvé
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Item not found"
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
  "code": 403,
  "error": "Forbidden",
  "message": "User must be a client"
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
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](error-responses.md) pour plus de détails
- **Performance** : La route retourne tous les items en une seule requête. Pour de grandes quantités de données, une pagination pourrait être ajoutée dans le futur
- **Relations** : 
  - Les items sont liés aux catégories via une relation ManyToOne
  - Les ClientItems sont directement liés au client via une relation ManyToOne
  - Les items du client dans l'inventaire sont liés via la table `Inventory` qui contient la quantité (peut référencer un Item par défaut ou un ClientItem)
  - Les items par défaut n'ont pas de relation directe avec un client spécifique
- **client_items** : Le champ `client_items` permet d'identifier rapidement quels items dans la liste `items` appartiennent au client (sont des ClientItem). Cela facilite l'affichage côté client pour distinguer les items personnalisés des items par défaut

