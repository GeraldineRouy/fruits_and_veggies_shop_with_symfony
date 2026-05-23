# Story #007 : Top produits et page d'accueil

## Description
En tant que **visiteur**, je veux voir les 3 produits les plus commandés sur la page d'accueil via un contrôleur imbriqué, afin d'être guidé vers les articles populaires.

## Critères d'acceptation
- [ ] La page d'accueil affiche les 3 produits les plus commandés (basé sur la quantité totale dans OrderLine)
- [ ] L'affichage utilise un contrôleur imbriqué (Embedded Controller)
- [ ] Chaque produit affiché est cliquable vers sa fiche détaillée
- [ ] Les données sont mises à jour dynamiquement (pas de cache statique)

## Tests automatisés
- Test unitaire : ProductRepository — findTopMostOrdered(int $limit)
- Test d'intégration : la réponse de la page d'accueil inclut le bloc des top produits
- Test E2E (Playwright) : vérifier l'affichage des 3 produits sur la page d'accueil (scénario lisible)

## Documentation
- Implémentation du contrôleur imbriqué à documenter

## Valeur utilisateur
Guide les visiteurs vers les produits les plus populaires, augmentant les chances de conversion.

## Dépendances
- Story #002 (entités Product, OrderLine)
- Story #004 (catalogue produits)
