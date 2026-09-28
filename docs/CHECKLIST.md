# CHECKLIST

## Pre-commit (every ticket, no exceptions)

- [ ] `make check` exits 0 — paste the output, including SKIP lines
- [ ] Acceptance criteria in `aes/tickets/TXXX-*.md` ticked with the command that proved them
- [ ] `git diff` contains no unrelated changes (surgical change rule)
- [ ] No `console.log` / `var_dump` / `error_log` debug residue in committed code
- [ ] No `TODO` without a linked ticket
- [ ] Docs changed **iff** behaviour changed (VISION / REQUIREMENTS / ROADMAP)
- [ ] Diffstory written in the Build output

## Theme-specific (BLOCKER)

- [ ] `php -l` clean on every changed PHP file
- [ ] No PHP fatal reachable on WP 6.0+ (the `style.css` `Requires at least` floor) unless a
      ticket deliberately raises it
- [ ] Core templates stay jQuery-free (`inc/ale-compat.php` is the only exception)
- [ ] New strings wrapped in `esc_html__()` / `esc_attr__()` with the `capuchinhoverde` text domain
- [ ] `languages/capuchinhoverde.pot` regenerated if translatable strings changed
- [ ] `theme.json` changes validated (valid JSON, still `version: 2`)

## Release (WARNING until a release process exists)

- [ ] Theme zip excludes `node_modules/`, `test-results/`, `aes/`, `Makefile`, `tests/`
- [ ] E2E run against a real instance at `$WP_URL` — a skip is not a pass
- [ ] Screenshot/visual parity with Cafeteria v1.7 for any `assets/**/legacy/` divergence

## Severity

| Item | Severity |
|------|----------|
| PHP fatal, jQuery in core, unescaped output | BLOCKER |
| Missing docs for changed behaviour | BLOCKER |
| E2E skipped, format skipped, static analysis absent | WARNING — must be stated explicitly, never reported as passing |
