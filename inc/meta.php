<?php
/**
 * Meta schema and editor surface.
 *
 * WHY THIS FILE EXISTS
 *
 * The ported templates read 30 keys through ale_get_meta(). Until 2026-09-27 the theme
 * registered no metabox, no Customizer panel and no register_post_meta — so there was no
 * writer for any of them. Every metered section (services, gallery, contact) was
 * permanently off and page-home.php rendered empty spacer divs. The E2E suite passed only
 * because scripts/seed.sh wrote the keys with wp-cli: the theme was testable but not
 * usable.
 *
 * The schema below is the SINGLE SOURCE OF TRUTH. It drives three consumers:
 *
 *   1. cg_register_meta()        -> register_post_meta(), so the REST API and the block
 *                                   editor can read/write the keys
 *   2. cg_render_meta_box()      -> the metabox UI
 *   3. cg_sanitize_meta_*()      -> the sanitisation callbacks
 *
 * A key therefore cannot exist in the UI without being registered, and cannot be registered
 * without a sanitiser. That coupling is what stops the "a template reads a key nobody can
 * write" bug class from recurring — it is the defect this whole file exists to prevent.
 *
 * Site-wide options (the keys ale_get_option() reads) live in inc/customizer.php and are
 * deliberately NOT here: a theme_mod is the right home for those.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Expand a repeatable group into flat numbered keys.
 *
 * The ported templates read keys by concatenation — ale_get_meta('teamname' . $i) — so the
 * keys exist on disk as teamname1..4. Generating them from one declaration keeps the
 * template's loop and the editor UI from drifting apart, and lets scripts/check-key-coverage.sh
 * compare loop bounds instead of grepping for keys that are never written literally.
 *
 * @param string $group      Group id, also used as the metabox table name.
 * @param array  $post_types Post types the keys apply to.
 * @param array  $fields     base key => [type, label].
 * @param int    $count      How many repetitions.
 */
function cg_meta_repeatable_group($group, $post_types, $fields, $count = 4) {
    $out = [];
    foreach ($fields as $base => $spec) {
        for ($i = 1; $i <= $count; $i++) {
            $out[$base . $i] = [
                'label'      => sprintf('%s (%d)', $spec[1], $i),
                'type'       => $spec[0],
                'post_types' => $post_types,
                'group'      => $group,
                'item'       => $i,
                'field'      => $base,
                'rows'       => 2,
            ];
        }
    }
    return $out;
}

/**
 * The schema.
 *
 * @return array<string,array{
 *     label:string, type:string, post_types:string[], group:string,
 *     description?:string, rows?:int
 * }>
 */
/**
 * Map a logical schema key to its post-meta storage key.
 *
 * The schema uses unprefixed logical names ('servtit1') because that is the vocabulary
 * the ported templates speak. They read through ale_get_meta(), which looks up
 * '_cg_' . $key. Centralising the prefix here means registration, the metabox, the save
 * handler and the reader can never disagree about the stored name — the bug this
 * function was added to fix.
 */
function cg_meta_storage_key($key) {
    return str_starts_with($key, '_cg_') ? $key : '_cg_' . $key;
}

function cg_meta_schema() {
    static $schema = null;
    if (null !== $schema) {
        return $schema;
    }

    $page_only = ['page'];

    $schema = [

        /* ---------------------------------------------------------------- sections */

        'serviceonhome' => [
            'label'      => __('Show the services section on this page', 'capuchinhoverde'),
            'type'       => 'boolean',
            'post_types' => $page_only,
            'group'      => 'sections',
        ],
        'galleryonhome' => [
            'label'      => __('Show the gallery section on this page', 'capuchinhoverde'),
            'type'       => 'boolean',
            'post_types' => $page_only,
            'group'      => 'sections',
        ],
        'contactonhome' => [
            'label'      => __('Show the contact section on this page', 'capuchinhoverde'),
            'type'       => 'boolean',
            'post_types' => $page_only,
            'group'      => 'sections',
        ],

        /* ---------------------------------------------------------------- services */

        'servtit' => [
            'label'       => __('Services — section title', 'capuchinhoverde'),
            'type'        => 'text',
            'post_types'  => $page_only,
            'group'       => 'services',
            'description' => __('Heading shown above the four service columns.', 'capuchinhoverde'),
        ],
        'servdesc' => [
            'label'       => __('Services — section description', 'capuchinhoverde'),
            'type'        => 'textarea',
            'post_types'  => $page_only,
            'group'       => 'services',
            'rows'        => 3,
        ],

        /* ---------------------------------------------------------------- gallery */

        'galbg' => [
            'label'       => __('Gallery — background image URL', 'capuchinhoverde'),
            'type'        => 'url',
            'post_types'  => $page_only,
            'group'       => 'gallery',
            'description' => __('Optional. Leave empty for no background image.', 'capuchinhoverde'),
        ],

        /* ---------------------------------------------------------------- contact */

        'contacttit' => [
            'label'      => __('Contact — section title', 'capuchinhoverde'),
            'type'       => 'text',
            'post_types' => $page_only,
            'group'      => 'contact',
        ],
        'contactemail' => [
            'label'       => __('Contact — email address', 'capuchinhoverde'),
            'type'        => 'email',
            'post_types'  => ['page'],
            'group'       => 'contact',
            'description' => __('Where contact form submissions are delivered. Defaults to the site admin email.', 'capuchinhoverde'),
        ],
        'contactphone' => [
            'label'      => __('Contact — phone', 'capuchinhoverde'),
            'type'       => 'text',
            'post_types' => ['page'],
            'group'      => 'contact',
        ],
        'contactaddress' => [
            'label'      => __('Contact — address', 'capuchinhoverde'),
            'type'       => 'textarea',
            'post_types' => ['page'],
            'group'      => 'contact',
            'rows'       => 2,
        ],
        'contactmap' => [
            'label'       => __('Contact — Google Maps embed URL or coordinates', 'capuchinhoverde'),
            'type'        => 'text',
            'post_types'  => ['page'],
            'group'       => 'contact',
            'description' => __('Used by the Contact page template (T012).', 'capuchinhoverde'),
        ],
        'contacttopimg' => [
            'label'      => __('Contact — top image URL', 'capuchinhoverde'),
            'type'       => 'url',
            'post_types' => ['page'],
            'group'      => 'contact',
        ],

        'teamtit' => [
            'label'       => __('Team — section title', 'capuchinhoverde'),
            'type'        => 'text',
            'post_types'  => $page_only,
            'group'       => 'team',
            'description' => __('Heading shown above the team members on the About template.', 'capuchinhoverde'),
        ],

        'menubg' => [
            'label'      => __('Prices — background image URL', 'capuchinhoverde'),
            'type'       => 'url',
            'post_types' => $page_only,
            'group'      => 'menu',
        ],

        'contactbg' => [
            'label'      => __('Contact — background image URL', 'capuchinhoverde'),
            'type'       => 'url',
            'post_types' => ['page'],
            'group'      => 'contact',
        ],

        /* ------------------------------------------------------------------ page */

        'custombg' => [
            'label'       => __('Page background image URL', 'capuchinhoverde'),
            'type'        => 'url',
            'post_types'  => ['page', 'post'],
            'group'       => 'page',
            'description' => __('Applied to the body element.', 'capuchinhoverde'),
        ],
        'custompagecss' => [
            'label'       => __('Page — custom CSS declarations', 'capuchinhoverde'),
            'type'        => 'css',
            'post_types'  => ['page', 'post'],
            'group'       => 'page',
            'description' => __('Declarations only, no braces or url(). Anything else is stripped when rendered.', 'capuchinhoverde'),
        ],
    ];

    // Services are four repeatable rows in the UI, but sixteen flat keys on disk, because
    // that is what the ported template reads (ale_get_meta('servtit' . $i)). Generating
    // them here keeps the loop in the template and the schema from drifting apart.
    $schema += cg_meta_repeatable_group('service_item', $page_only, [
        'servic'   => ['url',      __('Image URL', 'capuchinhoverde')],
        'servtit'  => ['text',     __('Title', 'capuchinhoverde')],
        'servlink' => ['url',      __('Link URL', 'capuchinhoverde')],
        'servdesc' => ['textarea', __('Description', 'capuchinhoverde')],
    ]);

    // Team on template-about.php — four members, same shape as the services.
    $schema += cg_meta_repeatable_group('team_item', $page_only, [
        'teamphoto' => ['url',      __('Photo URL', 'capuchinhoverde')],
        'teamname'  => ['text',     __('Name', 'capuchinhoverde')],
        'teamdesc'  => ['textarea', __('Description', 'capuchinhoverde')],
    ]);

    // Price items on template-about.php.
    //
    // KEY NAMING DEVIATION: the source reads 'menutitic1' — a typo for "menu title" — in
    // the item-top label, and 'menutit1' in the item heading. Both are ported under their
    // correct spelling (menutitle / menutit) because the `_cg_` meta prefix already means
    // source-theme content is not read by this port (see docs/PARITY.md §8), so preserving
    // a typo buys no import compatibility. Recorded in docs/PARITY.md.
    $schema += cg_meta_repeatable_group('menu_item', $page_only, [
        'menutitle' => ['text',     __('Label', 'capuchinhoverde')],
        'menutit'   => ['text',     __('Heading', 'capuchinhoverde')],
        'menuphoto' => ['url',      __('Photo URL', 'capuchinhoverde')],
        'menudesc'  => ['textarea', __('Description', 'capuchinhoverde')],
        'menuprice' => ['text',     __('Price', 'capuchinhoverde')],
    ]);

    // Slider slides live on the cg_slider post type, not on pages.
    $schema['slides'] = [
        'label'       => __('Slides', 'capuchinhoverde'),
        'type'        => 'repeater',
        'post_types'  => ['cg_slider'],
        'group'       => 'slides',
        'description' => __('Each slide: image, title, description, link and optional custom HTML. Order is the order shown here.', 'capuchinhoverde'),
        'item_label'  => __('Slide', 'capuchinhoverde'),
        'fields'      => [
            'image'       => ['type' => 'image', 'label' => __('Image', 'capuchinhoverde')],
            'title'       => ['type' => 'text', 'label' => __('Title', 'capuchinhoverde')],
            'description' => ['type' => 'textarea', 'label' => __('Description', 'capuchinhoverde')],
            'url'         => ['type' => 'url', 'label' => __('Link', 'capuchinhoverde')],
            'html'        => ['type' => 'textarea', 'label' => __('Custom HTML', 'capuchinhoverde')],
        ],
    ];

    /*
     * Per-slider effect settings. The source exposed these per slider (animation fade|slide,
     * slideshow, controlNav, randomize) plus width/height; our port had hard-coded
     * `animation: "fade"` and `controlNav: false` in the vendored InitHome.js, so there was
     * no way to change them. See aes/tickets/T018 (G2, G4).
     */
    $slider_effects = [
        // The defaults are not arbitrary: each one reproduces what the vendored InitHome.js
        // already does, so an untouched slider behaves exactly as it did before T018.
        //   animation  — InitHome passes animation:"fade"
        //   controlNav — InitHome passes controlNav:false
        //   slideshow  — NOT passed, so FlexSlider's default (true) applies
        //   randomize  — NOT passed, so FlexSlider's default (false) applies
        // These also match the source's own admin defaults (source sliders.php:436-470).
        'slideshow'  => [ __( 'Slideshow', 'capuchinhoverde' ), '1' ],
        'controlnav' => [ __( 'Control navigation', 'capuchinhoverde' ), '0' ],
        'randomize'  => [ __( 'Randomize slides', 'capuchinhoverde' ), '0' ],
    ];
    foreach ( $slider_effects as $key => $spec ) {
        $schema[ 'slider_' . $key ] = [
            'label'      => $spec[0],
            'type'       => 'select',
            'post_types' => ['cg_slider'],
            'group'      => 'slider_settings',
            'default'    => $spec[1],
            'options'    => [
                '1' => __( 'Yes', 'capuchinhoverde' ),
                '0' => __( 'No', 'capuchinhoverde' ),
            ],
        ];
    }
    // Animation is the one setting whose values are not yes/no.
    $schema['slider_animation'] = [
        'label'      => __('Animation', 'capuchinhoverde'),
        'type'       => 'select',
        'post_types' => ['cg_slider'],
        'group'      => 'slider_settings',
        'options'    => [
            'fade'  => __('Fade', 'capuchinhoverde'),
            'slide' => __('Slide', 'capuchinhoverde'),
        ],
        'default'    => 'fade',
    ];
    $schema['slider_width']  = [
        'label'      => __('Width', 'capuchinhoverde'),
        'type'       => 'text',
        'post_types' => ['cg_slider'],
        'group'      => 'slider_settings',
    ];
    $schema['slider_height'] = [
        'label'      => __('Height', 'capuchinhoverde'),
        'type'       => 'text',
        'post_types' => ['cg_slider'],
        'group'      => 'slider_settings',
    ];

    return $schema;
}

/* -------------------------------------------------------------------------- */
/* Sanitisers                                                                  */
/* -------------------------------------------------------------------------- */

/**
 * Scalar sanitisers.
 *
 * Every one of these starts with an is_array() guard. cg_save_meta() already replaces an
 * array with '' before calling them, but these functions are ALSO the sanitize_callback of a
 * register_post_meta() registration, and WordPress calls those straight from REST with
 * whatever decoded body was sent. Casting an array to string there is a PHP 8 warning
 * ("Array to string conversion") and stores the literal string "Array" — observed at
 * meta.php:338 while saving the seeded slider. Rejecting the array is both quieter and
 * correct.
 */
function cg_sanitize_meta_text($value) {
    return is_array($value) ? '' : sanitize_text_field((string) $value);
}

function cg_sanitize_meta_textarea($value) {
    return is_array($value) ? '' : sanitize_textarea_field((string) $value);
}

function cg_sanitize_meta_url($value) {
    if (is_array($value)) {
        return '';
    }
    return esc_url_raw(trim((string) $value), ['http', 'https', 'mailto', 'tel']);
}

function cg_sanitize_meta_email($value) {
    if (is_array($value)) {
        return '';
    }
    $value = sanitize_email((string) $value);
    return is_email($value) ? $value : '';
}

function cg_sanitize_meta_select($value) {
    if (is_array($value)) {
        return '';
    }
    return sanitize_text_field((string) $value);
}

function cg_sanitize_meta_boolean($value) {
    if (is_array($value)) {
        return '';
    }
    return $value ? 'on' : '';
}

/**
 * Strip anything that could escape a style attribute or a declaration block.
 *
 * Mirrors the render-time allowlist in header.php. Defence in depth: the renderer is the
 * last gate, but the value should never arrive in the database already weaponised.
 */
function cg_sanitize_meta_css($value) {
    $value = (string) $value;
    if (preg_match('/[{}<>"\']|url\s*\(|expression|@import|javascript:/i', $value)) {
        return '';
    }
    return trim(preg_replace('/[^a-zA-Z0-9#%.,\s:()\/-]/', '', $value));
}

/**
 * Sanitise a `json` schema field and return it as a PHP array.
 *
 * The sanitiser returns an array, not a JSON string, on purpose. The reader in
 * inc/ale-compat.php consumes arrays, and returning a string here made the only
 * user-facing way to configure the home slider — this function — produce a value the front
 * end always discarded. See aes/tickets/T015 (B4).
 */
function cg_sanitize_meta_json($value) {
    if (is_array($value)) {
        $decoded = $value;
    } else {
        if (!is_string($value) || '' === trim($value)) {
            return '';
        }
        $decoded = json_decode($value, true);
    }
    if (!is_array($decoded)) {
        return '';
    }
    $clean = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }
        $clean[] = [
            'image'       => isset($item['image']) ? esc_url_raw((string) $item['image'], ['http', 'https']) : '',
            'title'       => isset($item['title']) ? sanitize_text_field((string) $item['title']) : '',
            'description' => isset($item['description']) ? sanitize_textarea_field((string) $item['description']) : '',
            'url'         => isset($item['url']) ? esc_url_raw((string) $item['url'], ['http', 'https']) : '',
            // The source echoed this raw (source sliders.php:545, post_excerpt). Stored HTML
            // from the editor is a stored-XSS vector, so it is filtered to what a post may
            // legitimately contain. wp_kses_post matches how the source rendered it —
            // through apply_filters('the_content', …).
            'html'        => isset($item['html']) ? wp_kses_post((string) $item['html']) : '',
        ];
    }
    return $clean;
}

/**
 * Map a schema type to its sanitiser.
 */
function cg_meta_sanitizer_for($type) {
    switch ($type) {
        case 'url':
            return 'cg_sanitize_meta_url';
        case 'email':
            return 'cg_sanitize_meta_email';
        case 'boolean':
            return 'cg_sanitize_meta_boolean';
        case 'textarea':
            return 'cg_sanitize_meta_textarea';
        case 'json':
            return 'cg_sanitize_meta_json';
        case 'css':
            return 'cg_sanitize_meta_css';
        case 'select':
            return 'cg_sanitize_meta_select';
        case 'repeater':
            // A repeater is stored as the same JSON payload a `json` field is, which is why
            // the T018 slider UI needed no new sanitiser.
            return 'cg_sanitize_meta_json';
        case 'text':
        default:
            return 'cg_sanitize_meta_text';
    }
}

/* -------------------------------------------------------------------------- */
/* Registration                                                                 */
/* -------------------------------------------------------------------------- */

/**
 * Register every schema key with register_post_meta().
 *
 * show_in_rest is what exposes the key to the block editor and the REST API. The
 * auth_callback is what stops a subscriber-level REST caller from writing it — without
 * it, show_in_rest would be a privilege leak.
 */
function cg_register_meta() {
    foreach (cg_meta_schema() as $key => $field) {
        $storage_key = cg_meta_storage_key($key);
        foreach ($field['post_types'] as $post_type) {
            register_post_meta($post_type, $storage_key, [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => cg_meta_sanitizer_for($field['type']),
                'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', $post_id);
                },
                'default'           => 'text' === $field['type'] || 'textarea' === $field['type'] || 'url' === $field['type'] ? '' : '',
            ]);
        }
    }
}
add_action('init', 'cg_register_meta');

/* -------------------------------------------------------------------------- */
/* Metabox UI                                                                   */
/* -------------------------------------------------------------------------- */

function cg_meta_box_groups() {
    return [
        'sections'      => __('Home sections', 'capuchinhoverde'),
        'services'      => __('Services', 'capuchinhoverde'),
        'service_item'  => __('Service items', 'capuchinhoverde'),
        'team'          => __('Team', 'capuchinhoverde'),
        'team_item'     => __('Team members', 'capuchinhoverde'),
        'menu'          => __('Prices', 'capuchinhoverde'),
        'menu_item'     => __('Price items', 'capuchinhoverde'),
        'gallery'       => __('Gallery', 'capuchinhoverde'),
        'contact'       => __('Contact', 'capuchinhoverde'),
        'page'          => __('Page appearance', 'capuchinhoverde'),
        'slides'        => __('Slider', 'capuchinhoverde'),
        'slider_settings' => __('Slider effects', 'capuchinhoverde'),
    ];
}

/**
 * Render a repeater: repeatable rows of sub-fields, serialised to one hidden JSON input.
 *
 * WHY A HIDDEN INPUT AND NOT A NESTED ARRAY
 * cg_save_meta() deliberately discards array input (`$value = is_array($value) ? '' : $value`)
 * so a crafted POST cannot smuggle a structure past the sanitiser. A repeater that posted
 * `cg_meta[_cg_slides][0][image]` would therefore be thrown away on save. Instead the visible
 * inputs are UNNAMED, and assets/js/admin-repeater.js serialises them into the single
 * `cg_meta[_cg_slides]` input that cg_sanitize_meta_json() already handles. The save path and
 * the T015/B4 writer-reader contract are untouched by this UI.
 *
 * @param string $key   Schema key, used for ids and the input name.
 * @param array  $field Field spec; `fields` is name => [type, label].
 * @param mixed  $value Stored value: an array of rows, or a JSON string from an older save.
 */
function cg_render_repeater( $key, $field, $value ) {
    $rows = $value;
    if ( is_string( $rows ) ) {
        $decoded = json_decode( $rows, true );
        $rows    = is_array( $decoded ) ? $decoded : [];
    }
    if ( ! is_array( $rows ) ) {
        $rows = [];
    }

    printf(
        '<div class="cg-repeater" data-cg-repeater="%s" data-cg-item-label="%s">',
        esc_attr( $key ),
        esc_attr( $field['item_label'] ?? __( 'Item', 'capuchinhoverde' ) )
    );

    // The one input the save path sees.
    printf(
        '<input type="hidden" class="cg-repeater-json" name="cg_meta[%s]" value="%s" />',
        esc_attr( $key ),
        esc_attr( wp_json_encode( array_values( $rows ), JSON_UNESCAPED_SLASHES ) )
    );

    echo '<div class="cg-repeater-rows">';
    foreach ( array_values( $rows ) as $index => $row ) {
        cg_render_repeater_row( $key, $field, (array) $row, (int) $index );
    }
    echo '</div>';

    printf(
        '<p class="cg-repeater-actions"><button type="button" class="button cg-repeater-add">%s</button></p>',
        esc_html__( 'Add slide', 'capuchinhoverde' )
    );

    /*
     * The empty row the JS clones, kept out of the document flow until needed.
     *
     * A real <template>, not <script type="text/html">: the latter parses as raw text, so its
     * innerHTML is a STRING and appendChild() throws "parameter 1 is not of type 'Node'" —
     * which is exactly how the Add button silently failed to add a row until the first real
     * browser run. A <template>'s .content is an inert DocumentFragment, so the cloned inputs
     * are never submitted, never run, and take no part in validation until they are inserted.
     */
    echo '<template class="cg-repeater-template">';
    cg_render_repeater_row( $key, $field, [], 0 );
    echo '</template>';

    echo '</div>';
}

/** Render one repeater row. `$row_index` is replaced by the JS when a row is cloned. */
function cg_render_repeater_row( $key, $field, $row, $row_index ) {
    $token = '__INDEX_' . (int) $row_index . '__';
    printf( '<div class="cg-repeater-row" data-index="%s">', esc_attr( $token ) );

    printf(
        '<div class="cg-repeater-row-head"><span class="cg-repeater-handle" aria-hidden="true">&#8942;&#8942;</span>'
        . '<span class="cg-repeater-row-label">%s</span>'
        . '<span class="cg-repeater-row-tools">'
        . '<button type="button" class="button-link cg-repeater-move-up" aria-label="%s">&#9650;</button> '
        . '<button type="button" class="button-link cg-repeater-move-down" aria-label="%s">&#9660;</button> '
        . '<button type="button" class="button-link cg-repeater-remove" aria-label="%s">&#10005;</button>'
        . '</span></div>',
        esc_html( ( $field['item_label'] ?? __( 'Item', 'capuchinhoverde' ) ) . ' ' . ( (int) $row_index + 1 ) ),
        esc_attr__( 'Move up', 'capuchinhoverde' ),
        esc_attr__( 'Move down', 'capuchinhoverde' ),
        esc_attr__( 'Remove', 'capuchinhoverde' )
    );

    echo '<div class="cg-repeater-fields">';
    foreach ( (array) ( $field['fields'] ?? [] ) as $sub_key => $sub ) {
        $sub_name = 'cg-repeater-' . $token . '-' . $sub_key;
        $value    = isset( $row[ $sub_key ] ) ? (string) $row[ $sub_key ] : '';
        printf(
            '<label class="cg-repeater-field cg-repeater-field--%s"><span>%s</span>',
            esc_attr( $sub['type'] ?? 'text' ),
            esc_html( $sub['label'] ?? $sub_key )
        );

        switch ( $sub['type'] ?? 'text' ) {
            case 'image':
                printf(
                    '<span class="cg-repeater-image">'
                    . '<input type="text" class="cg-repeater-input" data-field="%s" id="%s" value="%s" placeholder="%s" />'
                    . '<button type="button" class="button cg-repeater-media">%s</button></span>',
                    esc_attr( $sub_key ),
                    esc_attr( $sub_name ),
                    esc_attr( $value ),
                    esc_attr__( 'Image URL', 'capuchinhoverde' ),
                    esc_html__( 'Choose image', 'capuchinhoverde' )
                );
                break;

            case 'textarea':
                printf(
                    '<textarea class="cg-repeater-input" data-field="%s" id="%s" rows="3">%s</textarea>',
                    esc_attr( $sub_key ),
                    esc_attr( $sub_name ),
                    esc_textarea( $value )
                );
                break;

            case 'url':
                printf(
                    '<input type="url" class="cg-repeater-input" data-field="%s" id="%s" value="%s" placeholder="https://" />',
                    esc_attr( $sub_key ),
                    esc_attr( $sub_name ),
                    esc_attr( $value )
                );
                break;

            default:
                printf(
                    '<input type="text" class="cg-repeater-input" data-field="%s" id="%s" value="%s" />',
                    esc_attr( $sub_key ),
                    esc_attr( $sub_name ),
                    esc_attr( $value )
                );
        }

        echo '</label>';
    }
    echo '</div></div>';
}

/**
 * Render one input for a schema field.
 */
function cg_render_meta_field($key, $field, $post_id) {
    $value = get_post_meta($post_id, cg_meta_storage_key($key), true);
    $id    = 'cg-meta-' . $key;
    $name  = 'cg_meta[' . $key . ']';

    echo '<p class="cg-meta-field">';
    printf(
        '<label for="%s"><strong>%s</strong></label><br>',
        esc_attr($id),
        esc_html($field['label'])
    );

    switch ($field['type']) {
        case 'boolean':
            printf(
                '<input type="checkbox" id="%s" name="%s" value="on" %s />',
                esc_attr($id),
                esc_attr($name),
                checked($value, 'on', false)
            );
            break;

        case 'textarea':
        case 'css':
            // 'css' renders as a textarea too: declarations are multi-line by nature, and a
            // single-line input invites the author to break the pattern.
            printf(
                '<textarea id="%s" name="%s" rows="%d" class="large-text code">%s</textarea>',
                esc_attr($id),
                esc_attr($name),
                (int) ($field['rows'] ?? 3),
                esc_textarea($value)
            );
            break;

        case 'url':
            printf(
                '<input type="url" id="%s" name="%s" value="%s" class="large-text" placeholder="https://" />',
                esc_attr($id),
                esc_attr($name),
                esc_attr($value)
            );
            break;

        case 'email':
            printf(
                '<input type="email" id="%s" name="%s" value="%s" class="regular-text" />',
                esc_attr($id),
                esc_attr($name),
                esc_attr($value)
            );
            break;

        case 'json':
            /*
             * A `json` field is stored as a PHP array (see cg_sanitize_meta_json), so the
             * raw value is not a string here. Passing an array straight to esc_textarea()
             * is a TypeError — htmlspecialchars() rejects arrays — and it took the whole
             * editor down with "There has been a critical error on this website" for any
             * post whose json field was populated. See aes/tickets/T015 (B3).
             */
            printf(
                '<textarea id="%s" name="%s" rows="6" class="large-text code">%s</textarea>',
                esc_attr($id),
                esc_attr($name),
                esc_textarea(
                    is_array( $value )
                        ? wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
                        : (string) $value
                )
            );
            break;

        case 'select':
            printf('<select id="%s" name="%s">', esc_attr($id), esc_attr($name));
            $cg_options = (array) ( $field['options'] ?? [] );
            $cg_selected = ( '' === (string) $value && isset( $field['default'] ) ) ? $field['default'] : (string) $value;
            // A <select> with no matching option renders with NOTHING selected, and the
            // browser then submits its first option. For a yes/no select whose first option
            // is "Yes", that silently turned every unset setting into "1" the first time
            // anything on the post was saved. Falling back to the first choice keeps the
            // control in a defined state.
            if ( $cg_options && ! array_key_exists( $cg_selected, $cg_options ) ) {
                $cg_selected = (string) key( $cg_options );
            }
            foreach ( $cg_options as $cg_opt_value => $cg_opt_label ) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr( $cg_opt_value ),
                    selected( $cg_selected, (string) $cg_opt_value, false ),
                    esc_html( $cg_opt_label )
                );
            }
            echo '</select>';
            break;

        case 'repeater':
            cg_render_repeater( $key, $field, $value );
            break;

        default:
            printf(
                '<input type="text" id="%s" name="%s" value="%s" class="large-text" />',
                esc_attr($id),
                esc_attr($name),
                esc_attr($value)
            );
    }

    if (!empty($field['description'])) {
        printf('<span class="description">%s</span>', esc_html($field['description']));
    }

    echo '</p>';
}

function cg_render_meta_box($post) {
    // Nonce is verified in cg_save_meta(); this field only stops the admin from
    // submitting the form for another post.
    wp_nonce_field('cg_save_meta', 'cg_meta_nonce');

    $schema  = cg_meta_schema();
    $groups  = [];
    foreach ($schema as $key => $field) {
        if (!in_array($post->post_type, $field['post_types'], true)) {
            continue;
        }
        $groups[$field['group']][] = $key;
    }

    $labels = cg_meta_box_groups();
    $echoed = 0;

    foreach ($groups as $group => $keys) {
        // Repeatable groups are rendered as a table so the rows line up visually instead of
        // as a wall of loose inputs. The columns come from the group's own field map, so a
        // new group needs no change here.
        if (str_ends_with($group, '_item')) {
            $by_item = [];
            $columns = [];
            foreach ($keys as $key) {
                $by_item[$schema[$key]['item']][$schema[$key]['field']] = $key;
                $columns[] = $schema[$key]['field'];
            }
            ksort($by_item);
            // Column order follows the field map's declaration order, not insertion order.
            $first_item = reset($by_item);
            $columns = array_keys($first_item);

            echo '<h4>' . esc_html($labels[$group] ?? $group) . '</h4>';
            // The group is in the class so a repeatable group's table can be targeted
            // (tests/e2e/meta.spec.ts scopes to service_item) instead of every table on the
            // page, which is ambiguous once a second group is added.
            printf(
                '<table class="form-table cg-meta-table cg-meta-table--%s"><thead><tr><th>#</th>',
                esc_attr($group)
            );
            foreach ($columns as $col) {
                echo '<th>' . esc_html($schema[$first_item[$col]]['label'] . ' — ' . $col) . '</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($by_item as $item => $fields) {
                echo '<tr>';
                echo '<th scope="row">' . esc_html((string) $item) . '</th>';
                foreach ($columns as $col) {
                    if (!isset($fields[$col])) {
                        echo '<td></td>';
                        continue;
                    }
                    $key   = $fields[$col];
                    $field = $schema[$key];
                    $value = get_post_meta($post->ID, cg_meta_storage_key($key), true);
                    echo '<td>';
                    if (in_array($field['type'], ['textarea', 'css', 'json'], true)) {
                        printf(
                            '<textarea name="cg_meta[%s]" rows="2" class="large-text">%s</textarea>',
                            esc_attr($key),
                            esc_textarea($value)
                        );
                    } else {
                        printf(
                            '<input type="text" name="cg_meta[%s]" value="%s" class="regular-text" />',
                            esc_attr($key),
                            esc_attr($value)
                        );
                    }
                    echo '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
            $echoed += count($keys);
            continue;
        }

        if (!empty($labels[$group])) {
            echo '<h4>' . esc_html($labels[$group]) . '</h4>';
        }
        foreach ($keys as $key) {
            cg_render_meta_field($key, $schema[$key], $post->ID);
        }
        $echoed += count($keys);
    }

    if (0 === $echoed) {
        echo '<p>' . esc_html__('This post type has no Capuchinho Verde options.', 'capuchinhoverde') . '</p>';
    }
}

/**
 * Register the metabox for every post type that has at least one key.
 */
function cg_add_meta_boxes() {
    $post_types = [];
    foreach (cg_meta_schema() as $field) {
        foreach ($field['post_types'] as $post_type) {
            $post_types[$post_type] = true;
        }
    }
    foreach (array_keys($post_types) as $post_type) {
        add_meta_box(
            'cg-theme-options',
            __('Capuchinho Verde', 'capuchinhoverde'),
            'cg_render_meta_box',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'cg_add_meta_boxes');

/**
 * Persist submitted meta.
 *
 * Two rules that matter:
 *  - the nonce is verified before anything is written;
 *  - the submitted keys are intersected with the schema, so a crafted POST cannot write
 *    arbitrary `_cg_` meta.
 */
function cg_save_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['cg_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cg_meta_nonce'])), 'cg_save_meta')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['cg_meta']) || !is_array($_POST['cg_meta'])) {
        return;
    }

    $submitted = wp_unslash($_POST['cg_meta']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised
    $schema    = cg_meta_schema();

    foreach ($schema as $key => $field) {
        if (!in_array(get_post_type($post_id), $field['post_types'], true)) {
            continue;
        }

        $sanitizer = cg_meta_sanitizer_for($field['type']);

        if (!array_key_exists($key, $submitted)) {
            // An unchecked checkbox is absent from the POST, so an absent key is a
            // deliberate "off" — not "leave unchanged".
            if ('boolean' === $field['type']) {
                delete_post_meta($post_id, cg_meta_storage_key($key));
            }
            continue;
        }

        $value = $submitted[$key];
        $value = is_array($value) ? '' : $value;
        $value = call_user_func($sanitizer, $value);

        $storage_key = cg_meta_storage_key($key);
        // A `json` field sanitises to an array, so an empty value can be [] as well as ''.
        $is_empty = ( is_array( $value ) ? [] === $value : ( '' === $value || null === $value ) );
        if ( $is_empty ) {
            delete_post_meta($post_id, $storage_key);
        } else {
            update_post_meta($post_id, $storage_key, $value);
        }
    }
}
add_action('save_post', 'cg_save_meta');
