<?php

namespace Vendidero\Germanized\Shipments\Packaging;

use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

class ReportQueue {

	public static function start( $type = 'quarterly', $date = null, $end_date = null ) {
		$types = ReportHelper::get_available_report_types();

		if ( ! array_key_exists( $type, $types ) ) {
			return false;
		}

		$args     = self::get_timeframe( $type, $date, $end_date );
		$interval = $args['start']->diff( $args['end'] );

		// Add version
		$args['version'] = Package::get_version();

		$generator  = new AsyncReportGenerator( $type, $args );
		$queue_args = $generator->get_args();
		$queue      = self::get_queue();

		self::cancel( $generator->get_id() );

		$report = $generator->start();

		if ( is_a( $report, '\Vendidero\Germanized\Shipments\Packaging\Report' ) && $report->exists() ) {
			Package::log( sprintf( 'Starting new %1$s', $report->get_title() ) );
			Package::log( sprintf( 'Default report arguments: %s', wc_print_r( $queue_args, true ) ) );

			$queue->schedule_single(
				time() + 10,
				self::get_hook_name( $generator->get_id() ),
				array( 'args' => $queue_args ),
				'woocommerce_gzd_shipments'
			);

			$running = self::get_reports_running();

			if ( ! in_array( $generator->get_id(), $running, true ) ) {
				$running[] = $generator->get_id();
			}

			update_option( 'woocommerce_gzd_shipments_packaging_reports_running', $running, false );
			self::clear_cache();

			return $generator->get_id();
		}

		return false;
	}

	public static function clear_cache() {
		wp_cache_delete( 'woocommerce_gzd_shipments_packaging_reports_running', 'options' );
	}

	public static function get_queue_details( $report_id ) {
		$details = array(
			'next_date'      => null,
			'link'           => admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=' . esc_attr( $report_id ) . '&status=pending' ),
			'shipment_count' => 0,
			'has_action'     => false,
			'is_finished'    => false,
			'action'         => false,
		);

		if ( $queue = self::get_queue() ) {
			if ( $next_date = $queue->get_next( self::get_hook_name( $report_id ) ) ) {
				$details['next_date'] = $next_date;
			}

			$search_args = array(
				'hook'     => self::get_hook_name( $report_id ),
				'status'   => \ActionScheduler_Store::STATUS_RUNNING,
				'order'    => 'DESC',
				'per_page' => 1,
			);

			$results = $queue->search( $search_args );

			/**
			 * Search for pending as fallback
			 */
			if ( empty( $results ) ) {
				$search_args['status'] = \ActionScheduler_Store::STATUS_PENDING;
				$results               = $queue->search( $search_args );
			}

			/**
			 * Last resort: Search for completed (e.g. if no pending and no running are found - must have been completed)
			 */
			if ( empty( $results ) ) {
				$search_args['status'] = \ActionScheduler_Store::STATUS_COMPLETE;
				$results               = $queue->search( $search_args );
			}

			if ( ! empty( $results ) ) {
				$action    = array_values( $results )[0];
				$args      = $action->get_args();
				$processed = isset( $args['args']['processed'] ) ? (int) $args['args']['processed'] : 0;

				$details['shipment_count'] = absint( $processed );
				$details['has_action']     = true;
				$details['action']         = $action;
				$details['is_finished']    = $action->is_finished();
			}
		}

		return $details;
	}

	public static function get_batch_size() {
		return apply_filters( 'woocommerce_gzd_shipments_packaging_report_batch_size', 25 );
	}

	public static function get_shipment_statuses() {
		$statuses = array_keys( wc_gzd_get_shipment_statuses() );
		$statuses = array_diff( $statuses, array( 'gzd-draft', 'gzd-requested' ) );

		return apply_filters( 'woocommerce_gzd_shipments_packaging_report_valid_statuses', $statuses );
	}

	/**
	 * @param $args
	 *
	 * @return \Vendidero\Germanized\Shipments\Shipment[]
	 */
	public static function query( $args ) {
		$query_args = array(
			'date_created' => $args['start'] . '...' . $args['end'],
			'offset'       => $args['offset'],
			'type'         => $args['type'],
			'status'       => $args['status'],
			'limit'        => $args['limit'],
		);

		return wc_gzd_get_shipments( $query_args );
	}

	public static function cancel( $id ) {
		$data      = ReportHelper::get_report_data( $id );
		$generator = new AsyncReportGenerator( $data['type'], $data );
		$queue     = self::get_queue();
		$running   = self::get_reports_running();

		if ( self::is_running( $id ) ) {
			$running = array_diff( $running, array( $id ) );
			Package::log( sprintf( 'Cancelled %s', ReportHelper::get_report_title( $id ) ) );

			update_option( 'woocommerce_gzd_shipments_packaging_reports_running', $running, false );
			self::clear_cache();
			$generator->delete();
		}

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( self::get_hook_name( $id ) );
	}

	public static function get_queue() {
		return function_exists( 'WC' ) ? WC()->queue() : false;
	}

	public static function is_running( $id ) {
		$running = self::get_reports_running();

		if ( in_array( $id, $running, true ) && self::get_queue()->get_next( self::get_hook_name( $id ) ) ) {
			return true;
		}

		return false;
	}

	public static function get_hook_name( $id ) {
		if ( ! strstr( $id, 'woocommerce_gzd_shipments_' ) ) {
			$id = 'woocommerce_gzd_shipments_' . $id;
		}

		return $id;
	}

	public static function next( $type, $args ) {
		$generator = new AsyncReportGenerator( $type, $args );
		$result    = $generator->next();
		$is_empty  = false;
		$queue     = self::get_queue();

		if ( is_wp_error( $result ) ) {
			$is_empty = $result->get_error_message( 'empty' );
		}

		if ( ! $is_empty ) {
			$new_args = $generator->get_args();

			// Increase offset
			$new_args['offset'] = (int) $new_args['offset'] + (int) $new_args['limit'];

			$queue->cancel_all( self::get_hook_name( $generator->get_id() ) );

			Package::log( sprintf( 'Starting new queue: %s', wc_print_r( $new_args, true ) ) );

			$queue->schedule_single(
				time() + 10,
				self::get_hook_name( $generator->get_id() ),
				array( 'args' => $new_args ),
				'woocommerce_gzd_shipments'
			);
		} else {
			self::complete( $generator );
		}
	}

	/**
	 * @param AsyncReportGenerator $generator
	 */
	public static function complete( $generator ) {
		$queue = self::get_queue();
		$type  = $generator->get_type();

		/**
		 * Cancel outstanding events.
		 */
		$queue->cancel_all( self::get_hook_name( $generator->get_id() ) );

		$report = $generator->complete();
		$status = 'failed';

		if ( is_a( $report, '\Vendidero\Germanized\Shipments\Packaging\Report' ) && $report->exists() ) {
			$status = 'completed';
		}

		Package::log( sprintf( 'Completed %1$s. Status: %2$s', $report->get_title(), $status ) );

		self::maybe_stop_report( $report->get_id() );
	}

	public static function maybe_stop_report( $report_id ) {
		$reports_running = self::get_reports_running();

		if ( in_array( $report_id, $reports_running, true ) ) {
			$reports_running = array_diff( $reports_running, array( $report_id ) );
			update_option( 'woocommerce_gzd_shipments_packaging_reports_running', $reports_running, false );

			if ( $queue = self::get_queue() ) {
				$queue->cancel_all( self::get_hook_name( $report_id ) );
			}

			/**
			 * Force non-cached running option
			 */
			wp_cache_delete( 'woocommerce_gzd_shipments_packaging_reports_running', 'options' );

			return true;
		}

		return false;
	}

	public static function get_reports_running() {
		return (array) get_option( 'woocommerce_gzd_shipments_packaging_reports_running', array() );
	}

	public static function get_timeframe( $type, $date = null, $date_end = null ) {
		$date_start      = null;
		$date_end        = is_null( $date_end ) ? null : $date_end;
		$start_indicator = is_null( $date ) ? new \WC_DateTime() : $date;

		if ( ! is_a( $start_indicator, 'WC_DateTime' ) && is_numeric( $start_indicator ) ) {
			$start_indicator = new \WC_DateTime( '@' . $start_indicator );
		}

		if ( ! is_null( $date_end ) && ! is_a( $date_end, 'WC_DateTime' ) && is_numeric( $date_end ) ) {
			$date_end = new \WC_DateTime( '@' . $date_end );
		}

		if ( 'quarterly' === $type ) {
			$month       = $start_indicator->date( 'n' );
			$quarter     = (int) ceil( $month / 3 );
			$start_month = 'Jan';
			$end_month   = 'Mar';

			if ( 2 === $quarter ) {
				$start_month = 'Apr';
				$end_month   = 'Jun';
			} elseif ( 3 === $quarter ) {
				$start_month = 'Jul';
				$end_month   = 'Sep';
			} elseif ( 4 === $quarter ) {
				$start_month = 'Oct';
				$end_month   = 'Dec';
			}

			$date_start = new \WC_DateTime( 'first day of ' . $start_month . ' ' . $start_indicator->format( 'Y' ) . ' midnight' );
			$date_end   = new \WC_DateTime( 'last day of ' . $end_month . ' ' . $start_indicator->format( 'Y' ) . ' midnight' );
		} elseif ( 'monthly' === $type ) {
			$month = $start_indicator->format( 'M' );

			$date_start = new \WC_DateTime( 'first day of ' . $month . ' ' . $start_indicator->format( 'Y' ) . ' midnight' );
			$date_end   = new \WC_DateTime( 'last day of ' . $month . ' ' . $start_indicator->format( 'Y' ) . ' midnight' );
		} elseif ( 'yearly' === $type ) {
			$date_end   = clone $start_indicator;
			$date_start = clone $start_indicator;

			$date_end->modify( 'last day of dec ' . $start_indicator->format( 'Y' ) . ' midnight' );
			$date_start->modify( 'first day of jan ' . $start_indicator->format( 'Y' ) . ' midnight' );
		} else {
			if ( is_null( $date_end ) ) {
				$date_end = clone $start_indicator;
				$date_end->modify( '-1 year' );
			}

			$date_start = clone $start_indicator;
		}

		/**
		 * Always set start and end time to midnight
		 */
		if ( $date_start ) {
			$date_start->setTime( 0, 0 );
		}

		if ( $date_end ) {
			$date_end->setTime( 0, 0 );
		}

		return array(
			'start' => $date_start,
			'end'   => $date_end,
		);
	}
}
