<?php
/**
 * Admin View: Notice - Theme not ready
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$dismiss_url = add_query_arg( 'notice', 'wc-gzd-hide-theme-notice', add_query_arg( 'nonce', wp_create_nonce( 'wc-gzd-hide-theme-notice' ) ) );
?>

<div class="error fade">
	<h3><?php _e( 'Theme not yet ready', 'woocommerce-germanized' ); ?></h3>
	<p><?php printf( __( 'It seems like your theme is not yet ready for WooCommerce Germanized. Please check your theme\'s styles. Some things might look weird - WooCommerce Germanized can only offer basic styles. See <a href="%s" target="_blank">making your theme compatible</a> or check out our Theme <a href="%s" target="_blank">VendiPro</a> for 100&#37; compatibility.', 'woocommerce-germanized' ), 'https://vendidero.de/dokument/woocommerce-germanized-theme-kompatibilitaet?theme=' . esc_attr( $current_theme->get( 'Name' ) ), 'https://vendidero.de/vendipro' ); ?></p>
	<p class="alignleft wc-gzd-button-wrapper">
		<a class="button button-primary" href="http://vendidero.de/vendipro" target="_blank"><?php _e( 'Get VendiPro now', 'woocommerce-germanized' ); ?></a>	
	</p>
	<p class="alignright">
		<a href="<?php echo esc_url( $dismiss_url );?>"><?php _e( 'Hide this notice', 'woocommerce-germanized' ); ?></a>
	</p>
	<div class="clear"></div>
</div>