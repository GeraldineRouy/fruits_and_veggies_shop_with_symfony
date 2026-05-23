# Tâche #002 - Story #001 : Controller d'accueil et vérification

## Objectif
Créer un contrôleur Symfony basique avec une page d'accueil qui répond HTTP 200, afin de vérifier que l'ensemble de la stack (PHP, Symfony, base de données) fonctionne.

## Contexte
- Story #001 : `docs/stories/story-001.md`
- Dépend de : Tâche #001 (le conteneur PHP doit exister)
- Nécessaire pour : Tâche #003 (tests d'intégration)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle
Le squelette Symfony 8.0 est installé mais il n'y a aucun contrôleur. Tu dois créer un `HomeController` avec une route `/` qui affiche une page d'accueil simple de bienvenue.

**Cas nominaux :**
- `GET http://localhost:8000/` retourne une page HTML avec HTTP 200
- La page utilise le template `base.html.twig` existant
- La page affiche un titre "Bienvenue chez Fruits & Veggies"

**Gestion d'erreurs :**
- Pas de cas d'erreur particulier (page publique simple)
- Si la base de données n'est pas accessible, la page s'affiche quand même (pas de dépendance DB)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `src/Controller/HomeController.php` | Créer | Contrôleur avec route `/` |
| `templates/home/index.html.twig` | Créer | Template de la page d'accueil |
| `templates/base.html.twig` | Modifier | Adapter le template de base (titre, CSS) |

### Signatures

```php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
```

### Contraintes techniques
- **Framework** : Symfony 8.0 avec attributs PHP 8 pour les routes (`#[Route]`)
- **Template** : Étendre `base.html.twig`, utiliser le block `body`
- **CSS** : Ajouter un style simple dans `assets/styles/app.css` pour la page d'accueil
- **AssetMapper** : Utiliser `{{ asset('styles/app.css') }}` dans `base.html.twig`
- **Pas de base de données** : Le contrôleur ne doit pas nécessiter de connexion DB (page statique)
- **Nom de route** : `app_home`

### Template

```twig
{# templates/home/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Accueil - Fruits & Veggies{% endblock %}

{% block body %}
    <h1>Bienvenue chez Fruits & Veggies</h1>
    <p>Votre magasin de fruits et légumes frais en ligne.</p>
{% endblock %}
```

### Tests à implémenter

#### Test d'intégration
- **Fichier** : `tests/Controller/HomeControllerTest.php`
- Scénario 1 : La route `/` retourne HTTP 200
  - Données : Client GET `/`
  - Résultat attendu : Status code 200, contenu HTML contient "Bienvenue chez Fruits & Veggies"

```php
// Structure du test
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomepageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bienvenue chez Fruits & Veggies');
    }
}
```

### Documentation

#### Documentation à créer
- Aucune (page d'accueil triviale)

#### Documentation à mettre à jour
- `README.md` : Mentionner la page d'accueil accessible sur `http://localhost:8000`
