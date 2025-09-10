<?php
/**
 * Return shipment. Counterparts to simple shipments. Return
 * shipments serve to handle retoure/return shipments from customers to the shop owner.
 *
 * @package Vendidero/Shiptastic
 * @version 1.0.0
 */
namespace Vendidero\Shiptastic;

use Exception;
use Vendidero\Shiptastic\Utilities\NumberUtil;
use WC_Order;
use WC_Tax;

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

	/**
	 * The corresponding order object.
	 *
	 * @var null|\WC_Order_Refund
	 */
	private $refund_order = null;

	protected $extra_data = array(
		'order_id'              => 0,
		'is_customer_requested' => false,
		'refund_order_id'       => 0,
		'sender_address'        => array(),
		'return_costs'          => '',
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
	 * Returns the refund order id belonging to the shipment.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_refund_order_id( $context = 'view' ) {
		return $this->get_prop( 'refund_order_id', $context );
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

	public function needs_refund() {
		if ( $this->has_status( 'delivered' ) && ! $this->get_refund_order() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return \WC_Order_Refund|\WP_Error
	 */
	public function create_refund( $refund_args = array() ) {
		if ( $this->get_refund_order() ) {
			return new \WP_Error( 'refund_order_exists', _x( 'This return is already linked to a refund.', 'shipments', 'woocommerce-germanized' ) );
		}

		$items    = $this->get_items();
		$item_map = array();

		if ( $order = $this->get_order() ) {
			$shipment_order = $this->get_order_shipment();
			$order_taxes    = $order->get_taxes();
			$refund_total   = 0.0;
			$return_costs   = $this->get_return_costs();

			foreach ( $items as $item ) {
				if ( $order_item = $order->get_item( $item->get_order_item_id() ) ) {
					$item_total = (float) $order->get_item_total( $order_item, false, false );
					$line_total = NumberUtil::round( $item_total * $item->get_quantity(), wc_get_rounding_precision() );
					$tax_data   = $order_item->get_taxes();
					$line_taxes = array();

					$refund_total += $line_total;

					foreach ( $order_taxes as $tax_id => $tax_item ) {
						$tax_item_id = $tax_item->get_rate_id();

						if ( isset( $tax_data['total'][ $tax_item_id ] ) ) {
							$item_total_tax = (float) $tax_data['total'][ $tax_item_id ] / $order_item->get_quantity();
							$line_total_tax = NumberUtil::round( $item_total_tax * $item->get_quantity(), wc_get_rounding_precision() );

							$line_taxes[ $tax_item_id ] = $line_total_tax;

							$refund_total += $line_total_tax;
						}
					}

					$item_map[ $item->get_order_item_id() ] = array(
						'qty'          => $item->get_quantity(),
						'refund_total' => $line_total,
						'refund_tax'   => $line_taxes,
					);
				}
			}

			$refund_total = $refund_total - $return_costs;
			$refund_total = wc_format_decimal( $refund_total, wc_get_price_decimals() );

			if ( $refund_total > 0 ) {
				$refund_reason = sprintf( _x( 'Refund to your return #%1$s.', 'shipments', 'woocommerce-germanized' ), $this->get_shipment_number() );

				$refund_args = wp_parse_args(
					$refund_args,
					array(
						'reason'         => apply_filters( "{$this->get_general_hook_prefix()}refund_reason", $refund_reason, $this ),
						'refund_payment' => false,
						'restock_items'  => true,
					)
				);

				$refund_args = array_replace_recursive(
					$refund_args,
					array(
						'amount'     => $refund_total,
						'order_id'   => $this->get_order_id(),
						'line_items' => $item_map,
					)
				);

				if ( $payment_gateway = wc_get_payment_gateway_by_order( $order ) ) {
					if ( $payment_gateway->can_refund_order( $order ) ) {
						$refund_args['refund_payment'] = true;
					}
				}

				try {
					if ( $return_costs > 0 ) {
						add_action(
							'woocommerce_create_refund',
							function ( $refund, $args ) use ( $return_costs, $shipment_order ) {
								if ( $args['order_id'] === $shipment_order->get_id() ) {
									$refund_costs_tax_rates  = $shipment_order->get_return_costs_tax_rates();
									$refund_costs_incl_taxes = $shipment_order->return_costs_include_taxes();

									$refund_fee = new \WC_Order_Item_Fee();
									$refund_fee->set_name( _x( 'Refund fee', 'shipments', 'woocommerce-germanized' ) );
									$refund_fee->update_meta_data( '_is_refund_return_costs_fee', 'yes' );

									if ( ! empty( $refund_costs_tax_rates ) ) {
										$taxes     = \WC_Tax::calc_tax( $return_costs, $refund_costs_tax_rates, $refund_costs_incl_taxes );
										$tax_total = array_sum( $taxes );

										if ( $refund_costs_incl_taxes ) {
											$return_costs = NumberUtil::round( $return_costs - $tax_total, wc_get_rounding_precision() );
										}

										$refund_fee->set_tax_class( array_values( $refund_costs_tax_rates )[0]['tax_class'] );
										$refund_fee->set_tax_status( 'taxable' );
										$refund_fee->set_taxes( array( 'total' => $taxes ) );
									}

									$refund_fee->set_total( $return_costs );

									do_action( "{$this->get_general_hook_prefix()}before_create_refund_fee", $refund_fee, $refund, $shipment_order->get_order(), $this );

									$refund->add_item( $refund_fee );

									do_action( "{$this->get_general_hook_prefix()}after_create_refund_fee", $refund, $shipment_order->get_order(), $this );

									$refund->update_taxes();
									$refund->calculate_totals( false );
								}
							},
							9999,
							2
						);
					}

					$refund = wc_create_refund( apply_filters( "{$this->get_general_hook_prefix()}refund_args", $refund_args, $this ) );

					remove_all_actions( 'woocommerce_create_refund', 9999 );

					if ( is_wp_error( $refund ) ) {
						return $refund;
					}

					$this->set_refund_order_id( $refund->get_id() );
					$this->save();

					return $refund;
				} catch ( \Exception $e ) {
					return new \WP_Error( $e->getCode(), $e->getMessage() );
				}
			}
		}

		return new \WP_Error( 'refund_order_error', _x( 'There was an error creating a refund for this return.', 'shipments', 'woocommerce-germanized' ) );
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
				 * @package Vendidero/Shiptastic
				 */
				do_action( 'woocommerce_shiptastic_return_shipment_customer_confirmed', $this->get_id(), $this );

				return true;
			} else {
				return false;
			}
		}

		return false;
	}

	public function hide_return_address() {
		$hide_return_address = ! $this->has_status( 'processing' );

		if ( $provider = $this->get_shipping_provider_instance() ) {
			if ( $provider->hide_return_address() ) {
				$hide_return_address = true;
			}
		}

		return apply_filters( "{$this->get_general_hook_prefix()}hide_return_address", $hide_return_address, $this );
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
	 * @param $context
	 *
	 * @return string
	 */
	public function get_return_costs( $context = 'view' ) {
		$costs = $this->get_prop( 'return_costs', $context );

		if ( 'view' === $context && '' === $costs ) {
			$costs = 0.0;
		}

		return $costs;
	}

	public function has_return_costs() {
		$costs = $this->get_return_costs( 'edit' );

		if ( '' === $costs ) {
			return false;
		} elseif ( (float) $costs > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	public function calculate_return_costs() {
		$costs         = 0.0;
		$applied_costs = false;

		if ( $method = $this->get_shipping_method_instance() ) {
			if ( $method->has_return_costs() ) {
				$costs         = (float) $method->get_return_costs();
				$applied_costs = true;
			}
		}

		if ( ! $applied_costs && ( $provider = $this->get_shipping_provider_instance() ) ) {
			$costs = (float) $provider->get_return_costs();
		}

		$this->set_return_costs( apply_filters( "{$this->get_general_hook_prefix()}calculated_return_costs", wc_format_decimal( $costs, wc_get_price_decimals() ), $this ) );
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
	 * Set shipment refund order id.
	 *
	 * @param string $order_id The order id.
	 */
	public function set_refund_order_id( $order_id ) {
		// Reset order object
		$this->refund_order = null;

		$this->set_prop( 'refund_order_id', absint( $order_id ) );
	}

	public function set_return_costs( $return_costs ) {
		$this->set_prop( 'return_costs', wc_format_decimal( $return_costs, wc_get_price_decimals() ) );
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
			$this->order_shipment = ( $order ? wc_stc_get_shipment_order( $order ) : false );
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
	 * Tries to fetch the refund order for the current shipment.
	 *
	 * @return bool|\WC_Order_Refund|null
	 */
	public function get_refund_order() {
		if ( is_null( $this->refund_order ) ) {
			$this->refund_order = ( $this->get_refund_order_id() > 0 ? wc_get_order( $this->get_refund_order_id() ) : false );
		}

		return $this->refund_order;
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
				 * Example hook name: woocommerce_shiptastic_return_shipment_get_sender_address_first_name
				 *
				 * @param string                                   $value The address property value.
				 * @param Shipment $this The shipment object.
				 *
				 * @package Vendidero/Shiptastic
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
				throw new Exception( esc_html_x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
			}

			$return_address      = wc_stc_get_shipment_return_address( $order_shipment );
			$order               = $order_shipment->get_order();
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
					'order_id'        => $order->get_id(),
					'country'         => $return_address['country'],
					'shipping_method' => $this->get_shipping_method( 'edit' ) ? $this->get_shipping_method( 'edit' ) : $order_shipment->get_shipping_method_id(),
					'address'         => $return_address,
					'sender_address'  => $sender_address_data,
					'weight'          => $this->get_weight( 'edit' ),
					'length'          => $this->get_length( 'edit' ),
					'width'           => $this->get_width( 'edit' ),
					'height'          => $this->get_height( 'edit' ),
				)
			);

			/**
			 * Make sure that manually adjusted providers are not overridden by syncing.
			 */
			$default_provider_instance = wc_stc_get_order_shipping_provider( $order, $args['shipping_method'] );
			$default_provider          = $default_provider_instance ? $default_provider_instance->get_name() : '';
			$provider                  = $this->get_shipping_provider( 'edit' );

			$args = wp_parse_args(
				$args,
				array(
					'shipping_provider' => ( ! empty( $provider ) ) ? $provider : $default_provider,
				)
			);

			/**
			 * Filter to allow adjusting the return shipment props synced from the corresponding order.
			 *
			 * @param mixed          $args The properties in key => value pairs.
			 * @param ReturnShipment $shipment The shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 *
			 * @package Vendidero/Shiptastic
			 */
			$args = apply_filters( 'woocommerce_shiptastic_return_shipment_sync_props', $args, $this, $order_shipment );

			$this->set_props( $args );

			/**
			 * Action that fires after a return shipment has been synced. Syncing is used to
			 * keep the shipment in sync with the corresponding parent shipment.
			 *
			 * @param ReturnShipment $shipment The return shipment object.
			 * @param Order          $order_shipment The shipment order object.
			 * @param array          $args Array containing properties in key => value pairs to be updated.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_return_shipment_synced', $this, $order_shipment, $args );

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
				throw new Exception( esc_html_x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
			}

			$order = $order_shipment->get_order();

			$args = wp_parse_args(
				$args,
				array(
					'items' => array(),
				)
			);

			$available_items = $order_shipment->get_selectable_items_for_return(
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
						$shipment_item = wc_stc_create_return_shipment_item( $this, $item, $sync_data );

						$this->add_item( $shipment_item );
					} else {
						$shipment_item->sync( $sync_data );
					}

					if ( $item->has_children() ) {
						$children = $item->get_children();

						foreach ( $children as $child_item ) {
							$child_order_item_id = $child_item->get_order_item_id();
							$sync_child_data     = array(
								'quantity'       => $child_item->get_quantity(),
								'item_parent_id' => $shipment_item->get_id(),
							);

							if ( ! $shipment_child_item = $this->get_item_by_order_item_id( $child_order_item_id ) ) {
								$shipment_child_item = wc_stc_create_return_shipment_item( $this, $child_item, $sync_child_data );

								$this->add_item( $shipment_child_item );
							} else {
								$shipment_child_item->sync( $sync_child_data );
							}
						}
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
			 * @package Vendidero/Shiptastic
			 */
			do_action( 'woocommerce_shiptastic_return_shipment_items_synced', $this, $order_shipment, $args );

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
		 * Example hook name: woocommerce_shiptastic_shipment_get_edit_url
		 *
		 * @param string   $url  The URL.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}edit_url", get_admin_url( null, 'post.php?post=' . $this->get_order_id() . '&action=edit&shipment_id=' . $this->get_id() ), $this );
	}

	public function save() {
		if ( $this->is_editable() && '' === $this->get_return_costs( 'edit' ) && version_compare( $this->get_version(), '4.7.0', '>=' ) ) {
			$this->calculate_return_costs();
		}

		return parent::save();
	}
}
