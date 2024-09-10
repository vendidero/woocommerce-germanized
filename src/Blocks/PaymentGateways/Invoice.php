<?php
namespace Vendidero\Germanized\Blocks\PaymentGateways;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Vendidero\Germanized\Blocks\Assets;

/**
 * Bank Transfer (BACS) payment method integration
 *
 * @since 3.0.0
 */
final class Invoice extends AbstractPaymentMethodType {
	/**
	 * @var string
	 */
	protected $name = 'invoice';

	/**
	 * @var Assets
	 */
	protected $assets = null;

	/**
	 * Constructor
	 *
	 * @param Assets $assets
	 */
	public function __construct( $assets ) {
		$this->assets = $assets;
	}

	public function get_supported_features() {
		return array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',
		);
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_invoice_settings', array() );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$is_active = filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );

		if ( $is_active && has_block( 'woocommerce/checkout' ) ) {
			$customer_only           = wc_string_to_bool( $this->get_setting( 'customers_only', 'no' ) );
			$customer_completed_only = wc_string_to_bool( $this->get_setting( 'customers_completed', 'no' ) );

			if ( $customer_only && ! is_user_logged_in() ) {
				$is_active = false;
			}

			if ( $customer_completed_only ) {
				$is_active = false;

				if ( is_user_logged_in() && WC()->customer ) {
					$is_active = WC()->customer->get_is_paying_customer() === true;
				}
			}
		}

		return $is_active;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$this->assets->register_script(
			'wc-gzd-payment-method-invoice',
			'build/wc-gzd-payment-method-invoice.js'
		);

		return array( 'wc-gzd-payment-method-invoice' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'              => $this->get_setting( 'title' ),
			'description'        => $this->get_setting( 'description' ),
			'supports'           => $this->get_supported_features(),
			'customersOnly'      => wc_string_to_bool( $this->get_setting( 'customers_only', 'no' ) ),
			'customersCompleted' => wc_string_to_bool( $this->get_setting( 'customers_completed', 'no' ) ),
		);
	}
}
