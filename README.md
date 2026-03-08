# Livre Interactif

Informatisation des *Livres Dont Vous Êtes Le Héros* de nos enfances.

Application web PHP/PostgreSQL permettant de lire des livres interactifs en ligne, avec gestion
des personnages, inventaire, combats et progression narrative.

---

## Fonctionnalités

- **Lecture interactive** : paragraphes numérotés, choix de navigation
- **Système de jeu Endurance / Habileté / Psi** (EHP) :
  - Création de personnage avec lancer de dés (2d6+20, 1d6+3, 1d6+3)
  - Système de combat (2d6, table de résolution)
  - Règle de fuite (2d6 vs Habileté)
  - Équipement de départ (poignard, épée, armure, gourde, sac à dos)
- **Gestion de personnage** : feuille de personnage, inventaire, historique de lecture
- **Persistance** : sessions de lecture sauvegardées, reprise possible
- **Administration** : import de livres et systèmes de jeu via script SQL
- **Déploiement** : GitHub Actions vers AlwaysData (SSH + rsync)

---

## Installation

### Prérequis

- PHP 8.1+
- PostgreSQL 14+
- Serveur web avec mod_rewrite (Apache) ou équivalent

### 1. Cloner le dépôt

```bash
git clone https://github.com/lcocault/livre-interactif.git
cd livre-interactif
```

### 2. Configuration

```bash
cp config/config.php.example config/config.php
# Éditez config/config.php avec vos paramètres de base de données
```

### 3. Créer la base de données

```bash
# Créer le schéma
psql -U <user> -d <database> -f db/schema.sql

# Importer le système de jeu EHP
psql -U <user> -d <database> -f db/seeds/game_system_ehp.sql

# (Optionnel) Charger un exemple de livre (9 paragraphes, à des fins de démonstration)
psql -U <user> -d <database> -f db/seeds/example_book.sql

# (Optionnel) Charger le livre complet "Le Tombeau du Vampire" (290 paragraphes)
psql -U <user> -d <database> -f db/seeds/tombeau_vampire.sql
```

### 4. Configurer le serveur web

Pointez la racine du serveur web vers le dossier `public/`.  
Le fichier `public/.htaccess` gère le routage via `mod_rewrite`.

### 5. Premier démarrage

Accédez à l'application. **Le premier utilisateur inscrit obtient automatiquement les droits
administrateur.**

---

## Structure du projet

```
livre-interactif/
├── public/                # Racine web (seul dossier accessible)
│   ├── index.php          # Front controller
│   ├── .htaccess          # Réécriture d'URL
│   ├── css/style.css      # Feuille de style
│   └── js/app.js          # JavaScript minimal
├── src/                   # Code PHP
│   ├── bootstrap.php      # Autoload, config, session
│   ├── Database.php       # Connexion PDO singleton
│   ├── Router.php         # Routeur regex
│   ├── Controller/        # Contrôleurs MVC
│   ├── Model/             # Modèles (accès BDD)
│   └── Service/           # Services métier (dés, combat)
├── templates/             # Vues PHP
│   ├── layout.php         # Layout principal
│   ├── auth/              # Login, inscription
│   ├── book/              # Bibliothèque, lecture
│   ├── character/         # Feuille de personnage
│   └── admin/             # Administration
├── db/
│   ├── schema.sql         # Schéma PostgreSQL complet
│   └── seeds/
│       ├── game_system_ehp.sql   # Système EHP
│       ├── example_book.sql      # Exemple de livre (9 paragraphes, démo)
│       └── tombeau_vampire.sql   # "Le Tombeau du Vampire" (290 paragraphes, complet)
├── config/
│   └── config.php.example # Modèle de configuration
└── .github/workflows/
    └── deploy.yml         # Déploiement automatisé
```

---

## Import de livres

Les livres sont importés via des scripts SQL depuis le panneau d'administration (`/admin/import`).

Le script doit :
1. Insérer un enregistrement dans `books` (référençant un `game_system_id`)
2. Insérer les paragraphes dans `paragraphs`
3. Insérer les choix de navigation dans `choices`
4. Insérer les rencontres de combat dans `combat_encounters` (si applicable)

Consultez `db/seeds/tombeau_vampire.sql` pour un exemple complet (290 paragraphes),
ou `db/seeds/example_book.sql` pour un exemple minimal (9 paragraphes, démo).

---

## Déploiement GitHub Actions

Configurez les secrets suivants dans votre dépôt GitHub :

| Secret           | Description                                      |
|------------------|--------------------------------------------------|
| `SSH_PRIVATE_KEY`| Clé SSH privée pour se connecter au serveur      |
| `SSH_KNOWN_HOSTS`| Empreinte du serveur (sortie de `ssh-keyscan`)   |
| `SSH_USER`       | Nom d'utilisateur SSH AlwaysData                 |
| `SSH_HOST`       | Nom d'hôte SSH AlwaysData                        |
| `DEPLOY_PATH`    | Chemin absolu du dossier de déploiement          |

Chaque push sur `master` déclenche automatiquement le déploiement des fichiers applicatifs.

> **Note :** Les scripts de base de données (`db/schema.sql`, `db/seeds/`) ne sont **pas** appliqués
> automatiquement lors du déploiement. Le mainteneur les applique manuellement selon les besoins
> (évolutions du schéma ou des données).

---

## Systèmes de jeu

Le système est conçu pour supporter plusieurs systèmes de jeu.
Chaque système est défini par un enregistrement JSON dans la table `game_systems`.

**Système EHP (Endurance / Habileté / Psi)** — fourni :
- ENDURANCE : 2d6 + 20 (combat, survie)
- HABILETÉ : 1d6 + 3 (agilité, fuite)
- PSI : 1d6 + 3 (résistance aux sortilèges)
- Combat : lancer 2d6, comparer à la table fournie dans chaque paragraphe
- Fuite : 2d6 > Habileté → -3 Endurance
