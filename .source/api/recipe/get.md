# Récupération de recettes

## Route
```
POST /api/recipe/get
```

## Méthode
`POST`

## Description
Cette route permet de récupérer une liste paginée de recettes depuis la base de données. Les recettes peuvent être filtrées selon le mode sélectionné (`all`, `favorite`, ou `author`) et peuvent être paginées à l'aide des paramètres `quantity` (nombre de recettes à récupérer) et `offset` (nombre de recettes à ignorer avant de commencer la récupération). Par défaut, le mode est `all` et retourne toutes les recettes triées par ID croissant.

## Paramètres

### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `quantity` | integer | Oui | Nombre de recettes à récupérer (doit être un entier positif, maximum 100) |
| `offset` | integer | Non | Nombre de recettes à ignorer avant de commencer à récupérer (doit être >= 0, défaut: 0) |
| `mode` | string | Non | Mode de récupération : `all` (toutes les recettes), `favorite` (recettes favorites du client), ou `author` (recettes dont le client est l'auteur). Défaut: `all` |

### Exemple de requête - Mode all (par défaut)
```json
{
  "quantity": 10
}
```

### Exemple de requête - Mode all avec offset
```json
{
  "quantity": 10,
  "offset": 0,
  "mode": "all"
}
```

### Exemple de requête - Recettes favorites
```json
{
  "quantity": 10,
  "mode": "favorite"
}
```

### Exemple de requête - Recettes favorites avec offset
```json
{
  "quantity": 10,
  "offset": 5,
  "mode": "favorite"
}
```

### Exemple de requête - Recettes dont le client est auteur
```json
{
  "quantity": 10,
  "mode": "author"
}
```

### Exemple de requête - Recettes dont le client est auteur avec offset
```json
{
  "quantity": 10,
  "offset": 5,
  "mode": "author"
}
```

## Headers requis

| Header | Type | Requis | Description |
|--------|------|--------|-------------|
| `Authorization` | string | Oui | Token JWT obtenu via `POST /user/login` au format `Bearer <token>` |
| `Content-Type` | string | Oui | Doit être `application/json` |

### Exemple de requête complète
```http
POST /api/recipe/get
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzU4NzY1NDAsImV4cCI6MTYzNTg4MDE0MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiY2xpZW50QGV4YW1wbGUuY29tIn0...
Content-Type: application/json

{
  "quantity": 10,
  "offset": 0
}
```

## Retour

### Succès (200 OK)
```json
{
  "recipes": [
    {
      "id": 1,
      "name": "Tarte aux pommes",
      "description": "Une délicieuse tarte aux pommes maison",
      "matching": 95,
      "preparation_time": 45,
      "ingredients": [
        "5 Pommes",
        "200g de farine",
        "100g de beurre",
        "50g de sucre",
        "1 pincée de sel"
      ],
      "steps": [
        "Préparer la pâte en mélangeant la farine, le beurre et le sel",
        "Étaler la pâte dans un moule à tarte",
        "Éplucher et couper les pommes en lamelles",
        "Disposer les pommes sur la pâte",
        "Saupoudrer de sucre",
        "Enfourner à 180°C pendant 30 minutes"
      ],
      "date": 1635876540,
      "image": null,
      "author": {
        "id": 1,
        "name": "Jean Dupont"
      }
    }
  ]
}
```

**Structure de la réponse :**

- `recipes` : Tableau contenant les recettes récupérées (array)
  - `id` : Identifiant unique de la recette (integer)
  - `name` : Nom de la recette (string)
  - `description` : Description de la recette (string)
  - `matching` : Score de pertinence entre 0 et 100 (integer)
  - `preparation_time` : Temps de préparation en minutes (integer)
  - `ingredients` : Liste des ingrédients nécessaires (array de string)
  - `steps` : Étapes de préparation (array de string)
  - `date` : Timestamp de création de la recette (integer)
  - `image` : URL de l'image de la recette ou `null` (string|null)
  - `author` : Objet contenant les informations de l'auteur de la recette (object|null)
    - `id` : Identifiant unique de l'auteur (integer)
    - `name` : Nom de l'auteur (string)

> **Note** : Si aucune recette n'est trouvée, la réponse contiendra un tableau vide : `{"recipes": []}`

## Erreurs possibles

> **Note** : Toutes les erreurs suivent un format uniformisé. Pour plus de détails, consultez la [documentation sur les réponses d'erreur](../error-responses.md).

**400 Bad Request** - Paramètre manquant
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "quantity: This value should not be blank."
  ]
}
```

**400 Bad Request** - Paramètre invalide (quantity négatif ou zéro)
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "quantity: This value should be positive."
  ]
}
```

**400 Bad Request** - Quantity supérieur à 100
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "quantity: This value should be less than or equal to 100."
  ]
}
```

**400 Bad Request** - Offset négatif
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "offset: This value should be greater than or equal to 0."
  ]
}
```

**400 Bad Request** - Mode invalide
```json
{
  "code": 400,
  "error": "Validation Error",
  "message": "Les données fournies ne sont pas valides",
  "details": [
    "mode: The value you selected is not a valid choice."
  ]
}
```

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

**403 Forbidden** - L'utilisateur n'est pas un Client (si mode = favorite ou author)
```json
{
  "code": 403,
  "error": "Forbidden",
  "message": "User must be a client"
}
```

## Contraintes

- **Sécurité** : Cette route est protégée et nécessite une authentification JWT
- **Authentification** : 
  - Un token JWT valide doit être fourni dans l'en-tête `Authorization`
  - Le token doit être obtenu via `POST /user/login`
  - Le token doit être au format `Bearer <token>`
  - Le token ne doit pas être expiré
- **Rôle requis** : L'utilisateur doit avoir au minimum le rôle `ROLE_USER`
- **Type d'utilisateur** : Si `mode` est `favorite` ou `author`, l'utilisateur connecté doit être une instance de `Client` (hérite de `User`)
- **Pagination** : 
  - `quantity` doit être un entier positif (>= 1) et ne peut pas dépasser 100
  - `offset` est optionnel et vaut 0 par défaut s'il n'est pas fourni
  - `offset` doit être un entier positif ou zéro (>= 0) s'il est fourni
  - La pagination s'applique à tous les modes
- **Modes disponibles** :
  - `all` : Retourne toutes les recettes triées par ID croissant (défaut)
  - `favorite` : Retourne uniquement les recettes favorites du client connecté (conserve l'ordre de la collection)
  - `author` : Retourne uniquement les recettes dont le client connecté est l'auteur (conserve l'ordre de la collection)

## Logique métier

1. **Validation des paramètres** : Les paramètres `quantity`, `offset` (optionnel) et `mode` (optionnel) sont validés
2. **Définition de l'offset** : Si `offset` n'est pas fourni, il est défini à 0 par défaut
3. **Définition du mode** : Si `mode` n'est pas fourni, il est défini à `all` par défaut
4. **Validation du mode** : Si `mode` n'est pas `all`, `favorite`, ou `author`, une erreur 400 est retournée
5. **Récupération des recettes** selon le mode (optimisée avec pagination SQL et préchargement des auteurs) :
   - **Mode `all`** :
     - Utilisation de `findRecipesWithAuthors()` pour récupérer les recettes depuis la base de données
     - Jointure avec l'auteur (`leftJoin`) et préchargement des données de l'auteur (`addSelect`)
     - Tri par ID croissant
     - Pagination au niveau SQL avec `LIMIT` et `OFFSET`
   - **Mode `favorite`** :
     - Vérification que l'utilisateur est un `Client`
     - Utilisation de `findFavoriteRecipes()` pour récupérer les recettes favorites
     - Jointure avec l'auteur et préchargement des données de l'auteur
     - Filtrage via la relation ManyToMany `Recipe -> Client` (favoris)
     - Pagination au niveau SQL avec `LIMIT` et `OFFSET`
   - **Mode `author`** :
     - Vérification que l'utilisateur est un `Client`
     - Utilisation de `findRecipesByAuthor()` pour récupérer les recettes dont le client est l'auteur
     - Jointure avec l'auteur et préchargement des données de l'auteur
     - Filtrage via la relation OneToMany `Client -> Recipe`
     - Pagination au niveau SQL avec `LIMIT` et `OFFSET`
6. **Transformation des données** : Les entités `Recipe` sont transformées en tableaux pour la réponse JSON
   - L'objet `author` est construit avec le nom de l'auteur (préchargé, évite le problème N+1)
7. **Retour des résultats** : Les recettes sont retournées dans un tableau `recipes`

## Exemples d'utilisation

### Exemple 1 : Récupérer les 10 premières recettes
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 10, "offset": 0}'
```

### Exemple 2 : Pagination - Récupérer les 10 recettes suivantes
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 10, "offset": 10}'
```

### Exemple 3 : Récupérer 5 recettes (offset par défaut)
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 5}'
```

### Exemple 4 : Récupérer les recettes favorites
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 10, "mode": "favorite"}'
```

### Exemple 5 : Récupérer les recettes favorites avec pagination
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 10, "offset": 5, "mode": "favorite"}'
```

### Exemple 6 : Récupérer les recettes dont le client est auteur
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 10, "mode": "author"}'
```

### Exemple 7 : Récupérer les recettes dont le client est auteur avec pagination
```bash
curl -X POST http://localhost:8000/api/recipe/get \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 10, "offset": 5, "mode": "author"}'
```

## Relations entre les données

- **Entité Recipe** : Les recettes sont récupérées depuis la table `Recipe` en base de données
- **Client (Author)** : L'objet `author` contient le nom du client auteur via la relation `Recipe -> Client` (ManyToOne)
- **Favoris** : Si `mode` est `favorite`, les recettes sont récupérées via la relation ManyToMany `Client <-> Recipe`
- **Auteur** : Si `mode` est `author`, les recettes sont récupérées via la relation OneToMany `Client -> Recipe`
- **Tri** : 
  - Mode `all` : Les recettes sont triées par ID croissant pour garantir un ordre cohérent lors de la pagination
  - Mode `favorite` : Les recettes sont triées par ID croissant pour garantir un ordre cohérent lors de la pagination
  - Mode `author` : Les recettes sont triées par ID croissant pour garantir un ordre cohérent lors de la pagination
- **Optimisations** :
  - **Pagination SQL** : La pagination est effectuée au niveau de la base de données (via `LIMIT` et `OFFSET`), ce qui évite de charger toutes les recettes en mémoire
  - **Préchargement des auteurs** : Les données des auteurs sont préchargées via `leftJoin` et `addSelect`, évitant le problème N+1 (une requête par auteur)
  - **Performance** : Ces optimisations permettent de gérer efficacement de grandes quantités de données sans impact sur les performances

## Notes importantes

- **Pagination** : Utilisez `quantity` et `offset` pour implémenter la pagination côté client
  - Page 1 : `{"quantity": 10}` ou `{"quantity": 10, "offset": 0}` (recettes 1-10)
  - Page 2 : `{"quantity": 10, "offset": 10}` (recettes 11-20)
  - Page 3 : `{"quantity": 10, "offset": 20}` (recettes 21-30)
- **Offset optionnel** : Si `offset` n'est pas fourni, il est automatiquement défini à 0
- **Mode optionnel** : Si `mode` n'est pas fourni, il est automatiquement défini à `all`
- **Modes disponibles** :
  - `all` : Récupère toutes les recettes triées par ID croissant
  - `favorite` : Récupère uniquement les recettes favorites du client connecté
  - `author` : Récupère uniquement les recettes dont le client connecté est l'auteur
- **Pagination** : La pagination fonctionne avec tous les modes
- **Tableaux vides** : Si aucune recette n'est trouvée (par exemple, si le client n'a pas de favoris, n'est pas auteur, ou si `offset` dépasse le nombre total de recettes), la réponse contiendra `{"recipes": []}`
- **Performance** : 
  - `quantity` est limité à 100 maximum pour garantir de bonnes performances
  - Il est recommandé d'utiliser des valeurs raisonnables pour `quantity` (par exemple, entre 10 et 50)
  - La pagination est effectuée au niveau SQL, ce qui garantit de bonnes performances même avec de grandes quantités de données
  - Les auteurs sont préchargés pour éviter le problème N+1 (une seule requête au lieu d'une par recette)
- **Tri** : 
  - Tous les modes trient les recettes par ID croissant, garantissant un ordre cohérent entre les requêtes et lors de la pagination

