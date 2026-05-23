# Fruits & Veggies Shop

Boutique en ligne de fruits et légumes frais.

## Prérequis

- Docker & Docker Compose
- Git

## Installation

```bash
# Cloner le projet
git clone <url-du-repo>
cd fruits_and_veggies_shop

# Démarrer l'environnement
docker compose up -d --build

# Exécuter les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

La page d'accueil est accessible sur [http://localhost:8000](http://localhost:8000).

## Démarrage rapide (après installation)

```bash
docker compose up -d
```

## Tests

```bash
docker compose exec app php bin/phpunit
```

## Commandes utiles

| Commande | Description |
|---|---|
| `docker compose up -d` | Démarrer les services |
| `docker compose down` | Arrêter les services |
| `docker compose logs -f app` | Voir les logs PHP |
| `docker compose exec app php bin/console ...` | Exécuter une commande Symfony |
| `docker compose exec app php bin/phpunit` | Exécuter les tests |
| `docker compose build app` | Reconstruire l'image PHP |

## Documentation

- [Environnement Docker](docs/docker-compose.md)
- [Spécification projet](docs/specification/specification.md)
- [Stories](docs/stories/)
- [Schéma de données](docs/schema-er.md)

## Stack

- **PHP** : 8.4 (FrankenPHP, ZTS)
- **Framework** : Symfony 8.0
- **Base de données** : PostgreSQL 16
- **Frontend** : Twig + Stimulus + Turbo + AssetMapper
- **Serveur web** : FrankenPHP (Caddy)
- **Messaging** : Symfony Messenger (Doctrine transport)
- **Email** : Symfony Mailer (Mailpit en dev)
