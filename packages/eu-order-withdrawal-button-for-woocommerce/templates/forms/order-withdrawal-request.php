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
 * @version 2.3.0
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
			<?php
				woocommerce_form_field(
					'order_number',
					apply_filters(
						'eu_owb_woocommerce_form_field_order_number_args',
						array(
							'label'             => _x( 'Contract Identification, e.g. order number', 'owb', 'woocommerce-germanized' ),
							'class'             => array( 'form-row-full', 'form-row-order-number' ),
							'autocomplete'      => 'off',
							'id'                => 'order-withdrawal-request-order-number',
							'default'           => '',
							'required'          => \Vendidero\OrderWithdrawalButton\Package::get_form_field_required( 'order_number' ),
							'custom_attributes' => array(
								'maxlength' => \Vendidero\OrderWithdrawalButton\Package::get_form_field_maxlength( 'order_number' ),
							),
						)
					)
				);
			?>

			<?php
				woocommerce_form_field(
					'email',
					apply_filters(
						'eu_owb_woocommerce_form_field_email_args',
						array(
							'label'        => _x( 'Email', 'owb', 'woocommerce-germanized' ),
							'class'        => array( 'form-row-full', 'form-row-email', 'validate-email' ),
							'autocomplete' => 'email',
							'id'           => 'order-withdrawal-request-email',
							'default'      => '',
							'required'     => true,
						)
					)
				);
			?>

			<?php
			woocommerce_form_field(
				'email_repeat',
				array(
					'label'        => _x( 'Email (repeat)', 'owb', 'woocommerce-germanized' ),
					'class'        => array( 'form-row-full', 'form-row-email-repeat' ),
					'autocomplete' => 'email',
					'id'           => 'order-withdrawal-request-email-repeat',
					'default'      => '',
					'required'     => true,
				)
			);
			?>

			<?php
			woocommerce_form_field(
				'first_name',
				apply_filters(
					'eu_owb_woocommerce_form_field_first_name_args',
					array(
						'label'             => _x( 'First name', 'owb', 'woocommerce-germanized' ),
						'class'             => array( 'form-row-first', 'form-row-first-name' ),
						'autocomplete'      => 'off',
						'id'                => 'order-withdrawal-request-first-name',
						'default'           => '',
						'required'          => \Vendidero\OrderWithdrawalButton\Package::get_form_field_required( 'first_name' ),
						'custom_attributes' => array(
							'maxlength' => \Vendidero\OrderWithdrawalButton\Package::get_form_field_maxlength( 'first_name' ),
						),
					)
				)
			);
			?>

			<?php
			woocommerce_form_field(
				'last_name',
				apply_filters(
					'eu_owb_woocommerce_form_field_last_name_args',
					array(
						'label'             => _x( 'Last name', 'owb', 'woocommerce-germanized' ),
						'class'             => array( 'form-row-last', 'form-row-last-name' ),
						'autocomplete'      => 'off',
						'id'                => 'order-withdrawal-request-last-name',
						'required'          => \Vendidero\OrderWithdrawalButton\Package::get_form_field_required( 'last_name' ),
						'default'           => '',
						'custom_attributes' => array(
							'maxlength' => \Vendidero\OrderWithdrawalButton\Package::get_form_field_maxlength( 'last_name' ),
						),
					)
				)
			);
			?>

			<div class="clear"></div>

			<?php
			if ( eu_owb_enable_additional_information_field() ) :
				woocommerce_form_field(
					'additional_information',
					apply_filters(
						'eu_owb_woocommerce_form_field_additional_information_args',
						array(
							'label'             => _x( 'Additional information', 'owb', 'woocommerce-germanized' ),
							'class'             => array( 'form-row-full', 'form-row-additional-information' ),
							'autocomplete'      => 'off',
							'id'                => 'order-withdrawal-request-additional-information',
							'default'           => '',
							'required'          => \Vendidero\OrderWithdrawalButton\Package::get_form_field_required( 'additional_information' ),
							'type'              => 'textarea',
							'custom_attributes' => array(
								'maxlength' => \Vendidero\OrderWithdrawalButton\Package::get_form_field_maxlength( 'additional_information' ),
							),
						)
					)
				);
				?>
				<div class="clear"></div>
			<?php endif; ?>

			<?php if ( \Vendidero\OrderWithdrawalButton\Package::enable_partial_withdrawals() ) : ?>
				<div class="order-supports-partial-withdrawal hidden">
					<?php
					woocommerce_form_field(
						'manually_select_items',
						apply_filters(
							'eu_owb_woocommerce_form_field_manually_select_items_args',
							array(
								'label'         => _x( 'I want to select the items to cancel manually.', 'owb', 'woocommerce-germanized' ),
								'class'         => array( 'form-row-full', 'form-row-manually-select-items' ),
								'id'            => 'manually-select-items',
								'default'       => $manually_select_items,
								'type'          => 'checkbox',
								'checked_value' => true,
							)
						)
					);
					?>

					<span class="notice eu-owb-woocommerce-select-certain-items-desc"><?php echo esc_html_x( 'You will receive an email confirming receipt of your withdrawal request. In the email, you will find a link that allows you to select the items you wish to return.', 'owb', 'woocommerce-germanized' ); ?></span>
				</div>
			<?php endif; ?>

			<?php do_action( 'eu_owb_woocommerce_return_request_guest_form' ); ?>
			<?php
		else :
			$default_email_address = $order ? $order->get_billing_email() : ( WC()->customer ? WC()->customer->get_billing_email() : '' );
			$default_first_name    = $order ? $order->get_billing_first_name() : ( WC()->customer ? WC()->customer->get_billing_first_name() : '' );
			$default_last_name     = $order ? $order->get_billing_last_name() : ( WC()->customer ? WC()->customer->get_billing_last_name() : '' );
			$orders                = is_user_logged_in() ? eu_owb_get_orders_for_user() : array();
			$default_order_id      = 0;

			if ( $order ) {
				$default_order_id = $order->get_id();
				$orders           = eu_owb_find_orders(
					array(
						'email'       => $order->get_billing_email(),
						'customer_id' => $order->get_customer_id(),
						'return'      => 'objects',
						'status'      => eu_owb_get_withdrawable_order_statuses(),
					)
				);

				if ( $request = eu_owb_get_withdrawal_request( $order ) ) {
					$default_email_address = $request->get_email();
					$default_first_name    = $request->get_first_name();
					$default_last_name     = $request->get_last_name();
				}
			}
			?>
			<?php
			if ( ! empty( $orders ) ) :
				$orders_select = array(
					'' => _x( 'Please select an order', 'owb', 'woocommerce-germanized' ),
				);

				foreach ( $orders as $t_order ) {
					$orders_select[ absint( $t_order->get_id() ) ] = sprintf( _x( 'Order %1$s', 'owb', 'woocommerce-germanized' ), $t_order->get_order_number() );
				}
				?>
				<?php
				woocommerce_form_field(
					'order_id',
					apply_filters(
						'eu_owb_woocommerce_form_field_order_select_args',
						array(
							'label'    => _x( 'Order', 'owb', 'woocommerce-germanized' ),
							'class'    => array( 'form-row-full', 'form-row-order' ),
							'id'       => 'order-withdrawal-request-order',
							'default'  => $default_order_id,
							'type'     => 'select',
							'options'  => $orders_select,
							'required' => true,
						),
						$default_order_id,
						$orders
					)
				);
				?>

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

				<?php
				woocommerce_form_field(
					'email',
					apply_filters(
						'eu_owb_woocommerce_form_field_email_args',
						array(
							'label'        => _x( 'Email', 'owb', 'woocommerce-germanized' ),
							'class'        => array( 'form-row-full', 'form-row-email', 'validate-email' ),
							'autocomplete' => 'email',
							'id'           => 'order-withdrawal-request-email',
							'default'      => $default_email_address,
							'required'     => true,
						)
					)
				);
				?>

				<?php
				woocommerce_form_field(
					'email_repeat',
					array(
						'label'        => _x( 'Email (repeat)', 'owb', 'woocommerce-germanized' ),
						'class'        => array( 'form-row-full', 'form-row-email-repeat' ),
						'autocomplete' => 'email',
						'id'           => 'order-withdrawal-request-email-repeat',
						'default'      => '',
						'required'     => true,
					)
				);
				?>

				<?php
				woocommerce_form_field(
					'first_name',
					apply_filters(
						'eu_owb_woocommerce_form_field_first_name_args',
						array(
							'label'             => _x( 'First name', 'owb', 'woocommerce-germanized' ),
							'class'             => array( 'form-row-first', 'form-row-first-name' ),
							'autocomplete'      => 'off',
							'id'                => 'order-withdrawal-request-first-name',
							'default'           => $default_first_name,
							'required'          => \Vendidero\OrderWithdrawalButton\Package::get_form_field_required( 'first_name' ),
							'custom_attributes' => array(
								'maxlength' => \Vendidero\OrderWithdrawalButton\Package::get_form_field_maxlength( 'first_name' ),
							),
						)
					)
				);
				?>

				<?php
				woocommerce_form_field(
					'last_name',
					apply_filters(
						'eu_owb_woocommerce_form_field_last_name_args',
						array(
							'label'             => _x( 'Last name', 'owb', 'woocommerce-germanized' ),
							'class'             => array( 'form-row-last', 'form-row-last-name' ),
							'autocomplete'      => 'off',
							'id'                => 'order-withdrawal-request-last-name',
							'required'          => \Vendidero\OrderWithdrawalButton\Package::get_form_field_required( 'last_name' ),
							'default'           => $default_last_name,
							'custom_attributes' => array(
								'maxlength' => \Vendidero\OrderWithdrawalButton\Package::get_form_field_maxlength( 'last_name' ),
							),
						)
					)
				);
				?>

				<div class="clear"></div>

				<?php if ( $order ) : ?>
					<?php
					if ( eu_owb_get_withdrawal_request( $order ) ) :
						woocommerce_form_field(
							'delete_original_request',
							apply_filters(
								'eu_owb_woocommerce_form_field_delete_original_request_args',
								array(
									'label'         => sprintf( _x( 'Please delete my original withdrawal request to order %1$s.', 'owb', 'woocommerce-germanized' ), esc_html( $order->get_order_number() ) ),
									'class'         => array( 'form-row-full', 'hidden', 'order-withdrawal-delete-original-request-checkbox' ),
									'id'            => 'delete-original-request',
									'default'       => $delete_original_request,
									'type'          => 'checkbox',
									'checked_value' => true,
								)
							)
						);
					endif;
					?>

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
		<<?php echo esc_html( \Vendidero\OrderWithdrawalButton\Package::force_div_form_field() ? 'div' : 'p' ); ?> class="form-row form-row-submit" id="order-withdrawal-request-submit">
			<?php wp_nonce_field( 'eu_owb_woocommerce_order_withdrawal_request' ); ?>
			<button type="submit" class="woocommerce-button button woocommerce-form-return_request__submit<?php echo esc_attr( eu_owb_wp_theme_get_element_class_name( 'button' ) ? ' ' . eu_owb_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="order_withdrawal_request" value="<?php echo esc_attr_x( 'Confirm withdrawal', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_attr_x( 'Confirm withdrawal', 'owb', 'woocommerce-germanized' ); ?></button>
		</<?php echo esc_html( \Vendidero\OrderWithdrawalButton\Package::force_div_form_field() ? 'div' : 'p' ); ?>>
	<?php endif; ?>

	<div class="clear"></div>
	<?php do_action( 'eu_owb_woocommerce_return_request_form_end', $order ); ?>
</form>
