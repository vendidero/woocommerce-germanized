<?php
/**
 * Label Factory
 *
 * The label factory creates the right label objects.
 *
 * @version 1.0.0
 * @package Vendidero/Germanized/DHL
 */
namespace Vendidero\Germanized\Shipments\Labels;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use \WC_Data_Store;
use \Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Label factory class
 */
class Factory {

	/**
	 * Get label.
	 *
	 * @param  mixed $label_id (default: false) Label id to get or empty if new.
	 * @return ShipmentLabel|bool
	 */
	public static function get_label( $label_id = false, $shipping_provider_name = '', $label_type = 'simple' ) {
		$label_id = self::get_label_id( $label_id );

		if ( $label_id ) {
			$label_data = WC_Data_Store::load( 'shipment-label' )->get_label_data( $label_id );

			if ( $label_data ) {
				$label_type             = $label_data->type;
				$shipping_provider_name = $label_data->shipping_provider;
			}
		}

		$shipping_provider_name = apply_filters( 'woocommerce_gzd_shipment_label_shipping_provider_name', $shipping_provider_name, $label_id, $label_type );

		if ( ! $shipping_provider = wc_gzd_get_shipping_provider( $shipping_provider_name ) ) {
			return false;
		}

		/**
		 * Simple shipping provider do not support labels
		 */
		if ( ! is_a( $shipping_provider, '\Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
			return false;
		}

		$classname = $shipping_provider->get_label_classname( $label_type );

		/**
		 * Filter that allows adjusting the default DHL label classname.
		 *
		 * @param string  $classname The classname to be used.
		 * @param integer $label_id The label id.
		 * @param string  $label_type The label type.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		$classname = apply_filters( 'woocommerce_gzd_shipment_label_class', $classname, $label_id, $label_type, $shipping_provider );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $label_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $label_id, $shipping_provider_name, $label_type ) );
			return false;
		}
	}

	public static function get_label_id( $label ) {
		if ( is_numeric( $label ) ) {
			return $label;
		} elseif ( $label instanceof Label ) {
			return $label->get_id();
		} elseif ( ! empty( $label->label_id ) ) {
			return $label->label_id;
		} else {
			return false;
		}
	}
}
