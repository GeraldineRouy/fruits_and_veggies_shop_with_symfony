# Story #014 : Page d'accueil enrichie (texte de bienvenue et catégories)

## Description
En tant que **visiteur**, je veux voir un texte de bienvenue présentant la boutique, suivi du top 3 des produits les plus vendus, puis des catégories de produits, afin d'être accueilli et guidé dès mon arrivée sur le site.

## Origine
Feedback du 2025-05-24 : "Dans la vue Accueil : Ajoute un petit de texte de bienvenue et de présentation de la boutique. Le top 3 des produits les plus vendus doit s'afficher juste en dessous. Puis affiche les catégories des produits."

## Critères d'acceptation
- [ ] La page d'accueil affiche le texte de bienvenue suivant :
      "Bienvenue chez Fruits & Veggies Shop, votre primeur et épicerie fine grenobloise !"
      "Nous sommes ravis de vous accueillir pour vous faire découvrir notre sélection de produits frais d'exception."
- [ ] Le top 3 des produits les plus vendus s'affiche juste en dessous du texte de bienvenue (déjà couvert par Story #007)
- [ ] Les catégories de produits sont affichées sous le top 3
- [ ] Chaque catégorie affiche son nom et le nombre de produits associés
- [ ] Le clic sur une catégorie redirige vers la liste des produits de cette catégorie
- [ ] La mise en page est responsive et cohérente avec le thème Tailwind (Story #012)

## Tests automatisés
- Test d'intégration : vérifier que la page d'accueil contient le texte de bienvenue, le top 3 et les catégories
- Test E2E (Playwright) : navigation depuis l'accueil → clic sur une catégorie → liste des produits

## Documentation
- Mettre à jour le README avec la description de la page d'accueil

## Valeur utilisateur
Offre une expérience d'accueil chaleureuse et guide le visiteur vers les produits populaires et les catégories, augmentant les chances de conversion.

## Dépendances
- Story #007 (top produits)
- Story #004 (catalogue et catégories)
- Story #012 (style Tailwind)
