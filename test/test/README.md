# Tests API - Route Test Gemini

Ce dossier contient les fichiers de test pour la route de test de connectivité Gemini.

## Fichiers disponibles

- **test-gemini.http** : Tests pour la route de test Gemini (`POST /test/gemini`)

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

2. **Configuration Gemini** :
   - La clé API Gemini doit être configurée dans le fichier `.env` avec la variable `GEMINI_KEY`
   - La route est accessible sans authentification (route publique)

## Réponses attendues

### Test Gemini (POST /test/gemini)
- **200 OK** : Requête réussie, réponse de Gemini retournée
  ```json
  {
    "status": "success",
    "input_prompt": "Bonjour, peux-tu me donner une recette simple ?",
    "gemini_response": "Voici une recette simple..."
  }
  ```

- **500 Internal Server Error** : Erreur lors de l'appel à Gemini
  ```json
  {
    "status": "error",
    "message": "Description de l'erreur"
  }
  ```

**Structure de la réponse (succès) :**
- `status` : Statut de la requête ("success" ou "error")
- `input_prompt` : Le prompt qui a été envoyé à Gemini
- `gemini_response` : La réponse textuelle de Gemini (en cas de succès)

**Structure de la réponse (erreur) :**
- `status` : "error"
- `message` : Message d'erreur décrivant le problème

## Paramètres

### Body (JSON)
| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `prompt` | string | Non | Le prompt à envoyer à Gemini. Par défaut : "hello" |

### Exemples de requêtes

**Avec prompt personnalisé :**
```json
{
  "prompt": "Donne-moi une recette de pâtes"
}
```

**Sans prompt (utilise le défaut "hello") :**
```json
{}
```

**Avec prompt vide (utilise le défaut "hello") :**
```json
{
  "prompt": ""
}
```

## Notes importantes

- Cette route est **publique** et ne nécessite pas d'authentification
- La route est utilisée pour tester la connectivité avec l'API Gemini
- Si le body JSON est invalide ou vide, le prompt par défaut "hello" sera utilisé
- Les erreurs peuvent survenir si :
  - La clé API Gemini n'est pas configurée ou est invalide
  - Il y a un problème de connexion avec l'API Gemini
  - Le service Gemini retourne une erreur

## Variables d'environnement

Assurez-vous que la variable d'environnement suivante est configurée dans votre fichier `.env` :
- `GEMINI_KEY` : Clé API pour accéder à l'API Google Gemini

