# Tâche #005 - Story #003 : Réinitialisation de mot de passe

## Objectif
Créer le flux complet de réinitialisation de mot de passe : formulaire de demande, envoi d'email avec lien, formulaire de nouveau mot de passe.

## Contexte
- Story #003 : [Story Inscription, connexion et validation email](../../stories/story-003.md)
- Dépend de : Tâche #001 (UserService), Tâche #002 (MailerService), Tâche #003 (Security)
- Nécessaire pour : Tâche #006 (Tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Un utilisateur qui a oublié son mot de passe doit pouvoir demander sa réinitialisation via un formulaire sur `/forgot-password`. Un email avec un lien unique lui est envoyé. En cliquant sur le lien, il accède à un formulaire pour définir un nouveau mot de passe.

**Cas nominaux :**
- Saisie d'un email valide existant → envoi d'un email de reset (même si le compte n'est pas vérifié)
- Clic sur le lien de reset avec token valide → affichage du formulaire de nouveau mot de passe
- Soumission du formulaire avec nouveau mot de passe valide → mise à jour en BDD, redirection vers `/login` avec message de succès

**Cas limites :**
- Saisie d'un email inexistant → pas d'erreur (ne pas révéler si l'email existe ou non), page "Si cet email existe, vous recevrez un lien"
- Token de reset invalide → page d'erreur "Lien de réinitialisation invalide"
- Token de reset expiré → page d'erreur "Ce lien a expiré. Veuillez refaire une demande."
- Nouveau mot de passe trop court (< 8 caractères) → erreur de validation

**Gestion d'erreurs :**
- Token invalide → flash "Lien de réinitialisation invalide" + redirection vers `/forgot-password`
- Token expiré → flash "Ce lien a expiré" + redirection vers `/forgot-password`
- Email inexistant → pas d'erreur affichée (sécurité : ne pas divulguer l'existence du compte)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Form/ForgotPasswordType.php` | Créer | Formulaire de demande de reset |
| `src/Form/ResetPasswordType.php` | Créer | Formulaire de nouveau mot de passe |
| `src/Controller/SecurityController.php` | Modifier | Ajouter les actions forgotPassword et resetPassword |
| `templates/security/forgot_password.html.twig` | Créer | Page de demande de reset |
| `templates/security/reset_password.html.twig` | Créer | Page de nouveau mot de passe |

### Signatures

```php
namespace App\Form;

class ForgotPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;
    public function configureOptions(OptionsResolver $resolver): void;
}
// Champ : email (EmailType)
```

```php
namespace App\Form;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;
    public function configureOptions(OptionsResolver $resolver): void;
}
// Champ : plainPassword (RepeatedType avec PasswordType, min 8 caractères)
```

```php
// Dans SecurityController

#[Route(path: '/forgot-password', name: 'app_forgot_password')]
public function forgotPassword(Request $request, MailerService $mailerService, UserService $userService): Response;

#[Route(path: '/reset-password', name: 'app_reset_password')]
public function resetPassword(Request $request, UserService $userService): Response;
```

### Contraintes techniques

- **Framework** : Symfony Form 8.0, Symfony Validator
- **Sécurité** : 
  - Ne pas divulguer si un email existe ou non en base de données → toujours afficher "Si un compte existe avec cet email, vous recevrez un lien"
  - Token de reset : expiration après 1 heure (vérifié via `ResetPasswordRequest::expiresAt`)
  - Les tokens de reset sont stockés dans l'entité `ResetPasswordRequest` (table dédiée) — utiliser `ResetPasswordRequestRepository::findOneByToken()` pour retrouver une demande valide
  - `UserService::requestPasswordReset()` crée un `ResetPasswordRequest` en base
  - `UserService::resetPassword()` vérifie la validité du `ResetPasswordRequest` (token + expiration), puis supprime l'entité après usage
- **ForgotPassword** : 
  - Si l'email existe → `UserService::requestPasswordReset()` retourne un token → `MailerService::sendPasswordResetEmail(User, token)`
  - Si l'email n'existe pas → ne rien faire (mais afficher le même message)
- **ResetPassword** : 
  - GET → lire `token` depuis la query string, vérifier sa validité, afficher le formulaire
  - POST → valider le formulaire, appeler `UserService::resetPassword()`, rediriger vers `/login` avec flash de succès

### Templates Twig

#### `templates/security/forgot_password.html.twig`
- Étendre `base.html.twig`
- Message explicatif : "Saisissez votre email pour recevoir un lien de réinitialisation"
- Champ email + bouton "Envoyer"
- Lien retour vers `/login`

#### `templates/security/reset_password.html.twig`
- Étendre `base.html.twig`
- Message "Choisissez un nouveau mot de passe"
- Champ password (RepeatedType) + bouton "Réinitialiser mon mot de passe"
- Le token est passé en champ caché ou dans l'URL

### Tests à implémenter

#### Tests d'intégration
- **Fichier** : `tests/Integration/Controller/PasswordResetControllerTest.php`
- Scénario 1 : GET `/forgot-password` → HTTP 200
- Scénario 2 : POST `/forgot-password` avec email existant → HTTP 200, message "Si un compte existe..."
- Scénario 3 : POST `/forgot-password` avec email inconnu → HTTP 200, message identique (pas de fuite d'info)
- Scénario 4 : GET `/reset-password?token=VALID` → HTTP 200, affiche le formulaire
- Scénario 5 : POST `/reset-password?token=VALID` avec nouveau mot de passe → redirection vers `/login`
- Scénario 6 : GET `/reset-password?token=INVALID` → message d'erreur
- Scénario 7 : GET `/reset-password?token=EXPIRED` → message d'erreur

### Documentation

#### Documentation à créer
- `docs/security/password-reset.md` : Fonctionnement de la réinitialisation de mot de passe
