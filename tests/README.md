# Tests

## Prérequis

- Docker & Docker Compose (environnement de développement)
- Node.js et npm (pour les tests E2E Playwright uniquement)

## Lancement des tests

### Tests unitaires et d'intégration (PHPUnit)

```bash
# Depuis le conteneur Docker
docker compose exec app php bin/phpunit

# Avec couverture de code
docker compose exec app php bin/phpunit --coverage-html var/coverage

# Filtrer par test suite
docker compose exec app php bin/phpunit --testsuite unit
docker compose exec app php bin/phpunit --testsuite integration
```

### Tests E2E (Playwright)

```bash
# Installer les dépendances Node.js
npm install

# Installer les navigateurs Playwright
npx playwright install chromium

# Lancer les tests E2E (le serveur doit être accessible sur http://localhost:8000)
npm run test:e2e

# Mode interactif UI
npm run test:e2e:ui
```

## Structure

```
tests/
├── Unit/
│   └── Service/
│       ├── UserServiceTest.php        # Tests unitaires du UserService
│       └── MailerServiceTest.php      # Tests unitaires du MailerService
├── Integration/
│   ├── Controller/
│   │   ├── LoginControllerTest.php    # Tests d'intégration de l'authentification
│   │   ├── RegistrationControllerTest.php  # Tests d'intégration de l'inscription
│   │   └── PasswordResetControllerTest.php # Tests d'intégration du reset de mot de passe
│   └── Service/
│       └── RegistrationFlowTest.php   # Test du parcours complet inscription → validation → connexion
├── E2E/
│   └── registration.spec.js           # Test E2E Playwright
└── bootstrap.php                      # Bootstrap de l'environnement de test
```

## Configuration

- Les tests utilisent la base de données suffixée `_test` (configurée dans `doctrine.yaml`)
- Le fichier `.env.test` définit les variables d'environnement pour l'environnement de test
- PHPUnit est configuré via `phpunit.dist.xml`

## Bonnes pratiques

- Utiliser l'attribut `#[Test]` (pas d'annotation `@test`)
- Utiliser `WebTestCase` pour les tests d'intégration
- Nettoyer les données de test entre chaque test via `setUp()`
