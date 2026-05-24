# Tâche #004 - Story #012 : Aperçu du panier au survol avec Stimulus + Tailwind

## Objectif
Implémenter un aperçu (popup/dropdown) du contenu du panier qui apparaît au survol de l'icône panier dans le header, affichant la liste des produits, les quantités et le total.

## Contexte
- Story #012 : `docs/stories/story-012.md`
- Dépend de : Tâche #002 (header avec icône panier)
- Nécessaire pour : Aucune

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Contrôleur Stimulus `cart-preview`
Créer un contrôleur Stimulus qui gère l'affichage/masquage d'un aperçu du panier au survol.

**Fonctionnement :**
- Au survol (`mouseenter`) de l'icône panier : afficher le dropdown après un court délai (200ms)
- Au départ de la souris (`mouseleave`) : masquer le dropdown après un court délai (300ms) pour permettre à l'utilisateur de déplacer la souris dans le dropdown
- Si la souris entre dans le dropdown : annuler le masquage
- Si la souris quitte le dropdown : masquer
- Le dropdown se positionne en dessous de l'icône panier
- Utiliser Turbo/TurboStream pour charger les données du panier à l'affichage (fetch du contenu du panier via une route dédiée)

#### 2. Route de rendu du panier pour le preview
Créer une route `GET /panier/preview` qui renvoie uniquement le contenu HTML du dropdown (pas la page complète). Cette route est réservée aux utilisateurs connectés (`ROLE_USER`) et peut être appelée via fetch depuis le contrôleur Stimulus.

**Contrôleur :** Dans `CartController.php`, ajouter une méthode `preview()` :

```php
#[Route('/panier/preview', name: 'app_cart_preview', methods: ['GET'])]
public function preview(): Response
{
    $this->denyAccessUnlessGranted('ROLE_USER');
    $user = $this->getUser();
    $cart = $this->cartService->getOrCreateCart($user);
    $items = $cart->getItems();

    return $this->render('cart/_preview.html.twig', [
        'items' => $items,
        'total' => $this->cartService->getTotal($user),
        'count' => $this->cartService->getProductCount($user),
    ]);
}
```

#### 3. Template du preview (`templates/cart/_preview.html.twig`)
Créer un partial Twig pour le contenu du dropdown :

```twig
<div class="w-80 bg-white rounded-xl shadow-xl border border-gray-200">
    <div class="p-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Mon panier</h3>
        {% if count > 0 %}
            <p class="text-sm text-gray-500">{{ count }} article(s)</p>
        {% endif %}
    </div>

    {% if items|length > 0 %}
        <div class="max-h-64 overflow-y-auto divide-y divide-gray-100">
            {% for item in items %}
                {% set product = item.product %}
                <div class="flex items-center space-x-3 p-3">
                    <img src="{{ asset(product.image) }}" alt="{{ product.name }}" class="w-10 h-10 rounded object-cover bg-gray-100">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ product.name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ item.quantity }} &times; {{ item.price|format_currency('EUR') }}
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">{{ (item.price * item.quantity)|format_currency('EUR') }}</p>
                </div>
            {% endfor %}
        </div>

        <div class="p-4 border-t border-gray-100">
            <div class="flex justify-between items-center mb-3">
                <span class="font-semibold text-gray-800">Total</span>
                <span class="font-bold text-brand-600">{{ total|format_currency('EUR') }}</span>
            </div>
            <a href="{{ path('app_cart_index') }}" class="block w-full bg-brand-600 text-white text-center px-4 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                Voir le panier
            </a>
        </div>
    {% else %}
        <div class="p-6 text-center text-gray-500">
            <p>Votre panier est vide</p>
        </div>
        <div class="p-4 pt-0">
            <a href="{{ path('app_home') }}" class="block w-full bg-brand-600 text-white text-center px-4 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
                Découvrir nos produits
            </a>
        </div>
    {% endif %}
</div>
```

#### 4. Contrôleur Stimulus complet

**Fichier :** `assets/controllers/cart_preview_controller.js`

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dropdown'];

    connect() {
        this.showTimeout = null;
        this.hideTimeout = null;
        this.loaded = false;
        this.dropdownTarget.classList.add('hidden');
    }

    disconnect() {
        clearTimeout(this.showTimeout);
        clearTimeout(this.hideTimeout);
    }

    mouseEnter() {
        clearTimeout(this.hideTimeout);
        this.showTimeout = setTimeout(() => this.show(), 200);
    }

    mouseLeave() {
        clearTimeout(this.showTimeout);
        this.hideTimeout = setTimeout(() => this.hide(), 300);
    }

    show() {
        this.dropdownTarget.classList.remove('hidden');
        if (!this.loaded) {
            this.load();
        }
    }

    hide() {
        this.dropdownTarget.classList.add('hidden');
    }

    async load() {
        try {
            const response = await fetch('/panier/preview');
            if (!response.ok) return;
            const html = await response.text();
            this.dropdownTarget.innerHTML = html;
            this.loaded = true;
        } catch (e) {
            // Silently fail — le dropdown reste vide
        }
    }
}
```

#### 5. Mise à jour de l'icône panier dans `base.html.twig`
Modifier l'icône panier (déjà dans le header de la Tâche #002) pour ajouter le dropdown :

```twig
<a href="{{ path('app_cart_index') }}" class="relative hover:text-brand-200 transition-colors"
   data-controller="cart-preview"
   data-action="mouseenter->cart-preview#mouseEnter mouseleave->cart-preview#mouseLeave">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        ...
    </svg>
    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center cart-badge">{{ cart_item_count() }}</span>

    <!-- Dropdown de preview -->
    <div data-cart-preview-target="dropdown"
         class="absolute top-full right-0 mt-2 z-50 hidden"
         data-action="mouseenter->cart-preview#mouseEnter mouseleave->cart-preview#mouseLeave">
        <!-- Le contenu est chargé via fetch -->
    </div>
</a>
```

**Cas nominaux :**
- Survol de l'icône panier : après 200ms, le dropdown apparaît avec un effet de fondu
- Dropdown affiche : les produits (image, nom, quantité × prix, sous-total), le total, et un bouton "Voir le panier"
- Le dropdown est chargé via fetch la première fois seulement (pas à chaque survol)
- Si le panier est vide : message "Votre panier est vide" + bouton "Découvrir nos produits"
- Déplacement de la souris de l'icône vers le dropdown : le dropdown reste visible

**Cas limites :**
- Panier vide : afficher le message et le bouton vers l'accueil
- Panier avec beaucoup d'articles : scroll vertical dans le dropdown (`max-h-64 overflow-y-auto`)
- Utilisateur non connecté : l'icône panier n'est pas affichée (pas de preview possible)
- Nom de produit très long : `truncate` pour couper avec "..."
- Erreur réseau lors du fetch : le dropdown reste vide mais ne crash pas

**Gestion d'erreurs :**
- Erreur 403 (non connecté) : le fetch échoue silencieusement, le dropdown reste affiché mais vide
- Image de produit manquante : la classe `bg-gray-100` sert de fond de remplacement
- Plusieurs survols rapides : les timeouts sont nettoyés (`clearTimeout`) pour éviter des comportements incohérents

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `assets/controllers/cart_preview_controller.js` | Créer | Contrôleur Stimulus pour le dropdown du panier |
| `templates/cart/_preview.html.twig` | Créer | Partial Twig pour le contenu du dropdown |
| `src/Controller/CartController.php` | Modifier | Ajouter la méthode `preview()` |
| `templates/base.html.twig` | Modifier | Ajouter `data-controller` et le target dropdown à l'icône panier |

### Signatures

```php
// src/Controller/CartController.php

#[Route('/panier/preview', name: 'app_cart_preview', methods: ['GET'])]
public function preview(): Response
{
    // Vérifie que l'utilisateur est connecté (ROLE_USER)
    // Récupère le panier via CartService
    // Retourne le rendu de cart/_preview.html.twig
}
```

```js
// assets/controllers/cart_preview_controller.js
export default class extends Controller {
    static targets = ['dropdown'];

    connect(): void;
    disconnect(): void;
    mouseEnter(): void;
    mouseLeave(): void;
    show(): void;
    hide(): void;
    load(): Promise<void>;
}
```

### Contraintes techniques
- **Framework** : Stimulus 3.x (déjà présent dans le projet), Symfony 8.0
- **Pattern** : Suivre le pattern des contrôleurs Stimulus existants pour la structure (targets, actions, connect/disconnect)
- **Turbo** : Le contrôleur doit gérer la reconnexion après navigation Turbo (le `connect()` gère déjà ça dans Stimulus)
- **Performance** : Le fetch du panier n'a lieu qu'**une seule fois** par chargement de page (pas à chaque survol)
- **UX** : Délai de 200ms avant l'affichage (évite les faux départs), délai de 300ms avant la disparition (permet d'atteindre le dropdown)
- **Position** : Le dropdown est en `absolute` positionné en-dessous et à droite de l'icône (`top-full right-0 mt-2`)
- **Z-index** : `z-50` pour être au-dessus de tout autre contenu
- **Sécurité** : La route `/panier/preview` est protégée par `denyAccessUnlessGranted('ROLE_USER')`

### Tests à implémenter

#### Test d'intégration
- **Fichier** : `tests/Integration/TailwindStylingTest.php` (créé dans Tâche #006)
- Scénario : Vérifier que la route `/panier/preview` renvoie un statut 200 pour un utilisateur connecté

### Documentation
Aucune documentation directe pour cette tâche.
