<?php

namespace Vendidero\Shiptastic\DataStores;

use Exception;
use Vendidero\Shiptastic\Package;
use WC_Data;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * WC Order Item Data Store
 *
 * @version  3.0.0
 */
class ShipmentItem extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_width',
		'_length',
		'_height',
		'_weight',
		'_total',
		'_subtotal',
		'_sku',
		'_return_reason_code',
		'_hs_code',
		'_customs_description',
		'_manufacture_country',
		'_attributes',
	);

	protected $core_props = array(
		'shipment_id',
		'order_item_id',
		'quantity',
		'name',
		'product_id',
		'parent_id',
		'item_parent_id',
	);

	protected $must_exist_meta_keys = array();

	protected $meta_type = 'stc_shipment_item';

	/**
	 * Create a new shipment item in the database.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 */
	public function create( &$item ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->stc_shipment_items,
			array(
				'shipment_id'                  => $item->get_shipment_id(),
				'shipment_item_quantity'       => $item->get_quantity(),
				'shipment_item_order_item_id'  => $item->get_order_item_id(),
				'shipment_item_product_id'     => $item->get_product_id(),
				'shipment_item_parent_id'      => $item->get_parent_id(),
				'shipment_item_item_parent_id' => $item->get_item_parent_id(),
				'shipment_item_name'           => $item->get_name(),
			)
		);

		$item->set_id( $wpdb->insert_id );
		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache( $item );

		/**
		 * Action that indicates that a new ShipmentItem has been created in the DB.
		 *
		 * @param integer                                      $shipment_item_id The shipment item id.
		 * @param \Vendidero\Shiptastic\ShipmentItem $item The shipment item object.
		 * @param integer                                      $shipment_id The shipment id.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_new_shipment_item', $item->get_id(), $item, $item->get_shipment_id() );
	}

	/**
	 * Update a shipment item in the database.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 */
	public function update( &$item ) {
		global $wpdb;

		$changes = $item->get_changes();

		if ( array_intersect( $this->core_props, array_keys( $changes ) ) ) {
			$wpdb->update(
				$wpdb->stc_shipment_items,
				array(
					'shipment_id'                  => $item->get_shipment_id(),
					'shipment_item_order_item_id'  => $item->get_order_item_id(),
					'shipment_item_quantity'       => $item->get_quantity(),
					'shipment_item_product_id'     => $item->get_product_id(),
					'shipment_item_parent_id'      => $item->get_parent_id(),
					'shipment_item_item_parent_id' => $item->get_item_parent_id(),
					'shipment_item_name'           => $item->get_name(),
				),
				array( 'shipment_item_id' => $item->get_id() )
			);
		}

		$this->save_item_data( $item );
		$item->save_meta_data();
		$item->apply_changes();
		$this->clear_cache( $item );

		/**
		 * Action that indicates that a ShipmentItem has been updated in the DB.
		 *
		 * @param integer                                      $shipment_item_id The shipment item id.
		 * @param \Vendidero\Shiptastic\ShipmentItem $item The shipment item object.
		 * @param integer                                      $shipment_id The shipment id.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipment_item_updated', $item->get_id(), $item, $item->get_shipment_id() );
	}

	/**
	 * Remove a shipment item from the database.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 * @param array         $args Array of args to pass to the delete method.
	 */
	public function delete( &$item, $args = array() ) {
		if ( $item->get_id() ) {
			global $wpdb;

			/**
			 * Action that fires before deleting a ShipmentItem from the DB.
			 *
			 * @param integer $shipment_item_id The shipment item id.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_before_delete_shipment_item', $item->get_id() );

			$wpdb->delete( $wpdb->stc_shipment_items, array( 'shipment_item_id' => $item->get_id() ) );
			$wpdb->delete( $wpdb->stc_shipment_itemmeta, array( 'stc_shipment_item_id' => $item->get_id() ) );

			/**
			 * Action that indicates that a ShipmentItem has been deleted from the DB.
			 *
			 * @param integer                                      $shipment_item_id The shipment item id.
			 * @param \Vendidero\Shiptastic\ShipmentItem $item The shipment item object.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_delete_shipment_item', $item->get_id(), $item );
			$this->clear_cache( $item );
		}
	}

	/**
	 * Read a shipment item from the database.
	 *
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 *
	 * @throws Exception If invalid shipment item.
	 */
	public function read( &$item ) {
		global $wpdb;

		$item->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'item-' . $item->get_id(), 'shiptastic-shipment-items' );

		if ( false === $data ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->stc_shipment_items} WHERE shipment_item_id = %d LIMIT 1;", $item->get_id() ) );
			wp_cache_set( 'item-' . $item->get_id(), $data, 'shiptastic-shipment-items' );
		}

		if ( ! $data ) {
			throw new Exception( esc_html_x( 'Invalid shipment item.', 'shipments', 'woocommerce-germanized' ) );
		}

		$item->set_props(
			array(
				'shipment_id'    => $data->shipment_id,
				'order_item_id'  => $data->shipment_item_order_item_id,
				'quantity'       => $data->shipment_item_quantity,
				'product_id'     => $data->shipment_item_product_id,
				'parent_id'      => $data->shipment_item_parent_id,
				'item_parent_id' => $data->shipment_item_item_parent_id,
				'name'           => $data->shipment_item_name,
			)
		);

		$this->read_item_data( $item );
		$item->read_meta_data();
		$item->set_object_read( true );
	}

	/**
	 * Read extra data associated with the shipment item.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 */
	protected function read_item_data( &$item ) {
		$props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( $this->meta_type, $item->get_id(), $meta_key, true );
		}

		$item->set_props( $props );
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
	 * @param WC_Data $shipment_item The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string   $meta_key Meta key to update.
	 * @param mixed    $meta_value Value to save.
	 *
	 * @return bool True if updated/deleted.
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 */
	protected function update_or_delete_meta( $shipment_item, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( $this->meta_type, $shipment_item->get_id(), $meta_key );
		} else {
			$updated = update_metadata( $this->meta_type, $shipment_item->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 */
	public function save_item_data( &$item ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {

			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		$props_to_update = $this->get_props_to_update( $item, $meta_key_to_props, $this->meta_type );

		foreach ( $props_to_update as $meta_key => $prop ) {

			$getter = "get_$prop";

			if ( ! is_callable( array( $item, $getter ) ) ) {
				continue;
			}

			$value = $item->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_meta( $item, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a ShipmentItem's properties.
		 *
		 * @param \Vendidero\Shiptastic\ShipmentItem $item The shipment item object.
		 * @param array                                        $changed_props The updated properties.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipment_item_object_updated_props', $item, $updated_props );
	}

	/**
	 * Clear meta cache.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item Shipment item object.
	 */
	public function clear_cache( &$item ) {
		wp_cache_delete( 'item-' . $item->get_id(), 'shiptastic-shipment-items' );
		wp_cache_delete( 'item-children-' . $item->get_id(), 'shiptastic-shipment-items' );
		wp_cache_delete( 'shipment-items-' . $item->get_shipment_id(), 'shiptastic-shipments' );
		wp_cache_delete( $item->get_id(), $this->meta_type . '_meta' );
	}

	/**
	 * Read item's children from database.
	 *
	 * @param \Vendidero\Shiptastic\ShipmentItem $item The item.
	 *
	 * @return array|\Vendidero\Shiptastic\ShipmentItem[]
	 */
	public function read_children( &$item ) {
		// Get from cache if available.
		$items = wp_cache_get( 'item-children-' . $item->get_id(), 'shiptastic-shipment-items' );

		if ( false === $items ) {
			global $wpdb;

			$get_items_sql = $wpdb->prepare( "SELECT * FROM {$wpdb->stc_shipment_items} WHERE shipment_item_item_parent_id = %d ORDER BY shipment_item_id;", $item->get_id() );
			$items         = $wpdb->get_results( $get_items_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $items as $child ) {
				wp_cache_set( 'item-' . $item->get_id(), $child, 'shiptastic-shipment-items' );
			}

			wp_cache_set( 'item-children-' . $item->get_id(), $items, 'shiptastic-shipment-items' );
		}

		if ( ! empty( $items ) ) {
			$items = array_map(
				function ( $item ) {
					return wc_stc_get_shipment_item( $item->shipment_item_id, $item->shipment_item_type );
				},
				$items
			);
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->stc_shipment_itemmeta;
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
}
