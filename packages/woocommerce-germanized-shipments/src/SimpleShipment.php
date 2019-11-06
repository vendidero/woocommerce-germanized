<?php
/**
 * Regular shipment
 *
 * @package Vendidero/Germanized/Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_Order;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
class SimpleShipment extends Shipment {

	/**
	 * The corresponding order object.
	 *
	 * @var null|WC_Order
	 */
	private $order = null;

	/**
	 * The corresponding order object.
	 *
	 * @var null|Order
	 */
	private $order_shipment = null;

	private $force_order_shipment_usage = false;

	protected $extra_data = array(
		'est_delivery_date'     => null,
		'order_id'              => 0,
	);

	/**
	 * Returns the shipment type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'simple';
	}

	/**
	 * Return the date this shipment is estimated to be delivered.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_est_delivery_date( $context = 'view' ) {
		return $this->get_prop( 'est_delivery_date', $context );
	}

	/**
	 * Set the date this shipment will be delivered.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_est_delivery_date( $date = null ) {
		$this->set_date_prop( 'est_delivery_date', $date );
	}

	/**
	 * Returns the order id belonging to the shipment.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Set shipment order id.
	 *
	 * @param string $order_id The order id.
	 */
	public function set_order_id( $order_id ) {
		// Reset order object
		$this->order = null;

		$this->set_prop( 'order_id', absint( $order_id ) );
	}

	/**
	 * Set shipment order.
	 *
	 * @param Order $order_shipment The order shipment.
	 */
	public function set_order_shipment( &$order_shipment ) {
		$this->order_shipment             = $order_shipment;
		/** Little hack to determine whether to work with the order shipment instance for retrieving return instances */
		$this->force_order_shipment_usage = true;
	}

	/**
	 * Tries to fetch the order for the current shipment.
	 *
	 * @return bool|WC_Order|null
	 */
	public function get_order() {
		if ( is_null( $this->order ) ) {
			$this->order = ( $this->get_order_id() > 0 ? wc_get_order( $this->get_order_id() ) : false );
		}

		return $this->order;
	}

	/**
	 * Returns the order shipment instance. Loads from DB if not yet exists.
	 *
	 * @return bool|Order
	 */
	public function get_order_shipment() {
		if ( is_null( $this->order_shipment ) ) {
			$order                = $this->get_order();
			$this->order_shipment = ( $order ? wc_gzd_get_shipment_order( $order ) : false );
		}

		return $this->order_shipment;
	}

	/**
	 * Sync the shipment with it's corresponding order.
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function sync( $args = array() ) {
		try {

			if ( ! $order_shipment = $this->get_order_shipment() ) {
				throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
			}

			/**
			 * Hotfix WCML infinite loop
			 *
			 */
			if ( function_exists( 'wc_gzd_remove_class_filter' ) ) {
				wc_gzd_remove_class_filter( 'woocommerce_order_get_items', 'WCML_Orders', 'woocommerce_order_get_items', 10 );
			}

			$order = $order_shipment->get_order();

			$args = wp_parse_args( $args, array(
				'order_id'        => $order->get_id(),
				'country'         => $order->get_shipping_country(),
				'shipping_method' => wc_gzd_get_shipment_order_shipping_method_id( $order ),
				'address'         => array_merge( $order->get_address( 'shipping' ), array( 'email' => $order->get_billing_email(), 'phone' => $order->get_billing_phone() ) ),
				'weight'          => $this->get_weight( 'edit' ),
				'length'          => $this->get_length( 'edit' ),
				'width'           => $this->get_width( 'edit' ),
				'height'          => $this->get_height( 'edit' ),
			) );

			$this->set_props( $args );

			/**
			 * Action that fires after a shipment has been synced. Syncing is used to
			 * keep the shipment in sync with the corresponding order.
			 *
			 * @param SimpleShipment $shipment The shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 * @param array          $args Array containing properties in key => value pairs to be updated.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_shipment_synced', $this, $order_shipment, $args );

		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Sync items with the corresponding order items.
	 * Limits quantities and removes non-existing items.
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function sync_items( $args = array() ) {
		try {

			if ( ! $order_shipment = $this->get_order_shipment() ) {
				throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
			}

			$order = $order_shipment->get_order();

			$args = wp_parse_args( $args, array(
				'items' => array(),
			) );

			$available_items = $order_shipment->get_available_items_for_shipment( array(
				'shipment_id'              => $this->get_id(),
				'exclude_current_shipment' => true,
			) );

			foreach( $available_items as $item_id => $item_data ) {

				if ( $order_item = $order->get_item( $item_id ) ) {
					$quantity = $item_data['max_quantity'];

					if ( ! empty( $args['items'] ) ) {
						if ( isset( $args['items'][ $item_id ] ) ) {
							$new_quantity = absint( $args['items'][ $item_id ] );

							if ( $new_quantity < $quantity ) {
								$quantity = $new_quantity;
							}
						} else {
							continue;
						}
					}

					if ( ! $shipment_item = $this->get_item_by_order_item_id( $item_id ) ) {
						$shipment_item = wc_gzd_create_shipment_item( $this, $order_item, array( 'quantity' => $quantity ) );

						$this->add_item( $shipment_item );
					} else {
						$shipment_item->sync( array( 'quantity' => $quantity ) );
					}
				}
			}

			foreach( $this->get_items() as $item ) {

				// Remove non-existent items
				if( ! $order_item = $order->get_item( $item->get_order_item_id() ) ) {
					$this->remove_item( $item->get_id() );
				}
			}

			/**
			 * Action that fires after items of a shipment have been synced.
			 *
			 * @param SimpleShipment $shipment The shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 * @param array          $args Array containing additional data e.g. items.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_shipment_items_synced', $this, $order_shipment, $args );

		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns available shipment methods by checking the corresponding order.
	 *
	 * @return string[]
	 */
	public function get_available_shipping_methods() {
		$methods = array();

		if ( $order = $this->get_order() ) {
			$items = $order->get_shipping_methods();

			foreach( $items as $item ) {
				$methods[ $item->get_method_id() . ':' . $item->get_instance_id() ] = $item->get_name();
			}
		}

		return $methods;
	}

	public function needs_shipping_provider_select() {
		$shipping_method = $this->get_shipping_method();

		if ( ! empty( $shipping_method ) ) {
			$expl = explode( ':', $shipping_method );

			// If no instance id is availabe - show selection
			if ( sizeof( $expl ) === 2 && empty( $expl[1] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns available items for return.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_available_items_for_return( $args = array() ) {
		$args     = wp_parse_args( $args, array(
			'disable_duplicates'       => false,
			'shipment_id'              => 0,
			'exclude_current_shipment' => false,
		) );

		$items    = array();
		$shipment = false;

		if ( $order_shipment = $this->get_order_shipment() ) {
			$shipment = $args['shipment_id'] ? $order_shipment->get_shipment( $args['shipment_id'] ) : false;
		}

		foreach( $this->get_items() as $item ) {

			$quantity_left = $this->get_item_quantity_left_for_return( $item->get_id(), $args );

			if ( $shipment ) {
				if ( $args['disable_duplicates'] && $shipment->get_item_by_item_parent_id( $item->get_id() ) ) {
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

	/**
	 * Returns the quantity left for return of a certain item.
	 *
	 * @param int $item_id
	 * @param array $args
	 *
	 * @return int
	 */
	public function get_item_quantity_left_for_return( $item_id, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'exclude_current_shipment' => false,
			'shipment_id'              => 0,
		) );

		$returns       = $this->get_returns();
		$quantity_left = 0;

		if ( $item = $this->get_item( $item_id ) ) {
			$quantity_left = $item->get_quantity();

			foreach( $returns as $return ) {

				if ( $args['exclude_current_shipment'] && $args['shipment_id'] > 0 && $args['shipment_id'] === $return->get_id() ) {
					continue;
				}

				if ( $return_item = $return->get_item_by_item_parent_id( $item_id ) ) {
					$quantity_left -= $return_item->get_quantity();
				}
			}

			if ( $quantity_left < 0 ) {
				$quantity_left = 0;
			}
		}

		return $quantity_left;
	}

	/**
	 * Returns the number of items available for shipment.
	 *
	 * @return int|mixed|void
	 */
	public function get_shippable_item_count() {
		if ( $order_shipment = $this->get_order_shipment() ) {
			return $order_shipment->get_shippable_item_count();
		}

		return 0;
	}

	/**
	 * Returns return shipments linked to that shipment.
	 *
	 * @return ReturnShipment[]
	 */
	public function get_returns() {
		if ( $this->force_order_shipment_usage && ( $order = $this->get_order_shipment() ) ) {
			return $order->get_return_shipments( $this->get_id() );
		} else {
			return wc_gzd_get_shipments( array(
				'parent_id' => $this->get_id(),
				'type'      => 'return',
			) );
		}
	}

	/**
	 * Checks whether the current shipment is fully returned, e.g. if
	 * items exist which are not yet linked to a return.
	 *
	 * @return bool
	 */
	public function has_complete_return() {
		$items = $this->get_available_items_for_return();

		return empty( $items ) ? true : false;
	}

	/**
	 * Returns whether the Shipment needs additional items or not.
	 *
	 * @param bool|integer[] $available_items
	 *
	 * @return bool
	 */
	public function needs_items( $available_items = false ) {

		if ( ! $available_items && ( $order = wc_gzd_get_shipment_order( $this->get_order() ) ) ) {
			$available_items = array_keys( $order->get_available_items_for_shipment() );
		}

		return ( $this->is_editable() && ! $this->contains_order_item( $available_items ) );
	}

	/**
	 * Checks if the current shipment is returnable or not.
	 *
	 * @return bool
	 */
	public function is_returnable() {

		if ( $this->has_status( array( 'shipped', 'delivered' ) ) && ! $this->has_complete_return() ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the edit shipment URL.
	 *
	 * @return mixed|string|void
	 */
	public function get_edit_shipment_url() {
		/**
		 * Filter to adjust the edit Shipment admin URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_gzd_shipment_get_edit_url
		 *
		 * @param string   $url  The URL.
		 * @param Shipment $this The shipment object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "{$this->get_hook_prefix()}edit_url", get_admin_url( null, 'post.php?post=' . $this->get_order_id() . '&action=edit&shipment_id=' . $this->get_id() ), $this );
	}
}
