# Tâche #002 - Story #012 : Header/Navbar et Footer avec Tailwind CSS

## Objectif
Styliser le header/navbar et le footer de toutes les pages avec Tailwind CSS : header moderne avec logo, navigation, icône panier avec badge ; footer complet avec plusieurs colonnes (liens, contact, informations). Design responsive (hamburger menu sur mobile).

## Contexte
- Story #012 : `docs/stories/story-012.md`
- Dépend de : Tâche #001 (intégration CDN + structure layout)
- Nécessaire pour : Tâche #004 (cart hover preview)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Header/Navbar modernisé
Transformer le `<nav>` dans `templates/base.html.twig` (dans le bloc `header`) pour utiliser un header complet avec :

**Structure du header :**
```html
<header>
    <nav class="bg-brand-700 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo / Titre -->
                <a href="{{ path('app_home') }}" class="flex items-center space-x-2">
                    <span class="text-2xl">🥦</span>
                    <span class="text-xl font-bold">Fruits &amp; Veggies</span>
                </a>

                <!-- Navigation principale (desktop) -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ path('app_home') }}" class="hover:text-brand-200 transition-colors">Accueil</a>
                    <a href="{{ path('app_home') }}" class="hover:text-brand-200 transition-colors">Boutique</a>
                    {% if app.user %}
                        <a href="{{ path('app_order_list') }}" class="hover:text-brand-200 transition-colors">Mes commandes</a>
                    {% endif %}
                </div>

                <!-- Actions (panier, connexion, menu mobile) -->
                <div class="flex items-center space-x-4">
                    {% if app.user %}
                        <a href="{{ path('app_cart_index') }}" class="relative hover:text-brand-200 transition-colors" id="cart-icon" data-controller="cart-preview">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                            </svg>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center cart-badge">{{ cart_item_count() }}</span>
                        </a>
                        <div class="hidden md:flex items-center space-x-2">
                            <span class="text-sm">{{ app.user.firstName }}</span>
                            <a href="{{ path('app_logout') }}" class="text-sm hover:text-brand-200 transition-colors">Déconnexion</a>
                        </div>
                    {% else %}
                        <a href="{{ path('app_login') }}" class="hover:text-brand-200 transition-colors">Connexion</a>
                        <a href="{{ path('app_register') }}" class="bg-white text-brand-700 px-4 py-2 rounded-lg font-medium hover:bg-brand-100 transition-colors">Inscription</a>
                    {% endif %}

                    <!-- Hamburger menu (mobile) -->
                    <button class="md:hidden mobile-menu-button" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Menu mobile (caché par défaut) -->
            <div class="md:hidden hidden mobile-menu pb-4">
                <a href="{{ path('app_home') }}" class="block py-2 hover:text-brand-200 transition-colors">Accueil</a>
                <a href="{{ path('app_home') }}" class="block py-2 hover:text-brand-200 transition-colors">Boutique</a>
                {% if app.user %}
                    <a href="{{ path('app_order_list') }}" class="block py-2 hover:text-brand-200 transition-colors">Mes commandes</a>
                    <hr class="border-brand-600 my-2">
                    <span class="block py-2 text-sm">{{ app.user.firstName }}</span>
                    <a href="{{ path('app_logout') }}" class="block py-2 hover:text-brand-200 transition-colors">Déconnexion</a>
                {% else %}
                    <hr class="border-brand-600 my-2">
                    <a href="{{ path('app_login') }}" class="block py-2 hover:text-brand-200 transition-colors">Connexion</a>
                    <a href="{{ path('app_register') }}" class="block py-2 hover:text-brand-200 transition-colors">Inscription</a>
                {% endif %}
            </div>
        </div>
    </nav>
</header>
```

#### 2. Menu mobile avec Stimulus
Créer un contrôleur Stimulus pour le menu mobile (hamburger) :

**Fichier :** `assets/controllers/mobile_menu_controller.js`

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.button = this.element.querySelector('.mobile-menu-button');
        if (this.button) {
            this.button.addEventListener('click', () => this.toggle());
        }
    }

    disconnect() {
        if (this.button) {
            this.button.removeEventListener('click', () => this.toggle());
        }
    }

    toggle() {
        this.menuTarget.classList.toggle('hidden');
    }
}
```

Mettre à jour le bouton hamburger et le menu mobile dans `base.html.twig` :

```twig
<nav class="..." data-controller="mobile-menu">
    ...
    <!-- Hamburger menu (mobile) -->
    <button class="md:hidden mobile-menu-button" aria-label="Menu" data-action="mobile-menu#toggle">
        ...
    </button>
    ...
    <!-- Menu mobile -->
    <div class="md:hidden hidden mobile-menu pb-4" data-mobile-menu-target="menu">
        ...
    </div>
</nav>
```

#### 3. Footer complet
Remplacer le footer minimal de la Tâche #001 par un footer complet avec 3 colonnes :

```html
<footer class="bg-brand-800 text-white">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Colonne 1 : À propos -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Fruits &amp; Veggies</h3>
                <p class="text-brand-200 text-sm">
                    Votre magasin de fruits et légumes frais en ligne. 
                    Produits locaux et de saison, livrés chez vous.
                </p>
            </div>

            <!-- Colonne 2 : Liens rapides -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Liens rapides</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ path('app_home') }}" class="text-brand-200 hover:text-white transition-colors">Accueil</a></li>
                    <li><a href="{{ path('app_home') }}" class="text-brand-200 hover:text-white transition-colors">Boutique</a></li>
                    {% if app.user %}
                        <li><a href="{{ path('app_order_list') }}" class="text-brand-200 hover:text-white transition-colors">Mes commandes</a></li>
                    {% else %}
                        <li><a href="{{ path('app_login') }}" class="text-brand-200 hover:text-white transition-colors">Connexion</a></li>
                        <li><a href="{{ path('app_register') }}" class="text-brand-200 hover:text-white transition-colors">Inscription</a></li>
                    {% endif %}
                </ul>
            </div>

            <!-- Colonne 3 : Contact -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Contact</h3>
                <ul class="space-y-2 text-sm text-brand-200">
                    <li class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>contact@fruits-veggies.local</span>
                    </li>
                    <li class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Grenoble, France</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Barre de copyright -->
    <div class="bg-brand-900 py-4">
        <div class="container mx-auto px-4 text-center text-sm text-brand-300">
            &copy; {{ 'now'|date('Y') }} Fruits &amp; Veggies. Tous droits réservés.
        </div>
    </div>
</footer>
```

#### 4. Correction du lien "Boutique"
**Important :** Le lien "Boutique" dans la navbar pointe actuellement vers `app_home`. La navbar doit permettre de naviguer vers la boutique. Puisqu'il n'y a pas de route de liste de toutes les catégories sur une page dédiée "shop", le lien peut soit pointer vers `app_home` (qui liste les catégories), soit vers une route dédiée. **Conserver `app_home`** pour l'instant, car la page d'accueil liste déjà toutes les catégories.

**Cas nominaux :**
- Header affiché sur toutes les pages avec fond vert (`bg-brand-700`)
- Logo "Fruits & Veggies" visible à gauche
- Navigation desktop : Accueil, Boutique, (si connecté) Mes commandes
- Zone actions : icône panier avec badge rouge, nom utilisateur + déconnexion OU connexion/inscription
- Footer avec 3 colonnes (à propos, liens, contact) sur desktop, 1 colonne sur mobile
- Copyright en bas du footer

**Cas limites :**
- Utilisateur non connecté : pas de badge panier, pas de "Mes commandes"
- Utilisateur connecté sans rien dans le panier : badge affiche 0
- Très petit écran (< 640px) : menu hamburger visible, navigation desktop cachée
- Écran moyen (640px-768px) : comportement mobile (hamburger)

**Gestion d'erreurs :**
- Si `cart_item_count()` n'est pas disponible (cas exceptionnel), le badge ne s'affiche pas — laisser le `{{ cart_item_count() }}` tel quel
- Le menu hamburger doit fonctionner avec/sans JavaScript : si JS désactivé, le menu reste visible par défaut

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/base.html.twig` | Modifier | Remplacer le `<nav>` par le header/navbar complet et le footer complet |
| `assets/controllers/mobile_menu_controller.js` | Créer | Contrôleur Stimulus pour le menu hamburger mobile |

### Signatures

```js
// assets/controllers/mobile_menu_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect(): void;
    disconnect(): void;
    toggle(): void;
}
```

### Contraintes techniques
- **Framework** : Stimulus 3.x (déjà dans le projet via AssetMapper), Twig 3.x, Symfony 8.0
- **Pattern** : Suivre le pattern des contrôleurs Stimulus existants (`assets/controllers/hello_controller.js`, `csrf_protection_controller.js`)
- **Tailwind** : Classes utilitaires uniquement (pas de CSS personnalisé)
- **Responsive** : Breakpoints Tailwind `md:` (768px) pour desktop vs mobile
- **Icônes** : Utiliser des SVG inline (pas de dépendance supplémentaire comme Font Awesome)
- **JavaScript** : Le menu mobile doit fonctionner avec Turbo (Stimulus se reconnecte automatiquement après les navigations Turbo)
- **Accessibilité** : Bouton hamburger avec `aria-label="Menu"`, navigation avec `aria-label`
- **Couleurs** : Utiliser les couleurs de la palette `brand` définies dans la config Tailwind de la Tâche #001

### Tests à implémenter

#### Tests d'intégration
- **Fichier** : `tests/Integration/TailwindStylingTest.php` (créé dans Tâche #006)
- Scénario : Vérifier que la page d'accueil contient les classes Tailwind attendues pour le header et footer

### Documentation
Aucune documentation directe pour cette tâche.
