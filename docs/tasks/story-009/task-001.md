# Tâche #001 - Story #009 : Formulaires CategoryType et ProductType

## Objectif
Créer les formulaires Symfony pour la gestion admin des catégories et des produits.

## Contexte
- Story #009 : `docs/stories/story-009.md`
- Dépend de : Rien (peut être fait en premier)
- Nécessaire pour : Tâche #002, Tâche #003

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Créer deux formulaires Symfony pour les entités `Category` et `Product` existantes.

**CategoryType** :
- Champ `name` (TextType, label "Nom")
- Champ `description` (TextareaType, label "Description")

**ProductType** :
- Champ `name` (TextType, label "Nom")
- Champ `description` (TextareaType, label "Description")
- Champ `price` (NumberType, label "Prix (€)", configuré avec `scale: 2`, `html5: true`, `attr.step: '0.01'`, `invalid_message: 'Le prix doit être un nombre valide.'`)
- Champ `image` (TextType, label "Chemin de l'image", help "Chemin relatif dans public/assets/images/")
- Champ `categories` (EntityType, label "Catégories", `multiple: true`, `expanded: false`, `class: Category::class`, `choice_label: 'name'`)

**Cas nominaux :**
- Le formulaire CategoryType lie les données à l'entité Category
- Le formulaire ProductType lie les données à l'entité Product
- ProductType permet la sélection multiple de catégories via EntityType (ManyToMany)

**Gestion d'erreurs :**
- Validation Symfony par attributs sur les entités (non bloquant si déjà présents)
- Le champ price doit accepter les formats décimaux avec point ou virgule

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Form/CategoryType.php` | Créer | Formulaire Category |
| `src/Form/ProductType.php` | Créer | Formulaire Product |

### Signatures

```php
namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;
    public function configureOptions(OptionsResolver $resolver): void;
}

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void;
    public function configureOptions(OptionsResolver $resolver): void;
}
```

### Contraintes techniques
- **Framework** : Symfony Forms, Doctrine ORM
- **Pattern** : Binding direct aux entités (pas de DTO) — suivre le pattern standard Symfony pour les CRUD admin
- **Validation** : Ne pas dupliquer les contraintes déjà présentes sur les entités via les attributs Doctrine/Validation
- **CSRF** : Activation par défaut (ne pas désactiver)
- **Traduction** : Labels en français, messages d'erreur par défaut de Symfony

### Tests à implémenter

#### Tests unitaires
- **Fichier** : `tests/Unit/Form/CategoryTypeTest.php`
- Scénario 1 : Soumission valide d'une catégorie
  - Données : `{ name: "Fruits", description: "Tous les fruits frais" }`
  - Résultat attendu : Formulaire valide, données mappées sur Category

- Scénario 2 : Soumission invalide (name vide) d'une catégorie
  - Données : `{ name: "", description: "Test" }`
  - Résultat attendu : Formulaire invalide, erreur sur name

- **Fichier** : `tests/Unit/Form/ProductTypeTest.php`
- Scénario 1 : Soumission valide d'un produit avec catégories
  - Données : `{ name: "Pomme", description: "Pomme rouge", price: "2.50", image: "images/pomme.jpg", categories: [1, 2] }`
  - Résultat attendu : Formulaire valide

- Scénario 2 : Soumission invalide (price négatif)
  - Données : `{ name: "Test", description: "Test", price: "-5", image: "test.jpg" }`
  - Résultat attendu : Formulaire invalide

Pour tester les formulaires, utiliser `FormTestCase` ou `KernelTestCase` avec `$this->getContainer()->get('form.factory')`.

### Documentation
- Aucune documentation spécifique pour les formulaires (documentée via la tâche README)

### Exemples d'utilisation
```php
// CategoryType
$form = $this->createForm(CategoryType::class, $category);

// ProductType
$form = $this->createForm(ProductType::class, $product);
```
