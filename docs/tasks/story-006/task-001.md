# Tâche #001 - Story #006 : OrderService — Logique métier des commandes

## Objectif

Créer le service métier `OrderService` responsable de la création de commandes à partir d'un panier, de la gestion des transitions de statut et de l'annulation, ainsi que la méthode `cartToOrder()` dans `CartService` qui délègue la logique de création à `OrderService`.

## Contexte

- Story #006 : `docs/stories/story-006.md`
- Dépend de : Story #005 (panier), Story #003 (authentification)
- Dépend des entités existantes : `Order`, `OrderLine`, `Cart`, `CartItem`, `Product`, `User`, `OrderStatus`
- Nécessaire pour : Tâche #002, Tâche #003, Tâche #004, Tâche #005

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux :**
- Créer une commande à partir du panier : copie les items du panier vers des OrderLine, fixe le prix au moment de la commande, vide le panier, retourne l'entité Order avec statut `confirmed` et `orderedAt` = now
- Transition `confirmed → preparing → shipped → delivered` : chaque transition doit être valide
- Un client peut annuler sa commande si le statut actuel est `confirmed`
- Un admin peut annuler une commande quel que soit son statut

**Cas limites :**
- Panier vide lors de la validation → lever une exception
- Transition invalide (ex: `confirmed → delivered` sans passer par `preparing` et `shipped`) → lever une exception
- Annulation d'une commande déjà livrée ou déjà annulée par un admin → autorisé car l'admin a tous les droits
- L'admin ne peut PAS annuler une commande déjà annulée (éviter double annulation) → lever une exception

**Gestion d'erreurs :**
- Panier vide → `InvalidArgumentException('Votre panier est vide.')`
- Transition invalide → `RuntimeException('Transition de statut invalide : de {from} vers {to}')`
- Annulation par client si statut ≠ confirmed → `RuntimeException('Vous ne pouvez pas annuler une commande avec le statut {status}.')`
- Annulation admin si déjà cancelled → `RuntimeException('Cette commande est déjà annulée.')`
- Admin annulation d'une commande déjà delivered → autorisé (cf. CA : "quel que soit son statut")
- Quantité négative ou nulle dans la conversion panier → ne peut pas arriver car filtré par CartService, mais prévoir une vérification defensive

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Service/OrderService.php` | Créer | Service métier : création, transition, annulation |
| `src/Service/CartService.php` | Modifier | Ajouter méthode `cartToOrder()` qui délègue à OrderService |

### Signatures

```php
namespace App\Service;

use App\Entity\Order;
use App\Entity\User;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartService $cartService,
        private readonly MailerService $mailerService,
        private readonly MessageBusInterface $messageBus,
    );

    /**
     * Crée une commande à partir du panier de l'utilisateur.
     * Vide le panier après création.
     * Déclenche l'envoi de l'email de confirmation.
     * @throws InvalidArgumentException si le panier est vide
     */
    public function createFromCart(User $user): Order;

    /**
     * Transitionne une commande vers un nouveau statut.
     * Validation de la séquence : confirmed → preparing → shipped → delivered.
     * Envoie un email à chaque changement de statut via MailerService.
     * @throws RuntimeException si la transition est invalide
     */
    public function transitionStatus(Order $order, OrderStatus $newStatus): void;

    /**
     * Annule une commande.
     * Un client ne peut annuler que si le statut est "confirmed".
     * Un admin peut annuler n'importe quelle commande (sauf déjà annulée).
     * @param bool $isAdmin=false true si l'utilisateur est admin
     * @throws RuntimeException si l'annulation n'est pas autorisée
     */
    public function cancelOrder(Order $order, bool $isAdmin = false): void;

    /**
     * Calcule le total d'une commande (somme des price * quantity des OrderLine).
     * Retourne une chaîne formatée "1234.56".
     */
    public function getOrderTotal(Order $order): string;
}
```

```php
// Dans CartService, ajouter :

/**
 * Convertit le panier en commande via OrderService.
 * Vide le panier après conversion.
 * @throws InvalidArgumentException si le panier est vide
 */
public function cartToOrder(User $user): Order;
```

### Validation des transitions

```php
// Matrice des transitions valides
private const array VALID_TRANSITIONS = [
    OrderStatus::Confirmed => [OrderStatus::Preparing],
    OrderStatus::Preparing => [OrderStatus::Shipped],
    OrderStatus::Shipped => [OrderStatus::Delivered],
];
```

### Contraintes techniques

- **Framework** : Symfony 8.0 / Doctrine ORM 3
- **Pattern** : Suivre le pattern de service existant (`CartService`, `MailerService`, `UserService`) — constructeur avec `private readonly` promotion de propriété, injection de dépendances via l'autowiring
- **PHP** : 8.4 — utiliser promoted properties, `match` expressions, `enum` methods si pertinent
- **Statuts** : utiliser l'enum `App\Enum\OrderStatus` existante
- **Entités** : les entités `Order`, `OrderLine`, `Cart`, `CartItem` existent déjà et sont mappées. Utiliser les getters/setters existants
- **Prix** : le prix est stocké en `string` (decimal 10,2) — respecter ce type pour les calculs
- **Transaction Doctrine** : la création de commande doit être dans une transaction pour garantir l'atomicité (flush après création des OrderLine, avant vidage du panier)
- **Email** : appeler `MailerService::sendOrderConfirmationEmail()` (créée dans tâche #002) après création de commande et dispatcher `OrderStatusChanged` (message créé dans tâche #002) après chaque changement de statut (transition + annulation). L'implémentation des méthodes MailerService et du Handler sera faite dans la tâche #002.
- **MessageBus** : dispatcher `new OrderStatusChanged((int) $order->getId())` via `$this->messageBus->dispatch()` après :
  - `createFromCart()` → confirmation (dispatch après flush)
  - `transitionStatus()` → chaque transition (dispatch après setStatus + flush)
  - `cancelOrder()` → annulation (dispatch après setStatus + flush)
- **Import** : `use Symfony\Component\Messenger\MessageBusInterface;` et `use App\Message\OrderStatusChanged;`. La classe `OrderStatusChanged` sera créée dans la tâche #002 — le code compilera après la tâche #002 uniquement. C'est un ordre d'exécution assumé.

### Tests à implémenter

Les tests pour OrderService sont dans la Tâche #005. Cette tâche ne génère que le code de production.

### Documentation

Aucune documentation spécifique pour cette tâche. Le processus global sera documenté dans la tâche #003.

### Exemples d'API (calqués sur les conventions du projet)

```php
// Dans un contrôleur (tâche #003) :
$order = $orderService->createFromCart($this->getUser());

// Transition par un admin (tâche #004) :
$orderService->transitionStatus($order, OrderStatus::Preparing);

// Annulation par un client :
$orderService->cancelOrder($order, isAdmin: false);

// Annulation par un admin :
$orderService->cancelOrder($order, isAdmin: true);
```
