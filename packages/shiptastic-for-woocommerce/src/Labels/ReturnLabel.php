<?php

namespace Vendidero\Shiptastic\Labels;

use Vendidero\Shiptastic\Interfaces\ShipmentLabel;
use Vendidero\Shiptastic\Interfaces\ShipmentReturnLabel;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Shipment;
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
	 * @param  string $prop Name of prop to get.
	 * @param  string $address billing or shipping.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_sender_address_prop( $prop, $context = 'view' ) {
		$value = $this->get_address_prop( $prop, 'sender_address', $context );

		return $value;
	}

	/**
	 * Returns the sender address first line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_address_1( $context = 'view' ) {
		return $this->get_sender_address_prop( 'address_1', $context );
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
		$sender_address = $this->get_sender_address();

		if ( array_key_exists( 'street', $sender_address ) ) {
			return $this->get_sender_address_prop( 'street', $context );
		}

		$split = wc_stc_split_shipment_street( $this->{'get_sender_address_1'}() );

		return $split['street'];
	}

	public function get_sender_street_number( $context = 'view' ) {
		$sender_address = $this->get_sender_address();

		if ( array_key_exists( 'street_number', $sender_address ) ) {
			return $this->get_sender_address_prop( 'street_number', $context );
		}

		$split = wc_stc_split_shipment_street( $this->{'get_sender_address_1'}() );

		return $split['number'];
	}

	public function get_sender_street_addition( $context = 'view' ) {
		$sender_address = $this->get_sender_address();

		if ( array_key_exists( 'street_addition', $sender_address ) ) {
			return $this->get_sender_address_prop( 'street_addition', $context );
		}

		$split = wc_stc_split_shipment_street( $this->{'get_sender_address_1'}() );

		return $split['addition'];
	}

	public function get_sender_company( $context = 'view' ) {
		return $this->get_sender_address_prop( 'company', $context );
	}

	public function get_sender_name( $context = 'view' ) {
		$sender_address = $this->get_sender_address();

		if ( array_key_exists( 'name', $sender_address ) ) {
			return $this->get_sender_address_prop( 'name', $context );
		}

		return '';
	}

	public function get_sender_first_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'first_name', $context );
	}

	public function get_sender_last_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'last_name', $context );
	}

	public function get_sender_formatted_full_name() {
		if ( empty( $this->get_sender_first_name() ) ) {
			return $this->get_sender_name();
		}

		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce-germanized' ), $this->get_sender_first_name(), $this->get_sender_last_name() );
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
