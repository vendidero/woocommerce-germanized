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
 * @version 2.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$manually_select_items   = apply_filters( 'eu_owb_woocommerce_manually_select_items_default', $manually_select_items );
$delete_original_request = apply_filters( 'eu_owb_woocommerce_delete_original_request_default', true );
$show_submit             = true;
?>
<form class="woocommerce-form woocommerce-form-order-withdrawal-request order-withdrawal-request" method="post">
	<?php do_action( 'eu_owb_woocommerce_return_request_form_start' ); ?>

	<div class="eu-owb-notice-wrapper"></div>

	<div class="eu-owb-form-fields">
		<?php if ( ! is_user_logged_in() && ! $order ) : ?>
			<div class="form-row form-row-first">
				<label for="order-withdrawal-request-order-number"><?php echo esc_html_x( 'Order number', 'owb', 'woocommerce-germanized' ); ?></label>
				<input type="text" class="input-text" name="order_number" id="order-withdrawal-request-order-number" autocomplete="off" />
			</div>

			<div class="form-row form-row-last">
				<label for="order-withdrawal-request-email"><?php echo esc_html_x( 'Email', 'owb', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="text" class="input-text" name="email" id="order-withdrawal-request-email" autocomplete="email" />
			</div>

			<div class="form-row form-row-first">
				<label for="order-withdrawal-request-first-name"><?php echo esc_html_x( 'First name', 'owb', 'woocommerce-germanized' ); ?></label>
				<input type="text" class="input-text" name="first_name" id="order-withdrawal-request-first-name" autocomplete="off" />
			</div>

			<div class="form-row form-row-last">
				<label for="order-withdrawal-request-last-name"><?php echo esc_html_x( 'Last name', 'owb', 'woocommerce-germanized' ); ?></label>
				<input type="text" class="input-text" name="last_name" id="order-withdrawal-request-last-name" autocomplete="off" />
			</div>

			<div class="clear"></div>

			<?php if ( \Vendidero\OrderWithdrawalButton\Package::enable_partial_withdrawals() ) : ?>
				<div class="order-supports-partial-withdrawal hidden">
					<div class="form-row form-row-full">
						<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="manually-select-items">
							<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="manually_select_items" <?php checked( $manually_select_items, true ); // WPCS: input var ok, csrf ok. ?> id="manually-select-items" />
							<span class="eu-owb-woocommerce-select-certain-items-text"><?php echo esc_html_x( 'I want to select the items to cancel manually.', 'owb', 'woocommerce-germanized' ); ?></span>
							<span class="notice eu-owb-woocommerce-select-certain-items-desc"><?php echo esc_html_x( 'You will receive an email confirming receipt of your withdrawal request. In the email, you will find a link that allows you to select the items you wish to return.', 'owb', 'woocommerce-germanized' ); ?></span>
						</label>
					</div>
				</div>
			<?php endif; ?>

			<?php do_action( 'eu_owb_woocommerce_return_request_guest_form' ); ?>
			<?php
		else :
			$default_email_address = $order ? $order->get_billing_email() : WC()->customer->get_billing_email();
			$orders                = is_user_logged_in() ? eu_owb_get_withdrawable_orders_for_user() : array();
			$default_order_id      = 0;

			if ( $order ) {
				$default_order_id = $order->get_id();
				$orders           = eu_owb_get_withdrawable_orders(
					eu_owb_find_orders(
						array(
							'email'       => $order->get_billing_email(),
							'customer_id' => $order->get_customer_id(),
						)
					)
				);

				if ( $request = eu_owb_get_withdrawal_request( $order ) ) {
					$default_email_address = eu_owb_get_order_withdrawal_email( $order, $request );
				}
			}
			?>
			<?php if ( ! empty( $orders ) ) : ?>
				<div class="form-row form-row-full">
					<label for="order-withdrawal-request-order"><?php echo esc_html_x( 'Order', 'owb', 'woocommerce-germanized' ); ?>&nbsp;<span class="required">*</span></label>
					<select name="order_id" id="order-withdrawal-request-order">
						<option value=""><?php echo esc_html_x( 'Please select an order', 'owb', 'woocommerce-germanized' ); ?></option>
						<?php foreach ( $orders as $t_order ) : ?>
							<option value="<?php echo esc_attr( $t_order->get_id() ); ?>" <?php selected( $default_order_id, $t_order->get_id() ); ?>><?php echo esc_html( sprintf( _x( 'Order %1$s', 'owb', 'woocommerce-germanized' ), $t_order->get_order_number() ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

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

				<div class="form-row form-row-full">
					<label for="order-withdrawal-request-email"><?php echo esc_html_x( 'Email', 'owb', 'woocommerce-germanized' ); ?></label>
					<input type="text" class="input-text" name="email" id="order-withdrawal-request-email" autocomplete="email" value="<?php echo esc_attr( $default_email_address ); ?>" />
				</div>

				<?php if ( $order ) : ?>
					<?php if ( eu_owb_get_withdrawal_request( $order ) ) : ?>
						<div class="form-row form-row-full hidden order-withdrawal-delete-original-request-checkbox">
							<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="delete-original-request">
								<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="delete_original_request" <?php checked( $delete_original_request, true ); // WPCS: input var ok, csrf ok. ?> id="delete-original-request" />
								<span class="eu-owb-woocommerce-select-certain-items-text"><?php printf( esc_html_x( 'Please delete my original withdrawal request to order %1$s.', 'owb', 'woocommerce-germanized' ), esc_html( $order->get_order_number() ) ); ?></span>
							</label>
						</div>
					<?php endif; ?>

					<input type="hidden" name="original_order_id" id="original-order-id" value="<?php echo esc_attr( $order->get_id() ); ?>" />

					<?php if ( ! empty( $order_key ) ) : ?>
						<input type="hidden" name="order_key" value="<?php echo esc_attr( $order_key ); ?>" />
					<?php endif; ?>
				<?php endif; ?>
				<?php
			else :
				$show_submit = false;
				?>
				<p class="woocommerce-info notice"><?php echo wp_kses_post( sprintf( _x( 'Sorry, there are currently no orders available to withdraw. If you have questions regarding one of your orders, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) ); ?></p>
			<?php endif; ?>

			<?php do_action( 'eu_owb_woocommerce_return_request_customer_form', $order ); ?>
		<?php endif; ?>
	</div>

	<?php do_action( 'eu_owb_woocommerce_return_request_form_before_submit', $order ); ?>

	<?php if ( $show_submit ) : ?>
		<div class="form-row form-row-submit">
			<?php wp_nonce_field( 'eu_owb_woocommerce_order_withdrawal_request' ); ?>
			<button type="submit" class="woocommerce-button button woocommerce-form-return_request__submit<?php echo esc_attr( eu_owb_wp_theme_get_element_class_name( 'button' ) ? ' ' . eu_owb_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="order_withdrawal_request" value="<?php echo esc_attr_x( 'Confirm withdrawal', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Confirm withdrawal', 'owb', 'woocommerce-germanized' ); ?></button>
		</div>
	<?php endif; ?>

	<div class="clear"></div>
	<?php do_action( 'eu_owb_woocommerce_return_request_form_end', $order ); ?>
</form>
