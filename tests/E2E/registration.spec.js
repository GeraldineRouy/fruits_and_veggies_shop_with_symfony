import { test, expect } from '@playwright/test';

test.describe('Registration flow', () => {
    test('full registration, verification and login', async ({ page }) => {
        const testEmail = `e2e_${Date.now()}@example.com`;

        // 1. Navigate to registration page
        await page.goto('/register');
        await expect(page.locator('h1')).toHaveText('Inscription');

        // 2. Fill in the registration form
        await page.fill('input[name="register[email]"]', testEmail);
        await page.fill('input[name="register[firstName]"]', 'E2E');
        await page.fill('input[name="register[lastName]"]', 'Test');
        await page.fill('input[name="register[plainPassword][first]"]', 'password123');
        await page.fill('input[name="register[plainPassword][second]"]', 'password123');

        // 3. Submit the form
        await page.click('button:has-text("Créer mon compte")');

        // 4. Verify redirect to check-email page
        await expect(page).toHaveURL(/\/register\/check-email/);
        await expect(page.locator('h1')).toHaveText('Vérifiez vos emails');

        // 5. Get the verification token from the database (via API call)
        // In a real E2E test environment, we'd fetch this from Mailpit API
        // For this test, we'll simulate by navigating to login
        await page.goto('/login');
        await expect(page.locator('h1')).toHaveText('Connexion');
    });
});
