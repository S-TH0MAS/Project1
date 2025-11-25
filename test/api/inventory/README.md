# Tests API - Routes Inventaire

Ce dossier contient les fichiers de test pour les routes liées aux inventaires.

## Fichiers disponibles

- **get-inventories.http** : Tests pour obtenir la liste des inventaires (`GET /api/inventories`)
- **add-inventory.http** : Tests pour ajouter un item à l'inventaire (`POST /api/inventories/add`)
- **remove-inventory.http** : Tests pour retirer une quantité d'un item (`POST /api/inventories/remove`)

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

2. **S'authentifier** avant de tester les inventaires :
   - Exécutez d'abord une requête dans `../user/login.http` pour obtenir un token JWT
   - La réponse contiendra un objet JSON avec le champ `token` :
     ```json
     {
       "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
     }
     ```
   - Copiez la valeur du token (sans les guillemets) et remplacez `{{token}}` dans le fichier `get-inventories.http`
   - **Important** : Le token expire après un certain temps, vous devrez peut-être vous reconnecter si vous obtenez une erreur 401

3. **Avoir des données en base** :
   - Des items par défaut (discr = 'item')
   - Des items dans l'inventaire du client connecté (table Inventory)

## Réponses attendues

### Liste des inventaires (GET /api/inventories)
- **200 OK** : Liste des inventaires retournée avec succès
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
        "name": "Item du client",
        "category": {
          "id": 2,
          "name": "Légumes"
        },
        "img": "item.jpg"
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
- `items` : Tableau contenant tous les items (items par défaut + items du client)
  - `id` : Identifiant unique de l'item (integer)
  - `name` : Nom de l'item (string)
  - `category` : Objet contenant les informations de la catégorie
    - `id` : Identifiant de la catégorie (integer)
    - `name` : Nom de la catégorie (string)
  - `img` : URL ou chemin de l'image de l'item (string, nullable)
- `inventory` : Tableau contenant les items du client avec leurs quantités
  - `item_id` : Identifiant de l'item (integer)
  - `quantity` : Quantité de l'item dans l'inventaire du client (integer)

- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

### Ajouter un item à l'inventaire (POST /api/inventories/add)
- **201 Created** : Inventory créé avec succès
  ```json
  {
    "message": "Inventory created successfully",
    "inventory": {
      "item_id": 1,
      "quantity": 5
    }
  }
  ```
- **200 OK** : Inventory mis à jour avec succès (si l'inventory existait déjà)
  ```json
  {
    "message": "Inventory updated successfully",
    "inventory": {
      "item_id": 1,
      "quantity": 8
    }
  }
  ```
- **400 Bad Request** : Données manquantes (itemId ou quantity) ou quantité invalide (<= 0)
- **404 Not Found** : Item non trouvé
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

**Champs requis :**
- `itemId` : Identifiant de l'item (integer)
- `quantity` : Quantité à ajouter (integer, doit être > 0)

### Retirer une quantité d'un item (POST /api/inventories/remove)
- **200 OK** : Inventory mis à jour avec succès
  ```json
  {
    "message": "Inventory updated successfully",
    "inventory": {
      "item_id": 1,
      "quantity": 3
    }
  }
  ```
- **200 OK** : Inventory supprimé avec succès (si la quantité finale <= 0)
  ```json
  {
    "message": "Inventory removed successfully",
    "inventory": {
      "item_id": 1,
      "quantity": 0
    }
  }
  ```
- **200 OK** : Inventory non trouvé (rien à faire)
  ```json
  {
    "message": "Inventory not found, nothing to remove"
  }
  ```
- **400 Bad Request** : Données manquantes (itemId ou quantity) ou quantité invalide (<= 0)
- **404 Not Found** : Item non trouvé
- **401 Unauthorized** : Token manquant ou invalide
- **403 Forbidden** : L'utilisateur n'est pas un Client

**Champs requis :**
- `itemId` : Identifiant de l'item (integer)
- `quantity` : Quantité à retirer (integer, doit être > 0)

## Variables d'environnement

Le fichier `../http-client.env.json` permet de définir différentes configurations :
- **dev** : Environnement de développement (localhost:8000)
- **prod** : Environnement de production (à configurer)

Pour utiliser un environnement spécifique dans PHPStorm :
1. Cliquez sur l'icône d'environnement en haut à droite
2. Sélectionnez l'environnement souhaité

## Notes importantes

### GET /api/inventories
- Cette route retourne tous les items par défaut (qui n'appartiennent à aucun client) plus tous les items du client connecté
- Les items sont dédupliqués automatiquement (si un item par défaut existe aussi dans l'inventaire du client, il n'apparaît qu'une fois dans `items`)
- Le tableau `inventory` contient uniquement les items qui appartiennent au client avec leur quantité
- Si le client n'a aucun item dans son inventaire, `inventory` sera un tableau vide mais `items` contiendra toujours les items par défaut

### POST /api/inventories/add
- Si l'inventory existe déjà pour cet item, la quantité est ajoutée à la quantité existante
- Si l'inventory n'existe pas, un nouvel inventory est créé avec la quantité spécifiée
- La quantité doit être strictement positive (> 0)

### POST /api/inventories/remove
- Si l'inventory existe, la quantité est retirée de la quantité existante
- Si la quantité finale est <= 0, l'inventory est supprimé automatiquement
- Si l'inventory n'existe pas, la route retourne 200 OK avec un message indiquant qu'il n'y a rien à retirer (pas d'erreur)
- La quantité à retirer doit être strictement positive (> 0)

