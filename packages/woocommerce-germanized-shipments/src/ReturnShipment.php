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
	 * The corresponding parent shipment.
	 *
	 * @var null|SimpleShipment
	 */
	private $parent = null;

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
		'parent_id'      => 0,
		'order_id'       => 0,
		'sender_address' => array(),
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
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_gzd_return_shipment_get_';
	}

	/**
	 * Returns the parent id belonging to the shipment.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
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
	 * Returns the address of the sender e.g. customer.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_sender_address( $context = 'view' ) {
		return $this->get_prop( 'sender_address', $context );
	}

	/**
	 * Set shipment parent id.
	 *
	 * @param string $parent_id The parent id.
	 */
	public function set_parent_id( $parent_id ) {
		// Reset parent object
		$this->parent = null;

		$this->set_prop( 'parent_id', absint( $parent_id ) );
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
	 * Returns shipment order.
	 *
	 * @return Order|null The order shipment.
	 */
	public function get_order_shipment() {
		return $this->order_shipment;
	}

	/**
	 * Tries to fetch the parent for the current shipment.
	 *
	 * @return bool|SimpleShipment|null
	 */
	public function get_parent() {
		if ( is_null( $this->parent ) ) {

			if ( $order_shipment = $this->get_order_shipment() ) {
				$this->parent = ( $this->get_parent_id() > 0 ? $order_shipment->get_shipment( $this->get_parent_id() ) : false );
			} else {
				$this->parent = ( $this->get_parent_id() > 0 ? wc_gzd_get_shipment( $this->get_parent_id() ) : false );
			}
		}

		return $this->parent;
	}

	/**
	 * Returns the shippable item count.
	 *
	 * @return int
	 */
	public function get_shippable_item_count() {
		if ( $parent = $this->get_parent() ) {
			return $parent->get_item_count();
		}

		return 0;
	}

	/**
	 * Set parent instance.
	 *
	 * @param $shipment
	 */
	public function set_parent( $shipment ) {
		$this->parent = $shipment;
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
		$methods = array();
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
	 * Returns the formatted sender address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
	public function get_formatted_sender_address( $empty_content = '' ) {
		$address = WC()->countries->get_formatted_address( $this->get_sender_address() );

		return $address ? $address : $empty_content;
	}

	/**
	 * Returns the sender address phone number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_phone( $context = 'view' ) {
		return $this->get_sender_address_prop( 'phone', $context );
	}

	/**
	 * Returns the sender address email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_email( $context = 'view' ) {
		return $this->get_sender_address_prop( 'email', $context );
	}

	/**
	 * Returns the sender address first line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_address_1( $context = 'view' ) {
		return $this->get_sender_address_prop( 'address_1', $context );
	}

	/**
	 * Returns the sender address second line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_address_2( $context = 'view' ) {
		return $this->get_sender_address_prop( 'address_2', $context );
	}

	/**
	 * Returns the sender address street number by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
	public function get_sender_address_street_number( $type = 'address_1' ) {
		$split = wc_gzd_split_shipment_street( $this->{"get_sender_$type"}() );

		return $split['number'];
	}

	/**
	 * Returns the sender address street without number by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
	public function get_sender_address_street( $type = 'address_1' ) {
		$split = wc_gzd_split_shipment_street( $this->{"get_sender_$type"}() );

		return $split['street'];
	}

	/**
	 * Returns the sender address street addition by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
	public function get_sender_address_street_addition( $type = 'address_1' ) {
		$split = wc_gzd_split_shipment_street( $this->{"get_sender_$type"}() );

		return $split['addition'];
	}

	/**
	 * Returns the sender address company.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_company( $context = 'view' ) {
		return $this->get_sender_address_prop( 'company', $context );
	}

	/**
	 * Returns the sender address first name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_first_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'first_name', $context );
	}

	/**
	 * Returns the shipment address last name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_last_name( $context = 'view' ) {
		return $this->get_sender_address_prop( 'last_name', $context );
	}

	/**
	 * Returns the sender address formatted full name.
	 *
	 * @return string
	 */
	public function get_formatted_sender_full_name() {
		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce-germanized' ), $this->get_sender_first_name(), $this->get_sender_last_name() );
	}

	/**
	 * Returns the sender address postcode.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_postcode( $context = 'view' ) {
		return $this->get_sender_address_prop( 'postcode', $context );
	}

	/**
	 * Returns the sender address city.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_city( $context = 'view' ) {
		return $this->get_sender_address_prop( 'city', $context );
	}

	/**
	 * Returns the sender address state.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_state( $context = 'view' ) {
		return $this->get_sender_address_prop( 'state', $context );
	}

	/**
	 * Returns the sender address country.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_country( $context = 'view' ) {
		return $this->get_sender_address_prop( 'country', $context ) ? $this->get_sender_address_prop( 'country', $context ) : '';
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

			if ( ! $parent_shipment = $this->get_parent() ) {
				throw new Exception( _x( 'Invalid shipment', 'shipments', 'woocommerce-germanized' ) );
			}

			$return_address = wc_gzd_get_shipment_return_address( $parent_shipment );

			$args = wp_parse_args( $args, array(
				'order_id'          => $parent_shipment->get_order_id(),
				'country'           => $return_address['country'],
				'shipping_method'   => $parent_shipment->get_shipping_method(),
				'shipping_provider' => $parent_shipment->get_shipping_provider(),
				'address'           => $return_address,
				'sender_address'    => $parent_shipment->get_address(),
				'weight'            => $this->get_weight( 'edit' ),
				'length'            => $this->get_length( 'edit' ),
				'width'             => $this->get_width( 'edit' ),
				'height'            => $this->get_height( 'edit' ),
			) );

			$this->set_props( $args );
			$this->set_parent_id( $parent_shipment->get_id() );

			/**
			 * Action that fires after a return shipment has been synced. Syncing is used to
			 * keep the shipment in sync with the corresponding parent shipment.
			 *
			 * @param ReturnShipment $shipment The return shipment object.
			 * @param SimpleShipment $parent_shipment The parent shipment object.
			 * @param array          $args Array containing properties in key => value pairs to be updated.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_return_shipment_synced', $this, $parent_shipment, $args );

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

			if ( ! $parent = $this->get_parent() ) {
				throw new Exception( _x( 'Invalid shipment', 'shipments', 'woocommerce-germanized' ) );
			}

			$args = wp_parse_args( $args, array(
				'items' => array(),
			) );

			$available_items = $parent->get_available_items_for_return( array(
				'shipment_id'              => $this->get_id(),
				'exclude_current_shipment' => true,
			) );

			foreach( $available_items as $item_id => $item_data ) {

				if ( $parent_item = $parent->get_item( $item_id ) ) {
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

					if ( ! $shipment_item = $this->get_item_by_item_parent_id( $item_id ) ) {
						$shipment_item = wc_gzd_create_return_shipment_item( $this, $parent_item, array( 'quantity' => $quantity ) );

						$this->add_item( $shipment_item );
					} else {
						$shipment_item->sync( array( 'quantity' => $quantity ) );
					}
				}
			}

			foreach( $this->get_items() as $item ) {

				// Remove non-existent items
				if ( ! $parent_item = $parent->get_item( $item->get_parent_id() ) ) {
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
			do_action( 'woocommerce_gzd_return_shipment_items_synced', $this, $parent, $args );

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

		if ( ! $available_items && ( $parent = $this->get_parent() ) ) {
			$available_items = array_keys( $parent->get_available_items_for_return() );
		}

		return ( $this->is_editable() && ! $this->contains_item_parent( $available_items ) );
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
		return apply_filters( "{$this->get_hook_prefix()}edit_url", get_admin_url( null, 'post.php?post=' . $this->get_order_id() . '&action=edit&shipment_id=' . $this->get_parent_id() . '&return_id=' . $this->get_id() ), $this );
	}
}
