<?php
/**
 * ALETheme Compatibility Layer
 *
 * Provides the small set of ale_* helpers that the ported Cafeteria templates call.
 *
 * Deliberate omissions — each was present before 2026-09-27 and was a defect:
 *
 *  - `_e()` redefinition. This file previously wrapped a redefinition of a WordPress
 *    CORE function in `if (!function_exists('_e'))`. Core always loads first, so the
 *    branch was dead code that misrepresented the port and would have become a fatal
 *    "cannot redeclare _e()" under any load-order change. Templates must call the core
 *    `_e()` directly. The ported templates used the 'aletheme' text domain; that was
 *    rewritten to 'capuchinhoverde' because the source theme's .mo files are not shipped.
 *  - `get_page_by_path()`. Deprecated since WP 6.2. Replaced with get_posts() below.
 *  - `ale_get_post_meta()`, a duplicate of ale_get_meta() with a different signature.
 *    Nothing in the theme called it.
 *
 * Note the meta prefix: the source framework stored post meta unprefixed and namespaced
 * it by CPT. This port uses an explicit `_cg_` prefix, so any meta written by the source
 * theme is NOT read here. See docs/PARITY.md §8.
 */

if (!function_exists('ale_get_option')) {
    /**
     * Resolve a theme option.
     *
     * The source theme's options framework (aletheme/options/*) is a plugin's worth of
     * machinery and is not being ported (docs/PARITY.md §6). Options therefore resolve
     * from the theme_mods / Customizer instead, which is the modern equivalent.
     */
    function ale_get_option($key, $default = '') {
        $mods = get_theme_mods();
        if (is_array($mods) && array_key_exists($key, $mods) && $mods[$key] !== '') {
            return $mods[$key];
        }
        return $default;
    }
}

if (!function_exists('ale_has_option')) {
    function ale_has_option($key) {
        $value = ale_get_option($key, '');
        return $value !== '' && $value !== null;
    }
}

if (!function_exists('ale_get_meta')) {
    /**
     * Read a post meta value written by the theme's editor UI.
     *
     * Falls back to the unprefixed key so content authored against the source theme's
     * conventions still resolves.
     */
    function ale_get_meta($key, $post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        if (!$post_id) {
            return '';
        }
        $value = get_post_meta($post_id, '_cg_' . $key, true);
        if ($value === '' || $value === false) {
            $value = get_post_meta($post_id, $key, true);
        }
        return $value === false ? '' : $value;
    }
}

if (!function_exists('ale_sliders_get_slider')) {
    /**
     * Fetch a slider and its slides.
     *
     * Replaces the previous get_page_by_path() call, which is deprecated since WP 6.2.
     * Returns null (not false) so callers can distinguish "no slider" from "empty slider".
     */
    function ale_sliders_get_slider($slug) {
        if (empty($slug)) {
            return null;
        }

        $slider_posts = get_posts([
            'post_type'      => 'cg_slider',
            'name'           => sanitize_title($slug),
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ]);

        if (!$slider_posts) {
            return null;
        }

        $slider_post = $slider_posts[0];
        $slides      = get_post_meta($slider_post->ID, '_cg_slides', true);
        /*
         * cg_sanitize_meta_json() stores an array, but rows written before that was fixed
         * (aes/tickets/T015, B4) hold a JSON string — and the seed script may still be
         * pointed at a pre-existing database. Decode those rather than discarding them,
         * so a slider is not lost merely because of when it was saved.
         */
        if ( is_string( $slides ) ) {
            $decoded   = json_decode( $slides, true );
            $slides    = is_array( $decoded ) ? $decoded : [];
        }
        if ( !is_array( $slides ) ) {
            $slides = [];
        }

        return [
            'id'     => $slider_post->ID,
            'title'  => $slider_post->post_title,
            'slug'   => $slider_post->post_name,
            'slides' => $slides,
        ];
    }
}

if (!function_exists('ale_part')) {
    /**
     * Include a template partial.
     *
     * Before 2026-09-27 this included from a `partials/` directory that does not exist
     * in this theme, so every ale_part() call was a silent no-op — including
     * ale_part('notfound') on the home page. It now reports the miss via a filterable
     * hook instead of failing silently.
     */
    function ale_part($name, $args = []) {
        $name  = sanitize_file_name($name);
        $file  = get_template_directory() . "/partials/{$name}.php";
        $found = file_exists($file);

        /**
         * Fires around a partial include.
         *
         * @param string $name  Partial name requested.
         * @param bool   $found Whether the partial exists.
         */
        do_action('ale_part', $name, $found);

        if (!$found) {
            return;
        }
        if (is_array($args) && $args) {
            extract($args, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        }
        include $file;
    }
}

if (!function_exists('ale_send_contact')) {
    /**
     * Handle a contact form submission.
     *
     * page-home.php called ale_send_contact() on POST but nothing defined it: every
     * submission returned HTTP 500 (verified 2026-09-27). This is a real handler, not a
     * stub — it validates, rate-limits by transient, and reports via a redirect flag so
     * the form can render the result without re-POSTing.
     */
    function ale_send_contact($data) {
        $result = [
            'status'  => 'error',
            'message' => __('Something went wrong. Please try again.', 'capuchinhoverde'),
            'fields'  => [],
        ];

        if (!is_array($data)) {
            return $result;
        }

        // Honeypot: a real browser leaves this empty. Silently "succeed" so bots do not
        // learn they were detected, and so no mail is sent.
        if (!empty($data['cg_website'])) {
            $result['status']  = 'success';
            $result['message'] = __('Thank you. Your message has been sent.', 'capuchinhoverde');
            return $result;
        }

        $name    = isset($data['name'])    ? sanitize_text_field($data['name'])    : '';
        $email   = isset($data['email'])   ? sanitize_email($data['email'])       : '';
        $subject = isset($data['subject']) ? sanitize_text_field($data['subject']) : '';
        $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';

        $errors = [];
        if ($name === '') {
            $errors['name'] = __('Please enter your name.', 'capuchinhoverde');
        }
        if (!is_email($email)) {
            $errors['email'] = __('Please enter a valid email address.', 'capuchinhoverde');
        }
        if ($message === '') {
            $errors['message'] = __('Please enter a message.', 'capuchinhoverde');
        }
        if ($errors) {
            $result['fields']  = $errors;
            $result['message'] = __('Please correct the highlighted fields.', 'capuchinhoverde');
            return $result;
        }

        // Rate limit: one accepted submission per email per 10 minutes.
        $throttle_key = 'cg_contact_' . md5(strtolower($email));
        if (get_transient($throttle_key)) {
            $result['message'] = __('You already sent a message recently. Please try again later.', 'capuchinhoverde');
            return $result;
        }

        $to      = ale_get_option('contact_email', get_option('admin_email'));
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', $name, $email),
            sprintf('Reply-To: %s <%s>', $name, $email),
        ];

        $sent = wp_mail(
            $to,
            $subject !== '' ? $subject : sprintf('[%s] Contact form', get_bloginfo('name')),
            $message,
            $headers
        );

        if ($sent) {
            set_transient($throttle_key, 1, 10 * MINUTE_IN_SECONDS);
            $result['status']  = 'success';
            $result['message'] = __('Thank you. Your message has been sent.', 'capuchinhoverde');
        } else {
            $result['message'] = __('The message could not be sent. Please email us directly.', 'capuchinhoverde');
        }

        return $result;
    }
}
