<?php
/**
 * The Template for displaying the edit data notice during checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/edit-data-notice.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<div class="wc-gzd-edit-data-notice">
	<p class="wc-gzd-info">
		<?php if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_back_to_cart_button' ) ) : ?>
			<?php printf( esc_html__( 'Please check all of your entries carefully. You may change your entries with the help of the button "%s".', 'woocommerce-germanized' ), esc_html__( 'Edit Order', 'woocommerce-germanized' ) ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Please check all of your entries carefully. You may change your entries with the help of the "Back" button in your browser', 'woocommerce-germanized' ); ?>
		<?php endif; ?>
	</p>
</div>
