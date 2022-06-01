<?php

defined( 'ABSPATH' ) || exit;

/**
 * Virtual VAT Helper
 *
 *
 * @class    WC_GZD_Deprecated_Virtual_VAT_Helper
 * @category Class
 * @author   vendidero
 * @deprecated
 */
class WC_GZD_Deprecated_Virtual_VAT_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 2 );
	}

	public function init() {
		// Calculate taxes for virtual vat rates based on customer address if available
		add_filter( 'woocommerce_base_tax_rates', array( $this, 'set_base_tax_rates' ), 10, 2 );
	}

	public function set_base_tax_rates( $rates, $tax_class ) {

		/**
		 * Maybe load frontend functions if necessary e.g. if WC()->customer exists
		 * In case WC()->customer exists, WC_Tax::get_tax_location tries to get location data from customer which then calls "wc_get_chosen_shipping_method_ids".
		 * This problem was reported by a customer - seems to be an edge problem which could not yet be reproduced.
		 */
		if ( ! empty( WC()->customer ) && ! function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
			if ( ! WC_germanized()->is_frontend() && function_exists( 'WC' ) ) {
				WC()->frontend_includes();
			}
		}

		// Prevent errors
		if ( ! function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
			return $rates;
		}

		$location               = WC_Tax::get_tax_location( $tax_class );
		$virtual_vat_applicable = in_array(
			$tax_class,
			array(
				'virtual-rate',
				'virtual-reduced-rate',
			),
			true
		) && isset( $location[0] ) && count( $location ) === 4 && wc_gzd_get_base_country() !== $location[0];

		/**
		 * Filter that allows disabling default customer VAT exempt check when handling virtual VAT rates.
		 *
		 * @param bool $check Whether to check for VAT exempt or not.
		 * @param array $rates Array containing tax rates.
		 * @param string $tax_class The current tax class.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_check_virtual_vat_exempt', true, $rates, $tax_class ) && is_callable(
			array(
				WC()->customer,
				'is_vat_exempt',
			)
		) ) {
			if ( WC()->customer->is_vat_exempt() ) {
				return $rates;
			}
		}

		/**
		 * Filter to adjust whether virtual VAT is applicable or not.
		 * If set to true, Germanized will return tax rates based on the user country.
		 *
		 * @param bool $virtual_vat_applicable Whether virtual VAT rates are applicable or not.
		 * @param string $tax_class The tax class.
		 * @param array $location The tax location data.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_force_tax_location_vat_base_rates', $virtual_vat_applicable, $tax_class, $location ) ) {

			list( $country, $state, $postcode, $city ) = $location;

			$rates = WC_Tax::find_rates(
				array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => $city,
					'tax_class' => $tax_class,
				)
			);
		}

		return $rates;
	}

}

return WC_GZD_Deprecated_Virtual_VAT_Helper::instance();
