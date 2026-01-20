# Marketplace Dashboard

Un tableau de bord pour la gestion d'une marketplace avec API marchands, clients et commandes.

## Configuration requise

- PHP 8.1 ou supérieur
- Composer
- Node.js et npm
- Base de données MySQL/MariaDB

## Installation et démarrage

### 1. Cloner et installer les dépendances

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
npm install
```

### 2. Configuration de l'environnement

```bash
# Copier le fichier de configuration
cp .env.example .env

# Générer la clé d'application
php artisan key:generate
```

Éditer le fichier `.env` et configurer :
- Les informations de la base de données
- L'URL de l'application
- Les paramètres de mail et autres services

### 3. Initialiser la base de données

```bash
# Exécuter les migrations
php artisan migrate

# (Optionnel) Remplir la base de données avec des données de test
php artisan db:seed
```

### 4. Démarrer l'application en développement

Ouvrir deux terminaux :

**Terminal 1 - Serveur Laravel :**
```bash
php artisan serve
```
L'application est accessible à `http://localhost:8000`

**Terminal 2 - Compilation des assets (Vite) :**
```bash
npm run dev
```

## Production

### Build des assets

```bash
npm run build
```

### Démarrage du serveur

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Documentation

- [API Marchands](merchant-api-documentation.md)
- [API Commandes](order-api-documentation.md)
- [Documentation supplémentaire](docs/)

## Structure du projet

- `/app` - Code applicatif (Models, Controllers, Services)
- `/database` - Migrations et seeders
- `/resources` - Vues, CSS et JavaScript
- `/routes` - Définition des routes
- `/tests` - Tests automatisés
