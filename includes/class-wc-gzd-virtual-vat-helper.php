<?php
/**
 * Virtual VAT Helper
 *
 *
 * @class 		WC_GZD_Virtual_VAT_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Virtual_VAT_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
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

		$location = WC_Tax::get_tax_location( $tax_class );

		$virtual_vat_applicable = in_array( $tax_class, array( 'virtual-rate', 'virtual-reduced-rate' ) ) && isset( $location[0] ) && sizeof( $location ) === 4 && $location[0] !== WC()->countries->get_base_country();

		// Make sure that the customer is not a vat exempt
		if ( is_callable( array( WC()->customer, 'is_vat_exempt' ) ) ) {
			if ( WC()->customer->is_vat_exempt() ) {
				return $rates;
			}
		}

		if ( apply_filters( 'woocommerce_gzd_force_tax_location_vat_base_rates', $virtual_vat_applicable, $tax_class, $location ) ) {

			list( $country, $state, $postcode, $city ) = $location;

			$rates = WC_Tax::find_rates( array(
				'country' 	=> $country,
				'state' 	=> $state,
				'postcode' 	=> $postcode,
				'city' 		=> $city,
				'tax_class' => $tax_class
			) );
		}

		return $rates;
	}

}

return WC_GZD_Virtual_VAT_Helper::instance();