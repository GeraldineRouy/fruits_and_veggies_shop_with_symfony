# Tâche #002 - Story #010 : Tests automatisés du compte admin

## Objectif

Implémenter un test d'intégration complet qui valide que la migration de données crée correctement le compte admin, que l'admin peut se connecter et accéder au dashboard.

## Contexte

- Story #010 : `docs/stories/story-010.md`
- Dépend de : Tâche #001 (migration du compte admin)
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer un seul fichier de test d'intégration qui valide l'ensemble du comportement du compte admin créé par la migration de données.

**Cas nominaux :**
- Après exécution de la migration, l'admin existe en base avec les bons champs (email, `ROLE_ADMIN`, password hashé, `verifiedAt` non null, `isActive = true`, `firstName = 'Admin'`, `lastName = 'Admin'`)
- La migration est idempotente : exécutée deux fois, elle ne crée pas de doublon
- L'admin peut se connecter avec email `admin@example.com` / mot de passe `admin`
- L'admin connecté peut accéder au dashboard `/admin`
- La méthode `down()` supprime bien l'admin

**Cas limites :**
- Un utilisateur avec l'email `admin@example.com` existant déjà n'est pas modifié

**Gestion d'erreurs :**
- Nettoyer la base avant chaque test (DELETE FROM User)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Integration/AdminMigrationFlowTest.php` | Créer | Test d'intégration complet (création, connexion, dashboard) |

### Signatures et structures

#### Test d'intégration : `tests/Integration/AdminMigrationFlowTest.php`

Namespace : `App\Tests\Integration`

Étend `WebTestCase`. Le test doit couvrir tous les aspects dans une ou plusieurs méthodes `#[Test]` :

1. **Exécution de la migration** : Utiliser `doctrine:migrations:migrate` commande via `$application->run()` pour exécuter la migration de données
2. **Vérification en base** : Requêter la table `"user"` pour vérifier les champs de l'admin
3. **Connexion** : Soumettre le formulaire de login avec les identifiants admin
4. **Accès dashboard** : Vérifier que `/admin` répond avec le dashboard

Méthode recommandée pour exécuter la migration en test :

```php
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$kernel = static::createKernel();
$application = new Application($kernel);
$application->setAutoExit(false);

// Exécuter toutes les migrations
$input = new ArrayInput([
    'command' => 'doctrine:migrations:migrate',
    '--no-interaction' => true,
    '--env' => 'test',
]);
$application->run($input, new NullOutput());
```

### Tests à implémenter

#### Tests dans `AdminMigrationFlowTest.php`

- **Scénario 1** : La migration crée l'admin avec les bons champs
  - Nettoyer la base (DELETE FROM User)
  - Exécuter `doctrine:migrations:migrate`
  - Requêter l'utilisateur avec email `admin@example.com` via `UserRepository`
  - Vérifier : `firstName = 'Admin'` ✅ `lastName = 'Admin'` ✅ `roles` contient `ROLE_ADMIN` ✅ `verifiedAt IS NOT NULL` ✅ `isActive = true` ✅

- **Scénario 2** : La migration est idempotente
  - Nettoyer la base
  - Exécuter la migration deux fois
  - Vérifier qu'il n'y a qu'un seul utilisateur avec l'email `admin@example.com`

- **Scénario 3** : Connexion de l'admin et accès au dashboard
  - Nettoyer la base
  - Exécuter la migration
  - Naviguer vers `/login`
  - Soumettre le formulaire avec `admin@example.com` / `admin`
  - Vérifier la redirection (succès de connexion) → suivre la redirection
  - Accéder à `/admin`
  - Vérifier `assertResponseIsSuccessful()` et présence du texte "Dashboard Administration"

- **Scénario 4** : La méthode `down()` supprime l'admin
  - Nettoyer la base
  - Exécuter la migration (`doctrine:migrations:migrate`)
  - Exécuter le rollback (`doctrine:migrations:migrate prev`)
  - Vérifier que l'email `admin@example.com` n'existe plus

### Contraintes techniques

- **Framework de test** : PHPUnit 13 avec attributs `#[Test]` (pas d'annotation `@test`)
- **Convention** : Suivre exactement le pattern des tests existants
  - `setUp()` : créer un client (`$this->client = static::createClient()`) et nettoyer `User`
  - Utiliser `static::getContainer()` pour accéder aux services
- **Nettoyage** : `DELETE FROM App\Entity\User u` via DQL (comme dans `AdminControllerTest::setUp()`)
- **Exécution de la migration en test** : Utiliser `$application->run()` avec `ArrayInput` (approche console)
- **Pas de fixture** : Ne pas créer de fixture - la migration elle-même est ce qu'on teste

### Documentation

Aucune documentation spécifique pour cette tâche.

### Exemples d'utilisation

```bash
# Exécuter le test d'intégration
docker compose exec app php bin/phpunit tests/Integration/AdminMigrationFlowTest.php
```
