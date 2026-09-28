<?php
/**
 * Template Name: Template About
 *
 * Ported from the source theme's template-about.php (164 lines).
 *
 * The source repeated the same four-column block by hand four times for the team, and again
 * four times for the price items — 8 near-identical 20-line blocks. They are loops here.
 * The markup the loops emit is identical, so the rendered output matches the source.
 *
 * The source's four team members and four price items are unrolled, not repeated, so the
 * row count is fixed by the markup rather than by a configurable number. If that ever needs
 * to be configurable, the schema in inc/meta.php and the two loops here must both change —
 * scripts/check-key-coverage.sh compares their loop bounds so they cannot drift silently.
 *
 * KEY NAMING: the source reads 'menutitic1' (a typo for "menu title") for the item-top label.
 * This port uses 'menutitle'. See the note in inc/meta.php.
 *
 * All output is escaped. The source echoed every meta value raw into HTML and into an
 * <img src> attribute.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

    <?php
    // Every page needs exactly one h1. The source had none on this template — it used h2
    // for the team, price and page headings — which fails the WCAG 2.1 AA claim in
    // docs/REQUIREMENTS.md. The title is already shown in the Details block, so the h1 is
    // for assistive technology only rather than adding a second visible heading.
    ?>
    <h1 class="cg-visually-hidden"><?php echo esc_html(get_the_title()); ?></h1>

    <?php // Print only sections that have content, so a half-filled page is not a wall of empties. ?>
    <?php
    $cg_team_title   = ale_get_meta('teamtit');
    $cg_team_members = [];
    for ($cg_i = 1; $cg_i <= 4; $cg_i++) {
        $cg_photo = ale_get_meta('teamphoto' . $cg_i);
        $cg_name  = ale_get_meta('teamname' . $cg_i);
        $cg_desc  = ale_get_meta('teamdesc' . $cg_i);
        if ($cg_photo || $cg_name || $cg_desc) {
            $cg_team_members[] = compact('cg_photo', 'cg_name', 'cg_desc');
        }
    }
    ?>

    <?php if ($cg_team_title || $cg_team_members) : ?>
        <!-- Our Team -->
        <article class="our-team">
            <?php if ($cg_team_title) : ?>
                <h2 class="firstfont caption colormain"><?php echo esc_html($cg_team_title); ?></h2>
                <div class="center-align">
                    <div class="line-cake">
                        <div class="cake"<?php echo cg_cake_style(); ?>></div>
                        <div class="line left"></div>
                        <div class="line right"></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($cg_team_members) : ?>
                <div class="center-align content cf">
                    <?php foreach ($cg_team_members as $cg_member) : ?>
                        <div class="col-3">
                            <div class="circle">
                                <?php if ($cg_member['cg_photo']) : ?>
                                    <img src="<?php echo esc_url($cg_member['cg_photo']); ?>"
                                         alt="<?php echo esc_attr($cg_member['cg_name']); ?>" />
                                <?php endif; ?>
                            </div>
                            <?php if ($cg_member['cg_name']) : ?>
                                <h2 class="firstfont caption colormain"><?php echo esc_html($cg_member['cg_name']); ?></h2>
                            <?php endif; ?>
                            <?php if ($cg_member['cg_desc']) : ?>
                                <p class="text"><?php echo esc_html($cg_member['cg_desc']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endif; ?>

    <?php
    $cg_prices = [];
    for ($cg_i = 1; $cg_i <= 4; $cg_i++) {
        $cg_row = [
            'label' => ale_get_meta('menutitle' . $cg_i),
            'head'  => ale_get_meta('menutit' . $cg_i),
            'photo' => ale_get_meta('menuphoto' . $cg_i),
            'desc'  => ale_get_meta('menudesc' . $cg_i),
            'price' => ale_get_meta('menuprice' . $cg_i),
        ];
        if ($cg_row['label'] || $cg_row['head'] || $cg_row['photo'] || $cg_row['desc'] || $cg_row['price']) {
            $cg_prices[] = $cg_row;
        }
    }
    ?>

    <?php if ($cg_prices) : ?>
        <!-- Home Price -->
        <?php $cg_menubg = ale_get_meta('menubg'); ?>
        <section class="home-price nokeyframebug"<?php echo $cg_menubg ? ' style="background-image: url(' . esc_url($cg_menubg) . ');"' : ''; ?>>
            <div class="maskkeyframebug">
                <div class="center-align">
                    <a href="#top" class="top"></a>
                    <div class="prices cf">
                        <?php foreach ($cg_prices as $cg_price) : ?>
                            <div class="col-3">
                                <div class="item-top">
                                    <div class="item-logo"></div>
                                    <?php if ($cg_price['label']) : ?>
                                        <span><?php echo esc_html($cg_price['label']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cg_price['photo']) : ?>
                                    <img src="<?php echo esc_url($cg_price['photo']); ?>"
                                         alt="<?php echo esc_attr($cg_price['head']); ?>" />
                                <?php endif; ?>
                                <div class="item-info">
                                    <?php if ($cg_price['head']) : ?>
                                        <h2 class="firstfont caption colormain"><?php echo esc_html($cg_price['head']); ?></h2>
                                    <?php endif; ?>
                                    <?php if ($cg_price['desc']) : ?>
                                        <p class="text"><?php echo esc_html($cg_price['desc']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cg_price['price']) : ?>
                                    <div class="item-price">
                                        <h3 class="firstfont"><?php echo esc_html($cg_price['price']); ?></h3>
                                    </div>
                                <?php endif; ?>
                                <div class="item-rombs"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="inner-border"></div>
                <div class="background-opacity"></div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Details -->
    <article class="details">
        <div class="center-align">
            <h2 class="caption colormain firstfont"><?php the_title(); ?></h2>
        </div>
        <div class="center-align content">
            <div class="text story">
                <?php the_content(); ?>
            </div>
        </div>
    </article>

<?php endwhile; else : ?>
    <?php ale_part('notfound'); ?>
<?php endif; ?>

<?php get_footer(); ?>
