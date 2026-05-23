# Tâche #002 - Story #005 : CartService (logique métier du panier)

## Objectif
Implémenter le service `CartService` contenant toute la logique métier du panier : ajout, modification, suppression de produits, calcul du total et du nombre d'articles.

## Contexte
- Story #005 : `docs/stories/story-005.md`
- Dépend de : Tâche #001 (entités Cart + CartItem)
- Nécessaire pour : Tâche #003 (CartController)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer le service `CartService` qui encapsule toutes les opérations sur le panier d'un utilisateur connecté. Le service reçoit l'utilisateur courant via paramètre de méthode et utilise l'EntityManager pour persister les changements.

**Cas nominaux :**
1. **Ajout d'un produit** : Si le produit n'est pas déjà dans le panier, créer un nouveau CartItem. S'il existe déjà, augmenter la quantité du nombre demandé. Le prix est figé au moment de l'ajout (copié depuis `Product.price` dans `CartItem.price`).
2. **Modification de quantité** : Mettre à jour la quantité d'un CartItem existant (identifié directement par l'objet CartItem).
3. **Suppression d'un item** : Retirer un CartItem du panier (identifié directement par l'objet CartItem).
4. **Vidage du panier** : Supprimer tous les CartItem du panier (le Cart reste).
5. **Calcul du total** : Somme de (quantité × price) pour chaque CartItem.
6. **Nombre d'articles** : Somme des quantités de tous les CartItems.

**Cas limites :**
- Ajout d'un produit déjà présent → incrémente la quantité (ne crée pas un doublon)
- Quantité mise à 0 dans `updateItemQuantity` → supprime automatiquement le CartItem (via `removeItem`)
- Quantité négative dans `updateItemQuantity` → refusée
- Panier vide (aucun CartItem) → `getTotal()` retourne "0.00", `getProductCount()` retourne 0
- `updateItemQuantity` ou `removeItem` avec un CartItem qui n'appartient pas au User → `\RuntimeException`
- `removeItem` avec un CartItem déjà orphelin → `\RuntimeException`

**Gestion d'erreurs :**
- `\RuntimeException` si le CartItem passé à `updateItemQuantity`/`removeItem` n'appartient pas au panier de l'utilisateur
- `\InvalidArgumentException` si la quantité est invalide (< 1 pour addProduct, < 0 pour updateItemQuantity)
- Si l'utilisateur n'a pas de panier au moment de `getOrCreateCart()`, un nouveau Cart est créé automatiquement

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Service/CartService.php` | Créer | Service métier du panier |

### Signatures

```php
namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;

class CartService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
    );

    /**
     * Récupère le panier de l'utilisateur, en crée un si nécessaire.
     */
    public function getOrCreateCart(User $user): Cart;

    /**
     * Ajoute un produit au panier (ou augmente la quantité).
     * Le prix est figé depuis Product.price au moment de l'appel.
     * @throws \InvalidArgumentException si quantity < 1
     */
    public function addProduct(User $user, Product $product, int $quantity = 1): void;

    /**
     * Modifie la quantité d'un CartItem.
     * Si quantity = 0, supprime l'item (délègue à removeItem).
     * @throws \InvalidArgumentException si quantity < 0
     * @throws \RuntimeException si le CartItem n'appartient pas à l'utilisateur
     */
    public function updateItemQuantity(CartItem $item, int $quantity): void;

    /**
     * Supprime un CartItem du panier.
     * @throws \RuntimeException si le CartItem n'appartient pas à l'utilisateur
     */
    public function removeItem(CartItem $item): void;

    /**
     * Vide le panier (supprime tous les items).
     */
    public function clearCart(User $user): void;

    /**
     * Calcule le nombre total d'articles (somme des quantités).
     */
    public function getProductCount(User $user): int;

    /**
     * Calcule le montant total du panier.
     * @return string Montant formaté en décimal (ex: "42.50")
     */
    public function getTotal(User $user): string;

    /**
     * Retourne les items du panier d'un utilisateur.
     * @return CartItem[]
     */
    public function getItems(User $user): array;
}
```

### Contraintes techniques
- **Architecture** : Suivre le pattern des services existants (`UserService`, `MailerService`) — constructeur avec injection de dépendances via `autowire`
- **EntityManager** : Utiliser `EntityManagerInterface` (pas l'EntityManager concret)
- **flush()** : Appeler `flush()` après chaque modification (le service est synchrone)
- **Prix** : Lors de l'ajout, figer le prix actuel du produit dans CartItem.price (ne pas recalculer depuis Product)
- **Vérification d'appartenance** : `updateItemQuantity` et `removeItem` doivent vérifier que le CartItem appartient bien au Cart de l'utilisateur courant. Comparer `$item->getCart()->getUser()->getId()` avec l'utilisateur passé. Si non, lancer `\RuntimeException`.
- **Pas de repository CartItem** nécessaire : on accède aux items via `$cart->getItems()`

### Tests associés
Voir Tâche #004 pour les tests détaillés.

### Documentation
- Rien à documenter pour cette tâche (API documentée dans le Readme via Tâche #004)
