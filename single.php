<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="entry-header">
<h1 class="entry-title"><?php the_title(); ?></h1>
<div class="entry-meta"><?php cg_posted_on(); ?> <?php cg_posted_by(); ?></div>
</header>
<div class="entry-content"><?php the_content(); ?></div>
</article>
<?php endwhile; ?>
<?php get_footer(); ?>
