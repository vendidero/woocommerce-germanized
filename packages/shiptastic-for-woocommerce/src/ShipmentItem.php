<?php

namespace Vendidero\Shiptastic;

use WC_Data;
use WC_Data_Store;
use Exception;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Order item class.
 */
class ShipmentItem extends WC_Data {

	protected $order_item = null;

	protected $shipment = null;

	protected $product = null;

	protected $children = null;

	protected $parent = null;

	/**
	 * Order Data array. This is the core order data exposed in APIs since 3.0.0.
	 *
	 * @var array
	 */
	protected $data = array(
		'shipment_id'         => 0,
		'order_item_id'       => 0,
		'parent_id'           => 0,
		'item_parent_id'      => 0,
		'quantity'            => 1,
		'product_id'          => 0,
		'weight'              => '',
		'width'               => '',
		'length'              => '',
		'height'              => '',
		'sku'                 => '',
		'name'                => '',
		'total'               => 0,
		'subtotal'            => 0,
		'hs_code'             => '',
		'customs_description' => '',
		'manufacture_country' => '',
		'attributes'          => array(),
	);

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shipment-items';

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'shipment_item';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'shipment_item';

	/**
	 * Constructor.
	 *
	 * @param int|object|array $item ID to load from the DB, or WC_Order_Item object.
	 */
	public function __construct( $item = 0 ) {
		parent::__construct( $item );

		if ( $item instanceof ShipmentItem ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'shipment-item' );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 * array_replace_recursive does not work well for order items because it merges taxes instead
	 * of replacing them.
	 *
	 */
	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}
		$this->changes = array();
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	public function get_general_hook_prefix() {
		return 'woocommerce_' . $this->object_type . '_';
	}

	public function get_type() {
		return 'simple';
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_shipment_id( $context = 'view' ) {
		return $this->get_prop( 'shipment_id', $context );
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_order_item_id( $context = 'view' ) {
		return $this->get_prop( 'order_item_id', $context );
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Get parent id, e.g. the item linked to a return item.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Get item parent id inside the current shipment.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_item_parent_id( $context = 'view' ) {
		return $this->get_prop( 'item_parent_id', $context );
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_subtotal( $context = 'view' ) {
		$subtotal = $this->get_prop( 'subtotal', $context );

		if ( 'view' === $context && empty( $subtotal ) ) {
			$subtotal = $this->get_total();
		}

		return $subtotal;
	}

	/**
	 * Get quantity.
	 *
	 * @return int
	 */
	public function get_sku( $context = 'view' ) {
		return $this->get_prop( 'sku', $context );
	}

	/**
	 * Get quantity.
	 *
	 * @return int
	 */
	public function get_quantity( $context = 'view' ) {
		return $this->get_prop( 'quantity', $context );
	}

	/**
	 * Get weight.
	 *
	 * @return string
	 */
	public function get_weight( $context = 'view' ) {
		return $this->get_prop( 'weight', $context );
	}

	/**
	 * Get width.
	 *
	 * @return string
	 */
	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	/**
	 * Get length.
	 *
	 * @return string
	 */
	public function get_length( $context = 'view' ) {
		return $this->get_prop( 'length', $context );
	}

	/**
	 * Get height.
	 *
	 * @return string
	 */
	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
	}

	public function get_name( $context = 'view' ) {
		$name = $this->get_prop( 'name', $context );

		if ( 'view' === $context && empty( $name ) && ( $item = $this->get_order_item() ) ) {
			$name = $item->get_name();
		}

		return $name;
	}

	public function get_customs_description( $context = 'view' ) {
		$customs_description = $this->get_prop( 'customs_description', $context );

		if ( 'view' === $context && empty( $customs_description ) ) {
			$customs_description = $this->get_name( $context );
		}

		return $customs_description;
	}

	public function get_hs_code( $context = 'view' ) {
		$legacy = $this->get_meta( '_dhl_hs_code', $context );
		$prop   = $this->get_prop( 'hs_code', $context );

		if ( '' === $prop && ! empty( $legacy ) ) {
			$prop = $legacy;
		}

		return $prop;
	}

	public function get_manufacture_country( $context = 'view' ) {
		$legacy = $this->get_meta( '_dhl_manufacture_country', $context );
		$prop   = $this->get_prop( 'manufacture_country', $context );

		if ( '' === $prop && ! empty( $legacy ) ) {
			$prop = $legacy;
		}

		if ( 'view' === $context && empty( $prop ) ) {
			$prop = Package::get_base_country();
		}

		return $prop;
	}

	/**
	 * Get attributes.
	 *
	 * @return string[]
	 */
	public function get_attributes( $context = 'view' ) {
		return $this->get_prop( 'attributes', $context );
	}

	public function has_attributes() {
		$attributes = $this->get_attributes();

		return ! empty( $attributes );
	}

	/**
	 * Get parent order object.
	 *
	 * @return SimpleShipment|ReturnShipment|Shipment
	 */
	public function get_shipment() {
		if ( is_null( $this->shipment ) && 0 < $this->get_shipment_id() ) {
			$this->shipment = wc_stc_get_shipment( $this->get_shipment_id() );
		}

		$shipment = ( $this->shipment ) ? $this->shipment : false;

		return $shipment;
	}

	/**
	 * Sets the linked shipment instance.
	 *
	 * @param Shipment $shipment
	 */
	public function set_shipment( &$shipment ) {
		$this->shipment = $shipment;
	}

	/**
	 * Syncs an item with either it's parent item or the corresponding order item.
	 *
	 * @param array $args
	 */
	public function sync( $args = array() ) {
		$item = false;

		if ( $shipment = $this->get_shipment() ) {
			if ( 'return' === $shipment->get_type() ) {
				if ( $shipment = $this->get_shipment() ) {
					if ( $order_shipment = $shipment->get_order_shipment() ) {
						$item = $order_shipment->get_simple_shipment_item( $this->get_order_item_id() );
					}
				}
			} else {
				$item = $this->get_order_item();
			}
		}

		if ( is_a( $item, '\Vendidero\Shiptastic\ShipmentItem' ) ) {
			$default_data = $item->get_data();

			unset( $default_data['id'] );
			unset( $default_data['shipment_id'] );
			unset( $default_data['item_parent_id'] );

			$default_data['parent_id'] = $item->get_id();
			$args                      = wp_parse_args( $args, $default_data );
		} elseif ( is_a( $item, 'WC_Order_Item' ) ) {
			if ( is_callable( array( $item, 'get_variation_id' ) ) && is_callable( array( $item, 'get_product_id' ) ) ) {
				$this->set_product_id( $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() );
			} elseif ( is_callable( array( $item, 'get_product_id' ) ) ) {
				$this->set_product_id( $item->get_product_id() );
			}

			$args = wp_parse_args(
				$args,
				array(
					'quantity' => 1,
				)
			);

			$product = null;

			if ( $shipment = $this->get_shipment() ) {
				if ( $order_shipment = $shipment->get_order_shipment() ) {
					$product = $order_shipment->get_order_item_product( $item );
				}
			}

			if ( ! $product && is_callable( array( $item, 'get_product' ) ) ) {
				if ( $product = $item->get_product() ) {
					$product = wc_shiptastic_get_product( $product );
				}

				$product = apply_filters( 'woocommerce_shiptastic_order_item_product', $product, $item );
			}

			/**
			 * Calculate the order item total per unit to make sure it is independent from
			 * shipment item quantity.
			 */
			$tax_total    = is_callable( array( $item, 'get_total_tax' ) ) ? ( (float) $item->get_total_tax() / $item->get_quantity() ) * $args['quantity'] : 0;
			$total        = is_callable( array( $item, 'get_total' ) ) ? ( (float) $item->get_total() / $item->get_quantity() ) * $args['quantity'] : 0;
			$subtotal     = is_callable( array( $item, 'get_subtotal' ) ) ? (float) $item->get_subtotal() / $item->get_quantity() * $args['quantity'] : 0;
			$tax_subtotal = is_callable( array( $item, 'get_subtotal_tax' ) ) ? (float) $item->get_subtotal_tax() / $item->get_quantity() * $args['quantity'] : 0;
			$meta         = $item->get_formatted_meta_data( apply_filters( "{$this->get_hook_prefix()}hide_meta_prefix", '_', $this ), apply_filters( "{$this->get_hook_prefix()}include_all_meta", false, $this ) );
			$attributes   = array();

			foreach ( $meta as $meta_id => $entry ) {
				$attributes[] = array(
					'key'                => $entry->key,
					'value'              => str_replace( array( '<p>', '</p>' ), '', $entry->display_value ),
					'label'              => $entry->display_key,
					'order_item_meta_id' => $meta_id,
				);
			}

			$args = wp_parse_args(
				$args,
				array(
					'order_item_id'       => $item->get_id(),
					'quantity'            => 1,
					'name'                => $item->get_name(),
					'sku'                 => $product ? $product->get_sku() : '',
					'total'               => $total + $tax_total,
					'subtotal'            => $subtotal + $tax_subtotal,
					'weight'              => $product ? wc_get_weight( $product->get_weight(), $shipment->get_weight_unit() ) : '',
					'length'              => $product ? wc_get_dimension( (float) $product->get_shipping_length(), $shipment->get_dimension_unit() ) : '',
					'width'               => $product ? wc_get_dimension( (float) $product->get_shipping_width(), $shipment->get_dimension_unit() ) : '',
					'height'              => $product ? wc_get_dimension( (float) $product->get_shipping_height(), $shipment->get_dimension_unit() ) : '',
					'hs_code'             => $product ? $product->get_hs_code() : '',
					'customs_description' => $product ? $product->get_customs_description() : '',
					'manufacture_country' => $product ? $product->get_manufacture_country() : '',
					'attributes'          => $attributes,
					'item_parent_id'      => $this->get_item_parent_id() ? $this->get_item_parent_id() : 0,
					'parent'              => null,
				)
			);

			if ( ! is_null( $args['parent'] ) && is_a( $args['parent'], 'Vendidero\Shiptastic\ShipmentItem' ) ) {
				$this->set_parent( $args['parent'] );
			}
		}

		$this->set_props( $args );

		/**
		 * Action that fires after a shipment item has been synced. Syncing is used to
		 * keep the shipment item in sync with the corresponding order item or parent shipment item.
		 *
		 * @param WC_Order_Item|ShipmentItem $item The order item object or parent shipment item.
		 * @param array                       $args Array containing props in key => value pairs which have been updated.
		 *
		 * @package Vendidero/Shiptastic
		 */
		do_action( 'woocommerce_shiptastic_shipment_item_synced', $this, $item, $args );
	}

	public function get_order_item() {
		if ( is_null( $this->order_item ) && 0 < $this->get_order_item_id() ) {
			if ( $shipment = $this->get_shipment() ) {
				if ( $order = $shipment->get_order() ) {
					$this->order_item = $order->get_item( $this->get_order_item_id() );
				}
			}
		}

		$item = ( $this->order_item ) ? $this->order_item : false;

		return $item;
	}

	public function get_product() {
		if ( is_null( $this->product ) && 0 < $this->get_product_id() ) {
			$this->product = wc_get_product( $this->get_product_id() );
		}

		$product = ( $this->product ) ? $this->product : false;

		return $product;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_shipment_id( $value ) {
		$this->order_item = null;
		$this->shipment   = null;
		$this->children   = null;

		$this->set_prop( 'shipment_id', absint( $value ) );
	}

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_order_item_id( $value ) {
		$this->set_prop( 'order_item_id', absint( $value ) );
	}

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_total( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'total', $value );
	}

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_subtotal( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'subtotal', $value );
	}

	/**
	 * Set order ID.
	 *
	 * @param int $value Order ID.
	 */
	public function set_product_id( $value ) {
		$this->product = null;
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Set parent id.
	 *
	 * @param int $value parent id.
	 */
	public function set_parent_id( $value ) {
		$this->set_prop( 'parent_id', absint( $value ) );
	}

	/**
	 * Set item parent id.
	 *
	 * @param int $value parent id.
	 */
	public function set_item_parent_id( $value ) {
		$this->set_prop( 'item_parent_id', absint( $value ) );
	}

	/**
	 * @param ShipmentItem $item
	 *
	 * @return void
	 */
	public function set_parent( $item ) {
		$this->parent = $item;

		$this->set_item_parent_id( $item->get_id() );
	}

	/**
	 * @param ShipmentItem $item
	 *
	 * @return void
	 */
	public function add_child( $item, $key = '' ) {
		$key      = empty( $key ) ? $item->get_id() : $key;
		$children = is_null( $this->children ) ? array() : $this->children;

		$children[ $key ] = $item;
		$this->children   = $children;
	}

	public function remove_child( $key ) {
		if ( ! is_null( $this->children ) ) {
			if ( isset( $this->children[ $key ] ) ) {
				unset( $this->children[ $key ] );
			}
		}
	}

	/**
	 * @return ShipmentItem|ShipmentReturnItem|null
	 */
	public function get_parent() {
		if ( is_null( $this->parent ) ) {
			if ( ! $this->get_item_parent_id() ) {
				return null;
			}

			if ( $this->get_shipment() ) {
				if ( $item = $this->get_shipment()->get_item( $this->get_item_parent_id() ) ) {
					$this->parent = $item;
				}
			} elseif ( $this->get_id() > 0 ) {
				if ( $item = wc_stc_get_shipment_item( $this->get_item_parent_id() ) ) {
					$this->parent = $item;
				}
			}
		}

		return $this->parent;
	}

	/**
	 * @return ShipmentItem[]
	 */
	public function get_children() {
		if ( is_null( $this->children ) ) {
			$this->children = array();

			if ( $this->get_shipment() ) {
				$items = $this->get_shipment()->get_items();

				foreach ( $items as $k => $item ) {
					if ( empty( $item->get_item_parent_id() ) ) {
						continue;
					}

					if ( $item->get_item_parent_id() === $this->get_id() ) {
						$this->children[ $k ] = $item;
					}
				}
			} elseif ( $this->get_id() > 0 ) {
				$this->children = $this->data_store->read_children( $this );
			}
		}

		return $this->children;
	}

	public function has_children() {
		$children = $this->get_children();

		return ! empty( $children ) ? true : false;
	}

	public function set_sku( $sku ) {
		$this->set_prop( 'sku', $sku );
	}

	/**
	 * Set weight in kg
	 *
	 * @param $weight
	 */
	public function set_weight( $weight ) {
		$this->set_prop( 'weight', '' === $weight ? '' : wc_format_decimal( $weight ) );

		if ( $shipment = $this->get_shipment() ) {
			$shipment->reset_content_data();
		}
	}

	/**
	 * Set width in cm
	 *
	 * @param $weight
	 */
	public function set_width( $width ) {
		$this->set_prop( 'width', '' === $width ? '' : wc_format_decimal( $width ) );

		if ( $shipment = $this->get_shipment() ) {
			$shipment->reset_content_data();
		}
	}

	/**
	 * Set length in cm
	 *
	 * @param $weight
	 */
	public function set_length( $length ) {
		$this->set_prop( 'length', '' === $length ? '' : wc_format_decimal( $length ) );

		if ( $shipment = $this->get_shipment() ) {
			$shipment->reset_content_data();
		}
	}

	/**
	 * Set height in cm
	 *
	 * @param $weight
	 */
	public function set_height( $height ) {
		$this->set_prop( 'height', '' === $height ? '' : wc_format_decimal( $height ) );

		if ( $shipment = $this->get_shipment() ) {
			$shipment->reset_content_data();
		}
	}

	public function get_dimensions( $context = 'view' ) {
		return array(
			'length' => $this->get_length( $context ),
			'width'  => $this->get_width( $context ),
			'height' => $this->get_height( $context ),
		);
	}

	public function set_quantity( $quantity ) {
		$this->set_prop( 'quantity', absint( $quantity ) );

		if ( $shipment = $this->get_shipment() ) {
			$shipment->reset_content_data();
		}
	}

	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	public function set_hs_code( $code ) {
		$this->set_prop( 'hs_code', $code );
	}

	public function set_customs_description( $description ) {
		$this->set_prop( 'customs_description', $description );
	}

	public function set_manufacture_country( $country ) {
		$this->set_prop( 'manufacture_country', wc_strtoupper( $country ) );
	}

	/**
	 * Set attributes
	 *
	 * @param $attributes
	 */
	public function set_attributes( $attributes ) {
		$this->set_prop( 'attributes', (array) $attributes );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	public function is_readonly() {
		return apply_filters( "{$this->get_general_hook_prefix()}is_readonly", $this->get_item_parent_id() > 0 ? true : false, $this );
	}

	public function save() {
		if ( $parent = $this->get_parent() ) {
			if ( $this->get_item_parent_id() !== $parent->get_id() ) {
				$this->set_item_parent_id( $parent->get_id() );
			}
		}

		return parent::save();
	}

	public function delete( $force_delete = false ) {
		$has_deleted = parent::delete( $force_delete );

		if ( true === $has_deleted && $this->has_children() ) {
			foreach ( $this->get_children() as $child ) {
				$child->delete( $force_delete );
			}
		}
	}
}
