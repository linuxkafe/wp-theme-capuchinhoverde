---
ticket: T017
title: The deployed theme does not match this repository
sprint: sprint-04
priority: high
status: pending
created: 2026-09-28
---

# T017 — Deployed theme has drifted from the repo

Opened after a production HTML capture was compared against the working tree, on the
assumption that the capture described this code. It does not.

## Evidence

The capture is served from
`/wp-content/themes/wp-theme-capuchinhoverde-main/` — a different directory name from this
repo (`capuchinhoverde`), so the capture is a **different working copy**. Two independent
proofs that it is an OLDER one, not a fork:

1. **Wrong legacy JS filenames.** The capture requests
   `assets/js/legacy/ale_modules.js` and `assets/js/legacy/scripts.js`. The disk has
   `modules.js` and `scripts.js`, and `functions.php:58-59` enqueues the correct names.
   The comment at `functions.php:51-52` already records that the `ale_*` names "404'd on
   every page load" and were fixed. So the deployed copy predates that fix. The capture
   also shows a `wp-conte` typo (missing `n`) in the isotope path — another artifact of the
   same older revision.

2. **Older `page-home.php`.** The capture's `<ul class="slides">` is **empty**.
   `page-home.php:59-88` always emits at least one `<li>` — either the configured slides or
   the `cg-slider-empty` fallback. An empty `<ul>` is not reachable from the current file.

`footer.php` and `header.php` in the capture *are* consistent with the current tree, so this
is a partial or stale copy rather than a clean fork. That is the worrying part: a mixed copy
means **local verification does not describe production**, in either direction.

## Consequence

Any acceptance claim verified locally is not evidence about the live site until this is
closed. This is the same class of error the peer review hit when it trusted
`scripts/seed.sh` as evidence about the UI.

## Acceptance criteria

- [ ] Determine how the theme gets to the production host (git deploy, rsync, manual zip)
- [ ] Establish which commit/revision production is actually running
- [ ] Either deploy the current tree, or document the delta and re-verify the BLOCKERs
      against production
- [ ] Add the deployment step to `make` so drift is detectable rather than discovered by
      reading HTML

## Out of scope

Diagnosing the host. No access to the production server was available in this session, and
this ticket records what was observed, not a diagnosis.

## Notes

Found while working T015. The BLOCKERs there are verified against **this repository**; the
production capture was explicitly rejected as evidence about the code.
