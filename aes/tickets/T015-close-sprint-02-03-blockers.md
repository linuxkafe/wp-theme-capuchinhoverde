---
ticket: T015
title: Close the four BLOCKERs from peer review round 1
sprint: sprint-04
priority: high
status: in-progress
created: 2026-09-28
---

# T015 — Close the sprint-02/03 BLOCKERs

`aes/kanban.md:78-98` records peer review round 1 as **REJECT** with 4 BLOCKERs. This
ticket is the one the kanban refers to and which was never written. Until it closes, sprint
02/03 must not be reported as delivered.

## Scope

B1 (`Makefile`), B2 (`assets/js/legacy/scripts.js` — **owner decision required, see
Blocker on B2**), B3 (`inc/meta.php`), B4 (`inc/meta.php` + `inc/ale-compat.php`), plus the
two corrections in "Review errors" below. The 9 MAJORs are **not** in scope; they are a
separate ticket.

---

## Blocker on B2 — a contract conflict that needs the owner

B2 can only be fixed by touching `assets/js/legacy/scripts.js`, which `CLAUDE.md` puts on
the **Never do** list. The sanctioned carve-out is narrow and does not cover this case:

> **Known carve-out:** where a vendored file hardcodes a path that the port relocated,
> work around it and document the reason next to the affected file […] Amending the rule
> itself is an owner decision.

`.live()` is not a hardcoded path. It is an API removed in jQuery 3.0. So:

- **(A) edit `scripts.js`**, `.live(` → `.on(` — fixes it, violates the never-do, lost on
  the next parity sync.
- **(B) shim `jQuery.fn.live = jQuery.fn.on`** in a non-legacy file — touches no vendored
  file, but re-adds a removed API globally and is itself "new jQuery usage".
- **(C) reimplement the colour selector in vanilla `assets/js/nav.js`** — matches the
  documented model, but **cannot work alone**: the vendored `if($('.colorselector').length)`
  block still runs and still throws the pageerror, so the partial would also have to stop
  being enqueued. That is a change to the partial, not to the vendored file.

(B) is the smallest change that satisfies both rules. (C) is the correct end state but is a
larger redesign than a BLOCKER fix should be. **Owner must choose.**

---

## B1 — the gate lies when zero tests run

**Reproduced 2026-09-28:**

```
$ make check WP_URL=http://localhost:9999
test: SKIP (no WordPress answering at http://localhost:9999)
check: OK — lint, format, doc-consistency, key-coverage, test
EXIT=0
```

`CLAUDE.md:63` says "Do not claim E2E passed when the run was skipped". The shipped tool
does exactly that. Contradicts `Makefile:52-64`.

**Design note.** Failing hard on every SKIP would break the legitimate PHP-only contributor
who has no WordPress — that is a real workflow, and `make test` SKIPping is a documented
feature. So the fix distinguishes the two cases:

- WordPress unreachable → `SKIP`, and `check:` line must not read `OK`; print `DEGRADED`.
- WordPress reachable but **0 tests executed** → this is always a failure, not a skip.

A `REQUIRE_TESTS=1` escape hatch is available for a run that genuinely must not need
WordPress. Closure condition: `make check WP_URL=http://localhost:9999` prints
`DEGRADED` and does not print `check: OK`, or exits non-zero.

## B2 — colour scheme is dead code

`assets/js/legacy/scripts.js:24,28,33` call `$(...).live('click', …)`. WordPress serves
`jquery.min.js?ver=3.7.1`; `.live()` was removed in jQuery 3.0. Every click on
`.colorselector .openbut` and `.icbox` binds nothing, so `skinselector=1` cannot function.
**Not re-verified in this session** — needs a browser with a live WordPress. Awaiting the
decision above.

## B3 — opening any Slider in the editor is a PHP fatal

`inc/meta.php:463` renders the `json` group through `esc_textarea($value)` with no type
guard. If `_cg_slides` holds an array, `esc_textarea()` is `htmlspecialchars(array)`.

**Mechanism proved 2026-09-28:**
```
TypeError: htmlspecialchars(): Argument #1 ($string) must be of type string, array given
```

This is **independent of B4**: aligning the writer does not fix it, because the fatal fires
on *render*, and an array is exactly what a correctly-stored value would be. Needs its own
`is_array($value) ? wp_json_encode($value, JSON_PRETTY_PRINT) : $value` guard.

## B4 — the Slides field saves a value the reader always discards

Writer `inc/meta.php:328` returns `wp_json_encode($clean)` — a **string**.
Reader `inc/ale-compat.php:96` does `if (!is_array($slides)) { $slides = []; }`.

The only UI path produces a string; the front end throws it away. The sole user-facing way
to configure the home slider cannot work.

---

## Review errors — two claims in round 1 are now stale

Recorded so nobody re-litigates them:

1. **B4's evidence is out of date.** The review says `scripts/seed.sh:90 injects a PHP
   array through wp-cli, bypassing the metabox entirely`. The seed no longer does that: it
   writes a JSON string with `--format=json` (`scripts/seed.sh:89-94`). The conclusion
   holds — worse, actually — but the proof does not. Per the project contract, code wins
   and the review is corrected here.

2. **The live site is not a witness for this repo.** Comparing a production capture against
   the working tree produced two independent proofs of a stale deployment:
   - the capture requests `assets/js/legacy/ale_modules.js` and `ale_scripts.js`; the disk
     has `modules.js` / `scripts.js`, and `functions.php:58-59` enqueues the correct names
     with a comment recording the 404 (`functions.php:51-52`).
   - the capture's `<ul class="slides">` is **empty**; `page-home.php:59-88` always emits
     at least one `<li>` (the slides, or the `cg-slider-empty` fallback).

   Any conclusion drawn from that capture describes the deployed copy, not this tree.

## A test that cannot fail for the reason it claims

`tests/e2e/smoke.spec.ts:141` asserts `.colormain` computes to the clay colour. It is
satisfied by the **empty-state fallback** (`page-home.php` renders
`firstfont caption colormain` inside `cg-slider-empty`). So the suite stays green while the
slider is entirely broken — B4 is invisible to it. This is a third instance of the pattern
behind M7, and it is why "68 passing tests" overstated coverage.

Closing B4 must add a test that fails when the seeded slides are absent — otherwise B4
silently reopens.

---

## Acceptance criteria

Proven in this session unless marked. Nothing here was verified by an E2E run — see the
"Residual risk" note at the foot of this ticket.

- [x] `make check WP_URL=http://localhost:9999` does not print `check: OK` — prints
      `check: DEGRADED`, observed
- [x] `REQUIRE_TESTS=1` with no WordPress → `check: FAIL`, exit non-zero, observed
- [x] WordPress reachable + 0 tests → FAIL branch — the branch was exercised against a real
      Playwright "Error: No tests found" run and fires; not wired to a live WordPress
- [x] `make lint` green (33 files)
- [x] B3 render no longer fatals on a stored array — proven by harness
- [x] B4 round trip: UI string → stored array → rendered slide — proven by harness
- [x] B4 legacy JSON strings still decode — proven by harness
- [x] `.live()` shim installed, ordered before the vendored file, no-op when present — proven
      by 7-assertion harness
- [x] No vendored file edited (`git diff --name-only -- assets/js/legacy/` is empty)
- [x] `CLAUDE.md` records the B2 decision and the new never-do rule
- [x] `aes/kanban.md` no longer points at a ticket that does not exist
- [ ] **`npx playwright test` on a live WordPress — NOT DONE.** `slider.spec.ts` is written
      and lint/format clean, but has never executed. This is the one criterion that actually
      closes B3 and B4 end to end.
- [ ] **B2's behavioural half — NOT DONE.** Requires `skinselector=1` written through the
      Customizer; left to the human validation script.
- [ ] 9 MAJORs from round 1 — out of scope, separate ticket.

## Residual risk

- **No E2E test has ever been run against this change.** This environment has no WordPress.
  The PHP and JS logic was verified in isolation, and the gate itself was exercised in all
  three states, but `make check` reports `DEGRADED` and that is a skip, not a pass.
- `format: SKIP (prettier not installed)` — prettier is not a devDependency, so the format
  gate has never checked the new JS/TS. Pre-existing, tracked in T004. I ran prettier via
  `npx` ad hoc to keep the new files formatted; adding it as a dep is a T004 decision.
- The pre-existing `Undefined function 'add_action' / '__'` LSP diagnostics across every PHP
  file are a missing-WordPress-stubs LSP configuration, not defects. `php -l` is the gate.

## Rollback

Each of B1/B3/B4 is a self-contained diff. B2 is not reversible across a parity sync — which
is the argument for the shim over editing the vendored file.

