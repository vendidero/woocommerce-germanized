<?php

namespace Vendidero\Germanized\Shipments\Interfaces;

use Vendidero\Germanized\Shipments\PDFMerger;

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

	/**
	 * Downloads the label by either forcing a download or embedding the file within the browser.
	 *
	 * @param array $args Array containing additional arguments, e.g. the force parameter.
	 *
	 * @return mixed
	 */
	public function download( $args = array() );

	/**
	 * Returns the (local) label file path. False in case the label is not stored locally.
	 *
	 * @return bool|string
	 */
	public function get_file();

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

	/**
	 * Returns the label shipment type, e.g. simple or return.
	 *
	 * @return string
	 */
	public function get_type();

	/**
	 * Saves the label to DB.
	 */
	public function save();

	/**
	 * Delete the label from DB.
	 */
	public function delete( $force = false );
}
