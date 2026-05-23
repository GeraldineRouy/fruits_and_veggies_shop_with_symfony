# Tâche #004 - Story #006 : Administration des commandes

## Objectif

Créer l'interface d'administration des commandes permettant à un admin de lister toutes les commandes, changer leur statut, et annuler n'importe quelle commande quel que soit son statut.

## Contexte

- Story #006 : `docs/stories/story-006.md`
- Dépend de : Tâche #001 (OrderService), Tâche #002 (emails), Tâche #003 (templates de base)
- Nécessaire pour : Tâche #005 (tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux :**
- **Liste des commandes** : GET `/admin/commandes` affiche toutes les commandes (tous utilisateurs), triées par date décroissante, avec pagination
- **Détail d'une commande** : GET `/admin/commande/{id}` affiche le détail complet (comme la vue client mais avec formulaire de changement de statut)
- **Changement de statut** : POST `/admin/commande/{id}/statut` permet de passer au statut suivant via un formulaire avec sélection du statut cible
- **Annulation** : POST `/admin/commande/{id}/annuler` annule la commande quel que soit son statut (sauf déjà annulée)

**Cas limites :**
- Commande introuvable → 404
- Annulation d'une commande déjà annulée → flash error avec message
- Transition invalide (via OrderService) → flash error
- Admin non connecté → redirigé vers login (géré par access_control)

**Gestion d'erreurs :**
- `OrderService::transitionStatus()` lève `RuntimeException` → flash error
- `OrderService::cancelOrder()` lève `RuntimeException` → flash error
- Tentative de changer le statut d'une commande annulée → flash error

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/AdminController.php` | Créer | Contrôleur admin avec routes de gestion des commandes |
| `templates/admin/orders.html.twig` | Créer | Liste des commandes (admin) |
| `templates/admin/order.html.twig` | Créer | Détail + formulaire statut (admin) |
| `src/Service/PaginationService.php` | Modifier | Ajouter méthode `paginateQuery(QueryBuilder, page, limit)` |
| `config/packages/security.yaml` | Vérifier | Le préfixe `/admin` est déjà protégé (ROLE_ADMIN) |

### Signatures

```php
// Dans AdminController (si existe déjà, ajouter les méthodes ; sinon créer le fichier)

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/commandes', name: 'app_admin_orders', methods: ['GET'])]
    public function orders(
        OrderRepository $orderRepository,
        Request $request,
        PaginationService $paginationService,
        OrderService $orderService,
    ): Response;

    #[Route('/commande/{id}', name: 'app_admin_order_detail', methods: ['GET'])]
    public function orderDetail(Order $order): Response;

    #[Route('/commande/{id}/statut', name: 'app_admin_order_status', methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, OrderService $orderService): Response;

    #[Route('/commande/{id}/annuler', name: 'app_admin_order_cancel', methods: ['POST'])]
    public function cancelOrder(Order $order, OrderService $orderService): Response;
}
```

### Contraintes techniques

- **AdminController** : n'existe pas encore → créer le fichier. Pattern identique à `CartController`.
- **Pagination** : 
  - Étendre `PaginationService` avec une nouvelle méthode :
    ```php
    public function paginateQuery(
        QueryBuilder $qb,
        int $page = 1,
        int $limit = 12,
    ): array {
        $query = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();
    
        $paginator = new Paginator($query);
    
        return $this->paginate($paginator, $page, $limit);
    }
    ```
    Ajouter l'import : `use Doctrine\ORM\QueryBuilder;`
  - Dans `AdminController::orders()`, récupérer `$page` du `Request`, construire une `QueryBuilder` depuis `OrderRepository`, appeler `$paginationService->paginateQuery($qb, $page)`.
- **Formulaire de changement de statut** : utiliser un formulaire POST simple avec un champ `<select>` des statuts possibles et un bouton "Mettre à jour". Ne pas créer de form Symfony pour simplifier ; utiliser un formulaire manuel dans le template avec `name="status"`.
- **Validation côté serveur** : vérifier que le statut reçu est un `OrderStatus` valide avant d'appeler `transitionStatus()`. Utiliser `OrderStatus::tryFrom($request->request->get('status'))`.
- **Sécurité** : sur les méthodes `orderDetail`, `updateStatus`, `cancelOrder`, vérifier que l'utilisateur a le rôle ADMIN via `#[IsGranted]` sur la classe (pas besoin de vérification supplémentaire).

### Structure des templates

#### `templates/admin/orders.html.twig`
- Étend `base.html.twig`
- Titre : "Gestion des commandes"
- Tableau avec colonnes : N°, Client (email), Date, Statut, Total, Actions (Voir)
- Le total est calculé dans le contrôleur via `$orderService->getOrderTotal($order)` et passé dans les données paginées
- Pagination en bas via les variables `hasPrevious`, `hasNext`, `currentPage`, `totalPages` du `PaginationService`
- Statut avec badge coloré (même code que tâche #003)

#### `templates/admin/order.html.twig`
- Étend `base.html.twig`
- En-tête : N° commande, Client (email + nom), Date, Statut (badge)
- Tableau des lignes de commande (comme la vue client)
- Total (via `total|format_currency('EUR')` passé par le contrôleur)
- Section "Changer le statut" : formulaire avec `<select>` des statuts cibles (confirmed → preparing → shipped → delivered, mais aussi possibilité de revenir en arrière pour l'admin? L'énoncé ne le précise pas — on autorise uniquement les transitions valides, pas de retour en arrière.)
  - Sélecteur avec les statuts disponibles pour la transition
  - Bouton "Mettre à jour"
- Section "Annuler la commande" : bouton POST vers `app_admin_order_cancel` avec confirmation JavaScript

### Documentation

Mettre à jour `docs/features/order-process.md` (créé dans tâche #003) avec la section administration :
- Accès admin à la gestion des commandes
- Possibilité de changer le statut et d'annuler n'importe quelle commande

### Tests à implémenter

Les tests sont dans la Tâche #005.
