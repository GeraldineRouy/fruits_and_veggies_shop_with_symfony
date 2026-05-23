# Tâche #004 - Story #005 : Tests et documentation

## Objectif
Implémenter les tests automatisés (unitaires, intégration, E2E) pour la fonctionnalité panier, et documenter les routes et le service.

## Contexte
- Story #005 : `docs/stories/story-005.md`
- Dépend de : Tâche #002 (CartService), Tâche #003 (CartController + templates)
- Nécessaire pour : Rien (dernière tâche de la story)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer des tests complets pour la fonctionnalité panier : tests unitaires du CartService, tests d'intégration des routes du CartController, et un test E2E Playwright avec un scénario utilisateur lisible.

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Unit/Service/CartServiceTest.php` | Créer | Tests unitaires du CartService |
| `tests/Integration/Controller/CartControllerTest.php` | Créer | Tests d'intégration des routes panier |
| `tests/E2E/cart.spec.js` | Créer | Test E2E Playwright |
| `README.md` | Modifier | Ajouter documentation panier |

**Note** : Les tests d'intégration utilisent le pattern existant de fixtures inline (création des entités directement dans les tests via l'EntityManager), pas de `doctrine-fixtures-bundle`.

---

### Tests unitaires — CartService

**Fichier** : `tests/Unit/Service/CartServiceTest.php`

Utiliser PHPUnit avec un mock de `EntityManagerInterface` et `CartRepository`.

**Scénarios :**

1. **`getOrCreateCart crée un nouveau panier si l'utilisateur n'en a pas`**
   - Données : User sans Cart associé
   - Mock : `CartRepository::findOneByUser` retourne null
   - Attendu : Nouveau Cart créé, `EntityManager::persist` et `flush` appelés

2. **`getOrCreateCart retourne le panier existant`**
   - Données : User avec un Cart existant
   - Mock : `CartRepository::findOneByUser` retourne le Cart
   - Attendu : Le Cart retourné est celui du mock

3. **`addProduct ajoute un nouveau produit au panier`**
   - Données : User avec Cart vide, Product avec price = "12.50"
   - Attendu : CartItem créé avec quantity=1, price="12.50", associé au Cart

4. **`addProduct incrémente la quantité si le produit existe déjà`**
   - Données : User avec Cart contenant déjà Product (quantity=2)
   - Attendu : Quantité passe à 3 (2 + 1)

5. **`addProduct lève une exception si quantité < 1`**
   - Données : Product valide, quantity=0
   - Attendu : `\InvalidArgumentException`

6. **`updateItemQuantity modifie la quantité d'un CartItem`**
   - Données : CartItem avec quantity=3, nouvelle quantité=5
   - Attendu : quantity devient 5

7. **`updateItemQuantity supprime le CartItem si quantité = 0`**
   - Données : CartItem avec quantity=3, nouvelle quantité=0
   - Attendu : `EntityManager::remove` appelé sur le CartItem (via `removeItem`)

8. **`updateItemQuantity lève une exception si quantité < 0`**
   - Données : nouvelle quantité = -1
   - Attendu : `\InvalidArgumentException`

9. **`removeItem supprime le CartItem`**
   - Données : CartItem existant dans le panier
   - Attendu : `EntityManager::remove` appelé

12. **`clearCart supprime tous les items`**
    - Données : Cart avec 3 CartItems
    - Attendu : `EntityManager::remove` appelé 3 fois

13. **`getTotal retourne la somme correcte`**
    - Données : 2 items : (2 × 12.50) + (1 × 5.00) = 30.00
    - Attendu : "30.00"

14. **`getTotal retourne 0.00 pour un panier vide`**
    - Données : Panier sans items
    - Attendu : "0.00"

15. **`getProductCount retourne le nombre total d'articles`**
    - Données : 2 items avec quantités 3 et 2
    - Attendu : 5

16. **`getProductCount retourne 0 pour un panier vide`**
    - Données : Panier sans items
    - Attendu : 0

---

### Tests d'intégration — CartController

**Fichier** : `tests/Integration/Controller/CartControllerTest.php`

Utiliser `WebTestCase` (WebTestAssertionsTrait) avec un client authentifié.

**Scénarios :**

1. **`accès page panier sans authentification redirige vers login`**
   - Données : Client non connecté
   - Requête : GET `/panier`
   - Attendu : Redirection 302 vers `/login`

2. **`page panier affiche "Votre panier est vide" pour un nouveau utilisateur`**
   - Données : Client connecté sans panier
   - Requête : GET `/panier`
   - Attendu : Statut 200, contient "Votre panier est vide"

3. **`ajout d'un produit au panier`**
   - Données : Client connecté, produit existant en base (via fixtures)
   - Requête : POST `/panier/ajouter/1` avec quantity=2
   - Attendu : Redirection 302 vers `/panier`, message flash success

4. **`page panier affiche les produits ajoutés`**
   - Données : Client connecté avec 2 produits dans le panier
   - Requête : GET `/panier`
   - Attendu : Statut 200, contient les noms des produits et le total

5. **`modification quantité d'un item`**
   - Données : Client connecté avec un CartItem (quantity=2)
   - Requête : POST `/panier/modifier/{cartItemId}` avec formulaire UpdateCartItemType (quantity=5)
   - Attendu : Redirection 302, la quantité est mise à jour

6. **`suppression d'un item du panier`**
   - Données : Client connecté avec 1 CartItem
   - Requête : POST `/panier/supprimer/{cartItemId}`
   - Attendu : Redirection 302, l'item n'est plus dans le panier

7. **`vidage du panier`**
   - Données : Client connecté avec 3 items
   - Requête : POST `/panier/vider`
   - Attendu : Redirection 302, le panier est vide

8. **`ajout d'un produit inexistant retourne 404`**
   - Données : Client connecté
   - Requête : POST `/panier/ajouter/99999`
   - Attendu : Statut 404

**Fixtures nécessaires :**
- Au moins 2 produits (avec noms et prix différents)
- 1 utilisateur connecté
- Eventuellement un panier pré-rempli pour certains tests

---

### Test E2E — Playwright

**Fichier** : `tests/E2E/cart.spec.js`

**Scénario lisible :**

```javascript
// Scénario : Parcours complet du panier
// 1. Un utilisateur se connecte (via page /login)
// 2. Navigue vers la page d'accueil puis une catégorie
// 3. Ajoute un produit au panier depuis la liste de produits
// 4. Navigue vers la fiche produit
// 5. Ajoute un autre produit avec quantité 3 depuis la fiche
// 6. Vérifie le badge panier qui affiche 4 articles
// 7. Va sur la page panier
// 8. Vérifie les 2 produits avec leurs quantités et prix
// 9. Modifie la quantité du premier produit à 2
// 10. Vérifie que le total est recalculé
// 11. Supprime le second produit
// 12. Vérifie qu'il ne reste qu'un produit
// 13. Vide le panier
// 14. Vérifie le message "Votre panier est vide"
// 15. Vérifie que le badge panier affiche 0
```

**Prérequis :** Le test E2E nécessite que les données de base soient présentes (au moins 2 produits, 1 catégorie, 1 utilisateur). Ces données doivent être créées via des fixtures chargées avant le test (via une commande artisanale ou via l'interface web). Comme le projet n'a pas de fixtures bundle, l'approche recommandée : exécuter un script de seed en amont (`bin/console dbal:run-sql` multiple ou une commande Symfony custom) ou utiliser le même compte utilisateur créé par le test d'inscription. Alternative : s'appuyer sur les fixtures chargées via `bin/console doctrine:fixtures:load` si ajoutées ultérieurement.

---

### Documentation

**README.md** — Ajouter une section "Panier d'achat" :

```markdown
## Panier d'achat

Le panier est une fonctionnalité réservée aux utilisateurs connectés.

### Routes

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/panier` | Afficher le contenu du panier |
| POST | `/panier/ajouter/{id}` | Ajouter un produit (id = Product) |
| POST | `/panier/modifier/{id}` | Modifier la quantité (id = CartItem) |
| POST | `/panier/supprimer/{id}` | Supprimer un produit (id = CartItem) |
| POST | `/panier/vider` | Vider le panier |

### Service

`App\Service\CartService` — Documentation complète dans le code source.

Méthodes principales :
- `getOrCreateCart(User)` : Récupère ou crée le panier d'un utilisateur
- `addProduct(User, Product, int $quantity = 1)` : Ajoute un produit
- `updateQuantity(User, Product, int $quantity)` : Modifie la quantité
- `removeProduct(User, Product)` : Supprime un produit
- `clearCart(User)` : Vide le panier
- `getTotal(User) : string` : Calcule le total
- `getProductCount(User) : int` : Compte les articles
```

### Contraintes techniques
- **Tests unitaires** : Utiliser PHPUnit 13 avec `#[Test]` attribute. Coverage minimum 80% pour CartService
- **Tests d'intégration** : Utiliser `symfony/browser-kit` (déjà installé) avec `WebTestCase`. Suivre le pattern de `ShopControllerTest` : créer les entités inline dans chaque méthode de test via l'EntityManager, nettoyer les tables avec `createQuery('DELETE FROM ...')->execute()` dans `setUp()`
- **Base de test** : La config utilise `dbname_suffix: '_test'` — les migrations sont appliquées automatiquement via `tests/bootstrap.php`
- **Playwright** : Playwright est déjà configuré (`playwright.config.js`). Ajouter le test dans `tests/E2E/cart.spec.js`. Le test doit s'exécuter avec `npm run test:e2e`. Utiliser le pattern `test.step()` comme dans `catalogue.spec.js`.
- **Fixtures** : Utiliser le pattern inline existant (pas de `doctrine-fixtures-bundle`). Les tests d'intégration créent les données nécessaires directement via l'EntityManager. Au minimum : créer un utilisateur, 2-3 produits, et éventuellement un Cart pré-rempli.
- **Authentification dans les tests** : Pour les tests nécessitant un utilisateur connecté, utiliser `$client->loginUser($user)` de Symfony (disponible avec `WebTestCase`)
