<?php get_header(); ?>
<?php if (have_posts()) : ?>
<div class="posts-list">
<?php while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="entry-header"><h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2></header>
<div class="entry-content"><?php the_excerpt(); ?></div>
</article>
<?php endwhile; ?>
<nav class="pagination"><?php the_posts_pagination(); ?></nav>
<?php else : ?>
<p><?php esc_html_e('No posts found.','capuchinhoverde'); ?></p>
<?php endif; ?>
<?php get_footer(); ?>
