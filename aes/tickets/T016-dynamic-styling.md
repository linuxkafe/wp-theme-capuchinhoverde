---
ticket: T016
title: Port the dynamic typography/colour system (css-option.php)
sprint: sprint-03
priority: medium
status: pending
created: 2026-09-27
---

# T016 — Port the dynamic typography and colour system

## Context

The source theme's design identity is not in its static CSS. It is generated at render time by
`partials/css-option.php` (246 LOC), which reads ~20 theme options and emits a `<style>` block:
heading sizes/faces/colours for h1–h6, body font, the three Google fonts, and the
`.colormain`/`.firstfont` mappings.

That partial was never ported. Consequences, all now fixed statically in `style.css` by T006:

| Class | Source behaviour | Port (as of T006) |
|-------|------------------|-------------------|
| `.colormain` | `ale_get_option('colormain')` | static `--cg-text-strong` (#5e3c3d) |
| `.firstfont` | `ale_get_option('headerfont')` | static `--cg-font-heading` (Georgia) |
| h1–h6 | per-heading size/face/colour options | static (only legacy CSS defaults) |

T006 established the **floor**. This ticket is the **ceiling**: real per-site control.

## Acceptance Criteria

- [ ] `partials/css-option.php` ported, or an equivalent `wp_add_inline_style()` in
      `inc/customizer.php` — choose one and delete the other
- [ ] Heading scale (h1–h6 size, face, colour) editable
- [ ] Body font and the three Google font families load via enqueued stylesheets, **not** raw
      `<link>` tags printed into the document (the source printed them directly)
- [ ] Every value passed through `wp_kses`/allowlist and escaped at output
- [ ] Defaults match the T006 tokens exactly, so a fresh install looks identical to now
- [ ] E2E: change an option in the Customizer, assert the computed style changes
- [ ] Options are `theme_mod`s, readable by `ale_get_option()` as written in T007

## Scope

**In scope:** `partials/css-option.php` or `inc/customizer.php`, `functions.php` font enqueue.
**Out of scope:** the ALETheme options panel and its TinyMCE builder (`docs/PARITY.md` §6).

## Alternatives rejected

- **Leave it static.** Cheapest, and arguably sufficient if the site is a single fixed design.
  Rejected because "portar todas as funcionalidades" includes the typography picker the source
  theme shipped, and because a hardcoded font is a hardcoded font.
- **Expose it through `theme.json` instead.** The right long-term home for colours and fonts in a
  block theme, but `theme.json` cannot express per-heading sizes. Keep the heading scale in PHP
  and the palettes in `theme.json` (T006 already moved the palette there).

## Dependencies

**T011** — the Customizer panel is where these controls live. Do not build controls twice.

## Rollback

Revert; the T006 static tokens remain as the floor.

## Known Risks

Per-heading `font-size` in a global stylesheet loses to block-level styles in the editor, so
what an author sees while editing may differ from the front end. Verify in the editor, not only
on the front end.
