<?php
namespace Vendidero\Germanized\Blocks\PaymentGateways;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Vendidero\Germanized\Blocks\Assets;

/**
 * Bank Transfer (BACS) payment method integration
 *
 * @since 3.0.0
 */
final class DirectDebit extends AbstractPaymentMethodType {
	/**
	 * @var string
	 */
	protected $name = 'direct-debit';

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

	public function initialize() {
		$this->settings = get_option( 'woocommerce_direct-debit_settings', array() );

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'validate' ), 8, 2 );
	}

	/**
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result object for the payment.
	 */
	public function validate( $context, &$result ) {
		$payment_method_object = $context->get_payment_method_instance();

		if ( ! $payment_method_object instanceof \WC_GZD_Gateway_Direct_Debit ) {
			return;
		}

		add_filter( 'woocommerce_gzd_direct_debit_store_fields_on_processing', '__return_true' );

		$this->set_context_legacy_data( $context );
	}

	/**
	 * @param PaymentContext $context Holds context for the payment.
	 */
	private function set_context_legacy_data( $context ) {
		$data            = $context->payment_data;
		$billing_country = wc_gzd_get_base_country();

		if ( is_a( $context->order, 'WC_Order' ) ) {
			$billing_country = $context->order->get_billing_country();
		}

		/**
		 * Set legacy data needed by direct debit field validation
		 *
		 * @see WC_GZD_Gateway_Direct_Debit::validate_fields()
		 */
		$context->set_payment_data(
			array_merge(
				$data,
				array(
					'payment_method'  => $context->payment_method,
					'billing_country' => $billing_country,
				)
			)
		);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$is_active = filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );

		return $is_active;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$this->assets->register_script(
			'wc-gzd-payment-method-direct-debit',
			'build/wc-gzd-payment-method-direct-debit.js'
		);

		return array( 'wc-gzd-payment-method-direct-debit' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		);
	}
}
