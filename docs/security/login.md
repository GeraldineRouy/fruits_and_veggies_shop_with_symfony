# Authentification

## Fonctionnement

L'authentification utilise le système Security de Symfony avec un formulaire de connexion (email/mot de passe).

## Flux

1. L'utilisateur visite `/login`
2. Il soumet email + mot de passe
3. Symfony vérifie les identifiants via le provider Doctrine (`App\Entity\User`, propriété `email`)
4. `UserChecker` vérifie :
   - `checkPreAuth()` : le compte est actif (`isActive`)
   - `checkPostAuth()` : l'email est vérifié (`verifiedAt`)
5. En cas de succès, l'utilisateur est redirigé vers la page précédente (`use_referer`)
6. En cas d'échec, un message d'erreur explicite est affiché

## Contrôle d'accès

| Route | Accès |
|---|---|
| `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email` | PUBLIC_ACCESS |
| `/profile` | ROLE_USER |
| `/admin` | ROLE_ADMIN |

## UserChecker

`App\Security\UserChecker` vérifie :

- `checkPreAuth()` : si `isActive === false` → "Votre compte a été désactivé"
- `checkPostAuth()` : si `verifiedAt === null` → "Vous devez confirmer votre adresse email"
