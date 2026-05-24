# Tâche #002 - Story #013 : Tests automatisés et documentation

## Objectif

Implémenter les tests automatisés (unitaires, intégration, E2E) pour le flux de paiement simulé et l'écran de confirmation, et mettre à jour la documentation du processus de commande.

## Contexte

- Story #013 : `docs/stories/story-013.md`
- Dépend de : Tâche #001 (paiement + confirmation implémentés)
- Framework de test : PHPUnit 13 (attribut `#[Test]`), Playwright pour E2E

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Les tests doivent couvrir intégralement les 5 critères d'acceptation de la Story #013 :
1. Après validation du panier, un formulaire de paiement simulé s'affiche avec les champs pré-remplis et non modifiables
2. L'utilisateur clique sur "Payer" pour valider (pas de saisie nécessaire)
3. Après soumission, un écran de confirmation s'affiche avec : numéro de commande, récapitulatif produits, montant total, date
4. Un email de confirmation est envoyé (déjà couvert par Story #006 — ne pas re-tester)
5. Le client peut revenir à la page d'accueil depuis l'écran de confirmation

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Integration/Controller/CheckoutControllerTest.php` | Créer | Tests d'intégration du flux paiement + confirmation |
| `tests/E2E/checkout.spec.js` | Créer | Test E2E Playwright du parcours complet |

### Tests à implémenter

---

#### Tests d'intégration — `tests/Integration/Controller/CheckoutControllerTest.php`

Utiliser `WebTestCase` (WebTestAssertionsTrait) avec un client authentifié. Suivre le pattern existant des tests d'intégration : créer les données inline via l'EntityManager, nettoyer les tables dans `setUp()`.

```php
namespace App\Tests\Integration\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckoutControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Client $client;

    protected function setUp(): void;
    protected function tearDown(): void;
    private function createUser(): User;
    private function createProduct(string $name, string $price): Product;
    private function addItemToCart(User $user, Product $product, int $quantity): void;
}
```

##### Scénario 1 : Accès à la page paiement sans authentification
- **Données** : Client non connecté
- **Requête** : GET `/commande/paiement`
- **Résultat attendu** : Redirection 302 vers `/login`

##### Scénario 2 : Accès à la page paiement avec panier vide
- **Données** : Client connecté sans panier
- **Requête** : GET `/commande/paiement`
- **Résultat attendu** : Redirection 302 vers `/panier`, message flash `error`

##### Scénario 3 : La page paiement affiche le formulaire avec les champs pré-remplis
- **Données** : Client connecté avec 1 produit dans le panier
- **Requête** : GET `/commande/paiement`
- **Résultat attendu** : Statut 200, contient :
  - `4242 4242 4242 4242` (numéro de carte)
  - `12/28` (date d'expiration)
  - `123` (CVV)
  - `Payer` (bouton de soumission)
  - `disabled` ou `readonly` sur les champs (attribut HTML)

##### Scénario 4 : Soumission du formulaire de paiement crée la commande
- **Données** : Client connecté avec 2 produits dans le panier
- **Requête** : POST `/commande/paiement`
- **Résultat attendu** : Redirection 302 vers `/commande/confirmation/{id}`, message flash `success`
- **Vérifier** : Une commande est créée en base avec statut `confirmed`, le panier est vide

##### Scénario 5 : Accès à la page de confirmation sans authentification
- **Données** : Client non connecté, commande existante en base
- **Requête** : GET `/commande/confirmation/1`
- **Résultat attendu** : Redirection 302 vers `/login`

##### Scénario 6 : La page de confirmation affiche les bons détails
- **Données** : Client connecté avec une commande existante (2 produits)
- **Requête** : GET `/commande/confirmation/{id}`
- **Résultat attendu** : Statut 200, contient :
  - `#1` (numéro de commande)
  - Noms des produits
  - Prix unitaires et quantités
  - Total formaté en EUR
  - `Retour à l'accueil` (lien)
  - `Merci pour votre commande !`

##### Scénario 7 : Accès à la confirmation d'une commande d'un autre utilisateur
- **Données** : Utilisateur A connecté, commande appartenant à l'utilisateur B
- **Requête** : GET `/commande/confirmation/{id}`
- **Résultat attendu** : Statut 403 (AccessDenied)

##### Scénario 8 : Commande de confirmation introuvable
- **Données** : Client connecté
- **Requête** : GET `/commande/confirmation/99999`
- **Résultat attendu** : Statut 404

##### Scénario 9 : La page de confirmation a un lien "Retour à l'accueil"
- **Données** : Client connecté avec une commande
- **Requête** : GET `/commande/confirmation/{id}`
- **Résultat attendu** : Le contenu HTML contient un lien dont le `href` contient `path('app_home')`

##### Scénario 10 : Soumission du paiement avec panier vide (double soumission)
- **Données** : Client connecté avec un panier vide
- **Requête** : POST `/commande/paiement`
- **Résultat attendu** : Redirection 302 vers `/panier`, flash `error`

##### Fixtures nécessaires (créées inline dans `setUp()` ou chaque test)
- 1 utilisateur (User)
- 1 catégorie (Category)
- 2-3 produits (Product) avec prix différents
- Des Cart/CartItem selon les scénarios
- Des Order/OrderLine pour les scénarios de confirmation

**Méthode de nettoyage recommandée dans `tearDown()` :**
```php
$this->client->request('GET', '/logout');
$this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine')->execute();
$this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
$this->entityManager->createQuery('DELETE FROM App\Entity\CartItem')->execute();
$this->entityManager->createQuery('DELETE FROM App\Entity\Cart')->execute();
$this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
$this->entityManager->createQuery('DELETE FROM App\Entity\Category')->execute();
$this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
```

---

#### Test E2E — `tests/E2E/checkout.spec.js`

```javascript
// Scénario : Parcours complet de paiement et confirmation
// 1. Un utilisateur se connecte (via page /login)
// 2. Navigue vers la boutique et ajoute un produit au panier
// 3. Va sur la page panier
// 4. Clique sur "Valider la commande"
// 5. Vérifie que la page de paiement affiche les champs pré-remplis
// 6. Vérifie que les champs sont disabled/readonly
// 7. Clique sur "Payer"
// 8. Vérifie que la page de confirmation affiche "Merci pour votre commande !"
// 9. Vérifie que le numéro de commande est affiché
// 10. Vérifie que le récapitulatif des produits est affiché
// 11. Clique sur "Retour à l'accueil"
// 12. Vérifie que la page d'accueil est affichée
```

**Prérequis :** Les données de base doivent être présentes (1 produit, 1 catégorie, 1 utilisateur). Utiliser le même pattern que les tests E2E existants pour la création des données.

**Conseil d'implémentation :** Pour le test E2E Playwright, utiliser `test.step()` comme dans les autres tests E2E du projet (`tests/E2E/catalogue.spec.js`).

### Contraintes techniques

- **Framework** : PHPUnit 13 avec attribut `#[Test]`
- **Bootstrap** : `tests/bootstrap.php` charge `.env` — les tests d'intégration utilisent la base de test automatiquement
- **Client authentifié** : Utiliser `$client->loginUser($user)` de Symfony
- **Assertions** : Utiliser les méthodes d'assertion de Symfony (`assertResponseStatusCodeSame`, `assertRouteSame`, etc.)
- **Nommage** : Les méthodes de test doivent avoir des noms explicites en anglais (ou français selon la convention existante dans le projet — vérifier les tests existants)
- **E2E** : Playwright est déjà configuré (`playwright.config.js`). Ajouter le test dans `tests/E2E/checkout.spec.js`. Le test doit s'exécuter avec `npm run test:e2e`.
- **Pas de fixtures bundle** : Créer les données inline via l'EntityManager directement dans les tests

### Documentation

#### Mettre à jour `docs/features/order-process.md`

Remplacer l'étape 2-3 par le nouveau flux :

```markdown
## Déroulement

1. Le client connecté ajoute des produits à son panier (`/panier`)
2. Depuis la page panier, il clique sur **"Valider la commande"**
3. La page de paiement simulé s'affiche (`/commande/paiement`) avec les champs pré-remplis
4. Le client clique sur **"Payer"** (pas de saisie nécessaire)
5. La commande est créée avec le statut `confirmed`
6. Un email de confirmation est envoyé automatiquement
7. Le client est redirigé vers la page de confirmation (`/commande/confirmation/{id}`)
8. Depuis la confirmation, le client peut retourner à l'accueil ou voir ses commandes
```

### Exemples d'utilisation

```php
// Exemple de test d'intégration — suivre le pattern exact de CartControllerTest
#[Test]
public function payment_page_shows_prefilled_card_fields(): void
{
    $user = $this->createUser('buyer@test.com');
    $category = $this->createCategory('Fruits');
    $product = $this->createProduct('Pomme', '2.50', $category);
    $this->addItemToCart($user, $product, 2);
    $this->client->loginUser($user);

    $this->client->request('GET', '/commande/paiement');

    self::assertResponseIsSuccessful();
    self::assertSelectorExists('input[value="4242 4242 4242 4242"]');
    self::assertSelectorExists('input[value="12/28"]');
    self::assertSelectorExists('input[value="123"]');
    self::assertSelectorTextContains('button[type="submit"]', 'Payer');
}

#[Test]
public function payment_process_creates_order_and_redirects_to_confirmation(): void
{
    $user = $this->createUser('buyer2@test.com');
    $category = $this->createCategory('Fruits');
    $product = $this->createProduct('Banane', '1.80', $category);
    $this->addItemToCart($user, $product, 3);
    $this->client->loginUser($user);

    $this->client->request('POST', '/commande/paiement');

    self::assertResponseRedirects();
    $this->client->followRedirect();
    self::assertSelectorTextContains('h1', 'Merci pour votre commande');
}
```
