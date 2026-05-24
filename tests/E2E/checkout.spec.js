// @ts-check
import { test, expect } from '@playwright/test';

test('Parcours complet de paiement et confirmation', async ({ page }) => {
    await test.step('Aller sur la page de connexion', async () => {
        await page.goto('/login');
        await expect(page.locator('h1')).toHaveText('Connexion');
    });

    await test.step('Se connecter', async () => {
        await page.fill('input[name="_username"]', 'user@test.com');
        await page.fill('input[name="_password"]', 'password');
        await page.click('button:has-text("Se connecter")');
        await expect(page).toHaveURL('/');
    });

    await test.step('Naviguer vers une catégorie et ajouter un produit au panier', async () => {
        await page.locator('.category-card').first().click();
        await expect(page.locator('.products-grid')).toBeVisible();

        const addButtons = page.locator('.add-to-cart-list button');
        await addButtons.first().click();
        await expect(page).toHaveURL('/panier');
    });

    await test.step('Cliquer sur "Valider la commande" et vérifier la page de paiement', async () => {
        await page.click('a:has-text("Valider la commande")');
        await expect(page).toHaveURL('/commande/paiement');
        await expect(page.locator('h1')).toHaveText('Paiement');
    });

    await test.step('Vérifier les champs de carte pré-remplis et désactivés', async () => {
        await expect(page.locator('input[value="4242 4242 4242 4242"]')).toBeDisabled();
        await expect(page.locator('input[value="12/28"]')).toBeDisabled();
        await expect(page.locator('input[value="123"]')).toBeDisabled();
    });

    await test.step('Cliquer sur "Payer" et vérifier la page de confirmation', async () => {
        await page.click('button:has-text("Payer")');
        await expect(page.locator('h1')).toHaveText('Merci pour votre commande !');
    });

    await test.step('Vérifier la présence du récapitulatif de commande', async () => {
        await expect(page.locator('text=Retour à l\'accueil')).toBeVisible();
        await expect(page.locator('text=Voir mes commandes')).toBeVisible();
    });

    await test.step('Cliquer sur "Retour à l\'accueil"', async () => {
        await page.click('a:has-text("Retour à l\'accueil")');
        await expect(page).toHaveURL('/');
    });
});
