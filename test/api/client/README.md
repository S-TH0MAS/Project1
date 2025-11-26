# Tests API - Routes Client

Ce dossier contient les fichiers de test pour les routes liées aux informations du client.

## Fichiers disponibles

- **get-client-info.http** : Tests pour obtenir les informations du client connecté (`GET /api/client`)

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

2. **S'authentifier** avant de tester les informations client :
   - Exécutez d'abord une requête dans `../user/login.http` pour obtenir un token JWT
   - La réponse contiendra un objet JSON avec le champ `token` :
     ```json
     {
       "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
     }
     ```
   - Copiez la valeur du token (sans les guillemets) et remplacez `{{token}}` dans le fichier `get-client-info.http`
   - **Important** : Le token expire après un certain temps, vous devrez peut-être vous reconnecter si vous obtenez une erreur 401

## Réponses attendues

### Obtenir les informations du client (GET /api/client)

#### Succès (200 OK) - Client
Pour un utilisateur de type Client :
```json
{
  "name": "Jean Dupont",
  "email": "client@example.com",
  "roles": ["ROLE_USER"]
}
```

#### Succès (200 OK) - Admin
Pour un utilisateur avec le rôle `ROLE_ADMIN` :
```json
{
  "email": "admin@example.com",
  "roles": ["ROLE_USER", "ROLE_ADMIN"]
}
```
**Note** : Le champ `name` n'est pas retourné pour les admins.

#### Erreurs possibles

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

**403 Forbidden** - L'utilisateur n'est pas un Client (et n'est pas admin)
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

## Structure de la réponse

### Pour un Client
- `name` : Nom du client (string)
- `email` : Adresse email du client (string)
- `roles` : Tableau des rôles du client (array<string>)

### Pour un Admin
- `email` : Adresse email de l'admin (string)
- `roles` : Tableau des rôles de l'admin (array<string>), contient au minimum `ROLE_USER` et `ROLE_ADMIN`
- **Note** : Le champ `name` n'est pas présent dans la réponse pour les admins

## Variables d'environnement

Le fichier `../http-client.env.json` permet de définir différentes configurations :
- **dev** : Environnement de développement (localhost:8000)
- **prod** : Environnement de production (à configurer)

Pour utiliser un environnement spécifique dans PHPStorm :
1. Cliquez sur l'icône d'environnement en haut à droite
2. Sélectionnez l'environnement souhaité

## Notes importantes

### GET /api/client
- Cette route retourne les informations de l'utilisateur connecté
- Le type de réponse dépend du type d'utilisateur :
  - **Client** : Retourne `name`, `email` et `roles`
  - **Admin** : Retourne uniquement `email` et `roles` (pas de `name`)
- Les rôles retournés incluent toujours au minimum `ROLE_USER` (ajouté automatiquement par Symfony)
- Pour tester avec un admin, vous devez avoir un utilisateur avec le rôle `ROLE_ADMIN` en base de données

