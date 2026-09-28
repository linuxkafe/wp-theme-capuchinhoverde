---
ticket: T001
title: Bootstrap AES project structure
sprint: sprint-01
priority: high
status: done
created: 2026-09-27
---

# T001 — Bootstrap AES project structure

## Context

The repository shipped an AES documentation set but none of the enforcement infrastructure:
no `aes/`, no `Makefile`, no `CLAUDE.md`, no `.aes/hooks/`, no `.gitignore`. `node_modules/`,
`package-lock.json` and `test-results/` were untracked and unignored, one commit away from being
committed. Every prior "AES Phase N" commit therefore had no gate behind it.

## Acceptance Criteria

- [x] `aes/{kanban.md,sprints,tickets,handoffs,verification}` exist
- [x] `Makefile` provides `setup test lint format check doctor metrics clean ux-check wake`
- [x] `make check` exits 0 on a clean tree, and reports SKIP (never PASS) for unrunnable gates
- [x] `make doctor` distinguishes real from skipped and never fails the build
- [x] `.aes/hooks/*.sh` installed, `bash -n` clean, `.git/hooks/pre-commit` wired
- [x] `scripts/verify-implementation.sh` + `scripts/hostile_analysis_lint.py` installed
- [x] `node_modules/`, `test-results/`, `playwright-report/`, `vendor/` ignored
- [x] `CLAUDE.md`, `docs/CHECKLIST.md`, `docs/QUALITY_GATES.md` written for this domain
- [x] T002 documented (docs drift found during bootstrap)

## Scope

**In scope:** scaffolding, gates, docs infrastructure.
**Out of scope:** PHP code, `style.css` header, translations, CI, PHPStan adoption (T004).

## Rollback

Delete `Makefile`, `.aes/`, `aes/`, `scripts/`, `bin/`, `.gitignore`; remove `.git/hooks/pre-commit`.
`docs/{CHECKLIST,QUALITY_GATES}.md` and `CLAUDE.md` are additive.

## Known Risks

- Hooks copied from `/opt/aes/.aes/hooks/` assume `aes/kanban.md` and ticket files exist. They ship in
  the same commit as the structure, so a hook cannot run against a half-installed state.
- The installed skill at `~/.config/opencode/skills/aes/hooks/` contains **broken symlinks** (they
  point at `../.aes/hooks/`, which does not exist in the install). Hooks must be copied from
  `/opt/aes/.aes/hooks/` instead. This is an AES installation defect, reported, not worked around
  silently.

## Defects found in this ticket's own output (fixed before declaring done)

1. `check-doc-consistency.sh` read `6.0` instead of `7.4` on the line
   "WordPress 6.0+ e PHP 7.4+" — the version was extracted from the whole line rather than from
   the match. Fixed with `grep -o` on the anchored token. This is exactly the naive-grep failure
   mode T003 warns about.
2. `make test-e2e` used multi-line `if ... exit 0` recipe blocks. In make each line is a separate
   shell, so the SKIP message printed **and Playwright ran anyway**, producing two confusing
   failures. Fixed by collapsing to one `if/elif/else` block.
3. `make doctor` aborted at the first failing gate, so the AES section never printed and a
   diagnostic was reported as a build failure. Fixed with `|| true` + an explicit note.
4. `aes/kanban.md` with the template's literal `current_ticket: ""` made `.aes/hooks/pre-commit.sh`
   extract the two-character string `""`, which is not empty — so the hook's "no current ticket →
   skip" branch never fired and **every** commit was blocked with
   `ERROR: Ticket file not found for ""`. Fixed by writing a bare `current_ticket:` in the kanban
   rather than forking the shared hook. Re-arm the gate by setting `current_ticket: TXXX`.

## Notes

Evidence for fix 2: with a stub server on :8083 the gate ran Playwright (1 passed / 1 failed);
with nothing on :8083 it printed SKIP and ran nothing. A skip that cannot be distinguished from a
real run is not a gate.
