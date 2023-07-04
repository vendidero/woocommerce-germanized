<?php

namespace Vendidero\Germanized\Shipments\DataStores;

use DVDoug\BoxPacker\VolumePacker;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packing\PackagingBox;
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
class Packaging extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'gzd_packaging';

	protected $must_exist_meta_keys = array();

	protected $core_props = array(
		'type',
		'date_created',
		'date_created_gmt',
		'weight',
		'max_content_weight',
		'length',
		'width',
		'height',
		'description',
		'order',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new packaging in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging Packaging object.
	 */
	public function create( &$packaging ) {
		global $wpdb;

		$packaging->set_date_created( time() );

		$data = array(
			'packaging_type'               => $packaging->get_type(),
			'packaging_description'        => $packaging->get_description(),
			'packaging_weight'             => $packaging->get_weight(),
			'packaging_max_content_weight' => $packaging->get_max_content_weight(),
			'packaging_length'             => $packaging->get_length(),
			'packaging_width'              => $packaging->get_width(),
			'packaging_height'             => $packaging->get_height(),
			'packaging_order'              => $packaging->get_order(),
			'packaging_date_created'       => $packaging->get_date_created( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $packaging->get_date_created( 'edit' )->getOffsetTimestamp() ) : null,
			'packaging_date_created_gmt'   => $packaging->get_date_created( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $packaging->get_date_created( 'edit' )->getTimestamp() ) : null,
		);

		$wpdb->insert(
			$wpdb->gzd_packaging,
			$data
		);

		$packaging_id = $wpdb->insert_id;

		if ( $packaging_id ) {
			$packaging->set_id( $packaging_id );

			$this->save_packaging_data( $packaging );

			$packaging->save_meta_data();
			$packaging->apply_changes();

			$this->clear_caches( $packaging );

			/**
			 * Action that indicates that a new Packaging has been created in the DB.
			 *
			 * @param integer  $packaging_id The packaging id.
			 * @param \Vendidero\Germanized\Shipments\Packaging $packaging The packaging instance.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_new_packaging', $packaging_id, $packaging );
		}
	}

	/**
	 * Method to update a packaging in the database.
	 *
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging Packaging object.
	 */
	public function update( &$packaging ) {
		global $wpdb;

		$updated_props  = array();
		$core_props     = $this->core_props;
		$changed_props  = array_keys( $packaging->get_changes() );
		$packaging_data = array();

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'date_created':
					if ( is_callable( array( $packaging, 'get_' . $prop ) ) ) {
						$packaging_data[ 'packaging_' . $prop ]          = $packaging->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $packaging->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : null;
						$packaging_data[ 'packaging_' . $prop . '_gmt' ] = $packaging->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $packaging->{'get_' . $prop}( 'edit' )->getTimestamp() ) : null;
					}
					break;
				default:
					if ( is_callable( array( $packaging, 'get_' . $prop ) ) ) {
						$packaging_data[ 'packaging_' . $prop ] = $packaging->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $packaging_data ) ) {
			$wpdb->update(
				$wpdb->gzd_packaging,
				$packaging_data,
				array( 'packaging_id' => $packaging->get_id() )
			);
		}

		$this->save_packaging_data( $packaging );

		$packaging->save_meta_data();
		$packaging->apply_changes();

		$this->clear_caches( $packaging );

		/**
		 * Action that indicates that a Packaging has been updated in the DB.
		 *
		 * @param integer  $packaging_id The packaging id.
		 * @param \Vendidero\Germanized\Shipments\Packaging $packaging The packaging instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_packaging_updated', $packaging->get_id(), $packaging );
	}

	/**
	 * Remove a Packaging from the database.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging Packaging object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$packaging, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->gzd_packaging, array( 'packaging_id' => $packaging->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->gzd_packagingmeta, array( 'gzd_packaging_id' => $packaging->get_id() ), array( '%d' ) );

		$this->clear_caches( $packaging );

		/**
		 * Action that indicates that a Packaging has been deleted from the DB.
		 *
		 * @param integer  $packaging_id The packaging id.
		 * @param \Vendidero\Germanized\Shipments\Packaging $packaging The packaging instance.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_packaging_deleted', $packaging->get_id(), $packaging );
	}

	/**
	 * Read a Packaging from the database.
	 *
	 * @since 3.3.0
	 *
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging Packaging object.
	 *
	 * @throws Exception Throw exception if invalid packaging.
	 */
	public function read( &$packaging ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->gzd_packaging} WHERE packaging_id = %d LIMIT 1",
				$packaging->get_id()
			)
		);

		if ( $data ) {
			$packaging->set_props(
				array(
					'type'               => $data->packaging_type,
					'description'        => $data->packaging_description,
					'weight'             => $data->packaging_weight,
					'max_content_weight' => $data->packaging_max_content_weight,
					'length'             => $data->packaging_length,
					'width'              => $data->packaging_width,
					'height'             => $data->packaging_height,
					'order'              => $data->packaging_order,
					'date_created'       => Package::is_valid_mysql_date( $data->packaging_date_created_gmt ) ? wc_string_to_timestamp( $data->packaging_date_created_gmt ) : null,
				)
			);

			$this->read_packaging_data( $packaging );

			$packaging->read_meta_data();
			$packaging->set_object_read( true );

			/**
			 * Action that indicates that a Packaging has been loaded from DB.
			 *
			 * @param \Vendidero\Germanized\Shipments\Packaging $packaging The Packaging object.
			 *
			 * @since 3.3.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_packaging_loaded', $packaging );
		} else {
			throw new Exception( _x( 'Invalid packaging.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging Packaging object.
	 * @since 3.0.0
	 */
	protected function clear_caches( &$packaging ) {
		wp_cache_delete( $packaging->get_id(), $this->meta_type . '_meta' );
		wp_cache_delete( 'packaging-list', 'packaging' );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the packaging type based on ID.
	 *
	 * @param int $packaging_id Packaging id.
	 * @return string
	 */
	public function get_packaging_type( $packing_id ) {
		global $wpdb;

		$type = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT packaging_type FROM {$wpdb->gzd_packaging} WHERE packaging_id = %d LIMIT 1",
				$packing_id
			)
		);

		return ! empty( $type ) ? $type[0] : false;
	}

	/**
	 * Read extra data associated with the Packaging.
	 *
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging Packaging object.
	 * @since 3.0.0
	 */
	protected function read_packaging_data( &$packaging ) {
		$props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( 'gzd_packaging', $packaging->get_id(), $meta_key, true );
		}

		$packaging->set_props( $props );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging
	 */
	protected function save_packaging_data( &$packaging ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $packaging, $meta_key_to_props, $this->meta_type );

		foreach ( $props_to_update as $meta_key => $prop ) {
			if ( ! is_callable( array( $packaging, "get_$prop" ) ) ) {
				continue;
			}

			$value = $packaging->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_meta( $packaging, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a Packaging's properties.
		 *
		 * @param \Vendidero\Germanized\Shipments\Packaging $packaging The Packaging object.
		 * @param array                                    $changed_props The updated properties.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( 'woocommerce_gzd_packaging_object_updated_props', $packaging, $updated_props );
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
			'date_created',
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
		 * Filter to adjust Packaging query arguments after parsing.
		 *
		 * @param array     $wp_query_args Array containing parsed query arguments.
		 * @param array     $query_vars The original query arguments.
		 * @param Packaging $data_store The packaging data store object.
		 *
		 * @since 3.3.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_packaging_data_store_get_shipments_query', $wp_query_args, $query_vars, $this );
	}

	public function get_packaging_list( $args = array() ) {
		global $wpdb;

		$all_types = array_keys( wc_gzd_get_packaging_types() );

		$args = wp_parse_args(
			$args,
			array(
				'type' => $all_types,
			)
		);

		if ( ! is_array( $args['type'] ) ) {
			$args['type'] = array( $args['type'] );
		}

		$types = array_filter( wc_clean( $args['type'] ) );
		$types = empty( $types ) ? $all_types : $types;

		$query = "
			SELECT packaging_id FROM {$wpdb->gzd_packaging} 
			WHERE packaging_type IN ( '" . implode( "','", $types ) . "' )
			ORDER BY packaging_order ASC
		";

		if ( $all_types === $types ) {
			// Get from cache if available.
			$results = wp_cache_get( 'packaging-list', 'packaging' );

			if ( false === $results ) {
				$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				wp_cache_set( 'packaging-list', $results, 'packaging' );
			}
		} else {
			$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		foreach ( $results as $key => $packaging ) {
			$results[ $key ] = wc_gzd_get_packaging( $packaging );
		}

		return $results;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 * @param \Vendidero\Germanized\Shipments\Packaging|PackagingBox $packaging
	 *
	 * @return bool
	 */
	public function shipment_fits_into_packaging_naive( $shipment, $packaging ) {
		if ( is_a( $packaging, '\Vendidero\Germanized\Shipments\Packing\PackagingBox' ) ) {
			$packaging = $packaging->getReference();
		}

		if ( ! $packaging ) {
			return false;
		}

		$weight           = (float) wc_format_decimal( empty( $shipment->get_content_weight() ) ? 0 : wc_get_weight( $shipment->get_content_weight(), wc_gzd_get_packaging_weight_unit(), $shipment->get_weight_unit() ), 1 );
		$volume           = (float) wc_format_decimal( empty( $shipment->get_content_volume() ) ? 0 : wc_gzd_get_volume_dimension( $shipment->get_content_volume(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );
		$fits             = true;
		$packaging_volume = (float) $packaging->get_length() * (float) $packaging->get_width() * (float) $packaging->get_height();

		/**
		 * The packaging does not fit in case:
		 * - total weight is greater than it's maximum capability
		 * - the total volume is greater than the packaging volume
		 */
		if ( ! empty( $packaging->get_max_content_weight() ) && $weight > $packaging->get_max_content_weight() ) {
			$fits = false;
		} elseif ( $volume > $packaging_volume ) {
			$fits = false;
		}

		return $fits;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return \Vendidero\Germanized\Shipments\Packaging[]
	 */
	public function find_available_packaging_for_shipment( $shipment ) {
		$packaging_available = array();
		$items_to_pack       = $shipment->get_items_to_pack();
		$results             = false;

		// Get from cache if available.
		if ( $shipment->get_id() > 0 ) {
			$results = wp_cache_get( 'available-packaging-' . $shipment->get_id(), 'shipments' );
		}

		if ( false === $results && count( $items_to_pack ) > 0 ) {
			$available_packaging_ids = array();

			if ( Package::is_packing_supported() ) {
				$packaging_list = wc_gzd_get_packaging_list();
				$items          = \DVDoug\BoxPacker\ItemList::fromArray( $items_to_pack );

				foreach ( $packaging_list as $packaging ) {
					/**
					 * Make sure to only check naively fitting packaging to improve performance
					 */
					if ( ! $this->shipment_fits_into_packaging_naive( $shipment, $packaging ) ) {
						continue;
					}

					$box      = new PackagingBox( $packaging );
					$org_size = count( $items_to_pack );

					$packer = new VolumePacker( $box, $items );
					$packed = $packer->pack();

					if ( count( $packed->getItems() ) === $org_size ) {
						$packaging_available[]     = $packaging;
						$available_packaging_ids[] = $packaging->get_id();
					}
				}
			} else {
				global $wpdb;

				$weight = wc_format_decimal( empty( $shipment->get_weight() ) ? 0 : wc_get_weight( $shipment->get_weight(), wc_gzd_get_packaging_weight_unit(), $shipment->get_weight_unit() ), 1 );
				$length = wc_format_decimal( empty( $shipment->get_length() ) ? 0 : wc_get_dimension( $shipment->get_length(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );
				$width  = wc_format_decimal( empty( $shipment->get_width() ) ? 0 : wc_get_dimension( $shipment->get_width(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );
				$height = wc_format_decimal( empty( $shipment->get_height() ) ? 0 : wc_get_dimension( $shipment->get_height(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );

				$types     = array_keys( wc_gzd_get_packaging_types() );
				$threshold = apply_filters( 'woocommerce_gzd_shipment_packaging_match_threshold', 0, $shipment );

				$query_sql = "SELECT 
					packaging_id, 
					(packaging_length - %f) AS length_diff,
					(packaging_width - %f) AS width_diff,
					(packaging_height - %f) AS height_diff,
					((packaging_length - %f) + (packaging_width - %f) + (packaging_height - %f)) AS total_diff   
					FROM {$wpdb->gzd_packaging} 
					WHERE ( packaging_max_content_weight = 0 OR packaging_max_content_weight >= %f ) AND packaging_type IN ( '" . implode( "','", $types ) . "' )
					HAVING length_diff >= %f AND width_diff >= %f AND height_diff >= %f
					ORDER BY total_diff ASC, packaging_weight ASC, packaging_order ASC
				";

				$query   = $wpdb->prepare( $query_sql, $length, $width, $height, $length, $width, $height, $weight, $threshold, $threshold, $threshold ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( $results ) {
					foreach ( $results as $result ) {
						$available_packaging_ids[] = $result->packaging_id;
						$packaging_available[]     = wc_gzd_get_packaging( $result->packaging_id );
					}
				}
			}

			wp_cache_set( 'available-packaging-' . $shipment->get_id(), $available_packaging_ids, 'shipments' );
		} elseif ( count( $items_to_pack ) <= 0 ) {
			$packaging_available = wc_gzd_get_packaging_list();
		} else {
			foreach ( (array) $results as $packaging_id ) {
				$packaging_available[] = wc_gzd_get_packaging( $packaging_id );
			}
		}

		$packaging_list = apply_filters( 'woocommerce_gzd_find_available_packaging_for_shipment', $packaging_available, $shipment );

		return $this->sort_packaging_list( $packaging_list );
	}

	protected function sort_packaging_list( $packaging ) {
		usort( $packaging, array( $this, 'sort_packaging_list_callback' ) );

		return $packaging;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging_a
	 * @param \Vendidero\Germanized\Shipments\Packaging $packaging_b
	 *
	 * @return int
	 */
	protected function sort_packaging_list_callback( $packaging_a, $packaging_b ) {
		if ( $packaging_a->get_volume() === $packaging_b->get_volume() ) {
			return 0;
		}

		return ( $packaging_a->get_volume() > $packaging_b->get_volume() ) ? 1 : -1;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 * @param string|array $types
	 *
	 * @return \Vendidero\Germanized\Shipments\Packaging|false
	 */
	public function find_best_match_for_shipment( $shipment ) {
		$results   = $this->find_available_packaging_for_shipment( $shipment );
		$packaging = false;

		if ( ! empty( $results ) ) {
			$packaging = $results[0];

			/**
			 * In case more than one packaging is available - choose the default packaging in case it is available.
			 */
			if ( count( $results ) > 1 ) {
				$default_packaging_id = Package::get_setting( 'default_packaging' );

				if ( ! empty( $default_packaging_id ) ) {
					$default_packaging_id = absint( $default_packaging_id );

					foreach ( $results as $result ) {
						if ( $result->get_id() === $default_packaging_id ) {
							$packaging = $result;
							break;
						}
					}
				}
			}
		}

		return apply_filters( 'woocommerce_gzd_find_best_matching_packaging_for_shipment', $packaging, $shipment );
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
		$table           = $wpdb->gzd_packagingmeta;
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
}
