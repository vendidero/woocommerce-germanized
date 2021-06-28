<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

class Report {

	private $id;

	private $args = array();

	private $type = 'yearly';

	/**
	 * @var \WC_DateTime
	 */
	private $date_start = null;

	/**
	 * @var \WC_DateTime
	 */
	private $date_end = null;

	public function __construct( $id, $args = array() ) {
		$this->set_id( $id );

		if ( empty( $args ) ) {
			$args = (array) get_option( $this->id . '_result', array() );
		}

		$args = wp_parse_args( $args, array(
			'countries' => array(),
			'totals'    => array(),
			'meta'      => array(),
		) );

		$args['totals'] = wp_parse_args( $args['totals'], array(
			'net_total' => 0,
			'tax_total' => 0
		) );

		$args['meta'] = wp_parse_args( $args['meta'], array(
			'date_requested' => null,
			'status'         => 'pending'
		) );

		$this->set_date_requested( $args['meta']['date_requested'] );
		$this->set_status( $args['meta']['status'] );

		$this->args = $args;
	}

	public function exists() {
		return get_option( $this->id . '_result', false );
	}

	public function get_title() {
		$title = Package::get_report_title( $this->get_id() );

		if ( $this->get_date_requested() ) {
			$title = $title . ' @ ' . $this->get_date_requested()->date_i18n();
		}

		return $title;
	}

	public function get_url() {
		return admin_url( 'admin.php?page=oss-reports&report=' . $this->get_id() );
	}

	public function get_type() {
		return $this->type;
	}

	public function set_type( $type ) {
		$this->set_id_part( $type, 'type' );
	}

	public function set_id( $id ) {
		$this->id         = $id;
		$data             = Package::get_report_data( $this->id );
		$this->type       = $data['type'];
		$this->date_start = $data['date_start'];
		$this->date_end   = $data['date_end'];
	}

	public function set_id_part( $value, $part = 'type' ) {
		$data          = Package::get_report_data( $this->id );
		$data[ $part ] = $value;

		$this->set_id( Package::get_report_id( $data ) );
	}

	public function get_id() {
		return $this->id;
	}

	public function get_date_start() {
		return $this->date_start;
	}

	public function set_date_start( $date ) {
		$date = Package::string_to_datetime( $date );

		$this->set_id_part( $date->format( 'Y-m-d' ), 'date_start' );
	}

	public function get_date_end() {
		return $this->date_end;
	}

	public function set_date_end( $date ) {
		$date = Package::string_to_datetime( $date );

		$this->set_id_part( $date->format( 'Y-m-d' ), 'date_end' );
	}

	public function get_status() {
		return $this->args['meta']['status'];
	}

	public function set_status( $status ) {
		$this->args['meta']['status'] = $status;
	}

	public function get_date_requested() {
		return is_null( $this->args['meta']['date_requested'] ) ? null : Package::string_to_datetime( $this->args['meta']['date_requested'] );
	}

	public function set_date_requested( $date ) {
		if ( ! empty( $date ) ) {
			$date = Package::string_to_datetime( $date );
		}

		$this->args['meta']['date_requested'] = is_a( $date, 'WC_DateTime' ) ? $date->date( 'Y-m-d' ) : null;
	}

	public function get_tax_total( $round = true ) {
		return $this->maybe_round( $this->args['totals']['tax_total'], $round );
	}

	public function get_net_total( $round = true ) {
		return $this->maybe_round( $this->args['totals']['net_total'], $round );
	}

	public function set_tax_total( $total ) {
		$this->args['totals']['tax_total'] = wc_format_decimal( floatval( $total ) );
	}

	public function set_net_total( $total ) {
		$this->args['totals']['net_total'] = wc_format_decimal( floatval( $total ) );
	}

	public function get_countries() {
		return array_keys( $this->args['countries'] );
	}

	public function reset() {
		$this->args['countries'] = array();

		$this->set_net_total( 0 );
		$this->set_tax_total( 0 );
		$this->set_date_requested( new \WC_DateTime() );
		$this->set_status( 'pending' );

		delete_option( $this->id . '_tmp_result' );
	}

	public function get_tax_rates_by_country( $country ) {
		$tax_rates = array();

		if ( array_key_exists( $country, $this->args['countries'] ) ) {
			$tax_rates = array_keys( $this->args['countries'][ $country ] );
		}

		return $tax_rates;
	}

	public function get_country_tax_total( $country, $tax_rate, $round = true ) {
		$tax_total = 0;

		if ( isset( $this->args['countries'][ $country ], $this->args['countries'][ $country ][ $tax_rate ] ) ) {
			$tax_total = $this->args['countries'][ $country ][ $tax_rate ]['tax_total'];
		}

		return $this->maybe_round( $tax_total, $round );
	}

	protected function maybe_round( $total, $round = true ) {
		$decimals = is_numeric( $round ) ? (int) $round : '';

		return (float) wc_format_decimal( $total, $round ? $decimals : false );
	}

	public function get_country_net_total( $country, $tax_rate, $round = true ) {
		$net_total = 0;

		if ( isset( $this->args['countries'][ $country ], $this->args['countries'][ $country ][ $tax_rate ] ) ) {
			$net_total = $this->args['countries'][ $country ][ $tax_rate ]['net_total'];
		}

		return $this->maybe_round( $net_total, $round );
	}

	public function set_country_tax_total( $country, $tax_rate, $tax_total = 0 ) {
		if ( ! isset( $this->args['countries'][ $country ] ) ) {
			$this->args['countries'][ $country ] = array();
		}

		if ( ! isset( $this->args['countries'][ $country ][ $tax_rate ] ) ) {
			$this->args['countries'][ $country ][ $tax_rate ] = array(
				'net_total' => 0,
				'tax_total' => 0,
			);
		}

		$this->args['countries'][ $country ][ $tax_rate ]['tax_total'] = $tax_total;
	}

	public function set_country_net_total( $country, $tax_rate, $net_total = 0 ) {
		if ( ! isset( $this->args['countries'][ $country ] ) ) {
			$this->args['countries'][ $country ] = array();
		}

		if ( ! isset( $this->args['countries'][ $country ][ $tax_rate ] ) ) {
			$this->args['countries'][ $country ][ $tax_rate ] = array(
				'net_total' => 0,
				'tax_total' => 0,
			);
		}

		$this->args['countries'][ $country ][ $tax_rate ]['net_total'] = $net_total;
	}

	public function save() {
		update_option( $this->id . '_result', $this->args );

		$reports_available = Package::get_report_ids();

		if ( ! in_array( $this->get_id(), $reports_available[ $this->get_type() ] ) ) {
			// Add new report to start of the list
			array_unshift( $reports_available[ $this->get_type() ], $this->get_id() );
			update_option( 'oss_woocommerce_reports', $reports_available );
		}

		delete_option( $this->id . '_tmp_result' );

		Package::clear_caches();

		return $this->id;
	}

	public function delete() {
		delete_option( $this->id . '_result' );
		delete_option( $this->id . '_tmp_result' );

		$reports_available = Package::get_report_ids();

		if ( in_array( $this->get_id(), $reports_available[ $this->get_type() ] ) ) {
			$reports_available[ $this->get_type() ] = array_diff( $reports_available[ $this->get_type() ], array( $this->get_id() ) );
			update_option( 'oss_woocommerce_reports', $reports_available );
		}

		if ( 'observer' === $this->get_type() ) {
			delete_option( 'oss_woocommerce_observer_report_' . $this->get_date_start()->format( 'Y' ) );
		}

		Package::clear_caches();

		return true;
	}

	public function get_export_link() {
		return add_query_arg( array( 'action' => 'oss_export_report', 'report_id' => $this->get_id() ), wp_nonce_url( admin_url( 'admin-post.php' ), 'oss_export_report' ) );
	}

	public function get_delete_link() {
		return add_query_arg( array( 'action' => 'oss_delete_report', 'report_id' => $this->get_id() ), wp_nonce_url( admin_url( 'admin-post.php' ), 'oss_delete_report' ) );
	}

	public function get_refresh_link() {
		return add_query_arg( array( 'action' => 'oss_refresh_report', 'report_id' => $this->get_id() ), wp_nonce_url( admin_url( 'admin-post.php' ), 'oss_refresh_report' ) );
	}

	public function get_cancel_link() {
		return add_query_arg( array( 'action' => 'oss_cancel_report', 'report_id' => $this->get_id() ), wp_nonce_url( admin_url( 'admin-post.php' ), 'oss_cancel_report' ) );
	}
}
