# 📘 Guide de Déploiement – Environnement de Développement

> ⚠️ **Attention :** Les commandes ci-dessous sont réalisées sur **Linux**.
> Pour Windows, adaptez les équivalences (PowerShell / Git Bash / WSL).

---

## ✅ Pré-requis

Assurez-vous d’avoir installé :

* **Git**
* **PHP 8+**
* **Composer**
* **Symfony CLI**
* **Node & npm** (pour le frontend)

---

## 📁 1. Création du dossier projet

```bash\mkdir project
cd project
```

---

## 🔄 2. Clonage des dépôts Git

### 🎨 Frontend (React)

```bash
git clone https://github.com/Benjamin-Nativel/Project1-Front.git
```

### 🛠️ Backend (Symfony)

```bash
git clone https://github.com/S-TH0MAS/Project1
```

---

# 🧩 3. Configuration du Backend

Accédez au dossier du backend :

```bash
cd Project1
```

### 📦 Installation des dépendances

```bash
composer install
```

### 🗄️ Initialisation de la base de données

> Base de données utilisée : **SQLite**, parfaite pour un environnement de test.

```bash
php bin/console doctrine:migrations:migrate
```

### 🌱 (Facultatif) Charger les fixtures de développement

```bash
php bin/console doctrine:fixtures:load
```

### 🔐 Génération des clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

---

## ⚙️ 4. Configuration du fichier `.env.local`

Créez un fichier `.env.local` à la racine du backend pour définir vos variables locales.

### 🔌 Variables nécessaires

| Variable             | Description                                                                                                                                      |
| -------------------- |--------------------------------------------------------------------------------------------------------------------------------------------------|
| **HTTP_PROXY**       | Obligatoire pour utiliser Gemini à La Réunion (proxy Webshare conseillé). ⚠️ Proxy gratuit en http = risque potentiel de fuite de la GEMINI_KEY. |
| **GEMINI_KEY**       | Clé API Gemini (à générer sur *AI Studio*, nécessite VPN).                                                                                       |
| **DISABLE_JWT_AUTH** | Mettre `true` pour désactiver l’auth JWT en dev. `Authorization` doit etre retiré des headers                                                    |
| **TEST_USER_EMAIL**  | Email de l’utilisateur auto-connecté lorsque JWT est désactivé.                                                                                  |

### Exemple de `.env.local`

```env
HTTP_PROXY=http://142.111.253.66:7089
GEMINI_KEY=AIzaSyXXXXXXXXXXXXXX

DISABLE_JWT_AUTH=true
TEST_USER_EMAIL=test@test.mail
```

---

## ▶️ 5. Démarrer le serveur backend

```bash
symfony server:start
```

Le backend tourne par défaut sur :
➡️ `http://localhost:8000`

---

# 🎨 6. Configuration du Frontend

Retournez dans le dossier principal :

```bash
cd ../Project1-Front
```

### 📦 Installation des dépendances

```bash
npm install
```

### ▶️ Lancer le serveur de développement

```bash
npm run dev
```

---

## 🔗 Configuration de l’URL API côté Frontend

Par défaut, le frontend appelle :

```
http://localhost:8000
```

Vous pouvez modifier cela en créant un fichier `.env.local` dans le frontend :

### Exemple

```env
VITE_API_BASE_URL=http://192.168.1.23:8000
```

---

# 🎉 Déploiement prêt !
