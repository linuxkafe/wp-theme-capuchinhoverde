---
ticket: T013
title: Port the six content shortcodes
sprint: sprint-03
priority: medium
status: done
created: 2026-09-27
---

# T013 — Port the six content shortcodes

## Context

The source registers **30** shortcodes. This ticket ports the **6 content** ones. The other 24
are deliberately out of scope, per the decision recorded in `aes/kanban.md` on 2026-09-27:

- **12 layout columns** (`ale_one_third`, `ale_two_fifths`, …) duplicate `core/columns` and
  `core/column` in a block theme.
- **12 chrome** (`ale_alert`, `ale_button`, `ale_divider`, the tabs family) have no core
  equivalent yet.

Both groups are recorded as gaps in `docs/PARITY.md` §4, not silently dropped.

## Acceptance Criteria

- [x] `ale_service`, `ale_team`, `ale_testimonial`, `ale_partner`, `ale_toggle`, `ale_map` ported
- [x] `extract()` removed from all six; attributes read explicitly
- [x] Every attribute escaped — the source allowed stored XSS from any `edit_posts` user
- [x] `ale_toggle` actually toggles (the source's had no handler at all)
- [x] `ale_map` redesigned — the source version cannot run on any current WordPress
- [x] 17 E2E tests, including a "no page is a truncated fatal" sweep over all 7 routes

## `ale_map` is a redesign, and the source version cannot work

| Source behaviour | Why it is unusable |
|---|---|
| `wp_print_scripts('google-maps-api')` | Deprecated in WP 2.8, **removed from core**. A fatal. |
| Registered the Maps JS API over plain HTTP from `maps.google.com`, no API key, obsolete `sensor=false` | The host no longer serves the API |
| `ale_map_get_coordinates()` geocoded **server-side over plain HTTP on every render**, cached in a transient | A blocking external request inside a page render, leaking visitor page views to Google, and failing silently |
| Width attribute misspelled `widht` | The width never applied |

The replacement is the provider's own client-side embed: no server round trip, no API key for
OpenStreetMap, nothing to break. The shortcode name and `address` attribute are preserved so
existing content keeps working. Two address forms are accepted — a recognised embed URL, or
`lat,lng` coordinates — and anything else is **refused with a message** rather than forwarded
to a provider or dropped silently. The misspelled `widht` is still accepted.

## Four of the six have NO stylesheet — in the source either

`ale_team`, `ale_testimonial`, `ale_partner` and `ale_toggle` have markup that Cafeteria v1.7's
own stylesheet never targeted: they rendered unstyled in the original theme. Only
`.ale-service` exists in `assets/css/legacy/main.css:2021`.

Porting them faithfully would mean shipping four unstyled `<div>`s. What was added instead is
**minimal layout using the theme's existing tokens**, clearly marked in `style.css` as *not*
recovered source CSS and intended to be replaced. Flagged because it is a design decision that
is not the source's.

## Defects found in our own work

| # | Defect | Why it matters |
|---|--------|----------------|
| 1 | **`has_shortcode( get_post(), 'ale_toggle' )` is a `TypeError` in PHP 8** — it takes a string, not a `WP_Post`. It truncated `/shortcodes/` to **510 bytes while still returning HTTP 200** | The failure mode is invisible to a status assertion. Every seeded route now has a test asserting page size and content, not just status |
| 2 | `footer.php` echoed `contactmap` **raw** through a no-op `str_replace('&','&', …)`, plus unescaped address/phone/email/copyright and all nine social URLs, the last with bare `target="_blank"` | A Customizer value of `javascript:alert(1)` became a live link. Now one loop: all nine get `esc_url()` and `rel="noopener noreferrer"` by construction. The map goes through the same allowlist as the Contact page — one definition, used twice |
| 3 | The seed script had a stray duplicated `fi`, then after a first fix the `fi` landed **inside** the single-quoted content string | Both caught by `bash -n` before running |
| 4 | A test expected `marker=…%2C…`; `esc_url` does not percent-encode commas and a comma is legal in a query string | My assertion was wrong, not the code |

## Known risks

- **`footer.php` emits `<section class="footer">`, not a `<footer>` landmark.** The source did
  too. Asserted as-is in the test rather than assumed. Worth a landmark pass with the other
  accessibility work.
- The toggle's behaviour is progressive enhancement: without JS the closed panel keeps the
  server-rendered `hidden`, so content is unreachable. That is the accessible fallback for a
  collapsed control, but it does mean the shortcode is not usable JS-off. The source was worse
  (it never toggled at all).
- The minimal shortcode CSS is a starting point, not a design.

## Rollback

Delete `inc/shortcodes.php`, `assets/js/toggle.js` and its `require`; revert the `style.css`
block and the seed page. Nothing destructive.

## Notes

`ale_toggle` needed its own `assets/js/toggle.js` because the vendored jQuery layer is
off-limits for new code per `CLAUDE.md`. It is vanilla and enqueued only on pages that
actually contain the shortcode.
