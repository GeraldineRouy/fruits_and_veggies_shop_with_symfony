# Tâche #002 - Story #015 : Image de bienvenue sur la page d'accueil

## Objectif
Ajouter l'image `welcome.png` (située dans `public/assets/images/home/`) sur la page d'accueil, entre le texte de bienvenue et le top 3 des produits.

## Contexte
- Story #015 : `docs/stories/story-015.md`
- Exécution : Après Tâche #001 (ordre séquentiel décidé)
- Dépend de : Story #014 (page d'accueil enrichie)
- Nécessaire pour : Rien

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

La page d'accueil (`templates/home/index.html.twig`) affiche actuellement :
1. Texte de bienvenue (h1 + p)
2. Top 3 produits (via contrôleur imbriqué)
3. Catégories de produits

Tu dois insérer l'image `welcome.png` entre l'étape 1 (texte de bienvenue) et l'étape 2 (top produits). L'image est située dans `public/assets/images/home/welcome.png` et doit être référencée via `asset('assets/images/home/welcome.png')`.

**Cas nominaux :**
- L'image welcome s'affiche entre le texte de bienvenue et le top 3 produits
- L'image est responsive (centrée, ne déborde pas)
- L'image a des coins arrondis et une ombre légère cohérente avec le style Tailwind existant

**Cas limites :**
- Si l'image est absente du dossier, un `alt` textuel est affiché à la place
- L'image ne doit pas casser la mise en page sur mobile

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/home/index.html.twig` | Modifier | Ajouter l'image welcome entre la section welcome et le top products |

### Contraintes techniques
- **Framework** : Twig + Tailwind CSS (CDN)
- **Style** : Utiliser les classes Tailwind cohérentes avec le style du projet (cf `templates/home/index.html.twig`)
  - L'image doit être responsive (`w-full`, `max-w-4xl`, `mx-auto`)
  - Coins arrondis (`rounded-xl`)
  - Ombre légère (`shadow-md`)
  - Marge verticale (`my-8` ou `my-12`)
  - `h-auto` pour préserver le ratio
  - `object-cover` si besoin de crop
- **Asset** : Utiliser `{{ asset('assets/images/home/welcome.png') }}`
- **Attribut** : Ajouter `loading="lazy"` pour le chargement différé
- **Alt text** : `"Bienvenue chez Fruits & Veggies Shop"`

### Tests à implémenter

#### Test d'intégration
- **Fichier** : `tests/Integration/Controller/HomeControllerTest.php` (ajouter 2 méthodes dans la classe existante)
- Scénario 1 : `welcomeImageIsDisplayedOnHomePage`
  - Requête `GET /`
  - Résultat attendu : La réponse contient `assets/images/home/welcome.png` et l'alt text `Bienvenue chez Fruits & Veggies Shop`
- Scénario 2 : `welcomeImageIsPositionedBetweenTextAndTopProducts`
  - Requête `GET /`
  - Résultat attendu : Dans le HTML, l'ordre des sélecteurs est : `.welcome` → `img[src*="welcome"]` → `.top-products`

### Résultat attendu

```twig
<section class="welcome mb-12 text-center">
    <h1 class="text-4xl font-bold text-gray-800 mb-4">Bienvenue chez Fruits &amp; Veggies Shop...</h1>
    <p class="text-xl text-gray-600">Nous sommes ravis de vous accueillir...</p>
</section>

<img src="{{ asset('assets/images/home/welcome.png') }}" alt="Bienvenue chez Fruits & Veggies Shop"
     class="w-full max-w-4xl mx-auto h-auto rounded-xl shadow-md my-12 object-cover" loading="lazy">

{{ render(controller('App\\Controller\\TopProductsController::topProducts')) }}
```
