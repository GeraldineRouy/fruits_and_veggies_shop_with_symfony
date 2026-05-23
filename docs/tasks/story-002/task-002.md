# Tâche #002 - Story #002 : Entités Product, Order et OrderLine

## Objectif
Créer les entités Doctrine `Product`, `Order` et `OrderLine` avec leurs Repository, incluant les relations ManyToMany (Product ↔ Category), ManyToOne (Order → User, OrderLine → Product) et OneToMany (Order → OrderLine).

## Contexte
- Story #002 : `docs/stories/story-002.md`
- Spécification : `docs/specification/specification.md`
- Dépend de : Tâche #001 (User et Category doivent exister)
- Nécessaire pour : Tâche #003 (migration, tests, documentation)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer les entités `Product`, `Order` et `OrderLine` avec leurs Repository et leurs relations :

**Product → Category : ManyToMany**
- Un produit peut appartenir à plusieurs catégories
- Une catégorie peut contenir plusieurs produits
- Le côté propriétaire est Product (avec `inversedBy: 'products'`)
- Le côté inverse est Category (avec `mappedBy: 'categories'` déjà amorcé dans task-001)

**Order → User : ManyToOne**
- Une commande appartient à un seul utilisateur
- Un utilisateur peut avoir plusieurs commandes (relation inverse à ajouter dans User)

**Order → OrderLine : OneToMany**
- Une commande peut avoir plusieurs lignes
- Une ligne appartient à une seule commande

**OrderLine → Product : ManyToOne**
- Une ligne de commande référence un seul produit
- Un produit peut apparaître dans plusieurs lignes de commande

**Cas nominaux :**
- L'entité Product a tous les champs (name, description, image, price) + la relation ManyToMany vers Category
- L'entité Order a tous les champs (user, orderedAt, status) + la relation OneToMany vers OrderLine
- L'entité OrderLine a tous les champs (order, quantity, price, product) + la relation ManyToOne vers Order et Product
- Le champ `status` de Order est une string avec les valeurs autorisées via un backed enum PHP

**Cas limites :**
- `price` (Product et OrderLine) : type `decimal` dans la base pour éviter les erreurs d'arrondi des floats (précision 10, échelle 2)
- `orderedAt` : valeur par défaut `new \DateTimeImmutable()` dans le constructeur
- `status` : doit être initialisé à `OrderStatus::Confirmed` dans le constructeur
- La relation Order.user est obligatoire (NOT NULL) côté Order
- `quantity` : valeur par défaut 1 dans le constructeur

**Gestion d'erreurs :**
- Une OrderLine ne peut exister sans Order parent (contrainte NOT NULL sur `order`)
- Une OrderLine ne peut exister sans Product (contrainte NOT NULL sur `product`)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Entity/Product.php` | Créer | Entité Product avec ManyToMany vers Category |
| `src/Entity/Order.php` | Créer | Entité Order (attention : mot réservé SQL) |
| `src/Entity/OrderLine.php` | Créer | Entité OrderLine |
| `src/Entity/User.php` | Modifier | Ajouter la collection `$orders` (OneToMany inverse vers Order) |
| `src/Entity/Category.php` | Modifier | Compléter la relation Product si nécessaire |
| `src/Enum/OrderStatus.php` | Créer | Backed enum PHP pour les statuts de commande |
| `src/Repository/ProductRepository.php` | Créer | Repository Doctrine pour Product |
| `src/Repository/OrderRepository.php` | Créer | Repository Doctrine pour Order |
| `src/Repository/OrderLineRepository.php` | Créer | Repository Doctrine pour OrderLine |

### Signatures

#### OrderStatus (Enum)

```php
// src/Enum/OrderStatus.php
namespace App\Enum;

enum OrderStatus: string
{
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
```

#### Product

```php
// src/Entity/Product.php
namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinTable(name: 'product_category')]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    // Getters/setters + addCategory/removeCategory
}
```

#### Order

```php
// src/Entity/Order.php
namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $orderedAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: OrderStatus::class)]
    private ?OrderStatus $status = null;

    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $orderLines;

    public function __construct()
    {
        $this->orderedAt = new \DateTimeImmutable();
        $this->status = OrderStatus::Confirmed;
        $this->orderLines = new ArrayCollection();
    }

    // Getters/setters + addOrderLine/removeOrderLine
}
```

#### OrderLine

```php
// src/Entity/OrderLine.php
namespace App\Entity;

use App\Repository\OrderLineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantity = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    // Getters/setters
}
```

#### Modifications sur User (ajout de la collection orders)

```php
// Dans src/Entity/User.php, ajouter :
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// Propriété :
#[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
private Collection $orders;

// Dans le constructeur :
public function __construct()
{
    $this->orders = new ArrayCollection();
}

// Méthodes :
public function getOrders(): Collection { return $this->orders; }
public function addOrder(Order $order): self { if (!$this->orders->contains($order)) { $this->orders->add($order); $order->setUser($this); } return $this; }
public function removeOrder(Order $order): self { if ($this->orders->removeElement($order)) { if ($order->getUser() === $this) { $order->setUser(null); } } return $this; }
```

### Contraintes techniques
- **Framework** : Symfony 8.0 avec Doctrine ORM 3
- **Mapping** : Attributs PHP 8 uniquement
- **Table `order`** : Échapper avec des backticks `#[ORM\Table(name: '`order`')]` (mot réservé SQL)
- **Enum Doctrine** : Utiliser `enumType: OrderStatus::class` dans l'attribut `#[ORM\Column]` pour les backed enums (Doctrine ORM 3 natif)
- **Decimal pour les prix** : Type `decimal`, precision 10, scale 2, typé `?string` en PHP (Doctrine retourne les decimals comme string pour éviter la perte de précision)
- **Datetime immuable** : Utiliser `\DateTimeImmutable` et le type Doctrine `datetime_immutable` pour `orderedAt`
- **JoinTable** : Pour ManyToMany Product ↔ Category, nommer la table `product_category`
- **Cascade** : `cascade: ['persist', 'remove']` sur Order.ordersLines pour que les lignes soient persistées/supprimées avec la commande
- **Conventions** : PSR-12, `declare(strict_types=1)`, propriétés avec types PHP 8

### Tests à implémenter
Aucun test pour cette tâche (les tests sont dans la Tâche #003). Cependant, les entités doivent être écrites de manière à être facilement testables.

### Documentation
Aucune documentation spécifique (documentation globale dans la Tâche #003).
