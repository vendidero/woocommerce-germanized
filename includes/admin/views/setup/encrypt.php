<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$new_key = WC_GZD_Secret_Box_Helper::get_random_encryption_key();

?>
<h1><?php esc_html_e( 'Encryption', 'woocommerce-germanized' ); ?></h1>

<script>
	function wc_gzd_copy_code(that){
		var inp =document.createElement('input');
		document.body.appendChild(inp)
		inp.value =that.textContent
		inp.select();
		document.execCommand('copy',false);
		inp.remove();
		alert("<?php echo esc_html__( 'Copied!', 'woocommerce-germanized' ); ?>")
	}
</script>

<p class="headliner"><?php echo wp_kses_post( sprintf( __( 'Germanized supports <a href="%s" target="_blank">encrypting sensitive data</a>, e.g. your DHL or Deutsche Post credentials.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/verschluesselung-sensibler-daten' ) ); ?></p>

<div class="wc-gzd-admin-settings">
	<?php if ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) : ?>
		<?php if ( WC_GZD_Secret_Box_Helper::supports_auto_insert() ) : ?>
			<p><?php echo wp_kses_post( sprintf( __( 'Please paste the following line to your <a href="%s" target="_blank">wp-config.php</a> file or use the insert key button:', 'woocommerce-germanized' ), 'https://wordpress.org/support/article/editing-wp-config-php/' ) ); ?></p>
		<?php else : ?>
			<p><?php echo wp_kses_post( sprintf( __( 'Please paste the following line to your <a href="%s" target="_blank">wp-config.php</a> file:', 'woocommerce-germanized' ), 'https://wordpress.org/support/article/editing-wp-config-php/' ) ); ?></p>
		<?php endif; ?>
		<p style="margin-top: 0;"><pre style="overflow: scroll; margin-top: 0; width: 100%;"><code onclick="wc_gzd_copy_code(this)">define( '<?php echo esc_attr( WC_GZD_Secret_Box_Helper::get_encryption_key_constant() ); ?>', '<?php echo esc_attr( $new_key ); ?>' );</code></pre></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Perfect! Your key has been placed and is working like a charm!', 'woocommerce-germanized' ); ?></p>
	<?php endif; ?>
</div>
