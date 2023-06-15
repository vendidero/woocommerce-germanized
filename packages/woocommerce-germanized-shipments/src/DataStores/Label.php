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
class Label extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'gzd_shipment_label';

	protected $core_props = array(
		'shipment_id',
		'parent_id',
		'product_id',
		'number',
		'path',
		'type',
		'shipping_provider',
		'date_created',
		'date_created_gmt',
	);

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_services',
		'_weight',
		'_net_weight',
		'_width',
		'_length',
		'_height',
		'_created_via',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new shipment in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label Label object.
	 */
	public function create( &$label ) {
		global $wpdb;

		$label->set_date_created( time() );

		$data = array(
			'label_number'            => $label->get_number(),
			'label_shipment_id'       => $label->get_shipment_id(),
			'label_path'              => $label->get_path(),
			'label_product_id'        => $label->get_product_id(),
			'label_type'              => $label->get_type(),
			'label_shipping_provider' => $label->get_shipping_provider(),
			'label_parent_id'         => $label->get_parent_id(),
			'label_date_created'      => gmdate( 'Y-m-d H:i:s', $label->get_date_created( 'edit' )->getOffsetTimestamp() ),
			'label_date_created_gmt'  => gmdate( 'Y-m-d H:i:s', $label->get_date_created( 'edit' )->getTimestamp() ),
		);

		$wpdb->insert(
			$wpdb->gzd_shipment_labels,
			$data
		);

		$label_id = $wpdb->insert_id;

		if ( $label_id ) {
			$label->set_id( $label_id );

			$this->save_label_data( $label );

			$label->save_meta_data();
			$label->apply_changes();

			$this->clear_caches( $label );

			$hook_postfix = $this->get_hook_postfix( $label );

			/**
			 * Action fires when a new DHL label has been created.
			 *
			 * The dynamic portion of this hook, `$hook_postfix` refers to the
			 * label type e.g. return in case it is not a simple label.
			 *
			 * @param integer $label_id The label id.
			 * @param Label   $label The label instance.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/DHL
			 */
			do_action( "woocommerce_gzd_shipment_{$hook_postfix}label_created", $label_id, $label );
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label
	 *
	 * @return string
	 */
	protected function get_hook_postfix( $label ) {
		$prefix = $label->get_shipping_provider() . '_';

		if ( 'simple' !== $label->get_type() ) {
			$prefix = $prefix . $label->get_type() . '_';
		}

		return $prefix;
	}

	/**
	 * Method to update a label in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label Label object.
	 */
	public function update( &$label ) {
		global $wpdb;

		$updated_props = array();
		$core_props    = $this->core_props;
		$changed_props = array_keys( $label->get_changes() );
		$label_data    = array();

		foreach ( $changed_props as $prop ) {

			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'date_created':
					$label_data[ 'label' . $prop ]           = $label->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $label->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : null;
					$label_data[ 'label_' . $prop . '_gmt' ] = $label->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $label->{'get_' . $prop}( 'edit' )->getTimestamp() ) : null;
					break;
				default:
					if ( is_callable( array( $label, 'get_' . $prop ) ) ) {
						$label_data[ 'label_' . $prop ] = $label->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $label_data ) ) {
			$wpdb->update(
				$wpdb->gzd_shipment_labels,
				$label_data,
				array( 'label_id' => $label->get_id() )
			);
		}

		$this->save_label_data( $label );
		$label->save_meta_data();

		$label->apply_changes();
		$this->clear_caches( $label );

		$hook_postfix = $this->get_hook_postfix( $label );

		/**
		 * Action fires after a DHL label has been updated in the DB.
		 *
		 * The dynamic portion of this hook, `$hook_postfix` refers to the
		 * label type e.g. return in case it is not a simple label.
		 *
		 * @param integer $label_id The label id.
		 * @param Label   $label The label instance.
		 * @param array   $changed_props Properties that have been changed.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( "woocommerce_gzd_shipment_{$hook_postfix}label_updated", $label->get_id(), $label, $changed_props );
	}

	/**
	 * Remove a shipment from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label Label object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$label, $force_delete = false ) {
		global $wpdb;

		if ( $file = $label->get_file() ) {
			wp_delete_file( $file );
		}

		/*
		 * Delete additional files e.g. export documents
		 */
		foreach ( $label->get_additional_file_types() as $file_type ) {
			if ( $file = $label->get_file( $file_type ) ) {
				wp_delete_file( $file );
			}
		}

		$wpdb->delete( $wpdb->gzd_shipment_labels, array( 'label_id' => $label->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_shipment_labelmeta, array( 'gzd_shipment_label_id' => $label->get_id() ), array( '%d' ) );

		$this->clear_caches( $label );

		$children = $label->get_children();

		foreach ( $children as $child ) {
			$child->delete( $force_delete );
		}

		$hook_postfix = $this->get_hook_postfix( $label );

		/**
		 * Action fires after a DHL label has been deleted from DB.
		 *
		 * The dynamic portion of this hook, `$hook_postfix` refers to the
		 * label type e.g. return in case it is not a simple label.
		 *
		 * @param integer                         $label_id The label id.
		 * @param \Vendidero\Germanized\DHL\Label $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( "woocommerce_gzd_shipment_{$hook_postfix}label_deleted", $label->get_id(), $label );
	}

	/**
	 * Read a shipment from the database.
	 *
	 * @since 3.0.0
	 *
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label Label object.
	 *
	 * @throws Exception Throw exception if invalid shipment.
	 */
	public function read( &$label ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->gzd_shipment_labels} WHERE label_id = %d LIMIT 1",
				$label->get_id()
			)
		);

		if ( $data ) {
			$label->set_props(
				array(
					'shipment_id'       => $data->label_shipment_id,
					'number'            => $data->label_number,
					'path'              => $data->label_path,
					'parent_id'         => $data->label_parent_id,
					'shipping_provider' => $data->label_shipping_provider,
					'product_id'        => $data->label_product_id,
					'date_created'      => Package::is_valid_mysql_date( $data->label_date_created_gmt ) ? wc_string_to_timestamp( $data->label_date_created_gmt ) : null,
				)
			);

			$this->read_label_data( $label );
			$label->read_meta_data();
			$label->set_object_read( true );

			$hook_postfix = $this->get_hook_postfix( $label );

			/**
			 * Action fires after reading a DHL label from DB.
			 *
			 * The dynamic portion of this hook, `$hook_postfix` refers to the
			 * label type e.g. return in case it is not a simple label.
			 *
			 * @param \Vendidero\Germanized\Shipments\Labels\Label $label The label object.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/DHL
			 */
			do_action( "woocommerce_gzd_shipment_{$hook_postfix}label_loaded", $label );
		} else {
			throw new Exception( _x( 'Invalid label.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label Label object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$label ) {
		wp_cache_delete( $label->get_id(), $this->meta_type . '_meta' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the label type based on label ID.
	 *
	 * @param int $label_id Label id.
	 * @return bool|mixed
	 */
	public function get_label_data( $label_id ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT label_type, label_shipping_provider FROM {$wpdb->gzd_shipment_labels} WHERE label_id = %d LIMIT 1",
				$label_id
			)
		);

		if ( ! empty( $data ) ) {
			return (object) array(
				'shipping_provider' => $data->label_shipping_provider,
				'type'              => $data->label_type,
			);
		}

		return false;
	}

	/**
	 * Read extra data associated with the shipment.
	 *
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label Label object.
	 * @since 3.0.0
	 */
	protected function read_label_data( &$label ) {
		$props     = array();
		$meta_keys = $this->internal_meta_keys;

		foreach ( $label->get_extra_data_keys() as $key ) {
			$meta_keys[] = '_' . $key;
		}

		foreach ( $meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( $this->meta_type, $label->get_id(), $meta_key, true );
		}

		$label->set_props( $props );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Labels\Label $label
	 */
	protected function save_label_data( &$label ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		// Make sure to take extra data (like product url or text for external products) into account.
		$extra_data_keys = $label->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $this->get_props_to_update( $label, $meta_key_to_props, $this->meta_type );

		foreach ( $props_to_update as $meta_key => $prop ) {

			if ( ! is_callable( array( $label, "get_$prop" ) ) ) {
				continue;
			}

			$value = $label->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			if ( is_bool( $value ) ) {
				$value = wc_bool_to_string( $value );
			}

			$updated = $this->update_or_delete_meta( $label, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action fires after DHL label meta properties have been updated.
		 *
		 * @param \Vendidero\Germanized\Shipments\Labels\Label $label The label object.
		 * @param array                           $updated_props The updated properties.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( 'woocommerce_gzd_shipment_label_object_updated_props', $label, $updated_props );
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
			$updated = delete_metadata( $this->meta_type, $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( $this->meta_type, $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
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
			'date_created' => 'post_date',
		);

		foreach ( $date_queries as $query_var_key => $db_key ) {
			if ( isset( $query_vars[ $query_var_key ] ) && '' !== $query_vars[ $query_var_key ] ) {

				// Remove any existing meta queries for the same keys to prevent conflicts.
				$existing_queries = wp_list_pluck( $wp_query_args['meta_query'], 'key', true );
				$meta_query_index = array_search( $db_key, $existing_queries, true );

				if ( false !== $meta_query_index ) {
					unset( $wp_query_args['meta_query'][ $meta_query_index ] );
				}

				$wp_query_args = $this->parse_date_for_wp_query( $query_vars[ $query_var_key ], $db_key, $wp_query_args );
			}
		}

		/**
		 * Replace date query columns after Woo parsed dates.
		 * Include table name because otherwise WP_Date_Query won't accept our custom column.
		 */
		if ( isset( $wp_query_args['date_query'] ) ) {
			foreach ( $wp_query_args['date_query'] as $key => $date_query ) {
				if ( isset( $date_query['column'] ) && in_array( $date_query['column'], $date_queries, true ) ) {
					$wp_query_args['date_query'][ $key ]['column'] = $wpdb->gzd_shipment_labels . '.label_' . array_search( $date_query['column'], $date_queries, true );
				}
			}
		}

		if ( ! isset( $query_vars['paginate'] ) || ! $query_vars['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		/**
		 * Filter to adjust the DHL label query args after parsing them.
		 *
		 * @param array                                      $wp_query_args Parsed query arguments.
		 * @param array                                      $query_vars    Original query arguments.
		 * @param Label $data_store The label data store.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_shipment_label_data_store_get_labels_query', $wp_query_args, $query_vars, $this );
	}

	public function get_query_args( $query_vars ) {
		return $this->get_wp_query_args( $query_vars );
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
		$table           = $wpdb->gzd_shipment_labelmeta;
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

	public function get_label_count() {
		global $wpdb;

		return absint( $wpdb->get_var( "SELECT COUNT( * ) FROM {$wpdb->gzd_shipment_labels}" ) );
	}
}
