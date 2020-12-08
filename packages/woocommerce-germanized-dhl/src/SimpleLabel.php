<?php

namespace Vendidero\Germanized\DHL;
use DateTimeZone;
use Vendidero\Germanized\Shipments\Shipment;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * DHL ReturnLabel class.
 */
class SimpleLabel extends Label {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'preferred_day'                 => '',
		'preferred_time_start'          => '',
		'preferred_time_end'            => '',
		'preferred_location'            => '',
		'preferred_neighbor'            => '',
		'ident_date_of_birth'           => '',
		'ident_min_age'                 => '',
		'visual_min_age'                => '',
		'email_notification'            => 'no',
		'has_inlay_return'              => 'no',
		'codeable_address_only'         => 'no',
		'duties'                        => '',
		'return_address'                => array(),
		'cod_total'                     => 0,
		'cod_includes_additional_total' => 'no',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_return_address( $context = 'view' ) {
		return $this->get_prop( 'return_address', $context );
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
	protected function get_return_address_prop( $prop, $context = 'view' ) {
		$value = $this->get_address_prop( $prop, 'return_address', $context );

		// Load from settings
		if ( is_null( $value ) ) {
			$value = Package::get_setting( 'return_' . $prop );
		}

		return $value;
	}

	public function get_return_street( $context = 'view' ) {
		return $this->get_return_address_prop( 'street', $context );
	}

	public function get_return_street_number( $context = 'view' ) {
		return $this->get_return_address_prop( 'street_number', $context );
	}

	public function get_return_company( $context = 'view' ) {
		return $this->get_return_address_prop( 'company', $context );
	}

	public function get_return_name( $context = 'view' ) {
		return $this->get_return_address_prop( 'name', $context );
	}

	public function get_return_formatted_full_name() {
		return sprintf( _x( '%1$s', 'dhl full name', 'woocommerce-germanized' ), $this->get_return_name() );
	}

	public function get_return_postcode( $context = 'view' ) {
		return $this->get_return_address_prop( 'postcode', $context );
	}

	public function get_return_city( $context = 'view' ) {
		return $this->get_return_address_prop( 'city', $context );
	}

	public function get_return_state( $context = 'view' ) {
		return $this->get_return_address_prop( 'state', $context );
	}

	public function get_return_country( $context = 'view' ) {
		return $this->get_return_address_prop( 'country', $context );
	}

	public function get_return_phone( $context = 'view' ) {
		return $this->get_return_address_prop( 'phone', $context );
	}

	public function get_return_email( $context = 'view' ) {
		return $this->get_return_address_prop( 'email', $context );
	}

	public function get_cod_total( $context = 'view' ) {
		return $this->get_prop( 'cod_total', $context );
	}

	public function get_cod_includes_additional_total( $context = 'view' ) {
		return $this->get_prop( 'cod_includes_additional_total', $context );
	}

	public function cod_includes_additional_total( $context = 'view' ) {
		return $this->get_cod_includes_additional_total() ? true : false;
	}

	public function get_duties( $context = 'view' ) {
		return $this->get_prop( 'duties', $context );
	}

	public function get_preferred_day( $context = 'view' ) {
		return $this->get_prop( 'preferred_day', $context );
	}

	public function get_preferred_time() {
		$start = $this->get_preferred_time_start();
		$end   = $this->get_preferred_time_end();

		if ( $start && $end ) {
			return $start->date( 'H:i' ) . '-' . $end->date( 'H:i' );
		}

		return null;
	}

	public function get_preferred_time_start( $context = 'view' ) {
		return $this->get_prop( 'preferred_time_start', $context );
	}

	public function get_preferred_time_end( $context = 'view' ) {
		return $this->get_prop( 'preferred_time_end', $context );
	}

	public function get_preferred_formatted_time() {
		$start = $this->get_preferred_time_start();
		$end   = $this->get_preferred_time_end();

		if ( $start && $end ) {
			return sprintf( _x( '%s-%s', 'dhl time-span', 'woocommerce-germanized' ), $start->date( 'H' ), $end->date( 'H' ) );
		}

		return null;
	}

	public function get_preferred_location( $context = 'view' ) {
		return $this->get_prop( 'preferred_location', $context );
	}

	public function get_preferred_neighbor( $context = 'view' ) {
		return $this->get_prop( 'preferred_neighbor', $context );
	}

	public function get_ident_date_of_birth( $context = 'view' ) {
		return $this->get_prop( 'ident_date_of_birth', $context );
	}

	public function get_ident_min_age( $context = 'view' ) {
		return $this->get_prop( 'ident_min_age', $context );
	}

	public function get_visual_min_age( $context = 'view' ) {
		return $this->get_prop( 'visual_min_age', $context );
	}

	public function get_email_notification( $context = 'view' ) {
		return $this->get_prop( 'email_notification', $context );
	}

	public function has_email_notification() {
		return ( true === $this->get_email_notification() );
	}

	public function get_has_inlay_return( $context = 'view' ) {
		return $this->get_prop( 'has_inlay_return', $context );
	}

	public function has_inlay_return() {
		$products = wc_gzd_dhl_get_inlay_return_products();

		return ( true === $this->get_has_inlay_return() && in_array( $this->get_dhl_product(), $products ) );
	}

	/**
	 * Returns a directly linked return label.
	 *
	 * @return bool|ReturnLabel
	 */
	public function get_inlay_return_label() {
		return wc_gzd_dhl_get_return_label_by_parent( $this->get_id() );
	}

	/**
	 * Checks whether the label has a directly linked return label.
	 *
	 * @return bool
	 */
	public function has_inlay_return_label() {
		$label = $this->get_inlay_return_label();

		return $label ? true : false;
	}

	public function get_codeable_address_only( $context = 'view' ) {
		return $this->get_prop( 'codeable_address_only', $context );
	}

	public function codeable_address_only() {
		return ( true === $this->get_codeable_address_only() );
	}

	public function set_return_address( $value ) {
		$this->set_prop( 'return_address', empty( $value ) ? array() : (array) $value );
	}

	public function set_cod_total( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'cod_total', $value );
	}

	public function set_cod_includes_additional_total( $value ) {
		$this->set_prop( 'cod_includes_additional_total', wc_string_to_bool( $value ) );
	}

	public function set_duties( $duties ) {
		$this->set_prop( 'duties', $duties );
	}

	public function set_dhl_product( $product ) {
		$this->set_prop( 'dhl_product', $product );
	}

	public function set_preferred_day( $day ) {
		$this->set_date_prop( 'preferred_day', $day );
	}

	public function set_preferred_time_start( $time ) {
		$this->set_time_prop( 'preferred_time_start', $time );
	}

	public function set_preferred_time_end( $time ) {
		$this->set_time_prop( 'preferred_time_end', $time );
	}

	public function set_preferred_location( $location ) {
		$this->set_prop( 'preferred_location', $location );
	}

	public function set_preferred_neighbor( $neighbor ) {
		$this->set_prop( 'preferred_neighbor', $neighbor );
	}

	public function set_email_notification( $value ) {
		$this->set_prop( 'email_notification', wc_string_to_bool( $value ) );
	}

	public function set_has_inlay_return( $value ) {
		$this->set_prop( 'has_inlay_return', wc_string_to_bool( $value ) );
	}

	public function set_codeable_address_only( $value ) {
		$this->set_prop( 'codeable_address_only', wc_string_to_bool( $value ) );
	}

	public function set_ident_date_of_birth( $date ) {
		$this->set_date_prop( 'ident_date_of_birth', $date );
	}

	public function set_ident_min_age( $age ) {
		$this->set_prop( 'ident_min_age', $age );
	}

	public function set_visual_min_age( $age ) {
		$this->set_prop( 'visual_min_age', $age );
	}
}
