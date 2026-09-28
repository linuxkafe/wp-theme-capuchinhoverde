<?php
/**
 * Cafeteria Footer Template
 */
?>

<?php if(is_page_template('page-home.php') or is_page_template('template-contact.php')){ ?>
    <section class="footer nokeyframebug" style="<?php
        $cg_fg_bg = ale_get_meta('contactbg');
        if ($cg_fg_bg) { echo 'background-image:url(' . esc_url($cg_fg_bg) . ');'; }
        if (ale_get_option('formcontact') == '1') { echo 'height:540px;'; }
    ?>" >
        <a name="success" href="#success"></a>
        <div class="maskkeyframebug">
            <div class="center-align">
                <a href="#top" class="top"></a>

                <h2 class="firstfont caption"> <?php echo esc_html( ale_get_meta('contacttit') ); ?></h2>
                <?php if(ale_get_option('formcontact') !== '1'){ ?>
                <?php } ?>
                <div class="contacts cf">
                    <div class="col-4">
                        <ul>
                            <li>
                                <div class="icon-adress"></div>
                                <p><?php esc_html_e( 'Address', 'capuchinhoverde' ); ?> // <?php echo esc_html( ale_get_meta('contactaddress') ); ?></p>
                            </li>
                            <li>
                                <div class="icon-phone"></div>
                                <p><?php esc_html_e( 'Telephone nr.', 'capuchinhoverde' ); ?> //  <?php echo esc_html( ale_get_meta('contactphone') ); ?></p>
                            </li>
                            <li>
                                <div class="icon-mail"></div>
                                <p><?php esc_html_e( 'E-Mail', 'capuchinhoverde' ); ?> //  <?php echo esc_html( ale_get_meta('contactemail') ); ?></p>
                            </li>
                        </ul>
                    </div>

                    <div class="col-8">
                            <?php
                            /*
                             * The source echoed the map meta raw, wrapped in a no-op
                             * str_replace('&','&'). Any author-supplied value was therefore
                             * written into the page as markup. It now goes through the same
                             * host-allowlisted, sandboxed embed as template-contact.php — the
                             * behaviour is defined once, in cg_map_embed_url().
                             */
                            cg_render_map_embed( ale_get_meta('contactmap') );
                            ?>
                        </div>
                </div>

                <?php if (ale_get_option('copyrights')) : ?>
                    <p class="copy"><?php echo esc_html( ale_get_option('copyrights') ); ?></p>
                <?php else: ?>
                    <p class="copy"><?php esc_html_e( 'Copyright, All Right Reserved', 'capuchinhoverde' ); ?></p>
                <?php endif; ?>

                <div class="social_icons">
                    <?php
                    /*
                     * Social links: every URL is escaped and every target="_blank" carries
                     * rel="noopener noreferrer". The source emitted all nine raw with a bare
                     * target="_blank", so a Customizer value of "javascript:alert(1)" became
                     * a live link.
                     */
                    foreach (cg_social_links() as $cg_network => $cg_meta_key) {
                        $cg_url = ale_get_option($cg_meta_key);
                        if (!$cg_url) {
                            continue;
                        }
                        printf(
                            '<a href="%s" class="sicon %sic" target="_blank" rel="noopener noreferrer">%s</a>',
                            esc_url($cg_url, ['http', 'https']),
                            esc_attr($cg_network),
                            esc_html(cg_social_label($cg_network))
                        );
                    }
                    ?>
                </div>
            </div>

            <div class="inner-border" style="<?php if(ale_get_option('formcontact') == '1'){echo 'height:529px;';} ?>"></div>
            <div class="background-opacity"></div>
        </div>
    </section>
    <?php if(ale_get_option('preloaderstatus')!=='1'){ ?></div> <!-- /hide --><?php } ?>
<?php } else { ?>
    <section class="footer footer-small">
        <div class="center-align">

            <?php if (ale_get_option('copyrights')) : ?>
                <p class="copy"><?php echo esc_html( ale_get_option('copyrights') ); ?></p>
            <?php else: ?>
                <p class="copy"><?php esc_html_e( 'Copyright, All Right Reserved', 'capuchinhoverde' ); ?></p>
            <?php endif; ?>

            <div class="social_icons">
                <?php
                foreach (cg_social_links() as $cg_network => $cg_meta_key) {
                    $cg_url = ale_get_option($cg_meta_key);
                    if (!$cg_url) {
                        continue;
                    }
                    printf(
                        '<a href="%s" class="sicon %sic" target="_blank" rel="noopener noreferrer">%s</a>',
                        esc_url($cg_url, ['http', 'https']),
                        esc_attr($cg_network),
                        esc_html(cg_social_label($cg_network))
                    );
                }
                ?>
            </div>
        </div>

        <!-- ## ## ## ## ## ## ## ## ## ## -->
        <div class="inner-border"></div>
        <div class="background-opacity"></div>
    </section>
<?php } ?>

<?php if(ale_get_option('skinselector') == "1") { ale_part('colorselector'); } ?>

<!-- Scripts -->
<?php wp_footer(); ?>
</body>
</html>