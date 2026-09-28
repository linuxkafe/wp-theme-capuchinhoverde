<?php
/**
 * Post body content.
 *
 * Ported from the source theme's partials/postcontent.php. The 'aletheme' text domain was
 * corrected to 'capuchinhoverde' (the source's .mo files are not shipped, so every string
 * rendered untranslated). get_the_tags() was replaced with has_tag(), which is the core
 * equivalent that does not suppress the loop's global state.
 */
?>
<div class="text story">
    <?php the_content(); ?>

    <?php if (has_tag()) : ?>
        <p class="tagsphar"><?php the_tags(''); ?></p>
    <?php endif; ?>

    <?php
    wp_link_pages([
        'before' => '<p>' . esc_html__('Pages:', 'capuchinhoverde'),
        'after'  => '</p>',
    ]);
    ?>
</div>
