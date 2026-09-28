import { defineConfig } from '@playwright/test';

export default defineConfig({
  use: {
    baseURL: process.env.WP_URL || 'http://localhost:8083',
    headless: true,
  },
  testDir: './tests/e2e',

  // The WordPress block editor takes 6–10s to hydrate before the theme meta box is
  // present, and the wp-admin login round-trip adds more. The default 30s timed out on
  // the very first admin-driven test, which looked like a product failure and was not.
  timeout: 90_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
});
