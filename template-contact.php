<?php
/**
 * Template Name: Template Contact
 *
 * Ported from the source theme's template-contact.php.
 *
 * Changes made during the port:
 *
 *  - The source posted to a form under `$_POST['contact']` and called ale_send_contact()
 *    with no nonce, no validation and no rate limit. All three now live in
 *    inc/ale-compat.php, and the nonce is verified here before the handler runs.
 *  - The form markup is now partials/contactform.php, shared with the home page, so the two
 *    copies cannot drift.
 *  - The source rendered only the page content — no contact details at all, despite the
 *    `contactemail` / `contactphone` / `contactaddress` / `contactmap` meta keys existing
 *    in the source's own config for exactly this page. Those are rendered here.
 *  - Output is escaped throughout.
 */

if (!defined('ABSPATH')) {
    exit;
}

$cg_contact_result = null;
if (isset($_POST['cg_contact'])) {
    // Verify before the handler sees the payload; ale_send_contact() only gets the array.
    if (isset($_POST['cg_contact_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cg_contact_nonce'])), 'cg_contact')
    ) {
        $cg_contact_result = ale_send_contact(
            wp_unslash($_POST['cg_contact']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised
        );
    } else {
        $cg_contact_result = [
            'status'  => 'error',
            'message' => __('Your submission expired. Please try again.', 'capuchinhoverde'),
            'fields'  => [],
        ];
    }
}

// A page using this template is the right home for the contact details; fall back to the
// site-wide option if the page leaves them blank.
$cg_contact_email   = ale_get_meta('contactemail', get_the_ID()) ?: ale_get_option('contact_email', get_option('admin_email'));
$cg_contact_phone   = ale_get_meta('contactphone', get_the_ID());
$cg_contact_address = ale_get_meta('contactaddress', get_the_ID());
$cg_contact_map     = ale_get_meta('contactmap', get_the_ID());
$cg_contact_top     = ale_get_meta('contacttopimg', get_the_ID());
$cg_contact_bg      = ale_get_meta('contactbg', get_the_ID());

get_header();
?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article class="contact-us">
        <?php // Exactly one h1 per page; see template-about.php for the reasoning. ?>
        <h1 class="cg-visually-hidden"><?php echo esc_html(get_the_title()); ?></h1>
        <h2 class="firstfont caption colormain"><?php the_title(); ?></h2>
        <div class="center-align">
            <div class="line-cake">
                <div class="cake"></div>
                <div class="line left"></div>
                <div class="line right"></div>
            </div>
        </div>

        <div class="center-align content">
            <?php if ($cg_contact_top) : ?>
                <div class="img">
                    <img src="<?php echo esc_url($cg_contact_top); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                    <div class="shadows"></div>
                </div>
            <?php endif; ?>

            <div class="text">
                <?php the_content(); ?>
            </div>

            <?php if ($cg_contact_phone || $cg_contact_address || $cg_contact_email) : ?>
                <ul class="cg-contact-details">
                    <?php if ($cg_contact_address) : ?>
                        <li class="cg-detail-address"><?php echo esc_html($cg_contact_address); ?></li>
                    <?php endif; ?>
                    <?php if ($cg_contact_phone) : ?>
                        <li class="cg-detail-phone">
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $cg_contact_phone)); ?>">
                                <?php echo esc_html($cg_contact_phone); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($cg_contact_email) : ?>
                        <li class="cg-detail-email">
                            <a href="mailto:<?php echo esc_attr($cg_contact_email); ?>"><?php echo esc_html($cg_contact_email); ?></a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php if ($cg_contact_map) : ?>
                <div class="cg-contact-map">
                    <?php
                    /*
                     * Only a Google Maps embed URL is accepted, and it is rendered as an
                     * iframe with a restrictive sandbox. The raw `contactmap` value is
                     * arbitrary text in the source's config, so it is not echoed into the
                     * page and reinterpreted as markup.
                     */
                    $cg_map_url = esc_url($cg_contact_map, ['https']);
                    $cg_map_host = $cg_map_url ? wp_parse_url($cg_map_url, PHP_URL_HOST) : '';
                    if ($cg_map_host && preg_match('/(^|\.)google\.[a-z.]+$|(^|\.)googleapis\.com$|(^|\.)openstreetmap\.org$/', (string) $cg_map_host)) {
                        printf(
                            '<iframe src="%s" width="100%%" height="400" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" sandbox="allow-scripts allow-same-origin allow-popups" title="%s"></iframe>',
                            esc_url($cg_map_url),
                            esc_attr__( 'Map', 'capuchinhoverde' )
                        );
                    } else {
                        echo '<p class="cg-map-invalid">' . esc_html__( 'The map address is not a recognised map URL, so it was not embedded.', 'capuchinhoverde' ) . '</p>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($cg_contact_result) : ?>
                <div class="cg-contact-result <?php echo 'success' === $cg_contact_result['status'] ? 'is-success' : 'is-error'; ?>" role="status">
                    <p><?php echo esc_html($cg_contact_result['message']); ?></p>
                </div>
            <?php endif; ?>

            <?php ale_part('contactform'); ?>
        </div>
    </article>
<?php endwhile; else : ?>
    <?php ale_part('notfound'); ?>
<?php endif; ?>
<?php get_footer(); ?>
