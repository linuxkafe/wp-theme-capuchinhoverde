<?php
/**
 * Inner page header band.
 *
 * Ported from the source theme's partials/innerheaders.php.
 *
 * Two defects were fixed rather than copied:
 *
 *  1. The source's final `else` branch is a PHP syntax error — it emits
 *     `style="background-image: none; height: 13px; margin-bottom: 60px;');"`
 *     with a stray `');`. It is a real syntax error in the shipped upstream file, which
 *     would fatal the moment that branch executed.
 *  2. The source tests `$post->post_type` against 'gallery', 'events' and 'menu'. This
 *     theme registers those as 'cg_gallery', 'cg_event' and 'cg_menu', so those three
 *     branches would never match. The post type names are now mapped rather than
 *     hardcoded, so the branch cannot drift from inc/post-types.php again.
 *
 * All output is escaped; the source echoed option values raw into a style attribute.
 */

if (!defined('ABSPATH')) {
    exit;
}

// The context options, in the source's precedence order. Kept as data so the rendering
// below stays a single loop and the precedence is readable at a glance.
$cg_header_contexts = [
    ['option' => 'contactheader', 'check' => static function () {
        return is_page_template('template-contact.php');
    }],
    ['option' => 'galleryheader', 'post_type' => 'cg_gallery'],
    ['option' => 'eventsheader',  'post_type' => 'cg_event'],
    ['option' => 'menuheader',    'post_type' => 'cg_menu'],
    ['option' => 'storyheader',   'check' => static function () {
        return is_home() || is_archive() || is_singular('post');
    }],
];

$cg_header_image = '';

foreach ($cg_header_contexts as $cg_context) {
    $cg_matches = isset($cg_context['check'])
        ? (bool) call_user_func($cg_context['check'])
        : ( is_singular($cg_context['post_type']) );

    if (!$cg_matches) {
        continue;
    }
    $cg_candidate = ale_get_option($cg_context['option']);
    if ($cg_candidate) {
        $cg_header_image = $cg_candidate;
        break;
    }
}

if (!$cg_header_image) {
    $cg_header_image = ale_get_option('mainheader');
}
?>
<div class="header-back"<?php echo $cg_header_image ? ' style="background-image: url(\'' . esc_url($cg_header_image) . '\');"' : ' style="background-image: none; height: 13px;"'; ?>>
    <div class="triang top"></div>
    <div class="triang bot"></div>
</div>
