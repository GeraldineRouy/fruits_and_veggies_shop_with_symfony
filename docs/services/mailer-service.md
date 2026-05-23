# MailerService

## Vue d'ensemble

`App\Service\MailerService` envoie les emails transactionnels via Symfony Mailer.

## Dépendances

- `Symfony\Component\Mailer\MailerInterface` — envoi des emails
- `%env(MAILER_SENDER_EMAIL)%` — adresse expéditeur
- `%env(APP_BASE_URL)%` — URL de base pour les liens

## Méthodes

### `sendValidationEmail(User $user): void`

Envoie un email de confirmation d'adresse email après inscription.

Le lien de validation pointe vers `{APP_BASE_URL}/verify-email?token={token}`.

### `sendPasswordResetEmail(User $user, string $token): void`

Envoie un email de réinitialisation de mot de passe.

Le lien de reset pointe vers `{APP_BASE_URL}/reset-password?token={token}`.

## Templates

| Template | Usage |
|----------|-------|
| `email/base.html.twig` | Layout commun avec header/footer |
| `email/validation.html.twig` | Corps HTML de l'email de validation |
| `email/validation.txt.twig` | Version texte de l'email de validation |
| `email/reset_password.html.twig` | Corps HTML de l'email de reset |
| `email/reset_password.txt.twig` | Version texte de l'email de reset |

## Configuration

Variables d'environnement :

```
MAILER_DSN=smtp://mailer:1025    # Mailpit en dev
MAILER_SENDER_EMAIL=noreply@fruits-veggies.local
APP_BASE_URL=http://localhost:8000
```

En environnement Docker, ces valeurs sont déjà définies dans `compose.yaml`.
