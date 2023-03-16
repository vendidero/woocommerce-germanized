<?php

namespace Vendidero\Germanized\Shipments\Packaging;

defined( 'ABSPATH' ) || exit;

class ReportHelper {

	public static function init() {
		/**
		 * Listen to action scheduler hooks for report generation
		 */
		foreach ( ReportQueue::get_reports_running() as $id ) {
			$data = self::get_report_data( $id );
			$type = $data['type'];

			add_action(
				ReportQueue::get_hook_name( $id ),
				function( $args ) use ( $type ) {
					ReportQueue::next( $type, $args );
				},
				10,
				1
			);
		}

		// Setup or cancel recurring tasks
		add_action( 'init', array( __CLASS__, 'setup_recurring_actions' ), 10 );
		add_action( 'woocommerce_gzd_shipments_daily_cleanup', array( __CLASS__, 'cleanup' ), 10 );

		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 25 );
		add_action( 'admin_head', array( __CLASS__, 'hide_page_from_menu' ) );

		foreach ( array( 'delete', 'refresh', 'cancel' ) as $action ) {
			add_action( 'admin_post_wc_gzd_shipments_packaging_' . $action . '_report', array( __CLASS__, $action . '_report' ) );
		}
	}

	public static function add_page() {
		add_submenu_page( 'woocommerce', _x( 'Packaging Report', 'shipments', 'woocommerce-germanized' ), _x( 'Packaging Report', 'shipments', 'woocommerce-germanized' ), 'manage_woocommerce', 'shipment-packaging-report', array( __CLASS__, 'render_report' ) );
	}

	public static function hide_page_from_menu() {
		remove_submenu_page( 'woocommerce', 'shipment-packaging-report' );
	}

	/**
	 * @param Report $report
	 *
	 * @return array[]
	 */
	public static function get_report_actions( $report ) {
		$actions = array(
			'view'    => array(
				'url'   => $report->get_url(),
				'title' => _x( 'View', 'shipments-packaging-report', 'woocommerce-germanized' ),
			),
			'refresh' => array(
				'url'   => $report->get_refresh_link(),
				'title' => _x( 'Refresh', 'shipments-packaging-report', 'woocommerce-germanized' ),
			),
			'delete'  => array(
				'url'   => $report->get_delete_link(),
				'title' => _x( 'Delete', 'shipments-packaging-report', 'woocommerce-germanized' ),
			),
		);

		if ( 'completed' !== $report->get_status() ) {
			$actions['cancel']          = $actions['delete'];
			$actions['cancel']['title'] = _x( 'Cancel', 'shipments-packaging-report', 'woocommerce-germanized' );

			unset( $actions['view'] );
			unset( $actions['refresh'] );
			unset( $actions['delete'] );
		}

		return $actions;
	}

	public static function render_report() {
		if ( isset( $_GET['report'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$report_id = isset( $_GET['report'] ) ? wc_clean( wp_unslash( $_GET['report'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $report_id ) {
				return;
			}

			if ( ! $report = self::get_report( $report_id ) ) {
				return;
			}

			$columns = array(
				'packaging' => _x( 'Packaging', 'shipments', 'woocommerce-germanized' ),
				'weight'    => _x( 'Weight', 'shipments', 'woocommerce-germanized' ),
				'count'     => _x( 'Count', 'shipments', 'woocommerce-germanized' ),
			);

			$actions       = self::get_report_actions( $report );
			$countries     = WC()->countries->get_countries();
			$packaging_ids = $report->get_packaging_ids();
			?>
			<div class="wrap wc-gzd-shipments-packaging-report packaging-report-<?php echo esc_attr( $report->get_id() ); ?>">
				<h1 class="wp-heading-inline"><?php echo esc_html( $report->get_title() ); ?></h1>
				<?php
				foreach ( $actions as $action_type => $action ) :
					if ( 'view' === $action_type ) {
						continue;
					}
					?>
					<a class="page-title-action button-<?php echo esc_attr( $action_type ); ?>" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['title'] ); ?></a>
				<?php endforeach; ?>

				<?php if ( 'completed' === $report->get_status() ) : ?>
					<p class="summary"><?php echo esc_html( $report->get_date_start()->date_i18n( wc_date_format() ) ); ?> &ndash; <?php echo esc_html( $report->get_date_end()->date_i18n( wc_date_format() ) ); ?>: <?php echo esc_html( wc_gzd_format_shipment_weight( $report->get_total_weight(), wc_gzd_get_packaging_weight_unit() ) ); ?> (<?php echo esc_html( sprintf( _x( '%d units', 'shipments-packaging-report', 'woocommerce-germanized' ), $report->get_total_count() ) ); ?>)</p>
					<hr class="wp-header-end" />

					<?php if ( ! empty( $packaging_ids ) ) : ?>
						<table class="wp-list-table widefat fixed striped posts wc-gzd-shipments-packaging-report-details" cellspacing="0">
							<thead>
							<tr>
								<?php foreach ( $columns as $key => $column ) : ?>
									<th class="wc-gzd-shipments-packaging-report-table-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
								<?php endforeach; ?>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ( $packaging_ids as $packaging_id ) :
								$packaging = is_numeric( $packaging_id ) ? wc_gzd_get_packaging( $packaging_id ) : false;
								?>
								<tr>
									<td class="wc-gzd-shipments-packaging-report-table-packaging"><?php echo esc_html( ( $packaging && $packaging->get_id() > 0 ? $packaging->get_description() : _x( 'Unknown', 'shipments-packaging-title', 'woocommerce-germanized' ) ) ); ?></td>
									<td class="wc-gzd-shipments-packaging-report-table-weight"><?php echo esc_html( wc_gzd_format_shipment_weight( $report->get_packaging_weight( $packaging_id ), wc_gzd_get_packaging_weight_unit() ) ); ?></td>
									<td class="wc-gzd-shipments-packaging-report-table-count"><?php echo esc_html( $report->get_packaging_count( $packaging_id ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php
					foreach ( $report->get_countries() as $country ) :
						?>
						<h4><?php echo esc_html( isset( $countries[ $country ] ) ? $countries[ $country ] : $country ); ?></h4>
						<p class="summary"><?php echo esc_html( wc_gzd_format_shipment_weight( $report->get_total_packaging_weight_by_country( $country ), wc_gzd_get_packaging_weight_unit() ) ); ?> (<?php echo esc_html( sprintf( _x( '%d units', 'shipments-packaging-report', 'woocommerce-germanized' ), $report->get_total_packaging_count_by_country( $country ) ) ); ?>)</p>
						<table class="wp-list-table widefat fixed striped posts wc-gzd-shipments-packaging-report-details" cellspacing="0">
							<thead>
							<tr>
								<?php foreach ( $columns as $key => $column ) : ?>
									<th class="wc-gzd-shipments-packaging-report-table-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
								<?php endforeach; ?>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ( $report->get_packaging_ids_by_country( $country ) as $packaging_id ) :
								$packaging = is_numeric( $packaging_id ) ? wc_gzd_get_packaging( $packaging_id ) : false;
								?>
								<tr>
									<td class="wc-gzd-shipments-packaging-report-table-packaging"><?php echo esc_html( ( $packaging && $packaging->get_id() > 0 ? $packaging->get_description() : _x( 'Unknown', 'shipments-packaging-title', 'woocommerce-germanized' ) ) ); ?></td>
									<td class="wc-gzd-shipments-packaging-report-table-weight"><?php echo esc_html( wc_gzd_format_shipment_weight( $report->get_packaging_weight( $packaging_id, $country ), wc_gzd_get_packaging_weight_unit() ) ); ?></td>
									<td class="wc-gzd-shipments-packaging-report-table-count"><?php echo esc_html( $report->get_packaging_count( $packaging_id, $country ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endforeach; ?>
					<?php
				else :
					$details = ReportQueue::get_queue_details( $report_id );
					?>
					<p class="summary"><?php printf( _x( 'Currently processed %1$s shipments. Next iteration is scheduled for %2$s. <a href="%3$s">Find pending actions</a>', 'shipments', 'woocommerce-germanized' ), esc_html( $details['shipment_count'] ), ( $details['next_date'] ? esc_html( $details['next_date']->date_i18n( wc_date_format() . ' @ ' . wc_time_format() ) ) : esc_html_x( 'Not yet known', 'shipments', 'woocommerce-germanized' ) ), esc_url( $details['link'] ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	public static function cleanup() {
		$running = array();

		/**
		 * Remove reports from running Queue in case they are not queued any longer.
		 */
		foreach ( ReportQueue::get_reports_running() as $report_id ) {
			$details = ReportQueue::get_queue_details( $report_id );

			if ( $details['has_action'] && ! $details['is_finished'] ) {
				$running[] = $report_id;
			} else {
				if ( $report = self::get_report( $report_id ) ) {
					if ( 'completed' !== $report->get_status() ) {
						$report->delete();
					}
				}
			}
		}

		$running = array_values( $running );

		update_option( 'woocommerce_gzd_shipments_packaging_reports_running', $running, false );
		ReportQueue::clear_cache();
	}

	public static function setup_recurring_actions() {
		if ( $queue = ReportQueue::get_queue() ) {
			// Schedule once per day at 2:00
			if ( null === $queue->get_next( 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' ) ) {
				$timestamp = strtotime( 'tomorrow midnight' );
				$date      = new \WC_DateTime();

				$date->setTimestamp( $timestamp );
				$date->modify( '+2 hours' );

				$queue->cancel_all( 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' );
				$queue->schedule_recurring( $date->getTimestamp(), DAY_IN_SECONDS, 'woocommerce_gzd_shipments_daily_cleanup', array(), 'woocommerce_gzd_shipments' );
			}
		}
	}

	public static function get_report_title( $id ) {
		$args  = self::get_report_data( $id );
		$title = _x( 'Report', 'shipments', 'woocommerce-germanized' );

		if ( 'quarterly' === $args['type'] ) {
			$date_start = $args['date_start'];
			$quarter    = 1;
			$month_num  = (int) $date_start->date_i18n( 'n' );

			if ( 4 === $month_num ) {
				$quarter = 2;
			} elseif ( 7 === $month_num ) {
				$quarter = 3;
			} elseif ( 10 === $month_num ) {
				$quarter = 4;
			}

			$title = sprintf( _x( 'Q%1$s/%2$s', 'shipments', 'woocommerce-germanized' ), $quarter, $date_start->date_i18n( 'Y' ) );
		} elseif ( 'monthly' === $args['type'] ) {
			$date_start = $args['date_start'];
			$month_num  = $date_start->date_i18n( 'm' );

			$title = sprintf( _x( '%1$s/%2$s', 'shipments', 'woocommerce-germanized' ), $month_num, $date_start->date_i18n( 'Y' ) );
		} elseif ( 'yearly' === $args['type'] ) {
			$date_start = $args['date_start'];

			$title = sprintf( _x( '%1$s', 'shipments', 'woocommerce-germanized' ), $date_start->date_i18n( 'Y' ) ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
		} elseif ( 'custom' === $args['type'] ) {
			$date_start = $args['date_start'];
			$date_end   = $args['date_end'];

			$title = sprintf( _x( '%1$s - %2$s', 'shipments', 'woocommerce-germanized' ), $date_start->date_i18n( 'Y-m-d' ), $date_end->date_i18n( 'Y-m-d' ) );
		}

		return $title;
	}

	public static function get_report_id( $parts ) {
		$parts = wp_parse_args(
			$parts,
			array(
				'type'       => 'daily',
				'date_start' => date( 'Y-m-d' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'date_end'   => date( 'Y-m-d' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			)
		);

		if ( is_a( $parts['date_start'], 'WC_DateTime' ) ) {
			$parts['date_start'] = $parts['date_start']->format( 'Y-m-d' );
		}

		if ( is_a( $parts['date_end'], 'WC_DateTime' ) ) {
			$parts['date_end'] = $parts['date_end']->format( 'Y-m-d' );
		}

		return sanitize_key( 'woocommerce_gzd_shipments_packaging_' . $parts['type'] . '_report_' . $parts['date_start'] . '_' . $parts['date_end'] );
	}

	public static function get_available_report_types() {
		$types = array(
			'quarterly' => _x( 'Quarterly', 'shipments', 'woocommerce-germanized' ),
			'yearly'    => _x( 'Yearly', 'shipments', 'woocommerce-germanized' ),
			'monthly'   => _x( 'Monthly', 'shipments', 'woocommerce-germanized' ),
			'custom'    => _x( 'Custom', 'shipments', 'woocommerce-germanized' ),
		);

		return $types;
	}

	public static function get_report_data( $id ) {
		$clean_id = str_replace( 'packaging_', '', $id );
		$clean_id = str_replace( 'woocommerce_gzd_shipments_', '', $clean_id );
		$id_parts = explode( '_', $clean_id );

		$data = array(
			'id'         => $id,
			'type'       => $id_parts[0],
			'date_start' => self::string_to_datetime( $id_parts[2] ),
			'date_end'   => self::string_to_datetime( $id_parts[3] ),
		);

		return $data;
	}

	public static function string_to_datetime( $time_string ) {
		if ( is_string( $time_string ) && ! is_numeric( $time_string ) ) {
			$time_string = strtotime( $time_string );
		}

		$date_time = $time_string;

		if ( is_numeric( $date_time ) ) {
			$date_time = new \WC_DateTime( "@{$date_time}", new \DateTimeZone( 'UTC' ) );
		}

		if ( ! is_a( $date_time, 'WC_DateTime' ) ) {
			return null;
		}

		return $date_time;
	}

	public static function clear_caches() {
		delete_transient( 'woocommerce_gzd_shipments_packaging_report_counts' );
		wp_cache_delete( 'woocommerce_gzd_shipments_packaging_reports', 'options' );
	}

	public static function get_report_ids() {
		$reports = (array) get_option( 'woocommerce_gzd_shipments_packaging_reports', array() );

		foreach ( array_keys( self::get_available_report_types() ) as $type ) {
			if ( ! array_key_exists( $type, $reports ) ) {
				$reports[ $type ] = array();
			}
		}

		return $reports;
	}

	public static function get_report_status_title( $status ) {
		if ( 'completed' === $status ) {
			return _x( 'Completed', 'shipments-report-status', 'woocommerce-germanized' );
		} else {
			return _x( 'Pending', 'shipments-report-status', 'woocommerce-germanized' );
		}
	}

	/**
	 * @param array $args
	 *
	 * @return Report[]
	 */
	public static function get_reports( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'    => '',
				'limit'   => -1,
				'offset'  => 0,
				'orderby' => 'date_start',
			)
		);

		$ids = self::get_report_ids();

		if ( ! empty( $args['type'] ) ) {
			$report_ids = array_key_exists( $args['type'], $ids ) ? $ids[ $args['type'] ] : array();
		} else {
			$report_ids = array_merge( ...array_values( $ids ) );
		}

		$reports_sorted = array();

		foreach ( $report_ids as $id ) {
			$reports_sorted[] = self::get_report_data( $id );
		}

		if ( array_key_exists( $args['orderby'], array( 'date_start', 'date_end' ) ) ) {
			usort(
				$reports_sorted,
				function( $a, $b ) use ( $args ) {
					if ( $a[ $args['orderby'] ] === $b[ $args['orderby'] ] ) {
						return 0;
					}

					return $a[ $args['orderby'] ] < $b[ $args['orderby'] ] ? -1 : 1;
				}
			);
		}

		if ( -1 !== $args['limit'] ) {
			$reports_sorted = array_slice( $reports_sorted, $args['offset'], $args['limit'] );
		}

		$reports = array();

		foreach ( $reports_sorted as $data ) {
			if ( $report = self::get_report( $data['id'] ) ) {
				$reports[] = $report;
			}
		}

		return $reports;
	}

	/**
	 * @param Report $report
	 */
	public static function remove_report( $report ) {
		$reports_available = self::get_report_ids();

		if ( in_array( $report->get_id(), $reports_available[ $report->get_type() ], true ) ) {
			$reports_available[ $report->get_type() ] = array_diff( $reports_available[ $report->get_type() ], array( $report->get_id() ) );

			update_option( 'woocommerce_gzd_shipments_packaging_reports', $reports_available, false );

			/**
			 * Force non-cached option
			 */
			wp_cache_delete( 'woocommerce_gzd_shipments_packaging_reports', 'options' );
		}
	}

	/**
	 * @param $id
	 *
	 * @return false|Report
	 */
	public static function get_report( $id ) {
		$report = new Report( $id );

		if ( $report->exists() ) {
			return $report;
		}

		return false;
	}

	public static function delete_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '', 'wc_gzd_shipments_packaging_delete_report' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( wp_unslash( $_GET['report_id'] ) ) : '';

		if ( ! empty( $report_id ) && ( $report = self::get_report( $report_id ) ) ) {
			$report->delete();

			$referer = self::get_clean_referer();

			/**
			 * Do not redirect deleted, refreshed reports back to report details page
			 */
			if ( strstr( $referer, '&report=' ) ) {
				$referer = admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=packaging' );
			}

			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'report_deleted' => $report_id ), $referer ) ) );
			exit();
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ) );
		exit();
	}

	protected static function get_clean_referer() {
		$referer = wp_get_referer();

		return remove_query_arg( array( 'report_created', 'report_deleted', 'report_restarted', 'report_cancelled' ), $referer );
	}

	public static function refresh_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '', 'wc_gzd_shipments_packaging_refresh_report' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( wp_unslash( $_GET['report_id'] ) ) : '';

		if ( ! empty( $report_id ) && ( $report = self::get_report( $report_id ) ) ) {
			ReportQueue::start( $report->get_type(), $report->get_date_start(), $report->get_date_end() );

			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'report_restarted' => $report_id ), self::get_clean_referer() ) ) );
			exit();
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ) );
		exit();
	}

	public static function cancel_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '', 'wc_gzd_shipments_packaging_cancel_report' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die();
		}

		$report_id = isset( $_GET['report_id'] ) ? wc_clean( wp_unslash( $_GET['report_id'] ) ) : '';

		if ( ! empty( $report_id ) && ReportQueue::is_running( $report_id ) ) {
			ReportQueue::cancel( $report_id );

			$referer = self::get_clean_referer();

			/**
			 * Do not redirect deleted, refreshed reports back to report details page
			 */
			if ( strstr( $referer, '&report=' ) ) {
				$referer = admin_url( 'admin.php?page=wc-settings&tab=germanized-shipments&section=packaging' );
			}

			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'report_cancelled' => $report_id ), $referer ) ) );
			exit();
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ) );
		exit();
	}
}
