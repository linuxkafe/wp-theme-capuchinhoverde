<?php
/**
 * Archive list.
 *
 * The source called ale_archives(), a 40-line ALETheme function (general.php:1064) built on
 * the framework's own year/month helpers. Porting the framework's version would pull in
 * ale_archives_get_years() and ale_archives_get_months() for no benefit: WordPress core has
 * done this since 2.1. This is the core function, formatted to match the source's markup.
 */
?>
<div id="archives" class="cf">
    <?php
    wp_get_archives([
        'type'    => 'monthly',
        'format'  => 'custom',
        'before'  => '<span class="month">',
        'after'   => '</span>',
        'link_before' => '',
        'link_after'  => '',
    ]);
    ?>
</div>
