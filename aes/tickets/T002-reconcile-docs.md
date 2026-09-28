---
ticket: T002
title: Reconcile docs with code
sprint: sprint-01
priority: high
status: pending
created: 2026-09-27
---

# T002 — Reconcile docs with code

## Context

`docs/REQUIREMENTS.md` states "WordPress 6.0+ e PHP 7.4+"; `style.css` declares
`Requires PHP: 8.2` and `Requires at least: 6.0` / `Tested up to: 6.6`, while the VISION and
ROADMAP describe a block-first theme targeting WP 6.5+. `docs/ROADMAP.md` also lists as backlog
work that is already committed: CPT templates (`archive-cg_menu.php`, `single-cg_event.php`,
`single-cg_gallery.php`), custom block pattern (`patterns/menu-card.php`), and Playwright E2E
(`playwright.config.ts`, `tests/e2e/smoke.spec.ts`).

Rule 1: *if it's not documented, it doesn't exist* — but the inverse also holds here: documented
requirements that contradict the code are worse than undocumented ones, because they license wrong
work.

## Acceptance Criteria

- [ ] `docs/REQUIREMENTS.md` PHP/WP minimums match the `style.css` header exactly
- [ ] `docs/ROADMAP.md` "Concluído" section reflects what is actually committed
- [ ] Remaining backlog items are real (not already shipped) and each has an Impact/Effort estimate
- [ ] `docs/DESIGN.md` states whether the AES dark palette in `style.css` is the shipped palette
      or a base default (the `:root` block is 8 lines; legacy CSS carries the visual design)

## Scope

**In scope:** `docs/REQUIREMENTS.md`, `docs/ROADMAP.md`, `docs/DESIGN.md` only.
**Out of scope:** changing PHP/WP support levels to match docs instead of the reverse — the code is
the source of truth; **changing theme code**; PT-PT translations.

## Dependencies

None. Blocks T003 (the gate must encode the corrected values).

## Rollback

Docs-only change; `git checkout docs/` restores.

## Known Risks

Choosing the wrong direction (bump code down to PHP 7.4 vs. bump docs up to 8.2) silently changes
the deployable audience. `inc/ale-compat.php` and the `theme.json` v2 shape suggest 8.2 is real —
confirm before writing.

## Notes

Decide whether `Requires at least: 6.0` in `style.css` is itself a defect (theme.json v2 +
block patterns want 6.5+). That is a `style.css` change → separate ticket if confirmed.
