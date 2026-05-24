# Tâche #004 - Story #008 : Tests automatisés

## Objectif

Couvrir l'ensemble de la Story #008 avec des tests unitaires (PHPUnit), d'intégration (PHPUnit) et E2E (Playwright).

## Contexte

- Story #008 : `docs/stories/story-008.md`
- Dépend de : Tâche #001, Tâche #002, Tâche #003 (tout doit être implémenté avant de tester)
- Ordre d'exécution des tâches métier : Tâche #001 → Tâche #003 → Tâche #002 (les commandes console n'étant pas nécessaires aux tests UI)
- Framework de test : PHPUnit 13 avec attribut `#[Test]`
- Aucun test n'existe encore dans `tests/`

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Implémenter tous les tests couvrant les critères d'acceptation de la Story #008.

**Tests demandés par la story :**
- Test unitaire : UserManager — désactivation, purge des comptes inactifs
- Test d'intégration : event listener déconnecte l'utilisateur désactivé
- Test E2E (Playwright) : admin désactive un utilisateur → il est déconnecté à la prochaine action

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/` | Créer répertoire | Racine des tests (si inexistant) |
| `tests/bootstrap.php` | Créer | Bootstrap PHPUnit chargé de créer la BDD de test |
| `tests/Unit/Service/UserServiceTest.php` | Créer | Tests unitaires de `deactivateUser` |
| `tests/Unit/Command/PurgeInactiveUsersCommandTest.php` | Créer | Tests unitaires de la commande `app:users:purge-inactive` |
| `tests/Unit/Command/PurgeUnverifiedUsersCommandTest.php` | Créer | Tests unitaires de la commande `app:users:purge-unverified` |
| `tests/Unit/Command/ListStalledOrdersCommandTest.php` | Créer | Tests unitaires de la commande `app:orders:list-stalled` |
| `tests/Integration/Controller/AdminControllerTest.php` | Créer | Tests d'intégration des routes admin (désactivation) |
| `tests/Integration/EventListener/UserDeactivatedSubscriberTest.php` | Créer | Tests d'intégration du listener de déconnexion |
| `tests/E2E/admin-deactivation.spec.ts` | Créer (ou `tests/Playwright/...`) | Test E2E Playwright |

### Signatures

#### Test unitaire UserService

```php
// tests/Unit/Service/UserServiceTest.php

#[Test]
public function deactivateUserTogglesIsActiveToFalse(): void;

#[Test]
public function deactivateUserTogglesIsActiveToTrue(): void;

#[Test]
public function deactivateUserFlushesEntityManager(): void;
```

#### Test unitaire Commande PurgeInactive

```php
// tests/Unit/Command/PurgeInactiveUsersCommandTest.php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[Test]
public function dryRunShowsUsersWithoutDeleting(): void;

#[Test]
public function executeDeletesInactiveUsers(): void;

#[Test]
public function noInactiveUsersShowsMessage(): void;
```

#### Test d'intégration AdminController

```php
// tests/Integration/Controller/AdminControllerTest.php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Test]
public function adminCanViewUserList(): void;

#[Test]
public function adminCanDeactivateUser(): void;

#[Test]
public function adminCannotDeactivateSelf(): void;
```

#### Test d'intégration Event Listener

```php
// tests/Integration/EventListener/UserDeactivatedSubscriberTest.php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Test]
public function deactivatedUserIsLoggedOutOnNextRequest(): void;
```

### Contraintes techniques

- **Framework** : PHPUnit 13
- **Conventions** :
  - Utiliser l'attribut `#[Test]` (pas l'annotation `@test`)
  - Respecter le naming `camelCase` pour les méthodes de test
  - Les classes de test doivent suffixer par `Test`
  - Étendre `KernelTestCase` pour les tests unitaires avec accès au conteneur
  - Étendre `WebTestCase` pour les tests d'intégration HTTP
- **Base de données** : Configurer le `dbname_suffix` pour les tests via `.env.test` (déjà prévu dans `doctrine.yaml` avec `'_test%env(default::TEST_TOKEN)%'`)
- **Bootstrap** : Créer `tests/bootstrap.php` qui :
  - Charge le `.env.test` avec `Dotenv`
  - Exécute les migrations Doctrine (`doctrine:schema:create` ou `doctrine:migrations:migrate -n`)
- **Fixtures** : Utiliser des méthodes privées `createUser(...)`, `createOrder(...)` pour insérer des données de test (pas de bundle DoctrineFixtures requis)
- **Client HTTP** : Utiliser `static::createClient()` pour les tests WebTestCase
- **Commandes** : Utiliser `CommandTester` pour tester les commandes console

### Tests à implémenter

#### Tests unitaires — UserService

**Fichier** : `tests/Unit/Service/UserServiceTest.php`

- Scénario 1 : `deactivateUser` bascule un compte actif en inactif
  - Données : User isActive = true
  - Résultat attendu : isActive = false après appel

- Scénario 2 : `deactivateUser` bascule un compte inactif en actif
  - Données : User isActive = false
  - Résultat attendu : isActive = true après appel

- Scénario 3 : `deactivateUser` persiste bien la modification
  - Données : User isActive = true, appel, rechargement depuis BDD (si test intégration)
  - Résultat attendu : isActive = false en base

#### Tests unitaires — PurgeInactiveUsersCommand

**Fichier** : `tests/Unit/Command/PurgeInactiveUsersCommandTest.php`

- Scénario 1 : Dry-run liste les utilisateurs sans les supprimer
  - Données : 1 user avec lastLoginAt = 3 ans, 1 user connecté hier
  - Résultat attendu : CommandTester output contient "1 user(s)", les 2 users existent encore

- Scénario 2 : Exécution réelle supprime les utilisateurs inactifs
  - Données : 1 user avec lastLoginAt = 3 ans
  - Résultat attendu : User supprimé (count = 0 après rechargement)

- Scénario 3 : Utilisateur avec lastLoginAt = null n'est PAS supprimé
  - Données : 1 user avec lastLoginAt = null (jamais connecté)
  - Résultat attendu : Aucun utilisateur supprimé

- Scénario 4 : Aucun utilisateur inactif → message
  - Données : Tous les users ont lastLoginAt récent (hier)
  - Résultat attendu : Output contient "Aucun utilisateur"

#### Tests unitaires — PurgeUnverifiedUsersCommand

**Fichier** : `tests/Unit/Command/PurgeUnverifiedUsersCommandTest.php`

- Scénario 1 : Supprime les utilisateurs non validés de plus de 7 jours
  - Données : 1 user verifiedAt = null, createdAt = -10 jours ; 1 user verifiedAt = null, createdAt = hier
  - Résultat attendu : Seul le user de -10 jours est supprimé

- Scénario 2 : Utilisateur avec createdAt = null (migration non passée) est exclu
  - Données : 1 user verifiedAt = null, createdAt = null
  - Résultat attendu : Aucun utilisateur supprimé

- Scénario 3 : Dry-run ne supprime rien
  - Données : 1 user éligible à la purge
  - Résultat attendu : Output liste le user, user encore en base

#### Tests unitaires — ListStalledOrdersCommand

**Fichier** : `tests/Unit/Command/ListStalledOrdersCommandTest.php`

- Scénario 1 : Affiche les commandes en attente depuis plus de 7 jours
  - Données : 1 order confirmed depuis 10 jours, 1 order delivered récent
  - Résultat attendu : Seule la commande confirmed est listée

- Scénario 2 : Aucune commande en attente
  - Données : Toutes les commandes sont delivered
  - Résultat attendu : Message "Aucune commande en attente"

#### Tests d'intégration — AdminController

**Fichier** : `tests/Integration/Controller/AdminControllerTest.php`

- Scénario 1 : Admin peut voir la liste des utilisateurs
  - Données : Se connecter en admin (ROLE_ADMIN)
  - Résultat attendu : 200 OK, contient "Gestion des utilisateurs"

- Scénario 2 : Admin peut désactiver un utilisateur
  - Données : POST sur `/admin/utilisateur/2/toggle` avec CSRF token
  - Résultat attendu : 302 redirect, l'utilisateur #2 a isActive = false

- Scénario 3 : Admin ne peut pas se désactiver lui-même
  - Données : Admin connecté, POST sur `/admin/utilisateur/{son-id}/toggle`
  - Résultat attendu : 302 redirect, flash error, admin toujours isActive = true

#### Tests d'intégration — Event Listener

**Fichier** : `tests/Integration/EventListener/UserDeactivatedSubscriberTest.php`

- Scénario 1 : Un utilisateur désactivé est déconnecté à la prochaine requête
  - Données : Créer user actif, simuler connexion (token), désactiver en BDD, faire requête sur une page protégée
  - Résultat attendu : Redirection vers /login, session invalidée
  - Note : Pour simuler "déjà connecté puis désactivé", on peut manipuler le token directement ou utiliser un test fonctionnel avec session persistée

#### Test E2E Playwright

**Fichier** : `tests/E2E/admin-deactivation.spec.ts` (ou dans `tests/Playwright/`)

- Scénario : Admin se connecte, va sur `/admin/utilisateurs`, désactive un utilisateur
  - Étapes :
    1. Admin se connecte (email: admin@test.com, password: password)
    2. Navigue vers `/admin/utilisateurs`
    3. Vérifie que la liste contient l'utilisateur "Jean Dupont"
    4. Clique sur "Désactiver" pour Jean Dupont
    5. Vérifie que le statut devient "Désactivé"
  - Résultat attendu : Le statut est mis à jour

### Documentation

#### Documentation à créer
- `README.md` : Ajouter/Ajourner la section "Commandes console" avec :
  - `app:users:purge-inactive` : Supprime les comptes inactifs depuis plus de 2 ans. Dry-run : `--dry-run`
  - `app:users:purge-unverified` : Supprime les comptes non validés après 7 jours. Dry-run : `--dry-run`
  - `app:orders:list-stalled` : Liste les commandes non livrées depuis plus de 7 jours

#### Documentation à mettre à jour
- `README.md` : Ajouter une section "Administration" décrivant :
  - Comment désactiver un utilisateur via l'interface `/admin/utilisateurs`
  - Qu'un utilisateur désactivé est automatiquement déconnecté à sa prochaine requête
  - Les commandes de maintenance disponibles

### Exemples d'utilisation

```bash
# Test unitaire UserService
bin/phpunit tests/Unit/Service/UserServiceTest.php

# Test d'intégration AdminController
bin/phpunit tests/Integration/Controller/AdminControllerTest.php

# Test d'intégration EventListener
bin/phpunit tests/Integration/EventListener/UserDeactivatedSubscriberTest.php

# Tous les tests
bin/phpunit

# Test E2E Playwright (commande à adapter)
npx playwright test tests/E2E/admin-deactivation.spec.ts
```
