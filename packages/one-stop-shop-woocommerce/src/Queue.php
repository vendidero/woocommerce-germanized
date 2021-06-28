<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

class Queue {

	public static function start( $type = 'quarterly', $date = null, $end_date = null ) {
		$types = Package::get_available_report_types( true );

		if ( ! array_key_exists( $type, $types ) ) {
			return false;
		}

		$args     = self::get_timeframe( $type, $date, $end_date );
		$interval = $args['start']->diff( $args['end'] );

		$generator  = new AsyncReportGenerator( $type, $args );
		$queue_args = $generator->get_args();
		$queue      = self::get_queue();

		self::cancel( $generator->get_id() );

		$report = $generator->start();

		if ( is_a( $report, '\Vendidero\OneStopShop\Report' ) && $report->exists() ) {
			Package::log( sprintf( 'Starting new %1$s', $report->get_title() ) );
			Package::extended_log( sprintf( 'Default report arguments: %s', wc_print_r( $queue_args, true ) ) );

			$queue->schedule_single(
				time() + 10,
				'oss_woocommerce_' . $generator->get_id(),
				array( 'args' => $queue_args ),
				'oss_woocommerce'
			);

			$running = self::get_reports_running();

			if ( ! in_array( $generator->get_id(), $running ) ) {
				$running[] = $generator->get_id();
			}

			update_option( 'oss_woocommerce_reports_running', $running );

			return $generator->get_id();
		}

		return false;
	}

	public static function get_queue_details( $report_id ) {
		$details = array(
			'next_date'   => null,
			'link'        => admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=' . esc_attr( $report_id ) .'&status=pending' ),
			'order_count' => 0,
		);

		if ( $queue = self::get_queue() ) {

			if ( $next_date = $queue->get_next( 'oss_woocommerce_' . $report_id ) ) {
				$details['next_date'] = $next_date;
			}

			$search_args = array(
				'hook'     => 'oss_woocommerce_' . $report_id,
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
				$results = $queue->search( $search_args );
			}

			/**
			 *  Last resort: Search for completed (e.g. if no pending and no running are found - must have been completed)
			 */
			if ( empty( $results ) ) {
				$search_args['status'] = \ActionScheduler_Store::STATUS_COMPLETE;
				$results = $queue->search( $search_args );
			}

			if ( ! empty( $results ) ) {
				$action    = array_values( $results )[0];
				$args      = $action->get_args();
				$processed = isset( $args['args']['orders_processed'] ) ? (int) $args['args']['orders_processed'] : 0;

				$details['order_count'] = absint( $processed );
			}
		}

		return $details;
	}

	public static function get_batch_size() {
		return apply_filters( 'oss_woocommerce_report_batch_size', 25 );
	}

	public static function use_date_paid() {
		return apply_filters( 'oss_woocommerce_report_use_date_paid', true );
	}

	public static function get_order_statuses() {
		$statuses = array_keys( wc_get_order_statuses() );
		$statuses = array_diff( $statuses, array( 'wc-refunded', 'wc-pending', 'wc-cancelled', 'wc-failed' ) );

		return apply_filters( 'oss_woocommerce_valid_order_statuses', $statuses );
	}

	public static function get_order_query_args( $args, $date_field = 'date_created' ) {
		/**
		 * Add one day to the end date to capture timestamps (including time data) in between
		 */
		if ( 'date_paid' === $date_field ) {
			$args['start'] = strtotime( $args['start'] );
			$args['end']   = strtotime( $args['end'] ) + DAY_IN_SECONDS;
		}

		$query_args = array(
			'limit'           => $args['limit'],
			'orderby'         => 'date',
			'order'           => 'ASC',
			$date_field       => $args['start'] . '...' . $args['end'],
			'offset'          => $args['offset'],
			'taxable_country' => Package::get_non_base_eu_countries( true ),
			'type'            => array( 'shop_order' ),
			'status'          => $args['status']
		);

		return $query_args;
	}

	public static function cancel( $id ) {
		$data      = Package::get_report_data( $id );
		$generator = new AsyncReportGenerator( $data['type'], $data );
		$queue     = self::get_queue();
		$running   = self::get_reports_running();

		if ( self::is_running( $id ) ) {
			$running = array_diff( $running, array( $id ) );

			Package::log( sprintf( 'Cancelled %s', Package::get_report_title( $id ) ) );

			update_option( 'oss_woocommerce_reports_running', $running );
			$generator->delete();
		}

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'oss_woocommerce_' . $id );
	}

	public static function get_queue() {
		return function_exists( 'WC' ) ? WC()->queue() : false;
	}

	public static function is_running( $id ) {
		$running = self::get_reports_running();

		if ( in_array( $id, $running ) && self::get_queue()->get_next( 'oss_woocommerce_' . $id ) ) {
			return true;
		}

		return false;
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
			$new_args['offset'] = $new_args['offset'] + $new_args['limit'];

			$queue->schedule_single(
				time() + 10,
				'oss_woocommerce_' . $generator->get_id(),
				array( 'args' => $new_args ),
				'oss_woocommerce'
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
		$queue->cancel_all( 'oss_woocommerce_' . $generator->get_id() );

		$report = $generator->complete();
		$status = 'failed';

		if ( is_a( $report, '\Vendidero\OneStopShop\Report' ) && $report->exists() ) {
			$status = 'completed';
		}

		Package::log( sprintf( 'Completed %1$s. Status: %2$s', $report->get_title(), $status ) );

		$running = self::get_reports_running();

		if ( in_array( $generator->get_id(), $running ) ) {
			$running = array_diff( $running, array( $generator->get_id() ) );
		}

		update_option( 'oss_woocommerce_reports_running', $running );

		if ( 'observer' === $report->get_type() ) {
			self::update_observer( $report );
		}
	}

	/**
	 * @param Report $report
	 */
	protected static function update_observer( $report ) {
		$end  = $report->get_date_end();
		$year = $end->date( 'Y' );

		if ( ! $observer_report = Package::get_observer_report( $year ) ) {
			$observer_report = $report;
		} else {
			$observer_report->set_net_total( $observer_report->get_net_total( false ) + $report->get_net_total( false ) );
			$observer_report->set_tax_total( $observer_report->get_tax_total( false ) + $report->get_tax_total( false ) );

			foreach( $report->get_countries() as $country ) {
				foreach( $report->get_tax_rates_by_country( $country ) as $tax_rate ) {
					$observer_report->set_country_tax_total( $country, $tax_rate, ( $observer_report->get_country_tax_total( $country, $tax_rate, false ) + $report->get_country_tax_total( $country, $tax_rate, false ) ) );
					$observer_report->set_country_net_total( $country, $tax_rate, ( $observer_report->get_country_net_total( $country, $tax_rate, false ) + $report->get_country_net_total( $country, $tax_rate, false ) ) );
				}
			}

			// Delete the old observer report
			$observer_report->delete();
		}

		// Delete the tmp report
		$report->delete();

		$observer_report->set_date_requested( $report->get_date_requested() );
		// Use the last report date as new end date
		$observer_report->set_date_end( $report->get_date_end() );
		$observer_report->save();

		update_option( 'oss_woocommerce_observer_report_' . $year, $observer_report->get_id() );

		do_action( 'oss_woocommerce_updated_observer', $observer_report );
	}

	/**
	 * @return false|Report
	 */
	public static function get_running_observer() {
		foreach( self::get_reports_running() as $id ) {
			if ( strstr( $id, 'observer_' ) ) {
				return Package::get_report( $id );
			}
		}

		return false;
	}

	public static function get_reports_running() {
		return (array) get_option( 'oss_woocommerce_reports_running', array() );
	}

	public static function get_timeframe( $type, $date = null, $date_end = null ) {
		$date_start      = null;
		$date_end        = is_null( $date_end ) ? null : $date_end;
		$start_indicator = is_null( $date ) ? new \WC_DateTime() : $date;

		if ( ! is_a( $start_indicator, 'WC_DateTime' ) && is_numeric( $start_indicator ) ) {
			$start_indicator = new \WC_DateTime( "@" . $start_indicator );
		}

		if ( ! is_null( $date_end ) && ! is_a( $date_end, 'WC_DateTime' ) && is_numeric( $date_end ) ) {
			$date_end = new \WC_DateTime( "@" . $date_end );
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

			$date_start = new \WC_DateTime( "first day of " . $start_month . " " . $start_indicator->format( 'Y' ) . " midnight" );
			$date_end   = new \WC_DateTime( "last day of " . $end_month . " " . $start_indicator->format( 'Y' ) . " midnight" );
		} elseif ( 'monthly' === $type ) {
			$month = $start_indicator->format( 'M' );

			$date_start = new \WC_DateTime( "first day of " . $month . " " . $start_indicator->format( 'Y' ) . " midnight" );
			$date_end   = new \WC_DateTime( "last day of " . $month . " " . $start_indicator->format( 'Y' ) . " midnight" );
		} elseif ( 'yearly' === $type ) {
			$date_end   = clone $start_indicator;
			$date_start = clone $start_indicator;

			$date_end->modify( "last day of dec " . $start_indicator->format( 'Y' ) . " midnight" );
			$date_start->modify( "first day of jan " . $start_indicator->format( 'Y' ) . " midnight" );
		} elseif ( 'observer' === $type ) {
			$date_start = clone $start_indicator;
			$report     = Package::get_observer_report( $date_start->format( 'Y' ) );

			if ( ! $report ) {
				// Calculate starting with the first day of the current year until yesterday
				$date_end   = clone $date_start;
				$date_start = new \WC_DateTime( "first day of jan " . $start_indicator->format( 'Y' ) . " midnight" );
			} else {
				// In case a report has already been generated lets do only calculate the timeframe between the end of the last report and now
				$date_end   = clone $date_start;
				$date_end->setTime( 0, 0 );

				$date_start = clone $report->get_date_end();
				$date_start->modify( '+1 day' );

				if ( $date_start > $date_end ) {
					$date_start = clone $date_end;
				}
			}
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
			'end'   => $date_end
		);
	}
}
