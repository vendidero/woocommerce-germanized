<?php

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Payment Gateways class
 *
 * Loads hooks for payment gateways
 *
 * @class        WC_GZD_Payment_Gateways
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Payment_Gateways {

	protected static $_instance = null;

	private $gateway_data = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		// Use a lower priority to prevent infinite loops with gateway plugins which use the same hook to detect availability
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'checkout' ), 5 );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'init_fee' ), 0 );
		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'remove_paypal_filter' ), 5, 2 );

		// Gateway admin export
		add_action( 'current_screen', array( $this, 'gateway_admin_init' ), 20 );

		// AJAX
		add_action( 'init', array( $this, 'gateway_ajax_init' ), 30 );

		// Init upon Pay action
		add_action( 'woocommerce_before_pay_action', array( $this, 'gateway_pay_init' ), 5 );
	}

	/**
	 * Remove PayPal thank you for payment text if the order has not been paid yet.
	 *
	 * @param $text
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public function remove_paypal_filter( $text, $order ) {
		if ( $order && 'paypal' === $order->get_payment_method() ) {
			$gateways = WC()->payment_gateways->get_available_payment_gateways();

			if ( isset( $gateways['paypal'] ) && $order->needs_payment() ) {
				remove_filter( 'woocommerce_thankyou_order_received_text', array( $gateways['paypal'], 'order_received_text' ), 10 );
			}
		}

		return $text;
	}

	public function gateway_admin_init() {
		$allowed = array( 'edit-shop_order', 'export' );
		$screen  = get_current_screen();

		if ( $screen && in_array( $screen->id, $allowed, true ) ) {
			$direct_debit = new WC_GZD_Gateway_Direct_Debit();
		}
	}

	public function gateway_pay_init() {
		$direct_debit = new WC_GZD_Gateway_Direct_Debit();
	}

	public function gateway_ajax_init() {
		if ( wp_doing_ajax() && class_exists( 'WC_Payment_Gateway' ) ) {
			$direct_debit = new WC_GZD_Gateway_Direct_Debit();
		}
	}

	public function save_fields() {
		wc_deprecated_function( 'WC_GZD_Payment_Gateways::save_fields', '3.19.12' );
	}

	/**
	 * Set default order button text instead of the button text defined by each payment gateway.
	 * Can be overridden by setting force_order_button_text within payment gateway class
	 * Manipulate payment gateway description if has a fee and init gateway title filter.
	 *
	 * This doesn't work for the block-based checkout as (grrr) the plain options, e.g. woocommerce_bacs_settings are loaded
	 * via get_option. @see Automattic\WooCommerce\Blocks\Payments\Integrations\CashOnDelivery
	 */
	public function checkout() {
		if ( is_admin() ) {
			return;
		}

		$this->manipulate_gateways();
	}

	public function gateway_supports_fees( $id ) {
		return in_array( $id, array( 'cod' ), true ) ? true : false;
	}

	protected function maybe_force_gateway_button_text( $gateway ) {
		$button_text = $gateway->order_button_text;

		if ( ! is_null( $button_text ) && ! empty( $button_text ) && ( ! isset( $gateway->force_order_button_text ) || $gateway->force_order_button_text ) ) {
			/**
			 * Filter to adjust the forced order submit button text per gateway.
			 * By default Woo allows gateways to adjust the submit button text.
			 * This behaviour does not comply with the button solution - that is why Germanized adds the
			 * option-based static text by default.
			 *
			 * @param string $button_text The static button text from within the options.
			 * @param string $gateway_id The gateway id.
			 *
			 * @since 1.0.0
			 *
			 */
			$gateway->order_button_text = apply_filters( 'woocommerce_gzd_order_button_payment_gateway_text', get_option( 'woocommerce_gzd_order_submit_btn_text', __( 'Buy Now', 'woocommerce-germanized' ) ), $gateway->id );
		}
	}

	public function manipulate_gateways() {
		if ( ! WC()->payment_gateways() ) {
			return;
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		foreach ( $gateways as $gateway ) {
			if ( 'yes' !== $gateway->enabled ) {
				continue;
			}

			$this->maybe_set_gateway_data( $gateway );
			$this->maybe_force_gateway_button_text( $gateway );

			if ( $this->gateway_supports_fees( $gateway->id ) && $this->get_cod_fee() ) {
				$gateway_description = $this->gateway_data[ $gateway->id ]['description'];
				$desc                = sprintf( __( '%s payment charge', 'woocommerce-germanized' ), wc_price( $this->get_cod_fee() ) ) . '.';

				if ( $this->get_cod_forwarding_fee() ) {
					$desc .= ' ' . sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $this->get_cod_forwarding_fee() ) ) . '.';
				}

				/**
				 * Filters the gateway description in case gateway fees have been added.
				 *
				 * @param string $html The description.
				 * @param WC_Payment_Gateway $gateway The gateway instance.
				 *
				 * @since 1.0.0
				 *
				 */
				$gateway_description .= apply_filters( 'woocommerce_gzd_payment_gateway_description', ' ' . $desc, $gateway );
				$gateway->description = $gateway_description;
			}
		}
	}

	private function maybe_set_gateway_data( $gateway ) {
		if ( ! isset( $this->gateway_data[ $gateway->id ] ) ) {
			$this->gateway_data[ $gateway->id ] = array(
				'title'       => $gateway->title,
				'description' => $gateway->description,
			);
		}
	}

	public function init_fields() {
		wc_deprecated_function( 'WC_GZD_Payment_Gateways::save_fields', '3.19.12' );
	}

	/**
	 * @note: No need to use a separate handling to retrieve the current payment gateway as
	 * Woo block-based checkout updates the session too when changing the payment gateway (as of Woo 9.9).
	 *
	 * @return string
	 */
	public function get_current_gateway() {
		$current_gateway = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';

		return $current_gateway;
	}

	public function enable_legacy_cod_fee() {
		return apply_filters( 'woocommerce_gzd_enable_legacy_cod_fee', 'yes' === get_option( 'woocommerce_gzd_has_legacy_cod_fee', 'no' ) );
	}

	/**
	 * Update fee for cart if gateway has been selected as payment method
	 */
	public function init_fee() {
		$current_gateway = $this->get_current_gateway();

		if ( ! $current_gateway || ! $this->enable_legacy_cod_fee() ) {
			return;
		}

		if ( ! $this->gateway_supports_fees( $current_gateway ) ) {
			return;
		}

		if ( $fee = $this->get_cod_fee() ) {
			WC()->cart->add_fee( __( 'Payment charge', 'woocommerce-germanized' ), $fee, true );
		}
	}

	/**
	 * @return float
	 */
	public function get_cod_fee() {
		$fee = get_option( 'woocommerce_gzd_checkout_cod_gateway_fee', '' );

		if ( ! empty( $fee ) ) {
			$fee = (float) wc_format_decimal( $fee );
		} else {
			$fee = 0.0;
		}

		return $fee;
	}

	/**
	 * @return float
	 */
	public function get_cod_forwarding_fee() {
		$fee = get_option( 'woocommerce_gzd_checkout_cod_gateway_forwarding_fee', '' );

		if ( ! empty( $fee ) ) {
			$fee = (float) wc_format_decimal( $fee );
		} else {
			$fee = 0.0;
		}

		return $fee;
	}

	/**
	 * Sets fee for a specific gateway
	 *
	 * @param object $gateway
	 */
	public function set_fee( $gateway ) {
		wc_deprecated_function( 'WC_GZD_Payment_Gateways::save_fields', '3.19.12' );
	}
}

return WC_GZD_Payment_Gateways::instance();
