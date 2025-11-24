# 📘 Contraintes du projet : Relations BDD & Symfony

Ce document décrit les contraintes techniques du projet concernant :

* les **types de relations en base de données** (One-To-One, One-To-Many, Many-To-Many)
* l'implémentation via **Symfony** (Twig non obligatoire)
* un cadre clair pour organiser le développement

---

## 🔗 Types de relations BDD à utiliser

### 1. One‑To‑One (1:1)

Une entité A est liée à **exactement une** entité B.

**Exemple de cas d'usage projet :**

* Une entité possède un détail stocké dans une entité séparée.

---

### 2. One‑To‑Many (1:N) / Many‑To‑One (N:1)

Une entité A peut posséder **plusieurs** entités B, tandis que chaque B dépend d'un seul A.

**Exemple de cas d'usage projet :**

* Une entité "parent" regroupe plusieurs éléments liés.

---

### 3. Many‑To‑Many (N:N)

Une entité A peut être liée à **plusieurs** entités B, et inversement.

**Exemple de cas d'usage projet :**

* Liaison multiple entre deux éléments sans relation hiérarchique.

---

## 🏗️ Implémentation avec Symfony

Le projet utilise **Symfony** et son ORM **Doctrine** pour gérer les relations.

### Points imposés :

* Utilisation des relations Doctrine correspondant aux besoins du projet.
* Respect des types de relations définis : 1:1, 1:N, N:N.
* **Twig n'est pas obligatoire** :

    * possibilité de travailler uniquement en API
    * ou d'utiliser un autre front si nécessaire

### Bonnes pratiques attendues :

* Définir clairement la propriété propriétaire de la relation (owning side).
* Utiliser les commandes Symfony pour générer les entités et migrations.
* Garder des entités cohérentes, sans logique métier inutile.

---

## 🧰 Commandes utiles Symfony

Créer une entité :

```
php bin/console make:entity
```

Générer et exécuter les migrations :

```
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 📝 Format des commits

### Types complets disponibles :

* **feat** – nouvelle fonctionnalité
* **fix** – correction de bug
* **docs** – documentation
* **style** – formatage sans impact sur le code
* **refactor** – restructuration du code sans changement fonctionnel
* **perf** – amélioration des performances
* **test** – ajout/modification de tests
* **build** – modifications liées au système de build
* **ci** – modifications pour l’intégration continue
* **chore** – maintenance, actions annexes
* **revert** – annulation d’un commit précédent

Format requis :

```
TYPE(PORTÉ): DESCRIPTION
```

### Exemples :

```
feat(entity): ajout relation many-to-one entre Product et Category
docs(bdd): documentation des relations du projet
refactor(controller): simplification de la méthode create()
```
