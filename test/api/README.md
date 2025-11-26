# Tests API - PHPStorm HTTP Client

Ce dossier contient les fichiers de configuration pour tester l'API avec PHPStorm HTTP Client.

## Structure des dossiers

- **user/** : Tests pour les routes utilisateur (création de client, login)
- **category/** : Tests pour les routes catégorie (liste des catégories)
- **item/** : Tests pour les routes item (création d'items utilisateur)
- **inventory/** : Tests pour les routes inventaire (liste des items et inventaire du client)
- **generate_recipe/** : Tests pour la génération de recettes via Gemini

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

2. **Ordre d'exécution recommandé** :
   - Commencez par créer un client via `user/create-user.http`
   - Authentifiez-vous via `user/login.http` pour obtenir un token JWT
   - Utilisez le token pour accéder aux routes protégées :
     - `category/get-categories.http` : Liste des catégories
     - `item/add-item.http` : Créer un nouvel item utilisateur
     - `inventory/get-inventories.http` : Liste des items et inventaire du client
     - `generate_recipe/generate-recipe.http` : Génération de recettes via Gemini

## Documentation détaillée

Pour plus de détails sur chaque groupe de routes, consultez les README.md dans chaque sous-dossier :
- **user/README.md** : Documentation des routes utilisateur
- **category/README.md** : Documentation des routes catégorie
- **item/README.md** : Documentation des routes item
- **inventory/README.md** : Documentation des routes inventaire
- **generate_recipe/README.md** : Documentation de la génération de recettes

