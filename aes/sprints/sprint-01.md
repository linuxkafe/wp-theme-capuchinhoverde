---
sprint: sprint-01
period: 2026-09-27 → 2026-10-04
status: active
---

# Sprint 01 — Establish gates and truthful docs

**Goal:** make the repository self-describing and make drift fail loudly instead of silently.

## Why this sprint exists

The theme shipped as a port of Cafeteria v1.7 with AES docs referenced but no AES infrastructure:
no `aes/`, no `Makefile`, no hooks, no `CLAUDE.md`. The docs that did exist were stale
(`docs/REQUIREMENTS.md` claims PHP 7.4+/WP 6.0+; `style.css` declares `Requires PHP: 8.2`), and
`docs/ROADMAP.md` still lists Playwright E2E and CPT templates as backlog although both are
committed (`playwright.config.ts`, `single-cg_menu.php`).

## Tickets

| ID | Title | Status |
|----|-------|--------|
| T001 | Bootstrap AES project structure | done |
| T002 | Reconcile docs with code | pending |
| T003 | Add doc-consistency gate to `make check` | pending |
| T004 | Decide on PHPStan / composer.json | blocked |

## Definition of Done

- [ ] `make check` exits 0 on a clean tree
- [ ] `docs/REQUIREMENTS.md` matches `style.css` header (PHP/WP versions)
- [ ] `docs/ROADMAP.md` backlog contains no completed work
- [ ] `CLAUDE.md` non-goals section filled in by a human
- [ ] `node_modules/`, `test-results/`, `playwright-report/` ignored by git

## Retrospective

*Filled at end of sprint.*
