# Tâche #003 - Story #008 : Event listener déconnexion des utilisateurs désactivés

## Objectif

Créer un event listener/subscriber Symfony qui déconnecte automatiquement un utilisateur dont le compte a été désactivé (`isActive = false`) lors de sa prochaine requête HTTP.

## Contexte

- Story #008 : `docs/stories/story-008.md`
- Dépend de : Story #003 (authentification Symfony), Tâche #001 (désactivation via admin)
- Nécessaire pour : Tâche #004 (tests d'intégration)
- `UserChecker` (`src/Security/UserChecker.php`) vérifie déjà `isActive` dans `checkPreAuth()` et bloque la connexion → **complémentaire** : le checker bloque les nouvelles connexions, tandis que l'event listener déconnecte les sessions existantes
- Le firewall `main` dans `security.yaml` utilise `user_checker: App\Security\UserChecker`

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Lorsqu'un administrateur désactive un compte utilisateur via l'interface admin (Tâche #001), l'utilisateur concerné, s'il est actuellement connecté avec une session active, doit être automatiquement déconnecté lors de sa prochaine requête HTTP.

**Mécanisme attendu :**

1. Un event subscriber écoute l'événement `KernelEvents::REQUEST` (priorité haute)
2. À chaque requête, si l'utilisateur est authentifié (instance de `User`) ET que `isActive` est `false` :
   - Invalider la session (`$request->getSession()->invalidate()`)
   - Déconnecter l'utilisateur du système de sécurité (`$tokenStorage->setToken(null)`)
   - Ajouter un message flash d'information
   - Rediriger vers la page de login
3. Si l'utilisateur est désactivé mais non authentifié (cas déjà géré par `UserChecker`), ne rien faire

**Cas nominaux :**
- Un admin désactive l'utilisateur "Jean" → à la prochaine requête de Jean, il est déconnecté et redirigé vers `/login` avec un flash "Votre compte a été désactivé"
- La session est invalidée (l'utilisateur ne peut pas revenir en arrière)

**Cas limites :**
- Utilisateur non authentifié (anonyme) → aucune action
- Utilisateur authentifié avec un autre type d'UserInterface (non `User`) → aucune action
- Admin qui se désactive lui-même (interdit par Tâche #001) → mais si jamais cela arrive, l'admin est aussi déconnecté (sécurité avant tout)

**Gestion d'erreurs :**
- Pas de session disponible (`$request->hasSession()` = false) → aucune action, pas d'erreur
- Pas de token dans le storage → aucune action

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/EventListener/UserDeactivatedSubscriber.php` | Créer | Event subscriber pour la déconnexion automatique |

### Signatures

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
// ...

class UserDeactivatedSubscriber
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly SessionInterface $session, // ou via Request
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 15)]
    public function onKernelRequest(RequestEvent $event): void;
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0, `HttpKernel` component
- **Pattern** : Utiliser l'attribut PHP 8 `#[AsEventListener]` (nouveau dans Symfony 6+) pour l'enregistrement automatique — pas de configuration YAML manuelle
- **Priorité** : Utiliser une priorité de `15` pour exécuter ce listener après le firewall (qui est autour de `8`), mais avant les contrôleurs
- **Dépendances injectées** :
  - `TokenStorageInterface` — pour vérifier et supprimer le token
  - `RouterInterface` — pour générer l'URL de redirection vers `/login`
  - `RequestStack` — pour accéder à la session et aux flashes (alternative : injecter `SessionInterface`)
- **Message flash** : Ajouter un message flash de type `danger` : `'Votre compte a été désactivé. Contactez l\'administrateur.'` — utiliser `$request->getSession()->getFlashBag()->add('danger', $message)`
- **Redirection** : Retourner une `RedirectResponse` vers la route `app_login` en arrêtant la propagation de l'événement (`$event->stopPropagation()` n'est pas nécessaire car on utilise `$event->setResponse()`)
- **Ne PAS dupliquer la vérification UserChecker** : le `UserChecker` s'exécute avant la résolution du token, donc avant l'authentification complète. Notre listener s'exécute après l'authentification, pendant la phase `REQUEST`. Les deux sont complémentaires.

### Tests à implémenter

Voir Tâche #004.

#### Test d'intégration
- **Scénario** : Un utilisateur a une session active, son compte est désactivé, à la prochaine requête il est déconnecté
  - Données : Créer un utilisateur, simuler une connexion avec un token, désactiver le user en base, faire une requête HTTP
  - Résultat attendu : Redirection vers `/login`, session invalidée, message flash présent

### Documentation

Aucune documentation spécifique pour cette tâche. Le comportement sera documenté dans le README (Tâche #004).

### Exemples d'utilisation (fonctionnement interne)

```php
// Logique du listener
public function onKernelRequest(RequestEvent $event): void
{
    if (!$event->isMainRequest()) {
        return;
    }

    $token = $this->tokenStorage->getToken();
    if ($token === null) {
        return;
    }

    $user = $token->getUser();
    if (!$user instanceof User) {
        return;
    }

    if ($user->isActive()) {
        return;
    }

    // Déconnexion : invalider la session et le token
    $request = $event->getRequest();
    if ($request->hasSession()) {
        $request->getSession()->invalidate();
        $request->getSession()->getFlashBag()->add(
            'danger',
            'Votre compte a été désactivé. Contactez l\'administrateur.'
        );
    }

    $this->tokenStorage->setToken(null);

    // Redirection vers login
    $loginUrl = $this->router->generate('app_login');
    $event->setResponse(new RedirectResponse($loginUrl));
}
```
