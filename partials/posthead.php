<?php
/**
 * Post / single-item heading, with slider support.
 *
 * Ported from the source theme's partials/posthead.php.
 *
 * Changes made during the port:
 *
 *  - Thumbnail sizes were 'post-slider' and 'events-slider', which the source generated from
 *    its own config. This theme registers them as 'cg-post-slider' and 'cg-event-slider'
 *    (see cg_image_sizes() in functions.php); with the old names
 *    get_the_post_thumbnail() silently fell back to full-size images.
 *  - The gallery post format queried attachment meta `_ale_hide_from_gallery`, a key the
 *    ALETheme metaboxes wrote. Those metaboxes are out of scope, so the filter is now on the
 *    theme's own `_cg_hide_from_gallery` key — and a gallery with no marked images still
 *    renders every attachment rather than nothing.
 *  - Output is escaped. The source printed titles, authors and categories raw.
 *
 * Opens a <div> closed by partials/postfooter.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

$cg_post_id  = get_the_ID();
$cg_posttype = get_post_type();

// Map the post type to its registered slider size, with a defined fallback.
$cg_thumb_sizes = [
    'post'     => 'cg-post-slider',
    'cg_event' => 'cg-event-slider',
    'cg_menu'  => 'cg-menu-slider',
];
$cg_thumbsize = $cg_thumb_sizes[$cg_posttype] ?? 'large';
?>
<div <?php post_class(); ?> id="post-<?php echo esc_attr((string) $cg_post_id); ?>" data-post-id="<?php echo esc_attr((string) $cg_post_id); ?>">
    <h2 class="caption firstfont colormain"><?php the_title(); ?></h2>
    <h3 class="firstfont info">
        <?php esc_html_e( 'Posted on', 'capuchinhoverde' ); ?> - <?php echo esc_html(get_the_date()); ?>
        <?php if (get_the_author() !== '') : ?>
            / <?php esc_html_e( 'Author', 'capuchinhoverde' ); ?> - <?php the_author(); ?>
        <?php endif; ?>
        <?php if (has_category()) : ?>
            / <?php esc_html_e( 'Category', 'capuchinhoverde' ); ?> - <?php echo esc_html(get_the_category_list(', ')); ?>
        <?php endif; ?>
    </h3>

    <?php
    if (has_post_format('gallery')) {
        /*
         * Gallery post format: render the post's attachments as a slider.
         *
         * `_cg_hide_from_gallery` lets an editor exclude an attachment. Attachments with no
         * value are included, so the filter is additive rather than requiring every image to
         * be explicitly marked.
         */
        $cg_attachments = get_posts([
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_status'    => 'inherit',
            'order'          => 'ASC',
            'orderby'        => 'menu_order ID',
            'post_parent'    => $cg_post_id,
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'relation' => 'OR',
                [
                    'key'     => '_cg_hide_from_gallery',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_cg_hide_from_gallery',
                    'value'   => '0',
                    'compare' => '=',
                ],
            ],
        ]);
        ?>
        <div class="story-slider cf">
            <div class="outlines"></div>
            <div class="shadow"></div>
            <ul class="slides">
                <?php foreach ($cg_attachments as $cg_attachment) : ?>
                    <li><?php echo wp_get_attachment_image($cg_attachment->ID, $cg_thumbsize); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php } elseif (has_post_thumbnail()) { ?>
        <div class="story-slider thumba cf">
            <div class="outlines"></div>
            <div class="shadow"></div>
            <?php echo get_the_post_thumbnail($cg_post_id, $cg_thumbsize); ?>
        </div>
    <?php } ?>
