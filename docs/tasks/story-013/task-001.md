# Tâche #001 - Story #013 : Paiement simulé et écran de confirmation

## Objectif

Ajouter un formulaire de paiement simulé entre la validation du panier et la création de la commande, ainsi qu'un écran de confirmation post-paiement. Modifier le flux existant pour passer par cette nouvelle étape.

## Contexte

- Story #013 : `docs/stories/story-013.md`
- Dépend de : Story #005 (panier), Story #006 (OrderService, CartService, emails)
- Nécessaire pour : Tâche #002 (tests)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

**Nouveau flux :**
1. Depuis la page panier, le bouton "Valider la commande" redirige vers le formulaire de paiement (GET `/commande/paiement`)
2. Le formulaire affiche des données de carte bancaire pré-remplies et non modifiables
3. L'utilisateur clique sur "Payer" (POST `/commande/paiement`) — pas de saisie nécessaire
4. La commande est créée via `CartService::cartToOrder()` / `OrderService::createFromCart()`, l'email de confirmation est envoyé
5. Redirection vers l'écran de confirmation (GET `/commande/confirmation/{id}`)
6. L'écran de confirmation affiche le récapitulatif et un lien vers l'accueil

**Cas nominaux :**
- Accès à `/commande/paiement` avec un panier non vide → affiche le formulaire avec les champs pré-remplis
- Soumission du formulaire → commande créée, redirection vers `/commande/confirmation/{id}`
- Page de confirmation → affiche numéro de commande, récapitulatif produits, montant total, date
- Lien "Retour à l'accueil" depuis la confirmation → redirige vers `app_home`
- Le bouton "Valider la commande" sur la page panier devient un lien GET vers `/commande/paiement`

**Cas limites :**
- Accès à `/commande/paiement` avec un panier vide → rediriger vers le panier avec flash error
- Accès direct à `/commande/confirmation/{id}` avec une commande qui n'appartient pas à l'utilisateur → 403
- Commande de confirmation introuvable → 404
- Soumission du formulaire de paiement alors que le panier a été vidé entre-temps (double-click, onglet parallèle) → géré par `OrderService::createFromCart()` qui lève `InvalidArgumentException`

**Gestion d'erreurs :**
- Panier vide → flash error + redirection `app_cart_index`
- Erreur de création de commande (exception) → flash error + redirection `app_cart_index`
- Commande non trouvée → 404 (ParamConverter)
- Commande d'un autre utilisateur → `createAccessDeniedException()`

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/OrderController.php` | Modifier | Ajouter les routes paiement et confirmation |
| `templates/checkout/payment.html.twig` | Créer | Formulaire de paiement simulé |
| `templates/checkout/confirmation.html.twig` | Créer | Écran de confirmation de commande |
| `templates/cart/index.html.twig` | Modifier | Changer le bouton "Valider" en lien GET |
| *(déjà présent)* `config/packages/security.yaml` | Aucune | La règle `{ path: ^/commande, roles: ROLE_USER }` existe déjà à la ligne 39 |

### Signatures

```php
// À ajouter dans OrderController.php

/**
 * Affiche le formulaire de paiement simulé.
 * Vérifie que le panier n'est pas vide avant d'afficher le formulaire.
 */
#[Route('/commande/paiement', name: 'app_order_payment', methods: ['GET'])]
public function payment(CartService $cartService): Response
{
    // Vérifier que le panier n'est pas vide
    // Récupérer le total et le nombre d'articles
    // Afficher le template checkout/payment.html.twig
}

/**
 * Traite le paiement simulé et crée la commande.
 * Redirige vers la page de confirmation.
 */
#[Route('/commande/paiement', name: 'app_order_payment_process', methods: ['POST'])]
public function processPayment(CartService $cartService): Response
{
    // Appeler cartService->cartToOrder($user)
    // Flash success
    // Rediriger vers app_order_confirmation
}

/**
 * Affiche l'écran de confirmation après paiement.
 * Vérifie que la commande appartient à l'utilisateur connecté.
 */
#[Route('/commande/confirmation/{id}', name: 'app_order_confirmation', methods: ['GET'])]
public function confirmation(Order $order, OrderService $orderService): Response
{
    // Vérifier que $order->getUser() === $this->getUser()
    // Calculer le total avec $orderService->getOrderTotal($order)
    // Afficher le template checkout/confirmation.html.twig
}
```

### Contraintes techniques

- **Route `/commande/valider` existante** : Supprimer la méthode `checkout()` et sa route `app_order_checkout` (POST `/commande/valider`). Le bouton "Valider la commande" sur la page panier pointe désormais directement vers GET `/commande/paiement` (`app_order_payment`). Aucune backward compatibility nécessaire.
- **OrdreController** : Ajouter les 3 nouvelles méthodes dans `OrderController` existant. L'attribut `#[IsGranted('ROLE_USER')]` sur la classe protège déjà toutes les méthodes.
- **ParamConverter** : La route `/commande/confirmation/{id}` utilise le ParamConverter implicite de Doctrine pour charger l'entité `Order`. Vérifier l'appartenance dans la méthode.
- **Sécurité** : La règle `{ path: ^/commande, roles: ROLE_USER }` existe déjà dans `config/packages/security.yaml` (ligne 39). Aucune modification nécessaire.
- **CartService** : La méthode `cartToOrder(User $user)` existe déjà et appelle `OrderService::createFromCart()`. Utiliser cette méthode dans `processPayment()`.
- **Flux** : Dans `payment()`, utiliser `CartService::getItems()` pour vérifier que le panier n'est pas vide, et `CartService::getTotal()` + `CartService::getProductCount()` pour passer les informations au template.

### Structure des templates

#### `templates/checkout/payment.html.twig`
- Étend `base.html.twig`
- Titre : "Paiement"
- Affiche un récapitulatif rapide du panier (nombre d'articles, total)
- Formulaire POST vers `app_order_payment_process` :
  - Champ "Numéro de carte" : pré-rempli avec `4242 4242 4242 4242`, `disabled`, `readonly`
  - Champ "Date d'expiration" : pré-rempli avec `12/28`, `disabled`, `readonly`
  - Champ "CVV" : pré-rempli avec `123`, `disabled`, `readonly`
  - Bouton "Payer" (submit, stylé succès/vert)
  - Aucune validation requise (pas de champ requis, pas de JS)
- Lien "Retour au panier" vers `app_cart_index`

```twig
{% extends 'base.html.twig' %}

{% block title %}Paiement - Fruits & Veggies{% endblock %}

{% block body %}
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Paiement</h1>

    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <p class="text-gray-700 mb-4">
            Récapitulatif : <strong>{{ productCount }} article(s)</strong> —
            Total : <strong class="text-brand-600">{{ total|format_currency('EUR') }}</strong>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="post" action="{{ path('app_order_payment_process') }}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de carte</label>
                    <input type="text" value="4242 4242 4242 4242" disabled readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                </div>
                <div class="flex space-x-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                        <input type="text" value="12/28" disabled readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                        <input type="text" value="123" disabled readonly
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="w-full bg-brand-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-brand-700 transition-colors text-lg">
                    Payer {{ total|format_currency('EUR') }}
                </button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ path('app_cart_index') }}" class="text-sm text-gray-600 hover:text-gray-800 transition-colors">
                &larr; Retour au panier
            </a>
        </div>
    </div>
</div>
{% endblock %}
```

#### `templates/checkout/confirmation.html.twig`
- Étend `base.html.twig`
- Titre : "Commande confirmée !"
- Message de succès : "Merci pour votre commande !"
- Affiche :
  - Numéro de commande : `#{{ order.id }}`
  - Date : `{{ order.orderedAt|date('d/m/Y H:i') }}`
  - Récapitulatif des produits (tableau : Produit, Prix unitaire, Quantité, Total)
  - Montant total
- Lien "Retour à l'accueil" vers `app_home` (bouton principal)
- Lien "Voir mes commandes" vers `app_order_list` (secondaire)

```twig
{% extends 'base.html.twig' %}

{% block title %}Commande confirmée - Fruits & Veggies{% endblock %}

{% block body %}
<div class="max-w-3xl mx-auto text-center">
    <div class="bg-white rounded-xl shadow-md p-8 mb-6">
        <div class="text-6xl mb-4">🎉</div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Merci pour votre commande !</h1>
        <p class="text-gray-600 mb-6">Votre commande <strong>#{{ order.id }}</strong> a bien été confirmée.</p>
        <p class="text-sm text-gray-500 mb-8">Date : {{ order.orderedAt|date('d/m/Y H:i') }}</p>

        <div class="text-left border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Récapitulatif</h2>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Produit</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Prix unitaire</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Quantité</th>
                        <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    {% for line in order.orderLines %}
                        <tr>
                            <td class="px-4 py-3 text-gray-800">{{ line.product.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ line.price|format_currency('EUR') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ line.quantity }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ (line.price * line.quantity)|format_currency('EUR') }}</td>
                        </tr>
                    {% endfor %}
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-800">Total :</td>
                        <td class="px-4 py-3 font-bold text-brand-600">{{ total|format_currency('EUR') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="{{ path('app_home') }}" class="bg-brand-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-brand-700 transition-colors">
            Retour à l'accueil
        </a>
        <a href="{{ path('app_order_list') }}" class="text-brand-600 hover:text-brand-800 transition-colors font-medium">
            Voir mes commandes
        </a>
    </div>
</div>
{% endblock %}
```

#### `templates/cart/index.html.twig` — modification
Remplacer le formulaire POST vers `app_order_checkout` par un lien GET vers `app_order_payment` :

```twig
{# Avant (lignes 30-31) : #}
<form method="post" action="{{ path('app_order_checkout') }}">
    <button type="submit" class="btn btn-success ...">Valider la commande</button>
</form>

{# Après : #}
<a href="{{ path('app_order_payment') }}" class="btn inline-block bg-brand-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-brand-700 transition-colors">
    Valider la commande
</a>
```

#### `src/Controller/OrderController.php` — modifications

1. **Supprimer** la méthode `checkout()` existante et sa route `app_order_checkout` (POST `/commande/valider`). Elle n'est plus utilisée — le bouton "Valider la commande" du panier pointe maintenant vers GET `/commande/paiement`.

2. Ajouter les 3 nouvelles méthodes `payment()`, `processPayment()`, `confirmation()` avec leur logique.

### Tests à implémenter

Voir Tâche #002 pour les tests complets.

### Documentation

Mettre à jour `docs/features/order-process.md` (créé dans Story #006) pour refléter le nouveau flux :
1. Panier → clic "Valider la commande" → page paiement
2. Paiement simulé → clic "Payer" → commande créée
3. Redirection vers écran de confirmation
4. Email de confirmation envoyé (inchangé)

### Exemples d'utilisation

```php
// OrdreController::payment()
$user = $this->getUser();
$items = $cartService->getItems($user);

if (empty($items)) {
    $this->addFlash('error', 'Votre panier est vide.');
    return $this->redirectToRoute('app_cart_index');
}

return $this->render('checkout/payment.html.twig', [
    'productCount' => $cartService->getProductCount($user),
    'total' => $cartService->getTotal($user),
]);
```

```php
// OrderController::processPayment()
try {
    $user = $this->getUser();
    $order = $cartService->cartToOrder($user);
    $this->addFlash('success', 'Votre commande a été confirmée. Un email de confirmation vous a été envoyé.');
    return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
} catch (InvalidArgumentException $e) {
    $this->addFlash('error', $e->getMessage());
    return $this->redirectToRoute('app_cart_index');
}
```

```php
// OrderController::confirmation()
if ($order->getUser() !== $this->getUser()) {
    throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
}

return $this->render('checkout/confirmation.html.twig', [
    'order' => $order,
    'total' => $orderService->getOrderTotal($order),
]);
```
