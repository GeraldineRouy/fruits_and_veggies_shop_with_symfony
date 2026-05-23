# Tâche #001 - Story #005 : Entités Cart et CartItem

## Objectif
Créer les entités Doctrine `Cart` et `CartItem` avec leurs relations, et générer la migration de base de données.

## Contexte
- Story #005 : `docs/stories/story-005.md`
- Dépend de : Story #002 (entités Product, User existantes)
- Nécessaire pour : Tâche #002 (CartService), Tâche #003 (CartController)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer le modèle de données du panier d'achat. Un panier appartient à un utilisateur connecté (relation OneToOne). Un panier contient plusieurs lignes (CartItem), chacune référençant un produit avec une quantité et un prix unitaire (prix figé au moment de l'ajout).

**Cas nominaux :**
- Un `Cart` est créé automatiquement quand un utilisateur connecté ajoute son premier produit (le constructeur seul ne le crée pas, c'est le CartService qui le fait)
- Un `CartItem` associe un produit, une quantité et le prix unitaire au moment de l'ajout
- Un `Cart` peut avoir 0 ou plusieurs `CartItem`s
- La relation avec `User` est OneToOne : un utilisateur a un seul panier actif

**Cas limites :**
- Un `Cart` sans `CartItem` (panier vide) — l'entité existe mais n'a pas de lignes
- Quantité minimum : 1 (ne pas descendre en dessous)
- Prix unitaire stocké en `decimal(10,2)` comme le prix du `Product`

**Gestion d'erreurs :**
- Tentative d'ajout d'un produit inexistant → `\InvalidArgumentException`
- Tentative de quantité <= 0 → `\InvalidArgumentException`
- Un utilisateur déjà connecté sans panier → le CartService en crée un à la demande

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Entity/Cart.php` | Créer | Entité Cart |
| `src/Entity/CartItem.php` | Créer | Entité CartItem |
| `src/Repository/CartRepository.php` | Créer | Repository Cart |
| `src/Repository/CartItemRepository.php` | Créer | Repository CartItem |
| `src/Entity/User.php` | Modifier | Ajouter la relation OneToOne vers Cart |
| `migrations/` | Créer | Nouvelle migration |

### Signatures

```php
// src/Entity/Cart.php
#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY'), ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'cart')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'cart', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct();
    public function getId(): ?int;
    public function getUser(): ?User;
    public function setUser(User $user): self;
    public function getItems(): Collection;
    public function addItem(CartItem $item): self;
    public function removeItem(CartItem $item): self;
}

// src/Entity/CartItem.php
#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY'), ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantity = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    public function __construct();
    public function getId(): ?int;
    public function getCart(): ?Cart;
    public function setCart(?Cart $cart): self;
    public function getProduct(): ?Product;
    public function setProduct(?Product $product): self;
    public function getQuantity(): ?int;
    public function setQuantity(int $quantity): self;
    public function getPrice(): ?string;
    public function setPrice(string $price): self;
}
```

### Contraintes techniques
- **ORM** : Doctrine attributes, `cascade: ['persist', 'remove']` sur Cart → CartItem, `orphanRemoval: true`
- **Types** : `decimal(10,2)` pour les prix (string en PHP), `integer` pour quantité
- **User** : Ajouter la propriété `cart` avec `#[ORM\OneToOne(targetEntity: Cart::class, mappedBy: 'user')]` et initialisation `null` par défaut
- **Migration** : Générer avec `bin/console make:migration` puis exécuter
- **Style** : Respecter le code existant (getters/setters avec nullables, fluent setters, `declare(strict_types=1)`)

### Tests associés
- Pas de tests séparés pour cette tâche — les entités sont testées via la Tâche #004 (tests CartService)

### Documentation
- Rien à documenter pour cette tâche (entités internes)
