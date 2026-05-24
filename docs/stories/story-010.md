# Story #010 : Création automatique du compte admin au démarrage

## Description
En tant qu'**administrateur**, je veux qu'un compte admin soit créé automatiquement au démarrage de l'application s'il n'existe pas déjà, avec l'email `admin@example.com` et le mot de passe `admin`, afin de pouvoir accéder immédiatement au back-office sans configuration manuelle.

## Origine
Feedback du 2025-05-24 : "Créer un compte admin au démarrage de l'application s'il n'y en a pas déjà un qui a pour adresse email 'admin@example.com' et qui a pour mot de passe 'admin'."

## Implémentation
- Mécanisme : migration de données Doctrine (avec `INSERT` conditionnel via `doctrine:migrations:migrate`)

## Critères d'acceptation
- [ ] Une migration de données Doctrine crée le compte admin avec email `admin@example.com` si aucun admin n'existe
- [ ] Le mot de passe du compte admin est `admin` (hashé en base)
- [ ] Le compte admin possède le rôle `ROLE_ADMIN`
- [ ] Le compte admin est vérifié (`verifiedAt` renseigné) et actif (`isActive = true`)
- [ ] Si le compte admin existe déjà, il n'est pas recréé ni modifié
- [ ] L'admin peut se connecter avec email `admin@example.com` / mot de passe `admin`
- [ ] L'admin a accès au dashboard `/admin` après connexion

## Tests automatisés
- Test unitaire : vérifier que la migration de données crée bien un admin avec les bons rôles
- Test d'intégration : exécuter la migration, vérifier que l'admin existe en base et peut se connecter
- Test E2E (Playwright) : connexion admin → accès au dashboard admin

## Documentation
- README.md : mentionner le compte admin par défaut (email/mot de passe)
- Recommander de changer le mot de passe après la première connexion

## Valeur utilisateur
Permet à l'administrateur de démarrer l'application et d'accéder immédiatement au back-office sans intervention manuelle en base de données.

## Dépendances
- Story #003 (authentification, rôles)
- Story #009 (dashboard admin)
