---
project: Capuchinho Verde
created: 2026-09-27
current_sprint: sprint-04
current_ticket: T015
tier: AES-standard
backend: local
---

# Kanban — Capuchinho Verde

WordPress block theme (PHP 8.2+, WP 6.5+ target, `theme.json` v2) porting the full functionality
and design of Cafeteria v1.7. Single contributor on a GitHub org remote → tier **AES-standard**
(stricter than the solo heuristic), ticket backend **local**.

**The governing document is `docs/PARITY.md`.** Every port ticket states which parity rows it
closes.

## Objective

Port all functionality and design from `../wp-abandoned-themes/cafeteria` (80 PHP files, 13,963
PHP LOC) to this theme, modernised for WP 6.5+ / PHP 8.2+.

Starting position, measured 2026-09-27: **5% of the source's PHP LOC, 0 of 30 shortcodes, 0 of 11
partials, 0 of 12 image sizes.** 13 features were claimed in `style.css` and `CLAUDE.md`; none
worked, and two errored. The port had never been executed — `make test` had always SKIPped.

## Current state

| | Sprint 01 | Sprint 02 |
|---|---|---|
| Outcome | gates + truthful docs | theme renders, features verifiable |
| `make check` | green, E2E skipped | green, **E2E running (16 passing)** |
| Parity blockers | 2 | 0 fatal |

## Sprint 01 — Establish gates and truthful docs (done)

| ID | Title | Priority | Status |
|----|-------|----------|--------|
| T001 | Bootstrap AES project structure | high | done |
| T002 | Reconcile docs with code (PHP 8.2, WP floor direction) | high | done |
| T003 | Harden the doc-consistency gate | medium | in-progress |
| T004 | Decide on static analysis (PHPStan) | low | blocked |
| T005 | Raise `Requires at least` to 6.5 in `style.css` | high | pending |

## Sprint 02 — Make parity observable (done)

| ID | Title | Priority | Status |
|----|-------|----------|--------|
| T007 | Fix the fatal and broken defects (F1–F7 + 6 more found live) | high | done |
| T008 | Foundations: image sizes, `cg_slider`, taxonomies, rewrite flush | high | done (folded into T007) |
| T010 | Real WordPress in Docker + seed script + E2E suite | high | done |
| T006 | Resolve the dark/light palette conflict | high | done (screenshot parity deferred) |

## Sprint 03 — Content model and the editor surface (in progress)

| ID | Title | Priority | Status |
|----|-------|----------|--------|
| T011 | Add an editor surface for the `_cg_` meta the templates read | high | **done** |
| T010b | `partials/` — the 10 missing partials | high | **done** |
| T012 | About + Contact templates | high | **done** |
| T013 | 6 content shortcodes (`ale_service`, `ale_team`, `ale_testimonial`, `ale_partner`, `ale_toggle`, `ale_map`) | medium | **done** |
| T016 | Port the dynamic typography/colour system (`css-option.php`) | medium | pending |

## Sprint 04 — Feature parity (planned)

Taxonomy templates, menu/price rendering, events, Isotope gallery filter, widgets/sidebar,
WooCommerce templates (unverified need), tax/category templates.

## Decisions taken (2026-09-27)

1. **Content model:** native `core/columns` for the 12 layout shortcodes; keep the 6 content
   shortcodes as shortcodes. Full block conversion is a redesign, not a port.
2. **Palette:** the Cafeteria identity is canonical; the dark AES template palette is deleted.
3. **Verification:** real WordPress in Docker. `make test` must run, not skip.
4. **ALETheme admin framework is out of scope** (`docs/PARITY.md` §6).

## Peer review round 1 — verdict: REJECT (2026-09-27)

The sprint-02/03 port was submitted after `make check` went green with 68 passing tests.
Review found **4 BLOCKER · 9 MAJOR · 3 MINOR**, all verified. Artifacts in
`aes/peer-reviews/sprint-02-03-port/`; ticket `T014`.

**The port is not complete and its "68 passing tests" overstates what was verified.**
`scripts/seed.sh` writes fixtures through wp-cli, so the E2E suite seeded around the UI paths
that were broken.

| # | BLOCKER | Gate status |
|---|---------|-------------|
| B1 | `make check` prints `check: OK` with **0 of 68 tests run** | gate lies when the environment is absent |
| B2 | Colour-scheme selector is dead code (jQuery `.live()` removed in 3.7.1) | test asserted presence, never behaviour |
| B3 | Editing any Slider is a PHP fatal (`esc_textarea()` on an array) | no test opened a `cg_slider` screen |
| B4 | Slides UI writes a JSON string; the reader requires an array | seed injected an array, bypassing the UI |

Blocking the sprint's completion claim. **Do not report sprint-02/03 as delivered until T015
closes the BLOCKERs.** The human validation script
(`aes/peer-reviews/sprint-02-03-port/human-validation.sh`) has NOT been executed — the author
cannot execute it, so the verdict cannot rise above REJECT until an independent human runs it.

### T015 (2026-09-28) — BLOCKERs closed in the repo, two caveats

| # | BLOCKER | State |
|---|---------|-------|
| B1 | gate printed `check: OK` after a SKIP | **fixed and reproduced** — `check` now distinguishes OK / DEGRADED / FAIL; a reachable WordPress that runs 0 tests is a FAIL |
| B3 | `esc_textarea()` on an array → editor fatal | **fixed** — render guard; mechanism proven (`htmlspecialchars(): Argument #1 must be of type string, array given`) |
| B4 | writer produced a string, reader wanted an array | **fixed** — writer stores an array, reader also decodes legacy JSON strings |
| B2 | colour selector bound via removed jQuery `.live()` | **fixed by shim** (owner decision) — `assets/js/legacy-compat.js`, no vendored byte edited. Behavioural half (clicking `.icbox` appends `<link id="schemeN-css">`) still needs the human validation script |

**Two caveats. Neither lets the verdict rise above REJECT.**

1. **No E2E test was executed.** This environment has no running WordPress, so
   `make check` reports `DEGRADED`, not `OK`. The PHP and JS logic was verified with
   isolated harnesses (10 PHP checks, 7 shim checks) and the gate was exercised in all
   three of its states — but `tests/e2e/slider.spec.ts` has never been run.
2. **Round 1's B4 evidence was wrong** and is corrected in T015: `seed.sh:90` no longer
   injects a PHP array, it writes a JSON string. The conclusion held; the proof did not.

Also found: the deployed theme is **not this repo** (T017) — a production capture requests
legacy filenames and an older `page-home.php` that the tree already fixed. Local verification
therefore does not describe production in either direction.

## Blockers

- T004 needs an owner decision on whether Composer is acceptable in the deploy pipeline.
- T005 needs confirmation that no live site runs below WP 6.5.
- T015: E2E never executed; needs a live WordPress.
- T017: production runs a drifted copy; local results do not describe it.


## Resolved

- ~~T011 gated theme usability~~ — every metered section now has a writer. 31 meta keys in
  `inc/meta.php`, 22 site-wide options in `inc/customizer.php`, both driven by a schema so
  registration, UI and sanitisation cannot drift apart. `make check` now fails if a template
  reads a key nothing can write. The theme is **usable**, not merely testable.

## Notes

- `make check` SKIPs format (no php-cs-fixer) — reported as a skip, never as a pass.
- E2E asserts the home page, the three CPT archives, asset resolution, the contact form
  (nonce + validation), the design tokens, and the `h1` count on four routes.
- `docs/PARITY.md` was wrong once: it called `get_page_by_path()` fatal. It was verified present
  in WP 6.6 and corrected. Deprecated ≠ removed.
