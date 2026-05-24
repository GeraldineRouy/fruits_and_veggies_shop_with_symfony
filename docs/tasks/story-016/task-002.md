# Tâche #002 - Story #016 : Gestion d'erreurs robuste dans le flux de paiement

## Objectif

Améliorer `OrderController::processPayment()` pour capturer toutes les exceptions et afficher un message d'erreur explicite à l'utilisateur au lieu d'une page d'erreur 500. Actuellement, seules les `InvalidArgumentException` sont capturées ; les erreurs Doctrine (comme `UniqueConstraintViolationException`) ne sont pas gérées.

## Contexte

- Story #016 : `docs/stories/story-016.md`
- Dépend de : Tâche #001 (correction de la séquence — le bug racine doit être corrigé d'abord)
- Nécessaire pour : Tâche #003 (tests d'intégration)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

`processPayment()` dans `OrderController` doit capturer toute exception survenue lors de la création de la commande et afficher un message d'erreur compréhensible, plutôt que de laisser l'exception remonter jusqu'à Symfony qui retourne une page 500.

**Cas nominaux :**
- Soumission du formulaire avec panier non vide → commande créée → redirection confirmation (inchangé)

**Cas limites :**
- Panier vide au moment de la soumission (double-clic, onglet parallèle) → flash error + redirection panier (inchangé, déjà géré par `InvalidArgumentException`)
- Erreur Doctrine (`UniqueConstraintViolationException`, `DBALException`, etc.) → flash error générique + redirection panier
- Erreur inattendue (`RuntimeException`, `Throwable`) → flash error générique + redirection panier
- Toute autre exception → redirection vers page panier avec message explicite

**Gestion d'erreurs :**
- `InvalidArgumentException` → "Votre panier est vide." (inchangé)
- `\Throwable` (toute autre erreur) → "Une erreur est survenue lors du paiement. Veuillez réessayer."
- Logger l'erreur avec `LoggerInterface` pour diagnostic

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/OrderController.php` | Modifier | Élargir le catch dans `processPayment()` |
| `src/Service/OrderService.php` | Modifier | Ajouter le logging via LoggerInterface |

### Signatures

```php
// OrdreController.php — catch élargi
#[Route('/commande/paiement', name: 'app_order_payment_process', methods: ['POST'])]
public function processPayment(
    CartService $cartService,
    LoggerInterface $logger,
): Response
{
    try {
        $order = $cartService->cartToOrder($this->getUser());
        $this->addFlash('success', 'Votre commande a été confirmée. Un email de confirmation vous a été envoyé.');
        return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
    } catch (InvalidArgumentException $e) {
        $this->addFlash('error', $e->getMessage());
    } catch (\Throwable $e) {
        $logger->error('Erreur lors du paiement : ' . $e->getMessage(), [
            'exception' => $e,
            'user_id' => $this->getUser()?->getId(),
        ]);
        $this->addFlash('error', 'Une erreur est survenue lors du paiement. Veuillez réessayer.');
    }

    return $this->redirectToRoute('app_cart_index');
}
```

### Contraintes techniques

- **Framework** : Symfony 8.0, ne pas modifier l'import existant — ajouter l'import manquant pour `Psr\Log\LoggerInterface`
- **Pattern** : Utiliser le logging déjà utilisé dans le projet (`LoggerInterface`) comme dans `OrderStatusChangedHandler`
- **Message utilisateur** : Le message d'erreur doit être explicite mais pas technique (ne pas exposer les détails de l'exception à l'utilisateur)
- **Sécurité** : Ne jamais afficher le message d'exception brute à l'utilisateur (risque d'information disclosure)
- **Messages flash** : Utiliser `$this->addFlash('error', ...)` — le template base.html.twig affiche déjà les messages flash dans le projet

### Tests à implémenter

Voir Tâche #003 pour les tests complets.

### Documentation

Aucune documentation utilisateur nécessaire. Mettre à jour `docs/features/order-process.md` si nécessaire pour mentionner le comportement en cas d'erreur.

### Exemples d'utilisation

```php
// Avant
try {
    $order = $cartService->cartToOrder($this->getUser());
    $this->addFlash('success', 'Votre commande a été confirmée. Un email de confirmation vous a été envoyé.');
    return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
} catch (InvalidArgumentException $e) {
    $this->addFlash('error', $e->getMessage());
    return $this->redirectToRoute('app_cart_index');
}

// Après
try {
    $order = $cartService->cartToOrder($this->getUser());
    $this->addFlash('success', 'Votre commande a été confirmée. Un email de confirmation vous a été envoyé.');
    return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
} catch (InvalidArgumentException $e) {
    $this->addFlash('error', $e->getMessage());
} catch (\Throwable $e) {
    $logger->error('Erreur lors du paiement', ['exception' => $e, 'user_id' => $this->getUser()?->getId()]);
    $this->addFlash('error', 'Une erreur est survenue lors du paiement. Veuillez réessayer.');
}
return $this->redirectToRoute('app_cart_index');
```
