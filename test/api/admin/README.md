# Tests API - Routes Admin

Ce dossier contient les fichiers de test pour les routes administrateur.

## Fichiers disponibles

- **category.http** : Tests pour les routes de gestion des catégories (`POST /api/admin/category/add`, `PATCH /api/admin/category/update/{id}`, `DELETE /api/admin/category/delete/{id}`)
- **user.http** : Tests pour les routes de gestion des utilisateurs (`GET /api/admin/user`, `DELETE /api/admin/user/delete/{id}`)

## Utilisation dans PHPStorm

1. **Ouvrir un fichier .http** dans PHPStorm
2. **Configurer l'URL de base** :
   - Par défaut : `http://localhost:8000`
   - Modifiez la variable `@baseUrl` si votre serveur tourne sur un autre port
3. **Configurer le token JWT ADMIN** :
   - Obtenez un token via `POST /user/login` avec un utilisateur ayant le rôle `ROLE_ADMIN`
   - Remplacez `{{adminToken}}` par votre token JWT dans les requêtes
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

2. **S'authentifier en tant qu'admin** :
   - Exécutez d'abord une requête dans `../user/login.http` avec un compte admin pour obtenir un token JWT
   - La réponse contiendra un objet JSON avec le champ `token` :
     ```json
     {
       "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
     }
     ```
   - Copiez la valeur du token (sans les guillemets) et remplacez `{{adminToken}}` dans les fichiers de test
   - **Important** : Le token expire après un certain temps, vous devrez peut-être vous reconnecter si vous obtenez une erreur 401

3. **Avoir des données en base** :
   - Pour les tests de catégories : avoir des catégories existantes pour les tests de mise à jour/suppression
   - Pour les tests d'utilisateurs : avoir des utilisateurs existants pour les tests de suppression

## Routes disponibles

### Catégories (AdminCategoryController)

#### POST /api/admin/category/add
Crée une nouvelle catégorie.

**Body (JSON) :**
```json
{
  "name": "Nouvelle Catégorie"
}
```

**Réponses attendues :**
- **201 Created** : Catégorie créée avec succès
- **400 Bad Request** : Erreur de validation (name manquant, vide, trop long)
- **403 Forbidden** : L'utilisateur n'est pas un admin
- **409 Conflict** : Une catégorie avec ce nom existe déjà

#### PATCH /api/admin/category/update/{id}
Met à jour une catégorie existante.

**Body (JSON) :**
```json
{
  "name": "Catégorie Modifiée"
}
```

**Réponses attendues :**
- **200 OK** : Catégorie mise à jour avec succès
- **400 Bad Request** : Erreur de validation
- **404 Not Found** : Catégorie non trouvée
- **403 Forbidden** : L'utilisateur n'est pas un admin
- **409 Conflict** : Une catégorie avec ce nom existe déjà

#### DELETE /api/admin/category/delete/{id}
Supprime une catégorie.

**Réponses attendues :**
- **200 OK** : Catégorie supprimée avec succès
- **404 Not Found** : Catégorie non trouvée
- **403 Forbidden** : L'utilisateur n'est pas un admin
- **409 Conflict** : La catégorie a des items associés et ne peut pas être supprimée

### Utilisateurs (AdminUserController)

#### GET /api/admin/user
Liste tous les utilisateurs (User et Client).

**Réponses attendues :**
- **200 OK** : Liste des utilisateurs retournée avec succès
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
      }
    ],
    "count": 2
  }
  ```
- **403 Forbidden** : L'utilisateur n'est pas un admin

#### DELETE /api/admin/user/delete/{id}
Supprime un utilisateur.

**Réponses attendues :**
- **200 OK** : Utilisateur supprimé avec succès
- **400 Bad Request** : Tentative de suppression de son propre compte
- **404 Not Found** : Utilisateur non trouvé
- **403 Forbidden** : L'utilisateur n'est pas un admin

## Notes importantes

### Sécurité
- Toutes les routes nécessitent le rôle `ROLE_ADMIN`
- Les tokens JWT doivent être valides et non expirés
- Un admin ne peut pas supprimer son propre compte

### Catégories
- Les noms de catégories doivent être uniques
- Une catégorie ne peut pas être supprimée si elle a des items associés
- Les validations incluent : name requis, non vide, max 255 caractères

### Utilisateurs
- La route GET retourne tous les utilisateurs (User et Client)
- Les Clients ont un champ `name` supplémentaire dans la réponse
- Le champ `type` indique si l'utilisateur est un `user` ou un `client`
- La suppression d'un utilisateur supprime également toutes ses relations (ClientItems, Inventories, etc.) grâce à `orphanRemoval: true`

## Variables d'environnement

Le fichier `../http-client.env.json` permet de définir différentes configurations :
- **dev** : Environnement de développement (localhost:8000)
- **prod** : Environnement de production (à configurer)

Pour utiliser un environnement spécifique dans PHPStorm :
1. Cliquez sur l'icône d'environnement en haut à droite
2. Sélectionnez l'environnement souhaité

