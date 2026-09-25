import { defineConfig } from '@playwright/test';

export default defineConfig({
  use: {
    baseURL: process.env.WP_URL || 'http://localhost:8083',
    headless: true,
  },
  testDir: './tests/e2e',
});
