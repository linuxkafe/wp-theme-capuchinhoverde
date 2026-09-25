<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
<header class="site-header">
<div class="site-branding">
<?php if (has_custom_logo()) { the_custom_logo(); } ?>
<h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
</div>
<nav class="site-nav">
<?php wp_nav_menu(['theme_location'=>'primary','depth'=>2]); ?>
</nav>
</header>
<main id="primary" class="site-main">
