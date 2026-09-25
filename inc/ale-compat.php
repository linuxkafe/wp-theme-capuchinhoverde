<?php
/**
 * ALETheme Compatibility Layer
 * Provides stub functions for ale_get_option, ale_get_meta, ale_sliders_get_slider
 * so legacy templates work without the full ALETheme framework.
 */

if (!function_exists('ale_get_option')) {
    function ale_get_option($key, $default = '') {
        $options = get_option('aletheme_options', []);
        return $options[$key] ?? get_theme_mod($key, $default);
    }
}

if (!function_exists('ale_get_meta')) {
    function ale_get_meta($key, $post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        $meta = get_post_meta($post_id, '_ale_' . $key, true);
        if ($meta !== '') {
            return $meta;
        }
        $meta = get_post_meta($post_id, $key, true);
        return $meta ?: '';
    }
}

if (!function_exists('ale_sliders_get_slider')) {
    function ale_sliders_get_slider($slug) {
        $slider_post = get_page_by_path($slug, OBJECT, 'cg_slider');
        if (!$slider_post) {
            $slider_post = get_posts([
                'post_type' => 'cg_slider',
                'name' => $slug,
                'posts_per_page' => 1,
            ]);
            $slider_post = $slider_post ? $slider_post[0] : null;
        }
        if (!$slider_post) {
            return false;
        }

        $slides = get_post_meta($slider_post->ID, '_ale_slides', true);
        if (!is_array($slides)) {
            $slides = [];
        }

        return [
            'id' => $slider_post->ID,
            'title' => $slider_post->post_title,
            'slug' => $slider_post->post_name,
            'slides' => $slides,
        ];
    }
}

if (!function_exists('ale_part')) {
    function ale_part($name) {
        $template = get_template_directory() . "/partials/{$name}.php";
        if (file_exists($template)) {
            include $template;
        }
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo esc_html(translate($text, $domain));
    }
}

if (!function_exists('ale_has_option')) {
    function ale_has_option($key) {
        $options = get_option('aletheme_options', []);
        return isset($options[$key]) && $options[$key] !== '';
    }
}

if (!function_exists('ale_get_post_meta')) {
    function ale_get_post_meta($post_id, $key, $single = true) {
        return get_post_meta($post_id, '_ale_' . $key, $single);
    }
}