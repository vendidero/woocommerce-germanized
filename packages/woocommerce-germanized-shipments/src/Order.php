<?php

namespace Vendidero\Germanized\Shipments;
use WC_Order;
use WC_Customer;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class Order {

    /**
     * The actual order item object
     *
     * @var object
     */
    protected $order;

    protected $shipments = null;

    protected $shipments_to_delete = array();

    /**
     * @param WC_Customer $customer
     */
    public function __construct( $order ) {
        $this->order = $order;
    }

    /**
     * Returns the Woo WC_Order original object
     *
     * @return object|WC_Order
     */
    public function get_order() {
        return $this->order;
    }

    public function get_shipping_status() {
        $status    = 'not-shipped';
        $shipments = $this->get_simple_shipments();

        if ( ! empty( $shipments ) ) {
            foreach( $shipments as $shipment ) {

                if ( $shipment->has_status( wc_gzd_get_shipment_sent_statuses() ) ) {
                    $status = 'partially-shipped';
                    break;
                }
            }
        }

        if ( ! $this->needs_shipping( array( 'sent_only' => true ) ) ) {
            $status = 'shipped';
        }

        return $status;
    }

    public function validate_shipments( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'save' => true,
        ) );

        foreach( $this->get_simple_shipments() as $shipment ) {

            if ( $shipment->is_editable() ) {

	            // Make sure we are working based on the current instance.
	            $shipment->set_order_shipment( $this );
	            $shipment->sync();

                $this->validate_shipment_item_quantities( $shipment->get_id() );
            }
        }

        if ( $args['save'] ) {
            $this->save();
        }
    }

    public function validate_shipment_item_quantities( $shipment_id = false ) {
        $shipment    = $shipment_id ? $this->get_shipment( $shipment_id ) : false;
        $shipments   = ( $shipment_id && $shipment ) ? array( $shipment ) : $this->get_simple_shipments();
        $order_items = $this->get_shippable_items();

        foreach( $shipments as $shipment ) {

            if ( ! is_a( $shipment, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
                continue;
            }

            // Do only check draft shipments
            if ( $shipment->is_editable() ) {
                foreach( $shipment->get_items() as $item ) {

                    // Order item does not exist
                    if ( ! isset( $order_items[ $item->get_order_item_id() ] ) ) {

	                    /**
	                     * Filter to decide whether to keep non-existing OrderItems within
	                     * the Shipment while validating or not.
	                     *
	                     * @param boolean                                      $keep Whether to keep non-existing OrderItems or not.
	                     * @param ShipmentItem $item The shipment item object.
	                     * @param Shipment $shipment The shipment object.
	                     *
	                     * @since 3.0.0
	                     * @package Vendidero/Germanized/Shipments
	                     */
                        if ( ! apply_filters( 'woocommerce_gzd_shipment_order_keep_non_order_item', false, $item, $shipment ) ) {
                            $shipment->remove_item( $item->get_id() );
                        }

                        continue;
                    }

                    $order_item = $order_items[ $item->get_order_item_id() ];
                    $quantity   = $this->get_item_quantity_left_for_shipping( $order_item, array(
                        'shipment_id'              => $shipment->get_id(),
                        'exclude_current_shipment' => true,
                    ) );

                    if ( $quantity <= 0 ) {
                        $shipment->remove_item( $item->get_id() );
                    } else {
                        $new_quantity = $item->get_quantity();

                        if ( $item->get_quantity() > $quantity ) {
                            $new_quantity = $quantity;
                        }

                        $item->sync( array( 'quantity' => $new_quantity ) );
                    }
                }

                if ( empty( $shipment->get_items() ) ) {
                    $this->remove_shipment( $shipment->get_id() );
                }
            }
        }
    }

    /**
     * @return Shipment[] Shipments
     */
    public function get_shipments() {

        if ( is_null( $this->shipments ) ) {

            $this->shipments = wc_gzd_get_shipments( array(
                'order_id' => $this->get_order()->get_id(),
                'limit'    => -1,
                'orderby'  => 'date_created',
                'type'     => array( 'simple', 'return' ),
                'order'    => 'ASC'
            ) );
        }

        $shipments = (array) $this->shipments;

        return $shipments;
    }

	/**
	 * @return SimpleShipment[]
	 */
    public function get_simple_shipments() {
    	$simple = array();

		foreach( $this->get_shipments() as $shipment ) {
			if ( 'simple' === $shipment->get_type() ) {
				$simple[] = $shipment;
			}
		}

		return $simple;
    }

	/**
	 * @return ReturnShipment[]
	 */
	public function get_return_shipments( $parent_id = false ) {
		$returns = array();

		foreach( $this->get_shipments() as $shipment ) {
			if ( 'return' === $shipment->get_type() ) {

				if ( $parent_id && $shipment->get_parent_id() !== (int) $parent_id ) {
					continue;
				}

				$returns[] = $shipment;
			}
		}

		return $returns;
	}

    public function add_shipment( &$shipment ) {
        $shipments = $this->get_shipments();

        $this->shipments[] = $shipment;
    }

    public function remove_shipment( $shipment_id ) {
        $shipments = $this->get_shipments();

        foreach( $this->shipments as $key => $shipment ) {

            if ( $shipment->get_id() === (int) $shipment_id ) {
                $this->shipments_to_delete[] = $shipment;

                unset( $this->shipments[ $key ] );
                break;
            }
        }
    }

	/**
	 * @param $shipment_id
	 *
	 * @return bool|SimpleShipment|ReturnShipment
	 */
    public function get_shipment( $shipment_id ) {
        $shipments = $this->get_shipments();

        foreach( $shipments as $shipment ) {

            if ( $shipment->get_id() === (int) $shipment_id ) {
                return $shipment;
            }
        }

        return false;
    }

    /**
     * @param WC_Order_Item $order_item
     */
    public function get_item_quantity_left_for_shipping( $order_item, $args = array() ) {
        $quantity_left = 0;
        $args          = wp_parse_args( $args, array(
            'sent_only'                => false,
            'shipment_id'              => 0,
            'exclude_current_shipment' => false,
        ) );

        if ( is_numeric( $order_item ) ) {
            $order_item = $this->get_order()->get_item( $order_item );
        }

        if ( $order_item ) {
            $quantity_left = $this->get_shippable_item_quantity( $order_item );

            foreach( $this->get_simple_shipments() as $shipment ) {

                if ( $args['sent_only'] && ! $shipment->has_status( wc_gzd_get_shipment_sent_statuses() ) ) {
                    continue;
                }

                if ( $args['exclude_current_shipment'] && $args['shipment_id'] > 0 && ( $shipment->get_id() === $args['shipment_id'] ) ) {
                    continue;
                }

                if ( $item = $shipment->get_item_by_order_item_id( $order_item->get_id() ) ) {
                    $quantity_left -= $item->get_quantity();
                }
            }
        }

        if ( $quantity_left < 0 ) {
            $quantity_left = 0;
        }

	    /**
	     * Filter to adjust the quantity left for shipment of a specific order item.
	     *
	     * @param integer                                      $quantity_left The quantity left for shipment.
	     * @param WC_Order_Item                                $order_item The order item object.
	     * @param Order $this The shipment order object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_shipment_order_item_quantity_left_for_shipping', $quantity_left, $order_item, $this );
    }

    /**
     * @param bool|Vendidero\Germanized\Shipments\Shipment $shipment
     * @return array
     */
    public function get_available_items_for_shipment( $args = array() ) {
        $args           = wp_parse_args( $args, array(
            'disable_duplicates'       => false,
            'shipment_id'              => 0,
            'sent_only'                => false,
            'exclude_current_shipment' => false,
        ) );

        $items          = array();
        $shipment       = $args['shipment_id'] ? $this->get_shipment( $args['shipment_id'] ) : false;

        foreach( $this->get_shippable_items() as $item ) {
            $quantity_left = $this->get_item_quantity_left_for_shipping( $item, $args );

            if ( $shipment ) {
                if ( $args['disable_duplicates'] && $shipment->get_item_by_order_item_id( $item->get_id() ) ) {
                    continue;
                }
            }

            if ( $quantity_left > 0 ) {
                $items[ $item->get_id() ] = array(
                    'name'         => $item->get_name(),
                    'max_quantity' => $quantity_left,
                );
            }
        }

        return $items;
    }

    public function item_needs_shipping( $order_item, $args = array() ) {
        $args = wp_parse_args( $args, array(
            'sent_only' => false,
        ) );

        $needs_shipping = false;

        if ( $this->get_item_quantity_left_for_shipping( $order_item, $args ) > 0 ) {
            $needs_shipping = true;
        }

	    /**
	     * Filter to decide whether an order item needs shipping or not.
	     *
	     * @param boolean                               $needs_shipping Whether the item needs shipping or not.
	     * @param WC_Order_Item                        $item The order item object.
	     * @param array                                 $args Additional arguments to be considered.
	     * @param Order $order The shipment order object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_shipment_order_item_needs_shipping', $needs_shipping, $order_item, $args, $this );
    }

    /**
     * Returns items that are ready for shipping (defaults to non-virtual line items).
     *
     * @return WC_Order_Item[] Shippable items.
     */
    public function get_shippable_items() {
        $items = $this->get_order()->get_items( 'line_item' );

        foreach( $items as $key => $item ) {
            $product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : false;

            if ( $product ) {

                if ( $product->is_virtual() || $this->get_shippable_item_quantity( $item ) <= 0 ) {
                    unset( $items[ $key ] );
                }
            }
        }

        $items = array_filter( $items );

	    /**
	     * Filter to adjust shippable order items for a specific order.
	     * By default excludes virtual items.
	     *
	     * @param WC_Order_Item[]                       $items Array containing shippable order items.
	     * @param WC_Order                              $order The order object.
	     * @param Order $order The shipment order object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_shipment_order_shippable_items', $items, $this->get_order(), $this );
    }

    public function get_shippable_item_quantity( $order_item ) {
        $refunded_qty = $this->get_order()->get_qty_refunded_for_item( $order_item->get_id() );

        // Make sure we are safe to substract quantity for logical purposes
        if ( $refunded_qty < 0 ) {
            $refunded_qty *= -1;
        }

        $quantity_left = $order_item->get_quantity() - $refunded_qty;

	    /**
	     * Filter that allows adjusting the quantity left for shipping or a specific order item.
	     *
	     * @param integer                               $quantity_left The quantity left for shipping.
	     * @param WC_Order_Item                        $item The order item object.
	     * @param Order $order The shipment order object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_shipment_order_item_shippable_quantity', $quantity_left, $order_item, $this );
    }

    public function get_shippable_item_count() {
        $count = 0;

        foreach( $this->get_shippable_items() as $item ) {
            $count += $this->get_shippable_item_quantity( $item );
        }

	    /**
	     * Filters the total number of shippable items available in an order.
	     *
	     * @param integer                               $count The total number of items.
	     * @param Order $order The shipment order object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_shipment_order_shippable_item_count', $count, $this );
    }

    /**
     * Checks whether the order needs shipping or not by checking quantity
     * for every line item.
     *
     * @param bool $sent_only Whether to only include shipments treated as sent or not.
     *
     * @return bool Whether the order needs shipping or not.
     */
    public function needs_shipping( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'sent_only' => false
        ) );

        $order_items    = $this->get_shippable_items();
        $needs_shipping = false;

        foreach( $order_items as $order_item ) {

            if ( $this->item_needs_shipping( $order_item, $args ) ) {
                $needs_shipping = true;
                break;
            }
        }

	    /**
	     * Filter to decide whether an order needs shipping or not.
	     *
	     * @param boolean                               $needs_shipping Whether the order needs shipping or not.
	     * @param WC_Order                              $order The order object.
	     * @param Order $order The shipment order object.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_shipment_order_needs_shipping', $needs_shipping, $this->get_order(), $this );
    }

    public function save() {
        if ( ! empty( $this->shipments_to_delete ) ) {

            foreach( $this->shipments_to_delete as $shipment ) {
                $shipment->delete( true );
            }
        }

        foreach( $this->shipments as $shipment ) {
            $shipment->save();
        }
    }

    /**
     * Call child methods if the method does not exist.
     *
     * @param $method
     * @param $args
     *
     * @return bool|mixed
     */
    public function __call( $method, $args ) {

        if ( method_exists( $this->order, $method ) ) {
            return call_user_func_array( array( $this->order, $method ), $args );
        }

        return false;
    }
}
