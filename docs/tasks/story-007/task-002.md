# Tâche #002 - Story #007 : Contrôleur imbriqué et intégration page d'accueil

## Objectif

Créer un contrôleur imbriqué dédié `TopProductsController` qui affiche les 3 produits les plus commandés via `render(controller(...))` dans la page d'accueil. Chaque produit est cliquable vers sa fiche détaillée.

## Contexte

- Story #007 : `docs/stories/story-007.md`
- Dépend de : Tâche #001 (méthode `findTopMostOrdered`)
- Contrôleurs existants : `HomeController` (page d'accueil), `ShopController` (catalogue)
- Template existant : `home/index.html.twig` (page d'accueil actuelle avec catégories)
- Routes existantes : `app_home` (/), `app_shop_product` (/boutique/produit/{id})
- Le `HomeController` injecte déjà `CategoryRepository` et affiche les catégories

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux :**
- La page d'accueil (`GET /`) affiche une section "Top produits" avec les 3 produits les plus commandés
- Chaque produit affiche : nom, image, prix
- Chaque produit est un lien cliquable vers sa fiche détaillée (`app_shop_product`)
- La section est rendue via un contrôleur imbriqué (Embedded Controller avec `render(controller(...))`)
- Si un produit est dans plusieurs catégories, il n'apparaît qu'une fois

**Cas limites :**
- Aucune commande en base → la section "Top produits" ne s'affiche pas
- Moins de 3 produits commandés → affiche uniquement ceux qui ont été commandés

**Gestion d'erreurs :**
- Si `findTopMostOrdered` retourne un tableau vide → le template partiel doit être rendu proprement (section masquée)
- L'appel au contrôleur imbriqué ne doit pas bloquer l'affichage du reste de la page

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/TopProductsController.php` | Créer | Nouveau contrôleur dédié pour l'embed |
| `templates/home/_top_products.html.twig` | Créer | Template partiel pour la section des top produits |
| `templates/home/index.html.twig` | Modifier | Ajouter `{{ render(controller(...)) }}` pour intégrer le contrôleur imbriqué |
| `tests/Controller/HomeControllerTest.php` | Modifier | Ajouter les tests d'intégration |

### Signatures

```php
namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TopProductsController extends AbstractController
{
    /**
     * Contrôleur imbriqué affichant les 3 produits les plus commandés.
     * Appelé via {{ render(controller(...)) }} dans le template de la page d'accueil.
     * Pas de route dédiée — accessible uniquement via render(controller(...)) en Twig.
     */
    public function topProducts(
        ProductRepository $productRepository,
    ): Response;
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0
- **Pattern Embedded Controller** : Utiliser `render(controller('App\\Controller\\TopProductsController::topProducts'))` dans le template Twig, PAS `include` ou `embed`. C'est une exigence fonctionnelle de la story.
- **Pas de route** : Le contrôleur imbriqué n'a PAS d'attribut `#[Route]`. Il est appelé uniquement via `render(controller(...))` en Twig. Symfony résout l'appel sans passer par le routeur.
- **Template partiel** : Créer un fichier séparé `home/_top_products.html.twig` (préfixé de `_` par convention pour les partiels). Ce template est rendu par le contrôleur imbriqué.
- **Navigation** : Chaque produit doit être cliquable via `path('app_shop_product', { id: product.id })`. La route `app_shop_product` existe déjà dans `ShopController`.
- **Design** : Suivre le style CSS existant. Ajouter une classe `.top-products` pour la section. Les produits peuvent être affichés dans une grille similaire à `.categories-grid` ou `.products-grid`.
- **Aucun cache** : Ne pas ajouter de cache. Les données sont mises à jour dynamiquement à chaque requête (exigence story : "pas de cache statique").
- **Ordre d'affichage** : Du plus commandé au moins commandé (l'ordre est déjà géré par `findTopMostOrdered`).

### Template attendu

```twig
{# templates/home/_top_products.html.twig #}
{% if topProducts is not empty %}
    <section class="top-products">
        <h2>Top produits</h2>
        <div class="top-products-grid">
            {% for product in topProducts %}
                <a href="{{ path('app_shop_product', { id: product.id }) }}" class="top-product-card">
                    <img src="{{ asset('images/' ~ product.image) }}" alt="{{ product.name }}">
                    <h3>{{ product.name }}</h3>
                    <p class="price">{{ product.price }} €</p>
                </a>
            {% endfor %}
        </div>
    </section>
{% endif %}
```

### Modification du template existant

Dans `templates/home/index.html.twig`, ajouter après la section des catégories :

```twig
{{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
```

Cette ligne doit être placée à l'intérieur du bloc `body`, après la section `.categories`.

### Tests à implémenter

Ajouter les tests DANS le fichier existant `tests/Controller/HomeControllerTest.php`.

Héritage de `WebTestCase`. Suivre le pattern existant avec `setUp()` qui nettoie les tables OrderLine, Order, Product, Category.

Ajouter des helpers privées pour créer catégories, produits et commandes :

```php
private function createCategory(string $name, string $description): Category;
private function createProduct(string $name, Category $category): Product;
private function createOrderWithProduct(Product $product, int $quantity): void;
```

#### Tests d'intégration

**Fichier** : `tests/Controller/HomeControllerTest.php`

- Scénario 1 : La page d'accueil inclut la section des top produits quand des commandes existent
  - Créer : 1 catégorie, 2 produits, 1 commande avec OrderLine (qty=5 pour P1, qty=3 pour P2)
  - Requête : `GET /`
  - Vérifier : le sélecteur `.top-products` existe, le sélecteur `.top-product-card` existe

- Scénario 2 : La section des top produits ne s'affiche pas quand aucune commande n'existe
  - Créer : 1 catégorie, 2 produits, aucune commande
  - Requête : `GET /`
  - Vérifier : le sélecteur `.top-products` n'existe PAS

- Scénario 3 : L'ordre des produits est correct (P1 plus commandé que P2)
  - Créer : P1 (qty=10), P2 (qty=3)
  - Requête : `GET /`
  - Vérifier que le HTML contient P1 avant P2 (vérifier l'ordre dans le contenu HTML)

### Documentation

Ajouter une section dans `README.md` (fichier à la racine du projet) décrivant le fonctionnement du contrôleur imbriqué pour les top produits.

### Exemples d'utilisation

```twig
{# Dans un template Twig (home/index.html.twig) : #}
{{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
```
