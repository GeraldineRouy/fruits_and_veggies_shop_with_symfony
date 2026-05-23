# Tâche #004 - Story #003 : Inscription et validation email

## Objectif
Créer le formulaire d'inscription, la page d'inscription, la gestion de la validation par email (lien de confirmation), et les templates associés.

## Contexte
- Story #003 : [Story Inscription, connexion et validation email](../../stories/story-003.md)
- Dépend de : Tâche #001 (UserService), Tâche #002 (MailerService), Tâche #003 (Security)
- Nécessaire pour : Tâche #006 (Tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Un visiteur doit pouvoir créer un compte en fournissant email, prénom, nom et mot de passe. Après soumission, un email de validation lui est envoyé. Le compte n'est actif qu'après clic sur le lien de validation.

**Cas nominaux :**
- Soumission du formulaire d'inscription avec des données valides → création du compte, envoi email, redirection vers page "Vérifiez vos emails"
- Clic sur le lien de validation avec token valide → `verifiedAt` mis à jour, redirection vers `/login` avec message de succès
- Le formulaire pré-remplit les champs en cas d'erreur de validation

**Cas limites :**
- Email déjà utilisé → message d'erreur "Cet email est déjà utilisé"
- Mot de passe trop court (< 8 caractères) → erreur de validation côté formulaire
- Token de validation invalide → page d'erreur avec message explicite
- Token de validation déjà utilisé (compte déjà vérifié) → redirection vers `/login` avec message "Votre compte est déjà vérifié"
- Soumission du formulaire sans accepter les CGU (si applicable) → Configuration non requise pour cette story

**Gestion d'erreurs :**
- Email déjà existant → `\RuntimeException` levée par UserService, catch dans le controller → flash "Cet email est déjà utilisé"
- Token invalide → flash "Lien de validation invalide" + redirection vers `/register`
- Compte déjà vérifié → flash "Votre email est déjà vérifié" + redirection vers `/login`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Form/DTO/RegisterDto.php` | Créer | DTO pour les données du formulaire d'inscription |
| `src/Form/RegisterType.php` | Créer | Formulaire d'inscription lié à RegisterDto |
| `src/Controller/SecurityController.php` | Modifier | Ajouter les actions register et verifyEmail |
| `templates/security/register.html.twig` | Créer | Page d'inscription |
| `templates/security/check_email.html.twig` | Créer | Page "Vérifiez vos emails" |
| `templates/security/verification_success.html.twig` | Créer | Page "Email vérifié avec succès" |

### Signatures

```php
namespace App\Form\DTO;

class RegisterDto
{
    public function __construct(
        private ?string $email = null,
        private ?string $firstName = null,
        private ?string $lastName = null,
        private ?string $plainPassword = null,
    ) {}

    // Getters et setters + méthodes d'aide toUser() si souhaité
    public function getEmail(): ?string;
    public function setEmail(?string $email): void;
    public function getFirstName(): ?string;
    public function setFirstName(?string $firstName): void;
    public function getLastName(): ?string;
    public function setLastName(?string $lastName): void;
    public function getPlainPassword(): ?string;
    public function setPlainPassword(?string $plainPassword): void;
}
```

```php
namespace App\Form;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;
    public function configureOptions(OptionsResolver $resolver): void; // data_class: RegisterDto
}
```

Champs du formulaire :
- `email` : `EmailType`
- `firstName` : `TextType`
- `lastName` : `TextType`
- `plainPassword` : `RepeatedType` avec `PasswordType`

```php
// Dans SecurityController

#[Route(path: '/register', name: 'app_register')]
public function register(Request $request, UserService $userService, MailerService $mailerService): Response;

#[Route(path: '/register/check-email', name: 'app_register_check_email')]
public function checkEmail(): Response;

#[Route(path: '/verify-email', name: 'app_verify_email')]
public function verifyEmail(Request $request, UserService $userService): Response;
```

### Contraintes techniques

- **Framework** : Symfony Form 8.0, Symfony Validator
- **Formulaire** : Utiliser `RegisterType` avec `data_class: RegisterDto`. Les contraintes de validation sont définies sur les propriétés du DTO via des attributs Symfony Validator.
- **Validation** (sur RegisterDto en attributs) : 
  - `email` : `#[Email]`, `#[NotBlank]`
  - `firstName` : `#[NotBlank]`, `#[Length(max: 100)]`
  - `lastName` : `#[NotBlank]`, `#[Length(max: 100)]`
  - `plainPassword` : `#[NotBlank]`, `#[Length(min: 8, minMessage: 'Votre mot de passe doit faire au moins 8 caractères')]`
- **Soumission** : Récupérer le RegisterDto rempli, mapper manuellement vers User (new User() + setters), appeler `UserService::register()`, puis `MailerService::sendValidationEmail()`
- **Redirection post-inscription** : `/register/check-email` (page "Vérifiez vos emails")
- **Route verifyEmail** : Lire le paramètre `token` de la query string, appeler `UserService::validateEmail()`
- **Messages flash** : Utiliser `addFlash()` pour les messages de succès/erreur

### Templates Twig

#### `templates/security/register.html.twig`
- Étendre `base.html.twig`
- Afficher le formulaire avec `form_start()`, `form_row()` pour chaque champ
- Bouton de soumission "Créer mon compte"
- Lien vers la page de connexion
- Afficher les erreurs globales (email déjà utilisé)

#### `templates/security/check_email.html.twig`
- Étendre `base.html.twig`
- Message : "Un email de validation vous a été envoyé à [email]. Cliquez sur le lien pour activer votre compte."
- Lien vers `/login`

### Tests à implémenter

#### Tests d'intégration
- **Fichier** : `tests/Integration/Controller/RegistrationControllerTest.php`
- Scénario 1 : GET `/register` → HTTP 200, contient le formulaire
- Scénario 2 : POST `/register` avec données valides → redirection vers `/register/check-email`, utilisateur créé en BDD avec emailVerificationToken non null
- Scénario 3 : POST `/register` avec email existant → HTTP 200, message d'erreur
- Scénario 4 : POST `/register` avec mot de passe trop court → HTTP 200, erreur de validation
- Scénario 5 : GET `/verify-email?token=VALID` → redirection vers `/login`, verifiedAt non null
- Scénario 6 : GET `/verify-email?token=INVALID` → message d'erreur
- Scénario 7 : GET `/verify-email?token=ALREADY_VERIFIED` → redirection vers `/login`, message "déjà vérifié"

### Documentation

#### Documentation à mettre à jour
- `README.md` : Ajouter la section "Inscription et connexion"
