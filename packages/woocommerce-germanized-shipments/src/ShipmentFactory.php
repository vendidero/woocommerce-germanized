<?php
/**
 * Shipment Factory
 *
 * The shipment factory creates the right shipment objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/Shipments
 */
namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Shipment;
use \WC_Data_Store;
use \Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment factory class
 */
class ShipmentFactory {

	/**
	 * Get shipment.
	 *
	 * @param  mixed $shipment_id (default: false) Shipment id to get or empty if new.
	 * @return SimpleShipment|ReturnShipment|bool
	 */
	public static function get_shipment( $shipment_id = false, $shipment_type = 'simple' ) {
		$shipment_id = self::get_shipment_id( $shipment_id );

		if ( $shipment_id ) {
			$shipment_type = WC_Data_Store::load( 'shipment' )->get_shipment_type( $shipment_id );

			/**
			 * Shipment type cannot be found, seems to not exist.
			 */
			if ( empty( $shipment_type ) ) {
				return false;
			}

			$shipment_type_data = wc_gzd_get_shipment_type_data( $shipment_type );
		} else {
			$shipment_type_data = wc_gzd_get_shipment_type_data( $shipment_type );
		}

		if ( $shipment_type_data ) {
			$classname = $shipment_type_data['class_name'];
		} else {
			$classname = false;
		}

		/**
		 * Filter to adjust the classname used to construct a Shipment.
		 *
		 * @param string  $clasname The classname to be used.
		 * @param integer $shipment_id The shipment id.
		 * @param string  $shipment_type The shipment type.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$classname = apply_filters( 'woocommerce_gzd_shipment_class', $classname, $shipment_id, $shipment_type );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $shipment_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $shipment_id, $shipment_type ) );
			return false;
		}
	}

	public static function get_shipment_id( $shipment ) {
		if ( is_numeric( $shipment ) ) {
			return $shipment;
		} elseif ( $shipment instanceof Shipment ) {
			return $shipment->get_id();
		} elseif ( ! empty( $shipment->shipment_id ) ) {
			return $shipment->shipment_id;
		} else {
			return false;
		}
	}
}
