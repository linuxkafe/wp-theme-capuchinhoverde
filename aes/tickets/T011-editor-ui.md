---
ticket: T011
title: Add an editor surface for the _cg_ meta the templates read
sprint: sprint-03
priority: high
status: done
created: 2026-09-27
---

# T011 — Add an editor surface for the theme's meta

## Context

**The single largest reason the port is hollow.** The templates read 30 keys through
`ale_get_meta()` and 24 through `ale_get_option()`. Until this ticket, the theme registered
**no metabox, no Customizer panel, and no `register_post_meta`** — there was no writer for any of
them. Every metered section was permanently off and `page-home.php` rendered empty spacers.

`scripts/seed.sh` writes the keys with wp-cli, so the E2E suite passes. **The theme is currently
testable but not usable.** That distinction is the whole point of this ticket.

## Design decision

One **schema array** in `inc/meta.php` is the single source of truth. It drives three consumers:
`register_post_meta()` (REST + block editor), the metabox UI, and the sanitisation. A key cannot
exist in the UI without being registered, and cannot be registered without a sanitiser — which is
what prevents the "template reads a key nobody can write" bug class from recurring.

Chosen surface: **classic metabox + `register_post_meta(show_in_rest)`.** The metabox renders in
the block editor through WordPress's meta box compatibility layer and in the classic editor, so
one implementation covers both — no custom `PluginSidebar` JavaScript to maintain.

Rejected: a block-editor `PluginDocumentSettingPanel`, which would be more idiomatic but needs
JS, a build step, and gives no benefit while the content model is still being migrated.

## Acceptance Criteria

- [x] Single schema in `inc/meta.php` drives registration, the metabox, and sanitisation
- [x] All 30 `ale_get_meta()` keys have a writer
- [x] Home-section toggles (`serviceonhome`, `galleryonhome`, `contactonhome`) are checkboxes
- [x] Services are a 4-row group (image, title, link, description) — not 16 loose inputs
- [x] Every key has a `sanitize_callback` and an `auth_callback`
- [x] Meta registered with `show_in_rest` so the block editor can read it
- [x] Missing keys render as empty, never as `0`/`null` leaking into output
- [x] `ale_get_meta()` never returns a truthy non-string for a text key
- [x] **E2E: log into wp-admin, edit the home page meta through the real UI, save, and assert
      the front end changes.** 7 tests in `tests/e2e/meta.spec.ts`, all driving the real UI
- [x] `make check` fails if a template reads a key that no schema declares
      (`scripts/check-key-coverage.sh`) — verified by deleting a key and watching it fail

## Scope

**In scope:** `inc/meta.php` (new), `inc/customizer.php` (new, site-wide options),
wiring in `functions.php`, sanitisation in `ale_get_meta()`.
**Out of scope:** the ALETheme options panel (`docs/PARITY.md` §6); the About/Contact templates
that consume the `contact*` keys (T012).

## Dependencies

Follows T010. Blocks T016 (the Customizer panel is where typography controls live).

## Rollback

Two new files plus a `require`. No data migration — all keys are additive.

## Known Risks

- Meta box values are saved on post save; a user who never opens the page editor never sees them.
  Acceptable for a first pass, but it means a `Customizer` section may later be the better home
  for the `homeslugfull`/logo/social keys. T016 will decide.
- Registering `show_in_rest` exposes the keys to anyone who can `edit_posts`. The
  `auth_callback` must deny that where the key is admin-only, or the REST endpoint is a
  privilege leak. This is asserted in the test.
