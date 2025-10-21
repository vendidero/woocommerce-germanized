<?php

namespace Vendidero\Shiptastic\Tracking;

use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentQuery;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup_recurring_actions' ), 10 );
		add_action( 'woocommerce_shiptastic_shipments_tracking', array( __CLASS__, 'init_batch_tracking' ) );
		add_action( 'woocommerce_shiptastic_shipments_tracking_single_run', array( __CLASS__, 'init_single_run' ) );
		add_action( 'woocommerce_shiptastic_shipments_tracking_track', array( __CLASS__, 'track' ) );

		add_action( 'rest_api_init', array( __CLASS__, 'register_tracking_event_endpoints' ) );
		add_action( 'woocommerce_shiptastic_shipment_created_label', array( __CLASS__, 'subscribe_to_remote_events' ), 20, 2 );
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return void
	 */
	public static function subscribe_to_remote_events( $shipment ) {
		if ( $provider = $shipment->get_shipping_provider_instance() ) {
			if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
				if ( $provider->supports_remote_shipment_status( 'push' ) && $provider->enable_remote_shipment_status_update( 'push' ) ) {
					$provider->subscribe_to_shipment_status_events( array( $shipment ) );
				}
			}
		}
	}

	public static function get_tracking_callback_url( $provider_name ) {
		return get_rest_url( null, "shiptastic/v1/{$provider_name}/track" );
	}

	public static function register_tracking_event_endpoints() {
		foreach ( \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_available_shipping_providers() as $provider ) {
			if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
				if ( $provider->supports_remote_shipment_status( 'push' ) && $provider->enable_remote_shipment_status_update( 'push' ) ) {
					register_rest_route(
						'shiptastic/v1',
						"{$provider->get_name()}/track",
						array(
							'methods'             => \WP_REST_Server::EDITABLE,
							'callback'            => function ( $request ) use ( $provider ) {
								$result = $provider->handle_remote_shipment_status_update( $request );

								if ( is_a( $result, 'Vendidero\Shiptastic\Tracking\ShipmentStatus' ) ) {
									if ( $shipment = $result->get_shipment() ) {
										$shipment->update_remote_status( $result );
									}

									$result = new \WP_REST_Response( array( 'success' => true ) );
									$result->set_status( 200 );
								}

								return rest_ensure_response( $result );
							},
							'permission_callback' => '__return_true',
						)
					);
				}
			}
		}
	}

	public static function get_status_update_types() {
		return array(
			'pull' => _x( 'Pull', 'shipments', 'woocommerce-germanized' ),
			'push' => _x( 'Push', 'shipments', 'woocommerce-germanized' ),
		);
	}

	public static function init_single_run( $shipments_query ) {
		$shipments_query = wp_parse_args(
			$shipments_query,
			array(
				'shipping_provider' => '',
				'time_offset'       => time(),
				'offset'            => 0,
				'limit'             => 20,
			)
		);

		$time_offset     = absint( $shipments_query['time_offset'] );
		$shipments_query = array_diff_key(
			$shipments_query,
			array(
				'time_offset' => 0,
			)
		);

		$query     = new ShipmentQuery( $shipments_query );
		$shipments = $query->get_shipments();
		$provider  = wc_stc_get_shipping_provider( $shipments_query['shipping_provider'] );

		if ( ! empty( $shipments ) && $provider && is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
			$single_query = array_diff_key(
				$shipments_query,
				array(
					'has_tracking' => true,
					'offset'       => 0,
					'return'       => 'ids',
				)
			);

			$number_of_shipments = $provider->get_number_of_shipments_per_status_check( 'pull' );

			if ( count( $shipments ) > $number_of_shipments ) {
				$chunks = array_chunk( $shipments, $number_of_shipments, true );
			} else {
				$chunks = array( $shipments );
			}

			foreach ( $chunks as $i => $inner_shipments ) {
				$single_query['include'] = $inner_shipments;

				Package::log( sprintf( 'Queueing %s shipment status refresh for:', $provider->get_title() ), 'info', 'tracking' );
				Package::log( wc_print_r( $inner_shipments, true ), 'info', 'tracking' );

				$cur_page = ceil( $shipments_query['offset'] / $shipments_query['limit'] );

				self::get_queue()->schedule_single(
					$time_offset + 150 + ( $cur_page * 60 ) + ( $i * 5 ),
					'woocommerce_shiptastic_shipments_tracking_track',
					array( 'query' => $single_query ),
					'woocommerce_shiptastic_tracking'
				);
			}
		}
	}

	public static function track( $shipments_query ) {
		/**
		 * If there are woocommerce_shiptastic_shipments_tracking_single_run actions left we did not finish
		 * building the query yet - postpone the event.
		 */
		if ( self::get_queue()->get_next( 'woocommerce_shiptastic_shipments_tracking_single_run', null, 'woocommerce_shiptastic_tracking' ) ) {
			self::get_queue()->schedule_single(
				time() + 150,
				'woocommerce_shiptastic_shipments_tracking_track',
				array( 'query' => $shipments_query ),
				'woocommerce_shiptastic_tracking'
			);

			return;
		}

		$shipments_query = wp_parse_args(
			$shipments_query,
			array(
				'shipping_provider' => '',
			)
		);

		if ( $provider = wc_stc_get_shipping_provider( $shipments_query['shipping_provider'] ) ) {
			if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
				if ( $provider->supports_remote_shipment_status( 'pull' ) && $provider->enable_remote_shipment_status_update( 'pull' ) ) {
					$query     = new ShipmentQuery( $shipments_query );
					$shipments = $query->get_shipments();

					Package::log( sprintf( 'Retrieving %s remote shipment status for %d shipments', $provider->get_title(), count( $shipments ) ), 'info', 'tracking' );

					if ( ! empty( $shipments ) ) {
						$statuses = $provider->get_remote_status_for_shipments( $shipments );

						Package::log( sprintf( 'Retrieved %d %s remote statuses:', count( $statuses ), $provider->get_title() ), 'info', 'tracking' );

						foreach ( $statuses as $status ) {
							if ( $shipment = $status->get_shipment() ) {
								Package::log( wc_print_r( $status->get_data(), true ), 'info', 'tracking' );

								$shipment->update_remote_status( $status );
							}
						}
					}
				}
			}
		}
	}

	public static function init_batch_tracking() {
		foreach ( \Vendidero\Shiptastic\ShippingProvider\Helper::instance()->get_available_shipping_providers() as $provider ) {
			if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
				if ( $provider->supports_remote_shipment_status( 'pull' ) && $provider->enable_remote_shipment_status_update( 'pull' ) ) {
					$supported_providers[ $provider->get_name() ] = $provider;
				}
			}
		}

		if ( ! empty( $supported_providers ) ) {
			foreach ( $supported_providers as $provider ) {
				$cutoff_date = new \WC_DateTime( 'now' );
				$cutoff_date->modify( '-4 weeks' );

				$per_batch_run   = 20;
				$shipments_query = apply_filters(
					'woocommerce_shiptastic_shipment_tracking_query',
					array(
						'shipping_provider' => $provider->get_name(),
						'has_tracking'      => true,
						'type'              => 'simple',
						'limit'             => $per_batch_run,
						'orderby'           => 'date_created',
						'order'             => 'ASC',
						'status'            => array( 'shipped', 'ready-for-shipping' ),
						'date_created'      => '>=' . $cutoff_date->getTimestamp(),
						'paginate'          => true,
						'count_total'       => true,
						'offset'            => 0,
						'return'            => 'ids',
					)
				);

				$query = new ShipmentQuery( $shipments_query );
				$query->get_shipments();

				$total                = $query->get_total();
				$pages                = ceil( $total / $per_batch_run );
				$cur_time             = time();
				$timeout_between_runs = 50;
				$max_exec_time        = $cur_time + ( ( $pages - 1 ) * $timeout_between_runs );

				Package::log( sprintf( 'Refreshing remote status for %d %s shipments', $total, $provider->get_title() ), 'info', 'tracking' );

				/**
				 * Loop all shipment pages and create an action which actually queries
				 * the shipments but does not yet refresh statuses to prevent the pagination
				 * from being disrupted by status updates. Instead, query shipment ids first and
				 * then schedule another action, after the last loop query, to actually refresh the status.
				 *
				 * Need to pass the args as associative array as the action scheduler extract the args.
				 */
				for ( $i = 0; $i < $pages; $i++ ) {
					$single_query                = array_diff_key(
						$shipments_query,
						array(
							'count_total' => false,
							'paginate'    => false,
						)
					);
					$single_query['offset']      = $i * $per_batch_run;
					$single_query['time_offset'] = $max_exec_time;

					self::get_queue()->schedule_single(
						$cur_time + ( $i * $timeout_between_runs ),
						'woocommerce_shiptastic_shipments_tracking_single_run',
						array( 'query' => $single_query ),
						'woocommerce_shiptastic_tracking'
					);
				}
			}
		}
	}

	protected static function get_queue() {
		return function_exists( 'WC' ) ? WC()->queue() : false;
	}

	public static function setup_recurring_actions() {
		if ( $queue = self::get_queue() ) {
			if ( null === $queue->get_next( 'woocommerce_shiptastic_shipments_tracking', array(), 'woocommerce_shiptastic_tracking' ) ) {
				$timestamp = strtotime( 'now' );

				$queue->cancel_all( 'woocommerce_shiptastic_shipments_tracking', array(), 'woocommerce_shiptastic' );

				$tracking_hour   = apply_filters( 'woocommerce_shiptastic_shipments_tracking_cron_hour', '20' );
				$tracking_minute = apply_filters( 'woocommerce_shiptastic_shipments_tracking_cron_minute', '0' );

				$queue->schedule_cron( $timestamp, "{$tracking_minute} {$tracking_hour} * * *", 'woocommerce_shiptastic_shipments_tracking', array(), 'woocommerce_shiptastic_tracking' );
			}
		}
	}
}
