# Story #003 : Inscription, connexion et validation email

## Description
En tant que **visiteur**, je veux créer un compte avec validation par email, me connecter et réinitialiser mon mot de passe, afin d'accéder aux fonctionnalités réservées aux utilisateurs connectés.

## Critères d'acceptation
- [ ] Un visiteur peut s'inscrire avec email, firstName, lastName et mot de passe
- [ ] Un email de validation est envoyé après inscription avec un lien unique
- [ ] Le compte n'est actif qu'après clic sur le lien de validation (verifiedAt)
- [ ] Un utilisateur peut se connecter avec email/mot de passe
- [ ] Un utilisateur peut demander la réinitialisation de son mot de passe par email
- [ ] ROLE_USER est attribué automatiquement, ROLE_ADMIN est défini manuellement
- [ ] Un utilisateur non validé ne peut pas se connecter

## Tests automatisés
- Test unitaire : UserService — validation du hash du mot de passe
- Test d'intégration : inscription, validation, connexion complètes
- Test E2E (Playwright) : parcours complet d'inscription → validation email → connexion (scénario lisible)

## Documentation
- Procédure d'inscription à documenter dans le README
- MailerService : solution locale simple (Mailpit), pas de configuration prod nécessaire

## Valeur utilisateur
Permet aux visiteurs de créer un compte sécurisé et de commencer à utiliser le site.

## Dépendances
- Story #002 (entités User)
- Story #001 (Docker/Symfony)
