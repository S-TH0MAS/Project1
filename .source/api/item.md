# Documentation API - Routes Item

## 1. Créer un nouvel item utilisateur

### Route
```
POST /api/items/add
```

### Méthode
`POST`

### Description
Cette route permet à un client de créer un nouvel item personnalisé qui lui appartient, avec optionnellement une image. L'item créé est un `ClientItem` qui hérite de `Item` et est automatiquement lié au client connecté. Contrairement aux items par défaut, cet item est unique au client et n'est pas partagé avec les autres utilisateurs.

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
POST /api/items/add HTTP/1.1
Host: localhost:8000
Authorization: Bearer <token>
Content-Type: multipart/form-data; boundary=BoundaryString

--BoundaryString
Content-Disposition: form-data; name="data"
Content-Type: application/json

{
  "name": "Apples",
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
    "name": "Apples",
    "category": "Fruits",
    "img": "apples-647df8a.jpg"
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

**400 Bad Request** - Champ 'data' manquant
```json
{
  "error": "Missing \"data\" field"
}
```

**400 Bad Request** - JSON invalide
```json
{
  "error": "Invalid JSON"
}
```

**400 Bad Request** - Données manquantes (dans le JSON)
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

**400 Bad Request** - Image trop lourde (> 2 Mo)
```json
{
  "error": "File too large. Maximum size allowed is 2MB."
}
```

**Note** : Si la limite serveur PHP est atteinte (avant la validation métier), le message peut être :
```json
{
  "error": "File too large (server limit exceeded)."
}
```

**400 Bad Request** - Format d'image invalide
```json
{
  "error": "Invalid file type. Allowed: JPG, PNG, GIF, WEBP"
}
```

**500 Internal Server Error** - Erreur d'upload générique
```json
{
  "error": "Upload failed with error code: <code>"
}
```

**500 Internal Server Error** - Échec du déplacement du fichier
```json
{
  "error": "Failed to upload image"
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
- **Format de requête** : Impérativement `multipart/form-data`
- **Validation JSON** : Le champ `data` doit contenir un JSON valide avec `name` et `category`
- **Image - Taille** :
  - Limite logicielle : 2 Mo
  - Limite serveur (PHP) : Dépend de `upload_max_filesize` et `post_max_size` (doivent être > 2 Mo)
- **Image - Formats** : Uniquement `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- **Image - Stockage** : Les images sont renommées (slug + uniqid) et stockées dans le dossier configuré (ex: `public/uploads/items`)
- **Validation** :
  - Le nom de l'item ne peut pas être vide
  - Le nom de l'item ne peut pas contenir uniquement des espaces (les espaces en début et fin sont automatiquement supprimés)
  - La catégorie doit exister dans la base de données (recherche par ID ou par nom)
- **Catégorie** :
  - La catégorie peut être spécifiée soit par son ID (integer) soit par son nom (string)
  - Si un nombre est fourni, la recherche se fait par ID
  - Si une chaîne de caractères est fournie, la recherche se fait par nom
  - La recherche par nom est sensible à la casse et doit correspondre exactement

### Logique métier

1. **Extraction** : Récupération du JSON depuis le champ `data` et du fichier depuis le champ `image`
2. **Validation JSON** : Vérification de la présence de `name` et `category`
3. **Recherche Catégorie** : La catégorie est recherchée par ID ou par nom
4. **Upload (Si image présente)** :
   - Vérification de l'erreur d'upload native (taille PHP) - `UPLOAD_ERR_OK`, `UPLOAD_ERR_INI_SIZE`, `UPLOAD_ERR_FORM_SIZE`
   - Vérification de la taille (2 Mo)
   - Vérification du type MIME (JPG, PNG, GIF, WEBP)
   - Renommage sécurisé du fichier (slug + uniqid)
   - Déplacement vers le dossier d'upload
5. **Création** : Création de l'entité `ClientItem` liée au client
6. **Persistance** : Sauvegarde en base de données avec le nom de l'image (ou `null`)

### Relations entre les données

- **ClientItem** : Hérite de `Item` et est stocké dans la table `item` avec `discr = 'client_item'`
- **Client** : Chaque `ClientItem` appartient à un seul `Client` via une relation `ManyToOne`
- **Category** : Chaque `ClientItem` appartient à une seule `Category` via une relation `ManyToOne` (héritée de `Item`)
- **Inventory** : Un `ClientItem` peut être ajouté à l'inventaire du client via la route `POST /api/inventories/add`, mais cette opération est séparée de la création de l'item

### Différence avec les autres routes

- **POST /api/inventories/add** : Ajoute une quantité d'un item existant (par défaut ou personnalisé) à l'inventaire du client. L'item doit déjà exister.
- **POST /api/items/add** : Crée un nouvel item personnalisé pour le client. L'item n'existe pas encore et est créé lors de cette opération.

### Exemple d'utilisation complète (CURL)

**Note** : Avec curl, l'option `-F` force le content-type à `multipart/form-data`.

```bash
# 1. Obtenir un token
export TOKEN="eyJ0eXAiOiJKV1Qi..."

# 2. Créer un nouvel item AVEC image
curl -X POST http://localhost:8000/api/items/add \
  -H "Authorization: Bearer $TOKEN" \
  -F 'data={"name": "Apples", "category": 1}' \
  -F "image=@/chemin/vers/ma_photo.jpg"

# 3. Créer un nouvel item SANS image
curl -X POST http://localhost:8000/api/items/add \
  -H "Authorization: Bearer $TOKEN" \
  -F 'data={"name": "Bananes", "category": "Fruits"}'
```

### Exemple d'utilisation avec PHPStorm HTTP Client

Voir le fichier `test/api/item/add-item.http` pour des exemples complets avec différents cas de test.

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes doivent avoir l'en-tête `Content-Type: multipart/form-data`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON cohérent avec un champ `code` et `message` ou `error`
- **Performance** : La création d'un item est une opération simple et rapide. Aucune pagination n'est nécessaire
- **Unicité** : Les items créés par un client sont uniques à ce client. Plusieurs clients peuvent créer des items avec le même nom, mais ce seront des entités distinctes dans la base de données
- **Héritage** : Les `ClientItem` héritent de `Item`, ce qui permet de les traiter de manière uniforme avec les items par défaut dans certaines opérations
- **Hybrid Approach** : L'approche choisie (JSON dans un champ texte + Fichier) permet de conserver la structure de données complexe tout en autorisant l'upload de fichier binaire standard
- **Configuration Serveur** : Assurez-vous que la configuration `php.ini` autorise les uploads (`file_uploads = On`) et que `upload_max_filesize` est suffisant (recommandé : > 2 Mo)
- **Réponse category** : La réponse retourne uniquement le nom de la catégorie (string), pas un objet avec `id` et `name`
