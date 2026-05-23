# Story #001 : Environnement Docker et socle Symfony

## Description
En tant que **développeur**, je veux un environnement Docker fonctionnel (FrankenPHP 8.4 + PostgreSQL 16) avec Symfony 8.0, afin de pouvoir développer et exécuter le projet dans un environnement reproductible.

## Critères d'acceptation
- [ ] Le `compose.yaml` définit les services FrankenPHP (PHP 8.4) et PostgreSQL 16
- [ ] Les migrations Symfony s'exécutent automatiquement au démarrage du conteneur PHP
- [ ] Le projet Symfony 8.0 répond sur `http://localhost:8000`
- [x] AssetMapper est configuré pour compiler les assets dans `public/assets/` (déjà en place)
- [ ] La connexion à PostgreSQL est fonctionnelle via Doctrine

## Tests automatisés
- Test d'intégration : vérifier que la page d'accueil répond en HTTP 200
- Test unitaire : validation de la configuration Doctrine

## Documentation
- README.md à mettre à jour avec les instructions d'installation via Docker
- Documentation docker-compose à créer

## Valeur utilisateur
Permet à tout développeur de démarrer le projet en une commande avec `docker compose up` sans configuration manuelle.

## Dépendances
- Aucune
