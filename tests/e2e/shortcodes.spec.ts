import { test, expect } from '@playwright/test';

/**
 * T013 — the six content shortcodes.
 *
 * scripts/seed.sh creates /shortcodes/ exercising all of them. An unexercised shortcode is
 * an untested one, and these had no natural home in the seeded pages.
 *
 * A NOTE ON WHY EVERY TEST HERE ALSO CHECKS PAGE SIZE: a PHP fatal in this environment
 * truncates the response but still returns **HTTP 200**, because display_errors is off. A
 * fatal in the [ale_toggle] asset check returned 200 with 510 bytes of HTML. A status-only
 * assertion cannot see that class of failure, so the sanity test below asserts on size and
 * content instead.
 */

/** Every seeded route must render substantially, not return a 200 with truncated output. */
const ROUTES = ['/', '/menu/', '/gallery/', '/events/', '/sobre/', '/contacto/', '/shortcodes/'];

test.describe('T013 — no page is a truncated fatal', () => {
  for (const route of ROUTES) {
    test(`${route} renders a real page`, async ({ page }) => {
      const errors: string[] = [];
      page.on('pageerror', (e) => errors.push(e.message));

      const response = await page.goto(route);
      expect(response?.status()).toBe(200);

      // A truncated fatal is small; a real page is tens of KB.
      const html = (await page.content()).length;
      expect(html, `${route} returned only ${html} bytes — likely a PHP fatal`).toBeGreaterThan(5000);

      // The bottom of the page must be present. footer.php emits <section class="footer">,
      // not a <footer> landmark — noted in T013's known risks rather than silently assumed.
      await expect(page.locator('section.footer, .footer').first()).toBeAttached();
      await expect(page.locator('body')).toBeVisible();
      expect(errors).toEqual([]);
    });
  }
});

test.describe('T013 — markup', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/shortcodes/');
  });

  test('ale_service renders its icon, title and content', async ({ page }) => {
    const el = page.locator('.ale-service').first();
    await expect(el).toBeVisible();
    await expect(el.locator('.servicetitle')).toHaveText('Service One');
    await expect(el.locator('.servicedescription')).toContainText('Service description one');
    await expect(el.locator('.iconbox img')).toHaveAttribute('src', /cake\.png$/);
  });

  test('ale_team renders the profile, bio and only the social links given', async ({ page }) => {
    const el = page.locator('.ale-team').first();
    await expect(el.locator('.testititle')).toHaveText('Team One');
    await expect(el.locator('.prof')).toHaveText('Physio');
    await expect(el.locator('.teamtextbox')).toContainText('Team bio one');
    // Only fblink was supplied: exactly one social button, not three with empty hrefs
    // (the source concatenated undefined variables here and emitted PHP notices).
    await expect(el.locator('.socialbut > div')).toHaveCount(1);
    await expect(el.locator('.fbbut a')).toHaveAttribute('href', 'https://facebook.com/example');
  });

  test('ale_testimonial renders name and content', async ({ page }) => {
    const el = page.locator('.ale-testimonial').first();
    await expect(el.locator('.testititle')).toHaveText('Client One');
    await expect(el.locator('.righttestimonialpart')).toContainText('A kind word');
  });

  test('ale_partner renders the logo link and the label', async ({ page }) => {
    const el = page.locator('.ale-partner').first();
    await expect(el.locator('.partnertitle')).toHaveText('Partner One');
    await expect(el.locator('.imagebox a')).toHaveAttribute('href', 'https://example.com/partner');
    await expect(el.locator('.imagebox img')).toHaveAttribute('src', /logo\.png$/);
  });
});

test.describe('T013 — ale_toggle', () => {
  test('state="open" renders expanded and visible', async ({ page }) => {
    await page.goto('/shortcodes/');
    const open = page.locator('.ale-toggle').first();
    await expect(open).toHaveClass(/is-open/);
    await expect(open.locator('.ale-toggle-title')).toHaveAttribute('aria-expanded', 'true');
    await expect(open.locator('.ale-toggle-inner')).toBeVisible();
  });

  test('state="closed" renders collapsed and hidden server-side', async ({ page }) => {
    await page.goto('/shortcodes/');
    const closed = page.locator('.ale-toggle').nth(1);
    await expect(closed).not.toHaveClass(/is-open/);
    // Hidden WITHOUT JS: the accessible fallback. The source emitted a <span> with no
    // handler at all, so this control never did anything.
    await expect(closed.locator('.ale-toggle-title')).toHaveAttribute('aria-expanded', 'false');
    await expect(closed.locator('.ale-toggle-inner')).toBeHidden();
  });

  test('the title is a real button that toggles on click', async ({ page }) => {
    await page.goto('/shortcodes/');
    const closed = page.locator('.ale-toggle').nth(1);
    const button = closed.locator('.ale-toggle-title');
    await expect(button).toHaveJSProperty('tagName', 'BUTTON');

    await button.click();
    await expect(button).toHaveAttribute('aria-expanded', 'true');
    await expect(closed.locator('.ale-toggle-inner')).toBeVisible();

    await button.click();
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await expect(closed.locator('.ale-toggle-inner')).toBeHidden();
  });
});

test.describe('T013 — ale_map', () => {
  test('coordinates render a sandboxed OpenStreetMap iframe', async ({ page }) => {
    await page.goto('/shortcodes/');
    const frame = page.locator('.cg-map iframe').first();
    await expect(frame).toHaveCount(1);
    const src = await frame.getAttribute('src');
    expect(src).toContain('openstreetmap.org/export/embed.html');
    // The comma stays literal: esc_url() does not percent-encode it, and a comma is legal
    // in a query string. (This assertion originally expected %2C and was wrong.)
    expect(src).toContain('marker=38.7223,-9.1393');
    await expect(frame).toHaveAttribute('sandbox', /allow-scripts/);
  });

  test('the height attribute is applied', async ({ page }) => {
    await page.goto('/shortcodes/');
    await expect(page.locator('.cg-map iframe').first()).toHaveAttribute('height', '300px');
  });

  test('an unresolvable address is refused, not embedded', async ({ page }) => {
    await page.goto('/shortcodes/');
    // The source geocoded server-side over plain HTTP with no API key and silently
    // returned nothing. This path must be explicit instead.
    await expect(page.locator('.cg-map-unresolved')).toBeVisible();
    await expect(page.locator('.cg-map-unresolved')).toContainText('not-a-provider.example/embed');
    await expect(page.locator('.cg-map iframe')).toHaveCount(1); // only the valid one
  });
});
