<?php
/**
 * "Nothing found" partial.
 *
 * Ported from the source theme's partials/notfound.php. The source used the 'aletheme'
 * text domain; the source .mo files are not shipped, so the domain is 'capuchinhoverde'.
 *
 * Before 2026-09-27 this file did not exist and ale_part() silently included nothing,
 * so the home page's empty-gallery case rendered literally nothing.
 */
?>
<div class="not-found">
	<p><?php esc_html_e( 'No results were found for the requested page.', 'capuchinhoverde' ); ?></p>
</div>
