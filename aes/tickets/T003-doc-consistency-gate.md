---
ticket: T003
title: Add doc-consistency gate to make check
sprint: sprint-01
priority: medium
status: pending
created: 2026-09-27
---

# T003 — Add doc-consistency gate to `make check`

## Context

T002 fixes the drift by hand. Hand-fixes decay. The bootstrap shipped
`scripts/check-doc-consistency.sh`, which compares the PHP/WP version claims in
`docs/REQUIREMENTS.md` against the `style.css` theme header and fails on mismatch. This ticket
makes it a first-class, tested gate rather than a script nobody trusts.

## Acceptance Criteria

- [ ] `make check` runs the consistency gate and exits non-zero on mismatch (verify by temporarily
      editing a version string, then reverting)
- [ ] Gate emits the specific field that mismatched, not just "docs out of date"
- [ ] Gate is a no-op (exit 0) when `docs/REQUIREMENTS.md` has no version claims to compare
- [ ] A unit test covers the mismatch and the no-claims cases
- [ ] Gate runs in the pre-commit path only as a WARNING (docs drift must not block unrelated commits)

## Scope

**In scope:** `scripts/check-doc-consistency.sh`, its test, `Makefile` wiring, `.aes/hooks/pre-commit.sh`
severity.
**Out of scope:** extending the gate to cover more than PHP/WP versions (ROADMAP-vs-git detection is a
separate, harder problem — see Backlog).

## Dependencies

T002 (values must be correct before they are enforced).

## Rollback

Remove the target from `make check`; nothing else depends on the script.

## Known Risks

A version gate is only as good as its parser. A naive `grep` will pass on commented-out lines —
use anchored patterns and fail loudly on unparseable input rather than skipping.

## Notes

Consider `git log` heuristics later for roadmap drift; that belongs in a `check-roadmap.sh` ticket.
