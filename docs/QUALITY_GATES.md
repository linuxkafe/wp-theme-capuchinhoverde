# QUALITY GATES

Domain-specific gates for a WordPress **PHP block theme**. Load alongside `docs/CHECKLIST.md`.

## Gates that run here

| Gate | Command | Severity | Notes |
|------|---------|----------|-------|
| PHP syntax | `make lint` | BLOCKER | `php -l` per tracked file. Parses only — not analysis. |
| Format | `make format` | WARNING | Skips cleanly; php-cs-fixer is not installed (T004). |
| Doc consistency | `make doc-consistency` | BLOCKER | `style.css` header vs `docs/REQUIREMENTS.md`. |
| E2E | `make test` | WARNING | Skips without a live WordPress at `$WP_URL`. |
| UX manifests | `make ux-check` | n/a | No `docs/UX/pages/` — classic PHP templates, not an SPA router. |
| Static analysis | — | BLOCKED | No `composer.json`; decision pending in T004. |

## Deliberately absent gates (and why)

- **PHPStan / Psalm** — no Composer in the deploy pipeline. `php -l` cannot catch wrong hook
  priorities, undefined globals, or misspelled meta keys. This is a known blind spot, tracked in
  `aes/tickets/T004-static-analysis-decision.md`. Do not claim type-safety anywhere.
- **Visual regression** — the design is a port; a screenshot diff against Cafeteria v1.7 would be
  the strongest available gate for `inc/ale-compat.php`, and it does not exist. Open a ticket
  before touching legacy parity code.
- **Theme Check plugin** — not wired. Candidate for CI (see ROADMAP).

## Definitions

- **PASS** — the gate ran to completion and every check succeeded.
- **FAIL** — the gate ran and found a violation.
- **SKIP** — the gate could not run (missing tool, unreachable service). A SKIP is never a PASS,
  and any report containing a SKIP must say so in the same breath.
- **PRE-EXISTING** — a failure present before the current diff. Reported, not fixed opportunistically.

## Escalation

| Situation | Action |
|-----------|--------|
| New lint error or PHP fatal | STOP, revert or fix before proceeding |
| Pre-existing failure | Record in the Verify output, continue, do not silently fix |
| Gate silently no-ops | Treat as a BLOCKER bug in the gate — see T003 |
| Environment missing a tool | Surface it; never work around it quietly |
