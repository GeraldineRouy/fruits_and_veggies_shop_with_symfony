# Story #012 : Style CSS aéré avec Tailwind CSS

## Description
En tant que **visiteur**, je veux un design aéré et moderne avec Tailwind CSS pour le header, le footer, les cartes produits et un aperçu du panier au survol, afin d'avoir une expérience visuelle agréable sur le site.

## Origine
Feedback du 2025-05-24 : "Rajouter un style CSS aéré avec la lib tailwindcss (header/navbar, footer, cartes produits, aperçu du contenu du panier au survol de la souris)"

## Implémentation
- Intégration : CDN via balise `<script>` dans le layout Twig

## Critères d'acceptation
- [ ] Tailwind CSS est intégré via CDN (balise `<script src="https://cdn.tailwindcss.com">` dans le layout)
- [ ] Le header/navbar utilise un style aéré avec Tailwind (espacement, couleurs, typographie)
- [ ] Le footer utilise un style cohérent avec le header
- [ ] Les cartes produits ont un design aéré (ombre, coins arrondis, espacement)
- [ ] Un aperçu du contenu du panier apparaît au survol de l'icône panier
- [ ] Le design est responsive (mobile et desktop)
- [ ] Le style global est cohérent sur toutes les pages du site

## Tests automatisés
- Test d'intégration : vérifier que les classes Tailwind sont bien compilées dans le CSS final
- Test E2E (Playwright) : vérifier visuellement que l'aperçu du panier au survol s'affiche

## Documentation
- Mettre à jour le README avec les informations sur l'intégration Tailwind

## Valeur utilisateur
Offre une expérience visuelle moderne et agréable, augmentant la confiance et l'engagement des visiteurs.

## Dépendances
- Story #004 (catalogue produits)
- Story #005 (panier)
