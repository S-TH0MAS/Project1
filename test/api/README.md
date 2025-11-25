# Tests API - PHPStorm HTTP Client

Ce dossier contient les fichiers de configuration pour tester l'API avec PHPStorm HTTP Client.

## Fichiers disponibles

- **create-user.http** : Tests pour la création de client (`POST /user/create`)
- **login.http** : Tests pour l'authentification (`POST /user/login`)
- **http-client.env.json** : Variables d'environnement pour différents environnements

## Utilisation dans PHPStorm

1. **Ouvrir un fichier .http** dans PHPStorm
2. **Configurer l'URL de base** :
   - Par défaut : `http://localhost:8000`
   - Modifiez la variable `@baseUrl` si votre serveur tourne sur un autre port
3. **Exécuter une requête** :
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

2. **Créer un client** avant de tester le login :
   - Exécutez d'abord une requête dans `create-user.http`
   - Notez l'email et le mot de passe créés
   - Utilisez ces identifiants dans `login.http`

## Réponses attendues

### Création de client (POST /user/create)
- **201 Created** : Client créé avec succès
- **400 Bad Request** : Données manquantes (email, password ou name) ou validation échouée
- **409 Conflict** : Utilisateur/Client avec cet email existe déjà

**Champs requis :**
- `email` : Adresse email du client
- `password` : Mot de passe du client
- `name` : Nom du client

### Login (POST /user/login)
- **200 OK** : Connexion réussie, retourne un token JWT
- **401 Unauthorized** : Identifiants incorrects
- **400 Bad Request** : Données manquantes

## Variables d'environnement

Le fichier `http-client.env.json` permet de définir différentes configurations :
- **dev** : Environnement de développement (localhost:8000)
- **prod** : Environnement de production (à configurer)

Pour utiliser un environnement spécifique dans PHPStorm :
1. Cliquez sur l'icône d'environnement en haut à droite
2. Sélectionnez l'environnement souhaité


