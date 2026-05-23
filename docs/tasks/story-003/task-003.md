# Tâche #003 - Story #003 : Configuration Security + Login/Logout

## Objectif
Configurer Symfony Security pour utiliser Doctrine comme UserProvider, mettre en place l'authentification par formulaire (email/mot de passe), le logout, le blocage des utilisateurs non vérifiés, et créer la page de connexion.

## Contexte
- Story #003 : [Story Inscription, connexion et validation email](../../stories/story-003.md)
- Dépend de : Tâche #001 (UserService - User entity avec UserInterface)
- Nécessaire pour : Tâches #004, #005, #006

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

La configuration security.yaml existante utilise un provider mémoire (`users_in_memory`) qui ne permet pas l'authentification via la base de données. Il faut la remplacer par un provider Doctrine pointant vers l'entité User.

**Cas nominaux :**
- Un utilisateur peut se connecter avec email/mot de passe via un formulaire sur `/login`
- Les rôles sont chargés depuis la colonne `roles` de l'entité User
- ROLE_USER est requis pour les pages protégées (profil, commandes)
- `/login` et `/register` sont accessibles sans authentification
- La déconnexion vide la session et redirige vers `/`

**Cas limites :**
- Un utilisateur avec `verifiedAt === null` ne peut pas se connecter (message d'erreur explicite)
- Un utilisateur avec `isActive === false` ne peut pas se connecter (message d'erreur explicite)
- Mauvais email ou mot de passe → message d'erreur générique "Identifiants invalides"
- Déjà connecté → redirection vers `/` si l'utilisateur visite `/login`

**Gestion d'erreurs :**
- Compte non vérifié → message flash "Vous devez confirmer votre adresse email avant de vous connecter."
- Compte désactivé → message flash "Votre compte a été désactivé. Contactez l'administrateur."
- Identifiants invalides → message flash "Email ou mot de passe incorrect."
- Tentative d'accès à une page protégée sans être connecté → redirection vers `/login`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `config/packages/security.yaml` | Modifier | Configuration complète du firewall, provider, form_login, logout, access_control |
| `src/Controller/SecurityController.php` | Créer | Controller avec actions login et logout |
| `src/Security/UserChecker.php` | Créer | Vérification que le compte est vérifié et actif |
| `templates/base.html.twig` | Modifier | Ajouter une barre de navigation avec login/logout/register |
| `templates/security/login.html.twig` | Créer | Page de connexion |

### Signatures

```php
namespace App\Controller;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response;

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): Response; // Ne fait rien - géré par le firewall
}
```

```php
namespace App\Security;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void;
    public function checkPostAuth(UserInterface $user): void;
}
```

### Contraintes techniques

- **Framework** : Symfony Security 8.0
- **Provider** : Utiliser `entity` provider pointant vers `App\Entity\User` avec `property: email`
- **Firewall** :
  - `main` firewall avec `lazy: true`, `provider: app_user_provider`
  - `form_login` avec `login_path: app_login`, `check_path: app_login`, `enable_csrf: true`, `use_referer: true` (rediriger vers la page précédente après connexion)
  - `logout` avec `path: /logout`, `target: /`
- **Access control** :
  - `^/login` et `^/register` → accès public
  - `^/profile` → `ROLE_USER`
  - `^/admin` → `ROLE_ADMIN`
- **UserChecker** : Implémenter `UserCheckerInterface` :
  - `checkPreAuth()` : Vérifier `isActive` → si false, lancer `AccountStatusException`
  - `checkPostAuth()` : Vérifier `verifiedAt` → si null, lancer `DisabledException` (ou une exception personnalisée)

### Templates Twig

#### `templates/base.html.twig` (modification)
Ajouter une barre de navigation dans le bloc `body` avant `{% block body %}` :
```twig
<nav>
    <a href="{{ path('app_home') }}">Accueil</a>
    {% if app.user %}
        <span>{{ app.user.firstName }}</span>
        <a href="{{ path('app_logout') }}">Déconnexion</a>
    {% else %}
        <a href="{{ path('app_login') }}">Connexion</a>
        <a href="{{ path('app_register') }}">Inscription</a>
    {% endif %}
</nav>
```

#### `templates/security/login.html.twig`
- Formulaire avec champs email et password
- Afficher les erreurs d'authentification via `app.flashes('error')` ou `error` de AuthenticationUtils
- Lien vers la page d'inscription : `/register`
- Lien vers la page de mot de passe oublié : `/forgot-password`
- Stylé avec le CSS existant
- Étendre `base.html.twig`

### Tests à implémenter

#### Tests d'intégration
- **Fichier** : `tests/Integration/Controller/SecurityControllerTest.php`
- Scénario 1 : Accès à `/login` → HTTP 200
- Scénario 2 : Soumission du formulaire avec email/mot de passe valides → redirection
- Scénario 3 : Soumission avec email non vérifié → message d'erreur, pas de redirection
- Scénario 4 : Soumission avec compte désactivé → message d'erreur, pas de redirection
- Scénario 5 : Accès à `/profile` sans être connecté → redirection vers `/login`

### Documentation

#### Documentation à créer
- `docs/security/login.md` : Fonctionnement de l'authentification
