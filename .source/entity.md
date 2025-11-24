# 📱 Entités pour le MVP (Version 1)

Voici la liste restreinte des entités à implémenter pour la première version, intégrant les contraintes techniques (1:1, 1:N, N:N, Héritage).

---

## 1. Authentification & Profil (Relations 1:1 et 1:N)

### 👤 `User`

L'utilisateur central. Nécessaire pour la fonctionnalité "Inscription / Connexion".

- **Champs :**
  - `email` (String, unique)
  - `password` (String)
  - `roles` (JSON)
- **Relations :**
  - **One-to-One** vers `UserProfile` (Contrainte technique).
  - **One-to-Many** vers `InventoryItem` (Le stock).
  - **One-to-Many** vers `AbstractNotification` (Les retours système).

### ⚙️ `UserProfile` (Relation One-to-One)

Sépare les détails personnels du compte de connexion.
_Pourquoi en V1 ?_ Pour stocker le nom d'affichage dès le départ sans polluer la table User.

- **Champs :**
  - `username` (String) - _Pour l'affichage "Bonjour Pierre"._
  - `preferences` (Text/JSON) - _Champ simple pour stocker "j'aime pas les brocolis" (utilisé par Gemini)._
- **Relations :**
  - **One-to-One** vers `User`.

---

## 2. Gestion de la Réserve (Relation 1:N)

### 🍎 `InventoryItem`

L'aliment ajouté manuellement. C'est le cœur de la fonctionnalité "Gestion manuelle de la réserve".

- **Champs :**
  - `name` (String) - _Ex: "Pâtes"._
  - `quantity` (Integer/Float) - _Ex: 500._
  - `unit` (String) - _Ex: "g"._
- **Relations :**
  - **Many-to-One** vers `User` (Propriétaire).

---

## 3. Recettes IA (Relation Many-to-Many)

### 🍲 `Recipe`

_Pourquoi en V1 ?_ Pour ne pas perdre une recette générée par Gemini. Si l'utilisateur demande une recette, on la sauvegarde pour éviter de rappeler l'API (coûteux) si il veut la relire 5 minutes après.

- **Champs :**
  - `title` (String)
  - `content` (Text) - _La recette complète générée par Gemini._
- **Relations :**
  - **Many-to-Many** vers `Tag` (Contrainte technique).

### 🏷️ `Tag` (Relation Many-to-Many)

Permet de catégoriser sommairement les recettes générées.
_Pourquoi en V1 ?_ Pour valider la contrainte N:N.

- **Champs :**
  - `name` (String) - _Ex: "Rapide", "Dîner"._
- **Relations :**
  - **Many-to-Many** vers `Recipe`.

---

## 4. Système de Feedback (Héritage)

### 🔔 `AbstractNotification` (Classe Abstraite)

_Pourquoi en V1 ?_ Même si les "alertes péremption" sont V4, vous avez besoin d'un système simple pour valider la contrainte d'héritage maintenant. On l'utilise ici pour des messages système simples (ex: "Bienvenue").

- **Champs :**
  - `message` (String)
  - `createdAt` (DateTime)
- **Relations :**
  - **Many-to-Many** vers `User`.
- **Type d'héritage :** Single Table Inheritance (STI).

### ℹ️ `SystemNotification` (Entité Enfant)

Une notification simple pour la V1.

- **Champs Spécifiques :**
  - `type` (String) - _Ex: "info", "warning"._
  - (Hérite de message et createdAt).

---

## 📝 Résumé pour le MVP

Pour démarrer le projet, lancez ces commandes dans l'ordre :

1.  `make:entity User`
2.  `make:entity UserProfile` (Liaison 1:1)
3.  `make:entity InventoryItem` (Liaison N:1 avec User)
4.  `make:entity Tag`
5.  `make:entity Recipe` (Liaison N:N avec Tag)
6.  `make:entity AbstractNotification` (Abstract)
7.  `make:entity SystemNotification` (Extends AbstractNotification)
