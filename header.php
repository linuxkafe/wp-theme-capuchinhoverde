<?php
/**
 * Cafeteria Header Template
 */

// Build the inline body style from discrete, escaped parts.
//
// This previously echoed ale_get_meta('custompagecss') straight into the style attribute.
// Any post meta reachable by an author is then a stored CSS-injection vector (and, via
// url() with a javascript: target in some engines, an XSS one). Escaped here; the raw
// value is only accepted if it survives a strict allowlist.
$cg_body_style = [];

$cg_custom_bg = ale_get_meta('custombg');
if ($cg_custom_bg) {
    $cg_bg_url = esc_url_raw($cg_custom_bg, ['http', 'https']);
    if ($cg_bg_url) {
        $cg_body_style[] = sprintf('background-image:url(%s);', esc_url($cg_bg_url));
    }
}

$cg_custom_css = ale_get_meta('custompagecss');
if ($cg_custom_css) {
    // Allow only declarations; drop anything containing a brace, quote, url(), or
    // expression to keep author-supplied CSS from escaping the attribute.
    $cg_safe_css = preg_replace('/[^a-zA-Z0-9#%.,\s:()\/-]/', '', $cg_custom_css);
    if ($cg_safe_css && !preg_match('/[{}<>"\']|url\s*\(|expression|@import/i', $cg_safe_css)) {
        $cg_body_style[] = $cg_safe_css;
    }
}

$cg_body_style[] = 'background-position:center;';
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" <?php language_attributes(); ?>> <!--<![endif]-->
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); echo 'style="' . esc_attr(implode(' ', $cg_body_style)) . '"'; ?> >
<?php wp_body_open(); ?>

<?php if(is_page_template('page-home.php')){
    if(ale_get_option('preloaderstatus')!=='1'){ ?>
    <div id="load-logo"><div class="logo-box" <?php if(ale_get_option('animationsitelogo')){echo 'style="background: url(' . esc_url(ale_get_option('animationsitelogo')) . ') no-repeat center;"';} ?>></div></div>
    <div id="load-hide"> <!-- hide while loading -->
<?php }
} ?>

<?php if(ale_get_option('langswitcher')=='1'){ ale_part('lang'); } ?>
<!-- Main Menu-->
<header class="cf">
    <div class="logo">
        <?php if(ale_get_option('sitelogo')){ ?>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="customlogo"><img src="<?php echo esc_url(ale_get_option('sitelogo')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" /></a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="mobcustomlogo cf"><img src="<?php echo esc_url(ale_get_option('sitelogo')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" /></a>
        <?php } else { ?>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="alelogo"><?php echo esc_html(get_bloginfo('name')); ?></a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="mobalelogo"><?php echo esc_html(get_bloginfo('name')); ?></a>
        <?php } ?>
    </div>
    <div class="menu-drop">
        <a>Menu</a>
        <?php
        if ( has_nav_menu( 'mobile_menu' ) ) {
            wp_nav_menu(array(
                'theme_location'=> 'mobile_menu',
                'menu'          => 'Mobile Menu',
                'menu_class'    => 'ul-drop',
                'container'     => '',
            ));
        } ?>
    </div>

    <div class="center-align">
            <?php if ( has_nav_menu( 'header_left_menu' ) ) {
                wp_nav_menu(array(
                    'theme_location'=> 'header_left_menu',
                    'menu'          => 'Header Left Menu',
                    'menu_class'    => 'col-6 left',
                    'container'     => '',
                ));
            }

            if ( has_nav_menu( 'header_right_menu' ) ) {
                wp_nav_menu(array(
                    'theme_location'=> 'header_right_menu',
                    'menu'          => 'Header Right Menu',
                    'menu_class'    => 'col-6 right',
                    'container'     => '',
                ));
            } ?>
    </div>
</header>
<?php if(is_404()){ ?>
    <div class="header-back" style="background-image: url('<?php echo esc_url(ale_get_option('mainheader')); ?>');">
        <div class="triang top"></div>
        <div class="triang bot"></div>
    </div>
<?php } elseif(!is_page_template('page-home.php')){
    // partials/innerheaders.php is not ported yet (T010). ale_part() fires the
    // `ale_part` action on a miss instead of failing silently, so the gap is
    // observable rather than invisible.
    ale_part('innerheaders');
} ?>