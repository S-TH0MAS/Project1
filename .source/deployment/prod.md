# 📘 Guide de Déploiement – Environnement de **Production**

> ⚠️ **Attention :** Les commandes sont adaptées pour **Linux**.
> Sous Windows, utilisez PowerShell, Git Bash ou WSL selon votre environnement.

---

## ✅ Pré-requis

Avant de commencer, assurez-vous d’avoir :

* **Git**
* **PHP 8+**
* **Composer**
* **Node.js & npm** (pour le build du frontend)

---

## 📁 1. Préparation du projet

Créez ou accédez au dossier où sera déployée votre application :

```bash
cd project
```

---

## 🔄 2. Clonage des dépôts

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

Accédez au dossier :

```bash
cd Project1
```

### 📦 Installation des dépendances

Installation complète pour la production :

```bash
composer install
```

---

## 🗄️ 4. Gestion de la Base de Données

### Création de la base de données (SQLite pour simplifier la prod)

```bash
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
```

### (Facultatif) Insertion des données de test

> ⚠️ À utiliser uniquement si vous souhaitez une base préremplie pour la démonstration.

```bash
DATABASE_URL="sqlite:///$PWD/var/data_prod.db" php bin/console doctrine:fixtures:load --no-interaction
```

---

## 📦 Installation optimisée pour la Production

```bash
APP_ENV=prod composer install --no-dev --optimize-autoloader --no-scripts
```

### Nettoyage des caches

```bash
rm -rf var/cache/*
```

### Préparation des caches pour la production

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup
```

---

## 🔐 Génération des clés JWT

Indispensable pour l’authentification :

```bash
php bin/console lexik:jwt:generate-keypair
```

---

# ⚙️ 4. Configuration du fichier `.env.local`

Créez un fichier `.env.local` à la racine du backend.

### 🔌 Variables nécessaires

| Variable       | Description                                                                                                |
| -------------- | ---------------------------------------------------------------------------------------------------------- |
| **HTTP_PROXY**       | Obligatoire pour utiliser Gemini à La Réunion (proxy Webshare conseillé). ⚠️ Proxy gratuit en http = risque potentiel de fuite de la GEMINI_KEY. |
| **GEMINI_KEY** | Clé API Gemini (via AI Studio, nécessite VPN).                                                             |
| **APP_ENV**    | Mettre `prod` pour activer le mode production.                                                             |

### Exemple :

```env
HTTP_PROXY=http://142.111.253.66:7089
GEMINI_KEY=AIzaSyXXXXXXXXXXXXXX
APP_ENV=prod
```

---

# 🎨 5. Configuration du Frontend

Retour au dossier frontend :

```bash
cd ../Project1-Front
```

### Installation des dépendances

```bash
npm install
```

### Build de production

```bash
npm run build
```

### Transfert du build vers Symfony

> Assurez-vous que ce soit bien le *chemin du dossier public* du backend.

```bash
npm run build:to ../Project1/public
```

---

# ▶️ 6. Lancement du serveur de Production

Retourner dans le backend :

```bash
cd ../Project1
```

### Lancer Symfony (mode production)

```bash
symfony server:start
```

> ℹ️ Ce n’est pas un serveur de production complet (comme Nginx + PHP-FPM), mais suffisant pour ce projet.

---

# 🎉 Déploiement terminé
