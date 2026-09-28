import { defineConfig } from "@playwright/test";

export default defineConfig({
  use: {
    baseURL: process.env.WP_URL || "http://localhost:8083",
    headless: true,
  },
  testDir: "./tests/e2e",

  // The WordPress block editor takes 6–10s to hydrate before the theme meta box is
  // present, and the wp-admin login round-trip adds more. The default 30s timed out on
  // the very first admin-driven test, which looked like a product failure and was not.
  timeout: 90_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,

  // One retry, deliberately.
  //
  // On a host this memory- and CPU-starved the suite intermittently loses exactly one
  // test: the Chromium renderer dies mid-navigation with "Target page, context or
  // browser has been closed", and the failing test moves between runs. It is not
  // nondeterminism in the theme: the same product code produced 76/76 and then 75/76,
  // WordPress answers in 0.14s, and the affected tests pass standalone in 3-6s.
  //
  // retries:1 is the right tool rather than papering over, because Playwright keeps the
  // distinction visible instead of hiding it: a test that only passes on the retry is
  // reported as **flaky**, not passed, and the count is printed. A genuine regression
  // still fails, because it fails on every attempt. Treat a rising flaky count as a real
  // signal about the host, not as noise to ignore.
  retries: 1,

  reporter: process.env.CI ? [["list"], ["html", { open: "never" }]] : "list",
});
