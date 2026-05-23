# Tâche #001 - Story #001 : Service PHP Docker + auto-migrations

## Objectif
Ajouter un service PHP (FrankenPHP) au `compose.yaml` existant avec entrée automatique des migrations Doctrine au démarrage, afin que l'environnement de développement complet soit opérationnel avec `docker compose up`.

## Contexte
- Story #001 : `docs/stories/story-001.md`
- Dépend de : Aucune
- Nécessaire pour : Tâche #002, Tâche #003

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle
Le projet Symfony 8.0 existe déjà (installé avec Composer). Un `compose.yaml` avec PostgreSQL 16 et un `compose.override.yaml` avec Mailpit sont déjà présents. Il manque un conteneur PHP pour exécuter l'application.

Tu dois ajouter un service PHP Symfony dans `compose.yaml` utilisant FrankenPHP (approche recommandée par Symfony 8.0), avec :
- PHP 8.4 avec les extensions nécessaires (intl, pdo_pgsql, pgsql, etc.)
- Le projet Symfony monté en tant que volume
- Les migrations Doctrine exécutées automatiquement au démarrage
- Le port 8000 exposé
- Dépendance sur le service `database` (wait for it)
- Healthcheck sur le port 8000

**Cas nominaux :**
- `docker compose up --build` démarre PHP, PostgreSQL et Mailpit
- Les migrations Doctrine s'exécutent automatiquement au démarrage du conteneur PHP
- L'application Symfony répond sur `http://localhost:8000`

**Cas limites :**
- Si la base n'est pas encore prête au démarrage de PHP, le conteneur PHP attend que PostgreSQL soit healthy
- Si les migrations échouent, le conteneur PHP doit crash avec un message d'erreur clair (pas de démarrage silencieux)
- Rebuild après `composer install` : les dépendances sont déjà installées dans l'image

**Gestion d'erreurs :**
- Migration échouée → le conteneur s'arrête avec code d'erreur 1 et log explicite
- Base de données injoignable → attente + timeout avec message d'erreur clair

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `Dockerfile` | Créer | Image PHP 8.4 avec FrankenPHP, extensions PHP, Composer |
| `compose.yaml` | Modifier | Ajouter le service `app` avec FrankenPHP |
| `docker-entrypoint.sh` | Créer | Script d'entrée : attend la DB, exécute les migrations, démarre le serveur |
| `.dockerignore` | Créer | Ignorer vendor/, var/, .git/, etc. |

### Contraintes techniques
- **Image de base** : Utiliser `frankenphp` officielle (ou `frankenphp/frankenphp:latest`) avec PHP 8.4
- **Extensions PHP nécessaires** : `intl`, `pdo_pgsql`, `pgsql`, `opcache`, `apcu`
- **Composer** : Inclure Composer dans l'image pour `composer install` au build
- **Volume** : Monter le code source dans `/app`
- **Port** : 8000 (FrankenPHP par défaut)
- **Migration** : Exécuter `php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration` au démarrage
- **Attente DB** : Utiliser une boucle shell avec `pg_isready` pour attendre PostgreSQL

### Structure des Dockerfiles

```dockerfile
# Dockerfile
FROM dunglas/frankenphp:latest-php8.4

LABEL Description="Fruits & Veggies Shop" Vendor="..."
```

### Signatures/Structure

**Script `docker-entrypoint.sh` :**
```bash
#!/bin/sh
set -e

# Attendre que PostgreSQL soit prêt
# Exécuter les migrations Doctrine
# Démarrer FrankenPHP
```

**Service dans `compose.yaml` :**
```yaml
services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - .:/app
    depends_on:
      database:
        condition: service_healthy
    environment:
      APP_ENV: dev
      DATABASE_URL: postgresql://app:!ChangeMe!@database:5432/app?serverVersion=16&charset=utf8
```

### Tests à implémenter

#### Test de build Docker
- **Fichier** : Vérification manuelle (pas automatisé dans CI)
- Scénario : `docker compose build` réussit sans erreur
- Scénario : `docker compose up -d` démarre tous les services
- Scénario : `curl http://localhost:8000` retourne HTTP 200

### Documentation

#### Documentation à créer
- `docs/docker-compose.md` : Guide d'utilisation de l'environnement Docker

#### Documentation à mettre à jour
- `README.md` : Ajouter les instructions d'installation et de démarrage avec Docker
