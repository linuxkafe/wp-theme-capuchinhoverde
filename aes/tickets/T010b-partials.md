---
ticket: T010b
title: Port the ten missing partials
sprint: sprint-03
priority: high
status: done
created: 2026-09-27
---

# T010b — Port the ten missing partials

## Context

Ten of the source theme's eleven `partials/` were never ported. `ale_part()` includes from
`get_template_directory() . '/partials/'`, a directory that did not exist, so **every
`ale_part()` call was a silent no-op**. `header.php` calls `ale_part('innerheaders')` on every
non-home page, so no inner page had the Cafeteria header band at all. `footer.php` calls
`ale_part('colorselector')`.

The six colour schemes are **light accent variants** of the Cafeteria identity
(`#f7f3f3`/`#f8f2f2`/`#ffffff` backgrounds, only the accent hue differs). This is consistent
with T006, which removed the *dark AES template* palette — a different thing. `scheme1.css` is
empty in the source, which is why the base stylesheet already carries the default palette.

## Acceptance Criteria

- [x] All 10 partials ported: `archives`, `colorselector`, `innerheaders`, `lang`, `notfound`,
      `pagehead`, `pagefooter`, `postcontent`, `postfooter`, `posthead`
- [x] `single.php` composed from `posthead` + `postcontent` + `postfooter`, as the source does
- [x] No partial is dead code — each is reachable from a template or the footer/header
- [x] The 6 colour schemes are served and the URL the vendored JS builds resolves
- [x] E2E asserts the partials render and respond to the editor UI (12 tests)

## Source defects fixed rather than copied

| # | Defect in the source | Evidence |
|---|----------------------|----------|
| 1 | `partials/innerheaders.php` **else branch is a PHP syntax error** — emits `style="…margin-bottom: 60px;');"` with a stray `');` | Would fatal the moment that branch executed |
| 2 | `innerheaders.php` tests `$post->post_type` against `'gallery'`, `'events'`, `'menu'` — this theme registers `cg_gallery`, `cg_event`, `cg_menu`, so **three of five branches could never match** | Mapped via a lookup table so they cannot drift again |
| 3 | `posthead.php` used image sizes `post-slider` / `events-slider`; this theme registers `cg-post-slider` / `cg-event-slider` | Silent fallback to full-size images |
| 4 | `posthead.php` filtered attachments on `_ale_hide_from_gallery`, written by the out-of-scope ALETheme metaboxes | Now `_cg_hide_from_gallery`, and attachments with no value are included, so the filter is additive rather than excluding everything |
| 5 | `archives.php` called `ale_archives()` (a 40-line framework function) | Replaced with core `wp_get_archives()`; the framework's version adds nothing over WP 2.1 |
| 6 | `pagehead.php`/`pagefooter.php`/`postfooter.php` split open/close tags across files | Kept as a pair, documented, because the ported templates include them together |

## Defects this ticket found in **our own** work

| # | Defect | Why it matters |
|---|--------|----------------|
| 1 | **82 asset files were serving as HTTP 403.** Files copied from the source kept its `0700` mode, so Apache (uid 33) could not read them — including every design image ported in T007, which had been broken since they were copied and no test noticed | A production-breaking defect that survived a whole sprint because the asset test only asserted on **404** |
| 2 | `make lint` used `git ls-files '*.php'`, which only sees **tracked** files — every newly written PHP file was unlinted until committed | It hid two real syntax errors in `partials/posthead.php` on the day they were written. Now uses `find` |
| 3 | The asset test could not see 403s at all | Now asserts **any** status >= 400, and walks four pages so CSS-referenced backgrounds are actually requested. Proven: re-applying `0700` to `cake.png` makes the test fail naming the exact URL |

## Deferred, with reasons

- **The sidebar.** The source's `single.php` wraps the story in `.col-8` beside a `.col-4`
  sidebar. This theme registers no sidebars and has no `sidebar.php`, so `get_sidebar()` would
  print an empty column. The story renders full width and the deviation is documented in the
  template header. Adding a sidebar needs `register_sidebar()` plus a widget surface — its
  own ticket, not a silent removal.
- **`css-option.php` (246 LOC)** stays out; it is T016 and depends on T011's Customizer.
- **The `/css/colors/` path wart.** The vendored `scripts.js` hardcodes that suffix and
  `CLAUDE.md` forbids editing vendored assets, so the files live at
  `assets/css/legacy/css/colors/` to satisfy the path the script already builds. Documented in
  a README beside them. The proper fix is to pass the directory through the localized `ale`
  object — one line in a vendored file, which needs an owner decision on the "never edit
  legacy assets" rule.

## Scope

**In scope:** `partials/`, `single.php`, 6 scheme stylesheets, 5 new Customizer options
(`contactheader`, `galleryheader`, `eventsheader`, `menuheader`, `storyheader`), the lint and
asset gates.
**Out of scope:** sidebar/widgets (above), `css-option.php` (T016), `archive.php` and the
taxonomy templates (sprint 04), the 6 content shortcodes (T013).

## Rollback

Delete `partials/*` except `notfound.php`, revert `single.php`. `ale_part()` fails soft, so a
revert degrades to the previous (missing-partial) behaviour rather than erroring.

## Known Risks

- `pagehead.php` opens an `<article>` closed by `pagefooter.php`. Including one without the
  other yields unbalanced markup. Nothing in the theme calls them yet; if a future template
  uses one it must use both.
- The scheme files are selected client-side and stored in a cookie, so a first-time visitor
  gets the default palette and only subsequent visits see their choice. That is the source's
  behaviour; making it server-side is a redesign, not a port.
