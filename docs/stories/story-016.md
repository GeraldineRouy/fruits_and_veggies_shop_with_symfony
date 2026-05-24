# Story #016 : Correction du bug de paiement

## Description
En tant que **client**, je veux pouvoir valider mon paiement sans message d'erreur, afin de finaliser ma commande avec succès.

## Origine
Feedback du 2025-05-24 : "Quand je teste manuellement le paiement d'une commande, j'ai un message d'erreur : il faut absolument corriger ce bug majeur."

## Cause identifiée
Erreur `UniqueConstraintViolationException` lors de la soumission du formulaire de paiement :
- `SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "order_line_pkey"`
- `DETAIL: Key (id)=(3) already exists.`
- Cause racine : l'ID des entités `OrderLine` est défini manuellement au lieu d'être auto-généré par Doctrine (problème de séquence/génération d'ID)

## Critères d'acceptation
- [ ] Le formulaire de paiement se soumet sans erreur `UniqueConstraintViolationException`
- [ ] Les IDs des `OrderLine` sont correctement auto-générés par Doctrine (sans conflit de clé primaire)
- [ ] Après soumission du formulaire de paiement, l'écran de confirmation de commande s'affiche correctement
- [ ] L'email de confirmation est bien envoyé (Story #006)
- [ ] Les données de la commande sont correctement persistées en base de données
- [ ] Un message d'erreur explicite est affiché si une erreur survient (au lieu de l'exception 500)

## Tests automatisés
- Test d'intégration : parcours complet panier → paiement → confirmation sans erreur
- Test unitaire : vérifier que les IDs des OrderLine sont bien auto-générés et uniques
- Test E2E (Playwright) : soumission du formulaire de paiement → vérification de l'écran de confirmation

## Documentation
- Mettre à jour la documentation du parcours de commande si nécessaire

## Valeur utilisateur
Corrige un bug bloquant qui empêche les clients de finaliser leurs achats, restaurant la confiance dans le processus de commande.

## Dépendances
- Story #013 (paiement simulé et écran de confirmation)
- Story #006 (passage de commande, email de confirmation)
- Story #005 (panier)
