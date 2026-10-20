<?php

namespace Vendidero\OrderWithdrawalButton\DataStores;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Order_Item_Shipping_Data_Store class.
 */
class WithdrawalItem extends \Abstract_WC_Order_Item_Type_Data_Store implements \WC_Object_Data_Store_Interface, \WC_Order_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_parent_id',
		'_quantity',
		'_refunded_quantity',
		'_product_id',
		'_variation_id',
	);

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalItem $item Item to read to.
	 * @throws \Exception If invalid shipping order item.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props(
			array(
				'parent_id'         => get_metadata( 'order_item', $id, '_parent_id', true ),
				'quantity'          => get_metadata( 'order_item', $id, '_quantity', true ),
				'refunded_quantity' => get_metadata( 'order_item', $id, '_refunded_quantity', true ),
				'product_id'        => get_metadata( 'order_item', $id, '_product_id', true ),
				'variation_id'      => get_metadata( 'order_item', $id, '_variation_id', true ),
			)
		);

		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @since 3.0.0
	 * @param \Vendidero\OrderWithdrawalButton\WithdrawalItem $item Item to read to.
	 */
	public function save_item_data( &$item ) {
		$id                = $item->get_id();
		$changes           = $item->get_changes();
		$meta_key_to_props = array(
			'_parent_id'         => 'parent_id',
			'_quantity'          => 'quantity',
			'_refunded_quantity' => 'refunded_quantity',
			'_product_id'        => 'product_id',
			'_variation_id'      => 'variation_id',
		);
		$props_to_update   = $this->get_props_to_update( $item, $meta_key_to_props, 'order_item' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			update_metadata( 'order_item', $id, $meta_key, $item->{"get_$prop"}( 'edit' ) );
		}
	}
}
