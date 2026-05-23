# Tâche #003 - Story #005 : CartController et templates panier

## Objectif
Créer le contrôleur du panier avec toutes les routes (affichage, ajout, modification, suppression, vidage) et les templates Twig associés, y compris la mise à jour de la navigation.

## Contexte
- Story #005 : `docs/stories/story-005.md`
- Dépend de : Tâche #002 (CartService)
- Nécessaire pour : Tâche #004 (tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer l'interface utilisateur complète du panier : une page panier visible, des actions depuis la fiche produit et la liste, un badge dans la navbar.

**Routes à créer :**

| Méthode | Route | Nom | Paramètre | Description |
|---------|-------|-----|-----------|-------------|
| GET | `/panier` | `app_cart_index` | — | Afficher le contenu du panier |
| POST | `/panier/ajouter/{id}` | `app_cart_add` | `Product` (via ParamConverter) | Ajouter un produit avec quantité (formulaire AddToCartType) |
| POST | `/panier/modifier/{id}` | `app_cart_update` | `CartItem` (via ParamConverter) | Modifier la quantité d'un item (formulaire UpdateCartItemType) |
| POST | `/panier/supprimer/{id}` | `app_cart_remove` | `CartItem` (via ParamConverter) | Supprimer un item du panier |
| POST | `/panier/vider` | `app_cart_clear` | — | Vider le panier |

**Cas nominaux :**
1. **Page panier** : Affiche la liste des produits (nom, image, prix unitaire, quantité, sous-total), le total général, et des boutons d'action (modifier quantité, supprimer, vider le panier)
2. **Ajout depuis la fiche produit** : L'utilisateur choisit une quantité (mini 1) et clique "Ajouter au panier" → redirection vers le panier ou message flash + retour à la page
3. **Ajout depuis la liste** : Chaque produit a un bouton "Ajouter au panier" (quantité=1 par défaut)
4. **Modification quantité** : Sur la page panier, l'utilisateur change la quantité via un champ nombre ou des boutons +/- → soumet le formulaire
5. **Suppression** : Un bouton "Supprimer" par ligne → retire le produit
6. **Vidage** : Un bouton "Vider le panier" → confirmation puis suppression de tous les items

**Cas limites :**
- Panier vide → message "Votre panier est vide" + lien vers le catalogue
- Quantité max : pas de limite haute (le contrôle est fait par le CartService)
- Si l'utilisateur n'est pas connecté et essaie d'accéder au panier → redirection vers la page de connexion (via le firewall)

**Gestion d'erreurs :**
- Produit inexistant → page 404 (ParamConverter gère cela)
- CartItem inexistant → page 404
- Panier inexistant (utilisateur sans panier) → affichage "panier vide" sans erreur

**Templates à créer/modifier :**

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/cart/index.html.twig` | Créer | Page panier complète |
| `templates/cart/_product_row.html.twig` | Créer | Partiel pour une ligne de produit dans le panier |
| `templates/shop/product.html.twig` | Modifier | Activer le bouton "Ajouter au panier" + formulaire quantité |
| `templates/shop/products.html.twig` | Modifier | Ajouter un bouton "Ajouter au panier" par produit |
| `templates/base.html.twig` | Modifier | Ajouter un lien/badge panier dans la navbar |

**Détails des templates :**

**cart/index.html.twig** :
- Titre "Mon panier"
- Si panier vide : message + lien "Découvrir nos produits" → `app_shop_category` (première catégorie ou page d'accueil)
- Si panier non vide : tableau avec colonnes (Produit, Prix unitaire, Quantité, Total, Actions)
- Chaque ligne : image thumbnail, nom, prix, input nombre pour quantité, sous-total, bouton supprimer
- Formulaire de mise à jour des quantités (POST vers `app_cart_update` pour chaque ligne)
- Total général en bas
- Bouton "Vider le panier" (POST avec confirmation via JS natif ou formulaire séparé)
- Lien ou bouton "Passer la commande" (vers Story #006 — pour l'instant désactivé ou non présent)

**base.html.twig** (modification navbar) :
- Ajouter un lien `app_cart_index` avec une icône panier
- Afficher le nombre d'articles dans un badge (accessible via un contrôleur qui expose la variable globale ou via un Twig `app.user` et un service appelé dans le contrôleur de base)

**shop/product.html.twig** (modification) :
- Remplacer le bouton désactivé par un formulaire POST vers `app_cart_add`
- Champ quantité (input number, min=1, default=1)
- Bouton "Ajouter au panier" (submit)
- Message flash de confirmation après ajout

**shop/products.html.twig** (modification) :
- Ajouter pour chaque produit un petit bouton ou lien "Ajouter au panier" (POST avec quantité=1)
- Utiliser un formulaire simple ou un lien avec confirmation

**Affichage du badge panier :**
Utiliser une **Twig runtime extension** (`CartRuntime` + `CartExtension`) exposant une fonction `cart_item_count()`. Cette fonction appelle `CartService::getProductCount()` via l'utilisateur courant (`Security::getUser()`). Si aucun utilisateur connecté, retourne 0 silencieusement. Voir les signatures détaillées dans la section Contraintes techniques ci-dessus.

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/CartController.php` | Créer | Contrôleur du panier |
| `src/Twig/CartRuntime.php` | Créer | Extension Twig Runtime pour le badge panier |
| `src/Form/Cart/AddToCartType.php` | Créer | Formulaire Symfony d'ajout au panier (quantité) |
| `src/Form/Cart/UpdateCartItemType.php` | Créer | Formulaire Symfony de modification quantité |
| `templates/cart/index.html.twig` | Créer | Page panier |
| `templates/cart/_product_row.html.twig` | Créer | Partiel ligne produit |
| `templates/shop/product.html.twig` | Modifier | Activer le bouton ajout panier |
| `templates/shop/products.html.twig` | Modifier | Ajouter bouton ajout panier |
| `templates/base.html.twig` | Modifier | Ajouter badge panier navbar |

### Contraintes techniques
- **Sécurité** : Toutes les routes du panier sont protégées par `ROLE_USER`. Ajouter dans `config/packages/security.yaml` la règle : `{ path: ^/panier, roles: ROLE_USER }` entre les règles `^/verify-email` et `^/admin`.
- **Formulaires Symfony** : Utiliser `AddToCartType` (champ `quantity` de type `IntegerType`, `data_class` non requis car pas de DTO dédié) et `UpdateCartItemType` (champ `quantity`). La protection CSRF est incluse automatiquement.
- **ParamConverter implicite** : Les routes `/panier/modifier/{id}` et `/panier/supprimer/{id}` reçoivent un `CartItem` via le ParamConverter implicite de Doctrine. Vérifier l'appartenance dans le contrôleur : `$cartItem->getCart()->getUser() === $this->getUser()`.
- **Validation appartenance** : Si le CartItem n'appartient pas à l'utilisateur courant, retourner 403 (AccessDeniedException) ou un message flash d'erreur.
- **Flash messages** : Utiliser `addFlash('success', '...')` après chaque action utilisateur
- **Redirections** : Après ajout depuis fiche produit, rediriger vers `app_cart_index` avec un flash "Produit ajouté au panier". Après modification/suppression, rediriger vers `app_cart_index`.
- **Forcer le login** : Si l'utilisateur connecté est redirigé vers le panier et n'a pas encore de Cart, le contrôleur l'appelle via `$this->getUser()` et `CartService::getOrCreateCart()` pour initialiser.

**Détails des formulaires Symfony :**

**AddToCartType** :
```php
class AddToCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('quantity', IntegerType::class, [
            'label' => 'Quantité',
            'attr' => ['min' => 1, 'value' => 1],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
```

**UpdateCartItemType** :
```php
class UpdateCartItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('quantity', IntegerType::class, [
            'label' => 'Quantité',
            'attr' => ['min' => 0],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
```

**CartRuntime (Twig Runtime) :**
```php
namespace App\Twig;

use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

class CartRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Security $security,
    ) {}

    public function getCartItemCount(): int
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return 0;
        }
        try {
            return $this->cartService->getProductCount($user);
        } catch (\RuntimeException) {
            return 0;
        }
    }
}
```

Enregistrer le runtime via une extension Twig (`CartExtension` dans `src/Twig/`) :
```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_item_count', [CartRuntime::class, 'getCartItemCount']),
        ];
    }
}
```

Utilisation dans `base.html.twig` : `{{ cart_item_count() }}`.

### Tests associés
Voir Tâche #004 pour les tests détaillés (navigation, formulaires, routes).

### Documentation
- Mettre à jour `docs/api/README.md` ou le README principal avec les nouvelles routes du panier
