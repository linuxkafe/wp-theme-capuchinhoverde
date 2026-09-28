# CLAUDE.md — Capuchinho Verde

Operational contract for agents and contributors working in this repository.
Read this before Phase 1 of any non-trivial task.

## What this is

A WordPress **block theme** for food delivery / restaurant sites: `theme.json` v2, native
Gutenberg support, PHP 8.2+, WP 6.5+ target. Origin is a port of the Cafeteria v1.7 / ALETheme
design; a compatibility layer lives in `inc/ale-compat.php` and legacy assets in
`assets/{css,js}/legacy/`.

## Non-goals

_(to be filled by the theme owner — see aes/tickets/T002)_

- Not a page builder. Blocks and `theme.json` are the extension mechanism.
- Not a plugin. Anything reusable across themes belongs elsewhere.
- Not a fork of the ALETheme framework. `aletheme/options/*`, the TinyMCE shortcode builder and
  the import/export machinery are a plugin's job and are deliberately **not** ported
  (see `docs/PARITY.md` §6).

## jQuery — corrected 2026-09-27

**The front end depends on jQuery, and this is not changing in the port's current scope.**
`functions.php` enqueues `jquery`, `jquery.isotope.min.js`, `jquery.cookie.js` and `scrollable.js`,
and `assets/js/legacy/*.js` are all jQuery plugins (FlexSlider, Isotope, scrollable).

An earlier version of this file claimed core was jQuery-free. That was false: jQuery was loaded
unconditionally on every page load while the document denied it. The rule now:

- **Do not add new jQuery usage.** New code is vanilla JS (`assets/js/nav.js` is the model).
- Do not treat the legacy jQuery layer as removable without a replacement for FlexSlider
  (the home slider) and Isotope (the gallery filter). Both are load-bearing.

## Essential commands

```bash
make setup     # npm ci
make wp-seed   # start WordPress in Docker, install, and seed content
make check     # lint + format + doc-consistency + e2e  ← the gate
make doctor    # what is real vs skipped in this environment
make wake      # current sprint / ticket
make metrics   # size and surface area
make wp-down   # stop WordPress (keeps data)
```

`make test` needs a WordPress answering at `$WP_URL` (default `http://localhost:8083`).
It is provided by `docker compose` (`make wp-up`). Without one it reports **SKIP**, never PASS.
Do not describe a skip as a passing test run.

Since T015/B1, `make check` has three terminal states, and they mean different things:

| Output | Meaning | Report it as |
|---|---|---|
| `check: OK` | every gate ran, tests executed | a pass |
| `check: DEGRADED` | non-test gates passed, **0 E2E tests ran** | a skip — *not* a pass |
| `check: FAIL` | a gate exited non-zero, or WordPress answered but 0 tests ran | a failure |

A WordPress that answers but runs **0** tests is always a `FAIL`, never a `SKIP`. Use
`REQUIRE_TESTS=1` in CI to turn a `DEGRADED` into a hard failure.


## Critical files

| Path | Why it matters |
|------|----------------|
| `style.css` | Theme header is the **source of truth** for PHP/WP minimums. The doc gate diffs `docs/REQUIREMENTS.md` against it. |
| `functions.php` | `cg_setup()` — all `add_theme_support`, menus, enqueues. |
| `inc/ale-compat.php` | Cafeteria parity shim. Highest defect density in the repo. |
| `inc/post-types.php` | Registers the `cg_menu`, `cg_gallery`, `cg_event` CPTs. |
| `theme.json` | Design tokens, layout widths, editor palette. v2. |
| `assets/css/legacy/` | Vendored Cafeteria CSS. Do not hand-edit; it is a port source. |
| `assets/js/legacy/` | Vendored legacy JS (Isotope, modernizr, scrollable). Same rule. |

## Never do

- Do not hand-edit `assets/{css,js}/legacy/` — those files are ported from upstream Cafeteria
  and edits are lost on the next parity sync. **Known carve-out:** where a vendored file
  hardcodes a path that the port relocated, work around it and document the reason next to the
  affected file (see `assets/css/legacy/css/colors/README.md`) rather than editing the vendored
  source.
- **Same rule, second sanctioned carve-out (owner decision, 2026-09-28, T015/B2).** The
  vendored `assets/js/legacy/scripts.js` calls jQuery `.live()`, removed in jQuery 3.0, which
  WordPress no longer serves. Rather than edit the vendored file, the API is restored from
  `assets/js/legacy-compat.js` — a **core** file, not a vendored one. That is the pattern for
  any vendored code that depends on a removed browser or jQuery API: shim it from
  `assets/js/`, never from `assets/js/legacy/`.
- Do not write a test that cannot fail for the reason it claims. The round-1 lesson: three
  tests (B2's pageerror, `smoke.spec.ts` `.colormain`, and the seeded slides) all passed
  against a completely broken slider because each was satisfied by the empty-state fallback.
  When a test asserts on rendered output, check what else on the page could satisfy it.
- Do not copy files out of the source theme without fixing their permissions. Source assets are
  `0700`; copied as-is they serve as HTTP 403 and `make check` will (now) catch it.
- Do not add jQuery to core. `inc/ale-compat.php` is the only sanctioned legacy consumer.
- Do not change the `style.css` version header to make a doc gate pass. Fix the doc, or open a
  ticket that intentionally changes support levels.
- Do not add `node_modules/`, `test-results/`, or `vendor/` to git.
- Do not claim E2E passed when the run was skipped for lack of a WordPress instance.
- Do not "improve" `docs/` opportunistically in unrelated commits — docs changes ride with the
  ticket that changes behaviour.

## Evidence required before declaring done

- `make check` output pasted, including explicit SKIP lines.
- Acceptance criteria in the ticket checked with a command that produced them.
- A diffstory in the Build output: what changed, why, what was deliberately untouched, residual risk.

## When the docs and the code disagree

The **code wins**. `style.css`, `theme.json`, and committed templates are reality; `docs/` is a
claim. If `make check` reports a `consistency: FAIL`, the fix is to correct the doc — or to open a
ticket that deliberately changes the declared support level. Never silence the gate.
