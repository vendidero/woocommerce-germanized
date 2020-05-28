<?php

namespace Vendidero\Germanized\DHL;
use Exception;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use Vendidero\Germanized\Shipments\ShipmentItem;
use Vendidero\Germanized\Shipments\ShippingProviderMethodPlaceholder;
use WC_Order_Item;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class ShipmentLabelWatcher {

	public static function init() {
		// Listen to shipments requiring label creation or deletion
		add_action( 'woocommerce_gzd_shipment_create_dhl_label', array( __CLASS__, 'create_shipment_label' ), 10, 4 );
		add_action( 'woocommerce_gzd_return_shipment_create_dhl_label', array( __CLASS__, 'create_return_shipment_label' ), 10, 4 );

		// Return the DHL label for a shipment if available
		add_filter( 'woocommerce_gzd_shipment_get_dhl_label', array( __CLASS__, 'get_shipment_label' ), 10, 2 );
		add_filter( 'woocommerce_gzd_return_shipment_get_dhl_label', array( __CLASS__, 'get_shipment_label' ), 10, 2 );

		// Legacy ShippingProviderMethod hook support
		add_filter( 'woocommerce_gzd_shipping_provider_method_provider', array( __CLASS__, 'legacy_provider_hook_support' ), 10, 3 );
	}

	public static function legacy_provider_hook_support( $provider, $method_id, $shipping_provider_method ) {

		if ( is_a( $shipping_provider_method, '\Vendidero\Germanized\Shipments\ShippingProviderMethodPlaceholder' ) ) {
			/**
			 * Filter to adjust the whether a certain (possibly unknown) shipping method
			 * supports DHL and it's features or not. You should better use the
			 * `woocommerce_gzd_shipping_provider_method_placeholder_provider` filter directly.
			 *
			 * @param boolean                           $enable Whether to enable DHL or not.
			 * @param string                            $id The method id e.g. advanced_flat_rate_shipping.
			 * @param ShippingProviderMethodPlaceholder $placeholder The shipping method instance.
			 *
			 * @since 3.0.6
			 * @package Vendidero/Germanized/DHL
			 */
			$has_dhl = apply_filters( 'woocommerce_gzd_dhl_enable_placeholder_shipping_method', false, $method_id, $shipping_provider_method );

			if ( $has_dhl ) {
				$provider = 'dhl';
			}
		}

		return $provider;
	}

	/**
	 * @param boolean  $label
	 * @param Shipment $shipment
	 *
	 * @return bool|Label
	 */
	public static function get_shipment_label( $label, $shipment ) {

		if ( $dhl_label = wc_gzd_dhl_get_shipment_label( $shipment ) ) {
			return $dhl_label;
		}

		return $label;
	}

	/**
	 * @param array $data
	 * @param WP_Error $error
	 * @param ReturnShipment $shipment
	 * @param array $raw_data
	 */
	public static function create_return_shipment_label( $data, $error, $shipment, $raw_data ) {
		$label = wc_gzd_dhl_create_label( $shipment, $data );

		if ( is_wp_error( $label ) ) {
			foreach( $label->get_error_messages() as $message ) {
				$error->add( 'error', $message );
			}
		}
	}

	/**
	 * @param array $data
	 * @param WP_Error $error
	 * @param Shipment $shipment
	 * @param array $raw_data
	 */
	public static function create_shipment_label( $data, $error, $shipment, $raw_data ) {
		$props = array();

		/**
		 * Do only parse post data if raw_data was passed which indicates that the label creation request is
		 * a manual user based request - in other cases - use defaults instead to prevent argument overrides.
		 */
		if ( ! empty( $raw_data ) ) {
			$services = array();
			$props    = array(
				'has_inlay_return'      => 'no',
				'codeable_address_only' => 'no',
			);

			foreach( $data as $key => $value ) {
				// Check if it is a service
				if ( substr( $key, 0, strlen( 'service_' ) ) === 'service_' ) {
					$new_key = substr( $key, ( strlen( 'service_' ) ) );

					if ( 'yes' === $value && in_array( $new_key, wc_gzd_dhl_get_services() ) ) {
						$services[] = $new_key;
					}
				} else {
					$props[ $key ] = $value;
				}
			}

			if ( isset( $props['preferred_time'] ) && ! empty( $props['preferred_time'] ) ) {
				$preferred_time = explode( '-', wc_clean( wp_unslash( $props['preferred_time'] ) ) );

				if ( sizeof( $preferred_time ) === 2 ) {
					$props['preferred_time_start'] = $preferred_time[0];
					$props['preferred_time_end']   = $preferred_time[1];
				}

				unset( $props['preferred_time'] );
			}

			$props['services'] = $services;
		}

		$label = wc_gzd_dhl_create_label( $shipment, $props );

		if ( is_wp_error( $label ) ) {
			foreach( $label->get_error_messages() as $message ) {
				$error->add( 'error', $message );
			}
		}
	}
}
