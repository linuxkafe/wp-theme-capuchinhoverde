---
ticket: T012
title: Port the About and Contact templates
sprint: sprint-03
priority: high
status: done
created: 2026-09-27
---

# T012 — Port the About and Contact templates

## Context

`template-about.php` (164 lines) and `template-contact.php` (47 lines) were absent while
`style.css` and `CLAUDE.md` advertised both. `functions.php` was already conditionally
enqueueing `InitAbout.js` for `template-about.php` — a script waiting for a template that did
not exist. Both templates need **35 new meta keys**, none of which had a writer.

## Acceptance Criteria

- [x] `template-about.php` ported: team (4), prices (4 × label/heading/photo/desc/price), page content
- [x] `template-contact.php` ported: top image, content, contact details, map, form
- [x] All 35 new keys declared in the schema; `make check` enforces it
- [x] The contact form extracted to `partials/contactform.php`, shared with the home page
- [x] Exactly one `h1` on both templates (asserted)
- [x] Map embed accepts only recognised map hosts; anything else is refused, not embedded
- [x] 11 E2E tests

## Source defects fixed rather than copied

| # | Defect | Evidence |
|---|--------|----------|
| 1 | `template-about.php` hand-copied the **same 20-line block 8 times** (4 team + 4 price) | Replaced with two loops. Output markup is identical |
| 2 | The source's `menutitic1` is a **typo** for "menu title", alongside a correct `menutit1` | Ported as `menutitle`. Justified because the `_cg_` meta prefix already means source content is not read (docs/PARITY.md §8), so the typo buys no import compatibility. Documented in the template and in `inc/meta.php` |
| 3 | `template-contact.php` **rendered no contact details at all**, despite `contactemail` / `contactphone` / `contactaddress` / `contactmap` existing in the source's own config for that page | All four are rendered now |
| 4 | The source posted with **no nonce, no validation and no rate limit** | Nonce verified in the template, validation + honeypot + rate limit in `ale_send_contact()` (T007) |
| 5 | Every meta value was echoed raw into HTML and into `src` / `alt` attributes | Escaped throughout |

## WCAG defects found by the E2E suite

Both templates had **no `h1`** — the source used `h2` for the team, price and page headings.
Same defect class T007 caught on the home page; `docs/REQUIREMENTS.md` claims WCAG 2.1 AA.
Fixed with a visually-hidden `h1` carrying the page title, since the title is already shown in
a visible `h2` further down. Asserted on both templates.

## Design decisions

- **Sections print only when they have content.** The source printed all eight blocks
  unconditionally, so a half-filled page rendered empty columns. Verified: `/sobre/` prints
  team + prices, and a page with neither prints neither.
- **The map is an allowlisted iframe, not raw output.** `contactmap` is arbitrary text in the
  source's config. Echoing it would reinterpret it as markup. It is parsed, host-checked
  against Google Maps / OpenStreetMap, and rendered as a sandboxed iframe — or refused with a
  message. Seeded with a non-map URL specifically to exercise the refusal path.

## Defects found in our own work

| # | Defect | Why it matters |
|---|--------|----------------|
| 1 | `scripts/seed.sh` passed meta **key and value as one argument**, so `wp post meta update` got two args and every write failed behind `>/dev/null`. About and Contact rendered empty and it looked like a template bug | The gate now `die`s on an odd argument count instead of failing silently |
| 2 | The key-coverage gate read the schema's generation bound from a literal `for` loop, but the bound had moved into `cg_meta_repeatable_group()`'s `$count = 4` default — so it reported a mismatch against a **correct** schema | A gate that cries wolf on correct code teaches people to ignore it. Now accepts either form, and **proved** it still catches drift in both directions (template wider, schema wider) |
| 3 | The bound scan was repo-wide, so `partials/colorselector.php`'s `for ($cg_scheme = 1; <= 6)` — which counts colour schemes — was read as a meta bound of 6 | Now scoped to the files that build dynamic meta keys |
| 4 | `meta.spec.ts` counted rows across **every** metabox table, so adding the two About groups made a T011 test fail | Repeatable tables now carry a `cg-meta-table--<group>` class; the assertion is scoped |

## Scope

**In scope:** the 2 templates, `partials/contactform.php`, 35 schema keys, 5 new Customizer
options, `single.php`-independent wiring, the key-coverage gate, the seed script.
**Out of scope:** `ale_map` and the other 5 content shortcodes (T013), `css-option.php` (T016),
taxonomy/archive templates (sprint 04), the sidebar.

## Rollback

Delete the two templates and `partials/contactform.php`, revert `page-home.php`'s form block and
the schema additions. Nothing is destructive.

## Known Risks

- Both templates need a page assigned in the admin (Page Attributes → Template). The seed does
  this; a hand-built site must do it too or the pages render with `page.php`.
- Team and price items are capped at 4 because the markup unrolls 4 columns. Making it
  configurable means changing the schema and both loops together — the gate compares their
  bounds, so a mismatch fails rather than silently truncating.
