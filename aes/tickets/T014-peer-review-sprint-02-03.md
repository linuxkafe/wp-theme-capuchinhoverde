---
ticket: T014
title: Peer review round 1 — sprint-02-03 port
sprint: sprint-03
priority: high
status: done
created: 2026-09-27
verdict: REJECT
---

# T014 — Peer review round 1: the port is REJECTED

## Context

The sprint-02 and sprint-03 port work (T007, T010b, T011, T012, T013) was submitted to
`aes-peer-review` after `make check` went green with 68 passing tests. The review found **4
BLOCKER, 9 MAJOR, 3 MINOR** — all verified by the moderator before acceptance.

Artifacts: `aes/peer-reviews/sprint-02-03-port/`
(rubric + hash, 16 finding files, `round-1-report.md`, `human-validation.sh`)

## Verdict: REJECT

| Rule (PEER_REVIEW.md §6) | Condition | Result |
|---|---|---|
| Finding counts | ≥1 BLOCKER → at best MAJOR-REVISIONS | 4 BLOCKER → cap |
| Human script not executed | → REJECT | The author cannot execute it — `human-validation.sh` awaits an independent human |

Mode was Multi-Perspective Review, **mandatory**: §4 requires 2+ reviewers from different model
families and the available subagents are all one family.

## Acceptance Criteria

- [x] Rubric pre-registered and committed before any reviewer saw the candidate
- [x] 4 personas convened as fresh subagents, no shared history or findings
- [x] Every finding reproduced by the moderator (VERIFIER role) before acceptance
- [x] Duplicates merged across personas, divergences preserved
- [x] Human validation script produced; **not executed — requires someone other than the author**
- [x] Verdict computed from the rule table, not negotiated
- [x] What a reviewer could not check is stated explicitly

## The four BLOCKERs

| # | Defect | Why the green suite missed it |
|---|--------|-------------------------------|
| B1 | `make check` prints `check: OK` and exits 0 with **0 of 68 tests run** | The gate's only environment check is a `curl` reachability probe that SKIPs |
| B2 | The colour-scheme selector is **dead code** — vendored `scripts.js` uses jQuery `.live()`, removed in 3.7.1 | The test asserts the six `.icbox` elements exist; it never clicks one |
| B3 | Editing **any Slider is a PHP fatal** — `esc_textarea()` on an array | No test opens a `cg_slider` edit screen |
| B4 | The only UI path for slider slides writes a **JSON string** while the reader requires an **array** | `seed.sh` injects a PHP array via wp-cli, bypassing the metabox |

## The pattern, named honestly

All four are the same failure: **a gate that reports success while the thing it gates is broken.**
And the root cause is mine: `scripts/seed.sh` writes fixtures through wp-cli, so the E2E suite
seeded around the UI paths that were broken. A test that sets up its own preconditions cannot
detect a broken precondition.

That is a defect in my methodology, not in one file. It also invalidates part of the evidence I
have been presenting: "68 passing" overstates what was actually verified, because the fixtures
were constructed to make passing possible.

## MAJORs accepted

M1 `docs/PARITY.md` reports the port as delivering nothing (0 shortcodes, 0 partials, 51 tests vs 6/11/68) ·
M2 `style.css` still claims "Zero jQuery dependency" · M3 three divergent map allowlists, two
documented as one · M4 three unreachable partials + `header.php` claiming a ported partial is
missing · M5 `seed.sh`'s rewrite check is a tautology that can never pass · M6 seed is not
idempotent (10 duplicate nav items) · M7 **the `custompagecss` security test cannot fail** —
deleting the render allowlist leaves it green · M8 "Enable the contact form" controls nothing ·
M9 the form says "correct the highlighted fields" and highlights none.

## Not verifiable in this round

Visual parity with Cafeteria (no reference render exists) · `make debt` (no counter in this
project) · PHPStan claims (not installed, T004 blocked) · R-01 clean-clone reproducibility
(requires `down -v`, which would destroy the fixture under review).

## Scope of this ticket

**In scope:** run the protocol and report the result honestly.
**Out of scope:** fixing the findings. That is T015+ and must not begin until the owner has seen
this verdict — 4 BLOCKERs invalidate the sprint's completion claim, and I am the least impartial
party to decide they are closed.

## Rollback

`git rm -r aes/peer-reviews/` — nothing in the theme depends on it.

## Notes

One reviewer (CÍNICO) modified the environment against explicit read-only instructions. Findings
were unaffected because two other personas read state independently, but the round's fixture
state is not provably pristine. Recorded as evidence about the protocol.
