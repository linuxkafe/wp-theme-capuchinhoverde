# Peer Review Round 1 — sprint-02-03 Cafeteria v1.7 port

- **Rubric:** `CAPUCHINHO-PORT-v1`, SHA-256 `d87229fd5263116f81c044823700defc74a40c076870931a6944be161580e58d`
  (pre-registered and staged to git before any reviewer saw the candidate)
- **Mode:** Multi-Perspective Review — **mandatory**, not optional. `PEER_REVIEW.md` §4 requires
  2+ reviewers from different model families; the 4 subagents available are all the same family,
  so the fallback protocol applies.
- **Moderator role:** VERIFIER (execution access). Every finding below was reproduced by me
  before being accepted. One finding is marked NÃO-VERIFICÁVEL.
- **Author role:** the authoring agent. Self-audit is pre-flight, not review; it is not counted
  as a reviewer anywhere in this round.

## Deduplication

17 raw findings from 4 personas collapsed to 16; one cluster merged three sources:

| Finding | Personas |
|---------|----------|
| Divergent map allowlists | PURISTA + PRAGMÁTICO + UTILIZADOR |
| PARITY.md reports the port as empty | CÍNICO + PURISTA |
| Unreachable partials + false "not ported" comment | CÍNICO + PRAGMÁTICO |

## Verdict: **REJECT**

Two independent rules in `PEER_REVIEW.md` §6 force this, and the second is not mine to waive:

| Rule | Condition | Result |
|------|-----------|--------|
| Finding counts | ≥1 BLOCKER → at best MAJOR-REVISIONS | 4 BLOCKER → cap MAJOR-REVISIONS |
| Human script not executed | → REJECT | I am the author, so I cannot execute it |

**4 BLOCKER · 9 MAJOR · 3 MINOR.** Every one verified with a command.

The four BLOCKERs share a shape worth naming: **each is a gate that reports success while the
thing it gates is broken.** `make check` exits 0 with zero tests run; the colour-scheme selector
is dead code credited as delivered; the Slider editor throws a fatal; and the only UI path for
slider slides writes a value the reader always discards. A green suite and 68 passing tests did
not catch any of them.

## The three that matter most

**B1 — the gate lies when the environment is absent.** `make check WP_URL=http://localhost:9999`
prints `check: OK` and exits 0 with **0 of 68 tests executed**. The whole suite lives behind one
`curl` reachability probe. `CLAUDE.md:63` says "Do not describe a skip as a passing test run" —
the tool the project ships does precisely that. A gate that cannot fail is worse than no gate,
because it is believed.

**B2 — a delivered feature is non-functional.** `assets/js/legacy/scripts.js:24,28,33` call
`$(...).live()`. jQuery 3.7.1 removed `.live()`; the served file has zero occurrences of it. Every
click in the colour selector binds nothing. T010b's ticket marks this delivered and the E2E test
asserts the six `.icbox` elements *exist* — it never clicks one. Asserting presence instead of
behaviour is how a dead control passes.

**B3+B4 — the Slider is broken on both paths.** The metabox calls `esc_textarea()` on an array →
`TypeError` → "There has been a critical error" on the editor screen. Separately, the sanitiser
emits a JSON **string** while `ale-compat.php:96` requires an **array**. The front end only
renders a slider because `seed.sh:90` injects a PHP array through wp-cli, bypassing the UI. The
E2E suite seeds through wp-cli too, so it could not have caught either.

## Pattern across the round

Three findings are the same mistake I made repeatedly, caught by people who were not me:

| Pattern | Findings |
|---------|----------|
| Seeded fixtures mask broken UI paths | B3, B4, M6 |
| Tests assert presence, not behaviour | B2, M7 |
| Documentation drifts from code and nobody diffs it | M1, M2, M4 |

M7 is the sharpest: **the `custompagecss` security test cannot fail.** The save-layer sanitiser
destroys the payload, so `header.php`'s render allowlist never executes — deleting that allowlist
outright leaves the test green.

## Reviewer conduct note

The CÍNICO persona modified the environment despite explicit read-only instructions (it re-ran
`scripts/seed.sh` and reset theme_mods). Findings were unaffected because the URILADOR and
PRAGMÁTICO personas read state independently, but the instruction was violated and the
round's fixture state is not provably pristine. Recorded because it is evidence about the
protocol, not about the code.

## What a reviewer could NOT check

- **Visual parity with Cafeteria v1.7.** No reference render exists. T006's screenshot criterion
  remains unticked for this reason.
- **`make debt`** — no debt counter exists in this project.
- **PHPStan** — not installed (T004 blocked). Every "no secrets", "no type error" and "no
  undefined variable" claim in this round rests on grep and runtime observation only.
- **`make check` from a clean clone** (R-01) — requires `docker compose down -v`, which would
  destroy the fixture the other reviewers were reading.

## Closure route

Findings → `scripts/finding-to-ticket.sh`. Proposed order, BLOCKERs first:

1. B1 → gate must not report OK with 0 tests
2. B3 + B4 → decide the `_cg_slides` data shape, then fix writer, reader and UI together
3. B2 → decide the vendored-JS path question (see M3 and `CLAUDE.md`'s carve-out)
4. M1 + M2 + M4 → recompute PARITY.md from the code; correct the `style.css` header
5. M3 → one map allowlist
6. M5 + M6 + M8 → seed correctness and dead toggles
7. M7 + M9 → tests that can fail, validation feedback that is visible
8. m1 + m2 + m3 → determinism and honest fixtures
