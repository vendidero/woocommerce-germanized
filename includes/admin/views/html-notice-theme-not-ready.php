<?php
/**
 * Admin View: Notice - Theme not ready
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="error">
	<h3><?php _e( 'Theme not yet ready', 'woocommerce-germanized' ); ?></h3>
	<p><?php printf( __( 'It seems like your theme is not yet ready for WooCommerce Germanized. Please check your theme\'s styles. Some things might look weird - WooCommerce Germanized can only offer basic styles. See <a href="%s" target="_blank">making your theme compatible</a> or check out our Theme <a href="%s" target="_blank">VendiPro</a> for 100&#37; compatibility.', 'woocommerce-germanized' ), 'http://vendidero.de/dokument/woocommerce-germanized-theme-kompatibilitaet', 'http://vendidero.de/vendipro' ); ?></p>
	<form name="wc-gzd-hide-theme-incompatible-notice" method="get" action="">
		<p>
			<a class="button button-primary" style="margin-right: 1em" href="http://vendidero.de/vendipro" target="_blank"><?php _e( 'Get VendiPro now', 'woocommerce-germanized' ); ?></a>
			<input type="hidden" name="wc-gzd-hide-theme-notice" value="1" />
			<?php if ( ! empty( $_GET ) ) : ?>
				<?php foreach ( $_GET as $key => $val ) : ?>
					<input type="hidden" name="<?php echo sanitize_text_field( $key ); ?>" value="<?php echo esc_attr( sanitize_text_field( $val ) ); ?>" />
				<?php endforeach; ?>
			<?php endif; ?>
			<button class="button button-secondary" type="submit"><?php _e( 'Hide this notice', 'woocommerce-germanized' ); ?></button>
		</p>
	</form>
</div>