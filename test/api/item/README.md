# Tests API - Routes Item

Ce dossier contient les fichiers de test pour les routes liées aux items.

## Fichiers disponibles

- **add-item.http** : Tests pour créer un nouvel item utilisateur (`POST /api/items/add`) - **Ancien fichier, conservé pour compatibilité**
- **client-items.http** : Tests complets pour les items client (`POST /api/items/add`, `PATCH /api/items/{id}`, `DELETE /api/items/{id}`)
- **admin-items.http** : Tests complets pour les items admin (`POST /api/admin/items/add`, `PATCH /api/admin/items/{id}`, `DELETE /api/admin/items/{id}`)

## Utilisation dans PHPStorm

1. **Ouvrir un fichier .http** dans PHPStorm
2. **Configurer l'URL de base** :
   - Par défaut : `http://localhost:8000`
   - Modifiez la variable `@baseUrl` si votre serveur tourne sur un autre port
3. **Configurer le token JWT** :
   - Obtenez un token via `POST /user/login` (voir `../user/login.http`)
   - Remplacez `{{token}}` par votre token JWT dans les requêtes
4. **Exécuter une requête** :
   - Cliquez sur le bouton ▶️ à côté de la requête
   - Ou utilisez `Ctrl+Enter` (Windows/Linux) ou `Cmd+Enter` (Mac)

## Prérequis

1. **Démarrer le serveur Symfony** :
   ```bash
   php -S localhost:8000 -t public
   ```
   Ou avec Symfony CLI :
   ```bash
   symfony server:start
   ```

2. **S'authentifier** avant de tester les items :
   - Exécutez d'abord une requête dans `../user/login.http` pour obtenir un token JWT
   - La réponse contiendra un objet JSON avec le champ `token` :
     ```json
     {
       "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
     }
     ```
   - Copiez la valeur du token (sans les guillemets) et remplacez `{{token}}` dans le fichier `add-item.http`
   - **Important** : Le token expire après un certain temps, vous devrez peut-être vous reconnecter si vous obtenez une erreur 401

3. **Avoir des catégories en base** :
   - Des catégories doivent exister dans la base de données
   - Vous pouvez vérifier les catégories disponibles via `GET /api/categories` (voir `../category/get-categories.http`)

## Réponses attendues

### Créer un nouvel item client (POST /api/items/add)
- **201 Created** : Item créé avec succès
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
- **400 Bad Request** : Données manquantes (name ou category) ou name vide
  ```json
  {
    "error": "name and category are required"
  }
  ```
  ou
  ```json
  {
    "error": "name cannot be empty"
  }
  ```
- **404 Not Found** : Catégorie non trouvée
  ```json
  {
    "error": "Category not found"
  }
  ```
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

**Champs requis :**
- `name` : Nom de l'item (string, requis, ne peut pas être vide)
- `category` : Catégorie de l'item (integer ou string, requis)
  - Peut être l'ID de la catégorie (integer)
  - Ou le nom de la catégorie (string)

**Champs optionnels :**
- `img` : Image de l'item (actuellement non géré, toujours null)

## Variables d'environnement

Le fichier `../http-client.env.json` permet de définir différentes configurations :
- **dev** : Environnement de développement (localhost:8000)
- **prod** : Environnement de production (à configurer)

Pour utiliser un environnement spécifique dans PHPStorm :
1. Cliquez sur l'icône d'environnement en haut à droite
2. Sélectionnez l'environnement souhaité

## Réponses attendues - Routes Admin

### Créer un nouvel item admin (POST /api/admin/items/add)
- **201 Created** : Item créé avec succès (identique à la route client)
- **400 Bad Request** : Données manquantes ou invalides
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un admin

### Mettre à jour un item admin (POST /api/admin/items/update/{id})
- **200 OK** : Item mis à jour avec succès
  ```json
  {
    "message": "Item updated successfully",
    "item": {
      "id": 1,
      "name": "Pomme Globale Modifiée",
      "category": "Fruits",
      "img": "valid-1234567890.png"
    }
  }
  ```
- **400 Bad Request** : Aucune donnée fournie (ni data ni image)
- **404 Not Found** : Item non trouvé ou c'est un ClientItem (seuls les Item globaux sont modifiables)
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un admin

### Supprimer un item admin (DELETE /api/admin/items/delete/{id})
- **200 OK** : Item supprimé avec succès
  ```json
  {
    "message": "Item deleted successfully"
  }
  ```
- **404 Not Found** : Item non trouvé ou c'est un ClientItem (seuls les Item globaux sont supprimables)
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un admin

## Réponses attendues - Routes Client

### Mettre à jour un item client (POST /api/items/update/{id})
- **200 OK** : Item mis à jour avec succès
  ```json
  {
    "message": "Item updated successfully",
    "item": {
      "id": 1,
      "name": "Pomme Client Modifiée",
      "category": "Fruits",
      "img": "valid-1234567890.png"
    }
  }
  ```
- **400 Bad Request** : Aucune donnée fournie (ni data ni image)
- **404 Not Found** : Item non trouvé ou n'appartient pas au client connecté
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

### Supprimer un item client (DELETE /api/items/delete/{id})
- **200 OK** : Item supprimé avec succès
  ```json
  {
    "message": "Item deleted successfully"
  }
  ```
- **404 Not Found** : Item non trouvé ou n'appartient pas au client connecté
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

## Notes importantes

### POST /api/items/add (Client)
- Cette route crée un `ClientItem` (item personnalisé appartenant à l'utilisateur connecté)
- L'item est automatiquement lié au client connecté
- La catégorie peut être spécifiée soit par son ID (integer) soit par son nom (string)
- Si le nom de l'item contient uniquement des espaces, il sera considéré comme vide et retournera une erreur 400
- L'item créé est un `ClientItem` qui hérite de `Item`, il apparaîtra donc dans la liste des items de l'utilisateur

### POST /api/admin/items/add (Admin)
- Cette route crée un `Item` global (non lié à un client)
- L'item est accessible à tous les utilisateurs
- Les mêmes règles de validation s'appliquent que pour les items client
- Seuls les utilisateurs avec le rôle `ROLE_ADMIN` peuvent créer des items globaux

### POST /api/items/update/{id} (Client)
- Permet la mise à jour partielle d'un item client
- Utilise POST au lieu de PATCH pour supporter multipart/form-data
- Seuls les champs fournis seront mis à jour (nom, catégorie, image)
- L'image peut être mise à jour indépendamment
- Seul le propriétaire de l'item peut le modifier

### POST /api/admin/items/update/{id} (Admin)
- Permet la mise à jour partielle d'un item global
- Utilise POST au lieu de PATCH pour supporter multipart/form-data
- Seuls les `Item` globaux peuvent être modifiés (pas les `ClientItem`)
- Les mêmes règles de validation s'appliquent que pour les items client

### DELETE /api/items/delete/{id} (Client)
- Supprime un item client et son image associée
- Seul le propriétaire de l'item peut le supprimer

### DELETE /api/admin/items/delete/{id} (Admin)
- Supprime un item global et son image associée
- Seuls les `Item` globaux peuvent être supprimés (pas les `ClientItem`)

### Différence entre Item et ClientItem
- **Item** : Item global créé par un admin, accessible à tous les utilisateurs
- **ClientItem** : Item personnalisé créé par un client, uniquement accessible à ce client
- Les admins peuvent créer/modifier/supprimer uniquement les `Item` globaux
- Les clients peuvent créer/modifier/supprimer uniquement leurs propres `ClientItem`

### Différence avec POST /api/inventories/add
- `POST /api/items/add` : Crée un nouvel item personnalisé (ClientItem) pour l'utilisateur
- `POST /api/inventories/add` : Ajoute une quantité d'un item existant (par défaut ou personnalisé) à l'inventaire de l'utilisateur

