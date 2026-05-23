# Tâche #002 - Story #003 : MailerService

## Objectif
Créer le service d'envoi d'emails pour la validation de compte et la réinitialisation de mot de passe, utilisant Symfony Mailer avec Mailpit en développement.

## Contexte
- Story #003 : [Story Inscription, connexion et validation email](../../stories/story-003.md)
- Dépend de : Tâche #001 (UserService)
- Nécessaire pour : Tâches #004, #005, #006

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Le MailerService doit permettre d'envoyer des emails transactionnels : email de validation après inscription, et email de réinitialisation de mot de passe. Les emails doivent utiliser des templates Twig pour le corps HTML, avec un texte brut en fallback.

**Cas nominaux :**
- `sendValidationEmail(User $user)` envoie un email avec un lien de validation unique
- `sendPasswordResetEmail(User $user)` envoie un email avec un lien de reset unique
- Les emails sont envoyés via Mailpit en environnement de développement (DSN configuré dans `.env`)

**Cas limites :**
- Si l'email ne peut pas être envoyé (Mailpit down), une exception `TransportException` est levée
- L'expéditeur est configurable via paramètre (`from@example.com`)
- Le sujet de l'email est en français

**Gestion d'erreurs :**
- Échec d'envoi → `\Symfony\Component\Mailer\Exception\TransportException` (laisser remonter naturelement)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Service/MailerService.php` | Créer | Service d'envoi d'emails |
| `templates/email/base.html.twig` | Créer | Layout de base pour les emails (header/footer) |
| `templates/email/validation.html.twig` | Créer | Template HTML pour email de validation |
| `templates/email/validation.txt.twig` | Créer | Template texte pour email de validation |
| `templates/email/reset_password.html.twig` | Créer | Template HTML pour email de reset |
| `templates/email/reset_password.txt.twig` | Créer | Template texte pour email de reset |

### Signatures

```php
namespace App\Service;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $senderEmail,      // configuré via parameter ou .env
        private string $appBaseUrl,        // configuré via parameter ou .env
        private Environment $twig,         // Twig\Environment
    ) {}

    /**
     * Envoie l'email de validation de compte.
     * Le lien est : $this->appBaseUrl . '/verify-email?token=' . $user->getEmailVerificationToken()
     */
    public function sendValidationEmail(User $user): void;

    /**
     * Envoie l'email de réinitialisation de mot de passe.
     * Le lien est : $this->appBaseUrl . '/reset-password?token=' . $token
     * @param string $token Le token de reset (stocké dans ResetPasswordRequest)
     */
    public function sendPasswordResetEmail(User $user, string $token): void;
}
```

### Contraintes techniques

- **Framework** : Symfony Mailer (MailerInterface), Twig pour les templates
- **Configuration** : 
  - `MAILER_DSN` déjà configuré dans `.env` (vérifier la valeur existante)
  - Ajouter `MAILER_SENDER_EMAIL` (ex: `noreply@fruits-veggies.local`) dans `.env`
  - Ajouter `APP_BASE_URL` (ex: `http://localhost:8000`) dans `.env`
- **Templates Twig** : Utiliser `templates/email/base.html.twig` comme layout (header avec logo/nom du site, footer avec lien de désinscription ou copyright)
- **Template base.html.twig** : Doit définir les blocs `subject`, `body` et `footer` utilisés par les templates enfants
- **Expéditeur** : Utiliser `$this->senderEmail` comme `From` et `Reply-To`
- **Format** : Email multipart (HTML + texte) avec `TemplatedEmail` de Symfony
- **Sujets** : 
  - Validation : "Confirmez votre adresse email - Fruits & Veggies Shop"
  - Reset : "Réinitialisation de votre mot de passe - Fruits & Veggies Shop"

### Templates Twig

#### `templates/email/validation.html.twig`
```twig
{% extends 'email/base.html.twig' %}
{% block subject %}Confirmez votre adresse email{% endblock %}
{% block body %}
    <h1>Bienvenue {{ user.firstName }} !</h1>
    <p>Cliquez sur le lien ci-dessous pour confirmer votre adresse email :</p>
    <a href="{{ validationUrl }}">Confirmer mon email</a>
    <p>Ce lien expire dans 7 jours.</p>
{% endblock %}
```

#### `templates/email/reset_password.html.twig`
```twig
{% extends 'email/base.html.twig' %}
{% block subject %}Réinitialisation de votre mot de passe{% endblock %}
{% block body %}
    <h1>Bonjour {{ user.firstName }} !</h1>
    <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
    <a href="{{ resetUrl }}">Réinitialiser mon mot de passe</a>
    <p>Ce lien expire dans 1 heure.</p>
{% endblock %}
```

#### `templates/email/base.html.twig`
```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{% block subject %}Fruits & Veggies Shop{% endblock %}</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #4CAF50; padding: 20px; text-align: center; color: white;">
        <h1>Fruits & Veggies Shop</h1>
    </div>
    <div style="padding: 20px;">
        {% block body %}{% endblock %}
    </div>
    <div style="padding: 10px 20px; text-align: center; color: #888; font-size: 12px; border-top: 1px solid #eee;">
        {% block footer %}
            <p>&copy; {{ 'now'|date('Y') }} Fruits & Veggies Shop. Tous droits réservés.</p>
        {% endblock %}
    </div>
</body>
</html>
```

### Tests à implémenter

#### Tests unitaires
- **Fichier** : `tests/Unit/Service/MailerServiceTest.php`
- Scénario 1 : `sendValidationEmail()` construit correctement le TemplatedEmail
  - Données : User valide (email, firstName, emailVerificationToken)
  - Résultat attendu : l'email a le bon destinataire, le bon sujet, et contient le lien de validation
- Scénario 2 : `sendPasswordResetEmail()` construit correctement le TemplatedEmail
  - Données : User valide (email, firstName) + token = "abc123"
  - Résultat attendu : l'email a le bon destinataire, le bon sujet, et contient le lien de reset avec le token

*Note : Ne pas envoyer réellement les emails en test. Utiliser un mock de `MailerInterface`.*

### Documentation

#### Documentation à créer
- `docs/services/mailer-service.md` : Documentation du MailerService

#### Documentation à mettre à jour
- `README.md` : Ajouter la section sur la configuration email avec Mailpit
