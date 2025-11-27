# Documentation API - Routes Admin Catégories

## 1. Créer une nouvelle catégorie

### Route
```
POST /api/admin/category/add
```

### Méthode
`POST`

### Description
Cette route permet à un administrateur de créer une nouvelle catégorie. Les catégories sont utilisées pour organiser les items. Le nom de la catégorie doit être unique.

### Paramètres

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `name` | string | Oui | Nom de la catégorie (ne peut pas être vide, max 255 caractères) |

#### Exemple de requête
```json
{
  "name": "Nouvelle Catégorie"
}
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Retour

#### Succès (201 Created)
```json
{
  "message": "Category created successfully",
  "category": {
    "id": 1,
    "name": "Nouvelle Catégorie"
  }
}
```

**Structure de la réponse :**

- `message` : Message de confirmation (string)
- `category` : Objet contenant les informations de la catégorie créée
  - `id` : Identifiant unique de la catégorie créée (integer)
  - `name` : Nom de la catégorie (string)

#### Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](error-responses.md).

**400 Bad Request** - Erreur de validation (name manquant, vide, trop long)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "name: name is required",
    "name: name cannot be empty"
  ]
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

**409 Conflict** - Une catégorie avec ce nom existe déjà
```json
{
  "code": 409,
  "error": "Conflict",
  "message": "A category with this name already exists"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
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
- **Validation** :
  - Le nom de la catégorie ne peut pas être vide
  - Le nom de la catégorie ne peut pas dépasser 255 caractères
  - Le nom de la catégorie doit être unique (pas de doublons)
- **Format de requête** : `application/json`

### Logique métier

1. **Validation** : Vérification de la présence et de la validité du nom
2. **Vérification d'unicité** : Vérification qu'aucune catégorie avec le même nom n'existe déjà
3. **Création** : Création de l'entité `Category`
4. **Persistance** : Sauvegarde en base de données

### Relations entre les données

- **Category** : Entité principale stockée dans la table `category`
- **Item** : Les catégories sont liées aux items via une relation `OneToMany` (une catégorie peut avoir plusieurs items)

---

## 2. Mettre à jour une catégorie

### Route
```
PATCH /api/admin/category/update/{id}
```

### Méthode
`PATCH`

### Description
Cette route permet à un administrateur de mettre à jour une catégorie existante. La mise à jour est partielle : seul le nom peut être modifié. Le nom doit rester unique.

### Paramètres

#### URL
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant de la catégorie à mettre à jour |

#### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `name` | string | Non | Nouveau nom de la catégorie (optionnel, max 255 caractères) |

#### Exemple de requête
```json
{
  "name": "Catégorie Modifiée"
}
```

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Retour

#### Succès (200 OK)
```json
{
  "message": "Category updated successfully",
  "category": {
    "id": 1,
    "name": "Catégorie Modifiée"
  }
}
```

**Structure de la réponse :**

- `message` : Message de confirmation (string)
- `category` : Objet contenant les informations de la catégorie mise à jour
  - `id` : Identifiant unique de la catégorie (integer)
  - `name` : Nom de la catégorie (string)

#### Erreurs possibles

**400 Bad Request** - Erreur de validation (name vide, trop long)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides"
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

**403 Forbidden** - L'utilisateur n'est pas un admin
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be an admin"
}
```

**409 Conflict** - Une catégorie avec ce nom existe déjà
```json
{
  "code": 409,
  "error": "Conflict",
  "message": "A category with this name already exists"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

### Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Rôle requis** : L'utilisateur doit avoir le rôle `ROLE_ADMIN`
- **Mise à jour partielle** : Seul le nom peut être mis à jour
- **Unicité** : Le nouveau nom doit être unique (ne peut pas être le nom d'une autre catégorie existante)
- **Validation** : Le nom ne peut pas être vide et ne peut pas dépasser 255 caractères

### Logique métier

1. **Vérification** : Vérification que la catégorie existe
2. **Validation** : Vérification de la validité du nouveau nom si fourni
3. **Vérification d'unicité** : Vérification qu'aucune autre catégorie n'a déjà ce nom
4. **Mise à jour** : Mise à jour du nom de la catégorie
5. **Persistance** : Sauvegarde des modifications en base de données

---

## 3. Supprimer une catégorie

### Route
```
DELETE /api/admin/category/delete/{id}
```

### Méthode
`DELETE`

### Description
Cette route permet à un administrateur de supprimer une catégorie. Une catégorie ne peut pas être supprimée si elle a des items associés.

### Paramètres

#### URL
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | Identifiant de la catégorie à supprimer |

### Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |

### Retour

#### Succès (200 OK)
```json
{
  "message": "Category deleted successfully"
}
```

#### Erreurs possibles

**404 Not Found** - Catégorie non trouvée
```json
{
  "code": 404,
  "error": "Not Found",
  "message": "Category not found"
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

**409 Conflict** - La catégorie a des items associés
```json
{
  "code": 409,
  "error": "Conflict",
  "message": "Cannot delete category: it has associated items"
}
```

**401 Unauthorized** - Token manquant ou invalide
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

### Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Rôle requis** : L'utilisateur doit avoir le rôle `ROLE_ADMIN`
- **Contrainte de suppression** : Une catégorie ne peut pas être supprimée si elle a des items associés (relation `OneToMany` avec `orphanRemoval: true`)

### Logique métier

1. **Vérification** : Vérification que la catégorie existe
2. **Vérification des dépendances** : Vérification que la catégorie n'a pas d'items associés
3. **Suppression** : Suppression de l'entité `Category` de la base de données

---

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes POST et PATCH doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](error-responses.md) pour plus de détails
- **Unicité des noms** : Les noms de catégories doivent être uniques dans toute l'application
- **Relations** : 
  - Les catégories sont liées aux items via une relation `OneToMany`
  - La suppression d'une catégorie avec des items associés est bloquée pour préserver l'intégrité des données
  - Les items sont liés à une catégorie via `orphanRemoval: true`, ce qui signifie que si une catégorie est supprimée, ses items doivent d'abord être supprimés ou réassignés
- **Séparation des responsabilités** :
  - Les admins gèrent les catégories via ces routes
  - Les clients utilisent les catégories existantes pour créer leurs items
  - Les catégories sont partagées entre tous les utilisateurs

