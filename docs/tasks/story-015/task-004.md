# Tâche #004 - Story #015 : Fallback "Image non disponible" + tests + documentation

## Objectif
Ajouter un affichage de fallback "Image non disponible" pour les produits sans image (Carotte, Persil, et tout produit avec `image` NULL), mettre à jour la documentation README, et rédiger les tests d'intégration de la Story #015.

## Contexte
- Story #015 : `docs/stories/story-015.md`
- Exécution : Après Tâche #003 (ordre séquentiel décidé)
- Dépend de : Tâche #001 (les produits doivent avoir leurs images à jour)
- Nécessaire pour : Rien (dernière tâche)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

Les produits sans image (champ `image` NULL en base, comme la Carotte et le Persil) doivent afficher un message "Image non disponible" stylisé dans toutes les vues où une image produit est affichée. Tu dois également supprimer les éventuels fichiers JPG résiduels dans `public/assets/images/products/`.

**Cas nominaux :**
- Un produit avec `image = NULL` affiche un bloc de remplacement "Image non disponible" dans toutes les vues
- Un produit avec `image` renseignée continue d'afficher son image normalement

**Cas limites :**
- Le bloc "Image non disponible" doit avoir les mêmes dimensions que l'image qu'il remplace (hauteur fixe)
- Le message doit être centré verticalement et horizontalement dans le bloc
- Le bloc doit utiliser le style Tailwind cohérent avec le reste du projet (fond gris, texte centré)

**Gestion d'erreurs :**
- Si `product.image` est une chaîne vide (au lieu de NULL), elle est traitée comme "pas d'image"
- `asset(product.image)` ne doit PAS être appelé si l'image est NULL (Twig lèverait une erreur)

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `templates/home/_top_products.html.twig` | Modifier | Fallback "Image non disponible" pour le top produits |
| `templates/shop/products.html.twig` | Modifier | Fallback pour les cartes produits de la boutique |
| `templates/shop/product.html.twig` | Modifier | Fallback pour la page détail produit |
| `templates/cart/_preview.html.twig` | Modifier | Fallback pour l'aperçu panier |
| `templates/cart/_product_row.html.twig` | Modifier | Fallback pour la ligne du panier |
| `templates/admin/products.html.twig` | Modifier | Fallback pour la liste admin |
| `README.md` | Modifier | Ajouter la section "Images et assets" |
| `public/assets/images/products/*.jpg` | Supprimer | Supprimer tout fichier .jpg restant |
| `tests/Integration/Story015AssetsTest.php` | Créer | Tests d'intégration pour la Story #015 |

### Contraintes techniques

#### Fallback dans les templates

Utiliser un bloc `<div>` stylisé avec Tailwind pour remplacer l'image manquante :

```twig
{% if product.image %}
    <img src="{{ asset(product.image) }}" alt="{{ product.name }}" class="...">
{% else %}
    <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400 text-sm rounded-t-lg">
        Image non disponible
    </div>
{% endif %}
```

La classe `h-48` doit correspondre à la hauteur de l'image dans chaque contexte :
- `_top_products.html.twig` : `h-48` (l'image fait `h-48`)
- `shop/products.html.twig` : `h-48`
- `shop/product.html.twig` : `h-96`
- `cart/_preview.html.twig` : `w-10 h-10`
- `cart/_product_row.html.twig` : `w-16 h-16`
- `admin/products.html.twig` : `w-12 h-12` (taille mini, 50x50px)

**Important :** Pour les petites icônes (panier préview, panier row, admin), adapter la taille du fallback :
- `cart/_preview.html.twig` : `w-10 h-10 rounded flex-shrink-0`
- `cart/_product_row.html.twig` : `w-16 h-16 rounded-lg flex-shrink-0`
- `admin/products.html.twig` : `w-12 h-12 rounded-lg`

#### Nettoyage des fichiers JPG
```bash
# Supprimer tous les .jpg restants dans le dossier products
Get-ChildItem -Path "public/assets/images/products" -Filter "*.jpg" | Remove-Item
```

#### Documentation README

Ajouter une section **"Images et assets"** après la section "Commandes utiles" (avant la section "Documentation") :

```markdown
## Images et assets

### Structure des dossiers

```
public/assets/images/
├── home/
│   └── welcome.png          # Image de bienvenue page d'accueil
├── products/
│   ├── pommes.png           # Images des produits (PNG)
│   ├── bananes.png
│   └── ...
└── avatars/
    ├── avatar_user.png      # Avatar utilisateur connecté
    └── avatar_admin.png     # Avatar administrateur
```

### Gestion des images produits

- Les images des produits sont stockées dans `public/assets/images/products/` au format PNG
- Le champ `image` de l'entité `Product` contient le chemin relatif (ex: `assets/images/products/pommes.png`)
- La fonction Twig `{{ asset() }}` résout le chemin depuis le dossier `public/`
- Pour les produits sans image associée, un message "Image non disponible" est affiché
- Les images sont chargées avec `loading="lazy"` pour optimiser les performances
```

### Tests à implémenter

#### Test d'intégration complet
- **Fichier** : `tests/Integration/Story015AssetsTest.php`
- **Classe** : `App\Tests\Integration\Story015AssetsTest`
- **Namespace** : `App\Tests\Integration`
- **Base** : `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`

Scénarios :

1. **Les images produits sont bien rendues dans les cartes produits**
   - Créer un produit avec une image PNG valide
   - Naviguer sur `/boutique/{id}`
   - Vérifier que la réponse contient le nom du fichier PNG et le `alt` text

2. **Le dossier products ne contient plus de fichiers .jpg**
   - Vérifier avec le système de fichiers qu'aucun `.jpg` n'existe dans `public/assets/images/products/`

3. **Un produit sans image affiche "Image non disponible"**
   - Créer un produit avec `image = null`
   - Naviguer sur la page du produit
   - Vérifier que la réponse contient "Image non disponible"
