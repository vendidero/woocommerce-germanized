<?php

namespace Vendidero\Germanized\Shipments\Labels;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use Vendidero\Germanized\Shipments\Interfaces\ShipmentReturnLabel;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Label class.
 */
class ReturnLabel extends Label implements ShipmentReturnLabel {

	/**
	 * Stores shipment data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'sender_address' => array(),
	);

	public function get_type() {
		return 'return';
	}

	public function get_sender_address( $context = 'view' ) {
		return $this->get_prop( 'sender_address', $context );
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * @since  3.0.0
	 * @param  string $prop Name of prop to get.
	 * @param  string $address billing or shipping.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_sender_address_prop( $prop, $context = 'view' ) {
		$value = $this->get_address_prop( $prop, 'sender_address', $context );

		return $value;
	}

	public function get_sender_address_2( $context = 'view' ) {
		return $this->get_sender_address_prop( 'address_2', $context );
	}

	public function get_sender_address_addition() {
		$addition        = $this->get_sender_address_2();
		$street_addition = $this->get_sender_street_addition();

		if ( ! empty( $street_addition ) ) {
			$addition = $street_addition . ( ! empty( $addition ) ? ' ' . $addition : '' );
		}

		return trim( $addition );
	}

	public function get_sender_street( $context = 'view' ) {
		return $this->get_sender_address_prop( 'street', $context );
	}

	public function get_sender_street_number( $context = 'view' ) {
		return $this->get_sender_address_prop( 'street_number', $context );
	}

	public function get_sender_street_addition( $context = 'view' ) {
		return $this->get_sender_address_prop( 'street_addition', $context );
	}

	public function get_sender_company( $context = 'view' ) {
		return $this->get_sender_address_prop( 'company', $context );
	}

	public function get_sender_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'name', $context );
	}

	public function get_sender_formatted_full_name() {
		return sprintf( _x( '%1$s', 'shipments full name', 'woocommerce-germanized' ), $this->get_sender_name() ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
	}

	public function get_sender_postcode( $context = 'view' ) {
		return $this->get_sender_address_prop( 'postcode', $context );
	}

	public function get_sender_city( $context = 'view' ) {
		return $this->get_sender_address_prop( 'city', $context );
	}

	public function get_sender_state( $context = 'view' ) {
		return $this->get_sender_address_prop( 'state', $context );
	}

	public function get_sender_country( $context = 'view' ) {
		return $this->get_sender_address_prop( 'country', $context );
	}

	public function get_sender_phone( $context = 'view' ) {
		return $this->get_sender_address_prop( 'phone', $context );
	}

	public function get_sender_email( $context = 'view' ) {
		return $this->get_sender_address_prop( 'email', $context );
	}

	public function set_sender_address( $value ) {
		$this->set_prop( 'sender_address', empty( $value ) ? array() : (array) $value );
	}
}
