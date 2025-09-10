<?php

namespace Vendidero\Shiptastic\DataStores;

use Vendidero\Shiptastic\Package;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;
use Exception;
use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Shipping Provider Data Store.
 *
 * @version  3.0.0
 */
class ShippingProvider extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_tracking_url_placeholder',
		'_tracking_desc_placeholder',
		'_description',
		'_return_manual_confirmation',
		'_return_instructions',
		'_return_costs',
		'_supports_customer_returns',
		'_supports_guest_returns',
	);

	protected $core_props = array(
		'activated',
		'title',
		'name',
		'order',
	);

	protected $meta_type = 'stc_shipping_provider';

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new provider in the database.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 */
	public function create( &$provider ) {
		global $wpdb;

		$provider->set_name( $this->get_unqiue_name( $provider ) );

		if ( 0 === $provider->get_order( 'edit' ) ) {
			$max_order     = 1;
			$max_order_col = $wpdb->get_col( "SELECT MAX(shipping_provider_order) FROM {$wpdb->stc_shipping_provider}" );

			if ( ! empty( $max_order_col ) ) {
				$max_order = absint( $max_order_col[0] ) + 1;
			}

			$provider->set_order( $max_order );
		}

		$data = array(
			'shipping_provider_activated' => $provider->is_activated() ? 1 : 0,
			'shipping_provider_name'      => $provider->get_name( 'edit' ),
			'shipping_provider_title'     => $provider->get_title( 'edit' ),
			'shipping_provider_order'     => $provider->get_order( 'edit' ),
		);

		$wpdb->insert(
			$wpdb->stc_shipping_provider,
			$data
		);

		$provider_id = $wpdb->insert_id;

		if ( $provider_id ) {
			$provider->set_id( $provider_id );

			$this->save_provider_data( $provider );

			$provider->update_settings_with_defaults();
			$provider->save_meta_data();
			$provider->apply_changes();

			$this->clear_caches( $provider );

			/**
			 * Action that indicates that a new Shipping Provider has been created in the DB.
			 *
			 * @param integer                                                     $provider_id The provider id.
			 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $shipping_provider The shipping provider instance.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_new_shipping_provider', $provider_id, $provider );
		}
	}

	/**
	 * Generate a unique name to save to the object.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 * @return string
	 */
	protected function get_unqiue_name( $provider ) {
		global $wpdb;

		$slug = sanitize_key( $provider->get_title() );

		// Post slugs must be unique across all posts.
		$check_sql           = "SELECT shipping_provider_name FROM $wpdb->stc_shipping_provider WHERE shipping_provider_name = %s AND shipping_provider_id != %d LIMIT 1";
		$provider_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $provider->get_id() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $provider_name_check || ( $this->is_manual_creation_request() && $this->is_reserved_name( $slug ) ) ) {
			$suffix = 2;
			do {
				$alt_provider_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$provider_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_provider_name, $provider->get_id() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				++$suffix;
			} while ( $provider_name_check || ( $this->is_manual_creation_request() && $this->is_reserved_name( $alt_provider_name ) ) );
			$slug = $alt_provider_name;
		}

		return $slug;
	}

	protected function is_manual_creation_request() {
		return apply_filters( 'woocommerce_shiptastic_shipping_provider_is_manual_creation_request', false );
	}

	protected function is_reserved_name( $name ) {
		$reserved_names = array(
			'dhl',
			'deutsche_post',
			'dpd',
			'gls',
			'ups',
			'hermes',
			'tnt',
			'fedex',
			'passport',
			'usps',
			'aramex',
			'blue_dart',
			'delhivery',
			'parcelforce',
			'postnl',
			'royal_mail',
		);

		return apply_filters( 'woocommerce_shiptastic_shipping_provider_is_reserved_name', in_array( $name, $reserved_names, true ) );
	}

	/**
	 * Method to update a shipping provider in the database.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 */
	public function update( &$provider ) {
		global $wpdb;

		$updated_props = array();
		$core_props    = $this->core_props;
		$changed_props = array_keys( $provider->get_changes() );
		$provider_data = array();

		foreach ( $changed_props as $prop ) {

			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'activated':
					$provider_data[ 'shipping_provider_' . $prop ] = $provider->is_activated() ? 1 : 0;
					break;
				default:
					if ( is_callable( array( $provider, 'get_' . $prop ) ) ) {
						$provider_data[ 'shipping_provider_' . $prop ] = $provider->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $provider_data ) ) {
			$wpdb->update(
				$wpdb->stc_shipping_provider,
				$provider_data,
				array( 'shipping_provider_id' => $provider->get_id() )
			);
		}

		$this->save_provider_data( $provider );

		$provider->save_meta_data();
		$provider->apply_changes();

		$this->clear_caches( $provider );

		/**
		 * Action that indicates that a shipping provider has been updated in the DB.
		 *
		 * @param integer                                                     $shipping_provider_id The shipping provider id.
		 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $shipping_provider The shipping provider instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipping_provider_updated', $provider->get_id(), $provider );
	}

	/**
	 * Remove a shipping provider from the database.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 * @param bool                $force_delete Unused param.
	 */
	public function delete( &$provider, $force_delete = false ) {
		global $wpdb;

		$wpdb->delete( $wpdb->stc_shipping_provider, array( 'shipping_provider_id' => $provider->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->stc_shipping_providermeta, array( 'stc_shipping_provider_id' => $provider->get_id() ), array( '%d' ) );

		$this->clear_caches( $provider );

		/**
		 * Action that indicates that a shipping provider has been deleted from the DB.
		 *
		 * @param integer                                                     $shipping_provider_id The shipping provider id.
		 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider The shipping provider object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipping_provider_deleted', $provider->get_id(), $provider );
	}

	/**
	 * Read a shipping provider from the database.
	 *
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 *
	 * @throws Exception Throw exception if invalid shipping provider.
	 */
	public function read( &$provider ) {
		global $wpdb;

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->stc_shipping_provider} WHERE shipping_provider_id = %d LIMIT 1",
				$provider->get_id()
			)
		);

		if ( $data ) {
			$provider->set_props(
				array(
					'name'      => $data->shipping_provider_name,
					'title'     => $data->shipping_provider_title,
					'activated' => $data->shipping_provider_activated,
					'order'     => $data->shipping_provider_order,
				)
			);

			$this->read_provider_data( $provider );

			$provider->read_meta_data();
			$provider->set_object_read( true );

			/**
			 * Action that indicates that a shipping provider has been loaded from DB.
			 *
			 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider The shipping provider object.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_shipping_provider_loaded', $provider );
		} else {
			throw new Exception( esc_html_x( 'Invalid shipping service provider.', 'shipments', 'woocommerce-germanized' ) );
		}
	}

	public function is_activated( $name ) {
		$shipping_providers = $this->get_shipping_providers();

		return array_key_exists( $name, $shipping_providers ) ? wc_string_to_bool( $shipping_providers[ $name ]->shipping_provider_activated ) : false;
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 */
	protected function clear_caches( &$provider ) {
		wp_cache_delete( $provider->get_id(), $this->meta_type . '_meta' );
		wp_cache_delete( 'all-providers', 'shiptastic-shipping-providers' );

		if ( $cache = \Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'shipping-providers' ) ) {
			$cache->remove( $provider->get_name() );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Read extra data associated with the shipping provider.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 */
	protected function read_provider_data( &$provider ) {
		$props     = array();
		$meta_keys = $this->internal_meta_keys;

		foreach ( $provider->get_extra_data_keys() as $key ) {
			$meta_keys[] = '_' . $key;
		}

		foreach ( $meta_keys as $meta_key ) {
			if ( function_exists( 'get_metadata_raw' ) ) {
				$value = get_metadata_raw( 'stc_shipping_provider', $provider->get_id(), $meta_key, true );
			} else {
				$value = null;

				if ( metadata_exists( 'stc_shipping_provider', $provider->get_id(), $meta_key ) ) {
					$value = get_metadata( 'stc_shipping_provider', $provider->get_id(), $meta_key, true );
				}
			}

			if ( ! is_null( $value ) ) {
				$props[ substr( $meta_key, 1 ) ] = $value;
			}
		}

		$provider->set_props( $props );
	}

	/**
	 * Save shipping provider data.
	 *
	 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider Shipping provider object.
	 */
	protected function save_provider_data( &$provider ) {
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
		$extra_data_keys = $provider->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $this->get_props_to_update( $provider, $meta_key_to_props, 'stc_shipping_provider' );

		foreach ( $props_to_update as $meta_key => $prop ) {

			if ( ! is_callable( array( $provider, "get_$prop" ) ) ) {
				continue;
			}

			$value = $provider->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			if ( is_bool( $value ) ) {
				$value = wc_bool_to_string( $value );
			}

			$updated = $this->update_or_delete_meta( $provider, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a shipping providers' properties.
		 *
		 * @param \Vendidero\Shiptastic\ShippingProvider\Simple $provider The shipping provider object.
		 * @param array                                                       $changed_props The updated properties.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipping_provider_object_updated_props', $provider, $updated_props );
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
	 * @param WC_Data $shipping_provider The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @return bool True if updated/deleted.
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 */
	protected function update_or_delete_meta( $shipping_provider, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( 'stc_shipping_provider', $shipping_provider->get_id(), $meta_key );
		} else {
			$updated = update_metadata( 'stc_shipping_provider', $shipping_provider->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->stc_shipping_providermeta;
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

	public function get_shipping_provider_count() {
		return count( $this->get_shipping_providers() );
	}

	public function get_shipping_provider_name( $provider_id ) {
		foreach ( $this->get_shipping_providers() as $shipping_provider ) {
			if ( (int) $shipping_provider->shipping_provider_id === (int) $provider_id ) {
				return $shipping_provider->shipping_provider_name;
			}
		}

		return false;
	}

	public function get_shipping_providers() {
		global $wpdb;

		$shipping_providers = wp_cache_get( 'all-providers', 'shiptastic-shipping-providers' );

		if ( false === $shipping_providers ) {
			$providers          = $wpdb->get_results( "SELECT * FROM $wpdb->stc_shipping_provider ORDER BY shipping_provider_order ASC" );
			$shipping_providers = array();

			foreach ( $providers as $provider ) {
				try {
					$shipping_providers[ $provider->shipping_provider_name ] = $provider;
				} catch ( Exception $e ) {
					continue;
				}
			}

			wp_cache_set( 'all-providers', $shipping_providers, 'shiptastic-shipping-providers' );
		}

		return $shipping_providers;
	}
}
