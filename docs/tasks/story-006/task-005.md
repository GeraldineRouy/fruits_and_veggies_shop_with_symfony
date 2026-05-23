# Tâche #005 - Story #006 : Tests automatisés

## Objectif

Implémenter les tests unitaires, d'intégration et E2E pour couvrir toute la fonctionnalité de passage de commande et suivi, conformément aux critères d'acceptation de la Story #006.

## Contexte

- Story #006 : `docs/stories/story-006.md`
- Dépend de : Tâche #001, Tâche #002, Tâche #003, Tâche #004
- Framework de test : PHPUnit 13 (attribut `#[Test]`)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Les tests doivent couvrir intégralement les 8 critères d'acceptation de la Story #006 :
1. Un client peut valider son panier et créer une commande (statut "confirmed")
2. Un email de confirmation est envoyé après la création de la commande
3. Le client peut voir la liste de ses commandes passées dans son profil
4. Le client peut voir le détail d'une commande (statut, produits, prix, date)
5. Les statuts évoluent : confirmed → preparing → shipped → delivered
6. Un email est envoyé à chaque changement de statut
7. Un client peut annuler sa commande si elle est encore au statut "confirmed"
8. Un admin peut annuler n'importe quelle commande, quel que soit son statut

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Unit/Service/OrderServiceTest.php` | Créer | Tests unitaires d'OrderService |
| `tests/Integration/Service/OrderServiceIntegrationTest.php` | Créer | Tests d'intégration conversion panier → commande |
| `tests/E2E/OrderProcessTest.php` | Créer | Test E2E (Playwright avec Panther ou Symfony BrowserKit) |

### Tests à implémenter

---

#### Tests unitaires — `tests/Unit/Service/OrderServiceTest.php`

Utiliser PHPUnit avec des mocks pour `EntityManagerInterface`, `CartService`, `MailerService`, `MessageBusInterface`.

##### Scénario 1 : Création d'une commande depuis un panier
- **Données** : User mocké, CartService mocké retournant un Cart avec 2 CartItems (Product A, Qté 2, Price 5.00 ; Product B, Qté 1, Price 3.50)
- **Résultat attendu** : Order créée avec 2 OrderLines, statut `confirmed`, `orderedAt` non null, `user` = User, total = 13.50
- **Vérifier** : CartService::clearCart() a été appelé, MailerService::sendOrderConfirmationEmail() a été appelé, MessageBusInterface::dispatch() a été appelé avec OrderStatusChanged

##### Scénario 2 : Calcul du total d'une commande
- **Données** : Order contenant 2 OrderLines (Product A price 5.00 × qty 2, Product B price 3.50 × qty 1)
- **Action** : `getOrderTotal($order)`
- **Résultat attendu** : Retourne `"13.50"`
- **Vérifier** : Le résultat est une string formatée avec 2 décimales

##### Scénario 3 : Total d'une commande sans lignes
- **Données** : Order sans OrderLine
- **Action** : `getOrderTotal($order)`
- **Résultat attendu** : Retourne `"0.00"`

##### Scénario 4 : Panier vide → exception
- **Données** : User mocké, CartService mocké retournant un Cart vide (getItems() = [])
- **Résultat attendu** : `InvalidArgumentException` avec le message "Votre panier est vide."

##### Scénario 5 : Transition de statut valide
- **Données** : Order mocké avec statut `confirmed`
- **Action** : `transitionStatus($order, OrderStatus::Preparing)`
- **Résultat attendu** : `$order->setStatus(OrderStatus::Preparing)` appelé, `MessageBusInterface::dispatch()` appelé

##### Scénario 6 : Transition de statut invalide
- **Données** : Order mocké avec statut `confirmed`
- **Action** : `transitionStatus($order, OrderStatus::Delivered)`
- **Résultat attendu** : `RuntimeException` avec message "Transition de statut invalide"
- **Vérifier** : `$order->setStatus()` n'a PAS été appelé

##### Scénario 7 : Enchaînement complet des statuts
- **Données** : Order mocké avec statut initial `confirmed`
- **Actions** : → `Preparing` → `Shipped` → `Delivered`
- **Résultat attendu** : Chaque transition réussit, statut final = `Delivered`

##### Scénario 8 : Annulation par client (statut confirmed)
- **Données** : Order mocké avec statut `confirmed`
- **Action** : `cancelOrder($order, isAdmin: false)`
- **Résultat attendu** : `$order->setStatus(OrderStatus::Cancelled)` appelé

##### Scénario 9 : Annulation par client (statut non-confirmed)
- **Données** : Order mocké avec statut `shipped`
- **Action** : `cancelOrder($order, isAdmin: false)`
- **Résultat attendu** : `RuntimeException` "Vous ne pouvez pas annuler une commande avec le statut shipped."

##### Scénario 10 : Annulation par admin (n'importe quel statut)
- **Données** : Order mocké avec statut `shipped`
- **Action** : `cancelOrder($order, isAdmin: true)`
- **Résultat attendu** : `$order->setStatus(OrderStatus::Cancelled)` appelé

##### Scénario 11 : Annulation par admin d'une commande déjà annulée
- **Données** : Order mocké avec statut `cancelled`
- **Action** : `cancelOrder($order, isAdmin: true)`
- **Résultat attendu** : `RuntimeException` "Cette commande est déjà annulée."

##### Scénario 12 : Email de confirmation envoyé après création
- **Données** : Mêmes données que Scénario 1
- **Vérifier** : `MailerService::sendOrderConfirmationEmail()` appelé avec l'Order créée

##### Scénario 13 : Email envoyé à chaque changement de statut
- **Données** : Order avec statut `confirmed`
- **Action** : transition vers `Preparing`
- **Vérifier** : `MailerService::sendOrderStatusChangeEmail()` appelé via le MessageHandler (dans le test unitaire, vérifier que `MessageBusInterface::dispatch()` est appelé — le handler est testé séparément)

---

#### Tests d'intégration — `tests/Integration/Service/OrderServiceIntegrationTest.php`

Utiliser une vraie base de données de test (via le `dbname_suffix: '_test%env(default::TEST_TOKEN)%'` configuré dans doctrine.yaml).

##### Scénario 1 : Conversion complète panier → commande
- **Données** : Créer un vrai User en base, un vrai Product en base, ajouter au panier via CartService
- **Action** : OrderService::createFromCart(user)
- **Vérifier** :
  - Une Order est en base avec statut `confirmed`
  - Des OrderLine sont en base liées à cette Order
  - Le panier est vide après conversion
  - Les prix correspondent aux prix des produits au moment de l'ajout

##### Scénario 2 : Enchaînement des statuts en base
- **Données** : Order créée en base avec statut `confirmed`
- **Actions** : transitionStatus → preparing → shipped → delivered
- **Vérifier** : En base, chaque appel met bien à jour le statut

##### Scénario 3 : Annulation par client
- **Données** : Order en base avec statut `confirmed`
- **Action** : cancelOrder(order, isAdmin: false)
- **Vérifier** : Statut en base = `cancelled`

##### Scénario 4 : Annulation par admin d'une commande livrée
- **Données** : Order en base avec statut `delivered`
- **Action** : cancelOrder(order, isAdmin: true)
- **Vérifier** : Statut en base = `cancelled`

---

#### Test E2E — `tests/E2E/OrderProcessTest.php`

Utiliser Symfony BrowserKit + Crawler (pas de Panther dans les dépendances). Le test simule un navigateur sans JavaScript.

```php
// Parcours complet (via WebTestCase + HttpClient) :
// 1. Créer un User en base avec un panier contenant des produits
// 2. POST /commande/valider (authentifié)
// 3. Suivre la redirection vers /profile/commande/{id}
// 4. Vérifier que le statut affiché est "Confirmée"
// 5. GET /profile/commandes
// 6. Vérifier que la commande apparaît dans la liste
// 7. POST /profile/commande/{id}/annuler
// 8. Suivre la redirection
// 9. Vérifier que le statut est "Annulée"
```

### Contraintes techniques

- **Framework** : PHPUnit 13 avec attribut `#[Test]` (pas d'annotation `@test`)
- **Bootstrap** : `tests/bootstrap.php` charge `.env` — les tests d'intégration utiliseront la base de test automatiquement
- **Fixtures** : pas de fixtures — créer les données directement dans les tests si nécessaire (créer User, Product, etc.)
- **Mocks** : utiliser `$this->createMock()` ou `$this->createStub()` de PHPUnit — pas de bibliothèque externe de mocking
- **E2E** : utiliser Symfony BrowserKit + Crawler via `WebTestCase`. Pas de Panther dans les dépendances — le test simule un navigateur sans JavaScript.
- **Nommage** : les méthodes de test doivent avoir des noms explicites en anglais (ou français si le projet a une convention — vérifier les tests existants)

### Spécificités des mocks

```php
// Exemple de setup pour OrderServiceTest
private function createOrderService(
    ?EntityManagerInterface $entityManager = null,
    ?CartService $cartService = null,
    ?MailerService $mailerService = null,
    ?MessageBusInterface $messageBus = null,
): OrderService {
    return new OrderService(
        entityManager: $entityManager ?? $this->createMock(EntityManagerInterface::class),
        cartService: $cartService ?? $this->createMock(CartService::class),
        mailerService: $mailerService ?? $this->createMock(MailerService::class),
        messageBus: $messageBus ?? $this->createMock(MessageBusInterface::class),
    );
}
```

### Documentation

Aucune documentation à créer pour les tests. Les tests sont leur propre documentation.
