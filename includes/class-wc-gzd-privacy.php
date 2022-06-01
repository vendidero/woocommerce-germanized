<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Privacy {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		// Export
		add_filter( 'woocommerce_privacy_export_customer_personal_data', array( $this, 'get_customer_data' ), 10, 2 );
		add_filter( 'woocommerce_privacy_export_order_personal_data', array( $this, 'get_order_data' ), 10, 2 );

		// Erase
		add_filter( 'woocommerce_privacy_erase_personal_data_customer', array( $this, 'erase_customer_data' ), 10, 2 );
		add_action(
			'woocommerce_privacy_before_remove_order_personal_data',
			array(
				$this,
				'erase_order_data',
			),
			10,
			1
		);
	}

	public function erase_order_data( $order ) {

		/**
		 * Filter to adjust personal order data to be anonymized while removing personal data from orders.
		 *
		 * @param array $meta_keys Meta keys to be anonymized.
		 * @param WC_Order $order The order object.
		 *
		 * @since 1.9.10
		 *
		 */
		$meta_data = apply_filters(
			'woocommerce_gzd_privacy_erase_order_personal_metadata',
			array(
				'_shipping_parcelshop_post_number' => 'text',
				'_billing_title'                   => 'text',
				'_shipping_title'                  => 'text',
				'_direct_debit_holder'             => 'text',
				'_direct_debit_iban'               => 'text',
				'_direct_debit_bic'                => 'text',
				'_direct_debit_mandate_mail'       => 'text',
			),
			$order
		);

		foreach ( $meta_data as $prop => $data_type ) {

			$value = $order->get_meta( $prop );

			// If the value is empty, it does not need to be anonymized.
			if ( empty( $value ) || empty( $data_type ) ) {
				continue;
			}

			if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
				$anon_value = wp_privacy_anonymize_data( $data_type, $value );
			} else {
				$anon_value = '';
			}

			$order->update_meta_data( $prop, $anon_value );
		}

		$order->save();
	}

	public function erase_customer_data( $response, $customer ) {

		/**
		 * Filter to adjust personal customer data to be anonymized while removing personal data from customers.
		 *
		 * @param array $meta_keys Meta keys to be anonymized.
		 * @param WC_Customer $customer The customer object.
		 *
		 * @since 1.9.10
		 *
		 */
		$meta_data = apply_filters(
			'woocommerce_gzd_privacy_erase_customer_personal_metadata',
			array(
				'shipping_parcelshop_post_number' => __( 'Postnumber', 'woocommerce-germanized' ),
				'billing_title'                   => __( 'Billing Title', 'woocommerce-germanized' ),
				'shipping_title'                  => __( 'Shipping Title', 'woocommerce-germanized' ),
				'direct_debit_holder'             => __( 'Account Holder', 'woocommerce-germanized' ),
				'direct_debit_iban'               => __( 'IBAN', 'woocommerce-germanized' ),
				'direct_debit_bic'                => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
			),
			$customer
		);

		foreach ( $meta_data as $prop => $title ) {
			if ( $value = $customer->get_meta( $prop ) ) {
				$customer->delete_meta_data( $prop );

				/* Translators: %s Prop name. */
				$response['messages'][] = sprintf( __( 'Removed customer "%s"', 'woocommerce-germanized' ), $title );
			}
		}

		$customer->save();

		return $response;
	}

	public function get_order_data( $data, $order ) {

		/**
		 * Filter to allow exporting personal data added by Germanized to orders.
		 *
		 * @param array $meta_keys Keys as well as titles to be exported.
		 * @param WC_Order $order The order object.
		 *
		 * @since 1.9.10
		 *
		 */
		$meta_data = apply_filters(
			'woocommerce_gzd_privacy_export_order_personal_metadata',
			array(
				'_shipping_parcelshop_post_number' => __( 'Postnumber', 'woocommerce-germanized' ),
				'_direct_debit_holder'             => __( 'Account Holder', 'woocommerce-germanized' ),
				'_direct_debit_iban'               => __( 'IBAN', 'woocommerce-germanized' ),
				'_direct_debit_bic'                => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
				'_direct_debit_mandate_date'       => __( 'Mandate Date', 'woocommerce-germanized' ),
				'_direct_debit_mandate_id'         => __( 'Mandate ID', 'woocommerce-germanized' ),
				'_direct_debit_mandate_mail'       => __( 'Mandate Email', 'woocommerce-germanized' ),
			),
			$order
		);

		foreach ( $meta_data as $prop => $title ) {

			if ( $value = $order->get_meta( $prop ) ) {

				if ( in_array( $prop, array( '_direct_debit_iban', '_direct_debit_bic' ), true ) ) {
					// Maybe Decrypt
					$value = $this->decrypt( $value );
				}

				$data[] = array(
					'name'  => $title,
					'value' => $value,
				);
			}
		}

		return $data;
	}

	private function decrypt( $data ) {
		include_once WC_GERMANIZED_ABSPATH . 'includes/gateways/direct-debit/class-wc-gzd-gateway-direct-debit.php';
		$instance = new WC_GZD_Gateway_Direct_Debit();

		return $instance->maybe_decrypt( $data );
	}

	public function get_customer_data( $data, $customer ) {

		/**
		 * Filter to allow exporting personal data added by Germanized to customers.
		 *
		 * @param array $meta_keys Keys as well as titles to be exported.
		 * @param WC_Customer $customer The customer object.
		 *
		 * @since 1.9.10
		 *
		 */
		$meta_data = apply_filters(
			'woocommerce_gzd_privacy_export_customer_personal_metadata',
			array(
				'shipping_parcelshop_post_number' => __( 'Postnumber', 'woocommerce-germanized' ),
				'billing_title'                   => __( 'Billing Title', 'woocommerce-germanized' ),
				'shipping_title'                  => __( 'Shipping Title', 'woocommerce-germanized' ),
				'direct_debit_holder'             => __( 'Account Holder', 'woocommerce-germanized' ),
				'direct_debit_iban'               => __( 'IBAN', 'woocommerce-germanized' ),
				'direct_debit_bic'                => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
			),
			$customer
		);

		foreach ( $meta_data as $prop => $title ) {
			if ( $value = $customer->get_meta( $prop ) ) {

				if ( in_array( $prop, array( 'billing_title', 'shipping_title' ), true ) ) {
					$value = wc_gzd_get_customer_title( $value );
				}

				if ( in_array( $prop, array( 'direct_debit_iban', 'direct_debit_bic' ), true ) ) {
					// Maybe Decrypt
					$value = $this->decrypt( $value );
				}

				$data[] = array(
					'name'  => $title,
					'value' => $value,
				);
			}
		}

		return $data;
	}

}

WC_GZD_Privacy::instance();
