<?php
/**
 * Page heading wrapper.
 *
 * Ported verbatim in substance from the source theme's partials/pagehead.php (which is a
 * single 120-byte line). outputBuffering: the source opened <article> here and closed it in
 * pagefooter.php, so the tag balance depended on two files being included together. It is
 * kept as-is because the ported templates include them as a pair.
 */
?>
<article <?php post_class(); ?> id="page-<?php the_ID(); ?>" data-page-id="<?php the_ID(); ?>" style="margin-bottom: 30px;">
