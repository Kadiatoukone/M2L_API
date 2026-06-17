# M2L API

## Description du projet

La **Maison des Ligues de Lorraine (M2L)** est une structure régionale financée par le Conseil Régional dont la mission de service public est de mettre à disposition des ligues sportives (football, tennis, judo, etc.) des infrastructures variées : locaux administratifs, salles de réunion, amphithéâtres et complexes sportifs.

Jusqu'à présent, la gestion de ces espaces reposait sur des processus manuels vieillissants : les demandes de réservation arrivaient par téléphone ou par courriel, saturant le secrétariat et augmentant le risque d'erreurs (doubles réservations, oublis, etc.). Dans le cadre de sa transformation numérique, la M2L a souhaité moderniser son image et fluidifier ses échanges avec les ligues.

Cette API REST, développée avec **Symfony 7.4**, constitue le cœur du système d'information M2L. Elle sert de pont entre deux applications clientes :
- Une **application web** destinée aux gestionnaires de la M2L
- Une **application mobile** destinée aux adhérents des ligues sportives

Elle centralise toute la logique métier : gestion des salles, des réservations, des adhérents et des gestionnaires.

---

## Description de l'API

L'API expose des endpoints REST sous le préfixe `/api` et retourne des réponses au format **JSON**.

### Entités principales

| Entité | Description |
|---|---|
| `Adherent` | Membre d'une ligue sportive, utilisateur de l'application mobile |
| `Gestionnaires` | Administrateur de la M2L, utilisateur de l'application web |
| `Salles` | Espaces mis à disposition (salles de réunion, amphithéâtres, etc.) |
| `TypeSalle` | Catégorie d'une salle |
| `Reservations` | Demande de réservation d'une salle par un adhérent |
| `Horaire` | Créneaux horaires associés aux salles |
| `Commentaire` | Commentaires liés aux réservations |

### Endpoints disponibles

| Méthode | Route | Accès | Description |
|---|---|---|---|
| POST | `/api/register` | Public | Inscription d'un nouvel adhérent |
| POST | `/api/login_check` | Public | Connexion gestionnaire (retourne un JWT) |
| POST | `/api/adherent/login_check` | Public | Connexion adhérent (retourne un JWT) |
| GET/POST/PUT/DELETE | `/api/salles` | Authentifié | Gestion des salles |
| GET/POST/PUT/DELETE | `/api/types-salles` | Authentifié | Gestion des types de salle |
| GET/POST/PUT/DELETE | `/api/reservations` | Authentifié | Gestion des réservations |
| GET/POST/PUT/DELETE | `/api/gestionnaires` | ROLE_SUPER_ADMIN | Gestion des gestionnaires |
| POST | `/api/upload` | Authentifié | Upload d'images|

---

## Initialisation du projet

### Prérequis

- PHP >= 8.2
- Composer
- Symfony CLI
- MySQL / MariaDB

### Créer un nouveau projet Symfony

```bash
symfony new nom_du_projet --version="7.4.*"
```

### Installer les dépendances

```bash
composer install
```

### Configurer l'environnement

Copier le fichier `.env` et renseigner les variables :

```bash
cp .env .env.local
```

Modifier `.env.local` :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/m2l_db"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
```

### Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

### Créer la base de données et jouer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### (Optionnel) Charger les données de test

```bash
php bin/console doctrine:fixtures:load
```

### Lancer le serveur de développement

```bash
symfony server:start
```

L'API sera disponible sur `https://localhost:8000`.

---

## Créer une Entity et effectuer les migrations

### Créer une nouvelle entité

```bash
php bin/console make:entity NomDeLEntite
```

Le maker interactif vous guidera pour ajouter les champs souhaités.

### Générer le fichier de migration

```bash
php bin/console make:migration
```

### Appliquer la migration en base de données

```bash
php bin/console doctrine:migrations:migrate
```

---

## Sécurité

### Authentification JWT

L'API utilise **LexikJWTAuthenticationBundle** pour sécuriser les échanges. Chaque requête sur une route protégée doit inclure un token JWT dans le header HTTP :

```
Authorization: Bearer <token>
```

### Double système d'authentification

Deux points d'entrée distincts sont prévus selon le type d'utilisateur :

- `/api/login_check` — pour les **gestionnaires** (identifiant + mot de passe)
- `/api/adherent/login_check` — pour les **adhérents** (email + mot de passe)

### Hiérarchie des rôles

```
ROLE_SUPER_ADMIN
    └── ROLE_GESTIONNAIRE
```

Les routes `/api/gestionnaires` sont réservées au `ROLE_SUPER_ADMIN`. Les autres routes protégées sont accessibles à tout utilisateur authentifié (`IS_AUTHENTICATED_FULLY`).

### Hashage des mots de passe

Les mots de passe sont hashés automatiquement par Symfony via `UserPasswordHasherInterface` (algorithme `auto`, bcrypt par défaut).

### CORS

Le bundle **NelmioCorsBundle** est configuré pour autoriser uniquement les origines définies dans la variable d'environnement `CORS_ALLOW_ORIGIN`, évitant les requêtes cross-origin non autorisées.

### Routes publiques

Seules trois routes sont accessibles sans authentification :
- `POST /api/login_check`
- `POST /api/adherent/login_check`
- `POST /api/register`

Toutes les autres routes nécessitent un token JWT valide.
