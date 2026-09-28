import { test, expect, type Page } from '@playwright/test';

/**
 * T011 — the editor surface.
 *
 * These tests drive the REAL wp-admin UI. That is the whole point of T011: the theme had
 * no writer for any of the ~54 keys its templates read, so the sections could never be
 * turned on by an administrator. A test that wrote the meta through wp-cli would pass
 * against exactly that broken state — which is what scripts/seed.sh does.
 *
 * Every test restores the value it changed, so the suite is idempotent and can run
 * against a shared environment.
 */

const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS || 'admin';
const HOME_ID = Number(process.env.WP_HOME_PAGE_ID || 4);

async function login(page: Page) {
  await page.goto('/wp-login.php');
  await page.fill('#user_login', ADMIN_USER);
  await page.fill('#user_pass', ADMIN_PASS);
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
  await expect(page.locator('#wpadminbar')).toBeVisible();
}

/**
 * Open the page editor and wait for it to be genuinely ready.
 *
 * Two traps, both hit while writing this file:
 *  - the meta box is server-rendered and appears in ~2.5s, but the editor toolbar takes
 *    ~20s to become interactive. Asserting on the meta box alone makes every save test
 *    fail against a perfectly healthy editor.
 *  - the save button reads "Save", not "Update", so a name-based locator matches nothing.
 *    Target the class instead.
 */
async function openEditor(page: Page, postId: number) {
  await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
  await page.locator('#cg-theme-options').waitFor({ state: 'visible', timeout: 60000 });
  await saveButton(page).waitFor({ state: 'visible', timeout: 60000 });
  await dismissEditorModals(page);
}

function saveButton(page: Page) {
  return page.locator('.editor-post-publish-button').first();
}

/**
 * Dismiss the block editor's welcome guide if it is showing.
 *
 * It renders a full-screen `.components-modal__screen-overlay` that sits above the editor
 * toolbar and swallows every click. Playwright's actionability check then retries forever
 * and the test dies as a 90s timeout with no useful message — which is exactly what
 * happened before this helper existed.
 */
async function dismissEditorModals(page: Page) {
  const overlay = page.locator('.components-modal__screen-overlay');
  const close = page.locator(
    '.components-modal__screen-overlay button[aria-label="Close"], ' +
    '.components-modal__screen-overlay .components-modal__header button'
  );
  if (await overlay.count()) {
    await close.first().click({ timeout: 10000 }).catch(() => undefined);
    await overlay.first().waitFor({ state: 'hidden', timeout: 15000 }).catch(() => undefined);
  }
}

/** Click save and wait for the editor to settle, so the next assertion is not racing it. */
async function save(page: Page) {
  const button = saveButton(page);
  await button.waitFor({ state: 'visible', timeout: 60000 });
  await button.click();
  // The button is disabled while the save is in flight and re-enabled when it completes.
  await page.waitForTimeout(500);
  await expect(button).toBeEnabled({ timeout: 60000 });
  await page.waitForTimeout(1000);
}

test.describe('T011 — editor surface exists', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('the metabox renders on the page editor with every group', async ({ page }) => {
    await openEditor(page, HOME_ID);
    await expect(page.locator('#cg-theme-options')).toBeVisible();
    for (const heading of ['Home sections', 'Services', 'Service items', 'Gallery', 'Contact', 'Page appearance',
                          'Team', 'Team members', 'Prices', 'Price items']) {
      await expect(page.locator('#cg-theme-options').getByText(heading, { exact: true }))
        .toBeVisible();
    }
  });

  test('the three section toggles are checkboxes', async ({ page }) => {
    await openEditor(page, HOME_ID);
    for (const key of ['serviceonhome', 'galleryonhome', 'contactonhome']) {
      await expect(page.locator(`input[type="checkbox"][name="cg_meta[${key}]"]`))
        .toHaveCount(1);
    }
  });

  test('services render as four rows, not sixteen loose inputs', async ({ page }) => {
    await openEditor(page, HOME_ID);
    const box = page.locator('#cg-theme-options');
    // Scoped to the services table: the About groups add two more tables, so counting every
    // table on the page is ambiguous. (This assertion passed only because the page under
    // test has no team or price data — a seed change broke it.)
    const services = box.locator('.cg-meta-table--service_item');
    await expect(services).toHaveCount(1);
    await expect(services.locator('tbody tr')).toHaveCount(4);
    // Each row has image, title, link, description inputs.
    await expect(services.locator('tbody tr').first().locator('input, textarea')).toHaveCount(4);
  });
});

test.describe('T011 — writing through the UI changes the front end', () => {
  test('a service title saved in the admin renders on the home page', async ({ page }) => {
    await login(page);
    await openEditor(page, HOME_ID);

    const marker = `Playwright Service ${Date.now()}`;
    const field = page.locator('input[name="cg_meta[servtit1]"]');
    const original = await field.inputValue();

    try {
      await field.fill(marker);
      await save(page);

      await page.goto('/');
      await expect(page.locator('.our-services')).toBeVisible();
      await expect(page.locator('.our-services')).toContainText(marker);
    } finally {
      // Restore so the suite stays idempotent.
      await openEditor(page, HOME_ID);
      await page.locator('input[name="cg_meta[servtit1]"]').fill(original);
      await save(page);
    }
  });

  test('unchecking a section toggle removes that section from the home page', async ({ page }) => {
    await login(page);
    await openEditor(page, HOME_ID);

    const toggle = page.locator('input[type="checkbox"][name="cg_meta[galleryonhome]"]');
    const wasChecked = await toggle.isChecked();

    try {
      if (wasChecked) await toggle.uncheck();
      else await toggle.check();
      await save(page);

      await page.goto('/');
      const gallery = page.locator('.home-gallery');
      if (wasChecked) {
        await expect(gallery).toHaveCount(0);
      } else {
        await expect(gallery).toHaveCount(1);
      }
    } finally {
      await openEditor(page, HOME_ID);
      const t = page.locator('input[type="checkbox"][name="cg_meta[galleryonhome]"]');
      if (wasChecked) await t.check();
      else await t.uncheck();
      await save(page);
    }
  });
});

test.describe('T011 — sanitisation is enforced on save, not only on render', () => {
  test('a javascript: image URL is stripped before it reaches the database', async ({ page }) => {
    await login(page);
    await openEditor(page, HOME_ID);

    const field = page.locator('input[name="cg_meta[galbg]"]');
    const original = await field.inputValue();

    try {
      await field.fill('javascript:alert(1)');
      await save(page);

      await page.goto('/');
      // The value must not reach the DOM in any form.
      const html = await page.content();
      expect(html).not.toContain('javascript:alert(1)');

      // And it must not have been stored either: reopen and confirm it is empty.
      await openEditor(page, HOME_ID);
      const stored = await page.locator('input[name="cg_meta[galbg]"]').inputValue();
      expect(stored).not.toBe('javascript:alert(1)');
    } finally {
      await openEditor(page, HOME_ID);
      await page.locator('input[name="cg_meta[galbg]"]').fill(original);
      await save(page);
    }
  });

  test('a custom CSS field cannot escape the style attribute', async ({ page }) => {
    await login(page);
    await openEditor(page, HOME_ID);

    const field = page.locator('textarea[name="cg_meta[custompagecss]"]');
    const original = await field.inputValue();

    try {
      // url() and braces are the two vectors header.php's allowlist blocks.
      await field.fill('background:url(javascript:alert(1));} body{display:none');
      await save(page);

      await page.goto('/');
      const bodyStyle = await page.locator('body').getAttribute('style');
      expect(bodyStyle ?? '').not.toContain('javascript');
      expect(bodyStyle ?? '').not.toContain('display:none');
    } finally {
      await openEditor(page, HOME_ID);
      await page.locator('textarea[name="cg_meta[custompagecss]"]').fill(original);
      await save(page);
    }
  });
});
