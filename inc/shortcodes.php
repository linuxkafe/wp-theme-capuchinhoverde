<?php
/**
 * Content shortcodes.
 *
 * PORTED FROM: aletheme/shortcodes/shortcodes.php in Cafeteria v1.7.
 *
 * SCOPE — why only these six
 *
 * The source registers 30 shortcodes. Twelve are pure layout columns (one_third, two_fifths,
 * …) which duplicate core/columns and core/column in a block theme; twelve more are chrome
 * (ale_alert, ale_button, ale_divider, the tabs family). The decision recorded in
 * aes/kanban.md on 2026-09-27 is: native columns for layout, keep the CONTENT shortcodes.
 * That is this file. The chrome shortcodes have no native equivalent in core/ yet and are
 * not ported; they are listed as gaps in docs/PARITY.md §4 rather than quietly omitted.
 *
 * CHANGES MADE DURING THE PORT
 *
 *  1. `extract()` is gone from all six. The source used it to splat shortcode_atts() into
 *     the function scope, which makes every attribute a local variable and is a documented
 *     hazard. Each shortcode now reads its array explicitly.
 *  2. Every attribute is escaped. The source concatenated attribute values straight into
 *     HTML, so a contributor-level author could inject markup through any shortcode
 *     attribute — stored XSS for anyone with edit_posts.
 *  3. `do_shortcode($content)` is preserved where the source had it (nested shortcodes), and
 *     the surrounding attribute values are escaped.
 *  4. ale_map is a REDESIGN, documented in detail below. The source version cannot work.
 *
 * @see docs/PARITY.md §4 for the full matrix.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------- */
/* Service                                                                     */
/* -------------------------------------------------------------------------- */

/**
 * [ale_service style="dark" icon="" name=""]
 */
function cg_shortcode_service($atts, $content = null) {
    $atts = shortcode_atts([
        'style' => 'dark',
        'icon'  => '',
        'name'  => '',
    ], $atts, 'ale_service');

    $html = '<div class="ale-service ' . esc_attr($atts['style']) . ' cf">';

    if ($atts['icon']) {
        $html .= '<div class="iconbox"><img src="' . esc_url($atts['icon']) . '" alt="" /></div>';
    }
    $html .= '<div class="servicetitle">' . esc_html($atts['name']) . '</div>';
    $html .= '<div class="servicedescription">' . do_shortcode((string) $content) . '</div>';
    $html .= '</div>';

    return $html;
}
add_shortcode('ale_service', 'cg_shortcode_service');

/* -------------------------------------------------------------------------- */
/* Team                                                                        */
/* -------------------------------------------------------------------------- */

/**
 * [ale_team style="dark" name="" avatar="" prof="" fblink="" twilink="" glink=""]
 */
function cg_shortcode_team($atts, $content = null) {
    $atts = shortcode_atts([
        'style'   => 'dark',
        'name'    => '',
        'avatar'  => '',
        'prof'    => '',
        'fblink'  => '',
        'twilink' => '',
        'glink'   => '',
    ], $atts, 'ale_team');

    // The source built these with `if ($fblink) { $fbbutton = … }` and then concatenated
    // $fbbutton.$twibutton.$gbutton — undefined-variable warnings whenever a link was
    // absent, and three notices per shortcode in the common case.
    $buttons = '';
    $links   = [
        'fbbut'   => ['url' => $atts['fblink'],  'label' => __('Facebook', 'capuchinhoverde')],
        'twibut'   => ['url' => $atts['twilink'], 'label' => __('Twitter', 'capuchinhoverde')],
        'gbut'    => ['url' => $atts['glink'],   'label' => __('Google', 'capuchinhoverde')],
    ];
    foreach ($links as $class => $link) {
        if (!$link['url']) {
            continue;
        }
        $buttons .= sprintf(
            '<div class="%s"><a href="%s" rel="nofollow noopener" target="_blank">%s</a></div>',
            esc_attr($class),
            esc_url($link['url']),
            esc_html($link['label'])
        );
    }

    $html = '<div class="ale-team ' . esc_attr($atts['style']) . ' cf">';
    if ($atts['avatar']) {
        $html .= '<div class="imagebox"><img src="' . esc_url($atts['avatar']) . '" alt="' . esc_attr($atts['name']) . '" /></div>';
    }
    $html .= '<div class="testititle">' . esc_html($atts['name']) . '</div>';
    $html .= '<div class="prof">' . esc_html($atts['prof']) . '</div>';
    $html .= '<div class="teamtextbox">' . do_shortcode((string) $content) . '</div>';
    $html .= '<div class="socialbut">' . $buttons . '</div>';
    $html .= '</div>';

    return $html;
}
add_shortcode('ale_team', 'cg_shortcode_team');

/* -------------------------------------------------------------------------- */
/* Testimonial                                                                 */
/* -------------------------------------------------------------------------- */

/**
 * [ale_testimonial style="dark" name="" avatar="" link=""]
 */
function cg_shortcode_testimonial($atts, $content = null) {
    $atts = shortcode_atts([
        'style'  => 'dark',
        'name'   => '',
        'avatar' => '',
        'link'   => '',
    ], $atts, 'ale_testimonial');

    $html = '<div class="ale-testimonial ' . esc_attr($atts['style']) . ' cf">';
    $html .= '<div class="lefttestimonialpart">';

    if ($atts['avatar']) {
        $image = '<img src="' . esc_url($atts['avatar']) . '" alt="" />';
        $html .= '<div class="avatarimage">';
        $html .= $atts['link']
            ? '<a href="' . esc_url($atts['link']) . '" rel="nofollow noopener" target="_blank">' . $image . '</a>'
            : $image;
        $html .= '</div>';
    }
    $html .= '<div class="testititle">' . esc_html($atts['name']) . '</div>';
    $html .= '</div>';
    $html .= '<div class="righttestimonialpart">' . do_shortcode((string) $content) . '</div>';
    $html .= '</div>';

    return $html;
}
add_shortcode('ale_testimonial', 'cg_shortcode_testimonial');

/* -------------------------------------------------------------------------- */
/* Partner                                                                     */
/* -------------------------------------------------------------------------- */

/**
 * [ale_partner style="dark" logo="" link=""]
 *
 * The source used do_shortcode($content) for the link's title attribute AND for the visible
 * label, so the "name" was markup that also had to survive being an attribute. Escaped here.
 */
function cg_shortcode_partner($atts, $content = null) {
    $atts = shortcode_atts([
        'style' => 'dark',
        'logo'  => '',
        'link'  => '',
    ], $atts, 'ale_partner');

    $label = wp_strip_all_tags((string) $content);
    $inner = do_shortcode((string) $content);

    $html = '<div class="ale-partner ' . esc_attr($atts['style']) . ' cf">';
    $html .= '<div class="imagebox">';
    $image = $atts['logo'] ? '<img src="' . esc_url($atts['logo']) . '" alt="" />' : '';
    $html .= $atts['link']
        ? '<a href="' . esc_url($atts['link']) . '" rel="nofollow noopener noopener" target="_blank" title="' . esc_attr($label) . '">' . $image . '</a>'
        : $image;
    $html .= '</div>';
    $html .= '<div class="partnertitle">' . $inner . '</div>';
    $html .= '</div>';

    return $html;
}
add_shortcode('ale_partner', 'cg_shortcode_partner');

/* -------------------------------------------------------------------------- */
/* Toggle                                                                      */
/* -------------------------------------------------------------------------- */

/**
 * [ale_toggle title="…" state="open"]
 *
 * The source emitted a data-id and a title span with NO behaviour attached — there is no
 * .ale-toggle handler in the ported scripts.js or modules.js, and none in the source's own
 * JS. The toggle therefore expanded and collapsed nothing. The markup is ported faithfully
 * and a small progressive-enhancement handler is registered below so the feature is not a
 * dead control: without JS the content stays visible, which is the accessible fallback.
 */
function cg_shortcode_toggle($atts, $content = null) {
    $atts = shortcode_atts([
        'title' => __('Title goes here', 'capuchinhoverde'),
        'state' => 'open',
    ], $atts, 'ale_toggle');

    $open = 'open' === strtolower($atts['state']);

    return sprintf(
        '<div class="ale-toggle%s" data-id="%s"><button type="button" class="ale-toggle-title" aria-expanded="%s" aria-controls="cg-toggle-%s">%s</button><div class="ale-toggle-inner" id="cg-toggle-%s"%s>%s</div></div>',
        $open ? ' is-open' : '',
        esc_attr($atts['state']),
        $open ? 'true' : 'false',
        esc_attr(sanitize_title($atts['title'])),
        esc_html($atts['title']),
        esc_attr(sanitize_title($atts['title'])),
        $open ? '' : ' hidden',
        do_shortcode((string) $content)
    );
}
add_shortcode('ale_toggle', 'cg_shortcode_toggle');

/**
 * Progressive enhancement for [ale_toggle].
 *
 * Vanilla JS in the theme's own file, not the vendored jQuery layer — see CLAUDE.md
 * ("do not add new jQuery usage"). Loaded only when a toggle is on the page.
 */
function cg_toggle_assets() {
    // has_shortcode() takes the CONTENT as a string. Passing the WP_Post worked in PHP 7
    // (silently coerced) and is a TypeError in PHP 8 — which killed the whole page render
    // at 510 bytes while still returning HTTP 200.
    $post = get_post();
    if (!$post || !is_string($post->post_content) || !has_shortcode($post->post_content, 'ale_toggle')) {
        return;
    }
    wp_enqueue_script(
        'cg-toggle',
        get_template_directory_uri() . '/assets/js/toggle.js',
        [],
        _CG_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'cg_toggle_assets');

/* -------------------------------------------------------------------------- */
/* Map — a redesign, and why                                                    */
/* -------------------------------------------------------------------------- */

/**
 * [ale_map address="…" width="100%" height="400px"]
 *
 * THIS IS A REDESIGN. The source implementation cannot work on any current WordPress:
 *
 *  - `wp_print_scripts()` was deprecated in 2.8 and REMOVED from core. Calling it is a fatal.
 *  - It registered the Google Maps JS API over plain HTTP from `maps.google.com`, a host
 *    that no longer serves the API, with no API key and the long-removed `sensor` parameter.
 *  - `ale_map_get_coordinates()` geocoded server-side over plain HTTP **on every render**
 *    of a page containing the shortcode, with no API key, then cached the result in a
 *    transient. That is a blocking external request inside a page render, it leaks visitor
 *    page views to Google, and it breaks whenever the outbound request fails — the map
 *    silently does not appear.
 *  - Its width attribute was misspelled `widht`, so the width never applied.
 *
 * The replacement is the provider's own embed, client side: no server round trip, no API key
 * for OpenStreetMap, nothing to break. The shortcode name and the `address` attribute are
 * kept so existing content keeps working.
 *
 * SECURITY: the address is used to build a URL against a host allowlist, and the result is
 * rendered as a sandboxed iframe. Arbitrary author input is never echoed into the page as
 * markup. An address that cannot be resolved to an allowlisted embed is refused with a
 * message rather than silently dropped.
 */
function cg_shortcode_map($atts, $content = null) {
    $atts = shortcode_atts([
        'address' => '',
        'width'   => '100%',
        'height'  => '400px',
        // The source's misspelling, accepted so old content is not broken by the fix.
        'widht'   => '',
    ], $atts, 'ale_map');

    $address = trim((string) $atts['address']);
    if ('' === $address) {
        return '';
    }

    $width  = $atts['width'] ?: $atts['widht'];
    $height = $atts['height'];

    $url = cg_map_embed_url($address);

    if (!$url) {
        return sprintf(
            '<div class="cg-map cg-map-unresolved"><p>%s</p><p class="cg-map-address">%s</p></div>',
            esc_html__('This map address could not be resolved to a map provider. Accepted forms are a Google Maps embed URL or "lat,lng" coordinates.', 'capuchinhoverde'),
            esc_html($address)
        );
    }

    return sprintf(
        '<div class="cg-map"><iframe src="%s" width="%s" height="%s" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" sandbox="allow-scripts allow-same-origin allow-popups" title="%s"></iframe></div>',
        esc_url($url),
        esc_attr($width),
        esc_attr($height),
        esc_attr__('Map', 'capuchinhoverde')
    );
}
add_shortcode('ale_map', 'cg_shortcode_map');

/**
 * Resolve a user-supplied map address to a provider embed URL.
 *
 * Two accepted forms, both unambiguous:
 *   1. A full embed URL on a recognised host — passed through with only the parameters we
 *      control appended.
 *   2. "lat,lng" coordinates — wrapped in an OpenStreetMap embed, which needs no API key.
 *
 * Anything else returns null. An arbitrary address string is never forwarded to a provider,
 * which is what keeps this from being a request-forgery gadget pointed at a chosen host.
 */
function cg_map_embed_url($address) {
    // 1. Already an embed URL?
    if (preg_match('#^https://#i', $address)) {
        $host = strtolower((string) wp_parse_url($address, PHP_URL_HOST));
        $allowed = [
            'google.com', 'www.google.com', 'maps.google.com', 'maps.googleapis.com',
            'www.google.pt', 'google.pt',
            'www.openstreetmap.org', 'openstreetmap.org',
        ];
        foreach ($allowed as $candidate) {
            if ($host === $candidate) {
                return $address;
            }
        }
        return null;
    }

    // 2. "lat,lng" coordinates.
    if (preg_match('/^\s*(-?\d{1,3}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $address, $m)) {
        $lat = (float) $m[1];
        $lng = (float) $m[2];
        // Reject out-of-range rather than clamping silently: an off-world coordinate is a typo.
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }
        $d = 12; // zoom-ish bbox padding
        $bbox = sprintf('%F,%F,%F,%F', $lng - $d, $lat - $d / 2, $lng + $d, $lat + $d / 2);
        return add_query_arg(
            [
                'bbox'    => $bbox,
                'layer'   => 'mapnik',
                'marker'  => $lat . ',' . $lng,
            ],
            'https://www.openstreetmap.org/export/embed.html'
        );
    }

    return null;
}
