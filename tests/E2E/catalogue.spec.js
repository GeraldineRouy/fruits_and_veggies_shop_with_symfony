// @ts-check
import { test, expect } from '@playwright/test';

test('Parcours catalogue : catégorie → liste → détail produit', async ({ page }) => {
    await test.step('Aller sur la page d\'accueil', async () => {
        await page.goto('/');
        await expect(page.locator('h1')).toContainText('Fruits & Veggies');
    });

    await test.step('Cliquer sur une catégorie', async () => {
        await page.locator('.category-card').first().click();
        await expect(page.locator('.products-grid')).toBeVisible();
    });

    await test.step('Cliquer sur un produit', async () => {
        await page.locator('.product-card').first().click();
        await expect(page.locator('.product-detail')).toBeVisible();
    });

    await test.step('Vérifier les informations du produit', async () => {
        await expect(page.locator('.product-detail__image')).toBeVisible();
        await expect(page.locator('.price')).toBeVisible();
    });
});
