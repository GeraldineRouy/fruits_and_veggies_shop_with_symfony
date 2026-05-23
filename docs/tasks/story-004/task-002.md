# Tâche #002 - Story #004 : ShopController, HomeController et templates du catalogue

## Objectif
Créer le `ShopController` avec les routes du catalogue, mettre à jour le `HomeController` pour lister les catégories sur la page d'accueil, et créer les templates Twig associés (liste des produits par catégorie, fiche détaillée produit, liste des catégories).

## Contexte
- Story #004 : [Catalogue produits et navigation](../../stories/story-004.md)
- Dépend de : Tâche #001 (ProductRepository + CategoryRepository avec pagination)
- Nécessaire pour : Tâche #003 (Tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Le catalogue doit permettre la navigation suivante :
1. Page d'accueil (`/`) : liste les catégories disponibles avec un lien vers chacune
2. Page catégorie (`/boutique/{id}`) : liste paginée des produits de la catégorie (nom, image, prix)
3. Page produit (`/boutique/produit/{id}`) : fiche détaillée du produit (nom, description, image, prix, catégories)

**Cas nominaux :**
- GET `/` : affiche la liste des catégories (avec `CategoryRepository::findAllOrdered()`)
- GET `/boutique/{id}` : affiche les produits de la catégorie, paginés par 12, triés par nom
- GET `/boutique/produit/{id}` : affiche la fiche détaillée du produit
- La barre de navigation contient un lien "Boutique" vers la page d'accueil (où les catégories sont listées)
- La pagination affiche "Page X sur Y" avec les liens Précédent/Suivant

**Cas limites :**
- GET `/boutique/{id}` avec un id de catégorie inexistant → HTTP 404
- GET `/boutique/produit/{id}` avec un id de produit inexistant → HTTP 404
- Catégorie sans produit → page vide avec message "Aucun produit dans cette catégorie pour le moment."
- Pagination : page 1 = pas de lien "Précédent", dernière page = pas de lien "Suivant"
- Pas de route `/boutique` sans id dédiée — les catégories sont listées sur la page d'accueil (`/`)

**Gestion d'erreurs :**
- Catégorie introuvable → `throw $this->createNotFoundException('Catégorie introuvable.')`
- Produit introuvable → `throw $this->createNotFoundException('Produit introuvable.')`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/ShopController.php` | Créer | Controller du catalogue (catégories, produits) |
| `src/Controller/HomeController.php` | Modifier | Ajouter la liste des catégories à la page d'accueil |
| `templates/home/index.html.twig` | Modifier | Ajouter la section des catégories sur la page d'accueil |
| `templates/shop/products.html.twig` | Créer | Liste paginée des produits d'une catégorie |
| `templates/shop/product.html.twig` | Créer | Fiche détaillée d'un produit |
| `templates/base.html.twig` | Modifier | Ajouter le lien "Boutique" dans la navigation |
| `templates/shop/_pagination.html.twig` | Créer | Partial de pagination réutilisable |
| `src/Service/PaginationService.php` | Créer | Service de pagination réutilisable (métadonnées : pages totales, page courante, etc.) |

### Signatures

```php
namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    #[Route('/boutique/{id}', name: 'app_shop_category', requirements: ['id' => '\d+'])]
    public function category(
        Category $category,
        Request $request,
        ProductRepository $productRepository,
        PaginationService $paginationService
    ): Response;

    #[Route('/boutique/produit/{id}', name: 'app_shop_product', requirements: ['id' => '\d+'])]
    public function product(Product $product): Response;
}
```

```php
// Modifications dans HomeController
namespace App\Controller;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response;
}
```

```php
namespace App\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginationService
{
    /**
     * Calcule les métadonnées de pagination pour un Paginator.
     *
     * @param Paginator $paginator Le Paginator Doctrine (déjà configuré avec setFirstResult/setMaxResults)
     * @param int $page Numéro de la page courante (1-indexed)
     * @param int $limit Nombre d'éléments par page
     * @return array{
     *     items: iterable,
     *     totalItems: int,
     *     totalPages: int,
     *     currentPage: int,
     *     limit: int,
     *     hasPrevious: bool,
     *     hasNext: bool
     * }
     */
    public function paginate(
        Paginator $paginator,
        int $page,
        int $limit = 12
    ): array;
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0, Twig 3.x, AssetMapper (pas de Webpack/Encore)
- **Routing** : Utiliser les attributs `#[Route]` (comme les contrôleurs existants). Les routes sont auto-découvertes via `config/routes.yaml`.
- **ParamConverter implicite** : Utiliser le ParamConverter automatique de Symfony dans les signatures (`Category $category`, `Product $product`) — Symfony résout automatiquement l'entité depuis l'ID dans l'URL. Pas besoin de repository dans l'action pour la résolution.
- **Pagination** : Utiliser `PaginationService::paginate()` dans `ShopController::category()` pour calculer les métadonnées (`totalPages`, `hasPrevious`, `hasNext`) à partir du `Paginator` retourné par le repository. Lire le paramètre `page` depuis la query string via `$request->query->getInt('page', 1)`.
- **Images** : Les images des produits sont stockées en base sous forme de chemin (string). Dans le template, utiliser `asset(product.image)` si le chemin est relatif, ou directement l'URL si c'est une URL absolue. Les images doivent avoir un `alt` text basé sur le nom du produit.
- **Prix** : Le champ `price` est de type `decimal(10,2)` et retourné comme `string` par Doctrine. Dans Twig, utiliser le filtre `|format_currency('EUR')` pour l'affichage (Symfony intl est installé).
- **Accessibilité** : Ajouter des attributs `aria-label` sur les liens de navigation pour l'accessibilité.
- **Conventions du projet** : Respecter le code style PSR-12, `declare(strict_types=1)`, injection via constructeur pour les repositories.

### Templates Twig

#### `templates/home/index.html.twig` (modification)

Ajouter une section "Nos catégories" après le titre existant :

```twig
<section class="categories">
    <h2>Nos catégories</h2>
    <div class="categories-grid">
        {% for category in categories %}
            <a href="{{ path('app_shop_category', { id: category.id }) }}" class="category-card">
                <h3>{{ category.name }}</h3>
                <p>{{ category.description }}</p>
            </a>
        {% endfor %}
    </div>
</section>
```

#### `templates/shop/products.html.twig`

- Étendre `base.html.twig`
- Afficher le nom de la catégorie comme titre de page
- Afficher une grille de produits (nom, image, prix) — chaque produit est un lien vers `app_shop_product`
- Inclure `shop/_pagination.html.twig` pour la pagination
- Si pas de produits : message "Aucun produit dans cette catégorie pour le moment."

```twig
{% extends 'base.html.twig' %}

{% block title %}Boutique - {{ category.name }} - Fruits & Veggies{% endblock %}

{% block body %}
    <h1>{{ category.name }}</h1>
    <p>{{ category.description }}</p>

    {% if pagination.items|length > 0 %}
        <div class="products-grid">
            {% for product in pagination.items %}
                <a href="{{ path('app_shop_product', { id: product.id }) }}" class="product-card">
                    <img src="{{ asset(product.image) }}" alt="{{ product.name }}" loading="lazy">
                    <h3>{{ product.name }}</h3>
                    <p class="price">{{ product.price|format_currency('EUR') }}</p>
                </a>
            {% endfor %}
        </div>

        {% include 'shop/_pagination.html.twig' with {
            pagination: pagination,
            categoryId: category.id
        } %}
    {% else %}
        <p>Aucun produit dans cette catégorie pour le moment.</p>
    {% endif %}
{% endblock %}
```

#### `templates/shop/product.html.twig`

- Étendre `base.html.twig`
- Afficher l'image du produit (grand format)
- Afficher le nom, la description, le prix, les catégories associées
- Lien retour vers la catégorie
- Bouton "Ajouter au panier" (placeholder — sera activé dans Story #005)

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ product.name }} - Fruits & Veggies{% endblock %}

{% block body %}
    <article class="product-detail">
        <img src="{{ asset(product.image) }}" alt="{{ product.name }}" class="product-detail__image">
        <div class="product-detail__info">
            <h1>{{ product.name }}</h1>
            <p class="price">{{ product.price|format_currency('EUR') }}</p>
            <p>{{ product.description }}</p>
            <p class="categories">
                Catégories :
                {% for category in product.categories %}
                    <a href="{{ path('app_shop_category', { id: category.id }) }}">{{ category.name }}</a>
                    {%- if not loop.last %}, {% endif %}
                {% endfor %}
            </p>
            <button class="btn" disabled title="Bientôt disponible">Ajouter au panier</button>
            <a href="{{ path('app_shop_category', { id: product.categories.first.id }) }}" class="back-link">← Retour à la catégorie</a>
        </div>
    </article>
{% endblock %}
```

#### `templates/shop/_pagination.html.twig`

```twig
{% if pagination.totalPages > 1 %}
    <nav class="pagination" aria-label="Pagination">
        {% if pagination.hasPrevious %}
            <a href="{{ path('app_shop_category', { id: categoryId, page: pagination.currentPage - 1 }) }}" class="pagination__prev" aria-label="Page précédente">← Précédent</a>
        {% endif %}

        <span class="pagination__info">Page {{ pagination.currentPage }} sur {{ pagination.totalPages }}</span>

        {% if pagination.hasNext %}
            <a href="{{ path('app_shop_category', { id: categoryId, page: pagination.currentPage + 1 }) }}" class="pagination__next" aria-label="Page suivante">Suivant →</a>
        {% endif %}
    </nav>
{% endif %}
```

#### `templates/base.html.twig` (modification)

Ajouter dans la barre de navigation (`<nav>`) le lien vers le catalogue :

```twig
<a href="{{ path('app_home') }}">Accueil</a>
<a href="{{ path('app_home') }}">Boutique</a>  {# ← AJOUTER #}
```

Note : Le lien "Boutique" pointe vers l'accueil, qui liste les catégories. Pas de route `/boutique` dédiée.

### Tests à implémenter

Les tests de cette tâche sont écrits dans la Tâche #003.

### Documentation

Aucune documentation spécifique pour cette tâche (documentation des routes dans la Tâche #003).
