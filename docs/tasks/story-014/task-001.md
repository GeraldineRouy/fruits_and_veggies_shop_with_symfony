# Tâche #001 - Story #014 : Texte de bienvenue et réorganisation de la page d'accueil

## Objectif
Modifier le template `home/index.html.twig` pour afficher le nouveau texte de bienvenue spécifié dans la story, et réorganiser l'ordre des sections sur la page d'accueil selon l'ordre : bienvenue → top produits → catégories.

## Contexte
- Story #014 : `docs/stories/story-014.md`
- Dépend de : Aucune (première tâche de la story)
- Nécessaire pour : Tâche #003 (tests et documentation)
- Template existant : `templates/home/index.html.twig` (contient déjà un texte de bienvenue, la section catégories, et le render du top produits dans le désordre)
- Le top produits est déjà intégré via `render(controller(...))` (Story #007)
- Les catégories sont déjà affichées avec `CategoryRepository::findAllOrdered()` (Story #004)
- Le style Tailwind est déjà intégré (Story #012)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Modifier la page d'accueil (`GET /`) pour qu'elle affiche dans cet ordre :
1. **Texte de bienvenue** : deux paragraphes avec le texte exact spécifié dans la story
2. **Top 3 produits** : section déjà existante via `render(controller(...))`
3. **Catégories de produits** : section déjà existante avec les catégories

**Cas nominaux :**
- Le texte de bienvenue correspond exactement à :
  - **Ligne 1** : "Bienvenue chez Fruits & Veggies Shop, votre primeur et épicerie fine grenobloise !"
  - **Ligne 2** : "Nous sommes ravis de vous accueillir pour vous faire découvrir notre sélection de produits frais d'exception."
- L'ordre des sections est : bienvenue → top produits → catégories
- Le design reste cohérent avec le thème Tailwind (Story #012)

**Cas limites :**
- Aucun top produit en base → la section top produits est masquée (déjà géré par TopProductsController)
- Aucune catégorie en base → la section des catégories affiche un message "Aucune catégorie disponible pour le moment."

**Gestion d'erreurs :**
- Si `categories` est vide → afficher un message informatif au lieu de la grille vide
- L'appel au contrôleur imbriqué ne doit pas bloquer l'affichage du reste de la page (déjà géré)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/home/index.html.twig` | Modifier | Remplacer le texte de bienvenue, réordonner les sections |

### Contenu attendu du template

```twig
{% extends 'base.html.twig' %}

{% block title %}Accueil - Fruits & Veggies{% endblock %}

{% block body %}
    <section class="welcome mb-12 text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Bienvenue chez Fruits &amp; Veggies Shop, votre primeur et épicerie fine grenobloise !</h1>
        <p class="text-xl text-gray-600">Nous sommes ravis de vous accueillir pour vous faire découvrir notre sélection de produits frais d'exception.</p>
    </section>

    {{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}

    <section class="categories mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Nos produits par catégories</h2>
        {% if categories is not empty %}
            <div class="categories-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {% for category in categories %}
                    <a href="{{ path('app_shop_category', { id: category.id }) }}" class="category-card bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-100 hover:border-brand-200">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ category.name }}</h3>
                        <p class="text-gray-600 text-sm">{{ category.description }}</p>
                    </a>
                {% endfor %}
            </div>
        {% else %}
            <p class="text-gray-500 text-center">Aucune catégorie disponible pour le moment.</p>
        {% endif %}
    </section>
{% endblock %}
```

**Changements par rapport à l'existant :**
- Remplacer le texte de bienvenue (les deux lignes) par le texte exact de la story
- Déplacer `{{ render(controller(...)) }}` **avant** la section catégories
- Ajouter la gestion du cas où `categories` est vide (condition `{% if categories is not empty %}`)
- Ajouter `mb-12` sur la section `.welcome` pour l'espacement

### Contraintes techniques
- **Framework** : Symfony 8.0, Twig 3.x
- **Texte** : Le texte de bienvenue doit être EXACTEMENT celui spécifié dans la story, caractère par caractère. Le "&" commercial doit être échappé en `&amp;` pour le HTML (déjà fait dans l'existant).
- **Style** : Conserver les classes Tailwind existantes. La section welcome utilise `text-center`, `text-4xl font-bold` pour le titre et `text-xl text-gray-600` pour le sous-titre (cohérent avec le style existant).
- **Ordre** : L'ordre doit strictement être : bienvenue → top produits → catégories
- **Accessibilité** : Conserver les attributs `aria-label` et la structure sémantique existante

### Tests à implémenter
Aucun test direct pour cette tâche — les tests d'intégration sont dans la Tâche #003.

### Documentation
Aucune documentation spécifique pour cette tâche.
