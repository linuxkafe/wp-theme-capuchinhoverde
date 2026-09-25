<?php get_header(); ?>
<h1 class="page-title"><?php post_type_archive_title(); ?></h1>
<div class="posts-list">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2></header>
<?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
<div class="entry-content"><?php the_excerpt(); ?></div>
</article>
<?php endwhile; the_posts_pagination(); else : ?>
<p><?php esc_html_e('No items found','capuchinhoverde'); ?></p>
<?php endif; ?>
</div>
<?php get_footer(); ?>
