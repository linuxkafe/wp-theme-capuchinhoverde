<?php
/**
 * Language switcher slot.
 *
 * Ported from the source theme's partials/lang.php, which rendered a placeholder telling
 * the developer to add their own switcher code. That placeholder is preserved — inventing a
 * switcher would be a feature nobody asked for — but the text domain is corrected and the
 * output is escaped.
 *
 * This is the natural home for a WPML/Polylang language switcher in a child theme.
 */
?>
<div class="langswitcher">
    <div class="center-align">
        <span><?php esc_html_e( 'Add your language switcher code in partials/lang.php', 'capuchinhoverde' ); ?></span>
    </div>
</div>
