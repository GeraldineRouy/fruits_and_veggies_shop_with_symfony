# Tâche #001 - Story #012 : Intégration Tailwind CSS et restructuration du layout de base

## Objectif
Intégrer Tailwind CSS via CDN dans le layout Twig principal (`base.html.twig`) et restructurer la base HTML avec des balises sémantiques (`<header>`, `<main>`, `<footer>`) ainsi qu'un conteneur global pour préparer le stylage Tailwind.

## Contexte
- Story #012 : `docs/stories/story-012.md`
- Dépend de : Aucune (première tâche de la story)
- Nécessaire pour : Tâche #002, Tâche #003, Tâche #004, Tâche #005

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Ajouter le CDN Tailwind CSS
Dans `templates/base.html.twig`, ajouter la balise `<script>` CDN de Tailwind dans le `<head>`, **juste avant** la balise `{% block stylesheets %}` :

```html
<script src="https://cdn.tailwindcss.com"></script>
```

> **Important** : Le CDN doit être chargé avant le bloc `stylesheets` pour que Tailwind soit disponible avant le CSS personnalisé. Cela permet d'écraser les classes Tailwind si nécessaire via `app.css`.

#### 2. Configurer Tailwind via `tailwind.config` inline
Toujours dans `<head>`, juste après le CDN, ajouter une configuration inline pour personnaliser le thème :

```html
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#e8f5e9',
                        100: '#c8e6c9',
                        200: '#a5d6a7',
                        300: '#81c784',
                        400: '#66bb6a',
                        500: '#4caf50',
                        600: '#43a047',
                        700: '#388e3c',
                        800: '#2e7d32',
                        900: '#1b5e20',
                    }
                }
            }
        }
    }
</script>
```

#### 3. Restructurer le layout avec des balises sémantiques et conteneur Tailwind
Transformer le `<body>` de `base.html.twig` pour utiliser une structure sémantique avec Tailwind :

```html
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">
    <header>
        {% block header %}
            {# Le header sera détaillé dans la Tâche #002 #}
            {# Pour l'instant, garder le même contenu que le <nav> existant mais avec des classes Tailwind minimales #}
            <nav class="bg-brand-700 text-white shadow-md">
                {# ... contenu du nav existant ... #}
            </nav>
        {% endblock %}
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        {# Afficher les messages flash #}
        {% for label, messages in app.flashes %}
            {% for message in messages %}
                <div class="alert alert-{{ label }}">{{ message }}</div>
            {% endfor %}
        {% endfor %}

        {% block body %}{% endblock %}
    </main>

    <footer>
        {% block footer %}
            {# Le footer sera détaillé dans la Tâche #002 #}
            <div class="bg-brand-800 text-white text-center py-4">
                <p>&copy; {{ 'now'|date('Y') }} Fruits &amp; Veggies. Tous droits réservés.</p>
            </div>
        {% endblock %}
    </footer>
</body>
```

**Changements structuraux clés :**
- `<body>` : classes Tailwind pour fond (`bg-gray-50`), texte (`text-gray-800`), police (`font-sans`), anti-aliasing (`antialiased`), hauteur min (`min-h-screen`), flex column (`flex flex-col`)
- `<header>` encapsule la navbar
- `<main>` : `flex-grow` pour pousser le footer en bas, `container mx-auto px-4 py-8` pour le centrage
- `<footer>` positionné en bas
- Ajouter un bloc `{% block flash_messages %}` pour que les pages puissent surcharger l'affichage des flashs si nécessaire

#### 4. Supprimer le CSS superflu dans `assets/styles/app.css`
Remplacer le contenu de `assets/styles/app.css` par un contenu minimal, puisque Tailwind gère désormais tout le style :

```css
/* Les styles personnalisés éventuels peuvent être ajoutés ici.
   Tailwind CSS est chargé via CDN dans base.html.twig. */
```

**Ne pas supprimer le fichier**, car il est importé par `asset('styles/app.css')` dans le bloc `stylesheets` — on veut garder la possibilité d'ajouter des surcharges CSS.

#### 5. Gestion centralisée des messages flash
Le bloc d'affichage des messages flash dans `<main>` est l'unique point d'affichage des flashs. **Les pages individuelles n'auront pas leurs propres blocs flash** (les flashs seront retirés des templates de login, register, etc. dans la Tâche #005).

#### 5. Ajouter le bloc `stylesheets` étendu pour permettre l'ajout de CSS supplémentaire par page
Dans `base.html.twig`, s'assurer que le bloc `stylesheets` a cette forme :

```twig
{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('styles/app.css') }}">
{% endblock %}
```

**Cas nominaux :**
- Le CDN Tailwind est chargé sur toutes les pages
- Le layout utilise une structure sémantique (`<header>`, `<main>`, `<footer>`)
- La page s'affiche avec le fond gris clair et la police système
- Le footer reste en bas de page même avec peu de contenu
- Les classes Tailwind de base sont fonctionnelles

**Cas limites :**
- Page avec très peu de contenu : le footer doit rester en bas (flex column + flex-grow)
- Page avec beaucoup de contenu : le main s'agrandit normalement
- Les templates enfants qui ne définissent pas certains blocs reçoivent les valeurs par défaut
- Pas de régression : toutes les pages existantes continuent de s'afficher (les classes CSS personnalisées existantes continuent de fonctionner)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/base.html.twig` | Modifier | Ajouter CDN Tailwind, config, restructurer body avec balises sémantiques et classes Tailwind |
| `assets/styles/app.css` | Modifier | Remplacer le contenu par un commentaire minimal |

### Signatures
Aucune fonction/ classe à créer — modifications purement template et CSS.

### Contraintes techniques
- **Framework** : Symfony 8.0, Twig 3.x
- **Tailwind** : Version 3.x (via CDN `cdn.tailwindcss.com`)
- **Pattern** : Suivre la structure existante des templates Twig (blocs, extends)
- **Responsive** : Les classes Tailwind `container mx-auto px-4` assurent déjà une base responsive
- **Accessibilité** : Conserver les balises sémantiques HTML5 (`<header>`, `<main>`, `<footer>`, `<nav>`)
- **Performance** : Le CDN Tailwind est chargé de manière bloquante (pas d'attribut `async`/`defer` pour éviter le FOUC)
- **Compatibilité** : Ne pas supprimer les classes CSS existantes (`product-card`, `btn`, etc.) utilisées dans les templates — elles continueront de fonctionner en parallèle de Tailwind

### Tests à implémenter
Aucun test direct pour cette tâche — les tests sont dans la Tâche #006.

### Documentation
Aucune documentation directe pour cette tâche — la documentation est dans la Tâche #006.
