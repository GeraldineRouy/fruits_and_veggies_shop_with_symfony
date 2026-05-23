# Réinitialisation de mot de passe

## Flux

1. L'utilisateur clique sur "Mot de passe oublié" depuis la page de connexion.
2. Il saisit son email sur `/forgot-password`.
3. Si l'email existe, un lien unique est envoyé par email (via `MailerService::sendPasswordResetEmail()`).
4. L'utilisateur clique sur le lien et arrive sur `/reset-password?token=...`.
5. Il choisit un nouveau mot de passe (min. 8 caractères).
6. Le token est supprimé après utilisation.

## Sécurité

- **Non-divulgation** : La page `/forgot-password` affiche toujours "Si un compte existe avec cet email, vous recevrez un lien" (même si l'email n'existe pas).
- **Expiration** : Les tokens expirent après 1 heure (configurable dans `UserService::RESET_TOKEN_EXPIRY_HOURS`).
- **Tokens** : Stockés dans l'entité `ResetPasswordRequest` (table dédiée), supprimés après utilisation.
- **Validation** : Les tokens invalides ou expirés redirigent vers `/forgot-password` avec un message d'erreur.

## Routes

| Route | Méthode | Description |
|---|---|---|
| `/forgot-password` | GET | Affiche le formulaire de demande |
| `/forgot-password` | POST | Envoie l'email de réinitialisation |
| `/reset-password?token=` | GET | Affiche le formulaire de nouveau mot de passe |
| `/reset-password?token=` | POST | Enregistre le nouveau mot de passe |

## Services utilisés

- `UserService::requestPasswordReset(User $user): string` — crée un `ResetPasswordRequest` et retourne le token
- `UserService::resetPassword(string $token, string $newPlainPassword): User` — valide le token et met à jour le mot de passe
- `MailerService::sendPasswordResetEmail(User $user, string $token): void` — envoie l'email avec le lien
