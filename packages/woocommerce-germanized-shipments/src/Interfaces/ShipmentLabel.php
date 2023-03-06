<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use Vendidero\Germanized\Shipments\ShipmentError;

/**
 * Shipment Label Interface
 *
 * @package  Germanized/Shipments/Interfaces
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShipmentLabel class.
 */
interface ShipmentLabel {

	/**
	 * Return the unique identifier for the label
	 *
	 * @return mixed
	 */
	public function get_id();

	public function get_download_url( $args = array() );

	public function supports_additional_file_type( $file_type );

	public function get_additional_file_types();

	/**
	 * Returns the (local) label file path. False in case the label is not stored locally.
	 *
	 * @return bool|string
	 */
	public function get_file( $file_type = '' );

	/**
	 * Returns the label number which is used as a tracking id for the corresponding shipment.
	 *
	 * @return string
	 */
	public function get_number();

	/**
	 * Checks whether the label already has a valid number or not.
	 *
	 * @return boolean
	 */
	public function has_number();

	public function get_product_id();

	public function get_services();

	/**
	 * Returns the label shipment type, e.g. simple or return.
	 *
	 * @return string
	 */
	public function get_type();

	public function get_parent_id();

	public function get_shipping_provider_instance();

	/**
	 * Saves the label to DB.
	 */
	public function save();

	/**
	 * Delete the label from DB.
	 */
	public function delete( $force = false );

	/**
	 * Returns whether the label is trackable or not.
	 *
	 * @return boolean
	 */
	public function is_trackable();

	public function supports_third_party_email_notification();

	public function set_props( $props );

	public function update_meta_data( $key, $value, $meta_id = 0 );

	/**
	 * Get the label from the API and store it locally
	 *
	 * @return ShipmentError|true
	 */
	public function fetch();
}
