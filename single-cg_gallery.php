<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header><h1><?php the_title(); ?></h1></header>
<?php if (has_post_thumbnail()) the_post_thumbnail('large'); ?>
<div class="entry-content"><?php the_content(); ?></div>
</article>
<?php endwhile; ?>
<?php get_footer(); ?>
