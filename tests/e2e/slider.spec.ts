import { test, expect, type Page } from "@playwright/test";

/**
 * T015 — the slider, end to end, through the real wp-admin UI.
 *
 * Peer review round 1 found that the home slider could not be configured at all:
 *   B3 — opening any cg_slider post in the editor was a PHP fatal, because the `json`
 *        metabox group called esc_textarea() on an array.
 *   B4 — the only writer of _cg_slides produced a JSON string and the only reader
 *        required an array, so the front end discarded every slide the UI saved.
 *   B2 — the colour-scheme selector in the vendored layer used jQuery's removed .live().
 *
 * The reason these went unnoticed is a lesson worth keeping: the existing suite asserted
 * `.colormain` on the home page, and that assertion is satisfied by the EMPTY-STATE
 * FALLBACK (page-home.php renders `firstfont caption colormain` inside cg-slider-empty).
 * So the suite stayed green while the slider was completely broken. These tests assert on
 * a *configured* slide by its title, which the fallback can never satisfy.
 *
 * Every test restores the value it changed, so the suite stays idempotent.
 */

const ADMIN_USER = process.env.WP_ADMIN_USER || "admin";
const ADMIN_PASS = process.env.WP_ADMIN_PASS || "admin";

async function login(page: Page) {
  await page.goto("/wp-login.php");
  await page.fill("#user_login", ADMIN_USER);
  await page.fill("#user_pass", ADMIN_PASS);
  await Promise.all([page.waitForNavigation(), page.click("#wp-submit")]);
  await expect(page.locator("#wpadminbar")).toBeVisible();
}

function saveButton(page: Page) {
  return page.locator(".editor-post-publish-button").first();
}

async function dismissEditorModals(page: Page) {
  const overlay = page.locator(".components-modal__screen-overlay");
  const close = page.locator(
    '.components-modal__screen-overlay button[aria-label="Close"], ' +
      ".components-modal__screen-overlay .components-modal__header button",
  );
  if (await overlay.count()) {
    await close
      .first()
      .click({ timeout: 10000 })
      .catch(() => undefined);
    await overlay
      .first()
      .waitFor({ state: "hidden", timeout: 15000 })
      .catch(() => undefined);
  }
}

/**
 * Find the seeded slider's post id from the admin list.
 *
 * Deliberately not an env var: seed.sh creates the post and prints its id, but nothing
 * exports it to the Playwright process, so an env-var id would silently drift out of sync
 * with the seed and the tests would skip or target the wrong post.
 */
async function findSliderId(page: Page): Promise<number> {
  await page.goto("/wp-admin/edit.php?post_type=cg_slider");
  const row = page.locator("#the-list tr").first();
  await expect(row).toBeVisible({ timeout: 30000 });
  const id = await row.getAttribute("id");
  const postId = Number((id || "").replace("post-", ""));
  expect(Number.isFinite(postId) && postId > 0).toBe(true);
  return postId;
}

async function openSliderEditor(page: Page, postId: number) {
  await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
  await page
    .locator("#cg-theme-options")
    .waitFor({ state: "visible", timeout: 60000 });
  await saveButton(page).waitFor({ state: "visible", timeout: 60000 });
  await dismissEditorModals(page);
}

async function save(page: Page) {
  const button = saveButton(page);
  await button.waitFor({ state: "visible", timeout: 60000 });
  await button.click();
  await page.waitForTimeout(500);
  await expect(button).toBeEnabled({ timeout: 60000 });
  await page.waitForTimeout(1000);
}

function slidesField(page: Page) {
  return page.locator('#cg-theme-options textarea[name="cg_meta[_cg_slides]"]');
}

const PROBE_TITLE = "T015 probe slide";

test.describe("T015 — B3: the slider editor must not fatal", () => {
  test("opening a Slider with populated slides does not raise a critical error", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);

    const errors: string[] = [];
    page.on("pageerror", (e) => errors.push(e.message));

    await openSliderEditor(page, postId);

    // The fatal rendered as a WordPress "critical error" page. The theme header comment
    // says the group heading is "Slides"; assert the textarea is present AND populated,
    // because an unpopulated textarea would also pass a bare visibility check.
    const field = slidesField(page);
    await expect(field).toBeVisible({ timeout: 30000 });
    const value = await field.inputValue();

    // B3's actual failure was esc_textarea() on an array: rendering a populated json
    // field was what crashed. So the assertion is on the CONTENT, not just presence.
    expect(
      value.trim(),
      "the Slides textarea rendered empty — the json branch is not reading the stored array",
    ).not.toBe("");
    expect(() => JSON.parse(value)).not.toThrow();

    expect(errors, "a JS pageerror escaped the editor").toEqual([]);
  });
});

test.describe("T015 — B4: slides saved through the UI must render", () => {
  test("a slide saved in the admin appears on the home page", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);

    const field = slidesField(page);
    await expect(field).toBeVisible({ timeout: 30000 });

    const original = await field.inputValue();

    // Write through the UI only. A wp-cli write here would pass against exactly the
    // writer/reader mismatch this test exists to catch.
    await field.fill(
      JSON.stringify(
        [
          {
            image: "",
            title: PROBE_TITLE,
            description: "probe",
            url: "http://localhost:8083/",
          },
        ],
        null,
        2,
      ),
    );
    await save(page);

    // Re-open: this is the B4 round trip. Saving a JSON string and re-reading it as an
    // array is what the mismatch broke, so the value must survive a reload.
    await openSliderEditor(page, postId);
    const reread = await slidesField(page).inputValue();
    expect(reread, "the slide did not survive an editor round trip").toContain(
      PROBE_TITLE,
    );

    // The front end must show the configured slide, not the empty-state fallback.
    await page.goto("/");
    const slides = page.locator(".slider .slides");
    await expect(slides).toBeVisible();
    await expect(
      slides.locator(`li h2.caption.colormain`, { hasText: PROBE_TITLE }),
      "the configured slide is missing — the reader discarded what the writer saved",
    ).toHaveCount(1);
    await expect(
      slides.locator(".cg-slider-empty"),
      "the home page fell back to the empty-state, so the saved slide was ignored",
    ).toHaveCount(0);

    // Restore, so the suite is idempotent.
    await login(page);
    await openSliderEditor(page, postId);
    await slidesField(page).fill(original);
    await save(page);
  });
});

test.describe("T015 — B2: jQuery .live() shim", () => {
  /**
   * This asserts the MECHANISM, not the click.
   *
   * The obvious test — "no pageerror, then click .colorselector .icbox" — cannot fail
   * when skinselector is off, because the vendored block is guarded by
   * `if($('.colorselector').length)`: with no selector in the DOM the `.live()` call is
   * never reached, so the page is clean whether or not the shim exists. That is the same
   * trap that let B2 through in the first place.
   *
   * So: assert jQuery.fn.live exists. Without the shim it is undefined and this fails.
   * The behavioural half of B2's closure condition (clicking .icbox appends
   * <link id="schemeN-css">) needs skinselector=1 written through the Customizer, and is
   * left to aes/peer-reviews/sprint-02-03-port/human-validation.sh — it is not claimed here.
   */
  test("jQuery.fn.live is restored and loads before the vendored scripts", async ({
    page,
  }) => {
    await page.goto("/");
    await page.waitForLoadState("domcontentloaded");

    const probe = await page.evaluate(() => {
      const jq = (window as any).jQuery;
      return {
        jquery: jq ? jq.fn.jquery : null,
        hasLive: Boolean(jq && jq.fn && typeof jq.fn.live === "function"),
        compatBeforeScripts: (() => {
          const order = Array.from(
            document.querySelectorAll("script[src]"),
          ).map((s) => (s as HTMLScriptElement).src);
          const compat = order.findIndex((s) => s.includes("legacy-compat.js"));
          const legacy = order.findIndex((s) => /legacy\/scripts\.js/.test(s));
          return compat !== -1 && legacy !== -1 && compat < legacy;
        })(),
      };
    });

    expect(
      probe.hasLive,
      "jQuery.fn.live is undefined — the .live() shim is missing or did not run",
    ).toBe(true);
    expect(
      probe.compatBeforeScripts,
      "legacy-compat.js must load before assets/js/legacy/scripts.js",
    ).toBe(true);

    // The vendored layer also calls .slideToggle-style jQuery plugins and the removed
    // .live(); no pageerror is the floor, not the proof.
    const errors: string[] = [];
    page.on("pageerror", (e) => errors.push(e.message));
    await page.reload();
    await page.waitForLoadState("networkidle");
    expect(
      errors.filter((m) => m.includes("is not a function")),
      "a jQuery method is missing at runtime",
    ).toEqual([]);
  });
});
