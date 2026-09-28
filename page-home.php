<?php
/**
 * Template name: Home
 *
 * Ported from the source theme's page-home.php.
 *
 * Changes made during the port (2026-09-27) — each was verified as broken against a live
 * WordPress 6.6 instance:
 *
 *  - `query_posts('&post_type=gallery…')` queried a post type this theme does not have
 *    (it is `cg_gallery`), so the home gallery was always empty. Replaced with WP_Query.
 *    query_posts() is also forbidden by docs/REQUIREMENTS.md and clobbers the main query.
 *  - The gallery filter read the `gallery-category` taxonomy; this theme registers
 *    `cg_gallery_category`, so the filter rendered no terms.
 *  - `get_the_post_thumbnail($post->ID, 'gallery-thumba')` used an unregistered size.
 *    Sizes are registered by cg_image_size() in functions.php as `cg-gallery-thumba`.
 *  - The 'aletheme' text domain was used throughout. The source theme's .mo files are not
 *    shipped, so every string rendered untranslated; the domain is now 'capuchinhoverde'.
 *  - Output was unescaped throughout. Slider image/title/description/url are now escaped.
 *  - `ale_send_contact()` was called but undefined → HTTP 500 on every POST.
 */

$cg_contact_result = null;
if ( isset( $_POST['cg_contact'] ) ) {
    // Verify the nonce before the handler touches the payload. The check belongs here,
    // not inside ale_send_contact(), which only ever sees the data array.
    if ( isset( $_POST['cg_contact_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cg_contact_nonce'] ) ), 'cg_contact' )
    ) {
        $cg_contact_result = ale_send_contact(
            wp_unslash( $_POST['cg_contact'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised
        );
    } else {
        $cg_contact_result = [
            'status'  => 'error',
            'message' => __( 'Your submission expired. Please try again.', 'capuchinhoverde' ),
            'fields'  => [],
        ];
    }
}

get_header();

$cg_slider = ale_sliders_get_slider( ale_get_option( 'homeslugfull', 'home' ) );
?>

<?php
// Every page needs exactly one h1. The source theme had none on the home template
// (it used h2 for the slider caption), which fails the WCAG 2.1 AA claim in
// docs/REQUIREMENTS.md and was caught by tests/e2e/smoke.spec.ts. Cafeteria's home
// design is a full-bleed slider with no visible page title, so the h1 is rendered
// for assistive technology only rather than inventing a visible heading that would
// break the ported layout.
?>
<h1 class="cg-visually-hidden"><?php echo esc_html( get_the_title() ); ?></h1>

<!-- Gallery / Slider -->
<div class="slider cf" id="cg-slider">
    <div class="triang top"></div>
    <div class="triang bot"></div>
    <ul class="slides">
        <?php if ( $cg_slider && ! empty( $cg_slider['slides'] ) ) : ?>
            <?php foreach ( $cg_slider['slides'] as $cg_slide ) : ?>
                <?php $cg_image = ! empty( $cg_slide['image'] ) ? esc_url( $cg_slide['image'] ) : ''; ?>
                <li<?php echo $cg_image ? ' style="background-image: url(\'' . esc_attr( $cg_image ) . '\');"' : ''; ?>>
                    <div class="box">
                        <?php if ( ! empty( $cg_slide['title'] ) ) : ?>
                            <h2 class="firstfont caption colormain"><?php echo esc_html( $cg_slide['title'] ); ?></h2>
                        <?php endif; ?>
                        <?php if ( ! empty( $cg_slide['description'] ) ) : ?>
                            <p class="text"><?php echo esc_html( $cg_slide['description'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $cg_slide['url'] ) ) : ?>
                            <a href="<?php echo esc_url( $cg_slide['url'] ); ?>"><?php esc_html_e( 'Read More', 'capuchinhoverde' ); ?></a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <li class="cg-slider-empty">
                <div class="box">
                    <h2 class="firstfont caption colormain"><?php esc_html_e( 'Welcome', 'capuchinhoverde' ); ?></h2>
                    <p class="text"><?php esc_html_e( 'No slider has been configured yet. Create a Slider and set it as the home slider.', 'capuchinhoverde' ); ?></p>
                </div>
            </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Services -->
<?php if ( ale_get_meta( 'serviceonhome' ) === 'on' ) : ?>
    <div>
        <article class="our-services cf">
            <h2 class="firstfont caption colormain"><?php echo esc_html( ale_get_meta( 'servtit' ) ); ?></h2>
            <div class="center-align">
                <div class="line-cake">
                    <div class="cake"></div>
                    <div class="line left"></div>
                    <div class="line right"></div>
                </div>
            </div>

            <div class="center-align content cf">
                <?php
                for ( $cg_i = 1; $cg_i <= 4; $cg_i++ ) :
                    $cg_image = ale_get_meta( 'servic' . $cg_i );
                    $cg_title = ale_get_meta( 'servtit' . $cg_i );
                    $cg_link  = ale_get_meta( 'servlink' . $cg_i );
                    $cg_desc  = ale_get_meta( 'servdesc' . $cg_i );
                    if ( ! $cg_image && ! $cg_title && ! $cg_desc ) {
                        continue;
                    }
                    ?>
                    <div class="col-3">
                        <div class="circle">
                            <?php if ( $cg_image ) : ?>
                                <div class="img" style="background-image: url('<?php echo esc_attr( $cg_image ); ?>');"></div>
                            <?php endif; ?>
                        </div>
                        <h2 class="firstfont caption colormain">
                            <?php if ( $cg_link ) : ?><a href="<?php echo esc_url( $cg_link ); ?>"><?php endif; ?>
                            <?php echo esc_html( $cg_title ); ?>
                            <?php if ( $cg_link ) : ?></a><?php endif; ?>
                        </h2>
                        <p class="text text-center"><?php echo esc_html( $cg_desc ); ?></p>
                    </div>
                <?php endfor; ?>
            </div>
        </article>
    </div>
<?php else : ?>
    <div class="heightonhome cf"></div>
<?php endif; ?>

<!-- Home Gallery -->
<?php if ( ale_get_meta( 'galleryonhome' ) === 'on' ) : ?>
    <?php
    $cg_gallery_bg = ale_get_meta( 'galbg' );
    $cg_categories  = get_terms( [
        'taxonomy'   => 'cg_gallery_category',
        'hide_empty' => true,
        'orderby'    => 'name',
    ] );
    $cg_gallery = new WP_Query( [
        'post_type'           => 'cg_gallery',
        'posts_per_page'      => 8,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ] );
    ?>
    <section class="home-gallery nokeyframebug"<?php echo $cg_gallery_bg ? ' style="background-image: url(' . esc_attr( $cg_gallery_bg ) . ');"' : ''; ?>>
        <div class="maskkeyframebug">
            <div class="center-align">
                <a href="#top" class="top"></a>
                <div class="filterwrapper">
                    <div id="filters" class="filter-line cf">
                        <p><?php esc_html_e( 'FILTER BY', 'capuchinhoverde' ); ?>:</p>
                        <a data-filter="*" href="#" class="active">
                            <span class="triangle"></span>
                            <span class="ref"><?php esc_html_e( 'All', 'capuchinhoverde' ); ?></span>
                        </a>
                        <?php if ( ! is_wp_error( $cg_categories ) ) : ?>
                            <?php foreach ( $cg_categories as $cg_cat ) : ?>
                                <a href="#" data-filter=".<?php echo esc_attr( $cg_cat->slug ); ?>">
                                    <span class="triangle"></span>
                                    <span class="ref"><?php echo esc_html( $cg_cat->name ); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="galcontainer" class="gallery cf">
                    <?php if ( $cg_gallery->have_posts() ) : ?>
                        <?php while ( $cg_gallery->have_posts() ) : ?>
                            <?php $cg_gallery->the_post(); ?>
                            <?php $cg_terms = get_the_terms( get_the_ID(), 'cg_gallery_category' ); ?>
                            <div class="col-3 element <?php echo esc_attr( is_array( $cg_terms ) ? implode( ' ', wp_list_pluck( $cg_terms, 'slug' ) ) : '' ); ?>">
                                <div class="background">
                                    <a href="<?php the_permalink(); ?>">
                                        <div class="hover"></div>
                                        <?php echo get_the_post_thumbnail( get_the_ID(), 'cg-gallery-thumba' ); ?>
                                    </a>
                                    <div class="pic"></div>
                                    <a class="look" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Take a look', 'capuchinhoverde' ); ?></a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <?php ale_part( 'notfound' ); ?>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            </div>

            <div class="inner-border"></div>
            <div class="background-opacity"></div>
        </div>
    </section>
<?php else : ?>
    <div class="heightonhome cf"></div>
<?php endif; ?>

<!-- Contact -->
<?php if ( ale_get_meta( 'contactonhome' ) === 'on' ) : ?>
    <section class="home-contact cf">
        <h2 class="firstfont caption colormain"><?php echo esc_html( ale_get_meta( 'contacttit' ) ); ?></h2>

        <?php if ( $cg_contact_result ) : ?>
            <div class="cg-contact-result <?php echo 'success' === $cg_contact_result['status'] ? 'is-success' : 'is-error'; ?>" role="status">
                <p><?php echo esc_html( $cg_contact_result['message'] ); ?></p>
            </div>
        <?php endif; ?>

        <?php ale_part( 'contactform' ); ?>
    </section>
<?php endif; ?>

<?php get_footer(); ?>
