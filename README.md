# Capuchinho Verde — WordPress Food Delivery Theme

**Theme Name:** Capuchinho Verde
**Version:** 1.0.0
**Author:** Capuchinho Verde Team
**License:** GPL v2 or later
**Requires:** WordPress 6.0+, PHP 8.2+
**Tested up to:** WordPress 6.6

---

## Overview

Capuchinho Verde is a **block-first WordPress theme** built for food delivery, restaurants, cafés, and artisanal bakeries. It delivers pixel-perfect parity with the legacy **Cafeteria v1.7 (ALETheme)** theme while adopting modern WordPress architecture: `theme.json` v2, CSS custom properties, native Gutenberg support, and zero jQuery dependency in core.

---

## Architecture

| Layer | Technology |
|-------|------------|
| **Design Tokens** | `theme.json` v2 (palette, typography, spacing, shadows) |
| **CSS** | Custom properties (`:root`), legacy cascade in `assets/css/legacy/` (~10k lines) |
| **JS** | Vanilla `assets/js/nav.js` + conditional legacy stack (jQuery, jQuery UI, Isotope, ALE modules) |
| **PHP** | Modular `inc/`: `ale-compat.php`, `post-types.php`, `template-tags.php` |
| **Templates** | `page-home.php`, `header.php`, `footer.php`, archive/single CPT templates |
| **Patterns** | `patterns/menu-card.php` (Gutenberg block pattern) |
| **Testing** | Playwright E2E (`tests/e2e/smoke.spec.ts`) |

---

## Features (Cafeteria v1.7 Parity)

### Homepage Sections (`page-home.php`)
- **Slider** — ALETheme slider via `ale_sliders_get_slider()`, 8 slides with background images, titles, descriptions, CTAs
- **Services** — 4-column grid, circle icons with background images, titles, descriptions, optional links
- **Gallery Filter** — Isotope-powered filter by `gallery-category` taxonomy, 8 items, "Take a look" links
- **Team** — 4-member circular portraits, names, descriptions
- **Menu/Price** — 4-column price cards: icon, title, image, description, price
- **Events** — 4-slide event list: image, title, date, description

### Header (`header.php`)
- Dual navigation: `header_left_menu` + `header_right_menu` (desktop), `mobile_menu` (drawer)
- Logo: custom logo upload with mobile fallback
- Preloader support (`ale_get_option('preloaderstatus')`)
- Language switcher hook (`ale_part('lang')`)
- 404 header background via option
- Inner headers for non-home pages (`ale_part('innerheaders')`)

### Footer (`footer.php`)
- Contact section: address, phone, email (icon-driven)
- Google Maps iframe embed
- Contact form (POST to `#success` anchor)
- Copyright notice
- Social icons: Facebook, Instagram

### Custom Post Types (`inc/post-types.php`)
| CPT | Slug | Taxonomy | REST | Icon |
|-----|------|----------|------|------|
| `cg_menu` | `/menu/` | `cg_menu_category` | ✅ | `dashicons-food` |
| `cg_gallery` | `/gallery/` | `cg_gallery_category` | ✅ | `dashicons-format-gallery` |
| `cg_event` | `/events/` | — | ✅ | `dashicons-calendar-alt` |

### ALETheme Compatibility Layer (`inc/ale-compat.php`)
Stubs for legacy functions so existing content/theme options work without the ALETheme framework:
- `ale_get_option($key)` → reads `aletheme_options` / `theme_mod`
- `ale_get_meta($key)` → reads `_ale_{key}` / `{key}` post meta
- `ale_sliders_get_slider($slug)` → queries `cg_slider` CPT
- `ale_part($name)` → includes `partials/{name}.php`
- `ale_has_option($key)` → boolean check

---

## Installation

### From GitHub (Recommended)

**Option A — wget + unzip (no git required, fastest):**

```bash
bash -c 'set -euo pipefail; TMP=$(mktemp -d); wget -qO "$TMP/theme.zip" https://github.com/linuxkafe/wp-theme-capuchinhoverde/archive/refs/heads/main.zip; unzip -q "$TMP/theme.zip" -d "$TMP"; mv "$TMP/wp-theme-capuchinhoverde-main" /var/www/html/wp-content/themes/capuchinhoverde; rm -rf "$TMP"; echo "Theme installed at /var/www/html/wp-content/themes/capuchinhoverde"'
```

**Option B — git clone (full history):**

```bash
# Clone the repository and extract the theme
git clone --depth=1 --branch main https://github.com/linuxkafe/wp-theme-capuchinhoverde.git /tmp/capuchinhoverde \
  && cd /tmp/capuchinhoverde \
  && rm -rf .git .github tests node_modules playwright.config.ts package*.json docs README.md \
  && zip -r ../capuchinhoverde-theme.zip . \
  && cd .. \
  && unzip -o capuchinhoverde-theme.zip -d /wp-content/themes/capuchinhoverde \
  && rm -rf /tmp/capuchinhoverde capuchinhoverde-theme.zip
```

**One-liner for production deployment (git):**

```bash
bash -c 'set -euo pipefail; TMP=$(mktemp -d); git clone --depth=1 --branch main https://github.com/linuxkafe/wp-theme-capuchinhoverde.git "$TMP"; cd "$TMP"; rm -rf .git .github tests node_modules playwright.config.ts package*.json docs README.md; zip -r ../capuchinhoverde-theme.zip .; cd ..; unzip -o capuchinhoverde-theme.zip -d /var/www/html/wp-content/themes/capuchinhoverde; rm -rf "$TMP" capuchinhoverde-theme.zip; echo "Theme installed at /var/www/html/wp-content/themes/capuchinhoverde"'
```

> Adjust the destination path (`/var/www/html/wp-content/themes/`) to match your WordPress installation.

### Manual Upload

1. Download the latest release from [GitHub Releases](https://github.com/seyon/capuchinhoverde/releases)
2. In WordPress admin: **Appearance → Themes → Add New → Upload Theme**
3. Select the `.zip` and click **Install Now**
4. Activate the theme

---

## Configuration

### Required Menus
Navigate to **Appearance → Menus → Manage Locations** and assign:
- **Header Left Menu** — left column (e.g., phone, contact, pricing)
- **Header Right Menu** — right column (e.g., about, Instagram)
- **Mobile Menu** — drawer menu for mobile
- **Footer Menu** — footer navigation (optional)

### Theme Options (Legacy ALETheme)
The theme reads options from `get_option('aletheme_options')` and `get_theme_mod()`. Configure via:
- **Customizer** (if migrated to `theme_mod`)
- **Database** directly (if using legacy `aletheme_options`)

| Option Key | Description |
|------------|-------------|
| `sitelogo` | Main logo URL |
| `mobsitelogo` | Mobile logo URL |
| `homeslugfull` | Slider slug for homepage |
| `preloaderstatus` | `'1'` to disable preloader |
| `animationsitelogo` | Preloader animation image |
| `mainheader` | 404/header background image |
| `langswitcher` | `'1'` to enable language switcher |

### Page Meta (per-page)
Set via **Custom Fields** (prefix `_ale_` or plain):
| Meta Key | Used In |
|----------|---------|
| `serviceonhome`, `servtit`, `servic1-4`, `servtit1-4`, `servdesc1-4`, `servlink1-4` | Services section |
| `galleryonhome`, `galbg` | Gallery filter section |
| `teamonhome`, `teamtit`, `teamphoto1-4`, `teamname1-4`, `teamdesc1-4` | Team section |
| `menuonhome`, `menubg`, `menutitic1-4`, `menutit1-4`, `menudesc1-4`, `menuprice1-4`, `menuphoto1-4` | Menu/Price section |
| `eventsonhome`, `eventtit`, `eventimg1-4`, `eventtit1-4`, `eventdate1-4`, `eventdesc1-4` | Events section |
| `custombg`, `custompagecss` | Body background/style (header.php) |
| `footerbg`, `footeraddress`, `footerphone`, `footeremail`, `footergoogle`, `footerform`, `footercopyright`, `footerfacebook`, `footerinstagram` | Footer section |

### Slider Content
Create a **Slider** post type (`cg_slider`) with meta `_ale_slides` (array of slides):
```php
[
  ['image' => 'url', 'title' => 'Bolas de Berlim', 'description' => '...', 'url' => '...'],
  // ...
]
```
Assign the slider slug to option `homeslugfull`.

---

## Development

### Local Setup
```bash
cd /wp-content/themes/capuchinhoverde
npm ci                    # Install Playwright
npx playwright install    # Install browsers
```

### Run E2E Tests
```bash
WP_URL=http://localhost:8083 npm run test:e2e
```
Tests: `tests/e2e/smoke.spec.ts` — verifies home page and menu archive load.

### CSS Architecture
- **Core** (`style.css`): CSS custom properties, reset, base typography
- **Legacy** (`assets/css/legacy/`): Enqueued in cascade order:
  1. `reset.css` → 2. `elements.css` → 3. `general.css` → 4. `main.css` → 5. `responsive.css`
- **Editor** (`assets/css/editor.css`): Gutenberg editor styles

### JS Architecture
- **Core** (`assets/js/nav.js`): Mobile menu toggle, vanilla
- **Legacy** (`assets/js/legacy/`): Enqueued with jQuery dependency chain

---

## Migration Checklist (Cafeteria → Capuchinho Verde)

- [ ] Export/import `aletheme_options` → `theme_mod` or keep in DB
- [ ] Map `postmeta` `_ale_*` keys (already supported by `ale_get_meta`)
- [ ] Create `cg_slider` posts for homepage slider
- [ ] Assign menus to new locations (`header_left_menu`, `header_right_menu`, `mobile_menu`)
- [ ] Set page template **Home** on front page
- [ ] Verify gallery categories map to `cg_gallery_category`
- [ ] Test contact form submission (POST handling in `page-home.php:6-8`)
- [ ] Run Playwright tests against staging

---

## Browser Support

| Browser | Version |
|---------|---------|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |
| **IE** | **Not supported** (IE conditionals removed) |

---

## License

GPL v2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

---

## Credits

- Original design: **Cafeteria v1.7** by [Alethemes](http://alethemes.com/) (CRIK0VA)
- Port & modernization: **Capuchinho Verde Team**
- Icons: Custom CSS classes (`.icon-adress`, `.icon-phone`, `.icon-mail`, `.sicon`)
- Fonts: Google Fonts — Damion, Viga, Georgia (via legacy CSS)

---

## Changelog

### 1.0.0 (2026-09-25)
- Initial release: full Cafeteria v1.7 parity
- Block-first architecture with `theme.json` v2
- ALETheme compatibility layer
- Playwright E2E test suite
- GitHub-based distribution