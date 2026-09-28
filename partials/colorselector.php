<?php
/**
 * Colour scheme selector.
 *
 * Ported from the source theme's partials/colorselector.php, rendered by footer.php when
 * the `skinselector` option is on.
 *
 * The six schemes are light accent variants of the Cafeteria identity (see the README in
 * assets/css/legacy/css/colors/). This is consistent with T006, which removed the
 * *dark AES template* palette — a different thing entirely.
 *
 * The `data-link` value is the one deliberate adaptation: the vendored scripts.js hardcodes
 * `/css/colors/` after the link, so data-link must point at the directory that contains
 * `css/colors/`. See assets/css/legacy/css/colors/README.md for why the path is shaped this
 * way rather than editing a vendored file.
 */

if (!defined('ABSPATH')) {
    exit;
}

$cg_scheme_base = get_template_directory_uri() . '/assets/css/legacy';
?>
<div class="colorselector cf">
    <div class="openbut"></div>
    <div class="bowithoption">
        <div class="title secondfont"><?php esc_html_e( 'Choose the color scheme', 'capuchinhoverde' ); ?></div>
        <div class="boxes cf">
            <?php for ($cg_scheme = 1; $cg_scheme <= 6; $cg_scheme++) : ?>
                <div class="icbox icbox<?php echo (int) $cg_scheme; ?>"
                     data-col="scheme<?php echo (int) $cg_scheme; ?>"
                     data-link="<?php echo esc_url($cg_scheme_base); ?>">
                    <span class="ictrin<?php echo (int) $cg_scheme; ?>"></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
