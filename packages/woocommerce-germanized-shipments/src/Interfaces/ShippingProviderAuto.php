<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;
use Vendidero\Germanized\Shipments\ShippingProvider\PickupLocation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShippingProviderAuto Interface
 *
 * @package  Germanized/Shipments/Interfaces
 * @version  3.1.0
 */
interface ShippingProviderAuto extends ShippingProvider, LabelConfigurationSet {

	public function get_label_classname( $type );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_products( $shipment );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_default_label_product( $shipment );

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields( $shipment );

	public function automatically_generate_label( $shipment = false );

	public function get_label_automation_shipment_status( $shipment = false );

	public function automatically_set_shipment_status_shipped( $shipment = false );

	public function automatically_generate_return_label();

	public function is_sandbox();

	public function get_settings_help_pointers( $section = '' );

	public function supports_pickup_locations();

	public function supports_pickup_location_delivery( $address, $query_args = array() );

	public function is_valid_pickup_location( $location_code, $address );

	/**
	 * @param $location_code
	 * @param $address
	 *
	 * @return PickupLocation|false
	 */
	public function get_pickup_location_by_code( $location_code, $address );

	/**
	 * @param $address
	 * @param $limit
	 *
	 * @return PickupLocation[]
	 */
	public function get_pickup_locations( $address, $query_args = array() );
}
