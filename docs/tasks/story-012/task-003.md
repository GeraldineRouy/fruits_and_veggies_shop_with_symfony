# Tâche #003 - Story #012 : Produits et catégories - Cartes stylisées avec Tailwind

## Objectif
Styliser les cartes produits (listing, détail, top produits) et les cartes catégories avec Tailwind CSS : ombres, coins arrondis, espacement, animations au survol, grille responsive.

## Contexte
- Story #012 : `docs/stories/story-012.md`
- Dépend de : Tâche #001 (intégration CDN Tailwind)
- Nécessaire pour : Aucune

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Grille de produits (`templates/shop/products.html.twig`)
Transformer la grille de produits pour utiliser les classes Tailwind :

**Avant (HTML existant) :**
```twig
<div class="products-grid">
    <div class="product-card">
        <a href="...">
            <img src="..." alt="..." loading="lazy">
            <h3>...</h3>
            <p class="price">...</p>
        </a>
        <form method="post" action="..." class="add-to-cart-list">
            ...
        </form>
    </div>
</div>
```

**Après (avec Tailwind) :**
```twig
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    {% for product in pagination.items %}
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden flex flex-col">
            <a href="{{ path('app_shop_product', { id: product.id }) }}" class="block">
                <div class="aspect-w-4 aspect-h-3 bg-gray-100">
                    <img src="{{ asset(product.image) }}" alt="{{ product.name }}" loading="lazy" class="w-full h-48 object-cover">
                </div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ product.name }}</h3>
                    <p class="text-xl font-bold text-brand-600">{{ product.price|format_currency('EUR') }}</p>
                </div>
            </a>
            {% if app.user %}
                <div class="px-4 pb-4 mt-auto">
                    <form method="post" action="{{ path('app_cart_add', { id: product.id }) }}">
                        <input type="hidden" name="add_to_cart[_token]" value="{{ csrf_token('add_to_cart') }}">
                        <input type="hidden" name="add_to_cart[quantity]" value="1">
                        <button type="submit" class="w-full bg-brand-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                            Ajouter au panier
                        </button>
                    </form>
                </div>
            {% endif %}
        </div>
    {% endfor %}
</div>
```

#### 2. Fiche détail produit (`templates/shop/product.html.twig`)
Transformer la page détail produit avec un layout 2 colonnes sur desktop :

```twig
<article class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="md:flex">
        <div class="md:w-1/2">
            <div class="aspect-w-1 aspect-h-1 bg-gray-100">
                <img src="{{ asset(product.image) }}" alt="{{ product.name }}" class="w-full h-96 object-cover">
            </div>
        </div>
        <div class="md:w-1/2 p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ product.name }}</h1>
            <p class="text-3xl font-bold text-brand-600 mb-6">{{ product.price|format_currency('EUR') }}</p>
            <p class="text-gray-600 mb-4 leading-relaxed">{{ product.description }}</p>
            <p class="text-sm text-gray-500 mb-6">
                Catégories :
                {% for category in product.categories %}
                    <a href="{{ path('app_shop_category', { id: category.id }) }}" class="text-brand-600 hover:text-brand-800 underline">{{ category.name }}</a>
                    {%- if not loop.last %}, {% endif %}
                {% endfor %}
            </p>
            {% if app.user %}
                <form method="post" action="{{ path('app_cart_add', { id: product.id }) }}" class="flex items-end space-x-4 mb-6">
                    <input type="hidden" name="add_to_cart[_token]" value="{{ csrf_token('add_to_cart') }}">
                    <div>
                        <label for="add_to_cart_quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantité :</label>
                        <input type="number" id="add_to_cart_quantity" name="add_to_cart[quantity]" value="1" min="1" class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                        Ajouter au panier
                    </button>
                </form>
            {% else %}
                <a href="{{ path('app_login') }}" class="inline-block bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition-colors mb-6">
                    Connectez-vous pour acheter
                </a>
            {% endif %}
            <div class="pt-4 border-t border-gray-200">
                <a href="{{ path('app_shop_category', { id: product.categories.first.id }) }}" class="text-brand-600 hover:text-brand-800 transition-colors">
                    &larr; Retour à la catégorie
                </a>
            </div>
        </div>
    </div>
</article>
```

#### 3. Top produits (`templates/home/_top_products.html.twig`)
Styliser les cartes du top 3 avec des badges de classement.

**Correction de chemin d'image :** Remplacer `asset('images/' ~ product.image)` par `asset(product.image)` pour être cohérent avec les autres templates et éviter la duplication de chemin (`product.image` contient déjà `images/products/xxx.jpg`, donc `asset('images/' ~ product.image)` génère un chemin invalide `images/images/products/xxx.jpg`).

```twig
{% if topProducts is not empty %}
    <section class="mt-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Top produits</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {% for product in topProducts %}
                <a href="{{ path('app_shop_product', { id: product.id }) }}" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden group">
                    <div class="relative">
                        <img src="{{ asset(product.image) }}" alt="{{ product.name }}" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute top-2 left-2 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full w-8 h-8 flex items-center justify-center shadow-md">
                            {{ loop.index }}
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ product.name }}</h3>
                        <p class="text-xl font-bold text-brand-600 mt-1">{{ product.price }} €</p>
                    </div>
                </a>
            {% endfor %}
        </div>
    </section>
{% endif %}
```

#### 4. Cartes catégories (`templates/home/index.html.twig`)
Styliser la grille des catégories sur la page d'accueil :

```twig
<section class="mb-12">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Nos produits par catégories</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {% for category in categories %}
            <a href="{{ path('app_shop_category', { id: category.id }) }}" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-100 hover:border-brand-200">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ category.name }}</h3>
                <p class="text-gray-600 text-sm">{{ category.description }}</p>
            </a>
        {% endfor %}
    </div>
</section>
```

#### 5. Page d'accueil - Structure globale (`templates/home/index.html.twig`)
Ajouter les classes Tailwind de structure :

```twig
{% extends 'base.html.twig' %}

{% block title %}Accueil - Fruits & Veggies{% endblock %}

{% block body %}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Bienvenue chez Fruits &amp; Veggies</h1>
        <p class="text-xl text-gray-600">Votre magasin de fruits et légumes frais en ligne.</p>
    </div>

    <section class="mb-12">
        {# categories grid #}
    </section>

    {{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
{% endblock %}
```

#### 6. Pagination (`templates/shop/_pagination.html.twig`)
Styliser la pagination avec Tailwind :

```twig
{% if pagination.totalPages > 1 %}
    <nav class="flex items-center justify-center space-x-4 mt-8" aria-label="Pagination">
        {% if pagination.hasPrevious %}
            <a href="{{ path('app_shop_category', { id: categoryId, page: pagination.currentPage - 1 }) }}" class="px-4 py-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow text-gray-700" aria-label="Page précédente">
                &larr; Précédent
            </a>
        {% endif %}

        <span class="text-gray-600 text-sm">Page {{ pagination.currentPage }} sur {{ pagination.totalPages }}</span>

        {% if pagination.hasNext %}
            <a href="{{ path('app_shop_category', { id: categoryId, page: pagination.currentPage + 1 }) }}" class="px-4 py-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow text-gray-700" aria-label="Page suivante">
                Suivant &rarr;
            </a>
        {% endif %}
    </nav>
{% endif %}
```

**Cas nominaux :**
- Grille de produits : 1 colonne mobile, 2 colonnes sm, 3 colonnes lg, 4 colonnes xl avec `gap-6`
- Chaque carte produit a : fond blanc, coins arrondis (`rounded-xl`), ombre (`shadow-md`), ombre plus marquée au survol (`hover:shadow-lg`)
- Image produit : `object-cover`, hauteur fixe `h-48`, transition zoom au survol
- Prix : en vert brand (`text-brand-600`), taille `text-xl` ou `text-3xl` selon contexte
- Bouton "Ajouter au panier" : fond brand, hover plus foncé, largeur complète dans la carte
- Cartes catégories : fond blanc, bordure subtile, bordure colorée au survol
- Top produits : badge numéroté jaune dans le coin supérieur gauche

**Cas limites :**
- Produit sans image : utiliser `bg-gray-100` comme fond de remplacement dans le conteneur d'image
- Produit avec nom très long : le texte doit passer à la ligne proprement (`word-break` géré par Tailwind)
- Grille avec 1 ou 2 produits seulement : la grille s'adapte automatiquement
- Pagination masquée quand `totalPages <= 1`

**Gestion d'erreurs :**
- Image manquante : l'attribut `loading="lazy"` évite le chargement bloquant, la classe `bg-gray-100` sert de placeholder
- Prix null ou invalide : le filtre Twig `format_currency('EUR')` gère déjà les cas d'erreur

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/home/index.html.twig` | Modifier | Styliser la page d'accueil, la grille de catégories et le titre |
| `templates/home/_top_products.html.twig` | Modifier | Styliser les cartes top produits avec badges |
| `templates/shop/products.html.twig` | Modifier | Styliser la grille et les cartes produits |
| `templates/shop/product.html.twig` | Modifier | Styliser la fiche détail produit (layout 2 colonnes) |
| `templates/shop/_pagination.html.twig` | Modifier | Styliser la pagination |

### Contraintes techniques
- **Framework** : Twig 3.x, Symfony 8.0
- **Tailwind** : Classes uniquement (pas de CSS personnalisé)
- **Images** : Utiliser `aspect-w-4 aspect-h-3` ou hauteur fixe `h-48` avec `object-cover`
- **Grille** : `grid` Tailwind, responsive breakpoints
- **Animations** : Transitions Tailwind (`transition-shadow`, `transition-transform`, `transition-colors`, `duration-300`)
- **Accessibilité** : Images avec `alt` text, `aria-label` sur la pagination, `loading="lazy"` sur les images de liste (pas sur la page détail)

### Tests à implémenter
Aucun test direct pour cette tâche — les tests sont dans la Tâche #006.

### Documentation
Aucune documentation directe pour cette tâche.
