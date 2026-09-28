import { test, expect, type Page } from '@playwright/test';

/**
 * T012 — the About and Contact templates.
 *
 * Both were absent while `style.css` and `CLAUDE.md` advertised them. `functions.php` was
 * already conditionally enqueueing InitAbout.js for `template-about.php` — a script waiting
 * for a template that did not exist.
 *
 * scripts/seed.sh creates the two pages with their template and content, so these tests
 * assert rendering and the security behaviour of the map embed. The admin-UI driven tests for
 * the same meta live in meta.spec.ts.
 */

test.describe('T012 — template-about', () => {
  test('renders the team section from the seeded meta', async ({ page }) => {
    await page.goto('/sobre/');
    const team = page.locator('.our-team');
    await expect(team).toBeVisible();
    await expect(team.locator('h2').first()).toHaveText('A Nossa Equipa');
    // Four members, rendered by a loop rather than four hand-copied blocks.
    await expect(team.locator('.content .col-3')).toHaveCount(4);
    await expect(team).toContainText('Ana Silva');
    await expect(team).toContainText('David Reis');
  });

  test('renders the price section with prices', async ({ page }) => {
    await page.goto('/sobre/');
    const prices = page.locator('.home-price');
    await expect(prices).toBeVisible();
    await expect(prices.locator('.prices .col-3')).toHaveCount(4);
    await expect(prices).toContainText('40 EUR');
    await expect(prices).toContainText('Grátis');
  });

  test('does not render a section it has no content for', async ({ page }) => {
    // The templates only print a section when it has content, so an unfilled page is not a
    // wall of empty columns. This page has team + prices but no menubg.
    await page.goto('/sobre/');
    await expect(page.locator('.our-team')).toBeVisible();
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.reload();
    expect(errors).toEqual([]);
  });

  // The source template had no h1 at all. Caught by the same accessibility rule that
  // caught the home page in T007.
  test('has exactly one h1', async ({ page }) => {
    await page.goto('/sobre/');
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveText('Sobre');
  });
});

test.describe('T012 — template-contact', () => {
  test('renders the contact details and the form', async ({ page }) => {
    await page.goto('/contacto/');
    await expect(page.locator('.contact-us')).toBeVisible();
    const details = page.locator('.cg-contact-details');
    await expect(details).toBeVisible();
    await expect(details.locator('.cg-detail-address')).toContainText('Rua Exemplo 1');
    await expect(details.locator('.cg-detail-phone')).toContainText('210 000 000');
    await expect(details.locator('.cg-detail-email')).toContainText('geral@example.test');
    await expect(page.locator('form.cg-contact-form')).toBeVisible();
  });

  test('the phone link is tel: with digits only', async ({ page }) => {
    await page.goto('/contacto/');
    const href = await page.locator('.cg-detail-phone a').getAttribute('href');
    expect(href).toBe('tel:+351210000000');
  });

  test('refuses to embed a non-map URL', async ({ page }) => {
    await page.goto('/contacto/');
    // Seeded with a non-map URL on purpose.
    await expect(page.locator('.cg-map-invalid')).toBeVisible();
    await expect(page.locator('.cg-contact-map iframe')).toHaveCount(0);
  });

  test('rejects a submission with a bad nonce', async ({ page }) => {
    await page.goto('/contacto/');
    await page.fill('#cg-contact-name', 'Test');
    await page.fill('#cg-contact-email', 'test@example.test');
    await page.fill('#cg-contact-message', 'Hello');
    await page.evaluate(() => {
      const el = document.querySelector('input[name="cg_contact_nonce"]') as HTMLInputElement;
      el.value = 'deadbeef';
    });
    await page.click('.cg-contact-form button[type="submit"]');
    await expect(page.locator('.cg-contact-result.is-error')).toContainText(/expired/i);
  });

  test('reports validation errors for empty input', async ({ page }) => {
    await page.goto('/contacto/');
    await page.click('.cg-contact-form button[type="submit"]');
    await expect(page.locator('.cg-contact-result.is-error')).toBeVisible();
  });

  test('the honeypot is present in the DOM but off-screen and hidden from AT', async ({ page }) => {
    await page.goto('/contacto/');
    const hp = page.locator('.cg-hp');
    await expect(hp).toHaveCount(1);
    // It is positioned off-screen rather than display:none, because a bot that skips hidden
    // fields would simply not fill it — but that is the point. It must be invisible to a
    // human and absent from the accessibility tree, not literally `hidden`.
    await expect(hp).not.toBeInViewport();
    await expect(hp).toHaveAttribute('aria-hidden', 'true');
    // And it must be unfocusable by keyboard, or a human tabbing through hits a mystery field.
    await expect(page.locator('#cg-contact-website')).toHaveAttribute('tabindex', '-1');
  });

  test('the contact form partial is shared with the home page', async ({ page }) => {
    // One partial, two call sites — if these ever diverge, a fix to one silently misses
    // the other.
    const fieldIds = async (url: string) => {
      await page.goto(url);
      return page.locator('.cg-contact-form [id^="cg-contact-"]')
        .evaluateAll((els) => els.map((e) => e.id).sort());
    };
    expect(await fieldIds('/')).toEqual(await fieldIds('/contacto/'));
  });
});
