# Tâche #001 - Story #003 : User entity + UserService + UserRepository

## Objectif
Mettre à jour l'entité User pour implémenter `UserInterface`, ajouter le champ `emailVerificationToken`, créer l'entité `ResetPasswordRequest` pour les tokens de reset, créer le `UserService` avec la logique métier (inscription, validation email, reset password) et enrichir le `UserRepository`.

## Contexte
- Story #003 : [Story Inscription, connexion et validation email](../../stories/story-003.md)
- Dépend de : Story #002 (entités déjà créées)
- Nécessaire pour : Tâches #002, #003, #004, #005, #006

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

L'entité User existe déjà mais n'implémente pas `UserInterface` de Symfony Security, ce qui la rend inutilisable pour l'authentification. Il faut également ajouter le champ `emailVerificationToken` pour la validation email, et créer une entité dédiée `ResetPasswordRequest` pour les tokens de réinitialisation de mot de passe (pour une meilleure traçabilité).

**Cas nominaux :**
- User implémente `UserInterface` + `PasswordAuthenticatedUserInterface` (déjà implémenté)
- `UserService::register()` créé un utilisateur avec mot de passe hashé et rôle ROLE_USER
- `UserService::validateEmail()` marque l'utilisateur comme vérifié et efface le token
- `UserService::isEmailVerified()` retourne true si verifiedAt non null
- `UserService::requestPasswordReset()` génère un token de reset avec expiration (via `ResetPasswordRequest`)
- `UserService::resetPassword()` hash le nouveau mot de passe et efface le `ResetPasswordRequest` associé

**Cas limites :**
- Appel de `register()` avec un email déjà existant → Email non unique : doit lever une `\RuntimeException` ou `\InvalidArgumentException`
- Appel de `validateEmail()` avec un token invalide → lever une exception
- Appel de `resetPassword()` avec un token expiré → lever une exception
- Appel de `resetPassword()` avec un token invalide → lever une exception
- User déjà vérifié qui clique à nouveau sur un lien de validation → redirection ou message informatif (pas d'erreur)

**Gestion d'erreurs :**
- Token de validation invalide → `\RuntimeException('Invalid verification token')`
- Token de reset invalide → `\RuntimeException('Invalid reset token')`
- Token de reset expiré → `\RuntimeException('Reset token has expired')`
- Email déjà existant → `\RuntimeException('Email already in use')`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Entity/User.php` | Modifier | Ajouter UserInterface, ajouter le champ emailVerificationToken |
| `src/Entity/ResetPasswordRequest.php` | Créer | Entité dédiée pour les tokens de reset |
| `src/Service/UserService.php` | Créer | Service métier pour la gestion des utilisateurs |
| `src/Repository/UserRepository.php` | Modifier | Ajouter findOneByEmail() et findOneByEmailVerificationToken() |
| `src/Repository/ResetPasswordRequestRepository.php` | Créer | Repository pour ResetPasswordRequest |
| `migrations/Version*.php` | Créer | Nouvelle migration : ajout emailVerificationToken sur User + table reset_password_request |

### Signatures

```php
namespace App\Entity;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Champs existants (id, email, firstName, lastName, roles, password, verifiedAt, lastLoginAt, isActive)
    // NOUVEAU champ à ajouter :
    private ?string $emailVerificationToken = null;

    // NOUVELLES méthodes (à ajouter aux getters/setters existants) :
    public function getUserIdentifier(): string;
    public function eraseCredentials(): void;
    public function getEmailVerificationToken(): ?string;
    public function setEmailVerificationToken(?string $token): self;
}
```

```php
namespace App\Entity;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
class ResetPasswordRequest
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 64)]
    private string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    // Getters + constructor avec User, token, requestedAt, expiresAt
}
```

```php
namespace App\Service;

class UserService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ResetPasswordRequestRepository $resetPasswordRequestRepository,
    ) {}

    public function register(User $user, string $plainPassword): User;
    public function validateEmail(string $token): User;
    public function isEmailVerified(User $user): bool;

    /** Crée un ResetPasswordRequest et retourne son token */
    public function requestPasswordReset(User $user): string;

    /** Valide le token ResetPasswordRequest et hash le nouveau mot de passe */
    public function resetPassword(string $token, string $newPlainPassword): User;

    private function generateToken(): string; // random hex token de 64 caractères
}
```

```php
namespace App\Repository;

class UserRepository extends ServiceEntityRepository
{
    public function findOneByEmail(string $email): ?User;
    public function findOneByEmailVerificationToken(string $token): ?User;
}
```

```php
namespace App\Repository;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository
{
    /** Retourne la demande de reset valide pour un token donné, ou null */
    public function findOneByToken(string $token): ?ResetPasswordRequest;

    /** Supprime toutes les demandes de reset expirées pour un utilisateur */
    public function deleteExpiredRequests(): void;
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0, Doctrine ORM 3, PHP 8.4
- **Pattern** : Repository pattern pour l'accès aux données, Service pattern pour la logique métier
- **ORM** : Utiliser les attributs Doctrine (comme dans les entités existantes)
- **Password hasher** : Utiliser `UserPasswordHasherInterface` (injection via constructeur)
- **Tokens** : Générer avec `bin2hex(random_bytes(32))` pour obtenir 64 caractères hexadécimaux
- **Migration** : Créer une nouvelle migration pour ajouter `email_verification_token` à la table `user` et créer la table `reset_password_request`
- **Validation** : Ajouter les contraintes de validation Symfony sur l'entité User (Email, NotBlank, Length)
- **Style** : Respecter le code existant (déclaration strict_types, PHPDoc, type hints)

### Tests à implémenter

#### Tests unitaires
- **Fichier** : `tests/Unit/Service/UserServiceTest.php`
- Scénario 1 : `register()` hashe le mot de passe et définit ROLE_USER
  - Données : User vierge, plainPassword = "SecurePass123!"
  - Résultat attendu : password !== "SecurePass123!", roles = ["ROLE_USER"]
- Scénario 2 : `validateEmail()` avec un token valide
  - Données : User avec token, appel validateEmail(token)
  - Résultat attendu : verifiedAt non null, emailVerificationToken = null
- Scénario 3 : `validateEmail()` avec un token invalide
  - Données : User avec token = "abc", appel validateEmail("xyz")
  - Résultat attendu : \RuntimeException
- Scénario 4 : `resetPassword()` avec token expiré
  - Données : ResetPasswordRequest avec expiresAt = now - 2 hours
  - Résultat attendu : \RuntimeException

#### Tests d'intégration
- **Fichier** : `tests/Integration/Repository/UserRepositoryTest.php`
- Scénario : Créer un User en base, le retrouver par email et par emailVerificationToken
  - Résultat attendu : Les méthodes findOneByEmail() et findOneByEmailVerificationToken() retournent l'entité
- **Fichier** : `tests/Integration/Repository/ResetPasswordRequestRepositoryTest.php`
- Scénario : Créer un ResetPasswordRequest en base, le retrouver par token
  - Résultat attendu : findOneByToken() retourne l'entité avec sa relation User

### Documentation

#### Documentation à créer
- `docs/services/user-service.md` : Documentation du UserService et de ses méthodes

#### Documentation à mettre à jour
- `docs/schema-er.md` : Ajouter le champ emailVerificationToken sur User + la table reset_password_request
