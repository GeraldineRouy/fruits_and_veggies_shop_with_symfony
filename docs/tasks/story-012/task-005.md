# Tâche #005 - Story #012 : Style Tailwind cohérent sur toutes les pages restantes

## Objectif
Appliquer un style Tailwind cohérent sur toutes les pages du site qui n'ont pas encore été traitées dans les tâches précédentes : pages d'authentification, panier, profil, administration, et composants réutilisables (alertes, boutons, tableaux, formulaires, badges).

## Contexte
- Story #012 : `docs/stories/story-012.md`
- Dépend de : Tâche #001 (intégration CDN), Tâche #002 (header/footer), Tâche #003 (produits/catégories)
- Nécessaire pour : Aucune

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

> **Décisions de conception :**
> - Les classes CSS existantes (`.btn`, `.alert`, `.badge`, `.product-card`, `top-products`, etc.) sont **conservées** en plus des classes Tailwind — rétrocompatibilité des tests existants
> - Les messages flash sont centralisés dans le layout de base (Tâche #001) — **supprimer les blocs flash individuels** dans chaque page
> - Un thème de formulaire Tailwind (`tailwind_2_layout`) est activé dans `config/packages/twig.yaml`
> - Les badges de catégorie dans la liste admin utilisent des couleurs par catégorie

#### 1. Pages d'authentification (login, register, forgot/reset password, check email)
Transformer toutes les pages d'auth pour utiliser des cartes centrées avec Tailwind.

**Supprimer les blocs flash individuels** dans toutes les pages d'auth — les flashs sont désormais gérés par le layout de base.

**Patron commun pour toutes les pages d'auth :**
```twig
{% extends 'base.html.twig' %}

{% block title %}...{% endblock %}

{% block body %}
<div class="max-w-md mx-auto mt-10">
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">Titre</h1>

        {# Flash messages #}
        {% for label, messages in app.flashes(['error']) %}
            {% for message in messages %}
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">{{ message }}</div>
            {% endfor %}
        {% endfor %}

        {# Form #}
        <form action="..." method="post">
            {# form fields #}
            <button type="submit" class="w-full bg-brand-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                Bouton
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            <a href="..." class="text-brand-600 hover:text-brand-800">Lien</a>
        </p>
    </div>
</div>
{% endblock %}
```

**Fichiers à modifier avec ce patron :**
- `templates/security/login.html.twig` — remplacer `login-container`, supprimer les flashs, remplacer `alert alert-error`, `form-group`, `btn btn-primary`, `login-links`
- `templates/security/register.html.twig` — remplacer `register-container`, supprimer les flashs, remplacer `alert`, `form-group`, `btn btn-primary`, `login-links`
- `templates/security/forgot_password.html.twig` — remplacer `forgot-password-container`, supprimer les flashs, remplacer `alert`, `form-group`, `btn btn-primary`, `login-links`
- `templates/security/reset_password.html.twig` — remplacer `reset-password-container`, supprimer les flashs, remplacer `alert`, `form-group`, `btn btn-primary`, `login-links`
- `templates/security/check_email.html.twig` — remplacer `check-email-container`, `btn btn-primary`, pas de flashs ici non plus

**Pour les formulaires Symfony (`form_start`, `form_row`, `form_end`) :**
Activer le thème de formulaire Tailwind intégré à Symfony (`tailwind_2_layout`) dans `config/packages/twig.yaml` :

```yaml
twig:
    form_themes: ['tailwind_2_layout']
```

Ce thème est disponible via `symfony/twig-bridge` (déjà installé). Il applique des classes Tailwind aux champs de formulaire (input, select, textarea, labels, erreurs). Pour les boutons, conserver les classes Tailwind manuelles (`bg-brand-600 text-white ...`).

```twig
{# Exemple pour register — PAS de blocs flash ici, gérés par le layout #}
<div class="max-w-md mx-auto mt-10">
    <div class="bg-white rounded-xl shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">Inscription</h1>

        {{ form_start(form, {attr: {class: 'space-y-4'}}) }}
            {{ form_row(form.email) }}
            {{ form_row(form.firstName) }}
            {{ form_row(form.lastName) }}
            {{ form_row(form.plainPassword) }}

            <button type="submit" class="w-full bg-brand-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                Créer mon compte
            </button>
        {{ form_end(form) }}

        <p class="mt-4 text-center text-sm text-gray-600">
            <a href="{{ path('app_login') }}" class="text-brand-600 hover:text-brand-800">Déjà un compte ? Se connecter</a>
        </p>
    </div>
</div>
```

#### 2. Page panier (`templates/cart/index.html.twig` et `_product_row.html.twig`)
Styliser la page panier avec Tailwind :

```twig
{% block body %}
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Mon panier</h1>

    {% if items|length > 0 %}
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Produit</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Prix unitaire</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Quantité</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Total</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    {% for item in items %}
                        {% include 'cart/_product_row.html.twig' with { item: item } %}
                    {% endfor %}
                </tbody>
            </table>

            <div class="p-6 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <p class="text-xl font-bold text-gray-800">Total : <span class="text-brand-600">{{ total|format_currency('EUR') }}</span></p>
                <div class="flex space-x-3">
                    <form method="post" action="{{ path('app_order_checkout') }}">
                        <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                            Valider la commande
                        </button>
                    </form>
                    <form method="post" action="{{ path('app_cart_clear') }}" onsubmit="return confirm('Vider le panier ?');">
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-600 transition-colors">
                            Vider le panier
                        </button>
                    </form>
                </div>
            </div>
        </div>
    {% else %}
        <div class="text-center py-12">
            <p class="text-gray-600 mb-4">Votre panier est vide.</p>
            <a href="{{ path('app_home') }}" class="inline-block bg-brand-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                Découvrir nos produits
            </a>
        </div>
    {% endif %}
{% endblock %}
```

**`templates/cart/_product_row.html.twig` :**
Remplacer le `<tr>` existant pour utiliser les classes Tailwind :

```twig
{% set product = item.product %}
{% set subtotal = (item.price * item.quantity)|number_format(2, '.', '') %}

<tr class="hover:bg-gray-50 transition-colors">
    <td class="px-4 py-3">
        <div class="flex items-center space-x-3">
            <img src="{{ asset(product.image) }}" alt="{{ product.name }}" width="60" height="60" class="rounded-lg object-cover w-16 h-16">
            <a href="{{ path('app_shop_product', { id: product.id }) }}" class="text-brand-600 hover:text-brand-800 font-medium">{{ product.name }}</a>
        </div>
    </td>
    <td class="px-4 py-3 text-gray-700">{{ item.price|format_currency('EUR') }}</td>
    <td class="px-4 py-3">
        <form method="post" action="{{ path('app_cart_update', { id: item.id }) }}" class="flex items-center space-x-2">
            <input type="hidden" name="update_cart_item[_token]" value="{{ csrf_token('update_cart_item') }}">
            <input type="number" name="update_cart_item[quantity]" value="{{ item.quantity }}" min="0" class="w-16 px-2 py-1 border border-gray-300 rounded-lg text-center focus:ring-brand-500 focus:border-brand-500">
            <button type="submit" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">OK</button>
        </form>
    </td>
    <td class="px-4 py-3 font-semibold text-gray-800">{{ subtotal|format_currency('EUR') }}</td>
    <td class="px-4 py-3">
        <form method="post" action="{{ path('app_cart_remove', { id: item.id }) }}">
            <button type="submit" class="px-3 py-1 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm">Supprimer</button>
        </form>
    </td>
</tr>
```

#### 3. Pages profil/commandes (`templates/profile/orders.html.twig`, `order.html.twig`)

**Liste des commandes :** Utiliser la même structure que la page panier (table avec `bg-white rounded-xl shadow-md overflow-hidden`).

**Détail commande :** Carte classique avec en-tête (statut avec badge coloré) et tableau des lignes.

```twig
{# Extraits de order.html.twig #}
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Commande #{{ order.id }}</h1>
                <p class="text-gray-600 text-sm mt-1">Date : {{ order.orderedAt|date('d/m/Y H:i') }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-semibold
                {% if order.status.value == 'confirmed' %}bg-yellow-100 text-yellow-800
                {% elseif order.status.value == 'preparing' %}bg-blue-100 text-blue-800
                {% elseif order.status.value == 'shipped' %}bg-purple-100 text-purple-800
                {% elseif order.status.value == 'delivered' %}bg-green-100 text-green-800
                {% elseif order.status.value == 'cancelled' %}bg-red-100 text-red-800
                {% endif %}">
                {{ status_label }}
            </span>
        </div>
        {# ... table ... #}
    </div>
</div>
```

**Remplacer les classes** `badge badge-<status>` dans `orders.html.twig` et `order.html.twig` par des classes Tailwind conditionnelles comme ci-dessus.

#### 4. Badges de catégories dans admin produits
Dans `templates/admin/products.html.twig`, remplacer le badge gris générique par des badges colorés selon la catégorie :

```twig
{% for category in product.categories %}
    {% set catColors = {
        'Fruits': 'bg-green-100 text-green-800',
        'Légumes': 'bg-orange-100 text-orange-800',
        'Fruits exotiques': 'bg-yellow-100 text-yellow-800',
        'Herbes aromatiques': 'bg-purple-100 text-purple-800',
        'Produits locaux d\'exception': 'bg-blue-100 text-blue-800',
    } %}
    {% set colorClass = catColors[category.name] ?? 'bg-gray-100 text-gray-700' %}
    <span class="inline-block px-2 py-1 rounded text-xs font-medium {{ colorClass }}">{{ category.name }}</span>
    {%- if not loop.last %}, {% endif %}
{% else %}
    <span class="text-gray-400 text-sm">—</span>
{% endfor %}
```

#### 5. Pages administration (`templates/admin/*.html.twig`)

**Dashboard (`dashboard.html.twig`) :**
```twig
{% block body %}
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard Administration</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ path('app_admin_orders') }}" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Commandes</h2>
            <p class="text-gray-600 text-sm">Gérer les commandes clients</p>
        </a>
        {# ... autres cartes ... #}
    </div>
{% endblock %}
```

**Pages CRUD (`products.html.twig`, `categories.html.twig`, `users.html.twig`, `orders.html.twig`, `order.html.twig`, `product_form.html.twig`, `category_form.html.twig`) :**
- Titre : `text-2xl font-bold text-gray-800 mb-6`
- Bouton "Nouveau" : `bg-brand-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors inline-block mb-4`
- Tableau : `min-w-full bg-white rounded-xl shadow-md overflow-hidden`
- Thead : `bg-gray-50 border-b border-gray-200`
- Th : `text-left px-4 py-3 text-sm font-semibold text-gray-600`
- Td : `px-4 py-3 text-gray-700`
- Pagination admin : `flex items-center justify-center space-x-4 mt-6 text-gray-600 text-sm`
- Badges (statut, actif/inactif) : utiliser des classes conditionnelles Tailwind

#### 6. Alertes flash centralisées
Remplacer la classe `alert` par des classes Tailwind. Dans `base.html.twig`, le bloc flash :

```twig
{% for label, messages in app.flashes %}
    {% for message in messages %}
        <div class="px-4 py-3 rounded-lg mb-4 text-sm
            {% if label == 'error' %}bg-red-50 border border-red-200 text-red-700
            {% elseif label == 'success' %}bg-green-50 border border-green-200 text-green-700
            {% elseif label == 'info' %}bg-blue-50 border border-blue-200 text-blue-700
            {% elseif label == 'warning' %}bg-yellow-50 border border-yellow-200 text-yellow-700
            {% else %}bg-gray-50 border border-gray-200 text-gray-700
            {% endif %}">
            {{ message }}
        </div>
    {% endfor %}
{% endfor %}
```

**Cas nominaux :**
- Toutes les pages d'authentification utilisent le même patron de carte centrée
- La page panier a un design cohérent avec le reste du site
- Les pages profil/commandes ont un style uniforme
- Le dashboard admin utilise une grille de cartes responsive
- Les pages CRUD admin ont des tableaux stylisés et des boutons cohérents
- Les badges de statut sont colorés (yellow=confirmé, blue=préparation, purple=expédié, green=livré, red=annulé)

**Cas limites :**
- Flash message sans label reconnu : utiliser le style gris par défaut
- Tableau vide : le message "Aucun résultat" est centré
- Statut de commande inconnu : badge gris par défaut
- Utilisateur non modifiable dans la liste admin : texte en gris clair

**Gestion d'erreurs :**
- Conserver les classes originales (`alert`, `badge`, `btn`) EN PLUS des classes Tailwind pour la rétrocompatibilité avec les tests qui vérifient ces sélecteurs. Ajouter `class="... les classes Tailwind ... ancienne-classe"` pour ne pas casser les tests existants.

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/security/login.html.twig` | Modifier | Style Tailwind centré, supprimer flashs |
| `templates/security/register.html.twig` | Modifier | Style Tailwind centré, supprimer flashs |
| `templates/security/forgot_password.html.twig` | Modifier | Style Tailwind centré, supprimer flashs |
| `templates/security/reset_password.html.twig` | Modifier | Style Tailwind centré, supprimer flashs |
| `templates/security/check_email.html.twig` | Modifier | Style Tailwind centré |
| `templates/cart/index.html.twig` | Modifier | Style Tailwind pour tableau et résumé |
| `templates/cart/_product_row.html.twig` | Modifier | Style Tailwind pour ligne de panier |
| `templates/profile/orders.html.twig` | Modifier | Style Tailwind pour tableau et badges |
| `templates/profile/order.html.twig` | Modifier | Style Tailwind pour détail commande |
| `templates/admin/dashboard.html.twig` | Modifier | Style Tailwind pour grille de cartes |
| `templates/admin/products.html.twig` | Modifier | Style Tailwind + badges colorés par catégorie |
| `templates/admin/product_form.html.twig` | Modifier | Style Tailwind pour formulaire |
| `templates/admin/categories.html.twig` | Modifier | Style Tailwind pour CRUD catégories |
| `templates/admin/category_form.html.twig` | Modifier | Style Tailwind pour formulaire |
| `templates/admin/users.html.twig` | Modifier | Style Tailwind pour CRUD utilisateurs |
| `templates/admin/orders.html.twig` | Modifier | Style Tailwind pour liste commandes |
| `templates/admin/order.html.twig` | Modifier | Style Tailwind pour détail commande admin |
| `templates/base.html.twig` (bloc flash) | Modifier | Style Tailwind pour messages flash |
| `config/packages/twig.yaml` | Modifier | Ajouter `form_themes: ['tailwind_2_layout']` |

### Contraintes techniques
- **Framework** : Twig 3.x, Symfony 8.0
- **Tailwind** : Classes uniquement (pas de CSS personnalisé)
- **Rétrocompatibilité** : NE PAS supprimer les classes CSS existantes (ex: `alert`, `badge`, `btn`, `product-card`, `top-products`, `top-product-card`, etc.) — les AJOUTER aux classes Tailwind. Les tests existants (ex: `HomeControllerTest`) vérifient des sélecteurs CSS comme `.top-products`, `.top-product-card`.
- **Cohérence** : Utiliser les mêmes classes Tailwind sur toutes les pages pour un rendu homogène
- **Formulaires Symfony** : Le thème `tailwind_2_layout` est activé dans `twig.yaml` — utiliser `form_start(form, {attr: {class: '...'}})` pour ajouter des classes Tailwind supplémentaires au conteneur
- **Badges de statut** : Utiliser des classes conditionnelles Twig (`{% if %}...{% endif %}`) pour les couleurs de statut

### Tests à implémenter
Aucun test direct pour cette tâche — les tests sont dans la Tâche #006.

### Documentation
Aucune documentation directe pour cette tâche.
