<?php

namespace Vendidero\Shiptastic\Compatibility;

use Vendidero\Shiptastic\Interfaces\Compatibility;
use Vendidero\Shiptastic\Order;
use Vendidero\Shiptastic\Packaging;
use Vendidero\Shiptastic\Packing\Item;
use Vendidero\Shiptastic\Product;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentItem;

defined( 'ABSPATH' ) || exit;

class Bundles implements Compatibility {

	protected static $cart_bundled_by_map = array();

	public static function is_active() {
		return class_exists( 'WC_Bundles' );
	}

	public static function init() {
		add_action(
			'woocommerce_shiptastic_before_prepare_cart_contents',
			function () {
				self::$cart_bundled_by_map = array();
			}
		);

		add_filter( 'woocommerce_shiptastic_shipment_order_available_items_for_packing', array( __CLASS__, 'items_for_packing' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_cart_item', array( __CLASS__, 'adjust_cart_item' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_order_selectable_items_for_shipment', array( __CLASS__, 'filter_bundle_children' ), 10, 3 );
		add_action( 'woocommerce_shiptastic_shipment_added_item', array( __CLASS__, 'on_added_shipment_item' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_shipment_order_item_quantity_left_for_shipping', array( __CLASS__, 'maybe_remove_children' ), 10, 2 );
		add_filter( 'woocommerce_shiptastic_order_item_product', array( __CLASS__, 'get_product_from_item' ), 10, 2 );
	}

	/**
	 * @param array $items
	 * @param Order $shipment_order
	 *
	 * @return array
	 */
	public static function items_for_packing( $items, $shipment_order ) {
		foreach ( $items as $order_item_id => $item ) {
			if ( ! $order_item = $shipment_order->get_order()->get_item( $order_item_id ) ) {
				continue;
			}

			if ( wc_pb_is_bundled_order_item( $order_item ) && ! self::order_item_is_shipped_individually( $order_item ) ) {
				if ( $container = wc_pb_get_bundled_order_item_container( $order_item, $shipment_order->get_order(), false ) ) {
					if ( self::order_item_is_assembled_bundle( $container, $shipment_order ) ) {
						unset( $items[ $order_item_id ] );
					}
				}
			}
		}

		return $items;
	}

	/**
	 * @param ShipmentItem $item
	 * @param Shipment $shipment
	 *
	 * @return void
	 */
	public static function on_added_shipment_item( $item, $shipment ) {
		if ( $order_item = $item->get_order_item() ) {
			if ( $shipment_order = $shipment->get_order_shipment() ) {
				$order = $shipment_order->get_order();

				if ( self::order_item_is_assembled_bundle( $order_item, $shipment_order ) ) {
					$available = $shipment_order->get_available_items_for_shipment(
						array(
							'shipment_id'        => $shipment->get_id(),
							'disable_duplicates' => true,
						)
					);

					$children_to_add = array();

					foreach ( $available as $item_id => $item_data ) {
						if ( ! $child_order_item = $order->get_item( $item_id ) ) {
							continue;
						}

						if ( wc_pb_is_bundled_order_item( $child_order_item ) && ! self::order_item_is_shipped_individually( $child_order_item ) ) {
							$container_id = wc_pb_get_bundled_order_item_container( $child_order_item, $order, true );

							if ( $container_id === $order_item->get_id() ) {
								$quantity_per_bundle         = absint( $child_order_item->get_quantity() / $order_item->get_quantity() );
								$child_quantity              = min( $item_data['max_quantity'], $quantity_per_bundle * $item->get_quantity() );
								$children_to_add[ $item_id ] = $child_quantity;

								$props = array(
									'quantity' => $child_quantity,
									'parent'   => $item,
								);

								if ( $child_item = wc_stc_create_shipment_item( $shipment, $child_order_item, $props ) ) {
									$shipment->add_item( $child_item );
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param $order_item
	 * @param $order
	 *
	 * @return void
	 */
	protected static function order_item_is_assembled_bundle( $order_item, $shipment_order ) {
		$is_assembled = false;

		if ( wc_pb_is_bundle_container_order_item( $order_item ) ) {
			if ( $product = $shipment_order->get_order_item_product( $order_item ) ) {
				if ( self::is_assembled_bundle( $product ) ) {
					$is_assembled = true;
				}
			}
		}

		return $is_assembled;
	}

	protected static function is_assembled_bundle( $product ) {
		$is_assembled = false;

		if ( $product->is_type( 'bundle' ) && apply_filters( 'woocommerce_shiptastic_is_assembled_bundle', ! $product->is_virtual(), $product ) ) {
			$is_assembled = true;
		}

		return $is_assembled;
	}

	/**
	 * @param integer $quantity_left
	 * @param \WC_Order_Item $order_item
	 *
	 * @return integer
	 */
	public static function maybe_remove_children( $quantity_left, $order_item ) {
		if ( wc_pb_is_bundled_order_item( $order_item ) ) {
			if ( apply_filters( 'woocommerce_shiptastic_remove_hidden_bundled_items', 'yes' === $order_item->get_meta( '_bundled_item_hidden' ), $order_item ) ) {
				$quantity_left = 0;
			}
		}

		return $quantity_left;
	}

	/**
	 * @param \WC_Order_Item[] $items
	 * @param array $args
	 * @param Order $shipment_order
	 *
	 * @return \WC_Order_Item[]
	 */
	public static function filter_bundle_children( $items, $args, $shipment_order ) {
		$order = $shipment_order->get_order();

		foreach ( $items as $order_item_id => $item ) {
			if ( ! $order_item = $order->get_item( $order_item_id ) ) {
				continue;
			}

			if ( wc_pb_is_bundled_order_item( $order_item ) && ! self::order_item_is_shipped_individually( $order_item ) ) {
				if ( $container = wc_pb_get_bundled_order_item_container( $order_item, $order, false ) ) {
					if ( self::order_item_is_assembled_bundle( $container, $shipment_order ) ) {
						unset( $items[ $order_item_id ] );
					}
				}
			}
		}

		return $items;
	}

	/**
	 * @param \WC_Order_Item $order_item
	 *
	 * @return bool
	 */
	protected static function order_item_is_shipped_individually( $order_item ) {
		if ( 'yes' === $order_item->get_meta( '_bundled_item_needs_shipping' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param Product|null $product
	 * @param \WC_Order_Item_Product $item
	 *
	 * @return Product
	 */
	public static function get_product_from_item( $product, $item ) {
		if ( ( ! $order = $item->get_order() ) || ! $product ) {
			return $product;
		}

		$reset_dimensions = false;
		$reset_weight     = false;

		if ( wc_pb_is_bundle_container_order_item( $item, $order ) ) {
			if ( $product->needs_shipping() ) {
				if ( $bundle_weight = $item->get_meta( '_bundle_weight', true ) ) {
					if ( is_null( $bundle_weight ) ) {
						$bundle_weight = '';
					}

					$product->set_weight( $bundle_weight );
				}
			} else {
				$reset_weight     = true;
				$reset_dimensions = true;
			}
		} elseif ( wc_pb_is_bundled_order_item( $item, $order ) ) {
			if ( ! $product->needs_shipping() || ! self::order_item_is_shipped_individually( $item ) ) {
				$reset_weight     = true;
				$reset_dimensions = true;
			}

			/**
			 * Force resetting product dimensions/weight for assembled bundle children to make sure
			 * that the content weight/dimensions matches the actual bundle data.
			 */
			if ( ! self::order_item_is_shipped_individually( $item ) ) {
				$shipment_order = wc_stc_get_shipment_order( $order );

				if ( $container = wc_pb_get_bundled_order_item_container( $item, $order, false ) ) {
					if ( self::order_item_is_assembled_bundle( $container, $shipment_order ) ) {
						$aggregate_weight = false;
						$reset_dimensions = true;
						$reset_weight     = false;

						if ( $parent_product = $shipment_order->get_order_item_product( $container ) ) {
							if ( is_a( $parent_product->get_product(), 'WC_Product_Bundle' ) && is_callable( array( $parent_product->get_product(), 'get_aggregate_weight' ) ) ) {
								$aggregate_weight = $parent_product->get_product()->get_aggregate_weight();
							}
						}

						if ( ! $aggregate_weight ) {
							$reset_weight = true;
						}
					}
				}
			}
		}

		if ( $reset_dimensions ) {
			$product->set_shipping_width( 0 );
			$product->set_shipping_height( 0 );
			$product->set_shipping_length( 0 );
		}

		if ( $reset_weight ) {
			$product->set_weight( 0 );
		}

		return $product;
	}

	/**
	 * Product Bundles cart item compatibility:
	 * In case the current item belongs to a parent bundle item (which contains the actual price)
	 * copy the pricing data from the parent once, e.g. for the first bundled item.
	 *
	 * @param $item
	 * @param $content_key
	 *
	 * @return mixed
	 */
	public static function adjust_cart_item( $item, $content_key ) {
		if ( isset( $item['bundled_by'] ) && 0.0 === (float) $item['line_total'] && function_exists( 'wc_pb_get_bundled_cart_item_container' ) ) {
			$bundled_by = $item['bundled_by'];

			if ( ! in_array( $bundled_by, self::$cart_bundled_by_map, true ) ) {
				if ( $container = wc_pb_get_bundled_cart_item_container( $item ) ) {
					$item['line_total']        = (float) $container['line_total'];
					$item['line_subtotal']     = (float) $container['line_subtotal'];
					$item['line_tax']          = (float) $container['line_tax'];
					$item['line_subtotal_tax'] = (float) $container['line_subtotal_tax'];

					self::$cart_bundled_by_map[] = $bundled_by;
				}
			}
		}

		return $item;
	}
}
