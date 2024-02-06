<?php

namespace Vendidero\Germanized\Shipments\DataStores;

use Vendidero\Germanized\Shipments\Package;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;
use Exception;
use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Order Data Store: Stored in CPT.
 *
 * @version  3.0.0
 */
class Shipment extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'gzd_shipment';

	protected $must_exist_meta_keys = array();

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_width',
		'_length',
		'_height',
		'_weight',
		'_packaging_weight',
		'_address',
		'_total',
		'_subtotal',
		'_additional_total',
		'_sender_address',
		'_weight_unit',
		'_dimension_unit',
		'_is_customer_requested',
	);

	protected $core_props = array(
		'country',
		'type',
		'parent_id',
		'order_id',
		'tracking_id',
		'date_created',
		'date_created_gmt',
		'date_sent',
		'date_sent_gmt',
		'est_delivery_date',
		'est_delivery_date_gmt',
		'status',
		'shipping_provider',
		'shipping_method',
		'packaging_id',
		'version',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new shipment in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 */
	public function create( &$shipment ) {
		global $wpdb;

		$shipment->set_date_created( time() );
		$shipment->set_weight_unit( get_option( 'woocommerce_weight_unit', 'kg' ) );
		$shipment->set_dimension_unit( get_option( 'woocommerce_dimension_unit', 'cm' ) );

		$data = array(
			'shipment_country'           => $shipment->get_country(),
			'shipment_order_id'          => is_callable( array( $shipment, 'get_order_id' ) ) ? $shipment->get_order_id() : 0,
			'shipment_parent_id'         => is_callable( array( $shipment, 'get_parent_id' ) ) ? $shipment->get_parent_id() : 0,
			'shipment_tracking_id'       => $shipment->get_tracking_id(),
			'shipment_status'            => $this->get_status( $shipment ),
			'shipment_search_index'      => $this->get_search_index( $shipment ),
			'shipment_packaging_id'      => $shipment->get_packaging_id(),
			'shipment_type'              => $shipment->get_type(),
			'shipment_shipping_provider' => $shipment->get_shipping_provider(),
			'shipment_shipping_method'   => $shipment->get_shipping_method(),
			'shipment_date_created'      => gmdate( 'Y-m-d H:i:s', $shipment->get_date_created( 'edit' )->getOffsetTimestamp() ),
			'shipment_date_created_gmt'  => gmdate( 'Y-m-d H:i:s', $shipment->get_date_created( 'edit' )->getTimestamp() ),
			'shipment_version'           => Package::get_version(),
		);

		if ( $shipment->get_date_sent() ) {
			$data['shipment_date_sent']     = gmdate( 'Y-m-d H:i:s', $shipment->get_date_sent( 'edit' )->getOffsetTimestamp() );
			$data['shipment_date_sent_gmt'] = gmdate( 'Y-m-d H:i:s', $shipment->get_date_sent( 'edit' )->getTimestamp() );
		}

		if ( is_callable( array( $shipment, 'get_est_delivery_date' ) ) && $shipment->get_est_delivery_date() ) {
			$data['shipment_est_delivery_date']     = gmdate( 'Y-m-d H:i:s', $shipment->get_est_delivery_date( 'edit' )->getOffsetTimestamp() );
			$data['shipment_est_delivery_date_gmt'] = gmdate( 'Y-m-d H:i:s', $shipment->get_est_delivery_date( 'edit' )->getTimestamp() );
		}

		$wpdb->insert(
			$wpdb->gzd_shipments,
			$data
		);

		$shipment_id = $wpdb->insert_id;

		if ( $shipment_id ) {
			$shipment->set_id( $shipment_id );

			$this->save_shipment_data( $shipment );

			$shipment->save_meta_data();
			$shipment->apply_changes();

			$this->clear_caches( $shipment );

			$hook_postfix = $this->get_hook_postfix( $shipment );

			/**
			 * Action that indicates that a new Shipment has been created in the DB.
			 *
			 * The dynamic portion of this hook, `$hook_postfix` refers to the
			 * shipment type in case it is not a simple shipment.
			 *
			 * @param integer  $shipment_id The shipment id.
			 * @param Shipment $shipment The shipment instance.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "woocommerce_gzd_new_{$hook_postfix}shipment", $shipment_id, $shipment );
		}
	}

	/**
	 * Get the status to save to the object.
	 *
	 * @since 3.6.0
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 * @return string
	 */
	protected function get_status( $shipment ) {
		$shipment_status = $shipment->get_status( 'edit' );

		if ( ! $shipment_status ) {
			/** This filter is documented in src/Shipment.php */
			$shipment_status = apply_filters( 'woocommerce_gzd_get_shipment_default_status', 'gzd-draft' );
		}

		$valid_statuses = array_keys( wc_gzd_get_shipment_statuses() );

		// Add a gzd- prefix to the status.
		if ( in_array( 'gzd-' . $shipment_status, $valid_statuses, true ) ) {
			$shipment_status = 'gzd-' . $shipment_status;
		}

		return $shipment_status;
	}

	/**
	 * Method to update a shipment in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 */
	public function update( &$shipment ) {
		global $wpdb;

		$updated_props = array();
		$core_props    = $this->core_props;
		$changed_props = array_keys( $shipment->get_changes() );
		$shipment_data = array();

		if ( '' === $shipment->get_weight_unit( 'edit' ) ) {
			$shipment->set_weight_unit( get_option( 'woocommerce_weight_unit', 'kg' ) );
		}

		if ( '' === $shipment->get_dimension_unit( 'edit' ) ) {
			$shipment->set_dimension_unit( get_option( 'woocommerce_dimension_unit', 'cm' ) );
		}

		// Make sure country in core props is updated as soon as the address changes
		if ( in_array( 'address', $changed_props, true ) ) {
			$changed_props[] = 'country';

			// Update search index
			$shipment_data['shipment_search_index'] = $this->get_search_index( $shipment );
		}

		// Shipping provider has changed - lets remove existing label
		if ( in_array( 'shipping_provider', $changed_props, true ) ) {

			if ( $shipment->supports_label() && $shipment->has_label() ) {
				$shipment->get_label()->delete();
			}
		}

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'status':
					$shipment_data[ 'shipment_' . $prop ] = $this->get_status( $shipment );
					break;
				case 'date_created':
				case 'date_sent':
				case 'est_delivery_date':
					if ( is_callable( array( $shipment, 'get_' . $prop ) ) ) {
						$shipment_data[ 'shipment_' . $prop ]          = $shipment->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $shipment->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : null;
						$shipment_data[ 'shipment_' . $prop . '_gmt' ] = $shipment->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $shipment->{'get_' . $prop}( 'edit' )->getTimestamp() ) : null;
					}
					break;
				default:
					if ( is_callable( array( $shipment, 'get_' . $prop ) ) ) {
						$shipment_data[ 'shipment_' . $prop ] = $shipment->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $shipment_data ) ) {
			$shipment_data['shipment_search_index'] = $this->get_search_index( $shipment );

			$wpdb->update(
				$wpdb->gzd_shipments,
				$shipment_data,
				array( 'shipment_id' => $shipment->get_id() )
			);
		}

		$this->save_shipment_data( $shipment );

		$shipment->save_meta_data();
		$shipment->apply_changes();

		$this->clear_caches( $shipment );

		$hook_postfix = $this->get_hook_postfix( $shipment );

		/**
		 * Action that indicates that a Shipment has been updated in the DB.
		 *
		 * The dynamic portion of this hook, `$hook_postfix` refers to the
		 * shipment type in case it is not a simple shipment.
		 *
		 * @param integer  $shipment_id The shipment id.
		 * @param Shipment $shipment The shipment instance.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "woocommerce_gzd_{$hook_postfix}shipment_updated", $shipment->get_id(), $shipment );
	}

	/**
	 * Remove a shipment from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$shipment, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->gzd_shipments, array( 'shipment_id' => $shipment->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_shipmentmeta, array( 'gzd_shipment_id' => $shipment->get_id() ), array( '%d' ) );

		$this->delete_items( $shipment );
		$this->clear_caches( $shipment );

		$hook_postfix = $this->get_hook_postfix( $shipment );

		/**
		 * Action that indicates that a Shipment has been deleted from the DB.
		 *
		 * The dynamic portion of this hook, `$hook_postfix` refers to the
		 * shipment type in case it is not a simple shipment.
		 *
		 * @param integer                                  $shipment_id The shipment id.
		 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "woocommerce_gzd_{$hook_postfix}shipment_deleted", $shipment->get_id(), $shipment );
	}

	/**
	 * Read a shipment from the database.
	 *
	 * @since 3.0.0
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 *
	 * @throws Exception Throw exception if invalid shipment.
	 */
	public function read( &$shipment ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->gzd_shipments} WHERE shipment_id = %d LIMIT 1",
				$shipment->get_id()
			)
		);

		if ( $data ) {
			$shipment->set_props(
				array(
					'order_id'          => $data->shipment_order_id,
					'parent_id'         => $data->shipment_parent_id,
					'country'           => $data->shipment_country,
					'tracking_id'       => $data->shipment_tracking_id,
					'shipping_provider' => $data->shipment_shipping_provider,
					'shipping_method'   => $data->shipment_shipping_method,
					'packaging_id'      => $data->shipment_packaging_id,
					'date_created'      => Package::is_valid_mysql_date( $data->shipment_date_created_gmt ) ? wc_string_to_timestamp( $data->shipment_date_created_gmt ) : null,
					'date_sent'         => Package::is_valid_mysql_date( $data->shipment_date_sent_gmt ) ? wc_string_to_timestamp( $data->shipment_date_sent_gmt ) : null,
					'est_delivery_date' => Package::is_valid_mysql_date( $data->shipment_est_delivery_date_gmt ) ? wc_string_to_timestamp( $data->shipment_est_delivery_date_gmt ) : null,
					'status'            => $data->shipment_status,
					'version'           => $data->shipment_version,
				)
			);

			$this->read_shipment_data( $shipment );

			$shipment->read_meta_data();
			$shipment->set_object_read( true );

			$hook_postfix = $this->get_hook_postfix( $shipment );

			/**
			 * Action that indicates that a Shipment has been loaded from DB.
			 *
			 * The dynamic portion of this hook, `$hook_postfix` refers to the
			 * shipment type in case it is not a simple shipment.
			 *
			 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment object.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( "woocommerce_gzd_{$hook_postfix}shipment_loaded", $shipment );
		} else {
			throw new Exception( _x( 'Invalid shipment.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$shipment ) {
		wp_cache_delete( 'shipment-items-' . $shipment->get_id(), 'shipments' );
		wp_cache_delete( $shipment->get_id(), $this->meta_type . '_meta' );
		wp_cache_delete( 'available-packaging-' . $shipment->get_id(), 'shipments' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	protected function get_search_index( $shipment ) {
		$index = array();

		if ( is_a( $shipment, '\Vendidero\Germanized\Shipments\ReturnShipment' ) ) {
			$index = array_merge( $index, $shipment->get_sender_address() );
		} else {
			$index = array_merge( $index, $shipment->get_address() );
		}

		return implode( ' ', $index );
	}

	protected function get_hook_postfix( $shipment ) {
		if ( 'simple' !== $shipment->get_type() ) {
			return $shipment->get_type() . '_';
		}

		return '';
	}

	/**
	 * Get the label type based on label ID.
	 *
	 * @param int $shipment_id Shipment id.
	 * @return string
	 */
	public function get_shipment_type( $shipment_id ) {
		global $wpdb;

		$type = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT shipment_type FROM {$wpdb->gzd_shipments} WHERE shipment_id = %d LIMIT 1",
				$shipment_id
			)
		);

		return ! empty( $type ) ? $type[0] : false;
	}

	/**
	 * Read extra data associated with the shipment.
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 * @since 3.0.0
	 */
	protected function read_shipment_data( &$shipment ) {
		$props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( 'gzd_shipment', $shipment->get_id(), $meta_key, true );
		}

		$shipment->set_props( $props );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	protected function save_shipment_data( &$shipment ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $shipment, $meta_key_to_props, 'gzd_shipment' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			if ( ! is_callable( array( $shipment, "get_$prop" ) ) ) {
				continue;
			}

			$value = $shipment->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			switch ( $prop ) {
				case 'is_customer_requested':
					$value = wc_bool_to_string( $value );
					break;
			}

			// Force updating props that are dependent on inner content data (weight, dimensions)
			if ( in_array( $prop, array( 'weight', 'width', 'length', 'height' ), true ) && ! $shipment->is_editable() ) {
				// Get weight in view context to maybe allow calculating inner content props.
				$value   = $shipment->{"get_$prop"}( 'view' );
				$updated = update_metadata( 'gzd_shipment', $shipment->get_id(), $meta_key, $value );
			} else {
				$updated = $this->update_or_delete_meta( $shipment, $meta_key, $value );
			}

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a Shipment's properties.
		 *
		 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment object.
		 * @param array                                    $changed_props The updated properties.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_shipment_object_updated_props', $shipment, $updated_props );
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param WC_Data $object The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_meta( $object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( 'gzd_shipment', $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( 'gzd_shipment', $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Read items from the database for this shipment.
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 *
	 * @return array
	 */
	public function read_items( $shipment ) {
		global $wpdb;

		// Get from cache if available.
		$items = 0 < $shipment->get_id() ? wp_cache_get( 'shipment-items-' . $shipment->get_id(), 'shipments' ) : false;

		if ( false === $items ) {

			$items = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$wpdb->gzd_shipment_items} WHERE shipment_id = %d ORDER BY shipment_item_id;", $shipment->get_id() )
			);

			foreach ( $items as $item ) {
				wp_cache_set( 'item-' . $item->shipment_item_id, $item, 'shipment-items' );
			}

			if ( 0 < $shipment->get_id() ) {
				wp_cache_set( 'shipment-items-' . $shipment->get_id(), $items, 'shipments' );
			}
		}

		if ( ! empty( $items ) ) {

			$shipment_type = $shipment->get_type();

			$items = array_map(
				function( $item_id ) use ( $shipment_type ) {
					return wc_gzd_get_shipment_item( $item_id, $shipment_type );
				},
				array_combine( wp_list_pluck( $items, 'shipment_item_id' ), $items )
			);
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * Remove all items from the shipment.
	 *
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment Shipment object.
	 */
	public function delete_items( $shipment ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->gzd_shipment_itemmeta} itemmeta INNER JOIN {$wpdb->gzd_shipment_items} items WHERE itemmeta.gzd_shipment_item_id = items.shipment_item_id and items.shipment_id = %d", $shipment->get_id() ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->gzd_shipment_items} WHERE shipment_id = %d", $shipment->get_id() ) );

		$this->clear_caches( $shipment );
	}

	/**
	 * Get valid WP_Query args from a WC_Order_Query's query variables.
	 *
	 * @since 3.0.6
	 * @param array $query_vars query vars from a WC_Order_Query.
	 * @return array
	 */
	protected function get_wp_query_args( $query_vars ) {
		global $wpdb;

		// Add the 'wc-' prefix to status if needed.
		if ( ! empty( $query_vars['status'] ) ) {
			if ( is_array( $query_vars['status'] ) ) {
				foreach ( $query_vars['status'] as &$status ) {
					$status = wc_gzd_is_shipment_status( 'gzd-' . $status ) ? 'gzd-' . $status : $status;
				}
			} else {
				$query_vars['status'] = wc_gzd_is_shipment_status( 'gzd-' . $query_vars['status'] ) ? 'gzd-' . $query_vars['status'] : $query_vars['status'];
			}
		}

		$wp_query_args = parent::get_wp_query_args( $query_vars );

		// Force type to be existent
		if ( isset( $query_vars['type'] ) ) {
			$wp_query_args['type'] = $query_vars['type'];
		}

		if ( ! isset( $wp_query_args['date_query'] ) ) {
			$wp_query_args['date_query'] = array();
		}

		if ( ! isset( $wp_query_args['meta_query'] ) ) {
			$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Allow Woo to treat these props as date query compatible
		$date_queries = array(
			'date_created',
			'date_sent',
			'est_delivery_date',
		);

		foreach ( $date_queries as $db_key ) {
			if ( isset( $query_vars[ $db_key ] ) && '' !== $query_vars[ $db_key ] ) {
				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				$meta_query_index = array_search( $db_key, $existing_queries, true );

				if ( false !== $meta_query_index ) {
					unset( $wp_query_args['meta_query'][ $meta_query_index ] );
				}

				$date_query_args = $this->parse_date_for_wp_query( $query_vars[ $db_key ], 'post_date', array() );

				/**
				 * Replace date query columns after Woo parsed dates.
				 * Include table name because otherwise WP_Date_Query won't accept our custom column.
				 */
				if ( isset( $date_query_args['date_query'] ) && ! empty( $date_query_args['date_query'] ) ) {
					$date_query = $date_query_args['date_query'][0];

					if ( 'post_date' === $date_query['column'] ) {
						$date_query['column'] = $wpdb->gzd_shipments . '.shipment_' . $db_key;
					}

					$wp_query_args['date_query'][] = $date_query;
				}
			}
		}

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		/**
		 * Filter to adjust Shipments query arguments after parsing.
		 *
		 * @param array                                               $wp_query_args Array containing parsed query arguments.
		 * @param array                                               $query_vars The original query arguments.
		 * @param Shipment $data_store The shipment data store object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipping_data_store_get_shipments_query', $wp_query_args, $query_vars, $this );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  3.0.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->gzd_shipmentmeta;
		$object_id_field = $this->meta_type . '_id';

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

	public function get_query_args( $query_vars ) {
		return $this->get_wp_query_args( $query_vars );
	}

	public function get_shipment_count( $status, $type = '' ) {
		global $wpdb;

		if ( empty( $type ) ) {
			$query = $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->gzd_shipments} WHERE shipment_status = %s", $status );
		} else {
			$query = $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->gzd_shipments} WHERE shipment_status = %s and shipment_type = %s", $status, $type );
		}

		return absint( $wpdb->get_var( $query ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
