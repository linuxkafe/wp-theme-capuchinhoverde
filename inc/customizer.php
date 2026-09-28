<?php
/**
 * Site-wide options in the Customizer.
 *
 * WHY THIS FILE EXISTS
 *
 * The ported templates read 24 site-wide values through ale_get_option() — logo, slider
 * slug, social links, feature toggles. Like the per-post meta in inc/meta.php, none of
 * them had a writer before 2026-09-27: ale_get_option() fell through to get_theme_mod(),
 * which was also never written, so every call returned the default. The options panel in
 * the source theme (aletheme/options/*, ~1,747 LOC) is a plugin's worth of machinery and
 * is deliberately not ported (docs/PARITY.md §6); the Customizer is its modern equivalent.
 *
 * Same discipline as inc/meta.php: a schema array drives registration, sanitisation and
 * the UI, so a key cannot be rendered without a sanitiser.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string,array{label:string,type:string,group:string,description?:string}>
 */
function cg_option_schema() {
    return [
        'sitelogo'          => ['label' => __('Site logo URL', 'capuchinhoverde'),           'type' => 'url',       'group' => 'identity'],
        'animationsitelogo' => ['label' => __('Preloader logo URL', 'capuchinhoverde'),      'type' => 'url',       'group' => 'identity'],
        'mainheader'        => ['label' => __('404 / inner header image URL', 'capuchinhoverde'), 'type' => 'url',  'group' => 'identity'],
        // Per-context inner-page header images, read by partials/innerheaders.php.
        'contactheader'      => ['label' => __('Contact page header image', 'capuchinhoverde'), 'type' => 'url', 'group' => 'headers'],
        'galleryheader'      => ['label' => __('Gallery header image', 'capuchinhoverde'),       'type' => 'url', 'group' => 'headers'],
        'eventsheader'       => ['label' => __('Events header image', 'capuchinhoverde'),        'type' => 'url', 'group' => 'headers'],
        'menuheader'         => ['label' => __('Menu header image', 'capuchinhoverde'),          'type' => 'url', 'group' => 'headers'],
        'storyheader'        => ['label' => __('Blog header image', 'capuchinhoverde'),          'type' => 'url', 'group' => 'headers'],
        'copyrights'        => ['label' => __('Footer copyright text', 'capuchinhoverde'),   'type' => 'text',      'group' => 'identity'],

        'homeslugfull'      => [
            'label'       => __('Home slider', 'capuchinhoverde'),
            'type'        => 'select',
            'group'       => 'home',
            'description' => __('Which Slider to show on the Home page.', 'capuchinhoverde'),
        ],

        'preloaderstatus'   => ['label' => __('Enable the preloader', 'capuchinhoverde'),    'type' => 'checkbox',  'group' => 'features'],
        'langswitcher'      => ['label' => __('Enable the language switcher', 'capuchinhoverde'), 'type' => 'checkbox', 'group' => 'features'],
        'skinselector'      => ['label' => __('Enable the skin selector', 'capuchinhoverde'), 'type' => 'checkbox', 'group' => 'features'],
        'formcontact'       => ['label' => __('Enable the contact form', 'capuchinhoverde'), 'type' => 'checkbox',  'group' => 'features'],
        'ajax_posts'        => ['label' => __('Enable AJAX post loading', 'capuchinhoverde'), 'type' => 'checkbox',  'group' => 'features'],
        'ajax_open_single'  => ['label' => __('Enable AJAX single post loading', 'capuchinhoverde'), 'type' => 'checkbox', 'group' => 'features'],
        'ajax_comments'     => ['label' => __('Enable AJAX comment loading', 'capuchinhoverde'), 'type' => 'checkbox', 'group' => 'features'],

        'contact_email'     => [
            'label'       => __('Contact form recipient', 'capuchinhoverde'),
            'type'        => 'email',
            'group'       => 'contact',
            'description' => __('Where form submissions are delivered. Defaults to the site admin email.', 'capuchinhoverde'),
        ],

        'fb'   => ['label' => 'Facebook',  'type' => 'url', 'group' => 'social'],
        'gog'  => ['label' => 'Google+',   'type' => 'url', 'group' => 'social'],
        'twi'  => ['label' => 'Twitter',   'type' => 'url', 'group' => 'social'],
        'insta' => ['label' => 'Instagram', 'type' => 'url', 'group' => 'social'],
        'lin'  => ['label' => 'LinkedIn',  'type' => 'url', 'group' => 'social'],
        'pin'  => ['label' => 'Pinterest', 'type' => 'url', 'group' => 'social'],
        'vim'  => ['label' => 'Vimeo',     'type' => 'url', 'group' => 'social'],
        'ytb'  => ['label' => 'YouTube',   'type' => 'url', 'group' => 'social'],
        'flickr' => ['label' => 'Flickr',  'type' => 'url', 'group' => 'social'],
    ];
}

function cg_option_groups() {
    return [
        'identity' => __('Identity', 'capuchinhoverde'),
        'headers'  => __('Inner page headers', 'capuchinhoverde'),
        'home'     => __('Home page', 'capuchinhoverde'),
        'features' => __('Features', 'capuchinhoverde'),
        'contact'  => __('Contact', 'capuchinhoverde'),
        'social'   => __('Social links', 'capuchinhoverde'),
    ];
}

function cg_option_sanitize($value, $type) {
    switch ($type) {
        case 'url':
            return esc_url_raw(trim((string) $value), ['http', 'https', 'mailto', 'tel']);
        case 'email':
            $value = sanitize_email((string) $value);
            return is_email($value) ? $value : '';
        case 'checkbox':
            return $value ? '1' : '';
        case 'select':
        case 'text':
        default:
            return sanitize_text_field((string) $value);
    }
}

function cg_customize_register($wp_customize) {
    $wp_customize->add_panel('cg_theme_options', [
        'title'       => __('Capuchinho Verde', 'capuchinhoverde'),
        'description' => __('Site-wide options read by the theme templates.', 'capuchinhoverde'),
        'priority'    => 30,
    ]);

    $group_labels = cg_option_groups();
    $schema       = cg_option_schema();

    foreach ($group_labels as $group => $label) {
        $wp_customize->add_section('cg_' . $group, [
            'title' => $label,
            'panel' => 'cg_theme_options',
        ]);
    }

    foreach ($schema as $key => $field) {
        $type = $field['type'];

        // The slider choice is a select over the published cg_slider posts, built at
        // registration time so a newly created slider appears without a code change.
        if ('select' === $type) {
            $choices = ['' => __('— none —', 'capuchinhoverde')];
            $sliders = get_posts([
                'post_type'      => 'cg_slider',
                'post_status'    => 'publish',
                'posts_per_page' => 50,
                'no_found_rows'  => true,
            ]);
            foreach ($sliders as $slider) {
                $choices[$slider->post_name] = $slider->post_title;
            }
            $wp_customize->add_setting('homeslugfull', [
                'default'           => '',
                'sanitize_callback' => static fn($v) => cg_option_sanitize($v, 'text'),
                'transport'         => 'refresh',
            ]);
            $wp_customize->add_control('homeslugfull', [
                'label'   => $field['label'],
                'section' => 'cg_' . $field['group'],
                'type'    => 'select',
                'choices' => $choices,
                'description' => $field['description'] ?? '',
            ]);
            continue;
        }

        $wp_customize->add_setting($key, [
            'default'           => '',
            'sanitize_callback' => static function ($value) use ($type) {
                return cg_option_sanitize($value, $type);
            },
            'transport'         => 'refresh',
        ]);

        $control = [
            'label'   => $field['label'],
            'section' => 'cg_' . $field['group'],
            'type'    => 'checkbox' === $type ? 'checkbox' : ('email' === $type ? 'email' : 'text'),
        ];
        if (!empty($field['description'])) {
            $control['description'] = $field['description'];
        }
        if ('url' === $type) {
            $control['type'] = 'url';
        }

        $wp_customize->add_control($key, $control);
    }
}
add_action('customize_register', 'cg_customize_register');
