<?php

namespace Vendidero\Germanized\Shipments\Packing;

use DVDoug\BoxPacker\BoxList;
use DVDoug\BoxPacker\ItemList;
use DVDoug\BoxPacker\PackedBoxList;
use Vendidero\Germanized\Shipments\Interfaces\PackingBox;
use Vendidero\Germanized\Shipments\Interfaces\PackingItem;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packaging;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $packaging = null;

	/**
	 * @var array|ItemList
	 */
	protected static $items_too_large = array();

	/**
	 * @param false $id
	 *
	 * @return PackagingBox[]|boolean|PackagingBox
	 */
	public static function get_packaging_boxes( $id = false ) {
		if ( is_null( self::$packaging ) ) {
			self::$packaging = array();

			foreach ( wc_gzd_get_packaging_list() as $packaging ) {
				self::$packaging[ $packaging->get_id() ] = new PackagingBox( $packaging );
			}
		}

		if ( false !== $id ) {
			if ( is_array( $id ) ) {
				$first_val = ! empty( $id ) ? array_values( $id )[0] : false;

				if ( is_a( $first_val, '\Vendidero\Germanized\Shipments\Packaging' ) ) {
					$packaging_boxes = array();

					foreach ( $id as $packaging ) {
						if ( array_key_exists( $packaging->get_id(), self::$packaging ) ) {
							$packaging_boxes[ $packaging->get_id() ] = self::$packaging[ $packaging->get_id() ];
						}
					}

					return $packaging_boxes;
				} else {
					return array_intersect_key( self::$packaging, array_flip( $id ) );
				}
			} else {
				return array_key_exists( $id, self::$packaging ) ? self::$packaging[ $id ] : false;
			}
		}

		return self::$packaging;
	}

	/**
	 * @param Packaging $packaging
	 *
	 * @return PackagingBox
	 */
	public static function get_packaging_box( $packaging ) {
		$packaging_boxes = self::get_packaging_boxes();

		return array_key_exists( $packaging->get_id(), $packaging_boxes ) ? $packaging_boxes[ $packaging->get_id() ] : new PackagingBox( $packaging );
	}

	public static function enable_auto_packing() {
		return 'yes' === get_option( 'woocommerce_gzd_shipments_enable_auto_packing' );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Packing\ItemList $items
	 * @param PackingBox[]|BoxList $boxes
	 *
	 * @return PackedBoxList
	 */
	public static function pack( $items, $boxes, $context = 'order' ) {
		self::$items_too_large = array();

		/**
		 * @var \Vendidero\Germanized\Shipments\Packing\Packer $packer
		 */
		$packer = apply_filters( 'woocommerce_gzd_shipments_packer_instance', new Packer(), $items, $boxes, $context );
		$packer->set_boxes( $boxes );
		$packer->set_items( $items );

		if ( 'yes' !== get_option( 'woocommerce_gzd_shipments_packing_balance_weights' ) ) {
			/**
			 * Pack the first available package as full as possible.
			 */
			$packer->set_max_boxes_to_balance_weight( 0 );
		}

		do_action( 'woocommerce_gzd_shipments_packer_before_pack', $packer, $context );

		$packed_boxes = $packer->pack();

		// Items that do not fit in any box
		self::$items_too_large = $packer->get_unpacked_items();

		if ( self::$items_too_large->count() > 0 ) {
			foreach ( self::$items_too_large as $item_too_large ) {
				Package::log( sprintf( _x( 'Item %1$s did not fit the available packaging.', 'shipments', 'woocommerce-germanized' ), $item_too_large->getDescription() ), 'info', 'packing' );
			}
		}

		return $packed_boxes;
	}

	/**
	 * @return ItemList|array
	 */
	public static function get_last_unpacked_items() {
		return self::$items_too_large;
	}

	public static function get_available_packaging( $id = false ) {
		return self::get_packaging_boxes( $id );
	}
}
