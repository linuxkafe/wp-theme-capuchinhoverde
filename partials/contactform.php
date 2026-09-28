<?php
/**
 * Contact form.
 *
 * Extracted from the home page (where it was inline) so the Contact template can reuse it
 * rather than carry a second copy that would drift. Both callers are covered by
 * tests/e2e/smoke.spec.ts.
 *
 * The nonce is verified by the CALLER, not here: the handler
 * (ale_send_contact()) only ever receives the data array, and a nonce check needs
 * $_POST. The form and its check therefore live together at the call site.
 *
 * Expects $cg_contact_result to be set by the caller (null when the form was not submitted).
 *
 * @var array|null $cg_contact_result
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<form class="cg-contact-form" method="post" action="">
    <?php wp_nonce_field('cg_contact', 'cg_contact_nonce'); ?>

    <p>
        <label for="cg-contact-name"><?php esc_html_e( 'Name', 'capuchinhoverde' ); ?></label>
        <input type="text" id="cg-contact-name" name="cg_contact[name]"
               value="<?php echo isset($_POST['cg_contact']['name']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['cg_contact']['name']))) : ''; ?>">
    </p>
    <p>
        <label for="cg-contact-email"><?php esc_html_e( 'Email', 'capuchinhoverde' ); ?></label>
        <input type="email" id="cg-contact-email" name="cg_contact[email]"
               value="<?php echo isset($_POST['cg_contact']['email']) ? esc_attr(sanitize_email(wp_unslash($_POST['cg_contact']['email']))) : ''; ?>">
    </p>
    <p>
        <label for="cg-contact-subject"><?php esc_html_e( 'Subject', 'capuchinhoverde' ); ?></label>
        <input type="text" id="cg-contact-subject" name="cg_contact[subject]"
               value="<?php echo isset($_POST['cg_contact']['subject']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['cg_contact']['subject']))) : ''; ?>">
    </p>
    <p>
        <label for="cg-contact-message"><?php esc_html_e( 'Message', 'capuchinhoverde' ); ?></label>
        <textarea id="cg-contact-message" name="cg_contact[message]" rows="6"><?php echo isset($_POST['cg_contact']['message']) ? esc_textarea(wp_unslash($_POST['cg_contact']['message'])) : ''; ?></textarea>
    </p>
    <?php // Honeypot — hidden from humans, irresistible to bots. ?>
    <p class="cg-hp" aria-hidden="true">
        <label for="cg-contact-website"><?php esc_html_e( 'Leave this empty', 'capuchinhoverde' ); ?></label>
        <input type="text" id="cg-contact-website" name="cg_contact[cg_website]" tabindex="-1" autocomplete="off">
    </p>
    <p>
        <button type="submit" class="firstfont"><?php esc_html_e( 'Send', 'capuchinhoverde' ); ?></button>
    </p>
</form>
