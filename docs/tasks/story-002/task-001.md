# Tâche #001 - Story #002 : Entités User et Category

## Objectif
Créer les entités Doctrine `User` et `Category` avec leurs Repository, en respectant les champs définis dans la spécification et la configuration existante du projet.

## Contexte
- Story #002 : `docs/stories/story-002.md`
- Spécification : `docs/specification/specification.md`
- Dépend de : Aucune (Story #001 terminée, Doctrine déjà configuré)
- Nécessaire pour : Tâche #002 (Product, Order, OrderLine)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer les entités `User` et `Category` avec leurs Repository associés en utilisant les attributs Doctrine (PHP 8 attributes, `#[ORM\Entity]`, `#[ORM\Column]`, etc.).

**Cas nominaux :**
- L'entité `User` est créée avec tous les champs requis et un Repository dédié
- L'entité `Category` est créée avec tous les champs requis et un Repository dédié
- Les champs utilisent les types Doctrine appropriés
- Les contraintes NOT NULL / NULL sont respectées selon la spécification

**Cas limites :**
- `verifiedAt` et `lastLoginAt` sont nullables (datetime, nullable)
- `roles` est de type `json` dans la base, mais doit être typé comme `array` en PHP avec une valeur par défaut `[]`
- `isActive` a une valeur par défaut à `true`

**Gestion d'erreurs :**
- Les validations au niveau Doctrine (types, nullabilité) sont définies par les attributs
- Pas de validation métier dans l'entité (pure couche modèle)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Entity/User.php` | Créer | Entité User avec attributs Doctrine |
| `src/Entity/Category.php` | Créer | Entité Category avec attributs Doctrine |
| `src/Repository/UserRepository.php` | Créer | Repository Doctrine pour User |
| `src/Repository/CategoryRepository.php` | Créer | Repository Doctrine pour Category |

### Signatures

```php
// src/Entity/User.php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $verifiedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    // Getters et setters pour tous les champs
    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): self { $this->firstName = $firstName; return $this; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): self { $this->lastName = $lastName; return $this; }
    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getVerifiedAt(): ?\DateTimeInterface { return $this->verifiedAt; }
    public function setVerifiedAt(?\DateTimeInterface $verifiedAt): self { $this->verifiedAt = $verifiedAt; return $this; }
    public function getLastLoginAt(): ?\DateTimeInterface { return $this->lastLoginAt; }
    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): self { $this->lastLoginAt = $lastLoginAt; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
}
```

```php
// src/Entity/Category.php
namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    // Collection pour la relation ManyToMany avec Product (sera utilisée dans task-002)
    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'categories')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    // Getters et setters pour name et description
    // getProducts() pour la relation
}
```

### Contraintes techniques
- **Framework** : Symfony 8.0 avec Doctrine ORM 3
- **Mapping** : Utiliser les attributs PHP 8 (`#[ORM\Entity]`, `#[ORM\Column]`, etc.) — PAS de YAML/XML
- **Table `user`** : Doctrine nécessite d'échapper `user` avec des backticks car c'est un mot réservé SQL (`#[ORM\Table(name: '`user`')]`)
- **Génération d'ID** : Utiliser `IDENTITY` (stratégie PostgreSQL avec séquence automatique)
- **UserRepository** : Étendre `Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository` et implémenter `PasswordAuthenticatedUserInterface` (pour Symfony Security) — voir imports
- **Relation Category ↔ Product** : Définir le côté inverse (`mappedBy: 'categories'`) avec une collection `ArrayCollection` initialisée dans le constructeur — le côté propriétaire sera dans Product (task-002)
- **Conventions du projet** : Suivre le code style PSR-12, typage strict (`declare(strict_types=1)`), propriétés typées avec `?` pour nullables, `self` en retour de setter

### Tests à implémenter
Aucun test pour cette tâche (les tests sont dans la Tâche #003). Cependant, l'entité doit être écrite de manière à être facilement testable.

### Documentation
Aucune documentation spécifique (documentation globale dans la Tâche #003).
