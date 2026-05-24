# Tâche #003 - Story #014 : Tests d'intégration, E2E et documentation

## Objectif
Implémenter les tests d'intégration (PHPUnit) et E2E (Playwright) pour la page d'accueil enrichie, et mettre à jour le README avec la description des nouvelles fonctionnalités.

## Contexte
- Story #014 : `docs/stories/story-014.md`
- Dépend de : Tâche #001 (template réorganisé), Tâche #002 (catégories avec compteur) — exécuter APRÈS #002
- Nécessaire pour : Rien (dernière tâche de la story)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux des tests :**

#### Tests d'intégration
- GET `/` → HTTP 200, la page contient le texte de bienvenue exact
- GET `/` → La section des top produits est présente (quand des commandes existent)
- GET `/` → La section des catégories est présente et chaque catégorie affiche son compteur de produits
- GET `/` → Le compteur de produits est correct (vérifier qu'une catégorie avec 3 produits affiche "(3)")

#### Test E2E (Playwright)
- Navigation : page d'accueil → vérifier la présence du texte de bienvenue → vérifier la présence du top 3 → cliquer sur une catégorie → liste des produits de la catégorie

**Cas limites testés :**
- Aucune commande en base → la section top produits ne s'affiche pas (test déjà existant, à vérifier)
- Aucune catégorie en base → la page affiche un message "Aucune catégorie disponible pour le moment."
- Catégorie sans produit → affiche "(0)" dans le compteur

**Gestion d'erreurs testées :**
- Vérification que les sections sont dans le bon ordre (bienvenue avant top produits avant catégories)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Controller/HomeControllerTest.php` | Modifier | Ajouter les tests d'intégration pour la page d'accueil enrichie |
| `tests/E2E/accueil.spec.js` | Créer | Test Playwright du parcours page d'accueil |
| `README.md` | Modifier | Mettre à jour la section page d'accueil |

### Signatures

```php
// tests/Controller/HomeControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    protected function setUp(): void;

    // Helpers privés
    private function createCategory(string $name, string $description): Category;
    private function createProduct(string $name, Category $category): Product;
    private function createOrderWithProduct(Product $product, int $quantity): void;

    // Tests
    public function testHomePageDisplaysWelcomeText(): void;
    public function testHomePageDisplaysTopProducts(): void;
    public function testHomePageDisplaysCategoriesWithProductCount(): void;
    public function testHomePageDisplaysZeroCountForEmptyCategory(): void;
    public function testHomePageSectionsOrder(): void;
}
```

### Contraintes techniques
- **Framework de test** : PHPUnit 13, attribut `#[Test]`
- **Base de test** : Utiliser la base de test avec suffixe `_test` (configurée dans `doctrine.yaml`)
- **setUp()** : Nettoyer les tables OrderLine, Order, Product, Category avant chaque test
- **Client de test** : Utiliser `static::createClient()` pour les tests d'intégration
- **Assertions** :
  - `assertResponseStatusCodeSame()` pour le code HTTP
  - `$client->getCrawler()->filter()` pour vérifier la présence d'éléments CSS
  - `assertStringContainsString()` pour vérifier le texte de bienvenue exact
  - `assertSelectorExists()` / `assertSelectorNotExists()` selon les besoins
- **Les tests existants** dans `HomeControllerTest.php` (créés par Story #007) doivent continuer de passer
- **E2E Playwright** : Même configuration que les tests E2E existants (Story #004)

### Tests à implémenter

#### Tests d'intégration

**Fichier :** `tests/Controller/HomeControllerTest.php` (modifier)

- `testHomePageDisplaysWelcomeText()` :
  - Requête : `GET /`
  - Assert : HTTP 200
  - Assert : le HTML contient le titre exact "Bienvenue chez Fruits & Veggies Shop, votre primeur et épicerie fine grenobloise !"
  - Assert : le HTML contient le sous-titre exact "Nous sommes ravis de vous accueillir pour vous faire découvrir notre sélection de produits frais d'exception."

- `testHomePageDisplaysTopProducts()` :
  - Créer : 1 catégorie, 2 produits, 1 commande avec OrderLine (qty=5 pour P1, qty=3 pour P2)
  - Requête : `GET /`
  - Assert : le sélecteur `.top-products` existe (le contrôleur imbriqué s'affiche)

- `testHomePageDisplaysCategoriesWithProductCount()` :
  - Créer : 2 catégories ("Fruits" avec 3 produits, "Légumes" avec 0 produit)
  - Requête : `GET /`
  - Assert : le sélecteur `.category-card` existe (2 cartes)
  - Assert : le HTML contient "Fruits (3)"
  - Assert : le HTML contient "Légumes (0)"

- `testHomePageDisplaysZeroCountForEmptyCategory()` :
  - Créer : 1 catégorie sans produit
  - Requête : `GET /`
  - Assert : le HTML contient "(0)" dans la carte de la catégorie

- `testHomePageSectionsOrder()` :
  - Créer : 1 catégorie, 1 produit, 1 commande
  - Requête : `GET /`
  - Assert : vérifier que le contenu HTML a l'ordre : texte bienvenue → top produits → catégories
  - Utiliser `assertStringContainsString()` pour vérifier que le texte de bienvenue apparaît AVANT ".top-products" qui apparaît AVANT ".categories"
  - On peut extraire le HTML et vérifier les positions relatives des marqueurs

#### Test E2E (Playwright)

**Fichier :** `tests/E2E/accueil.spec.js`

```javascript
// @ts-check
import { test, expect } from '@playwright/test';

test('Page d\'accueil : bienvenue, top produits et navigation vers catégorie', async ({ page }) => {
    await test.step('Aller sur la page d\'accueil', async () => {
        await page.goto('/');
    });

    await test.step('Vérifier le texte de bienvenue', async () => {
        await expect(page.locator('.welcome h1')).toBeVisible();
        await expect(page.locator('.welcome')).toContainText('primeur et épicerie fine grenobloise');
    });

    await test.step('Vérifier la présence du top 3 des produits', async () => {
        await expect(page.locator('.top-products')).toBeVisible();
    });

    await test.step('Vérifier la section des catégories', async () => {
        await expect(page.locator('.categories')).toBeVisible();
        await expect(page.locator('.category-card')).not.toHaveCount(0);
    });

    await test.step('Cliquer sur une catégorie et vérifier la redirection', async () => {
        await page.locator('.category-card').first().click();
        await expect(page).toHaveURL(/\/boutique\/\d+/);
        await expect(page.locator('.products-grid')).toBeVisible();
    });
});
```

### Documentation

#### Documentation à mettre à jour

**Fichier :** `README.md`

Remplacer la section "Page d'accueil et top produits" (lignes 71-88) par une section mise à jour qui décrit la page d'accueil complète :

```markdown
## Page d'accueil

La page d'accueil est organisée en trois sections dans cet ordre :

1. **Texte de bienvenue** : Message de présentation de la boutique
2. **Top 3 produits** : Les 3 produits les plus commandés (via contrôleur imbriqué)
3. **Catégories** : Liste des catégories avec le nombre de produits associés

### Texte de bienvenue

```
Bienvenue chez Fruits & Veggies Shop, votre primeur et épicerie fine grenobloise !
Nous sommes ravis de vous accueillir pour vous faire découvrir notre sélection de produits frais d'exception.
```

### Top produits

Les 3 produits les plus commandés sont affichés via un **contrôleur imbriqué** (Embedded Controller).

- `App\Controller\TopProductsController::topProducts()` interroge `ProductRepository::findTopMostOrdered(3)`
- La requête DQL agrège les quantités des `OrderLine` pour déterminer les produits les plus populaires
- Le contrôleur n'a pas de route dédiée : il est appelé uniquement via `render(controller(...))` dans `templates/home/index.html.twig`
- En l'absence de commandes, la section "Top produits" est masquée

```twig
{{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
```

### Catégories

Les catégories de produits sont listées avec le nombre de produits associés :

- Chaque catégorie affiche son nom, sa description et le nombre de produits (ex: "Fruits (5)")
- Les catégories sont triées par ordre alphabétique
- Le clic sur une catégorie redirige vers la liste de ses produits (`/boutique/{id}`)
- En l'absence de catégories, un message informatif est affiché
```

### Exemples d'utilisation

Fichier : `docs/tasks/story-014/task-003-examples.http`

```http
### Page d'accueil
GET http://localhost:8000/
```
