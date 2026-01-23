# Readme

## Sommaire

* [🎯 Objectif du projet](#objectif)

* [🏗️ Architecture générale](#architecture)

* [📁 Dépôts Git](#depots-git)

* [🖼️ Maquettes](#maquettes)

* [📊 Diagrammes](#diagrammes)

* [🚀 Déploiement](#deploiement)

* [🔐 Fonctionnalités principales](#fonctionnalites-principales)

  * [Authentification utilisateur](#authentification)
  * [Gestion de la réserve de nourriture](#gestion-reserve)
  * [Fonctionnalités IA via Gemini](#fonctionnalites-ia)

* [🚀 Vision long terme](#vision)

* [🧩 Découpage Agile des versions](#decoupage-agile)

* [📚 Stack technique](#stack-technique)

* [✨ Conclusion](#conclusion)

---

<a id="objectif"></a>

# Spécification du projet — Application de gestion de réserve alimentaire avec IA (Gemini)

## 🎯 Objectif du projet

Ce projet consiste à développer une application web permettant aux utilisateurs de gérer leur réserve de nourriture et d'obtenir des suggestions de recettes grâce à l'API Gemini. L'application vise à réduire au maximum les interactions nécessaires de la part de l'utilisateur en automatisant la mise à jour de sa réserve.

---

<a id="architecture"></a>

## 🏗️ Architecture générale

L'application sera conçue **en mobile first** afin d'assurer une expérience optimisée sur smartphones avant d'être adaptée aux écrans plus larges.

* **Backend :** API développée en **Symfony**
* **Frontend :** Interface utilisateur sous **React**
* **Base de données :** **SQLite** (simple, légère, adaptée à un POC ou MVP)
* **Service externe :** **Gemini API** pour les fonctionnalités IA
* **Méthodologie :** Développement **Agile** (itératif + incrémental)

---

<a id="depots-git"></a>

## 📁 Dépôts Git

* **Backend Symfony :** [https://github.com/S-TH0MAS/Project1.git](https://github.com/S-TH0MAS/Project1.git)
* **Frontend React :** [https://github.com/Benjamin-Nativel/Project1-Front](https://github.com/Benjamin-Nativel/Project1-Front)

---

<a id="maquettes"></a>

## 🖼️ Maquettes

* **Vers la maquette fonctionnelle V3** [https://S-TH0MAS.github.io/Project1](https://S-TH0MAS.github.io/Project1)
* **Vers les images** [https://github.com/S-TH0MAS/Project1/tree/master/.source/maquettes](https://github.com/S-TH0MAS/Project1/tree/master/.source/maquettes)

---

<a id="diagrammes"></a>

## 📊 Diagrammes

* **Vers le diagramme V1** [https://github.com/S-TH0MAS/Project1/tree/master/.source/diagrammes/V1](https://github.com/S-TH0MAS/Project1/tree/master/.source/diagrammes/V1)
* **Vers le diagramme V2** [https://github.com/S-TH0MAS/Project1/tree/master/.source/diagrammes/V2](https://github.com/S-TH0MAS/Project1/tree/master/.source/diagrammes/V2)
* **Vers le diagramme V3** [https://github.com/S-TH0MAS/Project1/tree/master/.source/diagrammes/V3](https://github.com/S-TH0MAS/Project1/tree/master/.source/diagrammes/V3)

---

<a id="deploiement"></a>

## 🚀 Déploiement

* **Déploiement en développement :** [https://github.com/S-TH0MAS/Project1/tree/master/.source/deployment/dev.md](https://github.com/S-TH0MAS/Project1/tree/master/.source/deployment/dev.md)
* **Déploiement en production :** [https://github.com/S-TH0MAS/Project1/tree/master/.source/deployment/prod.md](https://github.com/S-TH0MAS/Project1/tree/master/.source/deployment/prod.md)

---

<a id="fonctionnalites-principales"></a>

## 🔐 Fonctionnalités principales

<a id="authentification"></a>

### 1. Authentification utilisateur

* Inscription / Connexion
* Gestion de session

<a id="gestion-reserve"></a>

### 2. Gestion de la réserve de nourriture

* Consultation de la réserve en temps réel
* Ajout manuel d'aliments
* Mise à jour / suppression d'aliments

<a id="fonctionnalites-ia"></a>

### 3. Fonctionnalités IA via Gemini

* Recommandations de plats selon :

  * le contenu réel de la réserve
  * des contraintes (temps, allergies, préférences, matériel…)
* Analyse automatique d'un ticket de caisse *(futur sprint)*
* Mise à jour vocale de la réserve *(futur sprint)*

---

<a id="vision"></a>

## 🚀 Vision long terme

L'utilisateur doit avoir **le moins d'interactions possibles** avec l'application. L'IA devient un assistant autonome pour gérer sa réserve.

Fonctionnalités prévues dans les versions avancées :

* Scan d'un ticket de caisse (photo → extraction → mise à jour auto)
* Commande vocale pour ajouter / enlever des produits
* Suggestions automatiques de recettes intelligentes et personnalisées
* Prévisions de rupture et rappels de péremption

---

<a id="decoupage-agile"></a>

## 🧩 Découpage Agile des versions

### **MVP (Version 1)**

* Authentification
* Gestion manuelle de la réserve
* Appel simple à Gemini : génération de recettes

### **Version 2**

* UI améliorée
* Gestion administration

### **Version 3**

* Ajout des favoris sur ls recette
* Partage des recettes entre user

### **Version 4**

* Scan de ticket de caisse avec IA

### **Version 5**

* Fonctionnalité vocale

---

<a id="stack-technique"></a>

## 📚 Stack technique

### Backend (Symfony)

* Contrôleurs REST
* Validation des données
* Auth via JWT
* ORM Doctrine + SQLite

### Frontend (React)

* React + Vite
* Tailwind
* Gestion d'état (Zustand, Redux ou Context)
* Appels API
* UI simple et responsive

### Gemini API

* Génération de texte (recettes, analyses)
* Extraction sémantique sur ticket de caisse (OCR + analyse)

---

<a id="conclusion"></a>

## ✨ Conclusion

Ce projet combinera une architecture moderne, une base solide en Symfony, une interface fluide en React et la puissance de Gemini pour créer une application intelligente capable d'aider l'utilisateur à optimiser sa gestion alimentaire tout en réduisant ses efforts.
