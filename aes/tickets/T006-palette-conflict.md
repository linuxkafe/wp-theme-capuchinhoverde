---
ticket: T006
title: Resolve dark/light palette conflict between style.css and legacy CSS
sprint: sprint-02
priority: high
status: done
created: 2026-09-27
resolved: 2026-09-27
---

# T006 — Resolve the dark/light palette conflict (RESOLVED)

## Context

Two mutually exclusive visual identities are live in the same theme:

- **Cafeteria v1.7 (legacy, light)** — `assets/css/legacy/`: cream `#f0ece3`, clay `#5e3c3d`,
  mist `#b1dae6`, white surfaces. 98 white, 70 clay, 82 mist occurrences.
- **AES template (dark)** — the `:root` block at `style.css:19-27`: bg `#0a0a0a`, text `#fafafa`,
  accent `#22c55e`.

Only `--cg-accent` is actually consumed (`style.css:30`, link colour). The rest are declared
tokens no stylesheet uses. The conflict has already produced a visible defect once — commit
`72a0b4f` "fix: resolve black page issue - remove conflicting body styles" is the scar tissue.

`docs/DESIGN.md` now documents both palettes instead of asserting one, because choosing is the
point of this ticket.

## Acceptance Criteria

- [x] One palette declared canonical — **the Cafeteria identity**, decided by the owner 2026-09-27
- [x] The dark template `:root` block deleted, not merely re-labelled
- [x] `style.css` `:root` tokens populated from the source's own defaults
      (`aletheme/config.php:165` heading `#5e3c3d`, `:151` Georgia, `:201` body `#474747`)
- [x] `theme.json` palette and fontFamilies synced so the block editor matches the front end
- [x] `.colormain` and `.firstfont` defined — they were undefined theme-wide, because the
      source generated them from the unported `partials/css-option.php`
- [x] E2E asserts the computed background is `rgb(240, 236, 227)` and `.colormain` is
      `rgb(94, 60, 61)`, and that the dark palette is gone
- [x] `docs/DESIGN.md` rewritten to describe the canonical palette and cite each source
- [ ] **DEFERRED — visual parity by screenshot.** See below.

## Resolution notes

The design's decorative image set was also missing: the vendored CSS makes 104 `url()`
references to `assets/css/legacy/images/`, a directory that had never been copied. 76 files were
ported from the source's `css/images/`; E2E now asserts zero unresolved assets.

**Deferred and still open:** a screenshot baseline. There is no working reference render of
Cafeteria v1.7 to diff against — the source theme cannot be installed on WP 6.6 without
modifying it, which is the very port being done. Options: (a) install the source on WP 5.x in a
second container, (b) accept "token-for-token CSS parity" as the definition. This is a judgement
call for the owner and is why the criterion above is unticked rather than quietly passed.

## Scope

**In scope:** `style.css` `:root` block, `assets/css/legacy/` colour tokens (only if the Cafeteria
palette wins and a token layer is introduced), `docs/DESIGN.md`.
**Out of scope:** layout, typography, spacing, the PHP compat layer, WP minimum (T005).

## Dependencies

None, but do it **before** any future work on `assets/css/legacy/` — editing either palette while
the ownership question is open is how the black-page defect happened.

## Rollback

Single-file revert for the `style.css` block. A token refactor into the legacy CSS is larger and
must ship behind its own commit.

## Known Risks

- Choosing "AES dark" means abandoning visual parity with Cafeteria — a product decision, not a
  technical one. It would also make `assets/css/legacy/` largely obsolete.
- Choosing "Cafeteria light" means the theme is a rebrand of the original with a new name, which
  raises a licensing/attribution question about the vendored CSS.
- Token extraction is the kind of refactor that silently alters specificity. Any change must be
  verified by screenshot, not by inspection.

## Notes

The name "Capuchinho Verde" and the green accent suggest the AES dark palette with a green twist
was the intent, while every committed stylesheet says Cafeteria. The owner must resolve which
story is true before code moves.
