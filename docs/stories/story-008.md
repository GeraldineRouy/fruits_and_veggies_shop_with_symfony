# Story #008 : Administration des utilisateurs

## Description
En tant qu'**administrateur**, je veux désactiver un compte utilisateur, supprimer les comptes inactifs et non validés, et déconnecter automatiquement les utilisateurs désactivés, afin de gérer les comptes du site.

## Critères d'acceptation
- [ ] L'admin peut désactiver (isActive = false) un compte utilisateur
- [ ] L'admin peut supprimer les utilisateurs non connectés depuis 2 ans (commande console)
- [ ] L'admin peut supprimer les utilisateurs non validés après 7 jours (commande console)
- [ ] L'admin peut lister les commandes non livrées depuis plus de 7 jours (commande console)
- [ ] Un event listener déconnecte automatiquement un utilisateur désactivé lors de sa prochaine requête
- [ ] Un utilisateur désactivé ne peut plus se connecter

## Tests automatisés
- Test unitaire : UserManager — désactivation, purge des comptes inactifs
- Test d'intégration : event listener déconnecte l'utilisateur désactivé
- Test E2E (Playwright) : admin désactive un utilisateur → il est déconnecté à la prochaine action (scénario lisible)

## Documentation
- Procédure d'administration à documenter dans le README
- Commandes console à documenter dans le README (exécution manuelle uniquement via `bin/console`)

## Valeur utilisateur
Permet à l'administrateur de maintenir une base utilisateurs saine et sécurisée.

## Dépendances
- Story #003 (authentification)
- Story #002 (entité User complète)
