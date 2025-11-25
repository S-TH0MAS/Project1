# Variables d'environnement (En dehors du .env)

### ProxyAwareClient

#### `HTTP_PROXY`
- **Type** : String
- **Description** : Lien proxy afin d'utilser le service ProxyAwareClient avec un proxy
- **Format** : Lien de proxy
- **Exemple** : `http://poevix:rcv8zs6@142.178.48.253:7230`
- **Obtention** :
  [Webshare](https://webshare.io) propose 10 proxy gratuit 
- **Utilisation** : Utilisée par le service `GeminiRequest` car api non disponible dans certains pays

---

### API Gemini

#### `GEMINI_KEY`
- **Type** : String
- **Description** : Clé API pour accéder à l'API Google Gemini (Generative Language API)
- **Format** : Clé API fournie par Google
- **Exemple** : `AIzaSyAbCdEfGhIjKlMnOpQrStUvWxYz1234567`
- **Obtention** : 
  1. Allez sur [Google AI Studio](https://makersuite.google.com/app/apikey) (Avec vpn non dispo dans certains pays (Réunion inclus))
  2. Créez un nouveau projet ou sélectionnez un projet existant
  3. Générez une nouvelle clé API
  4. Copiez la clé et ajoutez-la à votre fichier `.env`
- **Sécurité** : ⚠️ **NE JAMAIS** commiter cette clé dans le dépôt Git
- **Utilisation** : Utilisée par le service `GeminiRequest` pour appeler l'API Gemini

---

## Variables pour le développement (.env.dev)

Ces variables sont spécifiques à l'environnement de développement et permettent de simplifier les tests.

### Authentification de développement

#### `DISABLE_JWT_AUTH`
- **Type** : Boolean
- **Description** : Active ou désactive l'authentification JWT en mode développement
- **Valeurs possibles** : `true` ou `false`
- **Défaut** : `false` (JWT activé)
- **Utilisation** : 
  - Si `true` : L'authentification JWT est désactivée pour les routes `/api/*` en environnement `dev`
  - Si `false` : L'authentification JWT fonctionne normalement
- **Sécurité** : ⚠️ **NE JAMAIS** utiliser cette variable en production
- **Comportement** : 
  - Active le `DevAuthenticator` qui authentifie automatiquement les requêtes vers `/api/*`
  - Permet de tester l'API sans avoir à générer un token JWT à chaque fois
  - Fonctionne uniquement si `APP_ENV=dev`

#### `TEST_USER_EMAIL`
- **Type** : String
- **Description** : Email de l'utilisateur de test utilisé par le `DevAuthenticator`
- **Format** : Adresse email valide
- **Exemple** : `test@example.com`
- **Prérequis** : L'utilisateur avec cet email doit exister dans la base de données
- **Utilisation** : 
  - Utilisé uniquement si `DISABLE_JWT_AUTH=true` et `APP_ENV=dev`
  - L'utilisateur avec cet email sera automatiquement authentifié pour toutes les requêtes vers `/api/*`
- **Sécurité** : ⚠️ **NE JAMAIS** utiliser cette variable en production

---
