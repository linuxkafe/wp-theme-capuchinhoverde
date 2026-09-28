<?php
if (!function_exists('cg_posted_on')) {
    function cg_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        $time_string = sprintf($time_string, esc_attr(get_the_date(DATE_W3C)), esc_html(get_the_date()));
        printf('<span class="posted-on">%s</span>', $time_string);
    }
}

if (!function_exists('cg_posted_by')) {
    function cg_posted_by() {
        printf('<span class="byline">%s</span>','<span class="author vcard"><a class="url fn n" href="%1$s">%2$s</a></span>');
    }
}

/**
 * Social networks and the Customizer option that holds each one's URL.
 *
 * The footer rendered nine near-identical inline blocks. They are one loop now, so adding a
 * network is a schema change rather than a copy-paste, and — more importantly — so that all
 * nine get esc_url() and rel="noopener noreferrer" by construction instead of by remembering
 * to add them each time.
 */
if (!function_exists('cg_social_links')) {
    function cg_social_links() {
        return [
            'fbic'    => 'fb',
            'twiic'   => 'twi',
            'pinic'   => 'pin',
            'flickric' => 'flickr',
            'vimic'   => 'vim',
            'linic'   => 'lin',
            'gogic'   => 'gog',
            'ytbic'   => 'ytb',
            'instaic' => 'insta',
        ];
    }
}

if (!function_exists('cg_social_label')) {
    function cg_social_label($network) {
        $labels = [
            'fbic'     => __('Facebook', 'capuchinhoverde'),
            'twiic'    => __('Twitter', 'capuchinhoverde'),
            'pinic'    => __('Pinterest', 'capuchinhoverde'),
            'flickric' => __('Flickr', 'capuchinhoverde'),
            'vimic'    => __('Vimeo', 'capuchinhoverde'),
            'linic'    => __('LinkedIn', 'capuchinhoverde'),
            'gogic'    => __('Google+', 'capuchinhoverde'),
            'ytbic'    => __('Youtube', 'capuchinhoverde'),
            'instaic'  => __('Instagram', 'capuchinhoverde'),
        ];
        return $labels[$network] ?? $network;
    }
}

/**
 * Render a map embed from a stored address.
 *
 * Single definition of "what counts as a map", shared by template-contact.php and footer.php.
 * The footer's address is site-wide (a Customizer option) where the contact page's is per
 * post, so the two used to diverge — the footer echoed the value raw.
 */
if (!function_exists('cg_render_map_embed')) {
    function cg_render_map_embed($address, $height = '400px') {
        $address = trim((string) $address);
        if ('' === $address) {
            return;
        }

        $url = cg_map_embed_url($address);
        if (!$url) {
            printf(
                '<p class="cg-map-unresolved">%s</p>',
                esc_html__('This map address could not be resolved to a map provider.', 'capuchinhoverde')
            );
            return;
        }

        printf(
            '<div class="cg-map"><iframe src="%s" width="100%%" height="%s" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" sandbox="allow-scripts allow-same-origin allow-popups" title="%s"></iframe></div>',
            esc_url($url),
            esc_attr($height),
            esc_attr__('Map', 'capuchinhoverde')
        );
    }
}
