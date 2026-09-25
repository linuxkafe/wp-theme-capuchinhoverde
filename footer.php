<?php
/**
 * Cafeteria Footer Template
 */

if (ale_get_meta('footerbg')) {
    echo '<section class="footer nokeyframebug" style="background-image: url('.ale_get_meta('footerbg').');">';
} else {
    echo '<section class="footer nokeyframebug">';
}
?>
    <a name="success" href="#success"></a>
    <div class="maskkeyframebug">
        <div class="center-align">
            <a href="#top" class="top"></a>

            <h2 class="firstfont caption"><?php _e('Contacte a Capuchinho','aletheme'); ?></h2>

            <div class="contacts cf">
                <div class="col-4">
                    <ul>
                        <li>
                            <div class="icon-adress"></div>
                            <p><?php echo ale_get_meta('footeraddress'); ?></p>
                        </li>
                        <li>
                            <div class="icon-phone"></div>
                            <p><?php echo ale_get_meta('footerphone'); ?></p>
                        </li>
                        <li>
                            <div class="icon-mail"></div>
                            <p><?php echo ale_get_meta('footeremail'); ?></p>
                        </li>
                    </ul>
                </div>

                <div class="col-8">
                    <?php if (ale_get_meta('footergoogle')) { ?>
                        <iframe src="<?php echo ale_get_meta('footergoogle'); ?>" width="600" height="450" frameborder="0" style="border:0" allowfullscreen></iframe>
                    <?php } else { ?>
                        <p><?php _e('Mapa não configurado.','aletheme'); ?></p>
                    <?php } ?>
                </div>
            </div>

            <?php if (ale_get_meta('footerform') == 'on') { ?>
                <form action="#success" method="post" class="cf">
                    <div class="col-4">
                        <input type="text" name="contact[name]" placeholder="<?php _e('Nome','aletheme'); ?>" required="required" />
                    </div>
                    <div class="col-4">
                        <input type="email" name="contact[email]" placeholder="<?php _e('Email','aletheme'); ?>" required="required" />
                    </div>
                    <div class="col-4">
                        <input type="tel" name="contact[phone]" placeholder="<?php _e('Telefone','aletheme'); ?>" />
                    </div>
                    <div class="col-8">
                        <textarea name="contact[message]" placeholder="<?php _e('Mensagem','aletheme'); ?>" required="required"></textarea>
                    </div>
                    <div class="col-8">
                        <input type="submit" name="contact" value="<?php _e('Enviar','aletheme'); ?>" />
                    </div>
                </form>
            <?php } ?>

            <p class="copy"><?php echo ale_get_meta('footercopyright'); ?></p>

            <div class="social_icons">
                <?php if (ale_get_meta('footerfacebook')) { ?>
                    <a href="<?php echo ale_get_meta('footerfacebook'); ?>" class="sicon fbic" target="_blank"><?php _e('Facebook','aletheme'); ?></a>
                <?php } ?>
                <?php if (ale_get_meta('footerinstagram')) { ?>
                    <a href="<?php echo ale_get_meta('footerinstagram'); ?>" class="sicon instaic" target="_blank"><?php _e('Instagram','aletheme'); ?></a>
                <?php } ?>
            </div>
        </div>

        <div class="inner-border"></div>
        <div class="background-opacity"></div>
    </div>
</section>

<?php wp_footer(); ?>