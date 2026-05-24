# Story #013 : Paiement simulé et écran de confirmation de commande

## Description
En tant que **client connecté**, je veux passer par un formulaire de paiement simulé après avoir validé mon panier, puis voir un écran de confirmation de commande, afin de vivre un parcours d'achat complet et rassurant.

## Origine
Feedback du 2025-05-24 : "Pour valider un panier, et donc valider une commande, simuler un formulaire de paiement, un écran de confirmation de commande et envoyez un email de confirmation."

## Critères d'acceptation
- [ ] Après validation du panier, un formulaire de paiement simulé s'affiche avec les champs pré-remplis et non modifiables : numéro de carte, date d'expiration, CVV
- [ ] L'utilisateur clique sur le bouton "Payer" pour valider (pas de saisie nécessaire)
- [ ] Après soumission du formulaire, un écran de confirmation de commande s'affiche avec :
  - Le numéro de commande
  - Le récapitulatif des produits
  - Le montant total
  - La date de commande
- [ ] Un email de confirmation est envoyé après la validation (déjà couvert par Story #006)
- [ ] Le client peut revenir à la page d'accueil depuis l'écran de confirmation

## Tests automatisés
- Test unitaire : vérifier le rendu du formulaire de paiement
- Test d'intégration : parcours complet panier → paiement → confirmation
- Test E2E (Playwright) : soumission du formulaire de paiement simulé → écran de confirmation

## Documentation
- Mettre à jour le README avec la description du parcours de commande

## Valeur utilisateur
Rassure le client avec un parcours d'achat complet, même en environnement de démonstration, et lui donne une visibilité claire sur sa commande.

## Dépendances
- Story #006 (passage de commande, email de confirmation)
- Story #005 (panier)
