# Story #005 : Panier d'achat

## Description
En tant que **client connecté**, je veux ajouter des produits à mon panier, modifier les quantités et voir le total, afin de préparer ma commande.

## Critères d'acceptation
- [ ] Un client peut ajouter un produit au panier depuis la fiche produit ou la liste
- [ ] Un client peut voir le contenu de son panier (produits, quantités, prix unitaire, total)
- [ ] Un client peut modifier la quantité d'un produit dans le panier
- [ ] Un client peut retirer un produit du panier
- [ ] Un client peut vider son panier
- [ ] Le panier est persistant via session (même après déconnexion/reconnexion)

## Tests automatisés
- Test unitaire : CartService — addProduct, removeProduct, getTotal, getProductCount
- Test d'intégration : ajout/suppression/modification dans le panier
- Test E2E (Playwright) : parcours complet ajout → modification → suppression (scénario lisible)

## Documentation
- API du CartService à documenter

## Valeur utilisateur
Permet aux clients de composer leur commande avant de passer à l'achat.

## Dépendances
- Story #003 (authentification)
- Story #004 (catalogue produits)
