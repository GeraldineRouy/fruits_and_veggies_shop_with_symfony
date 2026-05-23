# Tâche #002 - Story #006 : Emails de commande

## Objectif

Ajouter les méthodes d'envoi d'email pour les commandes dans `MailerService`, créer les templates email associés (HTML et TXT), et mettre en place un Message/Handler asynchrone pour l'envoi des notifications de changement de statut via Symfony Messenger.

## Contexte

- Story #006 : `docs/stories/story-006.md`
- Dépend de : Tâche #001 (OrderService utilise MessageBusInterface et dispatche OrderStatusChanged)
- Nécessaire pour : Tâche #003, Tâche #004, Tâche #005

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Cas nominaux :**
- Un email de confirmation est envoyé après la création d'une commande (à : `user.email`, sujet : "Confirmation de votre commande #ID")
- Un email est envoyé à chaque changement de statut (à : `user.email`, sujet : "Votre commande #ID est maintenant {statut}")
- L'email contient le numéro de commande, la liste des produits, les quantités, les prix, le total, le statut actuel
- Le mécanisme d'envoi est asynchrone via Messenger pour ne pas bloquer la réponse HTTP

**Cas limites :**
- Si l'utilisateur n'a pas d'email (cas improbable car requis), ne pas envoyer
- Si la commande n'a pas de lignes (ne devrait pas arriver), envoyer quand même l'email avec un message générique

**Gestion d'erreurs :**
- Erreur d'envoi d'email → gérée par Messenger (retry 3x avec multiplier 2) — ne pas catcher dans le handler, laisser le retry framework gérer
- Commande introuvable dans le handler → logger un warning et ignorer (ne pas relancer)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Service/MailerService.php` | Modifier | Ajouter `sendOrderConfirmationEmail()` et `sendOrderStatusChangeEmail()` |
| `src/Message/OrderStatusChanged.php` | Créer | Message asynchrone pour changement de statut |
| `src/MessageHandler/OrderStatusChangedHandler.php` | Créer | Handler Messenger qui envoie l'email |
| `templates/email/order_confirmation.html.twig` | Créer | Template HTML confirmation |
| `templates/email/order_confirmation.txt.twig` | Créer | Template TXT confirmation |
| `templates/email/order_status_change.html.twig` | Créer | Template HTML changement statut |
| `templates/email/order_status_change.txt.twig` | Créer | Template TXT changement statut |
| `config/packages/messenger.yaml` | Modifier | Router `OrderStatusChanged` vers le transport async |

### Signatures

```php
// Dans MailerService :

/**
 * Envoie un email de confirmation après création de commande.
 */
public function sendOrderConfirmationEmail(Order $order): void;

/**
 * Envoie un email de notification de changement de statut.
 */
public function sendOrderStatusChangeEmail(Order $order): void;
```

```php
// Nouveau fichier : src/Message/OrderStatusChanged.php

namespace App\Message;

class OrderStatusChanged
{
    public function __construct(
        private readonly int $orderId,
    ) {}

    public function getOrderId(): int;
}
```

```php
// Nouveau fichier : src/MessageHandler/OrderStatusChangedHandler.php

namespace App\MessageHandler;

use App\Message\OrderStatusChanged;
use App\Repository\OrderRepository;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class OrderStatusChangedHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly MailerService $mailerService,
        private readonly LoggerInterface $logger,
    );

    public function __invoke(OrderStatusChanged $message): void;
}
```

### Contraintes techniques

- **MailerService** : suivre le pattern exact des méthodes existantes (`sendValidationEmail`, `sendPasswordResetEmail`). Utiliser `TemplatedEmail`, `Address`, mêmes `from`, mêmes conventions de nommage.
- **Templates email** : étendre `email/base.html.twig` (même structure que les templates existants `email/validation.html.twig`). Le TXT doit contenir les mêmes infos sans HTML.
- **Sujets des emails** :
  - Confirmation : "Confirmation de votre commande #N — Fruits & Veggies Shop"
  - Changement statut : "Votre commande #N est {statut en français} — Fruits & Veggies Shop"
- **Traduction des statuts** : dans le template, mapper les statuts enum vers du français :
  - `confirmed` → "confirmée"
  - `preparing` → "en préparation"
  - `shipped` → "expédiée"
  - `delivered` → "livrée"
  - `cancelled` → "annulée"
- **Messenger** : créer un message `OrderStatusChanged` contenant l'ID de commande. Le handler charge la commande depuis le repository et appelle `MailerService::sendOrderStatusChangeEmail()`.
- **OrderService (tâche #001)** : ne dispatche pas le message directement. Le changement de statut doit dispatcher `OrderStatusChanged` via `MessageBusInterface` dans `transitionStatus()` et `cancelOrder()`. Tu modifies donc aussi `OrderService` pour injecter `MessageBusInterface` et dispatcher le message après chaque changement de statut.
  - Pour la confirmation (`createFromCart`), tu dispatch aussi `OrderStatusChanged` après création.

**Note :** `MessageBusInterface` est déjà injecté dans le constructeur d'`OrderService` (via la tâche #001). La tâche #002 n'a pas à modifier OrderService, sauf si le dispatch n'a pas été fait — dans ce cas, ajouter `$this->messageBus->dispatch(new OrderStatusChanged((int) $order->getId()))` après chaque changement de statut (vérifier dans le code de T001).

- **Configuration Messenger** : ajouter dans `config/packages/messenger.yaml` sous `routing` :

```yaml
'App\Message\OrderStatusChanged': async
```

Ainsi qu'une règle `when@dev` pour le passer en sync en développement :

```yaml
when@dev:
    framework:
        messenger:
            routing:
                'App\Message\OrderStatusChanged': sync
```

### Tests à implémenter

Les tests pour les emails sont dans la Tâche #005. Cette tâche ne génère que le code de production.

### Exemples de templates

```twig
{# templates/email/order_confirmation.html.twig #}
{% extends 'email/base.html.twig' %}

{% block subject %}Confirmation de votre commande #{{ order.id }}{% endblock %}

{% block body %}
<h2>Merci pour votre commande !</h2>
<p>Bonjour {{ order.user.firstName }},</p>
<p>Votre commande #{{ order.id }} a bien été confirmée.</p>
{# ... lignes de commande, total, etc. #}
{% endblock %}
```

```twig
{# templates/email/order_status_change.html.twig #}
{% extends 'email/base.html.twig' %}

{% block subject %}Votre commande #{{ order.id }} a changé de statut{% endblock %}

{% block body %}
<h2>Votre commande a changé de statut</h2>
<p>Bonjour {{ order.user.firstName }},</p>
<p>Votre commande #{{ order.id }} est maintenant <strong>{{ status_label }}</strong>.</p>
{% endblock %}
```
