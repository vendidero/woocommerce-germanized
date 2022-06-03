<?php
/**
 * Return shipment. Counterparts to simple shipments. Return
 * shipments serve to handle retoure/return shipments from customers to the shop owner.
 *
 * @package Vendidero/Germanized/Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments;

use Exception;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Return Shipment Class.
 */
class ReturnShipment extends Shipment {

	/**
	 * The corresponding order object.
	 *
	 * @var null|Order
	 */
	private $order_shipment = null;

	/**
	 * The corresponding order object.
	 *
	 * @var null|WC_Order
	 */
	private $order = null;

	protected $extra_data = array(
		'order_id'              => 0,
		'is_customer_requested' => false,
		'sender_address'        => array(),
	);

	/**
	 * Returns the shipment type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'return';
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
	 * Returns whether the current return was requested by a customer or not.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return boolean
	 */
	public function get_is_customer_requested( $context = 'view' ) {
		return $this->get_prop( 'is_customer_requested', $context );
	}

	public function is_customer_requested() {
		return $this->get_is_customer_requested();
	}

	public function confirm_customer_request() {
		if ( $this->is_customer_requested() && $this->has_status( 'requested' ) ) {

			$this->set_status( 'processing' );

			if ( $this->save() ) {
				/**
				 * Action that fires after a return request has been confirmed to the customer.
				 *
				 * @param integer        $shipment_id The return shipment id.
				 * @param ReturnShipment $shipment The return shipment object.
				 *
				 * @since 3.1.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( 'woocommerce_gzd_return_shipment_customer_confirmed', $this->get_id(), $this );

				return true;
			} else {
				return false;
			}
		}

		return false;
	}

	/**
	 * Returns the address of the sender e.g. customer.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_sender_address( $context = 'view' ) {
		return $this->get_prop( 'sender_address', $context );
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
	 * Set if the current return was requested by the customer or not.
	 *
	 * @param string $is_requested Whether or not it is requested by the customer.
	 */
	public function set_is_customer_requested( $is_requested ) {
		$this->set_prop( 'is_customer_requested', wc_string_to_bool( $is_requested ) );
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
	 * Returns shipment order.
	 *
	 * @return Order|null The order shipment.
	 */
	public function get_order_shipment() {
		if ( is_null( $this->order_shipment ) ) {
			$order                = $this->get_order();
			$this->order_shipment = ( $order ? wc_gzd_get_shipment_order( $order ) : false );
		}

		return $this->order_shipment;
	}

	/**
	 * Returns the shippable item count.
	 *
	 * @return int
	 */
	public function get_shippable_item_count() {

		if ( $order_shipment = $this->get_order_shipment() ) {
			return $order_shipment->get_returnable_item_count();
		}

		return 0;
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
	 * Returns available shipment methods by checking the corresponding order.
	 *
	 * @return string[]
	 */
	public function get_available_shipping_methods() {
		$methods                                 = array();
		$methods[ $this->get_shipping_method() ] = '';

		return $methods;
	}

	/**
	 * Returns a sender address prop.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
	protected function get_sender_address_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( isset( $this->changes['sender_address'][ $prop ] ) || isset( $this->data['sender_address'][ $prop ] ) ) {
			$value = isset( $this->changes['sender_address'][ $prop ] ) ? $this->changes['sender_address'][ $prop ] : $this->data['sender_address'][ $prop ];

			if ( 'view' === $context ) {
				/**
				 * Filter to adjust a Shipment's return sender address property e.g. first_name.
				 *
				 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
				 * unique hook for a shipment type. `$prop` refers to the actual address property e.g. first_name.
				 *
				 * Example hook name: woocommerce_gzd_return_shipment_get_sender_address_first_name
				 *
				 * @param string                                   $value The address property value.
				 * @param Shipment $this The shipment object.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				$value = apply_filters( "{$this->get_hook_prefix()}sender_address_{$prop}", $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Set sender address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_sender_address( $address ) {
		$this->set_prop( 'sender_address', empty( $address ) ? array() : (array) $address );
	}

	/**
	 * Syncs the return shipment with the corresponding parent shipment.
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

			$return_address = wc_gzd_get_shipment_return_address( $order_shipment );
			$order          = $order_shipment->get_order();

			/**
			 * Make sure that manually adjusted providers are not overridden by syncing.
			 */
			$default_provider    = $order_shipment->get_default_return_shipping_provider();
			$provider            = $this->get_shipping_provider( 'edit' );
			$sender_address_data = array_merge(
				( $order->has_shipping_address() ? $order->get_address( 'shipping' ) : $order->get_address( 'billing' ) ),
				array(
					'email' => $order->get_billing_email(),
					'phone' => $order->get_billing_phone(),
				)
			);

			// Prefer shipping phone in case exists
			if ( is_callable( array( $order, 'get_shipping_phone' ) ) && $order->get_shipping_phone() ) {
				$sender_address_data['phone'] = $order->get_shipping_phone();
			}

			$args = wp_parse_args(
				$args,
				array(
					'order_id'          => $order->get_id(),
					'country'           => $return_address['country'],
					'shipping_method'   => wc_gzd_get_shipment_order_shipping_method_id( $order ),
					'shipping_provider' => ( ! empty( $provider ) ) ? $provider : $default_provider,
					'address'           => $return_address,
					'sender_address'    => $sender_address_data,
					'weight'            => $this->get_weight( 'edit' ),
					'length'            => $this->get_length( 'edit' ),
					'width'             => $this->get_width( 'edit' ),
					'height'            => $this->get_height( 'edit' ),
				)
			);

			/**
			 * Filter to allow adjusting the return shipment props synced from the corresponding order.
			 *
			 * @param mixed          $args The properties in key => value pairs.
			 * @param ReturnShipment $shipment The shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$args = apply_filters( 'woocommerce_gzd_return_shipment_sync_props', $args, $this, $order_shipment );

			$this->set_props( $args );

			/**
			 * Action that fires after a return shipment has been synced. Syncing is used to
			 * keep the shipment in sync with the corresponding parent shipment.
			 *
			 * @param ReturnShipment $shipment The return shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 * @param array          $args Array containing properties in key => value pairs to be updated.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_return_shipment_synced', $this, $order_shipment, $args );

		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Keeps items in sync with the parent shipment items.
	 * Limits quantities and removes non-existent items.
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

			$available_items = $order_shipment->get_available_items_for_return(
				array(
					'shipment_id'              => $this->get_id(),
					'exclude_current_shipment' => true,
				)
			);

			foreach ( $available_items as $order_item_id => $item_data ) {

				if ( $item = $order_shipment->get_simple_shipment_item( $order_item_id ) ) {
					$quantity           = $item_data['max_quantity'];
					$return_reason_code = '';

					if ( ! empty( $args['items'] ) ) {
						if ( isset( $args['items'][ $order_item_id ] ) ) {

							if ( is_array( $args['items'][ $order_item_id ] ) ) {
								$default_item_data = wp_parse_args(
									$args['items'][ $order_item_id ],
									array(
										'quantity' => 1,
										'return_reason_code' => '',
									)
								);
							} else {
								$default_item_data = array(
									'quantity'           => absint( $args['items'][ $order_item_id ] ),
									'return_reason_code' => '',
								);
							}

							$new_quantity       = $default_item_data['quantity'];
							$return_reason_code = $default_item_data['return_reason_code'];

							if ( $new_quantity < $quantity ) {
								$quantity = $new_quantity;
							}
						} else {
							continue;
						}
					}

					if ( $quantity <= 0 ) {
						continue;
					}

					$sync_data = array(
						'quantity' => $quantity,
					);

					if ( ! empty( $return_reason_code ) ) {
						$sync_data['return_reason_code'] = $return_reason_code;
					}

					if ( ! $shipment_item = $this->get_item_by_order_item_id( $order_item_id ) ) {
						$shipment_item = wc_gzd_create_return_shipment_item( $this, $item, $sync_data );

						$this->add_item( $shipment_item );
					} else {
						$shipment_item->sync( $sync_data );
					}
				}
			}

			foreach ( $this->get_items() as $item ) {

				// Remove non-existent items
				if ( ! $shipment_item = $order_shipment->get_simple_shipment_item( $item->get_order_item_id() ) ) {
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
			do_action( 'woocommerce_gzd_return_shipment_items_synced', $this, $order_shipment, $args );

		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns whether the Shipment needs additional items or not.
	 *
	 * @param bool|integer[] $available_items
	 *
	 * @return bool
	 */
	public function needs_items( $available_items = false ) {

		if ( ! $available_items && ( $order_shipment = $this->get_order_shipment() ) ) {
			$available_items = array_keys( $order_shipment->get_available_items_for_return() );
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
