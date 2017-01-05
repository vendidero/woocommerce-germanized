<?php
/**
 * Checkout edit data notice
 *
 * @author   Vendidero
 * @package  WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="wc-gzd-edit-data-notice">
	<p class="wc-gzd-info">
		<?php if ( get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) === 'yes' ) : ?>
			<?php printf( __( 'Please check all of your entries carefully. You may change your entries with the help of the button "%s".', 'woocommerce-germanized' ), __( 'Edit Order', 'woocommerce-germanized' ) ); ?>
		<?php else : ?>
			<?php _e( 'Please check all of your entries carefully. You may change your entries with the help of the "Back" button in your browser', 'woocommerce-germanized' ); ?>
		<?php endif; ?>
	</p>
</div>