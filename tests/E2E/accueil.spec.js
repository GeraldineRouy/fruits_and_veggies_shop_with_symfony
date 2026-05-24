// @ts-check
import { test, expect } from '@playwright/test';

test('Page d\'accueil : bienvenue, top produits et navigation vers catégorie', async ({ page }) => {
    await test.step('Aller sur la page d\'accueil', async () => {
        await page.goto('/');
    });

    await test.step('Vérifier le texte de bienvenue', async () => {
        await expect(page.locator('.welcome h1')).toBeVisible();
        await expect(page.locator('.welcome')).toContainText('primeur et épicerie fine grenobloise');
    });

    await test.step('Vérifier la présence du top 3 des produits', async () => {
        await expect(page.locator('.top-products')).toBeVisible();
    });

    await test.step('Vérifier la section des catégories', async () => {
        await expect(page.locator('.categories')).toBeVisible();
        await expect(page.locator('.category-card')).not.toHaveCount(0);
    });

    await test.step('Cliquer sur une catégorie et vérifier la redirection', async () => {
        await page.locator('.category-card').first().click();
        await expect(page).toHaveURL(/\/boutique\/\d+/);
        await expect(page.locator('.products-grid')).toBeVisible();
    });
});
