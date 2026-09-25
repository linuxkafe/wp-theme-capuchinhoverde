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
    wp_enqueue_style('capuchinhoverde-editor', get_template_directory_uri().'/assets/css/editor.css', ['capuchinhoverde-style'], _CG_VERSION);

    // Legacy CSS from Cafeteria theme
    wp_enqueue_style('cafeteria-reset', get_template_directory_uri().'/assets/css/legacy/reset.css', [], '1.7');
    wp_enqueue_style('cafeteria-elements', get_template_directory_uri().'/assets/css/legacy/elements.css', ['cafeteria-reset'], '1.7');
    wp_enqueue_style('cafeteria-general', get_template_directory_uri().'/assets/css/legacy/general.css', ['cafeteria-elements'], '1.7');
    wp_enqueue_style('cafeteria-main', get_template_directory_uri().'/assets/css/legacy/main.css', ['cafeteria-general'], '1.7');
    wp_enqueue_style('cafeteria-responsive', get_template_directory_uri().'/assets/css/legacy/responsive.css', ['cafeteria-main'], '1.7');

    wp_enqueue_script('capuchinhoverde-nav', get_template_directory_uri().'/assets/js/nav.js', [], _CG_VERSION, true);

    // Legacy JS from Cafeteria theme
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('modernizr', get_template_directory_uri().'/assets/js/legacy/modernizr-2.5.3.min.js', ['jquery'], '2.5.3', true);
    wp_enqueue_script('scrollable', get_template_directory_uri().'/assets/js/legacy/scrollable.js', ['jquery'], '1.7', true);
    wp_enqueue_script('jquery-cookie', get_template_directory_uri().'/assets/js/legacy/jquery.cookie.js', ['jquery'], '1.7', true);
    wp_enqueue_script('isotope', get_template_directory_uri().'/assets/js/legacy/jquery.isotope.min.js', ['jquery'], '1.7', true);
    wp_enqueue_script('ale-modules', get_template_directory_uri().'/assets/js/legacy/ale_modules.js', ['jquery'], '1.7', true);
    wp_enqueue_script('ale-scripts', get_template_directory_uri().'/assets/js/legacy/ale_scripts.js', ['jquery', 'ale-modules'], '1.7', true);
    wp_enqueue_script('init-default', get_template_directory_uri().'/assets/js/legacy/InitDefault.js', ['jquery'], '1.7', true);
    wp_enqueue_script('init-home', get_template_directory_uri().'/assets/js/legacy/InitHome.js', ['jquery'], '1.7', true);

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts','cg_scripts');

/**
 * Editor styles
 */
function cg_editor_styles() {
    add_editor_style('assets/css/editor.css');
}
add_action('admin_init','cg_editor_styles');

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
 * Disable query_posts usage guard
 */
function cg_block_query_posts($query){
    // Not a hard block, but ensure developers use WP_Query
}
add_action('pre_get_posts','cg_block_query_posts');

/**
 * Custom Post Types
 */
require get_template_directory() . '/inc/post-types.php';

/**
 * Register block patterns
 */
function cg_register_patterns() {
    register_block_pattern_category('capuchinhoverde', ['label' => esc_html__('Capuchinho Verde','capuchinhoverde')]);
}
add_action('init','cg_register_patterns');
