<?php

namespace Vendidero\Shiptastic\Tracking;

use Vendidero\Shiptastic\Shipment;

defined( 'ABSPATH' ) || exit;

class ShipmentStatus {

	protected $status = '';

	protected $status_description = '';

	protected $is_delivered = false;

	protected $is_in_transit = false;

	protected $last_updated = null;

	protected $delivered_at = null;

	protected $ice = '';

	protected $meta = array();

	/**
	 * @var Shipment|null
	 */
	protected $shipment = null;

	/**
	 * @param Shipment $shipment
	 * @param $args
	 */
	public function __construct( $shipment, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'status'             => '',
				'status_description' => '',
				'is_delivered'       => false,
				'is_in_transit'      => false,
				'last_updated'       => null,
				'delivered_at'       => null,
				'ice'                => '',
			)
		);

		foreach ( $args as $arg_name => $value ) {
			if ( is_callable( array( $this, "set_{$arg_name}" ) ) ) {
				$this->{"set_{$arg_name}"}( $value );
			} else {
				$this->update_meta_data( $arg_name, $value );
			}
		}

		$this->shipment = $shipment;
	}

	/**
	 * @return Shipment|null
	 */
	public function get_shipment() {
		return $this->shipment;
	}

	public function set_status( $status ) {
		$this->status = $status;
	}

	public function update_meta_data( $meta_key, $meta_value ) {
		$this->meta[ $meta_key ] = $meta_value;
	}

	public function get_meta( $meta_key, $default_value = null ) {
		return array_key_exists( $meta_key, $this->meta ) ? $this->meta[ $meta_key ] : $default_value;
	}

	public function set_status_description( $status ) {
		$this->status_description = $status;
	}

	public function set_ice( $ice ) {
		$this->ice = $ice;
	}

	public function set_is_delivered( $is_delivered ) {
		$this->is_delivered = wc_string_to_bool( $is_delivered );
	}

	public function set_is_in_transit( $is_in_transit ) {
		$this->is_in_transit = wc_string_to_bool( $is_in_transit );
	}

	/**
	 * @param \WC_DateTime|null $last_updated
	 *
	 * @return void
	 */
	public function set_last_updated( $last_updated ) {
		if ( empty( $last_updated ) ) {
			$this->last_updated = null;
			return;
		}

		if ( ! is_a( $last_updated, 'WC_DateTime' ) ) {
			try {
				$last_updated = new \WC_DateTime( $last_updated, new \DateTimeZone( 'UTC' ) );
			} catch ( \Exception $e ) {
				$last_updated = null;
			}
		}

		$this->last_updated = $last_updated;
	}

	/**
	 * @param \WC_DateTime|null $delivered_at
	 *
	 * @return void
	 */
	public function set_delivered_at( $delivered_at ) {
		if ( empty( $delivered_at ) ) {
			$this->delivered_at = null;
			return;
		}

		if ( ! is_a( $delivered_at, 'WC_DateTime' ) ) {
			try {
				$delivered_at = new \WC_DateTime( $delivered_at, new \DateTimeZone( 'UTC' ) );
			} catch ( \Exception $e ) {
				$delivered_at = null;
			}
		}

		$this->delivered_at = $delivered_at;
	}

	public function get_status() {
		return $this->status;
	}

	/**
	 * International Coded Event
	 *
	 * @example see attachment https://developer.dhl.com/api-reference/dhl-paket-de-sendungsverfolgung-post-paket-deutschland#downloads-section
	 *
	 * @return string
	 */
	public function get_ice() {
		return $this->ice;
	}

	public function get_status_description() {
		return $this->status_description;
	}

	public function get_is_delivered() {
		return $this->is_delivered;
	}

	public function is_delivered() {
		return true === $this->get_is_delivered();
	}

	public function get_is_in_transit() {
		return $this->is_in_transit;
	}

	public function is_in_transit() {
		return true === $this->get_is_in_transit();
	}

	/**
	 * @return null|\WC_DateTime
	 */
	public function get_last_updated() {
		return $this->last_updated;
	}

	/**
	 * @return null|\WC_DateTime
	 */
	public function get_delivered_at() {
		return $this->delivered_at;
	}

	public function get_data() {
		$data = array(
			'status'             => $this->get_status(),
			'status_description' => $this->get_status_description(),
			'is_delivered'       => wc_bool_to_string( $this->get_is_delivered() ),
			'is_in_transit'      => wc_bool_to_string( $this->get_is_in_transit() ),
			'last_updated'       => $this->get_last_updated() ? $this->get_last_updated()->getTimestamp() : null,
			'delivered_at'       => $this->get_delivered_at() ? $this->get_delivered_at()->getTimestamp() : null,
			'ice'                => $this->get_ice(),
		);

		foreach ( $this->meta as $key => $value ) {
			$data[ $key ] = $value;
		}

		return $data;
	}
}
