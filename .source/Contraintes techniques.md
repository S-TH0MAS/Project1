# 📋 TP Symfony (2 semaines) : a

Ce document décrit les contraintes obligatoires et les exigences du projet Symfony concernant :( ce document est a adapté a notre projet sur certains point)

- les **maquettes** à réaliser avant le développement
- la **modélisation** des entités et relations
- les **fonctionnalités minimales** à implémenter
- les **exigences techniques** Symfony/Doctrine
- les **livrables** attendus

---

## 🎨 2. Contraintes obligatoires

### Maquettes

Les maquettes doivent être réalisées avant le début du développement et présentées à l'enseignant.

**Pages minimales :**

- Accueil
- Liste des événements
- Espace utilisateur
- Inscription / Connexion

### Modélisation

Fournir dans un document ou un README :

- Liste des entités
- Description des relations et cardinalités
- Diagramme UML ou diagramme entités/relations (Facultatif)

### Relations imposées

- **Héritage** (ex. Utilisateur → Organisateur, Participant, Intervenant)
- **OneToOne** (ex. Utilisateur ↔ Profil)
- **OneToMany** (ex. Organisateur → Événements)
- **ManyToMany** (ex. Participants ↔ Activités)

---

## ⚙️ 3. Fonctionnalités minimales

### Gestion des utilisateurs

- Inscription / Connexion / Déconnexion
- Rôles simples (Utilisateurs, Administrateurs)
- Page de profil utilisateur

### Gestion des événements

- Création, édition, suppression (Administrateurs)
- Consultation, recherche, filtrage (Utilisateurs)

### Gestion des activités

- Chaque événement contient plusieurs activités
- Possibilité d'utiliser l'héritage pour différents types d'activités
- Informations minimales : titre, type, horaire, capacité, intervenants

### Inscriptions des utilisateurs

- Inscription d'un utilisateur (selon votre modèle)
- Utilisation d'une relation `ManyToMany`
- Consultation de ses inscriptions dans l'espace utilisateur

---

## 🛠️ 4. Exigences techniques

- Symfony
- Doctrine ORM
- Migrations
- Architecture propre (contrôleurs / entités / formulaires / templates)

---

## 📦 5. Livrables

1. **Maquettes** (images ou lien Figma)
2. **Modélisation** (liste d'entités + schéma UML/ER) (Facultatif)
3. **Code source** dans un dépôt Git avec README :

   - Installation
   - Configuration BDD
   - Migrations

4. **Démonstration orale** (10–15 minutes)

---

## ⭐ 6. Bonus possibles

- Back-office avancé
- Upload d'images (affiche événement)
