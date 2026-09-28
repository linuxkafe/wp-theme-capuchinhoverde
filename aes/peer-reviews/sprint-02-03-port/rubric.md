---
rubric-id: CAPUCHINHO-PORT-v1
candidate: sprint-02-03 — Cafeteria v1.7 feature/design port (T007, T010b, T011, T012, T013)
created: 2026-09-27
dimensions: [correctness, security, coherence, debt, reproducibility, usability]
reviewers: multi-perspective (4 personas) — same model family, so PEER_REVIEW.md §4 fallback is mandatory
---

# Review Rubric — CAPUCHINHO-PORT-v1

Every criterion is a command or a file check. No adjectives.

## D1 Correctness

| ID | Criterion | Verifiable check |
|----|-----------|------------------|
| C-01 | Full gate is green | `make check` exits 0 |
| C-02 | E2E is not silently skipped | `make check` output contains no `test: SKIP` |
| C-03 | Test count is material, not decorative | `npx playwright test --list` yields ≥ 60 tests |
| C-04 | Every PHP file parses, including untracked | `make lint` exits 0 and reports ≥ 33 files |
| C-05 | The seed is idempotent | `bash scripts/seed.sh` twice in a row both exit 0 |
| C-06 | CPT rewrite rules exist after activation | `docker compose run --rm -T cli eval 'var_dump(!empty(get_option("rewrite_rules")))'` is not false |
| C-07 | No page is a truncated PHP fatal | `npx playwright test shortcodes -g "renders a real page"` passes for all 7 routes |

## D2 Security

| ID | Criterion | Verifiable check |
|----|-----------|------------------|
| S-01 | No secrets committed | `git diff --cached; grep -rniE 'password|api_key|secret|token' --include=*.php --include=*.ts --include=*.sh . | grep -v node_modules` returns nothing containing a real credential |
| S-02 | Shortcode attributes are escaped | `grep -c 'esc_attr\|esc_url\|esc_html' inc/shortcodes.php` ≥ 25 |
| S-03 | `extract()` is absent from ported code | `grep -rn 'extract(' inc/ partials/ *.php | grep -v EXTR_SKIP` is empty |
| S-04 | Custom CSS cannot escape a style attribute | `npx playwright test meta -g "escape the style attribute"` passes |
| S-05 | A `javascript:` URL never reaches the DOM | `npx playwright test meta -g "javascript: image URL"` passes |
| S-06 | Map embeds are host-allowlisted and sandboxed | `grep -c 'sandbox=' template-contact.php inc/shortcodes.php` ≥ 2 and `grep -c 'openstreetmap.org' inc/shortcodes.php` ≥ 1 |
| S-07 | The contact form is nonce-protected | `npx playwright test meta -g "bad nonce"` passes |

## D3 Epistemic Coherence

| ID | Criterion | Verifiable check |
|----|-----------|------------------|
| E-01 | Docs do not claim unwritten features | Every feature listed in `style.css` Description appears as OK or GAP in `docs/PARITY.md` |
| E-02 | SKIPs are labelled as SKIP, never as passes | `grep -c "SKIP" docs/QUALITY_GATES.md` ≥ 1 and the file defines SKIP ≠ PASS |
| E-03 | The parity matrix states its own method | `docs/PARITY.md` contains the word `verified` and names the WordPress version |
| E-04 | Deferred work is recorded, not dropped | `grep -c "deferred\|DEFERRED\|Deferred" aes/tickets/T010b-partials.md aes/tickets/T012-about-contact-templates.md aes/tickets/T013-content-shortcodes.md` ≥ 1 per file |
| E-05 | Key-coverage gate is honest about its own limits | `grep -q "literal keys only" scripts/check-key-coverage.sh` |
| E-06 | Every "absent" row in the parity matrix is a real gap | `grep -c "absent" docs/PARITY.md` equals the number of rows marked absent, verified by reading |

## D4 Debt

| ID | Criterion | Verifiable check |
|----|-----------|------------------|
| D-01 | No no-op functions with misleading comments | `grep -rn "Not a hard block\|Not implemented\|TODO" functions.php inc/ *.php` is empty |
| D-02 | No dead partials | every file in `partials/` is referenced by `grep -rn "ale_part(" *.php` or by another partial |
| D-03 | New JS is not jQuery | `grep -c 'jQuery\|\$(' assets/js/toggle.js` is 0 |
| D-04 | No duplicate definition of a core function | `grep -rn "function_exists('_e')\|function_exists('__')" . --include=*.php | grep -v node_modules` is empty |
| D-05 | No `query_posts` | `grep -rn "query_posts" *.php partials/ inc/` is empty |

## D5 Reproducibility

| ID | Criterion | Verifiable check |
|----|-----------|------------------|
| R-01 | A clean clone reaches a green gate | `make wp-seed && make check` exits 0 on a fresh `docker compose down -v` |
| R-02 | Assets are web-readable | `find assets -type f ! -perm -o=r | wc -l` is 0 |
| R-03 | No untracked file is outside gitignore | `git status --short | grep '^??' | grep -v node_modules` lists only intentional additions |
| R-04 | The seed is the documented path to a testable site | `grep -q "make wp-seed" CLAUDE.md` |
| R-05 | The gate provably fails when it should | documented in `aes/tickets/T010b-partials.md` (chmod 700 → test fails naming the URL) |

## D6 Usability

| ID | Criterion | Verifiable check |
|----|-----------|------------------|
| U-01 | An administrator can turn every section on through the UI | `npx playwright test meta -g "writing through the UI"` passes |
| U-02 | No key is readable by a template but writable by no one | `make key-coverage` exits 0 |
| U-03 | Every page has exactly one h1 | `npx playwright test smoke -g "exactly one h1"` passes on 4 routes, and `templates` on 2 more |
| U-04 | The contact form reports validation errors | `npx playwright test -g "reports validation errors"` passes |
| U-05 | Admin-driven tests exist, not just seeded ones | `grep -c "wp-admin" tests/e2e/*.spec.ts` ≥ 3 files |

---
pre-registered-hash: (see rubric-hash)
