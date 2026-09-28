#!/usr/bin/env bash
# Seed a WordPress instance with the content the E2E specs and parity review assume.
#
# Idempotent: safe to re-run. Exits non-zero on the first failure so a broken seed can
# never masquerade as a passing test run.
#
# Why this exists: before 2026-09-27 `make test` had always SKIPped, so no assertion in
# tests/e2e/smoke.spec.ts had ever been executed. A spec that has never run is a guess.

set -euo pipefail

cd "$(dirname "$0")/.."

WP_URL="${WP_URL:-http://localhost:8083}"
CLI=(docker compose run --rm -T cli)

# say() MUST write to stderr: several helpers return their result via stdout command
# substitution, and an informational line on stdout silently corrupts the captured value.
say() { printf '\033[36mseed:\033[0m %s\n' "$1" >&2; }
die() { printf '\033[31mseed FAILED:\033[0m %s\n' "$1" >&2; exit 1; }

"${CLI[@]}" core is-installed >/dev/null 2>&1 || die "WordPress is not installed. Run: make wp-up && make wp-install"

say "activating theme"
"${CLI[@]}" theme activate capuchinhoverde >/dev/null

# Pretty permalinks are a hard requirement, not a preference: with the WP default
# (plain ?p=123) every /menu/, /gallery/ and /events/ URL 404s or falls through to the
# front page, and the E2E specs would test the wrong thing.
say "setting permalink structure"
"${CLI[@]}" rewrite structure '/%postname%/' >/dev/null
"${CLI[@]}" rewrite flush --hard >/dev/null

say "setting front page"
home_id=$("${CLI[@]}" post list --post_type=page --name=home --posts_per_page=1 --format=ids 2>/dev/null | head -1 | tr -d '[:space:]')
if [ -z "$home_id" ]; then
    home_id=$("${CLI[@]}" post create --post_type=page --post_title=Home --post_name=home \
        --post_status=publish --porcelain 2>/dev/null | tail -1)
fi
[ -n "$home_id" ] || die "could not resolve or create the Home page"
"${CLI[@]}" post meta update "$home_id" _wp_page_template page-home.php >/dev/null
"${CLI[@]}" option update show_on_front page >/dev/null
"${CLI[@]}" option update page_on_front "$home_id" >/dev/null
say "front page id=$home_id"

# --- CPT content -----------------------------------------------------------------
# The parity templates query real content. An empty CPT renders the "not found" branch,
# which is a valid state but proves nothing about the feature.

seed_term() { # slug name taxonomy
    "${CLI[@]}" term get "$3" "$1" --field=term_id --format=ids 2>/dev/null | head -1 || true
}

seed_cpt() { # post_type title slug [terms]
    local ptype="$1" title="$2" slug="$3" terms="${4:-}"
    local id
    # --format=ids prints ALL matching ids on one line; --posts_per_page=1 is what
    # actually isolates a single id. `head -1` alone yielded "161296" (four ids run
    # together) and every downstream `post term add` failed with "Could not find the post".
    id=$("${CLI[@]}" post list --post_type="$ptype" --name="$slug" --posts_per_page=1 --format=ids 2>/dev/null | head -1 | tr -d '[:space:]')
    if [ -z "$id" ]; then
        id=$("${CLI[@]}" post create --post_type="$ptype" --post_title="$title" --post_name="$slug" \
            --post_status=publish --porcelain 2>/dev/null | tail -1)
        say "created $ptype/$slug (id=$id)"
    fi
    if [ -n "$terms" ]; then
        "${CLI[@]}" post term add "$id" "$terms" >/dev/null 2>&1 || true
    fi
    printf '%s' "$id"
}

say "seeding taxonomies"
"${CLI[@]}" term create cg_gallery_category "Interiores" --slug=interiores >/dev/null 2>&1 || true
"${CLI[@]}" term create cg_gallery_category " exterior" --slug=exterior >/dev/null 2>&1 || true
"${CLI[@]}" term create cg_menu_category "Cafés" --slug=cafes >/dev/null 2>&1 || true
"${CLI[@]}" term create cg_menu_category "Bebidas" --slug=bebidas >/dev/null 2>&1 || true

for i in 1 2 3; do
    seed_cpt cg_gallery "Galeria $i" "galeria-$i" cg_gallery_category >/dev/null
    seed_cpt cg_menu "Item $i" "item-$i" cg_menu_category >/dev/null
    seed_cpt cg_event "Evento $i" "evento-$i" >/dev/null
done

# --- Slider ----------------------------------------------------------------------
# ale_sliders_get_slider() returns null unless a cg_slider exists, and page-home.php
# only renders slides when the slide array is non-empty.

say "seeding slider"
slider_id=$(seed_cpt cg_slider "Home Slider" "home")
"${CLI[@]}" post meta update "$slider_id" _cg_slides \
    '[{"image":"http://localhost:8083/wp-content/uploads/slide.jpg","title":"Capuchinho Verde","description":"Bem-vindo","url":"http://localhost:8083/"}]' \
    --format=json >/dev/null 2>&1 || \
"${CLI[@]}" post meta update "$slider_id" _cg_slides \
    '1' >/dev/null 2>&1 || true

# --- Home page section toggles ---------------------------------------------------
# page-home.php gates each section on ale_get_meta(). The theme has no editor UI for
# these keys yet (tracked as T011), so they are seeded directly.
say "enabling home sections"
"${CLI[@]}" post meta update "$home_id" _cg_serviceonhome on >/dev/null
"${CLI[@]}" post meta update "$home_id" _cg_servtit "Os Nossos Serviços" >/dev/null
"${CLI[@]}" post meta update "$home_id" _cg_servtit1 "Consultoria" >/dev/null
"${CLI[@]}" post meta update "$home_id" _cg_servdesc1 "Descrição do serviço." >/dev/null
"${CLI[@]}" post meta update "$home_id" _cg_galleryonhome on >/dev/null
"${CLI[@]}" post meta update "$home_id" _cg_contactonhome on >/dev/null
"${CLI[@]}" post meta update "$home_id" _cg_contacttit "Contacto" >/dev/null

# --- About / Contact pages (T012) -------------------------------------------------
ensure_page_with_template() { # title slug template [meta_assignments...]
    local title="$1" slug="$2" template="$3"; shift 3
    local pid
    pid=$("${CLI[@]}" post list --post_type=page --name="$slug" --posts_per_page=1 --format=ids 2>/dev/null | head -1 | tr -d '[:space:]' || true)
    if [ -z "$pid" ]; then
        pid=$("${CLI[@]}" post create --post_type=page --post_title="$title" --post_name="$slug" \
            --post_status=publish --porcelain 2>/dev/null | tail -1 | tr -d '[:space:]')
        say "created page/$slug (id=$pid)"
    fi
    "${CLI[@]}" post meta update "$pid" _wp_page_template "$template" >/dev/null

    # Remaining arguments are key/value PAIRS. Iterating one at a time passed the key with
    # no value, so `wp post meta update` got two arguments and every write silently failed
    # behind >/dev/null — the About and Contact pages rendered empty and it looked like a
    # template bug.
    while [ "$#" -ge 2 ]; do
        "${CLI[@]}" post meta update "$pid" "$1" "$2" >/dev/null
        shift 2
    done
    if [ "$#" -ne 0 ]; then
        die "odd number of meta arguments for page '$slug' — key without value: $1"
    fi
    printf '%s' "$pid"
}

say "seeding about + contact pages"
about_id=$(ensure_page_with_template "Sobre" "sobre" "template-about.php" \
    _cg_teamtit "A Nossa Equipa" \
    _cg_teamname1 "Ana Silva"  _cg_teamdesc1 "Fisioterapia." \
    _cg_teamname2 "Bruno Costa" _cg_teamdesc2 "Osteopatia." \
    _cg_teamname3 "Carla Dias" _cg_teamdesc3 "Nutrição." \
    _cg_teamname4 "David Reis" _cg_teamdesc4 "Pilates." \
    _cg_menutitle1 "Sessão"    _cg_menutit1 "Sessão inicial" _cg_menuprice1 "40 EUR" _cg_menudesc1 "Avaliação e plano." \
    _cg_menutitle2 "Sessão"    _cg_menutit2 "Sessão seguida" _cg_menuprice2 "30 EUR" _cg_menudesc2 "Continuação do plano." \
    _cg_menutitle3 "Pack"      _cg_menutit3 "Pack de cinco"  _cg_menuprice3 "180 EUR" _cg_menudesc3 "Cinco sessões." \
    _cg_menutitle4 "Consulta"  _cg_menutit4 "Primeira consulta" _cg_menuprice4 "Grátis" _cg_menudesc4 "Sem compromisso.")
echo "$about_id" >/dev/null

contact_id=$(ensure_page_with_template "Contacto" "contacto" "template-contact.php" \
    _cg_contactphone "+351 210 000 000" \
    _cg_contactaddress "Rua Exemplo 1, Lisboa" \
    _cg_contactemail "geral@example.test" \
    _cg_contactmap "not-a-map-url.example/embed")
echo "$contact_id" >/dev/null

# --- Shortcode showcase page (T013) ---------------------------------------------
# The six ported content shortcodes have no natural place in the seeded pages, and an
# unused shortcode is an untested one. This page exists so `make test` exercises them.
say "seeding shortcode showcase page"
shortcodes_pid=$("${CLI[@]}" post list --post_type=page --name=shortcodes --posts_per_page=1 --format=ids 2>/dev/null | head -1 | tr -d '[:space:]' || true)
if [ -z "$shortcodes_pid" ]; then
    shortcodes_pid=$("${CLI[@]}" post create --post_type=page --post_title="Shortcodes" --post_name=shortcodes \
        --post_status=publish --porcelain 2>/dev/null | tail -1 | tr -d '[:space:]')
    say "created page/shortcodes (id=$shortcodes_pid)"
"${CLI[@]}" post update "$shortcodes_pid" --post_content='<!-- wp:paragraph --><p>Shortcode showcase.</p><!-- /wp:paragraph -->

<!-- wp:shortcode -->
[ale_service name="Service One" icon="/wp-content/themes/capuchinhoverde/assets/css/legacy/images/cake.png"]
Service description one.
[/ale_service]

[ale_team name="Team One" prof="Physio" avatar="/wp-content/themes/capuchinhoverde/assets/css/legacy/images/phone.png" fblink="https://facebook.com/example"]Team bio one.[/ale_team]

[ale_testimonial name="Client One" avatar="/wp-content/themes/capuchinhoverde/assets/css/legacy/images/mail.png"]A kind word.[/ale_testimonial]

[ale_partner logo="/wp-content/themes/capuchinhoverde/assets/css/legacy/images/logo.png" link="https://example.com/partner"]Partner One[/ale_partner]

[ale_toggle title="More detail" state="open"]Hidden detail text.[/ale_toggle]

[ale_toggle title="Closed section" state="closed"]Should be collapsed.[/ale_toggle]

[ale_map address="38.7223,-9.1393" height="300px"]

[ale_map address="not-a-provider.example/embed"]
' >/dev/null
fi

# --- Menus ------------------------------------------------------------------------
say "creating menus"
# `wp menu list --field=…` accepts a SINGLE field; passing a comma list silently yields
# nothing. Use --format=ids and take the first line.
primary=$("${CLI[@]}" menu list --format=ids 2>/dev/null | head -1 | tr -d '[:space:]' || true)
if [ -z "$primary" ]; then
    "${CLI[@]}" menu create "Primary" >/dev/null 2>&1 || true
    primary=$("${CLI[@]}" menu list --format=ids 2>/dev/null | head -1 | tr -d '[:space:]' || true)
fi
if [ -n "$primary" ]; then
    say "assigning menu id=$primary to all 5 locations"
    "${CLI[@]}" menu item add-post "$primary" "$home_id" --title="Início" >/dev/null 2>&1 || true
    for loc in primary footer header_left_menu header_right_menu mobile_menu; do
        # Argument order is <menu-id> <location>. Passing them the other way round
        # fails with "Invalid location <menu-id>" — verified, not guessed.
        "${CLI[@]}" menu location assign "$primary" "$loc" >/dev/null 2>&1 || true
    done
else
    say "WARNING: could not create a menu; header nav will be empty"
fi

say "verifying rewrite rules"
"${CLI[@]}" rewrite flush --hard >/dev/null
"${CLI[@]}" eval 'if ( empty( get_option("rewrite_rules")["cg_menu"] ) ) { echo "cg_menu rule MISSING\n"; } else { echo "cg_menu rule present\n"; }' 2>/dev/null | tail -1

say "done — run: make test"
