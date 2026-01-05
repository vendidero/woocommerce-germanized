<?php
namespace Vendidero\Shiptastic\Interfaces;

use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\PickupLocation;
use Vendidero\Shiptastic\Tracking\ShipmentStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ShippingProviderAuto extends ShippingProvider, LabelConfigurationSet {

	public function get_label_classname( $type );

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_available_label_products( $shipment );

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_default_label_product( $shipment );

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
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

	public function is_valid_pickup_location( $location_code, $address = array() );

	/**
	 * @param $location_code
	 * @param $address
	 *
	 * @return PickupLocation|false
	 */
	public function get_pickup_location_by_code( $location_code, $address = array() );

	/**
	 * @param $address
	 * @param $limit
	 *
	 * @return PickupLocation[]
	 */
	public function get_pickup_locations( $address, $query_args = array() );

	/**
	 * @return bool
	 */
	public function replace_shipping_address_by_pickup_location();

	/**
	 * Returns whether the shipping provider supports updating
	 * shipment status via API
	 *
	 * @return bool
	 */
	public function supports_remote_shipment_status( $type );

	/**
	 * Returns the number of shipments supported per status check run.
	 *
	 * @param $type
	 *
	 * @return integer
	 */
	public function get_number_of_shipments_per_status_check( $type );

	/**
	 * Handles a remote shipment status update event.
	 * Needs to handle authentication too.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response|ShipmentStatus
	 */
	public function handle_remote_shipment_status_update( $request );

	/**
	 * @param Shipment[] $shipments
	 *
	 * @return void
	 */
	public function subscribe_to_shipment_status_events( $shipments );

	/**
	 * @param Shipment[] $shipments
	 *
	 * @return void
	 */
	public function unsubscribe_from_shipment_status_events( $shipments );

	/**
	 * @return bool
	 */
	public function enable_remote_shipment_status_update( $type );

	/**
	 * @param Shipment[] $shipments
	 *
	 * @return ShipmentStatus[]
	 */
	public function get_remote_status_for_shipments( $shipments );
}
