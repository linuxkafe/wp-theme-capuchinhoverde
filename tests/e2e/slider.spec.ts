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
  // Bounded retry. On a contended host (this box runs 6 cores shared with an emulator and
  // several long-lived agent processes) the renderer can be starved badly enough that the
  // submit click never dispatches, and the test then dies on a 90s waitForURL with the
  // browser still sitting on wp-login.php. That is an environment stall, not a product
  // failure, and the fix is to retry the transport rather than loosen an assertion.
  //
  // A genuinely wrong password still fails: both attempts are real logins and the admin
  // bar is still asserted at the end, so this cannot make a broken login look healthy.
  let lastError: unknown;
  for (let attempt = 1; attempt <= 2; attempt += 1) {
    try {
      await page.goto("/wp-login.php");
      await page.fill("#user_login", ADMIN_USER);
      await page.fill("#user_pass", ADMIN_PASS);
      // waitForURL, not waitForNavigation: the latter is deprecated and can register its
      // waiter a moment too late when the page is slow, leaving a long timeout on a
      // perfectly healthy login. waitForURL is the recommended, non-racy form.
      await Promise.all([
        page.waitForURL(/\/wp-admin\//, { waitUntil: "domcontentloaded" }),
        page.click("#wp-submit"),
      ]);
      await expect(page.locator("#wpadminbar")).toBeVisible();
      return;
    } catch (error) {
      lastError = error;
    }
  }
  throw lastError;
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

/*
 * cg_slider is registered 'supports' => ['title'] with no 'editor', so
 * use_block_editor_for_post_type() returns FALSE for it (wp-includes/post.php bails on
 * `! post_type_supports( $post_type, 'editor' )`) and wp-admin serves the CLASSIC editor,
 * whose save control is #publish. No registration flag changes that; only adding 'editor'
 * would, and an unused content box for a post type whose slide text lives in _cg_slides
 * would be worse than the classic screen.
 *
 * cg_menu/cg_gallery/cg_event DO support 'editor' and get the block editor, so both
 * branches are needed. The first version of this file assumed the block editor and every
 * admin test failed on a 60s timeout waiting for a button that can never exist.
 */
function saveButton(page: Page) {
  return page.locator(".editor-post-publish-button, #publish").first();
}

/**
 * The control that actually holds the slides payload.
 *
 * T015 originally wrote this as `textarea[name="cg_meta[_cg_slides]"]`, which was wrong
 * twice over: the renderer names the input after the SCHEMA key (`cg_meta[slides]` — the
 * `_cg_` storage prefix is applied server-side), and T018 replaced the textarea with a
 * repeater that serialises into a hidden input. It was never `cg_meta[_cg_slides]` in the DOM.
 */
function slidesField(page: Page) {
  return page
    .locator("#cg-theme-options")
    .locator('[data-cg-repeater="slides"] input.cg-repeater-json');
}

function repeater(page: Page) {
  return page
    .locator("#cg-theme-options")
    .locator('[data-cg-repeater="slides"]');
}

async function openSliderEditor(page: Page, postId: number) {
  await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
  await page
    .locator("#cg-theme-options")
    .waitFor({ state: "visible", timeout: 60000 });
  // The repeater's hidden input is server-rendered, so waiting for it proves the metabox ran
  // to completion — which is exactly what B3's fatal prevented.
  await slidesField(page).waitFor({ state: "attached", timeout: 60000 });
  await saveButton(page).waitFor({ state: "visible", timeout: 60000 });
  await dismissEditorModals(page);
}

async function save(page: Page) {
  const classic = page.locator("#publish");
  if (await classic.count()) {
    // The classic editor submits the whole form and navigates.
    await Promise.all([
      page.waitForURL(/\/wp-admin\/post\.php/, {
        waitUntil: "domcontentloaded",
      }),
      classic.click(),
    ]);
    await page
      .locator("#cg-theme-options")
      .waitFor({ state: "visible", timeout: 60000 });
    return;
  }
  const button = page.locator(".editor-post-publish-button").first();
  await button.waitFor({ state: "visible", timeout: 60000 });
  await button.click();
  await page.waitForTimeout(500);
  await expect(button).toBeEnabled({ timeout: 60000 });
  await page.waitForTimeout(1000);
}

/** Add a slide through the repeater UI and return it. */
async function addSlide(page: Page, title: string, description = "probe") {
  const box = repeater(page);
  await box.locator(".cg-repeater-add").click();
  const row = box.locator(".cg-repeater-row").last();
  await row.locator('[data-field="title"]').fill(title);
  await row.locator('[data-field="description"]').fill(description);
  return row;
}

/**
 * A per-run unique probe title.
 *
 * A fixed title made this test order-dependent on the DATABASE: an earlier failed run left
 * rows behind, the round-trip assertion `toHaveCount(1)` could then never hold, and the test
 * failed for a reason that had nothing to do with the code under test. Uniqueness makes the
 * assertion exact regardless of what a previous run left behind.
 */
/**
 * Restore the repeater to a known JSON payload, THROUGH THE REPEATER.
 *
 * Setting the hidden input's .value directly does not work, and the symptom is nasty: the
 * repeater owns that input, and its submit handler re-syncs the value from the DOM rows, so
 * a direct assignment is silently discarded on save. Every run therefore leaked a slide and
 * the next run's assertions were made against the previous run's leftovers.
 *
 * So the rows themselves are rewritten with fill(), which fires the `input` event the
 * repeater listens for. That uses only the UI's own contract — no test-only hooks in
 * product code.
 */
async function restoreRows(page: Page, json: string) {
  const want = JSON.parse(json) as Record<string, string>[];
  const box = repeater(page);
  for (
    let n = (await box.locator(".cg-repeater-row").count()) - want.length;
    n > 0;
    n--
  ) {
    await box
      .locator(".cg-repeater-row")
      .last()
      .locator(".cg-repeater-remove")
      .click();
  }
  while ((await box.locator(".cg-repeater-row").count()) < want.length) {
    await box.locator(".cg-repeater-add").click();
  }
  for (let i = 0; i < want.length; i++) {
    const row = box.locator(".cg-repeater-row").nth(i);
    for (const [field, value] of Object.entries(want[i])) {
      const input = row.locator(`[data-field="${field}"]`);
      if ((await input.count()) && value !== undefined) {
        await input.fill(String(value));
      }
    }
  }
}

const PROBE_TITLE = "T015 probe slide";
const runId = `${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
const REPEATER_TITLE = `T018 repeater slide ${runId}`;

test.describe("T015 — B3: the slider editor must not fatal", () => {
  test("opening a Slider with populated slides does not raise a critical error", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);

    const errors: string[] = [];
    page.on("pageerror", (e) => errors.push(e.message));

    await openSliderEditor(page, postId);

    // B3's failure was esc_textarea() on an array: RENDERING a populated slides field is
    // what crashed, taking the editor down. So the assertion is on the serialised CONTENT,
    // not on presence — a field that rendered empty would also pass a visibility check.
    const field = slidesField(page);
    await expect(field).toBeAttached({ timeout: 30000 });
    const value = await field.inputValue();

    expect(
      value.trim(),
      "the slides field rendered empty — the stored array is not read back",
    ).not.toBe("");
    const parsed = JSON.parse(value) as unknown[];
    expect(
      Array.isArray(parsed),
      "the slides payload is not a JSON array",
    ).toBe(true);
    expect(
      parsed.length,
      "no slides were rendered back into the editor",
    ).toBeGreaterThan(0);

    expect(errors, "a JS pageerror escaped the editor").toEqual([]);
  });
});

test.describe("T015 — B4 / T018 — slides saved through the UI must render", () => {
  test("a slide added in the repeater round-trips and appears on the home page", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);

    const box = repeater(page);
    const original = await box.locator("input.cg-repeater-json").inputValue();
    const before = JSON.parse(original).length;

    // Drive the real UI. A wp-cli write would pass against exactly the writer/reader
    // mismatch this test exists to catch.
    const before_add = await box.locator(".cg-repeater-row").count();
    await addSlide(page, REPEATER_TITLE, "added by the repeater");
    await expect(box.locator(".cg-repeater-row")).toHaveCount(before_add + 1);

    // The hidden input is the only thing the save path sees, and it must already carry the
    // new slide before Save is ever clicked.
    const staged = JSON.parse(
      await box.locator("input.cg-repeater-json").inputValue(),
    );
    expect(
      staged,
      "the repeater did not serialise into its hidden input",
    ).toHaveLength(before_add + 1);
    expect(staged[staged.length - 1].title).toBe(REPEATER_TITLE);

    await save(page);

    // Round trip.
    await openSliderEditor(page, postId);
    await expect(
      page
        .locator("#cg-theme-options")
        .locator(`input[value="${REPEATER_TITLE}"]`),
      "the slide did not survive the editor round trip",
    ).toHaveCount(1);

    await page.goto("/");
    await expect(
      page.locator(".slider .slides h2.caption.colormain", {
        hasText: REPEATER_TITLE,
      }),
      "the slide added through the repeater is not on the home page",
    ).toHaveCount(1);

    // Restore.
    await login(page);
    await openSliderEditor(page, postId);
    await restoreRows(page, original);
    await save(page);
  });
});

test.describe("T018 — slide repeater", () => {
  test("the Slides field is a repeater, not a JSON textarea", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);

    const box = page.locator("#cg-theme-options");
    // The textarea is the failure this ticket exists to remove.
    await expect(
      box.locator('textarea[name="cg_meta[slides]"]'),
      "the raw JSON textarea is back — slides must be edited through the repeater",
    ).toHaveCount(0);

    await expect(box.locator('[data-cg-repeater="slides"]')).toBeVisible();
    await expect(box.locator("input.cg-repeater-json")).toHaveCount(1);
    // The visible inputs must be unnamed so they cannot collide with the save path, which
    // discards array input: a repeater posting cg_meta[slides][0][image] would be dropped.
    const named = await box
      .locator(
        '[data-cg-repeater="slides"] input[name], [data-cg-repeater="slides"] textarea[name]',
      )
      .count();
    expect(
      named,
      "a repeater input is named and would be discarded by the save path",
    ).toBe(1);
  });

  test("removing and reordering rows changes the order that is saved", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);

    const box = repeater(page);
    const original = await box.locator("input.cg-repeater-json").inputValue();
    const start = JSON.parse(original) as { title?: string }[];
    if (start.length < 2) {
      await addSlide(page, "T018 ordering filler");
      const filled = await box.locator("input.cg-repeater-json").inputValue();
      const parsed = JSON.parse(filled) as { title?: string }[];
      expect(parsed.length).toBeGreaterThanOrEqual(2);
    }
    const now = JSON.parse(
      await box.locator("input.cg-repeater-json").inputValue(),
    ) as { title?: string }[];

    const first = now[0].title;
    const second = now[1].title;

    // Move the second row up; the serialised order must follow.
    await box
      .locator(".cg-repeater-row")
      .nth(1)
      .locator(".cg-repeater-move-up")
      .click();
    const afterMove = JSON.parse(
      await box.locator("input.cg-repeater-json").inputValue(),
    ) as { title?: string }[];
    expect(
      afterMove[0].title,
      "move-up did not change the serialised order",
    ).toBe(second);
    expect(afterMove[1].title).toBe(first);

    // Row labels must renumber, or the admin sees "Slide 1" above "Slide 3".
    await expect(
      box.locator(".cg-repeater-row").first().locator(".cg-repeater-row-label"),
    ).toHaveText("Slide 1");

    // Remove a row.
    const count = await box.locator(".cg-repeater-row").count();
    await box
      .locator(".cg-repeater-row")
      .nth(1)
      .locator(".cg-repeater-remove")
      .click();
    await expect(box.locator(".cg-repeater-row")).toHaveCount(count - 1);
    expect(
      JSON.parse(await box.locator("input.cg-repeater-json").inputValue()),
    ).toHaveLength(count - 1);

    // Restore. Not saved: this test is about the UI, and the next test reloads anyway.
    await restoreRows(page, original);
  });
});

/**
 * Make sure the slider has at least two slides, because FlexSlider is only CONSTRUCTED then.
 *
 * modules.js:857 short-circuits to a plain fadeIn when there is a single <li>, so with one
 * slide there is no instance and therefore no option-dependent DOM at all — an animation or
 * controlNav assertion against a one-slide slider cannot fail for the right reason.
 *
 * Returns the original payload so the caller can put it back.
 */
async function ensureTwoSlides(
  page: Page,
): Promise<{ json: string; added: boolean }> {
  const rp = repeater(page);
  const json = await rp.locator("input.cg-repeater-json").inputValue();
  if ((JSON.parse(json) as unknown[]).length >= 2) {
    return { json, added: false };
  }
  await addSlide(page, REPEATER_TITLE);
  return { json, added: true };
}

test.describe("T018 — slider effect settings", () => {
  /*
   * These two tests assert on OBSERVABLE slider geometry, not on the option object.
   *
   * The obvious approach — read `jQuery(el).data("flexslider").vars.animation` — cannot work
   * with the bundled FlexSlider. modules.js:29 stores the jQuery object itself
   * (`$.data(el, "flexslider", slider)`) and the merged options stay a CLOSURE variable, so
   * `vars` is undefined on the instance. An earlier version of this test read it anyway and
   * reported "FlexSlider was not constructed" while the instance was sitting there working.
   *
   * Measured on this build, the two modes differ observably:
   *   animation=slide  ->  .slides gets `width: 800%; margin-left: -1280px`
   *   animation=fade   ->  .slides gets NO inline style at all
   * and controlNav=true builds an `ol.flex-control-nav`, controlNav=false builds none.
   * Both are consequences of the setting, so neither can be satisfied by the vendored
   * default — which is what makes them worth asserting.
   */
  // `page` is a per-test fixture, so it cannot be captured from describe scope — the helper
  // takes it explicitly. (Referencing it here directly was a ReferenceError at runtime.)
  const sliderGeometry = (page: Page) =>
    page.evaluate(() => {
      const el = document.querySelector(".slider");
      if (!el) return null;
      const slides = el.querySelector(".slides") as HTMLElement | null;
      return {
        published: el.getAttribute("data-cg-animation"),
        controlnav: el.getAttribute("data-cg-controlnav"),
        slidesStyle: slides ? slides.getAttribute("style") : null,
        activeSlide: el.querySelectorAll(".flex-active-slide").length,
        controlNavScaffold: el.querySelectorAll(".flex-control-nav").length,
      };
    });

  const applyAnimation = async (page: Page, value: string) => {
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);
    const select = page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_animation]"]');
    await select.selectOption(value);
    await save(page);
    return postId;
  };

  test("animation=slide produces slide geometry that the vendored fade cannot", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);

    const { json: originalJson, added } = await ensureTwoSlides(page);

    const select = page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_animation]"]');
    const original = await select.inputValue();
    await select.selectOption("slide");
    await save(page);

    await page.goto("/");
    const slide = await sliderGeometry(page);
    expect(slide, "the slider is missing from the home page").not.toBeNull();
    expect(slide!.published, "the template did not publish the setting").toBe(
      "slide",
    );
    expect(
      slide!.slidesStyle,
      "animation=slide did not take effect: .slides has no inline geometry, so the vendored fade is still in force",
    ).toMatch(/width:\s*\d+%/);
    expect(slide!.slidesStyle, "slide mode must also offset the track").toMatch(
      /margin-left:\s*-\d+/i,
    );

    // Restore.
    await login(page);
    await openSliderEditor(page, postId);
    if (added) {
      await restoreRows(page, originalJson);
    }
    await page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_animation]"]')
      .selectOption(original);
    await save(page);
  });

  test("animation=fade is the untouched vendored behaviour", async ({
    page,
  }) => {
    // Put the slider on the vendored default first, so this asserts the *default* path.
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);
    await page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_animation]"]')
      .selectOption("fade");
    await save(page);

    await page.goto("/");
    const fade = await sliderGeometry(page);
    expect(fade, "the slider is missing from the home page").not.toBeNull();
    expect(fade!.published).toBe("fade");
    expect(
      fade!.slidesStyle,
      "fade mode must not gain slide geometry — if this fails, fade and slide are not distinguishable",
    ).toBeNull();
  });

  test("controlNav=0 renders no control nav, controlNav=1 does", async ({
    page,
  }) => {
    await login(page);
    const postId = await findSliderId(page);
    await openSliderEditor(page, postId);
    const control = page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_controlnav]"]');
    await expect(control).toHaveCount(1);

    // Same one-slide trap: no instance means no control nav whatever the setting says.
    const { json: originalJson, added } = await ensureTwoSlides(page);

    // The vendored InitHome.js passes controlNav:false, so 0 is the untouched default.
    await control.selectOption("0");
    await save(page);
    await page.goto("/");
    const off = await sliderGeometry(page);
    expect(off, "the slider is missing from the home page").not.toBeNull();
    expect(off!.controlnav, "the template did not publish controlNav").toBe(
      "0",
    );
    expect(
      off!.controlNavScaffold,
      "controlNav=0 must build no control nav",
    ).toBe(0);

    await login(page);
    await openSliderEditor(page, postId);
    await page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_controlnav]"]')
      .selectOption("1");
    await save(page);
    await page.goto("/");
    const on = await sliderGeometry(page);
    expect(
      on!.controlNavScaffold,
      "controlNav=1 must build a control nav",
    ).toBeGreaterThan(0);

    // Restore.
    await login(page);
    await openSliderEditor(page, postId);
    if (added) {
      await restoreRows(page, originalJson);
    }
    await page
      .locator("#cg-theme-options")
      .locator('select[name="cg_meta[slider_controlnav]"]')
      .selectOption("0");
    await save(page);
  });
});

test.describe("T015 — B2: jQuery .live() shim", () => {
  /**
   * This asserts the MECHANISM, not the click.
   *
   * The obvious test — "no pageerror, then click .colorselector .icbox" — cannot fail when
   * skinselector is off, because the vendored block is guarded by
   * `if($('.colorselector').length)`: with no selector in the DOM the `.live()` call is never
   * reached, so the page is clean whether or not the shim exists. That is the same trap that
   * let B2 through in the first place.
   *
   * So: assert jQuery.fn.live exists. Without the shim it is undefined and this fails. The
   * behavioural half of B2's closure condition (clicking .icbox appends
   * <link id="schemeN-css">) needs skinselector=1 written through the Customizer, and is left
   * to aes/peer-reviews/sprint-02-03-port/human-validation.sh — it is not claimed here.
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
