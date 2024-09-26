<?php

namespace Vendidero\Germanized\Shipments\DataStores;

use DVDoug\BoxPacker\VolumePacker;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packaging\Helper;
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
		'weight_unit',
		'max_content_weight',
		'dimension_unit',
		'length',
		'width',
		'height',
		'inner_length',
		'inner_width',
		'inner_height',
		'description',
		'order',
	);

	/**
	 * Data stored in meta keys, but not considered "meta" for a packaging.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_available_shipping_provider',
		'_available_shipping_classes',
		'_configuration_sets',
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
		$packaging->set_weight_unit( wc_gzd_get_packaging_weight_unit() );
		$packaging->set_dimension_unit( wc_gzd_get_packaging_dimension_unit() );

		$data = array(
			'packaging_type'               => $packaging->get_type(),
			'packaging_description'        => $packaging->get_description(),
			'packaging_weight'             => $packaging->get_weight(),
			'packaging_weight_unit'        => $packaging->get_weight_unit(),
			'packaging_max_content_weight' => $packaging->get_max_content_weight(),
			'packaging_dimension_unit'     => $packaging->get_dimension_unit(),
			'packaging_length'             => $packaging->get_length(),
			'packaging_width'              => $packaging->get_width(),
			'packaging_height'             => $packaging->get_height(),
			'packaging_inner_length'       => $packaging->get_inner_length(),
			'packaging_inner_width'        => $packaging->get_inner_width(),
			'packaging_inner_height'       => $packaging->get_inner_height(),
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
					'weight_unit'        => $data->packaging_weight_unit,
					'max_content_weight' => $data->packaging_max_content_weight,
					'dimension_unit'     => $data->packaging_dimension_unit,
					'length'             => $data->packaging_length,
					'width'              => $data->packaging_width,
					'height'             => $data->packaging_height,
					'inner_length'       => $data->packaging_inner_length,
					'inner_width'        => $data->packaging_inner_width,
					'inner_height'       => $data->packaging_inner_height,
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
			throw new Exception( esc_html_x( 'Invalid packaging.', 'shipments', 'woocommerce-germanized' ) );
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

		Helper::clear_cache();

		if ( $cache = \Vendidero\Germanized\Shipments\Caches\Helper::get_cache_object( 'packagings' ) ) {
			$cache->remove( $packaging->get_id() );
		}
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
	 * @param WC_Data $wc_data_object The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @return bool True if updated/deleted.
	 *@since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 */
	protected function update_or_delete_meta( $wc_data_object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( $this->meta_type, $wc_data_object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( $this->meta_type, $wc_data_object->get_id(), $meta_key, $meta_value );
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

	/**
	 * @return array
	 */
	public function get_all_packaging() {
		global $wpdb;

		// Get from cache if available.
		$results = wp_cache_get( 'packaging-list', 'packaging' );

		if ( false === $results ) {
			$query = "
				SELECT packaging_id FROM {$wpdb->gzd_packaging} 
				ORDER BY packaging_order ASC
			";

			$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			wp_cache_set( 'packaging-list', $results, 'packaging' );
		}

		return $results;
	}

	/**
	 * @param $args
	 *
	 * @return \Vendidero\Germanized\Shipments\Packaging[]
	 */
	public function get_packaging_list( $args = array() ) {
		return Helper::get_packaging_list( $args );
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

		if ( ! $packaging->supports_shipping_provider( $shipment->get_shipping_provider() ) ) {
			return false;
		}

		$weight = (float) wc_format_decimal( empty( $shipment->get_content_weight() ) ? 0 : wc_get_weight( $shipment->get_content_weight(), wc_gzd_get_packaging_weight_unit(), $shipment->get_weight_unit() ), 3 );
		$volume = (float) wc_format_decimal( empty( $shipment->get_content_volume() ) ? 0 : wc_gzd_get_volume_dimension( $shipment->get_content_volume(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );
		$fits   = true;

		if ( $packaging->has_inner_dimensions() ) {
			$packaging_volume = (float) $packaging->get_inner_length() * (float) $packaging->get_inner_width() * (float) $packaging->get_inner_height();
		} else {
			$packaging_volume = (float) $packaging->get_length() * (float) $packaging->get_width() * (float) $packaging->get_height();
		}

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
		$items               = $shipment->get_items_to_pack();
		$results             = false;

		// Get from cache if available.
		if ( $shipment->get_id() > 0 ) {
			$results = wp_cache_get( 'available-packaging-' . $shipment->get_id(), 'shipments' );
		}

		if ( false === $results && count( $items ) > 0 ) {
			$available_packaging_ids = array();

			if ( Package::is_packing_supported() ) {
				$packaging_list = $this->get_packaging_list( array( 'shipping_provider' => $shipment->get_shipping_provider() ) );

				foreach ( $packaging_list as $packaging ) {
					/**
					 * Make sure to only check naively fitting packaging to improve performance
					 */
					if ( ! $this->shipment_fits_into_packaging_naive( $shipment, $packaging ) ) {
						continue;
					}

					$box        = new PackagingBox( $packaging );
					$item_count = count( $items );

					$packer = new VolumePacker( $box, $items );
					$packed = $packer->pack();

					if ( count( $packed->getItems() ) === $item_count ) {
						$packaging_available[]     = $packaging;
						$available_packaging_ids[] = $packaging->get_id();
					}
				}
			} else {
				global $wpdb;

				$weight = (float) wc_format_decimal( empty( $shipment->get_weight() ) ? 0 : wc_get_weight( $shipment->get_weight(), wc_gzd_get_packaging_weight_unit(), $shipment->get_weight_unit() ), 3 );
				$length = (float) wc_format_decimal( empty( $shipment->get_length() ) ? 0 : wc_get_dimension( $shipment->get_length(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );
				$width  = (float) wc_format_decimal( empty( $shipment->get_width() ) ? 0 : wc_get_dimension( $shipment->get_width(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );
				$height = (float) wc_format_decimal( empty( $shipment->get_height() ) ? 0 : wc_get_dimension( $shipment->get_height(), wc_gzd_get_packaging_dimension_unit(), $shipment->get_dimension_unit() ), 1 );

				$types     = array_keys( wc_gzd_get_packaging_types() );
				$threshold = apply_filters( 'woocommerce_gzd_shipment_packaging_match_threshold', 0, $shipment );

				$query_sql = "SELECT 
					packaging_id,
					CASE
                  		WHEN packaging_inner_length > 0
                 			THEN (packaging_inner_length - %f)
                  		ELSE (packaging_length - %f)
                    END as length_diff,
    				CASE
                  		WHEN packaging_inner_width > 0
                 			THEN (packaging_inner_width - %f)
                  		ELSE (packaging_width - %f)
                    END as width_diff,
    				CASE
                  		WHEN packaging_inner_height > 0
                 			THEN (packaging_inner_height - %f)
                  		ELSE (packaging_height - %f)
                    END as height_diff
					FROM {$wpdb->gzd_packaging} 
					WHERE ( packaging_max_content_weight = 0 OR packaging_max_content_weight >= %f ) AND packaging_type IN ( '" . implode( "','", $types ) . "' )
					HAVING length_diff >= %f AND width_diff >= %f AND height_diff >= %f
					ORDER BY (length_diff+width_diff+height_diff) ASC, packaging_weight ASC, packaging_order ASC
				";

				$query   = $wpdb->prepare( $query_sql, $length, $length, $width, $width, $height, $height, $weight, $threshold, $threshold, $threshold ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( $results ) {
					foreach ( $results as $result ) {
						$packaging = wc_gzd_get_packaging( $result->packaging_id );

						if ( ! $packaging->supports_shipping_provider( $shipment->get_shipping_provider() ) ) {
							continue;
						}

						$available_packaging_ids[] = $packaging->get_id();
						$packaging_available[]     = $packaging;
					}
				}
			}

			wp_cache_set( 'available-packaging-' . $shipment->get_id(), $available_packaging_ids, 'shipments' );
		} elseif ( count( $items ) <= 0 ) {
			$packaging_available = $this->get_packaging_list();
		} else {
			foreach ( (array) $results as $packaging_id ) {
				$packaging = wc_gzd_get_packaging( $packaging_id );

				if ( ! $packaging->supports_shipping_provider( $shipment->get_shipping_provider() ) ) {
					continue;
				}

				$packaging_available[] = $packaging;
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
