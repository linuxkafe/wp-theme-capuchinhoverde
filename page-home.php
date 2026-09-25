<?php
/*
 * Template name: Home
 */

if (isset($_POST['contact'])) {
    $error = ale_send_contact($_POST['contact']);
}

get_header(); ?>

<!-- Gallery -->
    <div class="slider cf">
        <div class="triang top"></div>
        <div class="triang bot"></div>
        <ul class="slides">
            <?php $slider = ale_sliders_get_slider(ale_get_option('homeslugfull')); ?>
            <?php if($slider):?>
                <?php foreach ($slider['slides'] as $slide) : ?>
                    <li style="background-image: url('<?php echo $slide['image'] ?>'); ">
                        <div class="box">
                            <?php if($slide['title']){ ?><h2 class="firstfont caption colormain"><?php echo $slide['title']; ?></h2><?php } ?>
                            <?php if($slide['description']){ ?><p class="text"><?php echo $slide['description']; ?></p><?php } ?>
                            <?php if($slide['url']){ ?><a href="<?php echo $slide['url']; ?>"><?php _e('Read More','aletheme'); ?></a><?php } ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif;?>
        </ul>
    </div>

<!-- Services -->
<?php if(ale_get_meta('serviceonhome')=='on') { ?>
    <div>
        <!-- Our Services -->
        <article class="our-services cf">
            <h2 class="firstfont caption colormain"><?php echo ale_get_meta('servtit'); ?></h2>
            <div class="center-align">
                <div class="line-cake">
                    <div class="cake"></div>
                    <div class="line left"></div>
                    <div class="line right"></div>
                </div>
            </div>

            <div class="center-align content cf">
                <div class="col-3">
                    <div class="circle">
                        <div class="img" style="background-image: url('<?php echo ale_get_meta('servic1'); ?>')"></div>
                    </div>
                    <h2 class="firstfont caption colormain"><?php if(ale_get_meta('servlink1')){ ?><a href="<?php echo ale_get_meta('servlink1'); ?>"><?php } ?><?php echo ale_get_meta('servtit1'); ?><?php if(ale_get_meta('servlink1')){ ?></a><?php } ?></h2>
                    <p class="text text-center"><?php echo ale_get_meta('servdesc1'); ?></p>
                </div>
                <div class="col-3">
                    <div class="circle">
                        <div class="img" style="background-image: url('<?php echo ale_get_meta('servic2'); ?>')"></div>
                    </div>
                    <h2 class="firstfont caption colormain"><?php if(ale_get_meta('servlink2')){ ?><a href="<?php echo ale_get_meta('servlink2'); ?>"><?php } ?><?php echo ale_get_meta('servtit2'); ?><?php if(ale_get_meta('servlink2')){ ?></a><?php } ?></h2>
                    <p class="text text-center"><?php echo ale_get_meta('servdesc2'); ?></p>
                </div>
                <div class="col-3">
                    <div class="circle">
                        <div class="img" style="background-image: url('<?php echo ale_get_meta('servic3'); ?>')"></div>
                    </div>
                    <h2 class="firstfont caption colormain"><?php if(ale_get_meta('servlink3')){ ?><a href="<?php echo ale_get_meta('servlink3'); ?>"><?php } ?><?php echo ale_get_meta('servtit3'); ?><?php if(ale_get_meta('servlink3')){ ?></a><?php } ?></h2>
                    <p class="text text-center"><?php echo ale_get_meta('servdesc3'); ?></p>
                </div>
                <div class="col-3">
                    <div class="circle">
                        <div class="img" style="background-image: url('<?php echo ale_get_meta('servic4'); ?>')"></div>
                    </div>
                    <h2 class="firstfont caption colormain"><?php if(ale_get_meta('servlink4')){ ?><a href="<?php echo ale_get_meta('servlink4'); ?>"><?php } ?><?php echo ale_get_meta('servtit4'); ?><?php if(ale_get_meta('servlink4')){ ?></a><?php } ?></h2>
                    <p class="text text-center"><?php echo ale_get_meta('servdesc4'); ?></p>
                </div>
            </div>
        </article>
    </div>
<?php } else { echo'<div class="heightonhome cf"></div>'; } ?>

<!-- ## Home Gallery -->
<?php if(ale_get_meta('galleryonhome')=='on') { ?>
    <section class="home-gallery nokeyframebug" <?php if(ale_get_meta('galbg')){ echo 'style="background-image: url('.ale_get_meta('galbg').');"';} ?>>
        <div class="maskkeyframebug">
            <div class="center-align">
                <a href="#top" class="top"></a>
                <div class="filterwrapper">
                    <div id="filters" class="filter-line cf">
                        <p><?php _e('FILTER BY','aletheme'); ?>:</p>
                        <a data-filter="*" href="#" class="active">
                            <span class="triangle"></span>
                            <span class="ref"><?php _e('All', 'aletheme')?></span>
                        </a>
                        <?php $args = array(
                            'type'                     => 'gallery',
                            'child_of'                 => 0,
                            'parent'                   => '',
                            'orderby'                  => 'name',
                            'order'                    => 'ASC',
                            'hide_empty'               => 1,
                            'hierarchical'             => 1,
                            'exclude'                  => '',
                            'include'                  => '',
                            'number'                   => '',
                            'taxonomy'                 => 'gallery-category',
                            'pad_counts'               => false );

                        $categories = get_categories( $args );

                        foreach($categories as $cat){ ?>
                            <a href="#" data-filter=".<?php echo $cat->slug; ?>">
                                <span class="triangle"></span>
                                <span class="ref"><?php echo $cat->name; ?></span>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div id="galcontainer" class="gallery cf">
                    <?php query_posts('&post_type=gallery&posts_per_page=8');
                    if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <div class="col-3 element <?php $terms = get_the_terms($post->ID, 'gallery-category'); //foreach($terms as $itcat) { echo $itcat->slug.' ';} ?>">
                        <div class="background">
                            <a href="<?php the_permalink(); ?>">
                                <div class="hover"></div>
                                <?php echo get_the_post_thumbnail($post->ID,'gallery-thumba'); ?>
                            </a>
                            <div class="pic"></div>
                            <a class="look" href="<?php the_permalink(); ?>"><?php _e('Take a look','aletheme'); ?></a>
                        </div>
                </div>
                    <?php endwhile; else: ?>
                        <?php ale_part('notfound')?>
                    <?php endif; wp_reset_query(); ?>
                </div>

            </div>

            <!-- ## ## ## ## ## ## ## ## ## ## -->
            <div class="inner-border"></div>
            <div class="background-opacity"></div>
        </div>
    </section>
<?php } else { echo'<div class="heightonhome cf"></div>'; } ?>

<!-- Footer -->
<?php get_footer(); ?>