<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Contains Revocation Form Fields
 *
 * @class        WC_GZD_Revocation
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Revocation {

	/**
	 * Returns necessary form fields for revocation_form
	 *
	 * @return array
	 */
	public static function get_fields() {
		wc_deprecated_function( __METHOD__, '4.0.8' );

		return array();
	}
}
