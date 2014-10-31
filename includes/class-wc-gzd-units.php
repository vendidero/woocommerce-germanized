<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * The Units Class stores units/measurements data.
 *
 * @class WC_Germanized_Units
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Units {

	/**
	 * array containg units
	 *
	 * @var array
	 */
	private $units;

	/**
	 * Adds the units from i18n template
	 */
	public function __construct() {
		$this->units = apply_filters( 'woocommerce_germanized_units', include WC_germanized()->plugin_path() . '/i18n/units.php' );
	}

	/**
	 * Get units by key
	 *
	 * @param mixed   $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( ! empty( $this->units[$key] ) )
			return $this->units[$key];
		return false;
	}

	/**
	 * Returns mixed units array
	 *
	 * @return mixed units as array
	 */
	public function get_units() {
		return $this->units;
	}
}
