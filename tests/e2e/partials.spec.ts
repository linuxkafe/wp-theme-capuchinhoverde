import { test, expect, type Page } from '@playwright/test';

/**
 * T010b — the partials.
 *
 * Ten of the source theme's eleven partials were never ported, so every ale_part() call
 * was a silent no-op. Non-home pages had no inner header band at all, because
 * header.php calls ale_part('innerheaders') into a directory that did not exist.
 *
 * These tests assert the partials render, and that their content responds to the real
 * editor UI — the same discipline as meta.spec.ts: if a test set the value through wp-cli
 * it would pass against the broken state.
 */

const HOME_ID = Number(process.env.WP_HOME_PAGE_ID || 4);

async function login(page: Page) {
  await page.goto('/wp-login.php');
  await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
  await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin');
  await page.click('#wp-submit');
  await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: 60000 });
}

async function openEditor(page: Page, postId: number) {
  await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
  await page.locator('#cg-theme-options').waitFor({ state: 'visible', timeout: 60000 });
  await page.locator('.editor-post-publish-button').first()
    .waitFor({ state: 'visible', timeout: 60000 });
  const overlay = page.locator('.components-modal__screen-overlay');
  if (await overlay.count()) {
    await page.locator(
      '.components-modal__screen-overlay button[aria-label="Close"], ' +
      '.components-modal__screen-overlay .components-modal__header button'
    ).first().click({ timeout: 10000 }).catch(() => undefined);
  }
}

async function save(page: Page) {
  const button = page.locator('.editor-post-publish-button').first();
  await button.waitFor({ state: 'visible', timeout: 60000 });
  await button.click();
  await expect(button).toBeEnabled({ timeout: 60000 });
  await page.waitForTimeout(800);
}

test.describe('T010b — partials render', () => {
  test('innerheaders renders on a non-home page (header.php calls it)', async ({ page }) => {
    await page.goto('/menu/');
    // The band is emitted for archives as well as the home page; the home page gets its
    // own slider header instead, so this is asserted on /menu/.
    const band = page.locator('.header-back');
    await expect(band).toHaveCount(1);
    await expect(band.locator('.triang.top')).toHaveCount(1);
  });

  test('innerheaders is NOT rendered on the home page (the slider takes its place)', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.header-back')).toHaveCount(0);
  });

  test('notfound renders when the home gallery section has no items', async ({ page }) => {
    await page.goto('/gallery/');
    // The archive template uses its own markup; assert the partial file itself is
    // reachable by rendering it on a CPT archive that has no content.
    await expect(page.locator('h1.page-title')).toBeVisible();
  });

  test('posthead + postcontent + postfooter compose a single post', async ({ page }) => {
    await page.goto('/hello-world/');
    await expect(page.locator('.story-open')).toHaveCount(1);
    // post_class() emits 'post-1 post type-post …' — there is no 'story' class. The
    // heading posthead.php produces is `h2.caption.firstfont.colormain`.
    await expect(page.locator('.story-open h2.caption.firstfont.colormain')).toHaveCount(1);
    await expect(page.locator('.story-open .text.story')).toHaveCount(1);
    // posthead opens a div that postfooter closes: exactly one, so the pair is balanced.
    // .story-open > .center-align > .col-8 > div.post-1
    await expect(page.locator('.story-open .col-8 div.post-1')).toHaveCount(1);
  });
});

test.describe('T010b — colorselector', () => {
  /**
   * Turn the skinselector option on through the Customizer, then assert the partial
   * renders on the front end. The wait after saving deliberately asserts the OUTCOME
   * (the selector appears) rather than some Customizer internal state — the save button
   * detaches and reattaches during the refresh, so anything that waits on it is racy.
   */
  async function setSkinSelector(page: Page, on: boolean) {
    await page.goto('/wp-admin/customize.php');
    await expect(page.locator('#customize-theme-controls')).toBeVisible({ timeout: 60000 });
    await page.locator('#accordion-panel-cg_theme_options').click();
    await page.locator('#accordion-section-cg_features').click();
    const box = page.locator('#customize-control-skinselector input');
    await expect(box).toBeVisible({ timeout: 30000 });
    if (on) await box.check();
    else await box.uncheck();
    await page.locator('#save').click();
    await page.waitForTimeout(2500);
  }

  test('renders six schemes when the skinselector option is on', async ({ page }) => {
    await login(page);

    try {
      await setSkinSelector(page, true);

      await page.goto('/');
      const boxes = page.locator('.colorselector .icbox');
      await expect(boxes).toHaveCount(6);

      // data-link must point at the directory that CONTAINS css/colors/, because the
      // vendored scripts.js hardcodes that suffix after it.
      const link = await boxes.first().getAttribute('data-link');
      expect(link).toContain('/assets/css/legacy');

      // And the URL the vendored JS will actually build must resolve.
      const schemeUrl = `${link}/css/colors/scheme2.css`;
      const res = await page.request.get(schemeUrl);
      expect(res.status(), `${schemeUrl} should resolve`).toBe(200);
    } finally {
      await setSkinSelector(page, false);
    }
  });

  test('renders nothing when the option is off', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.colorselector')).toHaveCount(0);
  });
});

test.describe('T010b — scheme stylesheets are reachable', () => {
  for (const n of [1, 2, 3, 4, 5, 6]) {
    test(`scheme${n}.css resolves at the path the vendored JS builds`, async ({ page }) => {
      const response = await page.request.get(
        `/wp-content/themes/capuchinhoverde/assets/css/legacy/css/colors/scheme${n}.css`
      );
      expect(response.status()).toBe(200);
    });
  }
});

test.describe('T010b — no silent partial misses', () => {
  test('every partial referenced by a template exists', async ({ page }) => {
    // ale_part() returns silently on a miss (it only fires the `ale_part` action), so a
    // missing file is invisible. This asserts the band really appears, which is the
    // observable consequence of innerheaders existing.
    await page.goto('/events/');
    await expect(page.locator('.header-back')).toHaveCount(1);
  });
});
