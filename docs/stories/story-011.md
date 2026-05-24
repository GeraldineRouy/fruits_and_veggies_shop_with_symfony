# Story #011 : Données produits enrichies (unités d'achat, top 3, nouvelle catégorie)

## Description
En tant qu'**administrateur**, je veux enrichir les données des produits avec des unités d'achat spécifiques, mettre à jour le top 3 des produits les plus vendus, et remplacer la catégorie "Légumes bio" par "Produits locaux d'exception" avec des produits régionaux, afin de refléter le catalogue réel du magasin.

## Origine
Feedbacks du 2025-05-24 :
- "Modifier les unités d'achat des produits : les avocats, les mangues, les ananas se vendent à la pièce, les herbes aromatiques se vendent au bouquet, les fraises en barquettes de 250g et tout le reste se vendent au kilogramme. Selon si les produits sont vendus à la pièce, au kg, au bouquet, en bouteille ou en barquette : le préciser dans la description du produit."
- "Modifie les produits du top 3 des produits les plus vendus : fraises, fromage Saint-Marcellin et ananas."
- "Enlève la catégorie 'Légumes bio' et remplace par 'Produits locaux d'exception'. Dans cette catégorie, rajoute les produits suivants : Noix de Grenoble AOC, huile de noix de Grenoble AOC bouteille 250ml, Fromage Bleu du Vercors-Sassenage, Fromage Saint-Marcellin, Chocolat Bonnat en tablettes de 100g."

## Critères d'acceptation
- [ ] Les avocats, mangues et ananas ont une description précisant "à la pièce"
- [ ] Les herbes aromatiques ont une description précisant "au bouquet"
- [ ] Les fraises ont une description précisant "barquette de 250g"
- [ ] Tous les autres produits ont une description précisant "au kilogramme"
- [ ] Le top 3 des produits les plus vendus affiche : fraises, Saint-Marcellin, ananas
- [ ] Les produits existants sont conservés (les nouveaux produits régionaux s'ajoutent en plus)
- [ ] Les produits de la catégorie "Légumes bio" sont supprimés SAUF s'ils appartiennent aussi à la catégorie "Légumes"
- [ ] La catégorie "Légumes bio" est supprimée
- [ ] La catégorie "Produits locaux d'exception" est créée
- [ ] Les produits suivants sont dans la catégorie "Produits locaux d'exception" :
  - Noix de Grenoble AOC
  - Huile de noix de Grenoble AOC (bouteille 250ml)
  - Fromage Bleu du Vercors-Sassenage
  - Fromage Saint-Marcellin
  - Chocolat Bonnat (tablette 100g)

## Tests automatisés
- Test unitaire : vérifier que chaque produit a une description contenant l'unité d'achat
- Test d'intégration : vérifier que la catégorie "Produits locaux d'exception" contient les 5 nouveaux produits
- Test d'intégration : vérifier que la requête du top 3 retourne fraises, Saint-Marcellin, ananas

## Documentation
- Mettre à jour le README avec la liste des catégories et produits disponibles
- Documenter les unités d'achat dans le README

## Valeur utilisateur
Permet aux clients de connaître précisément l'unité d'achat de chaque produit et découvre des produits régionaux d'exception, renforçant l'identité locale de la boutique.

## Dépendances
- Story #009 (migration des données d'exemple)
- Story #007 (top produits)
