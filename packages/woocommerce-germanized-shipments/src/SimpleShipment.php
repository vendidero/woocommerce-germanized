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

	protected $extra_data = array(
		'order_id' => 0,
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
		$this->order_shipment = $order_shipment;
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

			/**
			 * Make sure that manually adjusted providers are not overridden by syncing.
			 */
			$default_provider_instance = wc_gzd_get_order_shipping_provider( $order );
			$default_provider          = $default_provider_instance ? $default_provider_instance->get_name() : '';
			$provider                  = $this->get_shipping_provider( 'edit' );
			$address_data              = array_merge(
				( $order->has_shipping_address() ? $order->get_address( 'shipping' ) : $order->get_address( 'billing' ) ),
				array(
					'email' => $order->get_billing_email(),
					'phone' => $order->get_billing_phone(),
				)
			);

			// Prefer shipping phone in case exists
			if ( is_callable( array( $order, 'get_shipping_phone' ) ) && $order->get_shipping_phone() ) {
				$address_data['phone'] = $order->get_shipping_phone();
			}

			/**
			 * Fix to make sure that we are not syncing formatted customer titles (e.g. Herr)
			 * which prevents shipment addresses from being translated.
			 */
			if ( isset( $address_data['title'] ) && ! empty( $address_data['title'] ) ) {
				if ( $title = $order->get_meta( '_shipping_title', true ) ) {
					$address_data['title'] = $title;
				}
			}

			/**
			 * Force the country to have a max length of 2.
			 * https://github.com/woocommerce/woocommerce/issues/27521
			 */
			$country      = substr( strtoupper( ( $order->has_shipping_address() ? $order->get_shipping_country() : $order->get_billing_country() ) ), 0, 2 );
			$packaging_id = $this->get_packaging_id( 'edit' );

			$dimensions = array(
				'width'  => $this->get_width( 'edit' ),
				'length' => $this->get_length( 'edit' ),
				'height' => $this->get_height( 'edit' ),
			);

			$args = wp_parse_args(
				$args,
				array(
					'order_id'          => $order->get_id(),
					'shipping_method'   => wc_gzd_get_shipment_order_shipping_method_id( $order ),
					'shipping_provider' => ( ! empty( $provider ) ) ? $provider : $default_provider,
					'packaging_id'      => $this->get_packaging_id( 'edit' ),
					'address'           => $address_data,
					'country'           => $country,
					'weight'            => $this->get_weight( 'edit' ),
					'packaging_weight'  => $this->get_packaging_weight( 'edit' ),
					'length'            => $dimensions['length'],
					'width'             => $dimensions['width'],
					'height'            => $dimensions['height'],
					'additional_total'  => $order_shipment->calculate_shipment_additional_total( $this ),
				)
			);

			/**
			 * Filter to allow adjusting the shipment props synced from the corresponding order.
			 *
			 * @param mixed          $args The properties in key => value pairs.
			 * @param SimpleShipment $shipment The shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$args = apply_filters( 'woocommerce_gzd_shipment_sync_props', $args, $this, $order_shipment );

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

			$args = wp_parse_args(
				$args,
				array(
					'items' => array(),
				)
			);

			$available_items = $order_shipment->get_available_items_for_shipment(
				array(
					'shipment_id'              => $this->get_id(),
					'exclude_current_shipment' => true,
				)
			);

			foreach ( $available_items as $item_id => $item_data ) {
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

			foreach ( $this->get_items() as $item ) {
				// Remove non-existent items
				if ( ! $order_item = $order->get_item( $item->get_order_item_id() ) ) {
					$this->remove_item( $item->get_id() );
				}
			}

			// Sync packaging
			$this->sync_packaging();

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

			foreach ( $items as $item ) {
				$methods[ $item->get_method_id() . ':' . $item->get_instance_id() ] = $item->get_name();
			}
		}

		return $methods;
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
