<?php
/**
 * Packaging Factory
 *
 * The packaging factory creates the right packaging objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/Shipments
 */
namespace Vendidero\Germanized\Shipments;

use \Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Packaging factory class
 */
class PackagingFactory {

	/**
	 * Get packaging.
	 *
	 * @param  mixed $packaging_id (default: false) Packaging id to get or empty if new.
	 * @return Packaging|bool
	 */
	public static function get_packaging( $packaging_id = false ) {
		$packaging_id = self::get_packaging_id( $packaging_id );
		$classname    = '\Vendidero\Germanized\Shipments\Packaging';

		/**
		 * Filter to adjust the classname used to construct a Packaging.
		 *
		 * @param string  $classname The classname to be used.
		 * @param integer $packaging_id The packaging id.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$classname = apply_filters( 'woocommerce_gzd_packaging_class', $classname, $packaging_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $packaging_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $packaging_id ) );
			return false;
		}
	}

	public static function get_packaging_id( $packaging ) {
		if ( is_numeric( $packaging ) ) {
			return $packaging;
		} elseif ( $packaging instanceof Packaging ) {
			return $packaging->get_id();
		} elseif ( ! empty( $packaging->packaging_id ) ) {
			return $packaging->packaging_id;
		} else {
			return false;
		}
	}
}
