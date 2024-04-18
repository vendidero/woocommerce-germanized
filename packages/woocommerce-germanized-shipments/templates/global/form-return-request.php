<?php
/**
 * Guest return request.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/form-return-request.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<form class="woocommerce-form woocommerce-form-return-request return-request" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; ?>>

	<?php do_action( 'woocommerce_gzd_return_request_form_start' ); ?>

	<?php echo ( $message ) ? wpautop( wptexturize( $message ) ) : ''; // @codingStandardsIgnoreLine ?>

	<p class="form-row form-row-first">
		<label for="return-request-email"><?php echo esc_html_x( 'Order email', 'shipments', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="email" id="return-request-email" autocomplete="email" />
	</p>

	<p class="form-row form-row-last">
		<label for="return-request-order-id"><?php echo esc_html_x( 'Order id', 'shipments', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="order_id" id="return-request-order-id" autocomplete="off" />
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_gzd_return_request_form' ); ?>

	<p class="form-row">
		<?php wp_nonce_field( 'woocommerce-gzd-return-request', 'woocommerce-gzd-return-request-nonce' ); ?>
		<button type="submit" class="woocommerce-button button woocommerce-form-return_request__submit<?php echo esc_attr( wc_gzd_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_gzd_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="return_request" value="<?php echo esc_attr_x( 'Submit', 'shipments', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Submit', 'shipments', 'woocommerce-germanized' ); ?></button>
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_gzd_return_request_form_end' ); ?>

</form>
