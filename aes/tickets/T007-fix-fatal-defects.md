---
ticket: T007
title: Fix the seven fatal and broken defects blocking parity
sprint: sprint-02
priority: high
status: done
created: 2026-09-27
---

# T007 — Fix the fatal and broken defects

## Context

The port had never been executed. `make test` had always SKIPped (no WordPress existed), so the
defects in `docs/PARITY.md` §1 were found by reading code, not by running it. Standing up
Docker WordPress (T010) then turned them from suspicions into reproduced failures.

## Acceptance Criteria

- [x] F4/F5 — the two 404s (`ale_modules.js`, `ale_scripts.js`) are gone; asserted by E2E
- [x] F6 — the relative `./assets/css/editor.css` URL is gone; `add_editor_style()` moved from
      `admin_init` to `after_setup_theme`; editor CSS no longer loads on the front end
- [x] F3 — the `if (!function_exists('_e'))` redefinition of a core function is removed
- [x] F1 — `get_page_by_path()` (deprecated since WP 6.2) replaced with `get_posts()`
- [x] F2 — `ale_send_contact()` implemented: validation, honeypot, rate limit, nonce, and an
      honest failure message when `wp_mail()` fails. **Was HTTP 500 on every POST.**
- [x] F7 — `query_posts()` removed; the home gallery uses `WP_Query` and the correct CPT
- [x] All output in `header.php` and `page-home.php` escaped
- [x] The `aletheme` text domain replaced with `capuchinhoverde` throughout
- [x] E2E asserts each of the above

## Scope

**In scope:** `functions.php`, `inc/ale-compat.php`, `page-home.php`, `header.php`.
**Out of scope:** missing templates, missing shortcodes, editor UI (T011), design (T006).

## Rollback

Revert the four files. No data migration.

## Defects found *while doing this ticket* — none were on any backlog

| # | Defect | Evidence |
|---|--------|----------|
| D1 | **CPT rewrite rules were never flushed** — `/menu/`, `/gallery/`, `/events/` returned HTTP 200 with the *front page* content | curl + `get_option('rewrite_rules')` empty; fixed with a version-gated `after_switch_theme` flush |
| D2 | **`assets/css/legacy/images/` did not exist at all** — the vendored CSS makes 104 `url()` references to a directory that was never copied. Every logo, cake, triangle, shadow and icon 404'd | E2E caught 12 missing images per page load; 76 files copied from the source; now 0 unresolved |
| D3 | **JS global `ale` was never localized** — every page threw `ale is not defined`, so mobile detection, AJAX comments and conditional stylesheet loading silently did nothing | Playwright `pageerror` listener; fixed with `wp_localize_script` for all 6 consumed fields |
| D4 | **No `<h1>` anywhere** — the source had none on the home template; fails the WCAG 2.1 AA claim in `docs/REQUIREMENTS.md` | caught by the pre-existing E2E spec |
| D5 | **`header.php` echoed `custompagecss` raw into a `style` attribute** — stored CSS-injection vector reachable by any author | now escaped through a declaration allowlist |
| D6 | **`cg_block_query_posts()` was a no-op with a comment claiming it blocked `query_posts`** — a guard that guarded nothing | removed; the real guard is the E2E + lint gate |

## Known Risks

- The `custompagecss` allowlist strips `url()`, so a legitimate custom background-image rule
  supplied through that key will be dropped. The key has no writer UI yet (T011), so nothing
  regresses in practice. Revisit when T011 adds the field.
- Home sections are still gated on meta with no editor UI. `scripts/seed.sh` writes them
  directly, so the E2E specs pass while a real site administrator would see nothing. **T011 is
  the blocker for the theme being usable, not just testable.**

## Notes

F1 was mis-stated in the first version of `docs/PARITY.md` as a fatal. It was verified to still
exist in WP 6.6 and corrected — a reminder that "deprecated" and "removed" are different claims
and only one of them is checkable in ten seconds.
