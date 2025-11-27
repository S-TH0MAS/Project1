# Documentation API - Routes Recettes

Ce dossier contient la documentation complète pour toutes les routes liées aux recettes.

## Routes disponibles

1. **[Génération de recette](generate.md)** - `POST /api/recipe/generate`
   - Génère une recette personnalisée basée sur l'inventaire du client et sa demande
   - Utilise l'API Gemini pour créer la recette
   - Retourne une recette avec un `cache_key` pour la sauvegarder

2. **[Sauvegarde de recette](save.md)** - `POST /api/recipe/save`
   - Sauvegarde une recette générée précédemment en base de données
   - Récupère la recette depuis le cache à l'aide du `cache_key`
   - Ajoute automatiquement la recette aux favoris du client

3. **[Récupération de recettes](get.md)** - `POST /api/recipe/get`
   - Récupère une liste paginée de recettes depuis la base de données
   - Permet la pagination avec `quantity` et `offset` (optionnel, défaut: 0)
   - Permet de filtrer avec le paramètre `mode` : `all` (toutes les recettes), `favorite` (recettes favorites), ou `author` (recettes dont le client est l'auteur)
   - Les recettes sont triées par ID croissant pour tous les modes
   - **Optimisations** : Pagination au niveau SQL et préchargement des auteurs pour éviter le problème N+1

4. **[Suppression de recette](delete.md)** - `DELETE /api/recipe/{id}`
   - Supprime une recette sauvegardée
   - Seul l'auteur de la recette peut la supprimer
   - La suppression est définitive et ne peut pas être annulée

5. **[Gestion des favoris](favorite.md)** - `GET /api/recipe/favorite/add/{id}` et `GET /api/recipe/favorite/remove/{id}`
   - Ajoute ou retire une recette des favoris du client connecté
   - Vérifie automatiquement si la recette est déjà en favoris ou non
   - Gère les erreurs de duplication et d'absence

## Notes générales

- **Format des réponses** : Toutes les réponses sont au format JSON
- **Content-Type** : Les requêtes POST doivent avoir l'en-tête `Content-Type: application/json`
- **Base URL** : Les routes sont accessibles depuis la base URL configurée (ex: `http://localhost:8000`)
- **Gestion des erreurs** : Toutes les erreurs suivent un format JSON uniformisé. Voir [error-responses.md](../error-responses.md) pour plus de détails
- **Dépendances externes** : La route `/api/recipe/generate` dépend de l'API Gemini qui doit être accessible et correctement configurée
- **Configuration requise** : 
  - Variable d'environnement `GEMINI_KEY` doit être définie (pour `/api/recipe/generate`)
  - Variable `HTTP_PROXY` peut être nécessaire selon la localisation (voir `../../env/README.md`)

## Workflow recommandé

1. **Générer une recette** : Utilisez `POST /api/recipe/generate` avec un prompt décrivant la recette souhaitée
2. **Récupérer le cache_key** : La réponse contient un `cache_key` unique
3. **Sauvegarder la recette** : Utilisez `POST /api/recipe/save` avec le `cache_key` pour sauvegarder la recette en base de données
4. **Consulter les favoris** : Utilisez `POST /api/recipe/get` avec `{"quantity": 10, "mode": "favorite"}` pour voir vos recettes favorites
5. **Consulter vos recettes** : Utilisez `POST /api/recipe/get` avec `{"quantity": 10, "mode": "author"}` pour voir les recettes dont vous êtes l'auteur
6. **Parcourir les recettes** : Utilisez `POST /api/recipe/get` pour récupérer une liste paginée de toutes les recettes
7. **Supprimer une recette** : Utilisez `DELETE /api/recipe/{id}` pour supprimer une recette dont vous êtes l'auteur
8. **Gérer les favoris** : Utilisez `GET /api/recipe/favorite/add/{id}` pour ajouter une recette aux favoris et `GET /api/recipe/favorite/remove/{id}` pour la retirer

## Authentification

Toutes les routes nécessitent une authentification JWT. Pour obtenir un token :

1. Faites une requête `POST /user/login` avec vos identifiants
2. Récupérez le token de la réponse
3. Incluez le token dans l'en-tête `Authorization: Bearer <token>` pour toutes les requêtes suivantes

## Liens utiles

- [Documentation sur les réponses d'erreur](../error-responses.md)
- [Configuration de l'environnement](../../env/README.md)

