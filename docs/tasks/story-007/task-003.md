# Tâche #003 - Story #007 : Tests E2E Playwright — Top produits (ANNULÉE)

## Statut : ANNULÉE

Cette tâche est annulée suite à la décision de ne pas inclure de test E2E pour cette story. Les tests PHPUnit (unitaires et d'intégration) prévus dans les tâches #001 et #002 couvrent déjà la validation de la fonctionnalité.

## Justification

- La story #007 est une fonctionnalité statique d'affichage sur la page d'accueil
- Les tests unitaires (ProductRepository::findTopMostOrdered) et d'intégration (page d'accueil inclut le bloc) valident suffisamment le comportement
- Pas de scénario utilisateur complexe nécessitant un test E2E Playwright

## Couverture de test conservée

- **Tâche #001** : Tests unitaires du repository (`tests/Unit/Repository/ProductRepositoryTest.php`)
- **Tâche #002** : Tests d'intégration du contrôleur (`tests/Controller/HomeControllerTest.php`)
