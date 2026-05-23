# Story #006 : Passage de commande et suivi

## Description
En tant que **client connecté**, je veux convertir mon panier en commande et recevoir des emails à chaque changement de statut, afin d'être livré et informé du suivi.

## Critères d'acceptation
- [ ] Un client peut valider son panier et créer une commande (statut "confirmed")
- [ ] Un email de confirmation est envoyé après la création de la commande
- [ ] Le client peut voir la liste de ses commandes passées dans son profil
- [ ] Le client peut voir le détail d'une commande (statut, produits, prix, date)
- [ ] Les statuts évoluent : confirmed → preparing → shipped → delivered
- [ ] Un email est envoyé à chaque changement de statut
- [ ] Un client peut annuler sa commande si elle est encore au statut "confirmed"
- [ ] Un admin peut annuler n'importe quelle commande, quel que soit son statut

## Tests automatisés
- Test unitaire : OrderService — calcul du total, changement de statut, envoi email
- Test d'intégration : conversion panier → commande, enchaînement des statuts
- Test E2E (Playwright) : parcours complet commande jusqu'à la livraison (scénario lisible)

## Documentation
- Processus de commande à documenter

## Valeur utilisateur
Permet aux clients de passer commande et d'être tenus informés de son acheminement.

## Dépendances
- Story #005 (panier)
- Story #003 (authentification)
