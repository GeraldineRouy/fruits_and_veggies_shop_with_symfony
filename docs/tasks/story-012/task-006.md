# Tâche #006 - Story #012 : Tests automatisés et documentation

## Objectif
Implémenter les tests d'intégration (vérification de la présence Tailwind dans le HTML) et le test E2E (aperçu panier au survol), ainsi que la mise à jour du README avec les informations sur l'intégration Tailwind.

## Contexte
- Story #012 : `docs/stories/story-012.md`
- Dépend de : Tâche #001 (CDN Tailwind), Tâche #004 (cart preview)
- Nécessaire pour : Aucune

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Test d'intégration : vérifier la présence du CDN Tailwind
Créer un test qui vérifie que le CDN Tailwind est bien présent dans le HTML retourné par le serveur.

**Test :** Vérifier que la balise `<script src="https://cdn.tailwindcss.com">` est présente dans la réponse HTML de la page d'accueil.

**Scénarios :**
- La page d'accueil contient la balise CDN Tailwind
- Les autres pages (ex: boutique, login) contiennent aussi la balise CDN (car basées sur `base.html.twig`)

#### 2. Test d'intégration : vérifier la présence des classes Tailwind
Vérifier que les classes Tailwind sont bien appliquées dans le HTML.

**Scénarios :**
- La page d'accueil contient la classe `bg-brand-700` dans le header (couleur de fond du header)
- La page d'accueil contient la classe `container mx-auto` dans le `<main>`
- Une page produit contient la classe `rounded-xl` sur la carte produit
- La page panier contient la classe `bg-white rounded-xl shadow-md`

#### 3. Test d'intégration : vérifier la route `/panier/preview`
Créer un test qui vérifie que la route du preview panier fonctionne correctement.

**Scénarios :**
- L'utilisateur non connecté reçoit une redirection 302 (ou 401) vers la page de login
- L'utilisateur connecté (créé en setUp) reçoit une réponse 200 avec le contenu HTML du preview
- Le preview contient les classes Tailwind attendues (`bg-white rounded-xl shadow-xl`)

#### 4. Test d'intégration : aperçu panier (route `/panier/preview`)
Créer un test d'intégration qui vérifie la route `/panier/preview` (alternative pragmatique au test E2E Playwright, non disponible dans le projet).

**Scénario :**
1. Créer un utilisateur
2. Créer un produit
3. Ajouter le produit au panier via `CartService`
4. Se connecter
5. Faire une requête GET vers `/panier/preview`
6. Vérifier que la réponse contient le nom du produit ajouté
7. Vérifier que la réponse contient "Mon panier" et "Voir le panier"
8. Vérifier la présence des classes Tailwind attendues (`bg-white rounded-xl shadow-xl`)

#### 5. Mise à jour du README
Ajouter une section sur l'intégration Tailwind CSS dans le README.

**Section à ajouter** (entre "Stack" et "Email" par exemple, ou après la Stack) :

```markdown
## Style CSS avec Tailwind

Le projet utilise **Tailwind CSS** pour le style, intégré via CDN.

### Intégration

Tailwind CSS est chargé via CDN dans `templates/base.html.twig` :

```html
<script src="https://cdn.tailwindcss.com"></script>
```

### Configuration

La configuration Tailwind est définie inline dans le layout :

```html
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: {
                        50: '#e8f5e9',
                        100: '#c8e6c9',
                        200: '#a5d6a7',
                        300: '#81c784',
                        400: '#66bb6a',
                        500: '#4caf50',
                        600: '#43a047',
                        700: '#388e3c',
                        800: '#2e7d32',
                        900: '#1b5e20',
                    }
                }
            }
        }
    }
</script>
```

### Palette de couleurs

| Token | Usage |
|-------|-------|
| `brand-600` | Boutons principaux, prix, liens |
| `brand-700` | Header, footer |
| `brand-800` | Footer copyright |
| `brand-200` | Texte secondaire sur fond sombre |

### Composants stylisés

Les composants suivants utilisent exclusivement des classes utilitaires Tailwind :

- **Header/Navbar** : fond `bg-brand-700`, menu responsive avec hamburger
- **Footer** : 3 colonnes (à propos, liens, contact), copyright
- **Cartes produits** : `bg-white rounded-xl shadow-md hover:shadow-lg`
- **Cartes catégories** : `bg-white rounded-xl shadow-md p-6`
- **Pages d'auth** : carte centrée `max-w-md mx-auto`
- **Tableaux** : `bg-white rounded-xl shadow-md overflow-hidden`
- **Badges de statut** : couleurs conditionnelles (yellow/blue/purple/green/red)
- **Messages flash** : fonds colorés avec bordure
- **Aperçu panier** : dropdown `bg-white rounded-xl shadow-xl` au survol de l'icône panier
```

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `tests/Integration/TailwindStylingTest.php` | Créer | Tests d'intégration pour la présence Tailwind et la route preview |
| `README.md` | Modifier | Ajouter la section "Style CSS avec Tailwind" |

### Signatures

```php
namespace App\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TailwindStylingTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // Nettoyer les données
        // Créer les fixtures nécessaires
    }

    #[Test]
    public function tailwindCdnScriptIsPresent(): void
    {
        // GET /
        // Vérifie que le HTML contient "cdn.tailwindcss.com"
        // Vérifie que le HTML contient la balise script avec CDN
    }

    #[Test]
    public function headerHasTailwindClasses(): void
    {
        // GET /
        // Vérifie la présence de "bg-brand-700" dans le header
    }

    #[Test]
    public function mainContainerHasTailwindClasses(): void
    {
        // GET /
        // Vérifie "container mx-auto" dans le contenu
    }

    #[Test]
    public function cartPreviewRouteRequiresAuth(): void
    {
        // GET /panier/preview sans authentification
        // Vérifie la redirection vers /login
    }

    #[Test]
    public function cartPreviewReturnsCartContent(): void
    {
        // Créer un user, un produit, l'ajouter au panier
        // Se connecter
        // GET /panier/preview
        // Vérifie la présence du produit dans la réponse
        // Vérifie les classes Tailwind attendues
    }
}
```

### Contraintes techniques
- **Framework** : PHPUnit 13 avec attributs `#[Test]` (pas d'annotation `@test`)
- **Style** : Suivre le style des tests existants (`tests/Controller/HomeControllerTest.php`)
- **Base de données** : Utiliser `KernelTestCase` via `WebTestCase`, nettoyer les données dans `setUp()`
- **Fixtures** : Créer les données nécessaires en setUp (user, produit, ajout au panier)
- **Navigation** : Utiliser `$this->client->request()` pour les requêtes HTTP
- **Vérification HTML** : Utiliser `assertStringContainsString()`, `assertSelectorExists()`, etc.
- **README** : Respecter le format Markdown existant, ajouter la section sans modifier les sections existantes

### Tests à implémenter

#### Tests d'intégration
- **Fichier** : `tests/Integration/TailwindStylingTest.php`

| Scénario | Méthode | Description |
|----------|---------|-------------|
| Présence CDN | `tailwindCdnScriptIsPresent()` | Vérifie que `cdn.tailwindcss.com` est dans le HTML de la page d'accueil |
| Classes header | `headerHasTailwindClasses()` | Vérifie `bg-brand-700` dans le contenu |
| Classes main | `mainContainerHasTailwindClasses()` | Vérifie `container mx-auto` |
| Preview auth | `cartPreviewRouteRequiresAuth()` | Vérifie la redirection 302 sans auth |
| Preview contenu (connecté) | `cartPreviewReturnsCartContent()` | Vérifie le contenu du preview avec un article dans le panier — utilise `CartService::addProduct()` et `$this->client->loginUser($user)` |
| Classes carte produit | `productCardHasTailwindClasses()` | Vérifie `rounded-xl shadow-md` sur une page catégorie avec produit |

#### Documentation
- **README.md** : Ajouter la section "Style CSS avec Tailwind" complète (voir ci-dessus)
