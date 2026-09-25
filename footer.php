</main>
<footer class="site-footer">
<div class="site-info">
&copy; <?php echo date_i18n('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('Powered by WordPress','capuchinhoverde'); ?>
</div>
<?php wp_nav_menu(['theme_location'=>'footer','depth'=>1]); ?>
</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
