<?php

namespace Vendidero\Germanized\Shipments\Packaging;

use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

class AsyncReportGenerator {

	protected $args = array();

	protected $type = '';

	public function __construct( $type = 'yearly', $args = array() ) {
		$this->type    = $type;
		$default_end   = new \WC_DateTime();
		$default_start = new \WC_DateTime( 'now' );
		$default_start->modify( '-1 year' );

		$args = wp_parse_args(
			$args,
			array(
				'start'     => $default_start->format( 'Y-m-d' ),
				'end'       => $default_end->format( 'Y-m-d' ),
				'limit'     => ReportQueue::get_batch_size(),
				'status'    => ReportQueue::get_shipment_statuses(),
				'offset'    => 0,
				'type'      => apply_filters( 'woocommerce_gzd_shipments_packaging_report_default_shipment_types', array( 'simple' ) ),
				'processed' => 0,
			)
		);

		foreach ( array( 'start', 'end' ) as $date_field ) {
			if ( is_a( $args[ $date_field ], 'WC_DateTime' ) ) {
				$args[ $date_field ] = $args[ $date_field ]->format( 'Y-m-d' );
			} elseif ( is_numeric( $args[ $date_field ] ) ) {
				$date                = new \WC_DateTime( '@' . $args[ $date_field ] );
				$args[ $date_field ] = $date->format( 'Y-m-d' );
			}
		}

		$this->args = $args;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_args() {
		return $this->args;
	}

	public function get_id() {
		return ReportHelper::get_report_id(
			array(
				'type'       => $this->type,
				'date_start' => $this->args['start'],
				'date_end'   => $this->args['end'],
			)
		);
	}

	public function delete() {
		$report = new Report( $this->get_id() );
		$report->delete();

		delete_option( $this->get_id() . '_tmp_result' );
	}

	public function start() {
		$report = new Report( $this->get_id() );
		$report->reset();
		$report->save();

		return $report;
	}

	/**
	 * @return true|\WP_Error
	 */
	public function next() {
		$args                = $this->args;
		$shipments           = ReportQueue::query( $args );
		$shipments_processed = 0;
		$packaging_data      = $this->get_temporary_result();

		Package::log( sprintf( '%d applicable shipments found', count( $shipments ) ) );

		if ( ! empty( $shipments ) ) {
			foreach ( $shipments as $shipment ) {
				$packaging_weight = $shipment->get_packaging_weight();
				$packaging        = $shipment->get_packaging();
				$country          = $shipment->get_country();
				$packaging_id     = 'other';

				if ( ! $shipment->get_packaging_weight() ) {
					Package::log( sprintf( 'Skipping shipment #%1$s due to missing packaging weight.', $shipment->get_id() ) );
					continue;
				}

				if ( $packaging ) {
					$packaging_id = $packaging->get_id();
				}

				if ( ! isset( $packaging_data[ $country ][ "$packaging_id" ] ) ) {
					$packaging_data[ $country ][ "$packaging_id" ] = array(
						'count'        => 0,
						'weight_in_kg' => 0.0,
					);
				}

				$packaging_data[ $country ][ "$packaging_id" ]['count']        += 1;
				$packaging_data[ $country ][ "$packaging_id" ]['weight_in_kg'] += wc_add_number_precision( (float) wc_get_weight( $packaging_weight, 'kg', $shipment->get_weight_unit() ), false );

				$shipments_processed++;
			}

			$this->args['processed'] = absint( $this->args['processed'] ) + $shipments_processed;

			update_option( $this->get_id() . '_tmp_result', $packaging_data, false );

			return true;
		} else {
			return new \WP_Error( 'empty', _x( 'No shipments found.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @return Report
	 */
	public function complete() {
		Package::log( sprintf( 'Completed called' ) );

		$tmp_result       = $this->get_temporary_result();
		$report           = new Report( $this->get_id() );
		$count_total      = 0;
		$weight_total     = 0.0;
		$packaging_totals = array();

		foreach ( $tmp_result as $country => $packaging_ids ) {
			$country_count_total  = 0;
			$country_weight_total = 0.0;

			foreach ( $packaging_ids as $packaging_id => $totals ) {
				$count_total          += (int) $totals['count'];
				$weight_total         += (float) $totals['weight_in_kg'];
				$country_count_total  += (int) $totals['count'];
				$country_weight_total += (float) $totals['weight_in_kg'];

				if ( ! isset( $packaging_totals[ "$packaging_id" ] ) ) {
					$packaging_totals[ "$packaging_id" ] = array(
						'count'        => 0,
						'weight_in_kg' => 0.0,
					);
				}

				$packaging_totals[ "$packaging_id" ]['count']        += (int) $totals['count'];
				$packaging_totals[ "$packaging_id" ]['weight_in_kg'] += (float) $totals['weight_in_kg'];

				$report->set_packaging_count_by_country( $country, $packaging_id, (int) $totals['count'] );
				$report->set_packaging_weight_by_country( $country, $packaging_id, (float) wc_remove_number_precision( $totals['weight_in_kg'] ) );
			}

			$country_weight_total = (float) wc_remove_number_precision( $country_weight_total );

			$report->set_total_packaging_count_by_country( $country, $country_count_total );
			$report->set_total_packaging_weight_by_country( $country, $country_weight_total );
		}

		foreach ( $packaging_totals as $packaging_id => $totals ) {
			$packaging_weight_total = (float) wc_remove_number_precision( $totals['weight_in_kg'] );

			$report->set_packaging_count( $packaging_id, $totals['count'] );
			$report->set_packaging_weight( $packaging_id, $packaging_weight_total );
		}

		$weight_total = (float) wc_remove_number_precision( $weight_total );

		Package::log( sprintf( 'Completed packaging count: %d', $count_total ) );
		Package::log( sprintf( 'Completed packaging weight: %s', $weight_total ) );

		$report->set_total_count( $count_total );
		$report->set_total_weight( $weight_total );
		$report->set_status( 'completed' );
		$report->set_version( Package::get_version() );
		$report->save();

		return $report;
	}

	protected function get_temporary_result() {
		return (array) get_option( $this->get_id() . '_tmp_result', array() );
	}
}
