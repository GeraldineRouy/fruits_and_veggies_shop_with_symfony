# Tâche #006 - Story #003 : Tests automatisés

## Objectif
Implémenter les tests unitaires, d'intégration et E2E pour couvrir l'ensemble du flux d'inscription, validation email, connexion et réinitialisation de mot de passe.

## Contexte
- Story #003 : [Story Inscription, connexion et validation email](../../stories/story-003.md)
- Dépend de : Tâches #001, #002, #003, #004, #005 (toutes les implémentations doivent être terminées)
- Nécessaire pour : Rien (dernière tâche)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Cette tâche consolide et complète les tests éparpillés dans les tâches précédentes, et ajoute un test E2E Playwright pour le parcours complet.

**Cas nominaux :**
- Tests unitaires : UserService (hashing, validation, reset) et MailerService (construction des emails)
- Tests d'intégration : Parcours inscription → validation → connexion en base de données réelle de test
- Test E2E : Parcours utilisateur complet via navigateur (Playwright)

**Périmètre :**
- Tous les tests unitaires et d'intégration définis dans les tâches #001 à #005 doivent être implémentés dans cette tâche (en un seul fichier cohérent ou répartis logiquement)
- Un test E2E Playwright doit être ajouté

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Unit/Service/UserServiceTest.php` | Créer | Tests unitaires du UserService |
| `tests/Unit/Service/MailerServiceTest.php` | Créer | Tests unitaires du MailerService |
| `tests/Integration/Service/RegistrationFlowTest.php` | Créer | Test d'intégration du parcours complet |
| `tests/Integration/Controller/LoginControllerTest.php` | Créer | Tests du controller de login |
| `tests/Integration/Controller/RegistrationControllerTest.php` | Créer | Tests du controller d'inscription |
| `tests/Integration/Controller/PasswordResetControllerTest.php` | Créer | Tests du controller de reset password |
| `tests/E2E/registration.spec.js` | Créer | Test E2E Playwright |
| `playwright.config.js` | Créer | Configuration Playwright |
| `package.json` | Créer | Dépendances Node : Playwright |
| `composer.json` | Modifier | Vérifier les dépendances de test |

### Contraintes techniques

- **Framework de test** : PHPUnit 13 avec attributs `#[Test]` (pas d'annotation `@test`)
- **Configuration test** : Utiliser `.env.test` existant avec la base de données suffixée `_test`
- **Fixtures** : Utiliser les fixtures Doctrine pour créer les données de test
  - Créer `tests/ Fixtures/UserFixtures.php` si nécessaire
- **Client HTTP** : Utiliser `Symfony\Bundle\FrameworkBundle\Test\WebTestCase` avec `static::createClient()`
- **E2E** : Playwright (Node.js) — créer `package.json` avec `playwright` en dépendance de dev et `playwright.config.js` configuré pour l'URL `http://localhost:8000`
- **Playwright setup** : 
  - Créer `package.json` avec `{ "devDependencies": { "@playwright/test": "^1.52" } }`
  - Créer `playwright.config.js` avec `use: { baseURL: 'http://localhost:8000' }`
  - Le test E2E est exécutable via `npx playwright test`
- **Coverage** : Minimum 80% de coverage sur les services (UserService, MailerService)

### Tests à implémenter

#### Tests unitaires

##### UserServiceTest
- **Fichier** : `tests/Unit/Service/UserServiceTest.php`
- Scénario 1 : `register()` hashe le mot de passe et ajoute ROLE_USER
  - Mocker `UserPasswordHasherInterface` et `EntityManagerInterface`
  - Vérifier que le password est hashé et que roles = ['ROLE_USER']
- Scénario 2 : `register()` avec email duplicat → exception
  - Mocker `UserRepository::findOneByEmail()` pour retourner un User existant
- Scénario 3 : `validateEmail()` avec token valide
  - Vérifier que verifiedAt est défini et token effacé
- Scénario 4 : `validateEmail()` avec token invalide → exception
- Scénario 5 : `resetPassword()` avec token valide → nouveau hash, ResetPasswordRequest supprimé
- Scénario 6 : `resetPassword()` avec token expiré → exception
- Scénario 7 : `requestPasswordReset()` crée un ResetPasswordRequest en base → vérifier token non null, expiresAt = now + 1h

##### MailerServiceTest
- **Fichier** : `tests/Unit/Service/MailerServiceTest.php`
- Scénario 1 : `sendValidationEmail()` construit le bon email
  - Mocker `MailerInterface`
  - Vérifier le destinataire, le sujet, et la présence du lien
- Scénario 2 : `sendPasswordResetEmail()` construit le bon email avec un token donné
  - Mocker `MailerInterface`
  - Vérifier le destinataire et que l'URL contient bien le token passé en paramètre

#### Tests d'intégration

##### RegistrationFlowTest
- **Fichier** : `tests/Integration/Service/RegistrationFlowTest.php`
- Scénario 1 : Parcours inscription → validation → connexion
  1. POST `/register` avec données valides
  2. Vérifier utilisateur créé en BDD avec emailVerificationToken
  3. GET `/verify-email?token=...`
  4. Vérifier verifiedAt non null
  5. POST `/login` avec email/mot de passe
  6. Vérifier redirection (connexion réussie)
- Scénario 2 : Connexion sans validation → bloquée
  1. POST `/register` avec données valides
  2. POST `/login` sans avoir validé
  3. Vérifier présence du flash "confirmer votre adresse email"

##### LoginControllerTest
- Mêmes scénarios que Task #003

##### RegistrationControllerTest
- Mêmes scénarios que Task #004

##### PasswordResetControllerTest
- Mêmes scénarios que Task #005

#### Test E2E (Playwright)
- **Fichier** : `tests/E2E/registration.spec.js`
- **Prérequis** : Serveur de test lancé (`symfony server:start` ou `php -S`)
- Scénario :
  1. Naviguer vers `/register`
  2. Remplir le formulaire
  3. Cliquer sur "Créer mon compte"
  4. Vérifier la page "Vérifiez vos emails"
  5. (Optionnel) Récupérer le token depuis la base de données ou le mail catcher
  6. Naviguer vers le lien de validation
  7. Vérifier la redirection vers `/login`
  8. Se connecter
  9. Vérifier la redirection vers la page d'accueil

### Documentation

#### Documentation à créer
- `tests/README.md` : Comment lancer les tests (unitaires, intégration, E2E) avec les commandes exactes
