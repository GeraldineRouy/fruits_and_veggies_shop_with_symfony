# UserService

## Vue d'ensemble

`App\Service\UserService` gère la logique métier liée aux utilisateurs : inscription, validation email, réinitialisation de mot de passe.

## Dépendances

- `UserPasswordHasherInterface` — hashage des mots de passe
- `EntityManagerInterface` — persistance Doctrine
- `UserRepository` — recherche d'utilisateurs (email, token de validation)
- `ResetPasswordRequestRepository` — recherche de demandes de reset

## Méthodes

### `register(User $user, string $plainPassword): User`

Crée un utilisateur avec mot de passe hashé, rôle `ROLE_USER` et token de validation email.

- **Erreur** : `RuntimeException` si l'email existe déjà

### `validateEmail(string $token): User`

Valide l'email d'un utilisateur à partir du token de vérification.

- **Erreur** : `RuntimeException` si le token est invalide

### `isEmailVerified(User $user): bool`

Retourne `true` si l'utilisateur a vérifié son email (`verifiedAt` non null).

### `requestPasswordReset(User $user): string`

Crée une demande de réinitialisation de mot de passe (`ResetPasswordRequest`) avec un token unique expirant après 1 heure.

**Retourne** : le token généré (string)

### `resetPassword(string $token, string $newPlainPassword): User`

Valide le token de reset et met à jour le mot de passe. Supprime la demande de reset après utilisation.

- **Erreur** : `RuntimeException` si le token est invalide
- **Erreur** : `RuntimeException` si le token a expiré

## Entités associées

- `App\Entity\User` — champ `emailVerificationToken` pour la validation email
- `App\Entity\ResetPasswordRequest` — entité dédiée pour les tokens de reset
