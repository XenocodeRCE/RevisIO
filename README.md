<p align="center">
  <img src="https://i.imgur.com/n7x6RzZ.png" alt="RevisIO logo" width="180" />
</p>

<h1 align="center">RevisIO</h1>

<p align="center">
  Réviser, simplement. — Flashcards, QCM, IA et gamification.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-8892BF?style=flat-square&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/Vanilla_JS-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black" />
  <img src="https://img.shields.io/badge/OpenAI-API-412991?style=flat-square&logo=openai&logoColor=white" />
  <img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" />
</p>

---

## Présentation

**RevisIO** est une application web de révision légère.

Elle propose :

- **Flashcards** – mode entraînement avec retournement de carte animé
- **QCM** – questions à choix multiple avec correction immédiate
- **Mode Évaluation** – chronomètre 5 secondes par question
- **Génération IA** – création de decks entiers via IA
- **Partage de decks** – lien de partage direct entre utilisateurs
- **Gamification** – points XP, niveaux, trophées, série de jours
- **Thème clair / sombre** – animé, persisté en localStorage
- **100% responsive** – optimisé mobile et desktop

---

## Installation

### Prérequis

- PHP 8.1+ (serveur web ou `php -S`)
- Une clé API IA (OpenAI-compatible, donc AlbertAPI) (pour la génération de decks par IA)

### Étapes

```bash
git clone https://github.com/votre-compte/RevisIO.git
cd RevisIO

# Créer le fichier de config API à partir du template
cp api/openai.php.example api/openai.php
# Puis éditer api/openai.php et remplacer VOTRE_CLE_API_ICI
```

```bash
# Lancer en local
php -S localhost:8000
```

Ouvrir `http://localhost:8000` dans le navigateur.

> Le dossier `data/` et le fichier `users.json` sont créés automatiquement au premier usage.

---

## Structure du projet

```
RevisIO/
├── index.php           # Landing page + authentification (sans mot de passe)
├── app.php             # SPA principale (toutes les vues)
├── users.json          # Base utilisateurs (gitignorée)
├── data/               # Decks JSON + profils utilisateurs (gitignorée)
│   └── decks.json
├── api/
│   ├── api.php         # API REST PHP (decks, stats, partage…)
│   └── openai.php      # Proxy OpenAI — contient la clé API (gitignorée)
└── assets/
    ├── css/styles.css
    ├── js/
    │   ├── study.js    # Logique QCM / flashcards
    │   ├── ui.js       # Navigation, vues, modales
    │   └── utils.js    # Thème, sanitisation, helpers
    └── sounds/
```

---

## Authentification

RevisIO utilise un système **sans mot de passe** : à l'inscription, un code à 6 chiffres est généré et remis à l'utilisateur. Ce code est son identifiant unique. Pas d'email, pas de mot de passe, pas de cookie de session persistant.

---

## Génération IA

La route `api/openai.php` agit comme proxy vers l'API OpenAI. Elle intègre une étape de **modération automatique** avant chaque requête.


## Licence

[MIT](LICENSE) — libre d'utilisation, de modification et de distribution.