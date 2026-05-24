# Tâche #001 - Story #011 : Migration d'enrichissement des données produits

## Objectif
Créer une nouvelle migration Doctrine qui met à jour les descriptions des produits avec leurs unités d'achat, remplace la catégorie "Légumes bio" par "Produits locaux d'exception", ajoute 5 nouveaux produits régionaux, et insère des données de commandes (OrderLine) pour définir le top 3 des produits les plus vendus.

## Contexte
- Story #011 : `docs/stories/story-011.md`
- Dépend de : Story #009 (migration des données d'exemple existante), Story #007 (top produits)
- Nécessaire pour : Tâche #002 (tests), Tâche #003 (documentation)

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Mise à jour des descriptions produits avec unités d'achat
Pour chaque produit existant, concaténer l'unité d'achat à la description existante, séparée par " — " (tiret cadratin) :

| ID | Produit | Description actuelle | Unité | Nouvelle description |
|----|---------|---------------------|-------|---------------------|
| 5 | Avocat | "Avocat prêt à déguster" | à la pièce | "Avocat prêt à déguster — Vendu à la pièce" |
| 6 | Mangue | "Mangue bio du Pérou" | à la pièce | "Mangue bio du Pérou — Vendue à la pièce" |
| 15 | Ananas | "Ananas Victoria" | à la pièce | "Ananas Victoria — Vendu à la pièce" |
| 12 | Basilic | "Basilic frais en pot" | au bouquet | "Basilic frais — Vendu au bouquet" |
| 13 | Menthe | "Menthe fraîche en pot" | au bouquet | "Menthe fraîche — Vendue au bouquet" |
| 14 | Persil | "Persil plat en botte" | au bouquet | "Persil plat — Vendu au bouquet" |
| 4 | Fraise | "Fraise gariguette parfumée" | barquette 250g | "Fraise gariguette parfumée — Vendu en barquette de 250g" |
| 1,2,3,7,8,9,10,11 | Tous les autres produits | leurs descriptions actuelles | au kilogramme | "[description] — Vendu au kilogramme" |

#### 2. Remplacement de la catégorie "Légumes bio" par "Produits locaux d'exception"
- Supprimer l'association `product_category` pour les produits qui ne sont QUE dans "Légumes bio" (vérifier qu'ils n'appartiennent pas aussi à "Légumes")
- Supprimer la catégorie "Légumes bio" (id=4)
- Créer une nouvelle catégorie "Produits locaux d'exception" (description : "Produits régionaux d'exception de nos terroirs")

**Produits concernés par la suppression :**
- Carotte (id=7) : dans Légumes + Légumes bio → Garder le produit, supprimer seulement l'association avec Légumes bio
- Tomate (id=9) : dans Légumes + Légumes bio → idem
- Courgette (id=11) : dans Légumes + Légumes bio → idem
- Aucun produit exclusivement dans Légumes bio → aucun produit à supprimer

#### 3. Nouveaux produits régionaux (catégorie "Produits locaux d'exception")

| ID | Nom | Description | Image | Prix |
|----|-----|-------------|-------|------|
| 16 | Noix de Grenoble AOC | "Noix de Grenoble AOC, cerneaux de qualité supérieure — Vendu au kilogramme" | images/products/noix-de-grenoble.jpg | 12.00 |
| 17 | Huile de noix de Grenoble AOC | "Huile de noix de Grenoble AOC, bouteille 250ml — Vendue à la bouteille" | images/products/huile-noix-grenoble.jpg | 8.50 |
| 18 | Fromage Bleu du Vercors-Sassenage | "Bleu du Vercors-Sassenage AOP, fromage persillé — Vendu à la pièce" | images/products/bleu-vercors.jpg | 6.50 |
| 19 | Fromage Saint-Marcellin | "Saint-Marcellin AOP, fromage doux et crémeux — Vendu à la pièce" | images/products/saint-marcellin.jpg | 4.50 |
| 20 | Chocolat Bonnat | "Chocolat Bonnat, tablette de 100g — Vendu à la pièce" | images/products/chocolat-bonnat.jpg | 5.50 |

#### 4. Données de commandes (OrderLine) pour le top 3

Créer des commandes factices avec le user admin (id=1, créé par `Version20260524130000.php`) pour que la requête `findTopMostOrdered(3)` retourne :
1. **Fraise** (id=4) — quantité totale la plus élevée
2. **Fromage Saint-Marcellin** (id=19) — 2e quantité
3. **Ananas** (id=15) — 3e quantité

Quantités suggérées :
- Fraise (id=4) : 50 unités commandées
- Fromage Saint-Marcellin (id=19) : 30 unités commandées
- Ananas (id=15) : 25 unités commandées
- Autres produits existants : 5 à 10 unités chacun (pour éviter qu'ils dépassent le top 3)

Créer plusieurs commandes (par ex. 5 commandes) avec des OrderLines variées pour simuler un historique réaliste.

**Cas nominaux :**
- Une nouvelle migration doctrine est créée et peut être exécutée
- Les descriptions sont mises à jour avec les unités d'achat
- La catégorie "Légumes bio" n'existe plus
- La catégorie "Produits locaux d'exception" existe avec ses 5 produits
- La requête top 3 retourne : fraises, Saint-Marcellin, ananas
- Les données de seed OrderLine sont cohérentes (même user, order valide)

**Cas limites :**
- Si la catégorie "Légumes bio" a déjà été supprimée (migration rejouée), ne pas échouer
- Si les produits existent déjà (ON CONFLICT), ne pas dupliquer
- Un produit peut être dans plusieurs catégories ; ne pas casser les autres associations

**Gestion d'erreurs :**
- La migration doit être idempotente (utiliser `ON CONFLICT DO NOTHING` ou des vérifications préalables)
- En `down()` : recréer la catégorie "Légumes bio" (id=4), restaurer les associations `product_category` pour carotte/tomate/courgette, supprimer la catégorie "Produits locaux d'exception", supprimer les nouveaux produits (id 16-20) et les OrderLines de seed. **Les descriptions modifiées ne sont pas restaurées** (acceptable pour une migration de seed data).

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `migrations/Version20260524XXXXXX.php` | Créer | Nouvelle migration d'enrichissement (timestamp = date/heure actuelle) |
| `public/assets/images/products/noix-de-grenoble.jpg` | Créer | Image placeholder pour Noix de Grenoble |
| `public/assets/images/products/huile-noix-grenoble.jpg` | Créer | Image placeholder pour Huile de noix |
| `public/assets/images/products/bleu-vercors.jpg` | Créer | Image placeholder pour Bleu du Vercors |
| `public/assets/images/products/saint-marcellin.jpg` | Créer | Image placeholder pour Saint-Marcellin |
| `public/assets/images/products/chocolat-bonnat.jpg` | Créer | Image placeholder pour Chocolat Bonnat |

(Le numéro de version dans le nom de fichier doit être postérieur à la migration existante `Version20260524130000.php`. Utiliser la date/heure actuelle pour le timestamp.)

### Contraintes techniques
- **Framework** : Doctrine Migrations 3.x, Symfony 8.0
- **Style** : Suivre le style des migrations existantes (`Version20260524120000.php`)
- **SQL uniquement** : Utiliser des requêtes SQL brutes (`$this->addSql()`) comme les migrations existantes, pas d'ORM
- **Idempotence** : Toutes les insertions doivent utiliser `ON CONFLICT DO NOTHING`
- **Down** : Implémenter la méthode `down(Schema $schema): void` — ne pas restaurer les descriptions, mais supprimer les nouveaux produits, la nouvelle catégorie et les OrderLines de seed, et recréer "Légumes bio"
- **Ordre** : La migration suppose que `Version20260524120000.php` et `Version20260524130000.php` ont déjà été exécutées
- **Images** : Créer des fichiers image placeholder (1x1 px transparent JPEG ou PNG) pour les 5 nouveaux produits dans `public/assets/images/products/`

### Tests à implémenter
Aucun test direct pour cette migration — les tests sont dans la Tâche #002.

### Documentation
Aucune documentation directe pour cette migration — la documentation est dans la Tâche #003.
