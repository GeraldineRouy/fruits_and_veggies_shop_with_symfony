# Tâche #003 - Story #006 : Interface client — Commandes (Controller + Templates)

## Objectif

Créer le contrôleur et les templates permettant au client connecté de valider son panier, consulter la liste de ses commandes, voir le détail d'une commande, et annuler une commande éligible.

## Contexte

- Story #006 : `docs/stories/story-006.md`
- Dépend de : Tâche #001 (OrderService), Tâche #002 (emails)
- Nécessaire pour : Tâche #005 (tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux :**
- **Validation du panier** : depuis la page panier, un bouton "Valider la commande" POST vers `/commande/valider` crée la commande et redirige vers la page de confirmation
- **Liste des commandes** : GET `/profile/commandes` affiche toutes les commandes de l'utilisateur connecté, triées par date décroissante
- **Détail d'une commande** : GET `/profile/commande/{id}` affiche le détail (statut, produits, quantités, prix unitaire, total, date)
- **Annulation** : POST `/profile/commande/{id}/annuler` annule la commande si le statut est "confirmed"
- **Page de confirmation** : après validation du panier, rediriger vers `/profile/commande/{id}` avec un flash success

**Cas limites :**
- Commande qui n'appartient pas à l'utilisateur → 403
- Commande introuvable → 404
- Panier vide → rediriger vers le panier avec un flash error
- Annulation d'une commande non annulable → flash error et redirection

**Gestion d'erreurs :**
- `OrderService::createFromCart()` lève `InvalidArgumentException` → flash error + redirect panier
- `OrderService::cancelOrder()` lève `RuntimeException` → flash error + redirect détail commande
- Accès à une commande d'un autre utilisateur → `createAccessDeniedException()`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/OrderController.php` | Créer | Contrôleur des commandes client |
| `templates/profile/orders.html.twig` | Créer | Liste des commandes |
| `templates/profile/order.html.twig` | Créer | Détail d'une commande |
| `templates/base.html.twig` | Modifier | Ajouter lien "Mes commandes" dans la nav |
| `templates/cart/index.html.twig` | Modifier | Ajouter bouton "Valider la commande" |

### Signatures

```php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Entity\Order;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/commandes', name: 'app_order_list', methods: ['GET'])]
    public function list(OrderRepository $orderRepository): Response;

    #[Route('/commande/{id}', name: 'app_order_detail', methods: ['GET'])]
    public function detail(Order $order): Response;

    #[Route('/commande/{id}/annuler', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(Order $order, OrderService $orderService): Response;
}

// Route séparée pour la validation du panier (hors prefix /profile)
// car elle fait partie du flux panier :

#[Route('/commande')]
#[IsGranted('ROLE_USER')]
class OrderController // ou routes supplémentaires dans le même fichier
{
    #[Route('/valider', name: 'app_order_checkout', methods: ['POST'])]
    public function checkout(OrderService $orderService): Response;
}
```

**Note :** Tu peux mettre toutes les routes dans la même classe `OrderController` avec deux `#[Route]` de classe si nécessaire, ou scinder en deux classes. L'important est que les URLs soient :
- `POST /commande/valider` → `app_order_checkout`
- `GET /profile/commandes` → `app_order_list`
- `GET /profile/commande/{id}` → `app_order_detail`
- `POST /profile/commande/{id}/annuler` → `app_order_cancel`

### Structure des templates

#### `templates/profile/orders.html.twig`
- Étend `base.html.twig`
- Titre : "Mes commandes"
- Si aucune commande : message "Vous n'avez pas encore passé de commande." + lien vers la boutique
- Tableau avec colonnes : Numéro, Date, Statut, Total, Actions (Voir, Annuler si confirmed)
- Le statut est affiché en français avec un badge coloré :
  - `confirmed` → "Confirmée" (bleu)
  - `preparing` → "En préparation" (orange)
  - `shipped` → "Expédiée" (violet)
  - `delivered` → "Livrée" (vert)
  - `cancelled` → "Annulée" (rouge)
- Le total est affiché via `{{ orderData.total|format_currency('EUR') }}` (variable passée par le contrôleur)

#### `templates/profile/order.html.twig`
- Étend `base.html.twig`
- Affiche : numéro de commande, date, statut (badge), total (via `total|format_currency('EUR')`)
- Tableau des lignes de commande : Produit, Prix unitaire, Quantité, Total ligne
- Bouton "Annuler" si le statut est "confirmed"
- Message si déjà annulé ou livré

#### `templates/cart/index.html.twig` modification
- Ajouter un formulaire POST vers `app_order_checkout` dans le bloc `.cart-summary`
- Le bouton "Valider la commande" doit être stylé comme bouton principal (vert/succès)
- Le bouton "Vider le panier" reste mais en danger

#### `templates/base.html.twig` modification
- Dans le bloc nav, après le lien Panier, et avant le nom de l'utilisateur, ajouter :
```twig
<a href="{{ path('app_order_list') }}">Mes commandes</a>
```

### Contraintes techniques

- **Contrôleur** : suivre le pattern de `CartController` (mêmes conventions : `#[Route]`, `#[IsGranted]`, injection de services dans les signatures de méthodes)
- **Vérification propriété** : dans `detail()` et `cancel()`, vérifier que `$order->getUser() === $this->getUser()`, sinon `createAccessDeniedException()`
- **Format dates** : utiliser le filtre Twig `|date('d/m/Y H:i')`
- **Format monnaie** : utiliser `|format_currency('EUR')` comme dans `cart/index.html.twig`
- **Statuts** : passer une variable `status_label` aux templates qui traduit l'enum en français
- **Flash messages** : utiliser `addFlash('success', ...)` et `addFlash('error', ...)` comme dans le code existant
- **Routes** : le préfixe `/profile` est déjà protégé par `access_control` dans `security.yaml` (ROLE_USER). Ajouter également `{ path: ^/commande, roles: ROLE_USER }` dans `security.yaml` pour protéger `/commande/valider` (préfixe homogène avec `/panier`)
- **Total commande** : utiliser `$orderService->getOrderTotal($order)` dans le contrôleur pour calculer le total, et passer la valeur dans les variables du template. Ne pas utiliser de fonction Twig `order_total()`.
  Exemple dans le contrôleur :
  ```php
  // OrderController::list()
  $ordersData = array_map(fn(Order $o) => [
      'order' => $o,
      'total' => $orderService->getOrderTotal($o),
  ], $orders);
  ```

### Documentation

#### Documentation à créer
- `docs/features/order-process.md` : Documenter le processus de commande :
  - Déroulement : panier → validation → création → email confirmation
  - Les différents statuts et leur signification
  - Les règles d'annulation (client vs admin)

### Tests à implémenter

Les tests sont dans la Tâche #005.

### Exemples d'utilisation

```twig
{# templates/profile/orders.html.twig #}
<table>
    <thead>
        <tr>
            <th>N°</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Total</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        {% for orderData in ordersData %}
        <tr>
            <td>#{{ orderData.order.id }}</td>
            <td>{{ orderData.order.orderedAt|date('d/m/Y H:i') }}</td>
            <td><span class="badge badge-{{ orderData.order.status.value }}">{{ status_labels[orderData.order.status.value] }}</span></td>
            <td>{{ orderData.total|format_currency('EUR') }}</td>
            <td>
                <a href="{{ path('app_order_detail', {id: orderData.order.id}) }}">Voir</a>
                {% if orderData.order.status.value == 'confirmed' %}
                <form method="post" action="{{ path('app_order_cancel', {id: orderData.order.id}) }}" onsubmit="return confirm('Annuler cette commande ?');">
                    <button type="submit">Annuler</button>
                </form>
                {% endif %}
            </td>
        </tr>
        {% endfor %}
    </tbody>
</table>
```

```php
// Dans OrderController::list() :
$orders = $orderRepository->findBy(
    ['user' => $this->getUser()],
    ['orderedAt' => 'DESC']
);

$ordersData = array_map(fn(Order $o) => [
    'order' => $o,
    'total' => $orderService->getOrderTotal($o),
], $orders);

return $this->render('profile/orders.html.twig', [
    'ordersData' => $ordersData,
    'status_labels' => [
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ],
]);
```

```php
// Dans OrderController::detail() :
$total = $orderService->getOrderTotal($order);

return $this->render('profile/order.html.twig', [
    'order' => $order,
    'total' => $total,
    'status_label' => $statusLabels[$order->getStatus()->value] ?? $order->getStatus()->value,
]);
```
