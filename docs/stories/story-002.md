# Story #002 : Entités et schéma de base de données

## Description
En tant que **développeur**, je veux créer les 5 entités Doctrine (User, Category, Product, Order, OrderLine) avec leurs relations, afin d'avoir un schéma de base de données prêt à l'emploi.

## Critères d'acceptation
- [ ] L'entité User contient : email, firstName, lastName, roles (json), password, verifiedAt, lastLoginAt, isActive
- [ ] L'entité Category contient : name, description
- [ ] L'entité Product contient : name, description, image, price, categories (ManyToMany)
- [ ] L'entité Order contient : user (ManyToOne), orderedAt, status (confirmed/preparing/shipped/delivered/cancelled)
- [ ] L'entité OrderLine contient : order (ManyToOne), quantity, price, product (ManyToOne)
- [ ] Les migrations Doctrine sont générées et exécutables

## Tests automatisés
- Test unitaire : validation des getters/setters de chaque entité
- Test d'intégration : création et persistance de chaque entité en base de test

## Documentation
- Schéma entité-relation à documenter

## Valeur utilisateur
Fondation technique permettant aux fonctionnalités métier de s'appuyer sur un modèle de données complet.

## Dépendances
- Story #001
