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
}
add_action('init','cg_register_post_types');
