<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce PayPal Payments Helper
 *
 * @see https://wordpress.org/plugins/woocommerce-paypal-payments/
 *
 * @class    WC_GZD_Compatibility_WooCommerce_PayPal_Payments
 * @category Class
 * @author   vendidero
 */
class WC_GZD_Compatibility_WooCommerce_PayPal_Payments extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce PayPal Payments';
	}

	public static function get_path() {
		return 'woocommerce-paypal-payments/woocommerce-paypal-payments.php';
	}

	public function load() {
		add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', array( $this, 'move_paypal_payment_button' ), 10 );

		add_action(
			'woocommerce_gzd_after_checkout_order_submit',
			function () {
				do_action( 'woocommerce_gzd_render_paypal_payments_smart_button' );
			}
		);

		add_action(
			'woocommerce_review_order_after_payment',
			function () {
				if ( wc_gzd_checkout_adjustments_disabled() || ! woocommerce_gzd_checkout_custom_submit_button_is_shown() ) {
					do_action( 'woocommerce_gzd_render_paypal_payments_smart_button' );
				}
			}
		);

		add_filter(
			'woocommerce_paypal_payments_tracking_data_before_update',
			function ( $shipment_data ) {
				if ( isset( $shipment_data['carrier'] ) ) {
					if ( strstr( $shipment_data['carrier'], 'dpd' ) ) {
						$shipment_data['carrier'] = 'DPD';
					} elseif ( strstr( $shipment_data['carrier'], 'gls' ) ) {
						$shipment_data['carrier'] = 'GLS';
					} elseif ( strstr( $shipment_data['carrier'], 'hermes' ) ) {
						$shipment_data['carrier'] = 'HERMES';
					} elseif ( strstr( $shipment_data['carrier'], 'ups' ) ) {
						$shipment_data['carrier'] = 'UPS';
					}
				}

				return $shipment_data;
			},
			10,
			1
		);

		/**
		 * PayPal Payments sends the checkout form as JSON (not as post_data) when creating the PayPal order and
		 * only stores it within its own session after the purchase unit has been built. Provide the form to Germanized
		 * as early as possible so that every cart (re-)calculation during these requests (e.g. triggered by third-party plugins)
		 * respects checkout-dependent data such as the photovoltaic systems VAT exemption checkbox.
		 */
		add_action( 'wp_loaded', array( $this, 'maybe_set_checkout_data_before_cart_load' ), 1 );
		add_action( 'woocommerce_paypal_payments_create_order_request_started', array( $this, 'set_checkout_data_from_request' ), 1 );
		add_action( 'woocommerce_paypal_payments_approve_order_request_started', array( $this, 'set_checkout_data_from_session' ), 1 );

		/**
		 * PayPal Payments AJAX requests (e.g. express buttons on product/cart pages, wallets, order approval) may lead to
		 * a payment/order without passing the checkout. Exemptions which need to be confirmed during checkout
		 * (e.g. photovoltaic systems) must not apply implicitly.
		 */
		add_filter( 'woocommerce_gzd_is_express_checkout_request', array( $this, 'is_express_checkout_request' ), 10 );
		add_action( 'woocommerce_paypal_payments_create_order_request_started', array( $this, 'maybe_recalculate_express_cart' ), 5 );

		/**
		 * When PayPal > Settings > Pay Now Experience is enabled (skip order review page), PayPal Payments
		 * manually creates a WC_Order (withount setting created_via) via ppc-approve-order endpoint.
		 * This order will include shipping net prices - make sure Germanized does not handle those prices as gross instead.
		 */
		add_filter(
			'woocommerce_paypal_payments_approve_order_request_started',
			function () {
				add_filter(
					'woocommerce_gzd_order_item_additional_cost_is_net',
					function ( $cost_is_net, $old_item, $item ) {
						$item_total     = wc_format_decimal( floatval( $old_item->get_total() ) );
						$new_item_total = wc_format_decimal( floatval( $item->get_total() ) );
						$item_tax_total = floatval( $old_item->get_total_tax() );

						return 0.0 === $item_tax_total && $item_total === $new_item_total;
					},
					10,
					3
				);
			}
		);

		/**
		 * When PayPal > Settings > Pay Now Experience is enabled (skip order review page), we'll need to
		 * send the order confirmation as a fallback as no WC_Checkout hooks are applied in this case.
		 */
		add_filter(
			'woocommerce_gzd_send_order_confirmation_as_fallback',
			function ( $send_confirmation, $order ) {
				if ( '' === $order->get_created_via() && 'ppcp-gateway' === $order->get_payment_method() ) {
					$send_confirmation = true;
				}

				return $send_confirmation;
			},
			10,
			2
		);
	}

	public function after_plugins_loaded() {
		/**
		 * Do not send the order confirmation email instantly as we'll need to wait for the IPN callback to finish
		 * to allow showing custom PayPal banking data.
		 */
		add_filter(
			'woocommerce_gzd_instant_order_confirmation',
			function ( $send_confirmation, $order = null ) {
				if ( is_a( $order, 'WC_Order' ) && 'ppcp-pay-upon-invoice-gateway' === $order->get_payment_method() ) {
					$send_confirmation = false;
				}

				return $send_confirmation;
			},
			10,
			2
		);

		/**
		 * Disable the paid for order mail for the PP invoice gateway.
		 */
		add_filter(
			'woocommerce_gzd_disable_gateways_paid_order_email',
			function ( $gateways_disabled ) {
				$gateways_disabled[] = 'ppcp-pay-upon-invoice-gateway';

				return $gateways_disabled;
			}
		);
	}

	/**
	 * WooCommerce loads the cart from the session on wp_loaded, before PayPal Payments handles its AJAX request.
	 * Parse the create order request body (the same way PayPal Payments does) beforehand to make sure the cart
	 * is set up with the current checkout data (e.g. photovoltaic systems VAT exemption) from the very start.
	 *
	 * @return void
	 */
	public function maybe_set_checkout_data_before_cart_load() {
		if ( ! isset( $_GET['wc-ajax'] ) || 'ppc-create-order' !== wc_clean( wp_unslash( $_GET['wc-ajax'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$body = file_get_contents( 'php://input' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = $body ? json_decode( $body, true ) : null;

		if ( ! is_array( $data ) ) {
			return;
		}

		if ( isset( $data['form_encoded'] ) && is_string( $data['form_encoded'] ) ) {
			$form = array();
			parse_str( $data['form_encoded'], $form );

			$data['form'] = $form;
		}

		$this->set_checkout_data_from_request( $data );
	}

	/**
	 * @param array $data The parsed PayPal Payments request data (form_encoded is parsed into form).
	 *
	 * @return void
	 */
	public function set_checkout_data_from_request( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['context'] ) || 'checkout' !== $data['context'] ) {
			return;
		}

		if ( ! empty( $data['form'] ) && is_array( $data['form'] ) ) {
			WC_GZD_Checkout::instance()->set_checkout_data( $data['form'] );
		}
	}

	/**
	 * Whether the current request is a PayPal Payments (WC) AJAX request, e.g. ppc-create-order.
	 *
	 * @return bool
	 */
	public static function is_paypal_payments_ajax_request() {
		$action = isset( $_GET['wc-ajax'] ) ? wc_clean( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return is_string( $action ) && 'ppc-' === substr( $action, 0, 4 );
	}

	/**
	 * @param bool $is_express
	 *
	 * @return bool
	 */
	public function is_express_checkout_request( $is_express ) {
		if ( ! $is_express && self::is_paypal_payments_ajax_request() ) {
			$is_express = true;
		}

		return $is_express;
	}

	/**
	 * Express orders (e.g. buttons on product or cart pages) are created from the totals stored within the session
	 * which might have been calculated outside an exemption context (e.g. zero-taxed photovoltaic systems shown within the cart).
	 * Recalculate the cart before PayPal Payments builds the purchase unit.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function maybe_recalculate_express_cart( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['context'] ) || in_array( $data['context'], array( 'checkout', 'pay-now' ), true ) ) {
			return;
		}

		if ( WC()->cart && wc_gzd_cart_contains_photovoltaic_system( WC()->cart->get_cart() ) ) {
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Use the checkout form PayPal Payments stored within its session (during order creation) as fallback.
	 *
	 * @param array $data The PayPal Payments approve order request data.
	 *
	 * @return void
	 */
	public function set_checkout_data_from_session( $data = array() ) {
		/**
		 * Express checkouts (Pay Now) create the order right away without showing the checkout form.
		 * Do not apply a checkout form which might have been stored during an earlier checkout attempt.
		 */
		if ( is_array( $data ) && ! empty( $data['should_create_wc_order'] ) ) {
			return;
		}

		$ppcp_session = WC()->session ? WC()->session->get( 'ppcp' ) : null;

		if ( is_array( $ppcp_session ) && ! empty( $ppcp_session['checkout_form'] ) && is_array( $ppcp_session['checkout_form'] ) ) {
			WC_GZD_Checkout::instance()->set_checkout_data( $ppcp_session['checkout_form'] );
		}
	}

	public function move_paypal_payment_button( $filter ) {
		return 'woocommerce_gzd_render_paypal_payments_smart_button';
	}
}
