<?php
/**
 * Guest return request.
 *
 * This template can be overridden by copying it to yourtheme/shiptastic/global/form-return-request.php.
 *
 * HOWEVER, on occasion Shiptastic will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/Shiptastic/Templates
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<form class="woocommerce-form woocommerce-form-return-request return-request" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; ?>>
	<?php do_action( 'woocommerce_shiptastic_return_request_form_start' ); ?>

	<?php echo ( $message ) ? wp_kses_post( wpautop( wptexturize( $message ) ) ) : ''; ?>

	<p class="form-row form-row-first">
		<label for="return-request-email"><?php echo esc_html_x( 'Order email', 'shipments', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="email" id="return-request-email" autocomplete="email" />
	</p>

	<p class="form-row form-row-last">
		<label for="return-request-order-id"><?php echo esc_html_x( 'Order id', 'shipments', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
		<input type="text" class="input-text" name="order_id" id="return-request-order-id" autocomplete="off" />
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_shiptastic_return_request_form' ); ?>

	<p class="form-row">
		<?php wp_nonce_field( 'woocommerce-stc-return-request', 'woocommerce-stc-return-request-nonce' ); ?>
		<button type="submit" class="woocommerce-button button woocommerce-form-return_request__submit<?php echo esc_attr( wc_stc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_stc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="return_request" value="<?php echo esc_attr_x( 'Submit', 'shipments', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Submit', 'shipments', 'woocommerce-germanized' ); ?></button>
	</p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_shiptastic_return_request_form_end' ); ?>

</form>
