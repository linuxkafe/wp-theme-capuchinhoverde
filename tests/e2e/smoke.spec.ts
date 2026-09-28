import { test, expect } from '@playwright/test';

/**
 * Smoke + parity specs.
 *
 * These assertions had never been executed before 2026-09-27 — `make test` always
 * SKIPped because no WordPress existed. A spec that has never run is a guess, not a
 * test. `make wp-seed` must be run first; it creates the content these specs assume.
 *
 * The specs assert only what the port has actually delivered. A spec asserting an
 * unported feature would be a false claim, so unported features are listed in
 * docs/PARITY.md instead of being encoded here as failures.
 */

test.describe('smoke', () => {
  test('home page loads', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('h1')).toBeVisible();
  });

  test('menu archive loads', async ({ page }) => {
    await page.goto('/menu/');
    await expect(page.locator('h1.page-title')).toContainText('Menu');
  });
});

test.describe('T007 — defects fixed in this sprint', () => {
  test('no PHP fatal on the home page', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);
    expect(errors).toEqual([]);
  });

  // F4/F5: ale_modules.js and ale_scripts.js 404'd on every page load.
  //
  // NOTE: these assert on ANY non-2xx, not just 404. The first version of this test only
  // looked for 404 and passed while 82 asset files were serving as 403 Forbidden — they
  // had been copied from the source theme with its 0700 permissions, so Apache (uid 33)
  // could not read them. A 404-only assertion cannot see that class of failure.
  test('every enqueued script resolves', async ({ page }) => {
    const bad: string[] = [];
    page.on('response', (r) => {
      if (r.status() >= 400 && r.url().includes('/assets/')) {
        bad.push(`${r.status()} ${r.url()}`);
      }
    });
    await page.goto('/');
    expect(bad).toEqual([]);
  });

  // F6: './assets/css/editor.css' was a relative URL and 404'd.
  test('every enqueued stylesheet resolves', async ({ page }) => {
    const bad: string[] = [];
    page.on('response', (r) => {
      if (r.status() >= 400 && r.url().endsWith('.css')) {
        bad.push(`${r.status()} ${r.url()}`);
      }
    });
    await page.goto('/');
    expect(bad).toEqual([]);
  });

  // The vendored CSS references images by relative url(). Those are not "enqueued", so no
  // earlier test fetched them — which is how the 403s went unnoticed. Walk every page so
  // the browser actually requests the images the design depends on.
  for (const path of ['/', '/menu/', '/gallery/', '/events/']) {
    test(`theme assets referenced by CSS resolve on ${path}`, async ({ page }) => {
      const bad: string[] = [];
      page.on('response', (r) => {
        if (r.status() >= 400 && r.url().includes('/themes/capuchinhoverde/assets/')) {
          bad.push(`${r.status()} ${r.url()}`);
        }
      });
      await page.goto(path);
      // Give the browser a moment to fetch the CSS-referenced backgrounds.
      await page.waitForTimeout(1200);
      expect(bad).toEqual([]);
    });
  }

  // F1/F2: /menu/ fell through to the front page because CPT rewrite rules were never
  // flushed, and the contact POST returned HTTP 500.
  test('CPT archives serve their own template, not the front page', async ({ page }) => {
    for (const path of ['/menu/', '/gallery/', '/events/']) {
      const response = await page.goto(path);
      expect(response?.status(), `${path} should return 200`).toBe(200);
      await expect(page.locator('h1.page-title'), `${path} should be an archive`).toBeVisible();
    }
  });

  test('contact form is present and nonce-protected', async ({ page }) => {
    await page.goto('/');
    const form = page.locator('form.cg-contact-form');
    await expect(form).toBeVisible();
    await expect(form.locator('input[name="cg_contact_nonce"]')).toHaveCount(1);
  });

  test('contact form rejects a submission with a bad nonce', async ({ page }) => {
    await page.goto('/');
    await page.fill('#cg-contact-name', 'Test');
    await page.fill('#cg-contact-email', 'test@example.test');
    await page.fill('#cg-contact-message', 'Hello');
    await page.evaluate(() => {
      const el = document.querySelector('input[name="cg_contact_nonce"]') as HTMLInputElement;
      el.value = 'deadbeef';
    });
    await page.click('.cg-contact-form button[type="submit"]');
    await expect(page.locator('.cg-contact-result.is-error')).toContainText(/expired|correct/i);
  });

  test('contact form reports validation errors for empty input', async ({ page }) => {
    await page.goto('/');
    await page.click('.cg-contact-form button[type="submit"]');
    await expect(page.locator('.cg-contact-result.is-error')).toBeVisible();
  });
});

test.describe('T006 — design tokens', () => {
  // The dark AES template palette contradicted every vendored stylesheet and caused
  // the "black page" defect in commit 72a0b4f.
  test('the dark template palette is gone', async ({ page }) => {
    await page.goto('/');
    const bg = await page.evaluate(() =>
      getComputedStyle(document.body).backgroundColor
    );
    expect(bg).not.toBe('rgb(10, 10, 10)');
  });

  test('the Cafeteria cream background is applied', async ({ page }) => {
    await page.goto('/');
    const bg = await page.evaluate(() =>
      getComputedStyle(document.body).backgroundColor
    );
    expect(bg).toBe('rgb(240, 236, 227)');
  });

  // .colormain and .firstfont were undefined everywhere because the source generated
  // them from partials/css-option.php, which was never ported.
  //
  // CAVEAT, added by T015: this assertion CANNOT detect a broken home slider, because the
  // empty-state fallback in page-home.php also carries `firstfont caption colormain`. It
  // passed while the slider saved nothing the front end could read. The test that can
  // fail for that reason is in slider.spec.ts, which asserts a configured slide by title.
  test('colormain resolves to the clay heading colour', async ({ page }) => {
    await page.goto('/');
    const colour = await page.evaluate(() => {
      const el = document.querySelector('.colormain');
      return el ? getComputedStyle(el).color : null;
    });
    expect(colour).toBe('rgb(94, 60, 61)');
  });
});

test.describe('accessibility', () => {
  // docs/REQUIREMENTS.md claims WCAG 2.1 AA; every page needs exactly one h1.
  for (const path of ['/', '/menu/', '/gallery/', '/events/']) {
    test(`${path} has exactly one h1`, async ({ page }) => {
      await page.goto(path);
      await expect(page.locator('h1')).toHaveCount(1);
    });
  }
});
