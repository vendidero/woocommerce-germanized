<?php
/**
 * Admin View: Notice - Templates outdated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$theme       = wp_get_theme();
$dismiss_url = wp_nonce_url( add_query_arg( array( 'notice' => 'wc-gzd-hide-template-outdated-notice' ) ), 'wc-gzd-hide-template-outdated-notice', 'nonce' );
?>

<div id="message" class="error fade woocommerce-gzd-message">
    <a class="notice-dismiss"
       href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'woocommerce-germanized' ); ?></a>

    <p>
		<?php /* translators: %s: theme name */ ?>
		<?php printf( __( '<strong>Your theme (%s) contains outdated copies of some Germanized template files.</strong> These files may need updating to ensure they are compatible with the current version of Germanized. Suggestions to fix this:', 'woocommerce-germanized' ), esc_html( $theme['Name'] ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
    </p>
    <ol>
        <li><?php esc_html_e( 'Update your theme to the latest version. If no update is available contact your theme author asking about compatibility with the current Germanized version.', 'woocommerce-germanized' ); ?></li>
        <li><?php esc_html_e( 'If you copied over a template file to change something, then you will need to copy the new version of the template and apply your changes again.', 'woocommerce-germanized' ); ?></li>
    </ol>
    <p class="submit">
        <a class="button-primary"
           href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=germanized' ) ); ?>"
           target="_blank"><?php esc_html_e( 'View affected templates', 'woocommerce-germanized' ); ?></a>
    </p>
</div>