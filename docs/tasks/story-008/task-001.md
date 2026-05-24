# Tâche #001 - Story #008 : Interface admin de gestion des utilisateurs

## Objectif

Ajouter une interface d'administration permettant à un admin de lister les utilisateurs et de désactiver/réactiver un compte (champ `isActive`).

## Contexte

- Story #008 : `docs/stories/story-008.md`
- Dépend de : Story #002 (entité User), Story #003 (authentification)
- Nécessaire pour : Tâche #004 (tests E2E)
- L'entité `User` existe déjà avec les champs (`isActive`, `lastLoginAt`, `verifiedAt`). Le champ `createdAt` (datetime_immutable) sera ajouté dans la Tâche #002 et est disponible pour l'affichage dans le tableau
- Le `UserChecker` (`src/Security/UserChecker.php`) empêche déjà la connexion des utilisateurs désactivés (criterion 6 déjà implémenté)
- `AdminController` existe déjà dans `src/Controller/AdminController.php` avec des routes de gestion de commandes
- `UserService` existe déjà dans `src/Service/UserService.php` avec les méthodes `register`, `validateEmail`, `requestPasswordReset`, `resetPassword`

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Ajouter deux nouvelles routes dans `AdminController` et une nouvelle template Twig pour la gestion des utilisateurs.

**Cas nominaux :**
- L'admin accède à `/admin/utilisateurs` et voit la liste paginée de tous les utilisateurs avec : email, prénom, nom, date d'inscription, date de dernière connexion, statut (actif/désactivé), rôle
- L'admin peut cliquer sur un bouton "Désactiver" / "Réactiver" pour basculer le statut `isActive` d'un utilisateur (y compris les autres admins)
- Après désactivation/réactivation, l'admin est redirigé vers la liste avec un message flash de confirmation

**Cas limites :**
- L'admin ne peut pas se désactiver lui-même (vérification : l'utilisateur connecté ne peut pas modifier son propre statut)
- Un admin peut désactiver un autre admin (aucune restriction de rôle)
- Si l'utilisateur est déjà désactivé, le bouton affiche "Réactiver"
- Si l'utilisateur n'a jamais été connecté (`lastLoginAt` = null), afficher "Jamais"

**Gestion d'erreurs :**
- Utilisateur inexistant → 404 (géré automatiquement par le ParamConverter)
- Tentative d'auto-désactivation → flash error + redirection vers la liste

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/AdminController.php` | Modifier | Ajouter les routes `app_admin_users` et `app_admin_user_toggle` |
| `src/Service/UserService.php` | Modifier | Ajouter la méthode `deactivateUser(User $user)` |
| `templates/admin/users.html.twig` | Créer | Template liste des utilisateurs avec actions |
| `src/Repository/UserRepository.php` | Modifier | Ajouter la méthode `findAllPaginated` |
| `config/packages/security.yaml` | Modifier (si nécessaire) | Vérifier que la route est bien protégée par ROLE_ADMIN |

### Signatures

```php
// Dans AdminController

#[Route('/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
public function users(
    Request $request,
    UserRepository $userRepository,
    PaginationService $paginationService,
): Response;

#[Route('/utilisateur/{id}/toggle', name: 'app_admin_user_toggle', methods: ['POST'])]
public function toggleUser(
    User $user,
    UserService $userService,
): Response;
```

```php
// Dans UserService

public function deactivateUser(User $user): User
{
    // Bascule isActive, persist et flush
}
```

```php
// Dans UserRepository

/**
 * Retourne une requête paginée pour la liste des utilisateurs,
 * triée par id décroissant (les plus récents en premier).
 */
public function createPaginatedQueryBuilder(): QueryBuilder;
```

### Contraintes techniques

- **Framework** : Symfony 8.0, Twig 3.x
- **Pattern** : Suivre le pattern existant de `AdminController::orders()` pour la pagination et `AdminController::orderDetail()` pour la gestion d'erreurs
- **Routing** : Utiliser les attributs PHP 8 `#[Route]` comme dans le `AdminController` existant
- **Template** : Utiliser le pattern existant du dashboard admin (base commune). La template doit étendre `base.html.twig` avec un bloc spécifique. Utiliser `bootstrap_table` ou le style de tableau déjà utilisé dans `templates/admin/orders.html.twig`
- **Pagination** : Réutiliser `PaginationService` exactement comme dans `AdminController::orders()`
- **CSRF** : Le bouton de désactivation doit être un formulaire POST avec un token CSRF (pattern identique aux routes POST existantes dans `AdminController`)
- **Messages flash** : Utiliser `addFlash('success', ...)` et `addFlash('error', ...)` comme dans les méthodes existantes de `AdminController`
- **UserService** : La méthode `deactivateUser` doit appeler `$this->entityManager->flush()` et retourner le `User`

### Tests à implémenter

Ces tests seront écrits dans la Tâche #004. Seuls les tests unitaires et d'intégration ci-dessous sont à prévoir.

#### Tests unitaires
- **Fichier** : `tests/Unit/Service/UserServiceTest.php`
- Scénario : `deactivateUser` bascule `isActive` de true à false et vice versa
  - Données : User avec isActive = true → appel deactivateUser → isActive = false
  - Résultat attendu : L'entité est persistée et le champ est inversé

#### Tests d'intégration
- **Fichier** : `tests/Integration/Controller/AdminControllerTest.php`
- Scénario : Un admin peut accéder à la liste des utilisateurs et désactiver un utilisateur
  - Données : Créer un admin et un utilisateur standard, se connecter en admin, POST sur `/admin/utilisateur/{id}/toggle`
  - Résultat attendu : Redirection 302 vers `/admin/utilisateurs`, l'utilisateur est désactivé

### Documentation

Aucune documentation spécifique pour cette tâche. La procédure sera documentée dans la Tâche #004 dans le README.

### Exemples d'utilisation

```twig
{# templates/admin/users.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
<div class="container-fluid py-4">
    <h1>Gestion des utilisateurs</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Email</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Inscrit le</th>
                <th>Dernière connexion</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        {% for user in pagination.items %}
            <tr>
                <td>{{ user.email }}</td>
                <td>{{ user.firstName }}</td>
                <td>{{ user.lastName }}</td>
                <td>{{ user.createdAt ? user.createdAt|date('d/m/Y H:i') : '-' }}</td>
                <td>{{ user.lastLoginAt ? user.lastLoginAt|date('d/m/Y H:i') : 'Jamais' }}</td>
                <td>
                    <span class="badge bg-{{ user.isActive ? 'success' : 'danger' }}">
                        {{ user.isActive ? 'Actif' : 'Désactivé' }}
                    </span>
                </td>
                <td>
                    {% if app.user.id != user.id %}
                        <form method="post" action="{{ path('app_admin_user_toggle', {id: user.id}) }}" style="display:inline">
                            <input type="hidden" name="_token" value="{{ csrf_token('toggle-user-' ~ user.id) }}">
                            <button type="submit" class="btn btn-sm btn-{{ user.isActive ? 'warning' : 'success' }}">
                                {{ user.isActive ? 'Désactiver' : 'Réactiver' }}
                            </button>
                        </form>
                    {% else %}
                        <span class="text-muted">Non modifiable</span>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>
{% endblock %}
```
