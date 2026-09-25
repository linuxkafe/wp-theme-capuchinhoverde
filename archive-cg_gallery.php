<?php get_header(); ?>
<h1 class="page-title"><?php post_type_archive_title(); ?></h1>
<div class="gallery-grid">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<a href="<?php the_permalink(); ?>"><?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?></a>
<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
</article>
<?php endwhile; the_posts_pagination(); endif; ?>
</div>
<?php get_footer(); ?>
