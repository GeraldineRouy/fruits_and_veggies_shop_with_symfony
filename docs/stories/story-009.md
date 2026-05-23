# Story #009 : Dashboard admin et gestion des produits/catégories

## Description
En tant qu'**administrateur**, je veux un dashboard pour ajouter, modifier et supprimer des produits et des catégories, ainsi que des données d'exemple initialisées par migration, afin de gérer le catalogue du magasin.

## Critères d'acceptation
- [ ] L'admin peut accéder à un dashboard sécurisé (réservé ROLE_ADMIN)
- [ ] L'admin peut lister, créer, modifier et supprimer des catégories
- [ ] L'admin peut lister, créer, modifier et supprimer des produits
- [ ] Les formulaires de produit incluent la sélection des catégories (ManyToMany)
- [ ] Une migration Doctrine insère des produits et catégories d'exemple (fruits, légumes)
- [ ] Le README documente les fonctionnalités d'administration

## Tests automatisés
- Test unitaire : validation des formulaires ProductType et CategoryType
- Test d'intégration : CRUD complet d'un produit et d'une catégorie
- Test E2E (Playwright) : admin se connecte, crée un produit, modifie une catégorie

## Documentation
- Dashboard admin à documenter dans le README
- Migration de données d'exemple à documenter
- Commandes de migration à lister dans le README

## Valeur utilisateur
Permet à l'administrateur de gérer le catalogue sans intervention technique, et fournit des données de démonstration pour le développement.

## Dépendances
- Story #003 (authentification, rôles)
- Story #004 (entités Product, Category)
