# Tâche #003 - Story #001 : Tests et documentation Docker

## Objectif
Implémenter les tests automatisés (intégration + unitaire) et la documentation complète pour valider l'environnement Docker et le socle Symfony.

## Contexte
- Story #001 : `docs/stories/story-001.md`
- Dépend de : Tâche #002 (le contrôleur doit exister)
- Nécessaire pour : Aucune

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle
Les critères d'acceptation de la Story #001 doivent être validés par des tests automatisés et documentés.

**Cas nominaux :**
- Un test d'intégration vérifie que la page d'accueil répond en HTTP 200
- Un test unitaire valide la configuration Doctrine (connexion DBOK)
- Le README.md contient les instructions d'installation et de démarrage via Docker
- La documentation docker-compose est complète

**Gestion d'erreurs :**
- Les tests doivent fonctionner en environnement CI comme en local
- Les tests Doctrine doivent utiliser la base de test (suffixe `_test`)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Controller/HomeControllerTest.php` | Créer | Test d'intégration de la page d'accueil |
| `tests/Doctrine/DoctrineConnectionTest.php` | Créer | Test unitaire de la configuration Doctrine |
| `README.md` | Modifier | Ajouter les sections d'installation, démarrage, tests |
| `docs/docker-compose.md` | Créer | Documentation détaillée de l'environnement Docker |

### Signatures

```php
// tests/Doctrine/DoctrineConnectionTest.php
namespace App\Tests\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineConnectionTest extends KernelTestCase
{
    /**
     * Vérifie que la connexion Doctrine est fonctionnelle
     * et que l'entité manager est correctement configuré.
     */
    public function testDoctrineConnection(): void
    {
        // Boot du kernel Symfony
        // Récupérer EntityManagerInterface
        // Vérifier que la connexion est opérationnelle
        // Vérifier que le platform name est PostgreSQL
    }
}
```

### Contraintes techniques
- **PHPUnit 13** : Utiliser l'attribut `#[Test]`, pas l'annotation `@test`
- **KernelTestCase** : Pour les tests Doctrine (nécessite boot du kernel)
- **WebTestCase** : Pour les tests contrôleur
- **Base de test** : La config Doctrine utilise `dbname_suffix: '_test%env(default::TEST_TOKEN)%'` — pas de configuration manuelle nécessaire
- **README** : Format Markdown, sections claires

### Tests à implémenter

#### Test d'intégration (page d'accueil)
- **Fichier** : `tests/Controller/HomeControllerTest.php`
- Scénario 1 : Page d'accueil répond HTTP 200 avec contenu attendu
  - Client GET `/`
  - Assert response successful
  - Assert contient "Bienvenue chez Fruits & Veggies"
- Scénario 2 : La page utilise le template de base
  - Assert `doctype html`

#### Test unitaire (Doctrine)
- **Fichier** : `tests/Doctrine/DoctrineConnectionTest.php`
- Scénario 1 : La connexion Doctrine est fonctionnelle
  - Boot kernel, récupérer `EntityManagerInterface`
  - `$connection->connect()` ne lance pas d'exception
- Scénario 2 : Le platform name est PostgreSQL
  - `$connection->getDatabasePlatform()` retourne PostgreSQL platform

### Documentation

#### Documentation à créer
- `docs/docker-compose.md` :
  - Architecture des services (app, database, mailer)
  - Commandes utiles (`docker compose up -d`, `docker compose down`, etc.)
  - Logs et debugging
  - Reconstruction de l'image (`docker compose build`)
  - Exécution de commandes Symfony dans le conteneur (`docker compose exec app php bin/console ...`)

#### Documentation à mettre à jour
- `README.md` (section à ajouter) :
  ```markdown
  ## Installation

  ### Prérequis
  - Docker & Docker Compose

  ### Démarrage rapide
  1. `docker compose up -d`
  2. `docker compose exec app php bin/console doctrine:migrations:migrate -n`
  3. Accéder à `http://localhost:8000`

  ### Tests
  ```bash
  docker compose exec app php bin/phpunit
  ```
  ```

### Exemples d'API
- Aucun (pas d'API REST)

#### Commandes utiles à documenter

```bash
# Démarrer l'environnement
docker compose up -d

# Voir les logs
docker compose logs -f

# Exécuter une commande Symfony
docker compose exec app php bin/console cache:clear

# Exécuter les tests
docker compose exec app php bin/phpunit

# Arrêter l'environnement
docker compose down

# Reconstruire l'image PHP
docker compose build
```
