<?php
/**
 * Capuchinho Verde functions and definitions
 */

if (! defined('_CG_VERSION')) {
    define('_CG_VERSION', '1.0.0');
}

/**
 * After setup theme
 */
function cg_setup() {
    load_theme_textdomain('capuchinhoverde', get_template_directory() . '/languages');

    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('editor-styles');
    add_theme_support('align-wide');

    register_nav_menus([
        'primary' => esc_html__('Primary Menu','capuchinhoverde'),
        'footer'  => esc_html__('Footer Menu','capuchinhoverde'),
        'header_left_menu' => esc_html__('Header Left Menu','capuchinhoverde'),
        'header_right_menu' => esc_html__('Header Right Menu','capuchinhoverde'),
        'mobile_menu' => esc_html__('Mobile Menu','capuchinhoverde')
    ]);

    add_theme_support('custom-logo', ['height'=>200,'width'=>400,'flex-height'=>true]);
}
add_action('after_setup_theme', 'cg_setup');

/**
 * Enqueue scripts and styles
 */
function cg_scripts() {
    wp_enqueue_style('capuchinhoverde-style', get_stylesheet_uri(), [], _CG_VERSION);

    // Legacy CSS from Cafeteria theme
    wp_enqueue_style('cafeteria-reset', get_template_directory_uri().'/assets/css/legacy/reset.css', [], '1.7');
    wp_enqueue_style('cafeteria-elements', get_template_directory_uri().'/assets/css/legacy/elements.css', ['cafeteria-reset'], '1.7');
    wp_enqueue_style('cafeteria-general', get_template_directory_uri().'/assets/css/legacy/general.css', ['cafeteria-elements'], '1.7');
    wp_enqueue_style('cafeteria-main', get_template_directory_uri().'/assets/css/legacy/main.css', ['cafeteria-general'], '1.7');
    wp_enqueue_style('cafeteria-responsive', get_template_directory_uri().'/assets/css/legacy/responsive.css', ['cafeteria-main'], '1.7');

    wp_enqueue_script('capuchinhoverde-nav', get_template_directory_uri().'/assets/js/nav.js', [], _CG_VERSION, true);

    // Legacy JS from Cafeteria theme. File names are `modules.js` and `scripts.js`;
    // the previous `ale_modules.js` / `ale_scripts.js` handles 404'd on every page load.
    wp_enqueue_script('jquery');
    wp_enqueue_script('modernizr', get_template_directory_uri().'/assets/js/legacy/modernizr-2.5.3.min.js', [], '2.5.3', true);
    wp_enqueue_script('scrollable', get_template_directory_uri().'/assets/js/legacy/scrollable.js', ['jquery'], '1.7', true);
    wp_enqueue_script('jquery-cookie', get_template_directory_uri().'/assets/js/legacy/jquery.cookie.js', ['jquery'], '1.7', true);
    wp_enqueue_script('isotope', get_template_directory_uri().'/assets/js/legacy/jquery.isotope.min.js', ['jquery'], '1.7', true);
    // Restores jQuery .live(), removed in jQuery 3.0, which assets/js/legacy/scripts.js
    // still calls for the colour-scheme selector. Must run before ale-scripts; it is a
    // dependency rather than a bare enqueue so WordPress guarantees the order. See
    // aes/tickets/T015 (B2) for why the fix is not made in the vendored file.
    wp_enqueue_script('capuchinhoverde-legacy-compat', get_template_directory_uri().'/assets/js/legacy-compat.js', ['jquery'], _CG_VERSION, true);
    wp_enqueue_script('ale-modules', get_template_directory_uri().'/assets/js/legacy/modules.js', ['jquery'], '1.7', true);
    wp_enqueue_script('ale-scripts', get_template_directory_uri().'/assets/js/legacy/scripts.js', ['jquery', 'ale-modules', 'capuchinhoverde-legacy-compat'], '1.7', true);

    // The ported scripts.js reads a global `ale` object that the source framework
    // localized. Without it every page threw "ale is not defined" and the mobile
    // detection, AJAX comments and conditional stylesheet loading silently did nothing.
    wp_localize_script('ale-scripts', 'ale', [
        'template_dir'      => get_template_directory_uri(),
        'ajax_load_url'     => admin_url('admin-ajax.php'),
        'ajax_posts'        => (int) ale_get_option('ajax_posts', 0),
        'ajax_open_single'  => (int) ale_get_option('ajax_open_single', 0),
        'ajax_comments'     => (int) ale_get_option('ajax_comments', 0),
        'is_mobile'         => (int) wp_is_mobile(),
    ]);

    // Per-page initialisers, matching the source theme's own Init*.js split.
    if (is_front_page()) {
        wp_enqueue_script('init-home', get_template_directory_uri().'/assets/js/legacy/InitHome.js', ['jquery', 'ale-scripts', 'isotope'], '1.7', true);
    }
    if (is_page_template('page-home.php') || is_singular('cg_menu')) {
        wp_enqueue_script('init-menu', get_template_directory_uri().'/assets/js/legacy/InitOpenMenu.js', ['jquery', 'ale-scripts'], '1.7', true);
    }
    if (is_singular('cg_gallery') || is_post_type_archive('cg_gallery')) {
        wp_enqueue_script('init-gallery', get_template_directory_uri().'/assets/js/legacy/InitOpenGallery.js', ['jquery', 'ale-scripts', 'isotope'], '1.7', true);
    }
    if (is_page_template('template-about.php')) {
        wp_enqueue_script('init-about', get_template_directory_uri().'/assets/js/legacy/InitAbout.js', ['jquery', 'ale-scripts'], '1.7', true);
    }
    wp_enqueue_script('init-default', get_template_directory_uri().'/assets/js/legacy/InitDefault.js', ['jquery', 'ale-scripts'], '1.7', true);

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts','cg_scripts');

/**
 * Editor styles.
 *
 * add_editor_style() must run on after_setup_theme. It was previously called on
 * admin_init, which is after the editor has already resolved its stylesheet list.
 */
function cg_editor_styles() {
    add_editor_style('assets/css/editor.css');
}
add_action('after_setup_theme','cg_editor_styles');

/**
 * Content width
 */
if (! isset($content_width)) {
    $content_width = 1200;
}

/**
 * ALETheme Compatibility Layer
 */
require_once get_template_directory() . '/inc/ale-compat.php';

/**
 * Custom template tags
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Woocommerce support
 */
add_action('after_setup_theme','cg_woocommerce_support');
function cg_woocommerce_support(){
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}

/**
 * Image sizes.
 *
 * Ported from the source theme's aletheme_get_images_sizes() (aletheme/config.php:1500).
 * Without these, get_the_post_thumbnail($id, 'gallery-thumba') silently falls back to
 * full-size images — the port failed silently rather than loudly.
 */
function cg_image_sizes() {
    add_image_size('cg-gallery-thumba', 212, 162, true);
    add_image_size('cg-gallery-slider', 985, 410, true);
    add_image_size('cg-post-thumba', 475, 295, true);
    add_image_size('cg-post-slider', 658, 296, true);
    add_image_size('cg-event-thumba', 452, 130, true);
    add_image_size('cg-event-slider', 657, 296, true);
    add_image_size('cg-menu-thumba', 239, 141, true);
    add_image_size('cg-menu-slider', 985, 410, true);
}
add_action('after_setup_theme', 'cg_image_sizes');

/**
 * Custom Post Types
 */
require get_template_directory() . '/inc/post-types.php';

/**
 * Editor surfaces: per-post meta schema (inc/meta.php) and site-wide options
 * (inc/customizer.php). Both exist because the ported templates read ~54 keys
 * through ale_get_meta()/ale_get_option() and, before 2026-09-27, not one of them
 * had a writer. See inc/meta.php for the full rationale.
 */
require get_template_directory() . '/inc/meta.php';
require get_template_directory() . '/inc/customizer.php';

/**
 * Content shortcodes (ale_service, ale_team, ale_testimonial, ale_partner, ale_toggle,
 * ale_map). The 12 layout shortcodes from the source are deliberately not ported — they
 * duplicate core/columns. See inc/shortcodes.php for the full rationale.
 */
require get_template_directory() . '/inc/shortcodes.php';

/**
 * Register block patterns
 */
function cg_register_patterns() {
    register_block_pattern_category('capuchinhoverde', ['label' => esc_html__('Capuchinho Verde','capuchinhoverde')]);
}
add_action('init','cg_register_patterns');
