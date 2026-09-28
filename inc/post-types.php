<?php
/**
 * Custom Post Types for Capuchinho Verde
 */

function cg_register_post_types() {
    // Menu
    register_post_type('cg_menu', [
        'labels' => [
            'name' => esc_html__('Menu Items','capuchinhoverde'),
            'singular_name' => esc_html__('Menu Item','capuchinhoverde')
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title','editor','thumbnail','excerpt'],
        'menu_icon' => 'dashicons-food',
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'menu']
    ]);

    register_taxonomy('cg_menu_category', 'cg_menu', [
        'labels' => ['name'=>esc_html__('Menu Categories','capuchinhoverde')],
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug'=>'menu-category']
    ]);

    // Gallery
    register_post_type('cg_gallery', [
        'labels' => [
            'name' => esc_html__('Gallery','capuchinhoverde'),
            'singular_name' => esc_html__('Gallery Item','capuchinhoverde')
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title','editor','thumbnail'],
        'menu_icon' => 'dashicons-format-gallery',
        'show_in_rest' => true,
        'rewrite' => ['slug'=>'gallery']
    ]);

    register_taxonomy('cg_gallery_category', 'cg_gallery', [
        'labels' => ['name'=>esc_html__('Gallery Categories','capuchinhoverde')],
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug'=>'gallery-category']
    ]);

    // Events
    register_post_type('cg_event', [
        'labels' => [
            'name' => esc_html__('Events','capuchinhoverde'),
            'singular_name' => esc_html__('Event','capuchinhoverde')
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title','editor','thumbnail','excerpt'],
        'menu_icon' => 'dashicons-calendar-alt',
        'show_in_rest' => true,
        'rewrite' => ['slug'=>'events']
    ]);

    register_taxonomy('cg_event_category', 'cg_event', [
        'labels' => ['name'=>esc_html__('Event Categories','capuchinhoverde')],
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug'=>'event-category']
    ]);

    // Sliders.
    //
    // page-home.php calls ale_sliders_get_slider(), which queries a 'cg_slider' post type.
    // Without this registration the home slider could never render — silently, because
    // ale_sliders_get_slider() returns false and the template skips the markup.
    register_post_type('cg_slider', [
        'labels' => [
            'name' => esc_html__('Sliders','capuchinhoverde'),
            'singular_name' => esc_html__('Slider','capuchinhoverde')
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => ['title'],
        'rewrite' => false,
        'capability_type' => 'post',
    ]);
}
add_action('init','cg_register_post_types');

/**
 * Flush permalinks when the theme is activated.
 *
 * The CPTs above are registered at runtime, so their /menu/, /gallery/ and /events/
 * rewrite rules only exist after a flush. Without it those URLs fall through to the front
 * page query and return HTTP 200 with the wrong content — a silent failure, not an error.
 * Version-gated so the check costs nothing once it has run.
 */
function cg_flush_rewrites() {
    if ( get_option('cg_rewrite_version') === _CG_VERSION ) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('cg_rewrite_version', _CG_VERSION);
}
add_action('after_switch_theme', 'cg_flush_rewrites');
