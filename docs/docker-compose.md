# Environnement Docker

## Architecture des services

| Service | Image | Rôle |
|---------|-------|------|
| `app` | FrankenPHP 8.4 (ZTS) | Serveur PHP + Caddy |
| `database` | PostgreSQL 16 | Base de données |
| `mailer` | Mailpit | Capture des e-mails (dev) |

## Commandes utiles

```bash
# Démarrer l'environnement
docker compose up -d

# Démarrer en reconstruisant l'image PHP
docker compose up -d --build

# Arrêter l'environnement
docker compose down

# Voir les logs d'un service
docker compose logs -f app
docker compose logs -f database

# Reconstruire l'image PHP uniquement
docker compose build app

# Redémarrer un service
docker compose restart app

# Exécuter une commande Symfony dans le conteneur
docker compose exec app php bin/console cache:clear

# Exécuter les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# Exécuter les tests
docker compose exec app php bin/phpunit

# Accéder au shell du conteneur
docker compose exec app sh
```

## Logs et debugging

```bash
# Suivre les logs en temps réel
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f app

# Voir les dernières lignes
docker compose logs --tail=50 app
```

## Démarrage à froid (première exécution)

1. `docker compose up -d --build`
2. La compilation du cache Symfony s'effectue à la première requête HTTP (peut prendre ~1 minute)
3. `docker compose exec app php bin/console doctrine:migrations:migrate -n`
4. Accéder à `http://localhost:8000`

## Notes

- Le cache Symfony (`var/cache`) et les logs (`var/log`) sont montés en `tmpfs` (mémoire vive) pour éviter les problèmes de `flock()` sur les bind mounts Docker Desktop (Windows).
- Les assets sont compilés via AssetMapper.
- L'environnement PHP utilise le mode ZTS (Zend Thread Safety) requis par FrankenPHP.
- Mailpit est accessible sur `http://localhost:8025` pour visualiser les e-mails.
