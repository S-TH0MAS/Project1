# Documentation API - Routes Item Admin

## 1. Créer un nouvel item global

### Route
```
POST /api/admin/items/add
```

### Méthode
`POST`

### Description
Cette route permet à un administrateur de créer un nouvel item global qui sera accessible à tous les utilisateurs. L'item créé est un `Item` (pas un `ClientItem`) et n'est pas lié à un client spécifique. Ces items sont partagés entre tous les utilisateurs et apparaissent dans la liste des items disponibles pour tous les clients.

**Note importante** : Cette route utilise le format `multipart/form-data` (et non `application/json` pur) pour permettre l'envoi simultané de données structurées et d'un fichier binaire.

### Paramètres

#### Body (multipart/form-data)
La requête attend deux champs distincts dans le corps du formulaire.

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `data` | string (JSON) | Oui | Une chaîne de caractères contenant l'objet JSON avec `name` et `category` |
| `image` | File | Non | Fichier image. Formats : JPG, PNG, GIF, WEBP. Max : 2 Mo |

#### Structure du champ data (JSON décodé)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `name` | string | Oui | Nom de l'item (ne peut pas être vide ou contenir uniquement des espaces) |
| `category` | integer ou string | Oui | Catégorie de l'item. Peut être l'ID de la catégorie (integer) ou le nom de la catégorie (string) |

#### Exemple de structure de requête (Multipart)
```http
POST /api/admin/items/add HTTP/1.1
Host: localhost:8000
Authorization: Bearer <token>
Content-Type: multipart/form-data; boundary=BoundaryString

--BoundaryString
Content-Disposition: form-data; name="data"
Content-Type: application/json

{
  "name": "Pomme Globale",
  "category": 1
}
--BoundaryString
Content-Disposition: form-data; name="image"; filename="photo.jpg"
Content-Type: image/jpeg

[Données binaires du fichier...]
--BoundaryString--
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `multipart/form-data; boundary=...` (Géré automatiquement par les clients HTTP/Navigateurs) |

### Retour

#### Succès (201 Created)
```json
{
  "message": "Item created successfully",
  "item": {
    "id": 1,
    "name": "Pomme Globale",
    "category": "Fruits",
    "img": "pomme-globale-647df8a.jpg"
  }
}
```

**Structure de la réponse :**

- `message` : Message de confirmation (string)
- `item` : Objet contenant les informations de l'item créé
  - `id` : Identifiant unique de l'item créé (integer)
  - `name` : Nom de l'item (string)
  - `category` : Nom de la catégorie (string) - **Note** : Retourne uniquement le nom, pas un objet
  - `img` : Nom du fichier image généré sur le serveur (string ou `null` si aucune image envoyée)

#### Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](error-responses.md).

**400 Bad Request** - Champ 'data' manquant
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Missing \"data\" field"
}
```

**400 Bad Request** - Erreur de validation (JSON invalide, données manquantes, etc.)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "name: This value should not be blank.",
    "category: This value should not be null."
  ]
}
```

**400 Bad Request** - Image trop lourde (> 2 Mo)
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "File too large. Maximum size allowed is 2MB."
}
```

**400 Bad Request** - Format d'image invalide
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Invalid file type. Allowed: JPG, PNG, GIF, WEBP"
}
```

**404 Not Found** - Catégorie non trouvée
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Category not found"
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
- **Authentification** : 
  - Un token JWT valide doit être fourni dans l'en-tête `Authorization`
  - Le token doit être obtenu via `POST /user/login`
  - Le token doit être au format `Bearer <token>`
  - Le token ne doit pas être expiré
- **Rôle requis** : L'utilisateur doit avoir le rôle `ROLE_ADMIN`
- **Format de requête** : Impérativement `multipart/form-data`
- **Image - Taille** : Limite de 2 Mo
- **Image - Formats** : Uniquement `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- **Validation** :
  - Le nom de l'item ne peut pas être vide
  - La catégorie doit exister dans la base de données (recherche par ID ou par nom)

### Logique métier

1. **Extraction** : Récupération du JSON depuis le champ `data` et du fichier depuis le champ `image`
2. **Validation JSON** : Vérification de la présence de `name` et `category`
3. **Recherche Catégorie** : La catégorie est recherchée par ID ou par nom
4. **Upload (Si image présente)** : Vérification, validation et stockage de l'image
5. **Création** : Création de l'entité `Item` (pas de `ClientItem`)
6. **Persistance** : Sauvegarde en base de données avec le nom de l'image (ou `null`)

### Relations entre les données

- **Item** : Stocké dans la table `item` avec `discr = 'item'` (item global, non lié à un client)
- **Category** : Chaque `Item` appartient à une seule `Category` via une relation `ManyToOne`
- **Accessibilité** : Les items globaux sont accessibles à tous les utilisateurs et apparaissent dans la liste des items disponibles

---

## 2. Mettre à jour un item global

### Route
```
POST /api/admin/items/update/{id}
```

### Méthode
`POST`

### Description
Cette route permet à un administrateur de mettre à jour un item global. La mise à jour est partielle : seuls les champs fournis seront modifiés. L'image peut être mise à jour indépendamment des autres champs. Si une nouvelle image est fournie, l'ancienne image est automatiquement supprimée du serveur.

**Note importante** : Cette route utilise `POST` au lieu de `PATCH` car PHP ne supporte pas nativement `multipart/form-data` avec les méthodes `PATCH` ou `PUT`. Le format `multipart/form-data` est nécessaire pour permettre l'envoi d'images.

**Important** : Seuls les `Item` globaux peuvent être modifiés. Les `ClientItem` (items personnalisés des clients) ne peuvent pas être modifiés via cette route.

### Paramètres

#### URL
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant de l'item global à mettre à jour (doit être un `Item`, pas un `ClientItem`) |

#### Body (multipart/form-data)
La requête accepte deux champs optionnels dans le corps du formulaire.

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `data` | string (JSON) | Non | Une chaîne de caractères contenant l'objet JSON avec `name` et/ou `category` |
| `image` | File | Non | Nouveau fichier image. Formats : JPG, PNG, GIF, WEBP. Max : 2 Mo |

**Note** : Au moins un des deux champs (`data` ou `image`) doit être fourni.

#### Structure du champ data (JSON décodé)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `name` | string | Non | Nouveau nom de l'item (optionnel) |
| `category` | integer ou string | Non | Nouvelle catégorie de l'item (optionnel). Peut être l'ID de la catégorie (integer) ou le nom de la catégorie (string) |

#### Exemple de structure de requête (Multipart)
```http
POST /api/admin/items/update/1 HTTP/1.1
Host: localhost:8000
Authorization: Bearer <token>
Content-Type: multipart/form-data; boundary=BoundaryString

--BoundaryString
Content-Disposition: form-data; name="data"
Content-Type: application/json

{
  "name": "Pomme Globale Updated",
  "category": 2
}
--BoundaryString
Content-Disposition: form-data; name="image"; filename="new_photo.jpg"
Content-Type: image/jpeg

[Données binaires du fichier...]
--BoundaryString--
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `multipart/form-data; boundary=...` |

### Retour

#### Succès (200 OK)
```json
{
  "message": "Item updated successfully",
  "item": {
    "id": 1,
    "name": "Pomme Globale Updated",
    "category": "Fruits",
    "img": "new_photo-647df8a.jpg"
  }
}
```

**Structure de la réponse :**

- `message` : Message de confirmation (string)
- `item` : Objet contenant les informations de l'item mis à jour
  - `id` : Identifiant unique de l'item (integer)
  - `name` : Nom de l'item (string)
  - `category` : Nom de la catégorie (string)
  - `img` : Nom du fichier image (string ou `null`)

#### Erreurs possibles

**400 Bad Request** - Aucune donnée fournie (ni data ni image)
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "Missing \"data\" field or \"image\" file"
}
```

**400 Bad Request** - Erreur de validation
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides"
}
```

**400 Bad Request** - Image trop lourde ou format invalide
```json
{
  "code": 400,
  "error": "Bad Request",
  "message": "File too large. Maximum size allowed is 2MB."
}
```

**404 Not Found** - Item non trouvé ou c'est un ClientItem
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Item not found or access denied"
}
```

**404 Not Found** - Catégorie non trouvée
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Category not found"
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
- **Type d'item** : Seuls les `Item` globaux peuvent être modifiés (pas les `ClientItem`)
- **Mise à jour partielle** : Seuls les champs fournis seront mis à jour
- **Image** : Si une nouvelle image est fournie, l'ancienne est automatiquement supprimée
- **Validation** : La catégorie doit exister si elle est fournie

### Logique métier

1. **Vérification** : Vérification que l'item existe et est un `Item` global (pas un `ClientItem`)
2. **Extraction** : Récupération des données JSON et/ou du fichier image
3. **Mise à jour partielle** :
   - Si `name` est fourni, mise à jour du nom
   - Si `category` est fournie, recherche et mise à jour de la catégorie
4. **Gestion de l'image** :
   - Si une nouvelle image est fournie, suppression de l'ancienne image du serveur
   - Upload et validation de la nouvelle image
   - Mise à jour du nom de l'image dans l'item
5. **Persistance** : Sauvegarde des modifications en base de données

---

## 3. Supprimer un item global

### Route
```
DELETE /api/admin/items/delete/{id}
```

### Méthode
`DELETE`

### Description
Cette route permet à un administrateur de supprimer un item global. L'image associée à l'item est automatiquement supprimée du serveur si elle existe.

**Important** : Seuls les `Item` globaux peuvent être supprimés. Les `ClientItem` (items personnalisés des clients) ne peuvent pas être supprimés via cette route.

### Paramètres

#### URL
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant de l'item global à supprimer (doit être un `Item`, pas un `ClientItem`) |

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |

### Retour

#### Succès (200 OK)
```json
{
  "message": "Item deleted successfully"
}
```

#### Erreurs possibles

**404 Not Found** - Item non trouvé ou c'est un ClientItem
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Item not found or access denied"
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
- **Type d'item** : Seuls les `Item` globaux peuvent être supprimés (pas les `ClientItem`)
- **Suppression en cascade** : L'image associée est automatiquement supprimée du serveur

### Logique métier

1. **Vérification** : Vérification que l'item existe et est un `Item` global (pas un `ClientItem`)
2. **Suppression de l'image** : Si l'item a une image, suppression du fichier du serveur
3. **Suppression de l'item** : Suppression de l'entité `Item` de la base de données

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes POST doivent avoir l'en-tête `Content-Type: multipart/form-data`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](error-responses.md) pour plus de détails
- **Items globaux vs Items clients** :
  - **Item** (global) : Créé par un admin, accessible à tous les utilisateurs, stocké avec `discr = 'item'`
  - **ClientItem** : Créé par un client, uniquement accessible à ce client, stocké avec `discr = 'client_item'`
- **Héritage** : Les `ClientItem` héritent de `Item`, ce qui permet de les traiter de manière uniforme dans certaines opérations
- **Méthode POST pour UPDATE** : La route de mise à jour utilise `POST` au lieu de `PATCH` car PHP ne supporte pas nativement `multipart/form-data` avec `PATCH` ou `PUT`
- **Séparation des responsabilités** :
  - Les admins gèrent uniquement les items globaux (`Item`)
  - Les clients gèrent uniquement leurs propres items personnalisés (`ClientItem`)
  - Les admins ne peuvent pas modifier ou supprimer les `ClientItem`
  - Les clients ne peuvent pas modifier ou supprimer les `Item` globaux


