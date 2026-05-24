# Tâche #003 - Story #011 : Mise à jour de la documentation README

## Objectif
Mettre à jour le README avec la liste complète des catégories et produits disponibles, et documenter les unités d'achat des produits.

## Contexte
- Story #011 : `docs/stories/story-011.md`
- Dépend de : Tâche #001 (migration d'enrichissement)
- Aucune dépendance vers d'autres tâches

## Prompt

En tant qu'agent de code, tu dois implémenter ce qui suit.

### Description fonctionnelle

#### 1. Mettre à jour la section "Données d'exemple" du README
Dans le fichier `README.md`, la section "Données d'exemple" (lignes 193-204) liste actuellement les catégories et produits. Mettre à jour cette section pour refléter les changements de la Story #011 :

- Remplacer la liste des 5 catégories par les 5 nouvelles catégories (supprimer "Légumes bio", ajouter "Produits locaux d'exception")
- Remplacer la liste des 15 produits par les 20 produits (15 originaux + 5 nouveaux)
- Ajouter les unités d'achat pour chaque produit

#### 2. Documenter les unités d'achat
Ajouter une nouvelle sous-section "Unités d'achat" qui explique le système d'unités :

- Les produits sont vendus selon différentes unités d'achat
- Liste des unités : à la pièce, au bouquet, en barquette de 250g, au kilogramme, à la bouteille
- Chaque unité est précisée dans la description du produit

#### 3. Structure de la mise à jour
La section README mise à jour doit ressembler à :

```markdown
### Données d'exemple

Une migration Doctrine insère des données d'exemple (fruits, légumes, produits régionaux) :

```bash
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

#### Catégories

| Nom | Description |
|-----|-------------|
| Fruits | Fruits frais de saison |
| Légumes | Légumes frais de saison |
| Fruits exotiques | Fruits tropicaux et exotiques |
| Herbes aromatiques | Herbes et plantes aromatiques |
| Produits locaux d'exception | Produits régionaux d'exception de nos terroirs |

#### Produits

| Nom | Unité d'achat | Catégories |
|-----|---------------|------------|
| Pomme Golden | au kilogramme | Fruits |
| Banane | au kilogramme | Fruits, Fruits exotiques |
| Orange | au kilogramme | Fruits |
| Fraise | barquette de 250g | Fruits |
| Avocat | à la pièce | Fruits exotiques, Légumes |
| Mangue | à la pièce | Fruits exotiques |
| Carotte | au kilogramme | Légumes |
| Salade | au kilogramme | Légumes |
| Tomate | au kilogramme | Légumes |
| Concombre | au kilogramme | Légumes |
| Courgette | au kilogramme | Légumes |
| Basilic | au bouquet | Herbes aromatiques |
| Menthe | au bouquet | Herbes aromatiques |
| Persil | au bouquet | Herbes aromatiques |
| Ananas | à la pièce | Fruits exotiques |
| Noix de Grenoble AOC | au kilogramme | Produits locaux d'exception |
| Huile de noix de Grenoble AOC | à la bouteille | Produits locaux d'exception |
| Fromage Bleu du Vercors-Sassenage | à la pièce | Produits locaux d'exception |
| Fromage Saint-Marcellin | à la pièce | Produits locaux d'exception |
| Chocolat Bonnat | à la pièce | Produits locaux d'exception |
```

### Fichiers concernés

| Fichier | Action | Description |
|---------|--------|-------------|
| `README.md` | Modifier | Mettre à jour les sections Données d'exemple et Unités d'achat |

### Contraintes techniques
- **Format** : Markdown — respecter le formatage existant du README
- **Style** : Utiliser des tableaux Markdown comme dans l'existant
- **Ne pas modifier** : Les autres sections du README (installation, tests, commandes, etc.) doivent rester inchangées

### Tests à implémenter
Aucun test pour cette tâche.

### Documentation
Cette tâche EST la mise à jour de documentation.
