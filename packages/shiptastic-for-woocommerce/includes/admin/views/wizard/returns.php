<?php
defined( 'ABSPATH' ) || exit;

$main_provider = \Vendidero\Shiptastic\Admin\Setup\Wizard::get_main_shipping_provider();
?>

<div class="wc-shiptastic-wizard-entry">
	<h1><?php echo esc_html_x( 'Setup customer returns', 'shipments', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-shiptastic-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php echo esc_html_x( 'Processing returns with WooCommerce is inconvenient. Shiptastic enables your customers to submit return requests, which you can either review and approve or process automatically.', 'shipments', 'woocommerce-germanized' ); ?></p>

	<div class="wc-shiptastic-wizard-config">
		<div class="wc-shiptastic-error-wrapper"></div>

		<div class="wc-shiptastic-wizard-list-item list-item-numbered">
			<span class="list-item-number">1</span>
			<h3><?php echo esc_html_x( 'Place the returns shortcode', 'shipments', 'woocommerce-germanized' ); ?></h3>
			<p><?php echo esc_html_x( 'To allow all your customers, even guests, to submit return requests, place the [shiptastic_return_request_form] shortcode within a page or', 'shipments', 'woocommerce-germanized' ); ?></p>
			<a class="button button-secondary wc-shiptastic-ajax-loading-btn wc-shiptastic-ajax-action" data-action="create_return_page" data-nonce="<?php echo esc_attr( wp_create_nonce( 'shiptastic-create-return-page' ) ); ?>" href="#"><span class="btn-text"><?php echo esc_html_x( 'Create return page', 'shipments', 'woocommerce-germanized' ); ?></span></a>
		</div>

		<div class="wc-shiptastic-wizard-list-item list-item-numbered">
			<span class="list-item-number">2</span>
			<h3><?php echo esc_html_x( 'Choose return reasons', 'shipments', 'woocommerce-germanized' ); ?></h3>
			<p><?php echo esc_html_x( 'Your customers may select a return reason for each item to be returned.', 'shipments', 'woocommerce-germanized' ); ?></p>

			<table class="wc-shiptastic-wizard-settings form-table return-reasons">
				<tbody>
					<?php
					WC_Admin_Settings::output_fields(
						array(
							array(
								'type' => 'shipment_return_reasons',
							),
						)
					);
					?>
				</tbody>
			</table>
		</div>

		<?php if ( $main_provider ) : ?>
			<div class="wc-shiptastic-wizard-list-item list-item-numbered">
				<span class="list-item-number">3</span>
				<h3><?php printf( esc_html_x( 'Enable returns for %s', 'shipments', 'woocommerce-germanized' ), esc_html( $main_provider->get_title() ) ); ?></h3>
				<p><?php echo esc_html_x( 'Whether returns are available for the respective order or not is determined based on the settings of the linked shipping service provider.', 'shipments', 'woocommerce-germanized' ); ?></p>

				<table class="wc-shiptastic-wizard-settings form-table shipping-provider-settings">
					<tbody>
					<?php
					WC_Admin_Settings::output_fields(
						array(
							array(
								'title'       => _x( 'Allow customers to submit return requests', 'shipments', 'woocommerce-germanized' ),
								'id'          => "shipping_provider_{$main_provider->get_name()}_supports_customer_returns",
								'placeholder' => '',
								'value'       => wc_bool_to_string( $main_provider->get_supports_customer_returns( 'edit' ) ),
								'default'     => 'yes',
								'type'        => 'shiptastic_toggle',
							),

							array(
								'title'             => _x( 'Allow guests to submit return requests', 'shipments', 'woocommerce-germanized' ),
								'id'                => "shipping_provider_{$main_provider->get_name()}_supports_guest_returns",
								'default'           => 'yes',
								'value'             => wc_bool_to_string( $main_provider->get_supports_guest_returns( 'edit' ) ),
								'type'              => 'shiptastic_toggle',
								'custom_attributes' => array(
									"data-show_if_shipping_provider_{$main_provider->get_name()}_supports_customer_returns" => '',
								),
							),

							array(
								'title'             => _x( 'Return requests need manual confirmation.', 'shipments', 'woocommerce-germanized' ),
								'id'                => "shipping_provider_{$main_provider->get_name()}_return_manual_confirmation",
								'placeholder'       => '',
								'value'             => wc_bool_to_string( $main_provider->get_return_manual_confirmation( 'edit' ) ),
								'default'           => 'yes',
								'type'              => 'shiptastic_toggle',
								'custom_attributes' => array(
									"data-show_if_shipping_provider_{$main_provider->get_name()}_supports_customer_returns" => '',
								),
							),
						)
					);
					?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<div class="wc-shiptastic-wizard-links">
		<button class="button button-primary button-submit" type="submit"><?php echo esc_attr_x( 'Continue', 'shipments-wizard', 'woocommerce-germanized' ); ?></button>
	</div>
</div>

