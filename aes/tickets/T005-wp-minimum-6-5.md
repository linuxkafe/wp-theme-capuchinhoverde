---
ticket: T005
title: Raise WordPress minimum to 6.5 in style.css
sprint: sprint-02
priority: high
status: pending
created: 2026-09-27
---

# T005 — Raise `Requires at least` to 6.5 in `style.css`

## Context

`style.css:8` declares `Requires at least: 6.0` while `docs/VISION.md` and `docs/ROADMAP.md`
describe a block-first theme on a WP 6.5+ target, and the theme ships `theme.json` **v2** plus a
block pattern (`patterns/menu-card.php`) — both 6.5-era features. The header understates what the
code actually needs, which means the theme can be installed on 6.0/6.1/6.2/6.3/6.4 sites where
block rendering degrades.

Direction decided by the theme owner on 2026-09-27 (T002): the header is the intended value and the
floor is the defect. This ticket exists because `CLAUDE.md` forbids silently editing the `style.css`
version header to satisfy a gate — a support-level change gets its own ticket.

## Acceptance Criteria

- [ ] `style.css` `Requires at least: 6.5` and `Tested up to` reviewed against the last 6.x
- [ ] `docs/REQUIREMENTS.md` and `docs/VISION.md` updated in the same diff
- [ ] `make doc-consistency` green **after** the change (it will fail mid-ticket — that is expected)
- [ ] Block pattern renders in the site editor on a 6.5 instance
- [ ] `docs/QUALITY_GATES.md` "No PHP fatal reachable on WP 6.0+" item updated to 6.5+
- [ ] `docs/CHECKLIST.md` severity table unchanged (this is a BLOCKER-grade support claim)

## Scope

**In scope:** `style.css` header, `docs/REQUIREMENTS.md`, `docs/VISION.md`,
`docs/QUALITY_GATES.md`, `docs/CHECKLIST.md`.
**Out of scope:** any PHP/template change; back-compat shims for WP < 6.5; the palette conflict
(that is T006).

## Dependencies

None. Blocks nothing; T003's gate already handles the transient mismatch.

## Rollback

`git revert` the header commit — a one-line change, but the docs follow it.

## Known Risks

Drops support for WP 6.0–6.4 installs, which is a real audience decision. If a site in the wild
still runs 6.0, this upgrade is breaking for them. Confirm with the owner whether any known site
is below 6.5 before merging.

## Notes

Decided in the T002 question on 2026-09-27 ("Raise to 6.5 — opens a style.css ticket"). PHP stayed
at 8.2 (T002); only the WordPress axis moves.
