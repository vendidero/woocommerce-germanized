<?php
/**
 * Admin View: SEPA Encryption Notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_version;

?>

<div class="wc-gzd-direct-debit-encryption-notice notice error inline">

	<?php if ( version_compare( phpversion(), '5.4', '<' ) || ! extension_loaded( 'openssl' ) || version_compare( $wp_version, '4.4', '<' ) ) : ?>

		<p><?php echo wp_kses_post( sprintf( __( 'Please upgrade your PHP Version to at least 5.4 and make sure that you have <a href="%s" target="_blank">openssl</a> enabled and WP Version 4.4 or greater installed to support account data encryption.', 'woocommerce-germanized' ), 'http://php.net/manual/de/book.openssl.php' ) ); ?></p>

	<?php elseif ( ! WC_GZD_Gateway_Direct_Debit_Encryption_Helper::instance()->is_configured() ) : ?>

	<p><?php echo wp_kses_post( sprintf( __( 'Please insert the following code in your <a href="%s" target="_blank">wp-config.php</a> to enable encryption. You may of course choose your own key:', 'woocommerce-germanized' ), 'https://codex.wordpress.org/Editing_wp-config.php' ) ); ?></p>
	<pre style="overflow: scroll"><code>define( 'WC_GZD_DIRECT_DEBIT_KEY', '<?php echo esc_html( WC_GZD_Gateway_Direct_Debit_Encryption_Helper::instance()->get_random_key() ); ?>' );</code></pre>
	<p><?php echo wp_kses_post( sprintf( __( 'Your customers’ account data (IBAN, BIC) will from then on be saved <a href="%s" target="_blank">encrypted</a> within your database.', 'woocommerce-germanized' ), 'https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md#scenario-1-keep-data-secret-from-the-database-administrator' ) ); ?>
	<?php endif; ?>
</div>
