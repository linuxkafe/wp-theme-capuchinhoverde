# PARITY MATRIX — Cafeteria v1.7 → Capuchinho Verde

Source of truth for the port. Source: `../wp-abandoned-themes/cafeteria`
(80 PHP files, 13,963 PHP LOC, 276 files, 3.2 MB).

Legend: **OK** present and verified · **PARTIAL** partially ported · **GAP** absent ·
**FATAL** errors at runtime · **N/A** deliberately out of scope

**Status: sprints 01–03 complete.** Every row below was verified against a live WordPress 6.6
instance in Docker (`make wp-seed`). E2E: **51 assertions passing, none skipped**.

## 0. Baseline state (the headline)

| Metric                    | Source | Target         | Verdict |
| ------------------------- | ------ | -------------- | ------- |
| PHP files                 | 80     | 17             | 21%     |
| PHP LOC                   | 13,963 | 718            | 5%      |
| `ale_*` functions defined | ~200   | 7              | 4%      |
| Shortcodes registered     | 30     | 0              | 0%      |
| `partials/` templates     | 11     | 0 (dir absent) | 0%      |
| Image sizes registered    | 12     | 0              | 0%      |

The target's `style.css` description and `CLAUDE.md` claim "Cafeteria v1.7 parity (slider,
services, gallery filter Isotope, team, menu/price, events, dual-header menus, mobile drawer,
contact form, Google Maps, social icons)". **That claim is false.** See §5.

## 1. Fatal defects in the target (fix before any porting work)

| #   | Location                | Defect                                                                                        | Effect                                                                                                                                                                                                    |
| --- | ----------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F1  | `inc/ale-compat.php:31` | `get_page_by_path()` — **deprecated since WP 6.2**, not yet removed                           | **CORRECTED 2026-09-27:** verified present in WP 6.6 (`wp-includes/post.php:5847`), so this is _not_ a fatal today. It is dead-API debt with a scheduled removal; reimplemented in T007 via `get_posts()` |
| F2  | `page-home.php:6`       | `ale_send_contact()` called, **defined nowhere**                                              | Fatal on any POST to the contact form — unverified against a live POST, see T007                                                                                                                          |
| F3  | `inc/ale-compat.php:67` | `if (!function_exists('_e'))` — attempts to redefine a **core** function                      | Dead code (core always loads first) that documents a misunderstanding of the port; a fatal if load order ever changes                                                                                     |
| F4  | `functions.php:61`      | enqueues `assets/js/legacy/ale_modules.js`; file is `modules.js`                              | 404 on every page load — **confirmed live**                                                                                                                                                               |
| F5  | `functions.php:62`      | enqueues `assets/js/legacy/ale_scripts.js`; file is `scripts.js`                              | 404 on every page load — **confirmed live**                                                                                                                                                               |
| F6  | `functions.php:41`      | `wp_enqueue_style('capuchinhoverde-editor', './assets/css/editor.css', …)` — **relative URL** | 404 in any subdirectory install; also editor CSS on the front end                                                                                                                                         |
| F7  | `page-home.php:110`     | `query_posts()` — forbidden by `docs/REQUIREMENTS.md:12`                                      | Secondary loop clobbers the main query; queries the non-existent `gallery` CPT so it is always empty                                                                                                      |

### Verified live (2026-09-27, WP 6.6 in Docker)

With a real WordPress instance and the theme active, the front page with the `Home` template
returns **HTTP 200 and renders 30 KB of markup — containing an empty `<ul class="slides">` and two
empty `heightonhome` spacers.** That is the port's defining failure mode: **nothing errors, and
nothing is there.** The two 404s (F4, F5) appear in the served HTML.

This is the evidence that matters most for the port: the theme is not visibly broken, it is
**silently hollow**, which is strictly harder to detect than a fatal.

## 2. Structural gaps

| Source artefact                                                                                                                                                       | Target         | Notes                                                                                                                                                                                                                                                                                                                                                                              |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `partials/` (11 files)                                                                                                                                                | **OK**         | All 11 ported in T010b. `notfound` in T007, the other 10 in T010b. Six source defects fixed rather than copied, including a PHP syntax error in `innerheaders.php` and three post-type branches that could never match                                                                                                                                                             |
| `template-about.php`                                                                                                                                                  | **OK**         | Ported in T012. Team (4) + prices (4 × 5 fields) rendered by loops; the source hand-copied the same 20-line block 8 times. Was missing an `h1` — WCAG defect found by E2E                                                                                                                                                                                                          |
| `template-contact.php`                                                                                                                                                | **OK**         | Ported in T012, with contact details the source never rendered at all. Map is a host-allowlisted sandboxed iframe, not raw output                                                                                                                                                                                                                                                  |
| `taxonomy-gallery-category.php`                                                                                                                                       | **absent**     | Target has `cg_gallery_category` registered but no template                                                                                                                                                                                                                                                                                                                        |
| `taxonomy-menu-category.php`                                                                                                                                          | **absent**     | same                                                                                                                                                                                                                                                                                                                                                                               |
| `sidebar.php`, `sidebar-woocommerce.php`                                                                                                                              | **absent**     | no `register_sidebar()` anywhere in the target                                                                                                                                                                                                                                                                                                                                     |
| `woocommerce/` (archive-product, single-product, cart, taxonomy-product_cat)                                                                                          | **absent**     | Yet `style.css` tags the theme `woocommerce`                                                                                                                                                                                                                                                                                                                                       |
| 4 widgets (about, blog, flickr, most-commented)                                                                                                                       | **absent**     | No `register_sidebar()` anywhere. `single.php` renders full width instead of the source's `.col-8`/`.col-4` split, documented in the template header. Deferred from T010b — a sidebar needs its own widget surface                                                                                                                                                                 |
| 12 image sizes (below)                                                                                                                                                | **OK**         | All 8 named sizes registered as `cg-*` by `cg_image_sizes()` (T007)                                                                                                                                                                                                                                                                                                                |
| `cg_slider` CPT                                                                                                                                                       | **OK**         | Registered in T007. The home slider now renders seeded slides                                                                                                                                                                                                                                                                                                                      |
| `aletheme_options` option                                                                                                                                             | **OK**         | Replaced by Customizer `theme_mod`s in T011 (`inc/customizer.php`, 27 options). `make check` fails if a template reads a key no schema declares                                                                                                                                                                                                                                    |
| Slider slide editing                                                                                                                                                  | **OK** (T018)  | Was a raw JSON textarea. Now a repeater (`cg_render_repeater()` + `assets/js/admin-repeater.js`): add/remove/reorder, `wp.media` image picker, one hidden JSON input the existing save path already handles                                                                                                                                                                        |
| Slider effects (animation, slideshow, controlNav, randomize)                                                                                                          | **OK** (T018)  | Source exposed these per slider (`aletheme/sliders/sliders.php:436-470`); the port hard-coded `animation:"fade", controlNav:false` in the vendored `InitHome.js`. Now per-slider meta, applied by `assets/js/slider-init.js`, which wraps `$.fn.flexslider` — the bundled FlexSlider has no `destroy` and re-init is a no-op, so the settings cannot be changed after construction |
| Slide custom HTML                                                                                                                                                     | **OK** (T018)  | Source `post_excerpt`, rendered raw. Ours is filtered with `wp_kses_post` on save and again on output                                                                                                                                                                                                                                                                              |
| `colscheme` option                                                                                                                                                    | **OK** (T018)  | Source `ale_colscheme`. The six scheme stylesheets were vendored and the runtime selector could swap them, but no default was declared, so `scheme1.css` was never in `<head>` and `.colormain` had no colour behind it. Now declared and enqueued                                                                                                                                 |
| `customcake1`                                                                                                                                                         | **OK** (T018)  | Source applied it via `css-option.php`, which was never ported. Applied inline on `.line-cake .cake` instead, so it does not wait on T016                                                                                                                                                                                                                                          |
| `customcake2`                                                                                                                                                         | **N/A** (T018) | Targeted `.story-open .right .content .line-cake .cake`. `single.php` renders `.story-open` but no `.line-cake`, so the selector has no equivalent here. Declaring it would ship a control that does nothing                                                                                                                                                                       |
| `mobsitelogo`, `customcsscode`, `mainfooter`, `comments_style`, `footer_info`, `emailcont`, `favicon`, `ga`, `analyticstype`, `og_enabled`, `fb_id`, `social_sharing` | **absent**     | Real gaps, deliberately not closed: content/SEO features rather than effects. Not tracked by a ticket yet                                                                                                                                                                                                                                                                          |
| 6 typography options (`mainfont`, `headerfont`, `thirdfont` + `*fontex`)                                                                                              | **absent**     | `css-option.php` was never ported. That is T016, still pending                                                                                                                                                                                                                                                                                                                     |

## 3. Image sizes to register (from `aletheme/config.php:1500-1557`)

| Size             | W×H     | Crop | Source                        |
| ---------------- | ------- | ---- | ----------------------------- |
| `gallery-thumba` | 212×162 | yes  | gallery archive / home filter |
| `gallery-slider` | 985×410 | yes  | gallery slider                |
| `post-thumba`    | 475×295 | yes  | post archives                 |
| `post-slider`    | 658×296 | yes  | post slider                   |
| `events-thumba`  | 452×130 | yes  | events archive                |
| `events-slider`  | 657×296 | yes  | events slider                 |
| `menu-thumba`    | 239×141 | yes  | menu archive                  |
| `menu-slider`    | 985×410 | yes  | menu slider                   |

(8 named sizes × the framework's per-post-type registration = the 12 in §0.)

## 4. Shortcode parity (30 in source, 0 in target)

Source registers these in `aletheme/shortcodes/shortcodes.php`. The target defines none.

**Content** — `ale_service` · `ale_team` · `ale_testimonial` · `ale_partner` · `ale_toggle` · `ale_map`
**Chrome** — `ale_alert` · `ale_button` · `ale_divider`
**Layout** (12) — `ale_one_third`(+`_last`) · `ale_two_thirds`(+`_last`) · `ale_one_half`(+`_last`) ·
`ale_one_fourth`(+`_last`) · `ale_three_fourths`(+`_last`) · `ale_one_fifth`(+`_last`) ·
`ale_two_fifths`(+`_last`) · `ale_three_fifths`(+`_last`) · `ale_four_fifths`(+`_last`) ·
`ale_one_sixth`(+`_last`) · `ale_five_sixths`(+`_last`)

The 12 layout shortcodes duplicate native WP `core/columns` + `core/column`. The 6 content ones have
no native equivalent. **This is the pivotal port decision — see the plan, not this file.**

## 5. Claims audit

`style.css:4` and `CLAUDE.md` assert parity for: slider · services · gallery filter Isotope · team ·
menu/price · events · dual-header menus · mobile drawer · contact form · Google Maps · social icons.

| Claim                        | Reality                                                                                                                                |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| slider                       | renders an empty `<ul class="slides">` — verified live; `cg_slider` CPT does not exist                                                 |
| services                     | `ale_get_meta('serviceonhome')` is `''` (no metabox writes it) → the `else` branch always runs → empty spacer                          |
| gallery filter Isotope       | queries non-existent `gallery` CPT and `gallery-category` taxonomy → always empty; `InitOpenGallery.js` also orphaned (never enqueued) |
| team / testimonial / partner | not ported at all                                                                                                                      |
| menu/price                   | CPT exists (`cg_menu`), no template, no price rendering                                                                                |
| events                       | CPT exists (`cg_event`), no taxonomy, no image size                                                                                    |
| dual-header menus            | 5 menu locations registered; `header.php` must be checked separately                                                                   |
| mobile drawer                | `assets/js/nav.js` exists; unverified                                                                                                  |
| contact form                 | **HTTP 500 on POST — verified live.** `ale_send_contact()` undefined, no template                                                      |
| Google Maps                  | `ale_map` shortcode not ported                                                                                                         |
| social icons                 | not ported                                                                                                                             |

**13 claimed features: 0 fully working. Verified live: the home page renders hollow; the contact form returns HTTP 500.**

## 6. Not counted as gaps (deliberate)

`404.php` · `archive.php` · `author.php` · `category.php` · `tag.php` · `search.php` ·
`searchform.php` · `comments.php` · `attachment.php` are supplied by WordPress core fallbacks.
Their absence in the target is correct, not a defect. Listed here so nobody "fixes" them later.

Also deliberately **not** a parity target: the ALETheme admin framework itself —
`aletheme/options/*` (Theme Options panel, 1,747 LOC), `functions/import_export.php` (555 LOC),
`shortcodes/tinymce/*` (1,256 LOC), `options/admin/*`, metaboxes (568 LOC). That is a plugin's job,
not a theme's. Native equivalents are `theme.json` / Customizer / block patterns.

## 7. How to keep this file honest

Every port ticket must state which matrix rows it closes. `make check` does not verify this matrix
automatically — that gap is tracked as T003's follow-up in `aes/tickets/`.
