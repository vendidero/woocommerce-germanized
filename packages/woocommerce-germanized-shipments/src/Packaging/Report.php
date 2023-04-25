<?php

namespace Vendidero\Germanized\Shipments\Packaging;

use Vendidero\Germanized\Shipments\Package;

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

		$args = wp_parse_args(
			$args,
			array(
				'packaging' => array(),
				'countries' => array(),
				'totals'    => array(),
				'meta'      => array(),
			)
		);

		$args['totals'] = wp_parse_args(
			$args['totals'],
			array(
				'weight_in_kg' => 0.0,
				'count'        => 0,
			)
		);

		$args['meta'] = wp_parse_args(
			$args['meta'],
			array(
				'date_requested' => null,
				'status'         => 'pending',
				'version'        => '',
			)
		);

		$this->set_date_requested( $args['meta']['date_requested'] );
		$this->set_status( $args['meta']['status'] );
		$this->set_version( $args['meta']['version'] );

		$this->args = $args;
	}

	public function exists() {
		return get_option( $this->id . '_result', false );
	}

	public function get_title() {
		$title = ReportHelper::get_report_title( $this->get_id() );

		if ( $this->get_date_requested() ) {
			$title = $title . ' @ ' . $this->get_date_requested()->date_i18n();
		}

		return $title;
	}

	public function get_url() {
		return admin_url( 'admin.php?page=shipment-packaging-report&report=' . $this->get_id() );
	}

	public function get_delete_link() {
		return add_query_arg(
			array(
				'action'    => 'wc_gzd_shipments_packaging_delete_report',
				'report_id' => $this->get_id(),
			),
			wp_nonce_url( admin_url( 'admin-post.php' ), 'wc_gzd_shipments_packaging_delete_report' )
		);
	}

	public function get_refresh_link() {
		return add_query_arg(
			array(
				'action'    => 'wc_gzd_shipments_packaging_refresh_report',
				'report_id' => $this->get_id(),
			),
			wp_nonce_url( admin_url( 'admin-post.php' ), 'wc_gzd_shipments_packaging_refresh_report' )
		);
	}

	public function get_cancel_link() {
		return add_query_arg(
			array(
				'action'    => 'wc_gzd_shipments_packaging_cancel_report',
				'report_id' => $this->get_id(),
			),
			wp_nonce_url( admin_url( 'admin-post.php' ), 'wc_gzd_shipments_packaging_cancel_report' )
		);
	}

	public function get_type() {
		return $this->type;
	}

	public function set_type( $type ) {
		$this->set_id_part( $type, 'type' );
	}

	public function set_id( $id ) {
		$this->id         = $id;
		$data             = ReportHelper::get_report_data( $this->id );
		$this->type       = $data['type'];
		$this->date_start = $data['date_start'];
		$this->date_end   = $data['date_end'];
	}

	public function set_id_part( $value, $part = 'type' ) {
		$data          = ReportHelper::get_report_data( $this->id );
		$data[ $part ] = $value;

		$this->set_id( ReportHelper::get_report_id( $data ) );
	}

	public function get_id() {
		return $this->id;
	}

	public function get_date_start() {
		return $this->date_start;
	}

	public function set_date_start( $date ) {
		$date = ReportHelper::string_to_datetime( $date );

		$this->set_id_part( $date->format( 'Y-m-d' ), 'date_start' );
	}

	public function get_date_end() {
		return $this->date_end;
	}

	public function set_date_end( $date ) {
		$date = ReportHelper::string_to_datetime( $date );

		$this->set_id_part( $date->format( 'Y-m-d' ), 'date_end' );
	}

	public function get_status() {
		return $this->args['meta']['status'];
	}

	public function get_version() {
		return $this->args['meta']['version'];
	}

	public function set_status( $status ) {
		$this->args['meta']['status'] = $status;
	}

	public function set_version( $version ) {
		$this->args['meta']['version'] = $version;
	}

	public function get_date_requested() {
		return is_null( $this->args['meta']['date_requested'] ) ? null : ReportHelper::string_to_datetime( $this->args['meta']['date_requested'] );
	}

	public function set_date_requested( $date ) {
		if ( ! empty( $date ) ) {
			$date = ReportHelper::string_to_datetime( $date );
		}

		$this->args['meta']['date_requested'] = is_a( $date, 'WC_DateTime' ) ? $date->date( 'Y-m-d' ) : null;
	}

	/**
	 * @return int
	 */
	public function get_total_count() {
		return (int) $this->args['totals']['count'];
	}

	public function get_total_weight( $round = true, $unit = '' ) {
		if ( '' === $unit ) {
			$unit = wc_gzd_get_packaging_weight_unit();
		}

		$weight = wc_get_weight( $this->args['totals']['weight_in_kg'], $unit, 'kg' );

		return $this->maybe_round( $weight, $round );
	}

	public function set_total_weight( $weight ) {
		$this->args['totals']['weight_in_kg'] = wc_format_decimal( floatval( $weight ) );
	}

	public function set_total_count( $count ) {
		$this->args['totals']['count'] = absint( $count );
	}

	public function get_packaging_ids() {
		return array_keys( $this->args['packaging'] );
	}

	public function get_countries() {
		return array_keys( $this->args['countries'] );
	}

	public function reset() {
		$this->args['packaging'] = array();
		$this->args['countries'] = array();

		$this->set_total_count( 0 );
		$this->set_total_weight( 0 );
		$this->set_date_requested( new \WC_DateTime() );
		$this->set_status( 'pending' );
		$this->set_version( Package::get_version() );

		delete_option( $this->id . '_tmp_result' );
	}

	public function get_packaging_ids_by_country( $country ) {
		$packaging_ids = array();

		if ( array_key_exists( $country, $this->args['countries'] ) ) {
			$packaging_ids = array_keys( $this->args['countries'][ $country ]['packaging'] );
		}

		return $packaging_ids;
	}

	public function get_packaging_count( $packaging_id, $country = '' ) {
		$count = 0;

		if ( '' === $country ) {
			if ( isset( $this->args['packaging'][ "$packaging_id" ] ) ) {
				$count = absint( $this->args['packaging'][ "$packaging_id" ]['count'] );
			}
		} else {
			if ( isset( $this->args['countries'][ $country ], $this->args['countries'][ $country ]['packaging'][ "$packaging_id" ] ) ) {
				$count = absint( $this->args['countries'][ $country ]['packaging'][ "$packaging_id" ]['count'] );
			}
		}

		return $count;
	}

	public function get_packaging_weight( $packaging_id, $country = '', $round = true, $unit = '' ) {
		$weight = 0.0;

		if ( '' === $country ) {
			if ( isset( $this->args['packaging'][ "$packaging_id" ] ) ) {
				$weight = $this->args['packaging'][ "$packaging_id" ]['weight_in_kg'];
			}
		} else {
			if ( isset( $this->args['countries'][ $country ], $this->args['countries'][ $country ]['packaging'][ "$packaging_id" ] ) ) {
				$weight = $this->args['countries'][ $country ]['packaging'][ "$packaging_id" ]['weight_in_kg'];
			}
		}

		$weight = wc_get_weight( $weight, $unit, 'kg' );

		return $this->maybe_round( $weight, $round );
	}

	public function get_total_packaging_weight_by_country( $country, $round = true, $unit = '' ) {
		$weight = 0.0;

		if ( isset( $this->args['countries'][ $country ] ) ) {
			$weight = $this->args['countries'][ $country ]['weight_in_kg'];
		}

		$weight = wc_get_weight( $weight, $unit, 'kg' );

		return $this->maybe_round( $weight, $round );
	}

	public function get_total_packaging_count_by_country( $country ) {
		$count = 0;

		if ( isset( $this->args['countries'][ $country ] ) ) {
			$count = absint( $this->args['countries'][ $country ]['count'] );
		}

		return $count;
	}

	public function set_packaging_count( $packaging_id, $count ) {
		if ( ! isset( $this->args['packaging'][ "$packaging_id" ] ) ) {
			$this->args['packaging'][ "$packaging_id" ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
			);
		}

		$this->args['packaging'][ "$packaging_id" ]['count'] = absint( $count );
	}

	public function set_packaging_weight( $packaging_id, $weight ) {
		if ( ! isset( $this->args['packaging'][ "$packaging_id" ] ) ) {
			$this->args['packaging'][ "$packaging_id" ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
			);
		}

		$this->args['packaging'][ "$packaging_id" ]['weight_in_kg'] = (float) wc_format_decimal( $weight );
	}

	public function set_packaging_count_by_country( $country, $packaging_id, $count ) {
		if ( ! isset( $this->args['countries'][ $country ] ) ) {
			$this->args['countries'][ $country ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
				'packaging'    => array(),
			);
		}

		if ( ! isset( $this->args['countries'][ $country ]['packaging'][ "$packaging_id" ] ) ) {
			$this->args['countries'][ $country ]['packaging'][ "$packaging_id" ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
			);
		}

		$this->args['countries'][ $country ]['packaging'][ "$packaging_id" ]['count'] = absint( $count );
	}

	public function set_packaging_weight_by_country( $country, $packaging_id, $weight ) {
		if ( ! isset( $this->args['countries'][ $country ] ) ) {
			$this->args['countries'][ $country ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
				'packaging'    => array(),
			);
		}

		if ( ! isset( $this->args['countries'][ $country ]['packaging'][ "$packaging_id" ] ) ) {
			$this->args['countries'][ $country ]['packaging'][ "$packaging_id" ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
			);
		}

		$this->args['countries'][ $country ]['packaging'][ "$packaging_id" ]['weight_in_kg'] = (float) wc_format_decimal( $weight );
	}

	public function set_total_packaging_count_by_country( $country, $count ) {
		if ( ! isset( $this->args['countries'][ $country ] ) ) {
			$this->args['countries'][ $country ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
				'packaging'    => array(),
			);
		}

		$this->args['countries'][ $country ]['count'] = absint( $count );
	}

	public function set_total_packaging_weight_by_country( $country, $weight ) {
		if ( ! isset( $this->args['countries'][ $country ] ) ) {
			$this->args['countries'][ $country ] = array(
				'count'        => 0,
				'weight_in_kg' => 0.0,
				'packaging'    => array(),
			);
		}

		$this->args['countries'][ $country ]['weight_in_kg'] = (float) wc_format_decimal( $weight );
	}

	protected function maybe_round( $total, $round = true ) {
		$decimals = is_numeric( $round ) ? (int) $round : '';

		return (float) wc_format_decimal( $total, $round ? $decimals : false );
	}

	public function save() {
		update_option( $this->id . '_result', $this->args, false );

		$reports_available = ReportHelper::get_report_ids();

		if ( ! in_array( $this->get_id(), $reports_available[ $this->get_type() ], true ) ) {
			// Add new report to start of the list
			array_unshift( $reports_available[ $this->get_type() ], $this->get_id() );
			update_option( 'woocommerce_gzd_shipments_packaging_reports', $reports_available, false );
		}

		delete_option( $this->id . '_tmp_result' );

		ReportHelper::clear_caches();

		return $this->id;
	}

	public function delete() {
		delete_option( $this->id . '_result' );
		delete_option( $this->id . '_tmp_result' );

		ReportQueue::maybe_stop_report( $this->get_id() );
		ReportHelper::remove_report( $this );

		ReportHelper::clear_caches();

		return true;
	}
}
