<?php
/**
 * Single post / single item.
 *
 * Ported from the source theme's single.php, which composed the story from
 * partials/posthead.php + postcontent.php + postfooter.php and then called get_sidebar().
 *
 * DEVIATION: the source wraps the story in `.col-8` beside a `.col-4` sidebar. This theme
 * registers no sidebars and has no sidebar.php, so calling get_sidebar() would fall back to
 * dynamic_sidebar() and print an empty column. The story is rendered full width instead and
 * the sidebar is tracked as a parity gap in docs/PARITY.md §2 — adding a sidebar needs
 * register_sidebar() plus a widget surface, which is its own ticket rather than a silent
 * removal.
 */
get_header();
?>
<div class="story-open">
    <div class="center-align cf">
        <div class="col-8 main">
            <?php
            if (have_posts()) :
                while (have_posts()) :
                    the_post();
                    ale_part('posthead');
                    ale_part('postcontent');
                    ale_part('postfooter');
                endwhile;
            else :
                ale_part('notfound');
            endif;

            comments_template();
            ?>
        </div>
    </div>
</div>
<?php
get_footer();
