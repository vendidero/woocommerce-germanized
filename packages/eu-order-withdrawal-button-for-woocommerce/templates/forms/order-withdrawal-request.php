<?php
/**
 * Cancel order request form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/forms/order-withdrawal-request.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<form class="woocommerce-form woocommerce-form-order-withdrawal-request order-withdrawal-request" method="post">
	<?php do_action( 'eu_owb_woocommerce_return_request_form_start' ); ?>

	<div class="eu-owb-notice-wrapper"></div>

	<div class="eu-owb-form-fields">
		<?php if ( ! is_user_logged_in() && ! $order ) : ?>
			<div class="form-row form-row-first">
				<label for="order-withdrawal-request-email"><?php echo esc_html_x( 'Email', 'owb', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="text" class="input-text" name="email" id="order-withdrawal-request-email" autocomplete="email" />
			</div>

			<div class="form-row form-row-last">
				<label for="order-withdrawal-request-order-number"><?php echo esc_html_x( 'Order number', 'owb', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="text" class="input-text" name="order_number" id="order-withdrawal-request-order-number" autocomplete="off" />
			</div>

			<div class="clear"></div>

			<?php do_action( 'eu_owb_woocommerce_return_request_guest_form' ); ?>
		<?php else : ?>
			<?php if ( ! $order ) : ?>
				<div class="form-row form-row-full">
					<label for="order-withdrawal-request-order"><?php echo esc_html_x( 'Order', 'owb', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
					<select name="order_id" id="order-withdrawal-request-order">
						<option value=""><?php echo esc_html_x( 'Please select an order', 'owb', 'woocommerce-germanized' ); ?></option>
						<?php foreach ( eu_owb_get_withdrawable_orders_for_user() as $t_order ) : ?>
							<option value="<?php echo esc_attr( $t_order->get_id() ); ?>"><?php echo esc_html( sprintf( _x( 'Order %1$s', 'owb', 'woocommerce-germanized' ), $t_order->get_order_number() ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php else : ?>
				<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
				<input type="hidden" name="order_key" value="<?php echo esc_attr( $order_key ); ?>" />
			<?php endif; ?>

			<div class="eu-owb-order-item-select-wrapper">
				<?php
				if ( $order ) :
					wc_get_template(
						'forms/order-withdrawal-request-item-select.php',
						array(
							'order'                 => $order,
							'manually_select_items' => $manually_select_items,
						)
					);
				endif;
				?>
			</div>

			<?php do_action( 'eu_owb_woocommerce_return_request_customer_form', $order ); ?>
		<?php endif; ?>
	</div>

	<?php do_action( 'eu_owb_woocommerce_return_request_form_before_submit', $order ); ?>

	<div class="form-row">
		<?php wp_nonce_field( 'eu_owb_woocommerce_order_withdrawal_request' ); ?>
		<button type="submit" class="woocommerce-button button woocommerce-form-return_request__submit<?php echo esc_attr( eu_owb_wp_theme_get_element_class_name( 'button' ) ? ' ' . eu_owb_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="order_withdrawal_request" value="<?php echo esc_attr_x( 'Confirm withdrawal', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Confirm withdrawal', 'owb', 'woocommerce-germanized' ); ?></button>
	</div>

	<div class="clear"></div>
	<?php do_action( 'eu_owb_woocommerce_return_request_form_end', $order ); ?>
</form>
