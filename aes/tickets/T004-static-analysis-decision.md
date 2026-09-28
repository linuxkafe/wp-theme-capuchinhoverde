---
ticket: T004
title: Decide on static analysis (PHPStan) adoption
sprint: sprint-01
priority: low
status: blocked
created: 2026-09-27
---

# T004 — Decide on static analysis (PHPStan) adoption

## Context

The theme has zero PHP dependencies and no `composer.json`. `php -l` only proves the file parses;
it cannot catch the class of defects that matters in a WordPress theme — wrong hook priorities,
undefined `$wp_query` globals, misspelled `get_post_meta` keys, unsafe output. `inc/ale-compat.php`
is a compatibility shim around a legacy theme and is exactly the code where those defects hide.

## Acceptance Criteria

- [ ] Decision recorded: adopt PHPStan (with `szepeviktor/phpstan-wordpress` + `php-stubs/wordpress-stubs`)
      or explicitly decline with a reason
- [ ] If adopted: `composer.json` + `composer.lock` committed, `phpstan.neon` at level 0 first,
      `make lint` extended, baseline for pre-existing errors
- [ ] `inc/ale-compat.php` is in scope (not excluded as "legacy")
- [ ] `make check` stays green on adoption day (baseline must be honest, not zero-error-by-exclusion)

## Scope

**In scope:** decision + minimal PHPStan wiring.
**Out of scope:** fixing findings (that is per-ticket work); upgrading to a high level immediately.

## Dependencies

**BLOCKED** — requires a decision from the theme owner: is Composer acceptable in the deploy
pipeline for a theme that currently has no vendor directory?

## Rollback

Delete `composer.json`, `phpstan.neon`, `vendor/`; revert the `make lint` hunk.

## Known Risks

`vendor/` in a theme repo is a deployment smell (bloated zip, stale deps shipped to production).
A `.distignore` or CI-only install may be the right call instead. Decide that first.

## Notes

If the answer is "no Composer", the fallback is a targeted `php -l` + grep-based assertions in
`tests/`, which is weaker — say so explicitly rather than pretending the gate is equivalent.
