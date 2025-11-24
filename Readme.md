# Spécification du projet — Application de gestion de réserve alimentaire avec IA (Gemini)

## 🎯 Objectif du projet

Ce projet consiste à développer une application web permettant aux utilisateurs de gérer leur réserve de nourriture et d'obtenir des suggestions de recettes grâce à l'API Gemini. L'application vise à réduire au maximum les interactions nécessaires de la part de l'utilisateur en automatisant la mise à jour de sa réserve.

---

## 🏗️ Architecture générale

L'application sera conçue **en mobile first** afin d'assurer une expérience optimisée sur smartphones avant d'être adaptée aux écrans plus larges.

* **Backend :** API développée en **Symfony**
* **Frontend :** Interface utilisateur sous **React**
* **Base de données :** **SQLite** (simple, légère, adaptée à un POC ou MVP)
* **Service externe :** **Gemini API** pour les fonctionnalités IA
* **Méthodologie :** Développement **Agile** (itératif + incrémental)

---

## 🔐 Fonctionnalités principales

### 1. Authentification utilisateur

* Inscription / Connexion
* Gestion de session

### 2. Gestion de la réserve de nourriture

* Consultation de la réserve en temps réel
* Ajout manuel d'aliments
* Mise à jour / suppression d'aliments

### 3. Fonctionnalités IA via Gemini

* Recommandations de plats selon :

  * le contenu réel de la réserve
  * des contraintes (temps, allergies, préférences, matériel…)
* Analyse automatique d'un ticket de caisse *(futur sprint)*
* Mise à jour vocale de la réserve *(futur sprint)*

---

## 🚀 Vision long terme

L'utilisateur doit avoir **le moins d'interactions possibles** avec l'application. L'IA devient un assistant autonome pour gérer sa réserve.

Fonctionnalités prévues dans les versions avancées :

* Scan d'un ticket de caisse (photo → extraction → mise à jour auto)
* Commande vocale pour ajouter / enlever des produits
* Suggestions automatiques de recettes intelligentes et personnalisées
* Prévisions de rupture et rappels de péremption

---

## 🧩 Découpage Agile des versions

### **MVP (Version 1)**

* Authentification
* Gestion manuelle de la réserve
* Appel simple à Gemini : génération de recettes

### **Version 2**

* UI améliorée
* Prise en compte de contraintes pour les recettes

### **Version 3**

* Scan de ticket de caisse avec IA

### **Version 4**

* Fonctionnalité vocale

---

## 📚 Stack technique

### Backend (Symfony)

* API Platform ou contrôleurs REST
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

## 📦 Livrables (à venir)

* **Maquettes** : images ou lien Figma
* **Modélisation** : liste des entités + schéma UML/ER *(facultatif)*
* **Code source** dans un dépôt Git avec README contenant :

  * instructions d'installation
  * configuration de la base de données
  * migrations
* **Démonstration orale** : 10–15 minutes

## 📁 Dépôts Git

* **Backend Symfony :** [https://github.com/S-TH0MAS/Project1.git](https://github.com/S-TH0MAS/Project1.git)
* **Frontend React :** [https://github.com/Benjamin-Nativel/Project1-Front](https://github.com/Benjamin-Nativel/Project1-Front)

## ✨ Conclusion

Ce projet combinera une architecture moderne, une base solide en Symfony, une interface fluide en React et la puissance de Gemini pour créer une application intelligente capable d'aider l'utilisateur à optimiser sa gestion alimentaire tout en réduisant ses efforts.
