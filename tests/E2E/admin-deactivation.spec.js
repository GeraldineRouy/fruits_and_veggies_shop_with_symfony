import { test, expect } from '@playwright/test';

test.describe('Admin user deactivation', () => {
    test('admin deactivates a user and status changes', async ({ page }) => {
        const testEmail = `e2e_deact_${Date.now()}@example.com`;

        await page.goto('/register');
        await expect(page.locator('h1')).toHaveText('Inscription');

        await page.fill('input[name="register[email]"]', testEmail);
        await page.fill('input[name="register[firstName]"]', 'Deact');
        await page.fill('input[name="register[lastName]"]', 'Test');
        await page.fill('input[name="register[plainPassword][first]"]', 'password123');
        await page.fill('input[name="register[plainPassword][second]"]', 'password123');
        await page.click('button:has-text("Créer mon compte")');

        await page.goto('/login');
        await expect(page.locator('h1')).toHaveText('Connexion');

        // Switch to admin view — in E2E we need an admin account seeded in the DB
        await page.goto('/admin/utilisateurs');
        await expect(page.locator('h1')).toHaveText('Gestion des utilisateurs');

        // Find the test user and click Désactiver
        const userRow = page.locator('tr', { hasText: testEmail });
        await userRow.locator('button:has-text("Désactiver")').click();

        // Verify the status changed to Désactivé
        await expect(userRow.locator('span.badge-inactive')).toHaveText('Désactivé');
    });
});
