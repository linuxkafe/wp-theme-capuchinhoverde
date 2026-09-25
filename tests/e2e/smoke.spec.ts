import { test, expect } from '@playwright/test';

test('home page loads', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('h1')).toBeVisible();
});

test('menu archive loads', async ({ page }) => {
  await page.goto('/menu/');
  await expect(page.locator('h1.page-title')).toContainText('Menu');
});
