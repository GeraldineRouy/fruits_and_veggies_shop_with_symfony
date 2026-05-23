# Tâche #003 - Story #004 : Tests automatisés et documentation

## Objectif
Implémenter les tests unitaires (PHPUnit), d'intégration (PHPUnit avec client) et E2E (Playwright) pour le catalogue produits, ainsi que documenter les routes et les méthodes des repositories.

## Contexte
- Story #004 : [Catalogue produits et navigation](../../stories/story-004.md)
- Dépend de : Tâche #001 (Repository), Tâche #002 (Controller + Templates)
- Nécessaire pour : Rien (dernière tâche de la story)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux des tests :**

#### Tests unitaires (ProductRepository)
- `findByCategoryPaginated()` avec catégorie existante contenant des produits → retourne un Paginator non vide
- `findByCategoryPaginated()` avec page=1, limit=12 et 25 produits → retourne 12 résultats, sur 3 pages totales
- `findByCategoryPaginated()` avec page=1 et limit=5 → retourne max 5 résultats
- `findByCategoryPaginated()` avec catégorie sans produit → Paginator vide (0 résultats)
- `findAllOrdered()` → retourne les catégories triées par nom (ordre alphabétique)

#### Tests d'intégration (routes)
- GET `/` → HTTP 200, contient la liste des catégories
- GET `/boutique/{id}` avec catégorie valide → HTTP 200, contient les produits de la catégorie
- GET `/boutique/{id}` avec catégorie invalide → HTTP 404
- GET `/boutique/produit/{id}` avec produit valide → HTTP 200, contient le nom et le prix du produit
- GET `/boutique/produit/{id}` avec produit invalide → HTTP 404
- GET `/boutique/{id}?page=2` → HTTP 200, pagination fonctionnelle

#### Test E2E (Playwright)
- Navigation complète : page d'accueil → clic sur une catégorie → affichage des produits → clic sur un produit → affichage de la fiche détaillée

**Cas limites testés :**
- Pagination : page 1 sur 1 page → pas de pagination affichée
- Pagination : page = 999 (page inexistante) → aucun produit
- Catégorie sans produit → message "Aucun produit dans cette catégorie"

**Gestion d'erreurs testées :**
- 404 sur catégorie inexistante
- 404 sur produit inexistant
- Page < 1 → exception InvalidArgumentException

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Unit/Repository/ProductRepositoryTest.php` | Créer | Tests unitaires du ProductRepository |
| `tests/Unit/Repository/CategoryRepositoryTest.php` | Créer | Tests unitaires du CategoryRepository |
| `tests/Integration/Controller/ShopControllerTest.php` | Créer | Tests d'intégration des routes du catalogue |
| `tests/E2E/catalogue.spec.js` | Créer | Test Playwright du parcours catalogue |
| `bin/e2e.ps1` | Créer | Script PowerShell qui démarre le serveur Symfony, lance les tests Playwright, puis arrête le serveur |
| `playwright.config.js` | Créer | Configuration Playwright (url de base, reporter, etc.) |
| `docs/routes/catalogue.md` | Créer | Documentation des routes du catalogue |

### Signatures

```php
// tests/Unit/Repository/ProductRepositoryTest.php
namespace App\Tests\Unit\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $productRepository;

    protected function setUp(): void;

    public function testFindByCategoryPaginatedReturnsPaginator(): void;
    public function testFindByCategoryPaginatedWithPagination(): void;
    public function testFindByCategoryPaginatedWithCustomLimit(): void;
    public function testFindByCategoryPaginatedEmptyCategory(): void;
    public function testFindByCategoryPaginatedThrowsExceptionForInvalidPage(): void;
}
```

```php
// tests/Unit/Repository/CategoryRepositoryTest.php
namespace App\Tests\Unit\Repository;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryRepositoryTest extends KernelTestCase
{
    private CategoryRepository $categoryRepository;

    protected function setUp(): void;

    public function testFindAllOrderedReturnsCategoriesSortedByName(): void;
}
```

```php
// tests/Integration/Controller/ShopControllerTest.php
namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShopControllerTest extends WebTestCase
{
    public function testHomePageDisplaysCategories(): void;
    public function testCategoryPageShowsProducts(): void;
    public function testCategoryPageNotFound(): void;
    public function testProductDetailPage(): void;
    public function testProductDetailNotFound(): void;
    public function testCategoryPageWithPagination(): void;
}
```

### Contraintes techniques

- **Framework de test** : PHPUnit 13, utiliser l'attribut `#[Test]` (pas l'annotation `@test`)
- **Base de test** : Utiliser la base de test avec suffixe `_test` (configurée dans `doctrine.yaml` via `dbname_suffix`)
- **Fixtures** : Les données de test sont créées en inline dans chaque méthode de test (via `EntityManager::persist()` + `flush()`), pas de classe de fixtures partagée. Chaque setUp() ou méthode crée les entités nécessaires.
  - Pour les tests unitaires : utiliser `KernelTestCase` avec boot du kernel et récupération de l'`EntityManager`
  - Pour les tests d'intégration : utiliser `WebTestCase` avec `static::createClient()` et récupération du conteneur pour l'`EntityManager`
  - Créer au moins 2 catégories : "Fruits" et "Légumes"
  - Créer au moins 25 produits répartis entre les 2 catégories (pour tester la pagination à 12)
  - Les produits doivent avoir des noms variés (classement alphabétique vérifiable)
  - Les prix doivent être des décimaux (ex: 2.50, 3.00)
- **Client de test** : Utiliser `static::createClient()` pour les tests d'intégration
- **Assertions Twig** : Utiliser `$client->getCrawler()->filter()` pour vérifier la présence d'éléments dans le HTML rendu
- **Assertions HTTP** : Vérifier le code status avec `assertResponseStatusCodeSame()`
- **E2E Playwright** :
  - Installer Playwright si pas déjà fait : `npx playwright install`
  - Créer un fichier `playwright.config.js` à la racine du projet avec :
    - `baseURL: 'http://127.0.0.1:8000'`
    - reporter `list`
    - Les tests sont dans `tests/E2E/`
  - Créer un script `bin/e2e.ps1` (PowerShell) qui :
    1. Démarre le serveur Symfony en arrière-plan : `symfony server:start --port=8000 --no-tls --daemon`
    2. Attend que le serveur soit prêt
    3. Lance `npx playwright test`
    4. Arrête le serveur : `symfony server:stop`
  - Le test E2E doit être exécutable via : `.\bin\e2e.ps1`
  - Scénario : navigation homepage → clic sur une catégorie → vérifier que les produits s'affichent → clic sur un produit → vérifier la page détail
  - Le test doit être lisible (nommer les étapes avec `test.step()`)
  - Pour Windows, le script peut aussi utiliser `php -S 127.0.0.1:8000 -t public` comme alternative si `symfony` CLI n'est pas installé

### Tests à implémenter

#### Tests unitaires

**Fichier :** `tests/Unit/Repository/ProductRepositoryTest.php`

- `testFindByCategoryPaginatedReturnsPaginator()` :
  - Créer 1 catégorie + 5 produits associés
  - Appeler `findByCategoryPaginated(category, 1, 12)`
  - Assert : `assertInstanceOf(Paginator::class, $result)`
  - Assert : `assertCount(5, $result)`

- `testFindByCategoryPaginatedWithPagination()` :
  - Créer 1 catégorie + 25 produits associés
  - Appeler `findByCategoryPaginated(category, 1, 12)`
  - Assert : `assertCount(12, iterator_to_array($result))` (12 produits page 1)
  - Assert : `ceil(25/12) === 3` pages totales (vérifier via `count($result)` ou calcul externe)
  - Appeler page 3 : dernier résultat = 1 produit restant

- `testFindByCategoryPaginatedWithCustomLimit()` :
  - Créer 1 catégorie + 10 produits
  - Appeler `findByCategoryPaginated(category, 1, 5)`
  - Assert : `assertCount(5, iterator_to_array($result))`

- `testFindByCategoryPaginatedEmptyCategory()` :
  - Créer 1 catégorie sans produit
  - Appeler `findByCategoryPaginated(category, 1, 12)`
  - Assert : `assertCount(0, iterator_to_array($result))`

- `testFindByCategoryPaginatedThrowsExceptionForInvalidPage()` :
  - Créer 1 catégorie
  - Appeler `findByCategoryPaginated(category, 0, 12)`
  - Assert : `expectException(\InvalidArgumentException::class)`

**Fichier :** `tests/Unit/Repository/CategoryRepositoryTest.php`

- `testFindAllOrderedReturnsCategoriesSortedByName()` :
  - Créer 3 catégories avec noms : "Légumes", "Fruits", "Agrumes"
  - Appeler `findAllOrdered()`
  - Assert : ordre = "Agrumes", "Fruits", "Légumes"
  - Assert : `assertCount(3, $result)`

#### Tests d'intégration

**Fichier :** `tests/Integration/Controller/ShopControllerTest.php`

- `testHomePageDisplaysCategories()` :
  - GET `/`
  - Assert : HTTP 200
  - Assert : le crawler contient au moins un élément `.category-card` ou le texte d'une catégorie

- `testCategoryPageShowsProducts()` :
  - Créer une catégorie avec 3 produits en base
  - GET `/boutique/{categoryId}`
  - Assert : HTTP 200
  - Assert : le crawler contient au moins un élément `.product-card`

- `testCategoryPageNotFound()` :
  - GET `/boutique/99999`
  - Assert : HTTP 404

- `testProductDetailPage()` :
  - Créer une catégorie avec 1 produit
  - GET `/boutique/produit/{productId}`
  - Assert : HTTP 200
  - Assert : le crawler contient le nom du produit

- `testProductDetailNotFound()` :
  - GET `/boutique/produit/99999`
  - Assert : HTTP 404

- `testCategoryPageWithPagination()` :
  - Créer une catégorie avec 25 produits
  - GET `/boutique/{categoryId}?page=2`
  - Assert : HTTP 200
  - Assert : des produits sont affichés (pas de message "aucun produit")

#### Test E2E (Playwright)

**Fichier :** `tests/E2E/catalogue.spec.js`

```javascript
// @ts-check
import { test, expect } from '@playwright/test';

test('Parcours catalogue : catégorie → liste → détail produit', async ({ page }) => {
    await test.step('Aller sur la page d\'accueil', async () => {
        await page.goto('/');
        await expect(page.locator('h1')).toContainText('Fruits & Veggies');
    });

    await test.step('Cliquer sur une catégorie', async () => {
        await page.locator('.category-card').first().click();
        await expect(page.locator('.products-grid')).toBeVisible();
    });

    await test.step('Cliquer sur un produit', async () => {
        await page.locator('.product-card').first().click();
        await expect(page.locator('.product-detail')).toBeVisible();
    });

    await test.step('Vérifier les informations du produit', async () => {
        await expect(page.locator('.product-detail__image')).toBeVisible();
        await expect(page.locator('.price')).toBeVisible();
    });
});
```

### Documentation

#### Documentation à créer

**Fichier :** `docs/routes/catalogue.md`

Documenter les routes du catalogue :

```markdown
# Routes du catalogue

## Page d'accueil
- **Route** : `app_home`
- **URL** : `GET /`
- **Controller** : `HomeController::index`
- **Description** : Affiche la page d'accueil avec la liste des catégories

## Liste des produits par catégorie
- **Route** : `app_shop_category`
- **URL** : `GET /boutique/{id}`
- **Controller** : `ShopController::category`
- **Paramètres** :
  - `id` (int) : ID de la catégorie
  - `page` (int, optionnel, défaut: 1) : Numéro de page dans la query string
- **Description** : Affiche les produits d'une catégorie avec pagination (12 par page)

## Fiche détaillée d'un produit
- **Route** : `app_shop_product`
- **URL** : `GET /boutique/produit/{id}`
- **Controller** : `ShopController::product`
- **Paramètres** :
  - `id` (int) : ID du produit
- **Description** : Affiche la fiche détaillée d'un produit (nom, description, image, prix, catégories)
```

### Exemples d'utilisation

Fichier : `docs/tasks/story-004/task-003-examples.http`

```http
### Page d'accueil
GET http://localhost:8000/

### Liste des produits de la catégorie 1 (page 1)
GET http://localhost:8000/boutique/1

### Liste des produits de la catégorie 1 (page 2)
GET http://localhost:8000/boutique/1?page=2

### Détail du produit 1
GET http://localhost:8000/boutique/produit/1

### Catégorie inexistante (doit retourner 404)
GET http://localhost:8000/boutique/99999

### Produit inexistant (doit retourner 404)
GET http://localhost:8000/boutique/produit/99999
```
