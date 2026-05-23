# Story #001 : Environnement Docker et socle Symfony

## Description
En tant que **développeur**, je veux un environnement Docker fonctionnel (PHP 8 + MySQL LTS) avec Symfony 7.4 installé, afin de pouvoir développer et exécuter le projet dans un environnement reproductible.

## Critères d'acceptation
- [ ] Le `docker-compose.yml` définit les services PHP 8.x et MySQL LTS
- [ ] Les migrations Symfony s'exécutent automatiquement au démarrage du conteneur PHP
- [ ] Le projet Symfony 7.4 répond sur `http://localhost:8000`
- [ ] AssetMapper est configuré pour compiler les assets dans `public/assets/`
- [ ] La connexion à MySQL est fonctionnelle via Doctrine

## Tests automatisés
- Test d'intégration : vérifier que la page d'accueil par défaut répond en HTTP 200
- Test unitaire : validation de la configuration Doctrine

## Documentation
- README.md à mettre à jour avec les instructions d'installation via Docker
- Documentation docker-compose existante à enrichir

## Valeur utilisateur
Permet à tout développeur de démarrer le projet en une commande sans configuration manuelle.

## Dépendances
- Aucune
