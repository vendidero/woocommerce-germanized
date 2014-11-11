<?php
/**
 * Admin View: Notice - Theme incompatibility
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="error">
	<h3><?php _e( 'Theme incompatibility found', 'woocommerce-germanized' ); ?></h3>
	<p><?php printf( __( 'It seems like your theme tries to overwrite legally relevant templates. Please review your checkout page. Some things might look weird because WooCommerce Germanized had to stop template overriding for legal purposes. See <a href="%s" target="_blank">making your theme compatible</a> or check out our Theme <a href="%s" target="_blank">VendiPro</a> for 100&#37; compatibility.', 'woocommerce-germanized' ), 'http://vendidero.de/dokument/woocommerce-germanized-theme-kompatibilitaet', 'http://vendidero.de/vendipro' ); ?></p>
	<form name="wc-gzd-hide-theme-incompatible-notice" method="get">
		<p>
			<a class="button button-primary" style="margin-right: 1em" href="http://vendidero.de/vendipro" target="_blank"><?php _e( 'Get VendiPro now', 'woocommerce-germanized' ); ?></a>
			<input type="hidden" name="wc-gzd-hide-theme-notice" value="1" /><button class="button button-secondary" type="submit"><?php _e( 'Hide this notice', 'woocommerce-germanized' ); ?></button>
		</p>
	</form>
</div>