# Story #004 : Catalogue produits et navigation

## Description
En tant que **visiteur**, je veux parcourir les produits par catégorie et voir les détails d'un produit, afin de découvrir l'offre du magasin.

## Critères d'acceptation
- [ ] La page d'accueil liste les catégories disponibles
- [ ] Chaque catégorie affiche ses produits (nom, image, prix)
- [ ] Un clic sur un produit affiche sa fiche détaillée (nom, description, image, prix, catégories)
- [ ] Les produits sont paginés (12 par page par défaut)

## Tests automatisés
- Test unitaire : ProductRepository — findByCategory, pagination
- Test d'intégration : routes /shop et /shop/{id} répondent correctement avec des fixtures
- Test E2E (Playwright) : navigation catégorie → liste → détail produit (scénario lisible)

## Documentation
- Catalogue des routes à documenter

## Valeur utilisateur
Permet aux clients de visualiser l'offre de fruits et légumes disponible.

## Dépendances
- Story #002 (entités Product, Category)
