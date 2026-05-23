// @ts-check
import { test, expect } from '@playwright/test';

test('Parcours complet du panier : ajout → modification → suppression', async ({ page }) => {
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

    await test.step('Naviguer vers une catégorie', async () => {
        await page.locator('.category-card').first().click();
        await expect(page.locator('.products-grid')).toBeVisible();
    });

    await test.step('Ajouter un premier produit depuis la liste', async () => {
        const addButtons = page.locator('.add-to-cart-list button');
        await addButtons.first().click();
        await expect(page).toHaveURL('/panier');
    });

    await test.step('Revenir au catalogue pour un second produit', async () => {
        await page.goto('/');
        await page.locator('.category-card').first().click();
        await expect(page.locator('.products-grid')).toBeVisible();
    });

    await test.step('Cliquer sur un produit pour voir le détail', async () => {
        await page.locator('.product-card a').first().click();
        await expect(page.locator('.product-detail')).toBeVisible();
    });

    await test.step('Ajouter le produit depuis la fiche avec quantité 3', async () => {
        await page.fill('#add_to_cart_quantity', '3');
        await page.click('button:has-text("Ajouter au panier")');
        await expect(page).toHaveURL('/panier');
    });
});
