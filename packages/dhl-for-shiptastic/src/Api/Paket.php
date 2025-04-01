<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\API\Helper;
use Vendidero\Shiptastic\DHL\Package;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Vendidero\Shiptastic\DHL\ParcelServices;

defined( 'ABSPATH' ) || exit;

class Paket {

	protected $is_debug_mode = false;

	public function __construct( $is_debug_mode = false ) {
		$this->is_debug_mode = $is_debug_mode;
	}

	protected function is_debug_mode() {
		return $this->is_debug_mode;
	}

	/**
	 * @return LabelSoap|LabelRest|\Vendidero\Shiptastic\Interfaces\Api
	 * @throws Exception
	 */
	public function get_label_api() {
		if ( Package::use_legacy_soap_api() ) {
			try {
				return Helper::get_api( 'dhl_paket_label_soap', $this->is_debug_mode() );
			} catch ( Exception $e ) {
				throw new Exception( esc_html( sprintf( _x( 'Label API not available: %s', 'dhl', 'woocommerce-germanized' ), $e->getMessage() ) ) );
			}
		} else {
			return $this->get_label_rest_api();
		}
	}

	public function get_label_rest_api() {
		return Helper::get_api( 'dhl_paket_label_rest', $this->is_debug_mode() );
	}

	/**
	 * @return LocationFinder|false
	 */
	public function get_finder_api() {
		return Helper::get_api( 'dhl_location_finder', $this->is_debug_mode() );
	}

	/**
	 * @return ReturnRest|\Vendidero\Shiptastic\Interfaces\Api
	 */
	public function get_return_api() {
		return Helper::get_api( 'dhl_paket_return_rest', $this->is_debug_mode() );
	}

	public function get_parcel_api() {
		return $this->get_parcel_services_api();
	}

	/**
	 * @return \Vendidero\Shiptastic\DHL\Api\ParcelServices|\Vendidero\Shiptastic\Interfaces\Api
	 */
	public function get_parcel_services_api() {
		return Helper::get_api( 'dhl_paket_parcel_services', $this->is_debug_mode() );
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function test_connection() {
		try {
			return $this->get_label_api()->test_connection();
		} catch ( \Exception $e ) {
			$error = new \WP_Error();
			$error->add( $e->getCode(), $e->getMessage() );

			return $error;
		}
	}

	public function get_return_label( &$label ) {
		return $this->get_return_api()->get_return_label( $label );
	}

	public function get_label( &$label ) {
		return $this->get_label_api()->get_label( $label );
	}

	public function delete_label( &$label ) {
		if ( $api = $this->get_label_api() ) {
			return $api->delete_label( $label );
		}

		return false;
	}

	protected function is_holiday( $datetime ) {
		return ( in_array( $datetime->format( 'Y-m-d' ), Package::get_holidays( 'DE' ), true ) ) ? true : false;
	}

	protected function is_working_day( $datetime ) {
		$is_working_day = ! $this->is_holiday( $datetime );

		if ( $is_working_day ) {
			/**
			 * Filter to decide whether DHL should consider saturday as a working day
			 * for preferred day calculation or not.
			 *
			 * @param boolean $is_working_day True if saturday should be considered a working day.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Shiptastic/DHL
			 */
			if ( apply_filters( 'woocommerce_shiptastic_dhl_consider_saturday_as_working_day', true ) ) {
				$is_working_day = $datetime->format( 'N' ) > 6 ? false : true;
			} else {
				$is_working_day = $datetime->format( 'N' ) > 5 ? false : true;
			}
		}

		return $is_working_day;
	}

	/**
	 * This method calculates the starting date for the preferred day time option
	 * and calls the DHL API to retrieve days and times to be chosen by the user in the frontend.
	 *
	 * Starting date calculation works as follows:
	 * 1. If preparation days are set -> add x working days to the current date
	 * 2. If current time is greater than cutoff time -> add one working day
	 * 3. If excluded working days have been chosen -> add x working days
	 * 4. Statically add 2 days for DHL
	 *
	 * @param $postcode
	 * @param string $cutoff_time
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_preferred_available_days( $postcode, $cutoff_time = '' ) {
		$exclude_working_days = wc_stc_dhl_get_excluded_working_days();

		// Always exclude Sunday
		$exclude_working_days = array_merge( $exclude_working_days, array( 'sun' ) );

		// Check if every day is excluded -> prevent infinite loops
		if ( count( $exclude_working_days ) >= 7 ) {
			return array();
		}

		$preparation_days = ParcelServices::get_preferred_day_preparation_days();
		$cutoff_time      = empty( $cutoff_time ) ? ParcelServices::get_preferred_day_cutoff_time() : $cutoff_time;
		$tz_obj           = new DateTimeZone( 'Europe/Berlin' );
		$starting_date    = new DateTime( 'now', $tz_obj );
		$days_added       = 0;

		// Add preparation days
		if ( ! empty( $preparation_days ) ) {
			while ( ! $this->is_working_day( $starting_date ) || $days_added < $preparation_days ) {
				$starting_date->add( new DateInterval( 'P1D' ) );
				++$days_added;
			}
		}

		// In case no preparation days have been added and current time is greater than cutoff time -> add one working day
		if ( $days_added <= 0 && $starting_date->format( 'Hi' ) > str_replace( ':', '', $cutoff_time ) ) {
			$starting_date->add( new \DateInterval( 'P1D' ) );
		}

		// Add days as long as starting date is excluded or is not a working day
		while ( in_array( strtolower( $starting_date->format( 'D' ) ), $exclude_working_days, true ) || ! $this->is_working_day( $starting_date ) ) {
			$starting_date->add( new DateInterval( 'P1D' ) );
		}

		// Add 2 working days (for DHL)
		$days_added = 0;

		while ( ! $this->is_working_day( $starting_date ) ) {
			$starting_date->add( new DateInterval( 'P1D' ) );
			++$days_added;
		}

		$args['postcode']   = $postcode;
		$args['start_date'] = $starting_date->format( 'Y-m-d' );

		try {
			$preferred_services = $this->get_parcel_services_api()->get_services( $args );
			$preferred_days     = $this->get_preferred_days( $preferred_services );
		} catch ( Exception $e ) {
			throw $e;
		}

		return $preferred_days;
	}

	protected function get_preferred_days( $preferred_services ) {
		$day_of_week_arr = array(
			'1' => _x( 'Mon', 'dhl', 'woocommerce-germanized' ),
			'2' => _x( 'Tue', 'dhl', 'woocommerce-germanized' ),
			'3' => _x( 'Wed', 'dhl', 'woocommerce-germanized' ),
			'4' => _x( 'Thu', 'dhl', 'woocommerce-germanized' ),
			'5' => _x( 'Fri', 'dhl', 'woocommerce-germanized' ),
			'6' => _x( 'Sat', 'dhl', 'woocommerce-germanized' ),
			'7' => _x( 'Sun', 'dhl', 'woocommerce-germanized' ),
		);

		$preferred_days = array();

		$tz_obj = new DateTimeZone( 'Europe/Berlin' );

		if ( isset( $preferred_services->preferredDay->available ) && $preferred_services->preferredDay->available && isset( $preferred_services->preferredDay->validDays ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $preferred_services->preferredDay->validDays as $days_key => $days_value ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$starting_date = new DateTime( $days_value->start, $tz_obj );
				$day_of_week   = $starting_date->format( 'N' );
				$week_date     = $starting_date->format( 'Y-m-d' );

				$preferred_days[ $week_date ] = $day_of_week_arr[ $day_of_week ];
			}

			// Add none option
			array_unshift( $preferred_days, _x( 'None', 'dhl day context', 'woocommerce-germanized' ) );
		}

		return $preferred_days;
	}
}
