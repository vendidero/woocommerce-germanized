<?php
/**
 * Regular shipment
 *
 * @package Vendidero/Shiptastic
 * @version 1.0.0
 */
namespace Vendidero\Shiptastic;

use DVDoug\BoxPacker\ItemList;
use Vendidero\Shiptastic\Caches\Helper;
use Vendidero\Shiptastic\Interfaces\ShipmentLabel;
use Vendidero\Shiptastic\Interfaces\ShipmentReturnLabel;
use Vendidero\Shiptastic\Labels\Label;
use Vendidero\Shiptastic\ShippingMethod\ProviderMethod;
use Vendidero\Shiptastic\ShippingProvider\PickupLocation;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_Data_Store_WP;
use WC_DateTime;
use WC_Order;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
abstract class Shipment extends WC_Data {

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'shipment';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @var object
	 */
	protected $data_store_name = 'shipment';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shipment';

	protected $label_configuration_set = null;

	/**
	 * @var null|ShipmentLabel
	 */
	protected $label = null;

	/**
	 * The contained ShipmentItems.
	 *
	 * @var null|Shipment
	 */
	protected $items = null;

	/**
	 * List of items to be deleted on save.
	 *
	 * @var Shipment[]
	 */
	protected $items_to_delete = array();

	protected $items_to_pack = null;

	protected $pickup_location = null;

	/**
	 * Item weights.
	 *
	 * @var null|float[]
	 */
	protected $weights = null;

	/**
	 * Item lengths.
	 *
	 * @var null|float[]
	 */
	protected $lengths = null;

	/**
	 * Item widths.
	 *
	 * @var null|float[]
	 */
	protected $widths = null;

	/**
	 * Item volumes.
	 *
	 * @var null|float[]
	 */
	protected $volumes = null;

	/**
	 * Item heights.
	 *
	 * @var null|float[]
	 */
	protected $heights = null;

	/**
	 * Packaging
	 *
	 * @var null|Packaging
	 */
	protected $packaging = null;

	/**
	 * @var ProviderMethod
	 */
	protected $shipping_method_instance = null;

	/**
	 * Stores shipment data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'                    => null,
		'date_sent'                       => null,
		'status'                          => '',
		'weight'                          => '',
		'width'                           => '',
		'height'                          => '',
		'length'                          => '',
		'packaging_weight'                => '',
		'packaging_title'                 => '',
		'weight_unit'                     => '',
		'dimension_unit'                  => '',
		'country'                         => '',
		'address'                         => array(),
		'tracking_id'                     => '',
		'shipping_provider'               => '',
		'shipping_method'                 => '',
		'pickup_location_code'            => '',
		'pickup_location_customer_number' => '',
		'total'                           => 0,
		'subtotal'                        => 0,
		'additional_total'                => 0,
		'est_delivery_date'               => null,
		'packaging_id'                    => 0,
		'version'                         => '',
	);

	/**
	 * Get the shipment if ID is passed, otherwise the shipment is new and empty.
	 * This class should NOT be instantiated, but the `wc_stc_get_shipment` function should be used.
	 *
	 * @param int|object|Shipment $shipment Shipment to read.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof Shipment ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

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
	 * This method overwrites the base class's clone method to make it a no-op. In base class WC_Data, we are unsetting the meta_id to clone.
	 *
	 * @see WC_Abstract_Order::__clone()
	 */
	public function __clone() {}

	public function get_type() {
		return '';
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
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

	/**
	 * @return bool|Order
	 */
	public function get_order_shipment() {
		return false;
	}

	public function set_order_shipment( &$order_shipment ) {}

	/**
	 * Return item count (quantities summed up).
	 *
	 * @return int
	 */
	public function get_item_count() {
		$items    = $this->get_items();
		$quantity = 0;

		foreach ( $items as $item ) {
			$quantity += $item->get_quantity();
		}

		return $quantity;
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		$shipment_prefix = 'simple' === $this->get_type() ? '' : $this->get_type() . '_';

		return "woocommerce_shiptastic_{$shipment_prefix}shipment_";
	}

	public function get_shipping_zone() {
		$zone = 'int';

		if ( $this->is_shipping_domestic() ) {
			$zone = 'dom';
		} elseif ( $this->is_shipping_inner_eu() ) {
			$zone = 'eu';
		}

		return apply_filters( "{$this->get_general_hook_prefix()}shipping_zone", $zone, $this );
	}

	public function is_shipping_domestic() {
		return apply_filters(
			"{$this->get_general_hook_prefix()}is_shipping_domestic",
			Package::is_shipping_domestic(
				$this->get_country(),
				array(
					'sender_country'  => $this->get_sender_country(),
					'sender_postcode' => $this->get_sender_postcode(),
					'postcode'        => $this->get_postcode(),
				)
			),
			$this
		);
	}

	/**
	 * Returns true in case the shipment is being shipped inner EU, e.g.
	 * from a base country inside of the EU to another country inside the EU.
	 *
	 * @return bool
	 */
	public function is_shipping_inner_eu() {
		if ( apply_filters(
			"{$this->get_general_hook_prefix()}is_shipping_inner_eu",
			Package::is_shipping_inner_eu_country(
				$this->get_country(),
				array(
					'sender_country'  => $this->get_sender_country(),
					'sender_postcode' => $this->get_sender_postcode(),
					'postcode'        => $this->get_postcode(),
				)
			),
			$this
		) ) {
			return true;
		}

		return false;
	}

	public function is_shipping_international() {
		if ( $this->is_shipping_domestic() || $this->is_shipping_inner_eu() ) {
			return false;
		}

		return true;
	}

	/**
	 * Return the shipment statuses without wc-stc- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {

			/**
			 * Filters the default Shipment status used as fallback.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for a shipment type.
			 *
			 * Example hook name: woocommerce_shiptastic_shipment_get_default_shipment_status
			 *
			 * @param string $status Default fallback status.
			 *
			 * @package Vendidero/Shiptastic
			 */
			$status = apply_filters( "{$this->get_hook_prefix()}}default_shipment_status", 'draft' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		return $status;
	}

	/**
	 * Checks whether the shipment has a specific status or not.
	 *
	 * @param  string|string[] $status The status to be checked against.
	 * @return boolean
	 */
	public function has_status( $status ) {
		/**
		 * Filter to decide whether a Shipment has a certain status or not.
		 *
		 * @param boolean                                  $has_status Whether the Shipment has a status or not.
		 * @param Shipment $this The shipment object.
		 * @param string                                   $status The status to be checked against.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_shipment_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
	}

	/**
	 * Return the date this shipment was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Return the date this shipment was sent.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_sent( $context = 'view' ) {
		return $this->get_prop( 'date_sent', $context );
	}

	/**
	 * Returns the shipment method.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipping_method( $context = 'view' ) {
		return $this->get_prop( 'shipping_method', $context );
	}

	public function get_shipping_method_instance() {
		$method_id = $this->get_shipping_method();

		if ( is_null( $this->shipping_method_instance ) && ! empty( $method_id ) ) {
			$this->shipping_method_instance = wc_stc_get_shipping_provider_method( $this->get_shipping_method() );
		}

		return is_null( $this->shipping_method_instance ) ? false : $this->shipping_method_instance;
	}

	/**
	 * @return false|Labels\ConfigurationSet
	 */
	public function get_label_configuration_set() {
		if ( is_null( $this->label_configuration_set ) ) {
			$this->label_configuration_set = false;

			if ( $packaging = $this->get_packaging() ) {
				if ( $method_set = $packaging->get_configuration_set( $this ) ) {
					$this->label_configuration_set = $method_set;
				}
			}

			if ( ! $this->label_configuration_set && ( $method = $this->get_shipping_method_instance() ) ) {
				if ( $method_set = $method->get_configuration_set( $this ) ) {
					$this->label_configuration_set = $method_set;
				}
			}

			if ( ! $this->label_configuration_set && ( $provider = $this->get_shipping_provider_instance() ) ) {
				if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\LabelConfigurationSet' ) ) {
					if ( $provider_set = $provider->get_configuration_set( $this ) ) {
						$this->label_configuration_set = $provider_set;
					}
				}
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}label_configuration_set", $this->label_configuration_set, $this );
	}

	/**
	 * Returns the shipment weight. In case view context was chosen and weight is not yet set, returns the content weight.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'weight', $context );

		if ( 'view' === $context && '' === $weight ) {
			return $this->get_content_weight();
		}

		return $weight;
	}

	public function get_total_weight() {
		$weight = $this->get_weight() + $this->get_packaging_weight();

		return $weight;
	}

	public function get_packaging_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'packaging_weight', $context );

		if ( 'view' === $context && '' === $weight ) {
			$weight = wc_format_decimal( 0 );

			if ( $packaging = $this->get_packaging() ) {
				if ( ! empty( $packaging->get_weight() ) ) {
					$weight = wc_get_weight( $packaging->get_weight(), $this->get_weight_unit(), wc_stc_get_packaging_weight_unit() );
				}
			}
		}

		return $weight;
	}

	public function get_packaging_title( $context = 'view' ) {
		$title = $this->get_prop( 'packaging_title', $context );

		if ( 'view' === $context && '' === $title ) {
			if ( $packaging = $this->get_packaging() ) {
				$title = $packaging->get_title();
			}
		}

		return $title;
	}

	/**
	 * @return ShipmentItem[]|ItemList
	 */
	public function get_items_to_pack() {
		if ( ! Package::is_packing_supported() ) {
			return $this->get_items();
		} else {
			if ( is_null( $this->items_to_pack ) ) {
				$this->items_to_pack = new ItemList();

				foreach ( $this->get_items() as $item ) {
					$box_item = new Packing\ShipmentItem( $item );
					$this->items_to_pack->insert( $box_item, $item->get_quantity() );
				}
			}

			return apply_filters( "{$this->get_hook_prefix()}items_to_pack", $this->items_to_pack, $this );
		}
	}

	/**
	 * Returns the shipment weight unit.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_weight_unit( $context = 'view' ) {
		$unit = $this->get_prop( 'weight_unit', $context );

		if ( 'view' === $context && '' === $unit ) {
			return get_option( 'woocommerce_weight_unit' );
		}

		return $unit;
	}

	/**
	 * Returns the shipment dimension unit.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_dimension_unit( $context = 'view' ) {
		$unit = $this->get_prop( 'dimension_unit', $context );

		if ( 'view' === $context && '' === $unit ) {
			return get_option( 'woocommerce_dimension_unit' );
		}

		return $unit;
	}

	/**
	 * Returns the shipment length. In case view context was chosen and length is not yet set, returns the content length.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_length( $context = 'view' ) {
		$length = $this->get_prop( 'length', $context );

		if ( 'view' === $context && '' === $length ) {
			return $this->get_content_length();
		}

		return $length;
	}

	public function get_package_length() {
		$length = $this->get_length();

		// Older versions did not sync dimensions with packaging dimensions
		if ( '' === $this->get_version() ) {
			if ( $packaging = $this->get_packaging() ) {
				$length = wc_get_dimension( $packaging->get_length(), $this->get_dimension_unit(), wc_stc_get_packaging_dimension_unit() );
			}
		}

		return $length;
	}

	public function has_packaging() {
		return ( $this->get_packaging_id() > 0 && $this->get_packaging() ) ? true : false;
	}

	/**
	 * Returns the shipment width. In case view context was chosen and width is not yet set, returns the content width.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_width( $context = 'view' ) {
		$width = $this->get_prop( 'width', $context );

		if ( 'view' === $context && '' === $width ) {
			return $this->get_content_width();
		}

		return $width;
	}

	public function get_package_width() {
		$width = $this->get_width();

		if ( '' === $this->get_version() ) {
			if ( $packaging = $this->get_packaging() ) {
				$width = wc_get_dimension( $packaging->get_width(), $this->get_dimension_unit(), wc_stc_get_packaging_dimension_unit() );
			}
		}

		return $width;
	}

	/**
	 * Returns the shipment height. In case view context was chosen and height is not yet set, returns the content height.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_height( $context = 'view' ) {
		$height = $this->get_prop( 'height', $context );

		if ( 'view' === $context && '' === $height ) {
			return $this->get_content_height();
		}

		return $height;
	}

	public function get_package_height() {
		$height = $this->get_height();

		if ( '' === $this->get_version() ) {
			if ( $packaging = $this->get_packaging() ) {
				$height = wc_get_dimension( $packaging->get_height(), $this->get_dimension_unit(), wc_stc_get_packaging_dimension_unit() );
			}
		}

		return $height;
	}

	public function has_dimensions() {
		$width  = $this->get_width();
		$length = $this->get_length();
		$height = $this->get_height();

		return ( ! empty( $width ) && ! empty( $length ) && ! empty( $height ) );
	}

	/**
	 * Returns the calculated weights for included items.
	 *
	 * @return float[]
	 */
	public function get_item_weights() {
		if ( is_null( $this->weights ) ) {
			$this->weights = array();

			foreach ( $this->get_items() as $k => $item ) {
				$this->weights[ $k ] = ( ( $item->get_weight() === '' ? 0 : $item->get_weight() ) * $item->get_quantity() );
			}

			if ( empty( $this->weights ) ) {
				$this->weights = array( 0 );
			}
		}

		return $this->weights;
	}

	/**
	 * Returns the calculated lengths for included items.
	 *
	 * @return float[]
	 */
	public function get_item_lengths() {
		if ( is_null( $this->lengths ) ) {
			$this->lengths = array();

			foreach ( $this->get_items() as $k => $item ) {
				$this->lengths[ $k ] = $item->get_length() === '' ? 0 : $item->get_length();
			}

			if ( empty( $this->lengths ) ) {
				$this->lengths = array( 0 );
			}
		}

		return $this->lengths;
	}

	public function get_item_volumes() {
		if ( is_null( $this->volumes ) ) {
			$this->volumes = array();

			foreach ( $this->get_items() as $k => $item ) {
				$dimensions = $item->get_dimensions();
				$volume     = ( '' !== $dimensions['length'] ? (float) $dimensions['length'] : 0 ) * ( '' !== $dimensions['width'] ? (float) $dimensions['width'] : 0 ) * ( '' !== $dimensions['height'] ? (float) $dimensions['height'] : 0 );

				$this->volumes[ $k ] = $volume * (float) $item->get_quantity();
			}

			if ( empty( $this->volumes ) ) {
				$this->volumes = array( 0 );
			}
		}

		return $this->volumes;
	}

	/**
	 * Returns the calculated widths for included items.
	 *
	 * @return float[]
	 */
	public function get_item_widths() {
		if ( is_null( $this->widths ) ) {
			$this->widths = array();

			foreach ( $this->get_items() as $k => $item ) {
				$this->widths[ $k ] = $item->get_width() === '' ? 0 : $item->get_width();
			}

			if ( empty( $this->widths ) ) {
				$this->widths = array( 0 );
			}
		}

		return $this->widths;
	}

	/**
	 * Returns the calculated heights for included items.
	 *
	 * @return float[]
	 */
	public function get_item_heights() {
		if ( is_null( $this->heights ) ) {
			$this->heights = array();

			foreach ( $this->get_items() as $k => $item ) {
				$this->heights[ $k ] = ( $item->get_height() === '' ? 0 : $item->get_height() ) * $item->get_quantity();
			}

			if ( empty( $this->heights ) ) {
				$this->heights = array( 0 );
			}
		}

		return $this->heights;
	}

	/**
	 * Returns the calculated weight for included items.
	 *
	 * @return float
	 */
	public function get_content_weight() {
		return wc_format_decimal( array_sum( $this->get_item_weights() ) );
	}

	public function get_content_dimensions() {
		return array(
			'length' => $this->get_content_length(),
			'width'  => $this->get_content_width(),
			'height' => $this->get_content_height(),
		);
	}

	/**
	 * Returns the calculated length for included items.
	 *
	 * @return float
	 */
	public function get_content_length() {
		$default = max( $this->get_item_lengths() );

		return wc_format_decimal( $default, false, true );
	}

	/**
	 * Returns the calculated width for included items.
	 *
	 * @return float
	 */
	public function get_content_width() {
		$default = max( $this->get_item_widths() );

		return wc_format_decimal( $default, false, true );
	}

	/**
	 * Returns the calculated volume for included items.
	 *
	 * @return float
	 */
	public function get_content_volume() {
		$default = array_sum( $this->get_item_volumes() );

		return wc_format_decimal( $default, false, true );
	}

	/**
	 * Returns the calculated height for included items.
	 *
	 * @return float
	 */
	public function get_content_height() {
		$default_height = array_sum( $this->get_item_heights() );

		return wc_format_decimal( $default_height, false, true );
	}

	/**
	 * Returns the shipping address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_address( $context = 'view' ) {
		$address = $this->get_prop( 'address', $context );

		if ( 'view' === $context ) {
			if ( $customer_number = $this->get_pickup_location_customer_number() ) {
				$address['pickup_location_customer_number'] = $customer_number;
			}
		}

		return $address;
	}

	/**
	 * Returns the shipment total.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return float
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/**
	 * Returns the shipment total.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return float
	 */
	public function get_subtotal( $context = 'view' ) {
		$subtotal = $this->get_prop( 'subtotal', $context );

		if ( 'view' === $context && empty( $subtotal ) ) {
			$subtotal = $this->get_total();
		}

		return $subtotal;
	}

	/**
	 * Returns the additional total amount containing shipping and fee costs.
	 * Only one of the shipments related to an order should include additional total.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return float
	 */
	public function get_additional_total( $context = 'view' ) {
		return $this->get_prop( 'additional_total', $context );
	}

	/**
	 * Default shipment incoterms, e.g. DAP.
	 *
	 * @return string
	 */
	public function get_incoterms() {
		return wc_strtoupper( apply_filters( "{$this->get_hook_prefix()}incoterms", '', $this ) );
	}

	public function has_tracking() {
		$has_tracking = true;

		if ( ! $this->has_tracking_instruction() && ! $this->get_tracking_url() ) {
			$has_tracking = false;
		}

		/**
		 * Check whether the label supports tracking or not
		 */
		if ( $this->has_label() && ( $label = $this->get_label() ) ) {
			if ( ! $label->is_trackable() ) {
				$has_tracking = false;
			}
		}

		return apply_filters( "{$this->get_general_hook_prefix()}has_tracking", $has_tracking, $this );
	}

	/**
	 * Returns the shipment tracking id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_tracking_id( $context = 'view' ) {
		return $this->get_prop( 'tracking_id', $context );
	}

	/**
	 * Retrieves the pickup location code, in case existent.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_pickup_location_code( $context = 'view' ) {
		$code = $this->get_prop( 'pickup_location_code', $context );

		if ( 'view' === $context ) {
			$code = wc_stc_parse_pickup_location_code( $code );
		}

		return $code;
	}

	/**
	 * Retrieves the pickup location customer number, in case existent.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_pickup_location_customer_number( $context = 'view' ) {
		return $this->get_prop( 'pickup_location_customer_number', $context );
	}

	public function has_pickup_location() {
		$code = $this->get_pickup_location_code();

		return apply_filters( "{$this->get_general_hook_prefix()}has_pickup_location", ! empty( $code ), $this );
	}

	/**
	 * @return false|PickupLocation
	 */
	public function get_pickup_location() {
		if ( is_null( $this->pickup_location ) ) {
			$this->pickup_location = false;

			if ( $this->has_pickup_location() ) {
				if ( $provider = $this->get_shipping_provider_instance() ) {
					if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
						$this->pickup_location = $provider->get_pickup_location_by_code( $this->get_pickup_location_code( 'edit' ), $this->get_address() );
					}
				}
			}
		}

		return $this->pickup_location;
	}

	/**
	 * Returns the shipment tracking URL.
	 *
	 * @return string
	 */
	public function get_tracking_url() {
		$tracking_url = '';

		if ( $provider = $this->get_shipping_provider_instance() ) {
			$tracking_url = $provider->get_tracking_url( $this );
		}

		/**
		 * Filter to adjust a Shipment's tracking URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_get_tracking_url
		 *
		 * @param string   $tracking_url The tracking URL.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}tracking_url", $tracking_url, $this );
	}

	/**
	 * Returns the shipment tracking instruction.
	 *
	 * @return string
	 */
	public function get_tracking_instruction( $plain = false ) {
		$instruction = '';

		if ( $provider = $this->get_shipping_provider_instance() ) {
			$instruction = $provider->get_tracking_desc( $this, $plain );
		}

		/**
		 * Filter to adjust a Shipment's tracking instruction.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_get_tracking_instruction
		 *
		 * @param string                                   $instruction The tracking instruction.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}tracking_instruction", $instruction, $this );
	}

	/**
	 * Returns whether the current shipment has tracking instructions available or not.
	 *
	 * @return boolean
	 */
	public function has_tracking_instruction() {
		$instruction = $this->get_tracking_instruction( true );

		return ( ! empty( $instruction ) ) ? true : false;
	}

	/**
	 * Returns the shipment shipping provider.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipping_provider( $context = 'view' ) {
		return $this->get_prop( 'shipping_provider', $context );
	}

	public function get_shipping_provider_title() {
		if ( $provider = $this->get_shipping_provider_instance() ) {
			return $provider->get_title();
		}

		return '';
	}

	public function get_shipping_provider_instance() {
		$provider = $this->get_shipping_provider();

		if ( ! empty( $provider ) ) {
			return wc_stc_get_shipping_provider( $provider );
		}

		return false;
	}

	/**
	 * Returns the formatted shipping address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
	public function get_formatted_address( $empty_content = '' ) {
		$address = WC()->countries->get_formatted_address( $this->get_address() );

		return $address ? $address : $empty_content;
	}

	/**
	 * Get a formatted shipping address for the order.
	 *
	 * @return string
	 */
	public function get_address_map_url( $address ) {
		// Remove name and company before generate the Google Maps URL.
		unset( $address['first_name'], $address['last_name'], $address['company'], $address['email'], $address['phone'], $address['title'] );

		/**
		 * Filter to adjust a Shipment's address parts used for constructing the Google maps URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_get_address_map_url_parts
		 *
		 * @param string[] $address The address parts used.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		$address = apply_filters( "{$this->get_hook_prefix()}address_map_url_parts", $address, $this );
		$address = array_filter( $address );

		/**
		 * Filter to adjust a Shipment's address Google maps URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_get_address_map_url
		 *
		 * @param string   $url The address url.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}address_map_url", 'https://maps.google.com/maps?&q=' . rawurlencode( implode( ', ', $address ) ) . '&z=16', $this );
	}

	/**
	 * Returns the shipment address phone number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_phone( $context = 'view' ) {
		return $this->get_address_prop( 'phone', $context );
	}

	/**
	 * Returns the shipment address email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_address_prop( 'email', $context );
	}

	/**
	 * Returns the shipment address first line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_address_1( $context = 'view' ) {
		return $this->get_address_prop( 'address_1', $context );
	}

	/**
	 * Returns the shipment address second line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_address_2( $context = 'view' ) {
		return $this->get_address_prop( 'address_2', $context );
	}

	/**
	 * Returns the shipment address street number by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
	public function get_address_street_number( $type = 'address_1' ) {
		$split = wc_stc_split_shipment_street( $this->{"get_$type"}() );

		/**
		 * Filter to adjust the shipment address street number.
		 *
		 * @param string   $number The shipment address street number.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_get_shipment_address_street_number', $split['number'], $this );
	}

	/**
	 * Returns the shipment address street without number by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
	public function get_address_street( $type = 'address_1' ) {
		$split = wc_stc_split_shipment_street( $this->{"get_$type"}() );

		/**
		 * Filter to adjust the shipment address street.
		 *
		 * @param string   $street The shipment address street without street number.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_get_shipment_address_street', $split['street'], $this );
	}

	public function get_address_street_addition( $type = 'address_1' ) {
		$split = wc_stc_split_shipment_street( $this->{"get_$type"}() );

		/**
		 * Filter to adjust the shipment address street addition.
		 *
		 * @param string   $addition The shipment address street addition e.g. EG14.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_get_shipment_address_street_addition', $split['addition'], $this );
	}

	public function get_address_street_addition_2( $type = 'address_1' ) {
		$split = wc_stc_split_shipment_street( $this->{"get_$type"}() );

		/**
		 * Filter to adjust the shipment address street addition.
		 *
		 * @param string   $addition The shipment address street addition e.g. EG14.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_get_shipment_address_street_addition_2', $split['addition_2'], $this );
	}

	/**
	 * Returns the shipment address company.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_company( $context = 'view' ) {
		return $this->get_address_prop( 'company', $context );
	}

	/**
	 * Returns the shipment address first name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_address_prop( 'first_name', $context );
	}

	/**
	 * Returns the shipment address last name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_address_prop( 'last_name', $context );
	}

	/**
	 * Returns the shipment address formatted full name.
	 *
	 * @return string
	 */
	public function get_formatted_full_name() {
		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce-germanized' ), $this->get_first_name(), $this->get_last_name() );
	}

	/**
	 * Returns the shipment address postcode.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_postcode( $context = 'view' ) {
		return $this->get_address_prop( 'postcode', $context );
	}

	/**
	 * Returns the shipment address city.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_city( $context = 'view' ) {
		return $this->get_address_prop( 'city', $context );
	}

	/**
	 * Returns the shipment address state.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_state( $context = 'view' ) {
		return $this->get_address_prop( 'state', $context );
	}

	public function get_formatted_state() {
		if ( '' === $this->get_state() || '' === $this->get_country() ) {
			return '';
		}

		return wc_stc_get_formatted_state( $this->get_state(), $this->get_country() );
	}

	/**
	 * Returns the shipment address country.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_country( $context = 'view' ) {
		return $this->get_address_prop( 'country', $context ) ? $this->get_address_prop( 'country', $context ) : '';
	}

	/**
	 * Returns the shipment address customs reference number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_customs_reference_number( $context = 'view' ) {
		return $this->get_address_prop( 'customs_reference_number', $context ) ? $this->get_address_prop( 'customs_reference_number', $context ) : '';
	}

	/**
	 * Returns a sender address prop by checking the corresponding provider and falling back to
	 * global sender address setting data.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
	protected function get_sender_address_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( $provider = $this->get_shipping_provider_instance() ) {
			$getter = "get_shipper_{$prop}";

			if ( is_callable( array( $provider, $getter ) ) ) {
				$value = $provider->$getter( $context );
			}
		} else {
			$sender_address = wc_stc_get_shipment_setting_address_fields();

			if ( array_key_exists( $prop, $sender_address ) ) {
				$value = $sender_address[ $prop ];
			}
		}

		if ( 'view' === $context ) {
			/**
			 * Filter to adjust a shipment's sender address property e.g. first_name.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for a shipment type. `$prop` refers to the actual address property e.g. first_name.
			 *
			 * Example hook name: woocommerce_shiptastic_shipment_get_sender_address_first_name
			 *
			 * @param string   $value The address property value.
			 * @param Shipment $this The shipment object.
			 *
			 * @package Vendidero/Shiptastic
			 */
			$value = apply_filters( "{$this->get_hook_prefix()}sender_address_{$prop}", $value, $this );
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
	 * Returns the address of the sender e.g. customer.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_sender_address( $context = 'view' ) {
		return apply_filters(
			"{$this->get_hook_prefix()}sender_address",
			array(
				'company'    => $this->get_sender_company( $context ),
				'first_name' => $this->get_sender_first_name( $context ),
				'last_name'  => $this->get_sender_last_name( $context ),
				'address_1'  => $this->get_sender_address_1( $context ),
				'address_2'  => $this->get_sender_address_2( $context ),
				'postcode'   => $this->get_sender_postcode( $context ),
				'city'       => $this->get_sender_city( $context ),
				'country'    => $this->get_sender_country( $context ),
				'state'      => $this->get_sender_state( $context ),
			),
			$this
		);
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
		$split = wc_stc_split_shipment_street( $this->{"get_sender_$type"}() );

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
		$split = wc_stc_split_shipment_street( $this->{"get_sender_$type"}() );

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
		$split = wc_stc_split_shipment_street( $this->{"get_sender_$type"}() );

		return $split['addition'];
	}

	public function get_sender_address_street_addition_2( $type = 'address_1' ) {
		$split = wc_stc_split_shipment_street( $this->{"get_sender_$type"}() );

		return $split['addition_2'];
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
	 * Returns the sender address customs reference number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_customs_reference_number( $context = 'view' ) {
		return $this->get_sender_address_prop( 'customs_reference_number', $context ) ? $this->get_sender_address_prop( 'customs_reference_number', $context ) : '';
	}

	/**
	 * Returns the sender address customs reference number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_sender_customs_uk_vat_id( $context = 'view' ) {
		return $this->get_sender_address_prop( 'customs_uk_vat_id', $context ) ? $this->get_sender_address_prop( 'customs_uk_vat_id', $context ) : '';
	}

	public function get_formatted_sender_state() {
		if ( '' === $this->get_sender_state() || '' === $this->get_sender_country() ) {
			return '';
		}

		return wc_stc_get_formatted_state( $this->get_sender_state(), $this->get_sender_country() );
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
	 * Return the date this shipment is estimated to be delivered.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_est_delivery_date( $context = 'view' ) {
		return $this->get_prop( 'est_delivery_date', $context );
	}

	/**
	 * Decides whether the shipment is sent to an external pickup or not.
	 *
	 * @param string[]|string $types
	 *
	 * @return boolean
	 */
	public function send_to_external_pickup( $types = array() ) {
		$types      = is_array( $types ) ? $types : array( $types );
		$has_pickup = $this->has_pickup_location();

		if ( ! empty( $types ) ) {
			$has_pickup = false;

			if ( $location = $this->get_pickup_location() ) {
				$has_pickup = in_array( $location->get_type(), $types, true );
			}
		}

		/**
		 * Filter to decide whether a Shipment is to be sent to a external pickup location
		 * e.g. packstation.
		 *
		 * @param boolean                                  $external True if the Shipment goes to a pickup location.
		 * @param array                                    $types Array containing the types to be checked against, or empty.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_shipment_send_to_external_pickup', $has_pickup, $types, $this );
	}

	/**
	 * Returns an address prop.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
	protected function get_address_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( isset( $this->changes['address'][ $prop ] ) || isset( $this->data['address'][ $prop ] ) ) {
			$value = isset( $this->changes['address'][ $prop ] ) ? $this->changes['address'][ $prop ] : $this->data['address'][ $prop ];

			if ( 'view' === $context ) {
				/**
				 * Filter to adjust a Shipment's shipping address property e.g. first_name.
				 *
				 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
				 * unique hook for a shipment type. `$prop` refers to the actual address property e.g. first_name.
				 *
				 * Example hook name: woocommerce_shiptastic_shipment_get_address_first_name
				 *
				 * @param string                                   $value The address property value.
				 * @param Shipment $this The shipment object.
				 *
				 * @package Vendidero/Shiptastic
				 */
				$value = apply_filters( "{$this->get_hook_prefix()}address_{$prop}", $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Returns dimensions.
	 *
	 * @return string|array
	 */
	public function get_dimensions( $context = 'view' ) {
		return array(
			'length' => $this->get_length( $context ),
			'width'  => $this->get_width( $context ),
			'height' => $this->get_height( $context ),
		);
	}

	/**
	 * Returns dimensions.
	 *
	 * @return string|array
	 */
	public function get_package_dimensions() {
		return array(
			'length' => $this->get_package_length(),
			'width'  => $this->get_package_width(),
			'height' => $this->get_package_height(),
		);
	}

	public function get_formatted_dimensions() {
		return wc_stc_format_shipment_dimensions( $this->get_dimensions(), $this->get_dimension_unit() );
	}

	public function get_formatted_package_dimensions() {
		return wc_stc_format_shipment_dimensions( $this->get_package_dimensions(), $this->get_dimension_unit() );
	}

	/**
	 * Returns whether the shipment is editable or not.
	 *
	 * @return boolean
	 */
	public function is_editable() {
		/**
		 * Filter to dedice whether the current Shipment is still editable or not.
		 *
		 * @param boolean                                  $is_editable Whether the Shipment is editable or not.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_shipment_is_editable', $this->has_status( wc_stc_get_shipment_editable_statuses() ), $this );
	}

	/**
	 * Returns the shipment number.
	 *
	 * @return string
	 */
	public function get_shipment_number() {
		/**
		 * Filter to adjust a Shipment's number.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_get_shipment_number
		 *
		 * @param string                                   $number The shipment number.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return (string) apply_filters( "{$this->get_hook_prefix()}shipment_number", $this->get_id(), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set shipment status.
	 *
	 * @param string  $new_status Status to change the shipment to. No internal wc-stc- prefix is required.
	 * @param boolean $manual_update Whether it is a manual status update or not.
	 * @return array  details of change
	 */
	public function set_status( $new_status, $manual_update = false ) {
		$old_status = $this->get_status();
		$new_status = apply_filters( "{$this->get_general_hook_prefix()}valid_status_slug", $new_status, $old_status, $this );

		$this->set_prop( 'status', $new_status );

		$result = array(
			'from' => $old_status,
			'to'   => $new_status,
		);

		if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'manual' => (bool) $manual_update,
			);

			if ( $manual_update ) {
				/**
				 * Action that fires after a shipment status has been updated manually.
				 *
				 * @param integer $shipment_id The shipment id.
				 * @param string  $status The new shipment status.
				 *
				 * @package Vendidero/Shiptastic
				 */
				do_action( 'woocommerce_shiptastic_shipment_edit_status', $this->get_id(), $result['to'] );
			}

			$this->maybe_set_date_sent();
		}

		return $result;
	}

	public function is_shipped() {
		$is_shipped = $this->has_status( wc_stc_get_shipment_sent_statuses() );

		return apply_filters( $this->get_hook_prefix() . 'is_shipped', $is_shipped, $this );
	}

	/**
	 * Maybe set date sent.
	 *
	 * Sets the date sent variable when transitioning to the shipped shipment status.
	 * Date sent is set once in this manner - only when it is not already set.
	 */
	public function maybe_set_date_sent() {
		// This logic only runs if the date_sent prop has not been set yet.
		if ( ! $this->get_date_sent( 'edit' ) ) {
			if ( $this->is_shipped() ) {
				// If payment complete status is reached, set paid now.
				$this->set_date_sent( time() );
			}
		}
	}

	/**
	 * Updates status of shipment immediately.
	 *
	 * @uses Shipment::set_status()
	 *
	 * @param string $new_status    Status to change the shipment to. No internal wc-stc- prefix is required.
	 * @param bool   $manual        Is this a manual order status change?
	 * @return bool
	 */
	public function update_status( $new_status, $manual = false ) {
		if ( ! $this->get_id() ) {
			return false;
		}

		try {
			$this->set_status( $new_status, $manual );
			$this->save();
		} catch ( Exception $e ) {
			$logger = wc_get_logger();
			$logger->error(
				sprintf( 'Error updating status for shipment #%d', $this->get_id() ),
				array(
					'shipment' => $this,
					'error'    => $e,
				)
			);
			return false;
		}
		return true;
	}

	/**
	 * Set the date this shipment was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set the date this shipment was sent.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_sent( $date = null ) {
		$this->set_date_prop( 'date_sent', $date );
	}

	/**
	 * Set shipment weight in kg.
	 *
	 * @param string $weight The weight.
	 */
	public function set_weight( $weight ) {
		$this->set_prop( 'weight', '' === $weight ? '' : wc_format_decimal( $weight ) );
	}

	public function set_packaging_weight( $weight ) {
		$this->set_prop( 'packaging_weight', '' === $weight ? '' : wc_format_decimal( $weight ) );
	}

	public function set_packaging_title( $title ) {
		$this->set_prop( 'packaging_title', $title );
	}

	/**
	 * Set shipment total weight.
	 *
	 * @param string $weight The weight.
	 */
	public function set_total_weight( $weight ) {
		$this->set_prop( 'total_weight', '' === $weight ? '' : wc_format_decimal( $weight ) );
	}

	/**
	 * Set shipment width.
	 *
	 * @param string $width The width.
	 */
	public function set_width( $width ) {
		$this->set_prop( 'width', '' === $width ? '' : wc_format_decimal( $width ) );
	}

	public function set_weight_unit( $unit ) {
		$this->set_prop( 'weight_unit', $unit );
	}

	public function set_dimension_unit( $unit ) {
		$this->set_prop( 'dimension_unit', $unit );
	}

	/**
	 * Set shipment length.
	 *
	 * @param string $length The length.
	 */
	public function set_length( $length ) {
		$this->set_prop( 'length', '' === $length ? '' : wc_format_decimal( $length ) );
	}

	/**
	 * Set shipment height.
	 *
	 * @param string $height The height.
	 */
	public function set_height( $height ) {
		$this->set_prop( 'height', '' === $height ? '' : wc_format_decimal( $height ) );
	}

	/**
	 * Set shipment address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_address( $address ) {
		$this->set_prop( 'address', empty( $address ) ? array() : (array) $address );
	}

	/**
	 * Set shipment shipping method.
	 *
	 * @param string $method The shipping method.
	 */
	public function set_shipping_method( $method ) {
		$this->shipping_method_instance = null;

		$this->set_prop( 'shipping_method', $method );
	}

	/**
	 * Set shipment version.
	 *
	 * @param string $version The version.
	 */
	public function set_version( $version ) {
		$this->set_prop( 'version', $version );
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
	 * Set shipment total.
	 *
	 * @param float|string $value The shipment total.
	 */
	public function set_total( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'total', $value );
	}

	/**
	 * Set shipment total.
	 *
	 * @param float|string $value The shipment total.
	 */
	public function set_subtotal( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'subtotal', $value );
	}

	/**
	 * Set shipment additional total.
	 *
	 * @param float|string $value The shipment total.
	 */
	public function set_additional_total( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'additional_total', $value );
	}

	/**
	 * Set shipment shipping country.
	 *
	 * @param string $country The country in ISO format.
	 */
	public function set_country( $country ) {
		$this->set_address_prop( 'country', $country );
	}

	/**
	 * Update a specific address prop.
	 *
	 * @param $prop
	 * @param $value
	 */
	protected function set_address_prop( $prop, $value ) {
		$address          = $this->get_address();
		$address[ $prop ] = $value;

		$this->set_address( $address );
	}

	/**
	 * Set shipment tracking id.
	 *
	 * @param string $tracking_id The trakcing id.
	 */
	public function set_tracking_id( $tracking_id ) {
		$this->set_prop( 'tracking_id', $tracking_id );
	}

	/**
	 * Set shipment pickup location code.
	 *
	 * @param string $location_code The location code.
	 */
	public function set_pickup_location_code( $location_code ) {
		$this->set_prop( 'pickup_location_code', $location_code );
		$this->pickup_location = null;
	}

	/**
	 * Set shipment pickup location customer number.
	 *
	 * @param string $customer_number The customer number.
	 */
	public function set_pickup_location_customer_number( $customer_number ) {
		$this->set_prop( 'pickup_location_customer_number', $customer_number );
	}

	/**
	 * Set shipment shipping provider.
	 *
	 * @param string $provider The shipping provider.
	 */
	public function set_shipping_provider( $provider ) {
		$this->set_prop( 'shipping_provider', wc_stc_get_shipping_provider_slug( $provider ) );
	}

	/**
	 * Set packaging id.
	 *
	 * @param integer $packaging_id The packaging id.
	 */
	public function set_packaging_id( $packaging_id ) {
		$this->set_prop( 'packaging_id', absint( $packaging_id ) );

		$this->packaging = null;
	}

	public function sync_packaging() {
		$available_packaging = $this->get_available_packaging();
		$default_packaging   = $this->get_default_packaging();
		$packaging_id        = $this->get_packaging_id( 'edit' );

		if ( ! empty( $packaging_id ) ) {
			$exists = false;

			foreach ( $available_packaging as $packaging ) {
				if ( (int) $packaging_id === (int) $packaging->get_id() ) {
					$exists = true;
					break;
				}
			}

			if ( ! $exists && $default_packaging ) {
				$this->set_packaging_id( $default_packaging->get_id() );
			}
		} elseif ( empty( $packaging_id ) && $default_packaging ) {
			$this->set_packaging_id( $default_packaging->get_id() );
		}
	}

	public function update_packaging() {
		if ( $packaging = $this->get_packaging() ) {
			$dimension_unit = wc_stc_get_packaging_dimension_unit();

			$props = array(
				'width'            => wc_get_dimension( $packaging->get_width(), $this->get_dimension_unit(), $dimension_unit ),
				'length'           => wc_get_dimension( $packaging->get_length(), $this->get_dimension_unit(), $dimension_unit ),
				'height'           => wc_get_dimension( $packaging->get_height(), $this->get_dimension_unit(), $dimension_unit ),
				'packaging_weight' => wc_get_weight( $packaging->get_weight(), $this->get_weight_unit(), wc_stc_get_packaging_weight_unit() ),
				'packaging_title'  => $packaging->get_title(),
			);

			$this->set_props( $props );
		} else {
			$props   = array(
				'packaging_weight' => '',
				'packaging_title'  => '',
			);
			$changes = $this->get_changes();

			/**
			 * Maybe reset dimensions in case they've not been explicitly set
			 */
			if ( array_key_exists( 'packaging_id', $changes ) ) {
				foreach ( array( 'length', 'width', 'height' ) as $dim_prop ) {
					if ( ! array_key_exists( $dim_prop, $changes ) ) {
						$props = array_merge( $props, array( $dim_prop => '' ) );
					}
				}
			}

			// Reset
			$this->set_props( $props );
		}

		return true;
	}

	/**
	 * Return an array of items within this shipment.
	 *
	 * @return ShipmentItem[]
	 */
	public function get_items( $context = 'admin' ) {
		$items = array();

		if ( is_null( $this->items ) ) {
			$this->items = array_filter( $this->data_store->read_items( $this ) );

			foreach ( $this->items as $k => $item ) {
				if ( $item->get_item_parent_id() > 0 && isset( $this->items[ $item->get_item_parent_id() ] ) ) {
					$this->items[ $item->get_item_parent_id() ]->add_child( $item );
				}
			}

			$items = (array) $this->items;
		} else {
			$items = (array) $this->items;
		}

		/**
		 * By default, remove children in customer context.
		 */
		if ( 'customer' === $context ) {
			foreach ( $items as $k => $item ) {
				if ( $item->get_item_parent_id() > 0 ) {
					unset( $items[ $k ] );
				}
			}
		}

		/**
		 * Filter to adjust items belonging to a Shipment.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_get_items
		 *
		 * @param string                                   $number The shipment number.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}items", $items, $this, $context );
	}

	/**
	 * Get's the URL to edit the shipment in the backend.
	 *
	 * @return string
	 */
	abstract public function get_edit_shipment_url();

	public function get_view_shipment_url() {
		/**
		 * Filter to adjust the URL being used to access the view shipment page on the customer account page.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_shiptastic_shipment_view_shipment_url
		 *
		 * @param string   $url The URL pointing to the view page.
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}_view_shipment_url", wc_get_endpoint_url( 'view-shipment', $this->get_id(), wc_get_page_permalink( 'myaccount' ) ), $this );
	}

	/**
	 * Get an item object.
	 *
	 * @param  int  $item_id ID of item to get.
	 *
	 * @return ShipmentItem|false
	 */
	public function get_item( $item_id ) {
		$items = $this->get_items();

		if ( isset( $items[ $item_id ] ) ) {
			return $items[ $item_id ];
		}

		return false;
	}

	/**
	 * Remove item from the shipment.
	 *
	 * @param int $item_id Item ID to delete.
	 *
	 * @return false|void
	 */
	public function remove_item( $item_id ) {
		$item = $this->get_item( $item_id );

		if ( ! $item ) {
			return;
		}

		// Unset and remove later.
		$this->items_to_delete[] = $item;

		unset( $this->items[ $item->get_id() ] );

		if ( $item->has_children() ) {
			foreach ( $item->get_children() as $child ) {
				// Unset and remove later.
				$this->items_to_delete[] = $child;

				if ( isset( $this->items[ $child->get_id() ] ) ) {
					unset( $this->items[ $child->get_id() ] );
				}

				$item->remove_child( $child->get_id() );
			}
		}

		$this->reset_content_data();
		$this->calculate_totals();
		$this->sync_packaging();
	}

	public function update_item_quantity( $item_id, $quantity = 1 ) {
		if ( $item = $this->get_item( $item_id ) ) {
			$item->set_quantity( $quantity );

			if ( array_key_exists( 'quantity', $item->get_changes() ) ) {
				$this->sync_packaging();
			}

			return true;
		}

		return false;
	}

	/**
	 * Adds a shipment item to this shipment. The shipment item will not persist until save.
	 *
	 * @param ShipmentItem $item Shipment item object.
	 *
	 * @return false|void
	 */
	public function add_item( $item ) {
		// Make sure that items are loaded
		$items = $this->get_items();

		// Set parent.
		$item->set_shipment_id( $this->get_id() );
		$item->set_shipment( $this );

		// Append new row with generated temporary ID.
		$item_id = $item->get_id();

		if ( $item_id ) {
			$this->items[ $item_id ] = $item;
		} else {
			$this->items[ 'new:' . count( $this->items ) ] = $item;
		}

		$this->items_to_pack = null;

		$this->reset_content_data();
		$this->calculate_totals();
		$this->sync_packaging();

		do_action( "{$this->get_general_hook_prefix()}added_item", $item, $this );
	}

	/**
	 * Reset item content data.
	 */
	public function reset_content_data() {
		$this->weights = null;
		$this->lengths = null;
		$this->widths  = null;
		$this->heights = null;
		$this->volumes = null;
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				/**
				 * Action that fires before a shipment status transition happens.
				 *
				 * @param integer  $shipment_id The shipment id.
				 * @param Shipment $shipment The shipment object.
				 * @param array    $status_transition The status transition data.
				 *
				 * @package Vendidero/Shiptastic
				 */
				do_action( 'woocommerce_shiptastic_shipment_before_status_change', $this->get_id(), $this, $this->status_transition );

				$status_to          = $status_transition['to'];
				$status_hook_prefix = 'woocommerce_shiptastic_' . ( 'simple' === $this->get_type() ? '' : $this->get_type() . '_' ) . 'shipment_status';

				/**
				 * Action that indicates shipment status change to a specific status.
				 *
				 * The dynamic portion of the hook name, `$status_hook_prefix` constructs a unique prefix
				 * based on the shipment type. `$status_to` refers to the new shipment status.
				 *
				 * Example hook name: `woocommerce_stc_return_shipment_status_processing`
				 *
				 * @param integer  $shipment_id The shipment id.
				 * @param Shipment $shipment The shipment object.
				 *
				 * @see wc_stc_get_shipment_statuses()
				 *
				 * @package Vendidero/Shiptastic
				 */
				do_action( "{$status_hook_prefix}_$status_to", $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					$status_from = $status_transition['from'];

					/**
					 * Action that indicates shipment status change from a specific status to a specific status.
					 *
					 * The dynamic portion of the hook name, `$status_hook_prefix` constructs a unique prefix
					 * based on the shipment type. `$status_from` refers to the old shipment status.
					 * `$status_to` refers to the new status.
					 *
					 * Example hook name: `woocommerce_stc_return_shipment_status_processing_to_shipped`
					 *
					 * @param integer  $shipment_id The shipment id.
					 * @param Shipment $shipment The shipment object.
					 *
					 * @see wc_stc_get_shipment_statuses()
					 *
					 * @package Vendidero/Shiptastic
					 */
					do_action( "{$status_hook_prefix}_{$status_from}_to_{$status_to}", $this->get_id(), $this );

					/**
					 * Action that indicates shipment status change.
					 *
					 * @param integer  $shipment_id The shipment id.
					 * @param string   $status_from The old shipment status.
					 * @param string   $status_to The new shipment status.
					 * @param Shipment $shipment The shipment object.
					 *
					 * @see wc_stc_get_shipment_statuses()
					 *
					 * @package Vendidero/Shiptastic
					 */
					do_action( 'woocommerce_shiptastic_shipment_status_changed', $this->get_id(), $status_from, $status_to, $this );
				}
			} catch ( Exception $e ) {
				$logger = wc_get_logger();
				$logger->error(
					sprintf( 'Status transition of shipment #%d errored!', $this->get_id() ),
					array(
						'shipment' => $this,
						'error'    => $e,
					)
				);
			}
		}
	}

	/**
	 * Remove all items from the shipment.
	 */
	public function remove_items() {
		$this->data_store->delete_items( $this );
		$this->items = array();

		$this->items_to_pack = null;

		$this->reset_content_data();
		$this->calculate_totals();
		$this->sync_packaging();
	}

	/**
	 * Save all items which are part of this shipment.
	 */
	protected function save_items() {
		$items_changed = false;

		foreach ( $this->items_to_delete as $item ) {
			$item->delete();
			$items_changed = true;
		}

		$this->items_to_delete = array();

		foreach ( $this->get_items() as $item_key => $item ) {
			$item->set_shipment_id( $this->get_id() );

			$item_id = $item->save();

			// If ID changed (new item saved to DB)...
			if ( $item_id !== $item_key ) {
				$this->items[ $item_id ] = $item;

				unset( $this->items[ $item_key ] );

				$items_changed = true;
			}
		}
	}

	/**
	 * Finds an ShipmentItem based on an order item id.
	 *
	 * @param integer $order_item_id
	 *
	 * @return bool|ShipmentItem
	 */
	public function get_item_by_order_item_id( $order_item_id ) {
		$items = $this->get_items();

		foreach ( $items as $item ) {
			if ( $item->get_order_item_id() === (int) $order_item_id ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Returns version.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_version( $context = 'view' ) {
		return $this->get_prop( 'version', $context );
	}

	/**
	 * Returns the packaging id belonging to the shipment.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_packaging_id( $context = 'view' ) {
		return $this->get_prop( 'packaging_id', $context );
	}

	public function get_packaging() {
		if ( is_null( $this->packaging ) && $this->get_packaging_id() > 0 ) {
			if ( $packaging = wc_stc_get_packaging( $this->get_packaging_id() ) ) {
				// Do only allow load packaging if it does really exist in DB.
				if ( $packaging->get_id() > 0 ) {
					$this->packaging = $packaging;
				}
			}
		}

		return $this->packaging;
	}

	/**
	 * Returns a list of available (fitting) packaging options for the current shipment
	 *
	 * @return Packaging[]
	 */
	public function get_available_packaging() {
		$packaging_store = \WC_Data_Store::load( 'packaging' );

		return apply_filters( "{$this->get_hook_prefix()}available_packaging", $packaging_store->find_available_packaging_for_shipment( $this ), $this );
	}

	/**
	 * Returns a list of user-selectable packaging options for the current shipment.
	 *
	 * @return Packaging[]
	 */
	public function get_selectable_packaging() {
		$all       = wc_stc_get_packaging_list( array( 'shipping_provider' => $this->get_shipping_provider() ) );
		$available = $this->get_available_packaging();

		$diff       = array_diff( $all, $available );
		$selectable = array_merge( $available, $diff );

		return apply_filters( "{$this->get_hook_prefix()}selectable_packaging", $selectable, $this );
	}

	public function get_default_packaging() {
		$packaging_store   = \WC_Data_Store::load( 'packaging' );
		$default_packaging = $packaging_store->find_best_match_for_shipment( $this );

		if ( ! $default_packaging ) {
			$setting = Package::get_setting( 'default_packaging' );

			if ( ! empty( $setting ) && wc_stc_get_packaging( $setting ) ) {
				$default_packaging = wc_stc_get_packaging( $setting );
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}default_packaging_id", $default_packaging, $this );
	}

	/**
	 * Tries to fetch the order for the current shipment.
	 *
	 * @return bool|WC_Order|null
	 */
	abstract public function get_order();

	abstract public function get_order_id();

	/**
	 * Returns the formatted order number.
	 *
	 * @return string
	 */
	public function get_order_number() {
		if ( $order = $this->get_order() ) {
			return $order->get_order_number();
		}

		return $this->get_order_id();
	}

	/**
	 * Returns whether the Shipment contains an order item or not.
	 *
	 * @param integer|integer[] $item_id
	 *
	 * @return boolean
	 */
	public function contains_order_item( $item_id ) {

		if ( ! is_array( $item_id ) ) {
			$item_id = array( $item_id );
		}

		$new_items = $item_id;

		foreach ( $item_id as $key => $order_item_id ) {

			if ( is_a( $order_item_id, 'WC_Order_Item' ) ) {
				$order_item_id   = $order_item_id->get_id();
				$item_id[ $key ] = $order_item_id;
			}

			if ( $this->get_item_by_order_item_id( $order_item_id ) ) {
				unset( $new_items[ $key ] );
			}
		}

		$contains = empty( $new_items ) ? true : false;

		/**
		 * Filter to adjust whether a Shipment contains a specific order item or not.
		 *
		 * @param boolean                                  $contains Whether the Shipment contains the order item or not.
		 * @param integer[]                                $order_item_id The order item id(s).
		 * @param Shipment $this The shipment object.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_shipment_contains_order_item', $contains, $item_id, $this );
	}

	public function get_shippable_item_count() {
		return 0;
	}

	/**
	 * Finds an ShipmentItem based on an item parent id.
	 *
	 * @param integer $item_parent_id
	 *
	 * @return bool|ShipmentItem
	 */
	public function get_item_by_item_parent_id( $item_parent_id ) {
		$items = $this->get_items();

		foreach ( $items as $item ) {
			if ( $item->get_parent_id() === $item_parent_id ) {
				return $item;
			}
		}

		return false;
	}

	public function needs_items( $available_items = false ) {
		return false;
	}

	public function sync( $args = array() ) {
		return false;
	}

	public function sync_items( $args = array() ) {
		return false;
	}

	/**
	 * Returns a label
	 *
	 * @return boolean|ShipmentLabel|ShipmentReturnLabel
	 */
	public function get_label() {
		$prefix = '';

		if ( is_null( $this->label ) ) {
			$this->label = false;

			if ( $provider = $this->get_shipping_provider_instance() ) {
				$prefix      = $provider->get_name() . '_';
				$this->label = $provider->get_label( $this );
			}
		}

		/**
		 * Filter for shipping providers to retrieve the `ShipmentLabel` corresponding to a certain shipment.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type. `$provider` is related to the current shipping provider
		 * for the shipment (slug).
		 *
		 * Example hook name: `woocommerce_stc_return_shipment_get_dhl_label`
		 *
		 * @param boolean|ShipmentLabel $label The label instance.
		 * @param Shipment              $shipment The current shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}{$prefix}label", $this->label, $this );
	}

	/**
	 * Output label admin fields.
	 */
	public function get_label_settings_html() {
		$hook_prefix = $this->get_general_hook_prefix();
		$html        = '';

		if ( $provider = $this->get_shipping_provider_instance() ) {
			$hook_prefix = $hook_prefix . '_' . $provider->get_name();
			$html        = $provider->get_label_fields_html( $this );
		}

		/**
		 * Action for shipping providers to output available admin settings while creating a label.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` is used to construct a
		 * unique hook for a shipment type. `$provider` is related to the current shipping provider
		 * for the shipment (slug).
		 *
		 * Example hook name: `woocommerce_stc_return_shipment_print_dhl_label_admin_fields`
		 *
		 * @param Shipment $shipment The current shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$hook_prefix}label_settings_html", $html, $this );
	}

	/**
	 * @param $props
	 *
	 * @return true|ShipmentError
	 */
	public function create_label( $props = false ) {
		$hook_prefix   = $this->get_general_hook_prefix();
		$provider_name = $this->get_shipping_provider() ? $this->get_shipping_provider() . '_' : '';
		$error         = new ShipmentError();
		$this->label   = null;

		/**
		 * Sanitize props
		 */
		if ( is_array( $props ) ) {
			foreach ( $props as $key => $value ) {
				$props[ $key ] = wc_clean( wp_unslash( $value ) );
			}
		}

		do_action( "{$hook_prefix}before_create_{$provider_name}label", $props, $this );

		if ( $provider = $this->get_shipping_provider_instance() ) {
			$provider_name = $provider->get_name();
			$result        = $provider->create_label( $this, $props );

			do_action( "{$hook_prefix}after_create_{$provider_name}label", $props, $this, $result );

			if ( is_wp_error( $result ) ) {
				$error = wc_stc_get_shipment_error( $result );

				if ( ! $error->is_soft_error() ) {
					return $error;
				}
			}
		} else {
			/**
			 * Action for shipping providers to create the `ShipmentLabel` corresponding to a certain shipment.
			 *
			 * The dynamic portion of this hook, `$hook_prefix` is used to construct a
			 * unique hook for a shipment type. `$provider` is related to the current shipping provider
			 * for the shipment (slug).
			 *
			 * Example hook name: `woocommerce_stc_return_shipment_create_dhl_label`
			 *
			 * @param array|false $props Array containing props extracted from post data (if created manually).
			 * @param WP_Error    $error An WP_Error instance useful for returning errors while creating the label.
			 * @param Shipment    $shipment The current shipment instance.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( "{$hook_prefix}create_{$provider_name}label", $props, $error, $this );

			if ( wc_stc_shipment_wp_error_has_errors( $error ) && ! $error->is_soft_error() ) {
				return $error;
			}
		}

		if ( $label = $this->get_label() ) {
			$this->set_tracking_id( $label->get_number() );

			/**
			 * Action for shipping providers to adjust the shipment before updating it after a label has
			 * been successfully generated.
			 *
			 * The dynamic portion of this hook, `$hook_prefix` is used to construct a
			 * unique hook for a shipment type. `$provider` is related to the current shipping provider
			 * for the shipment (slug).
			 *
			 * Example hook name: `woocommerce_stc_return_shipment_created_dhl_label`
			 *
			 * @param Shipment  $shipment The current shipment instance.
			 * @param array     $props Array containing props extracted from post data (if created manually) and sanitized via `wc_clean`.
			 * @param array     $raw_data Raw post data unsanitized.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( "{$hook_prefix}created_{$provider_name}label", $this, $props );

			do_action( "{$hook_prefix}created_label", $this, $props );

			$this->save();
		}

		if ( wc_stc_shipment_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return true;
	}

	public function delete_label( $force = false ) {
		if ( $this->supports_label() && ( $label = $this->get_label() ) ) {
			$label->delete( $force );
			$this->set_tracking_id( '' );
			$this->save();

			return true;
		}

		return false;
	}

	/**
	 * Whether or not the current shipments supports labels or not.
	 *
	 * @return bool
	 */
	public function supports_label() {
		if ( $provider = $this->get_shipping_provider_instance() ) {
			if ( $provider->supports_labels( $this->get_type(), $this ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether or not the current shipments needs a label or not.
	 *
	 * @return bool
	 */
	public function needs_label( $check_status = true ) {
		$needs_label = true;
		$provider    = $this->get_shipping_provider();
		$hook_prefix = $this->get_general_hook_prefix();

		if ( ! empty( $provider ) ) {
			$provider = $provider . '_';
		}

		if ( $this->has_label() ) {
			$needs_label = false;
		}

		if ( ! $this->supports_label() ) {
			$needs_label = false;
		}

		if ( $shipping_provider = $this->get_shipping_provider_instance() ) {
			if ( ! $shipping_provider->is_activated() ) {
				$needs_label = false;
			}
		}

		// If shipment is already delivered
		if ( $check_status && $this->is_shipped() ) {
			$needs_label = false;
		}

		/**
		 * Filter for shipping providers to decide whether the shipment needs a label or not.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` is used to construct a
		 * unique hook for a shipment type. `$provider` is related to the current shipping provider
		 * for the shipment (slug).
		 *
		 * Example hook name: `woocommerce_stc_return_shipment_needs_dhl_label`
		 *
		 * @param boolean   $needs_label Whether or not the shipment needs a label.
		 * @param boolean   $check_status Whether or not checking the shipment status is needed.
		 * @param Shipment  $shipment The current shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$hook_prefix}needs_{$provider}label", $needs_label, $check_status, $this );
	}

	/**
	 * Whether or not the current shipment has a valid label or not.
	 *
	 * @return bool
	 */
	public function has_label() {
		$label = $this->get_label();

		if ( $label && is_a( $label, '\Vendidero\Shiptastic\Interfaces\ShipmentLabel' ) ) {
			if ( 'return' === $label->get_type() ) {
				if ( ! is_a( $label, '\Vendidero\Shiptastic\Interfaces\ShipmentReturnLabel' ) ) {
					return false;
				}
			}

			return true;
		} else {
			return false;
		}
	}

	public function get_label_download_url( $args = array() ) {
		$download_url = '';
		$provider     = $this->get_shipping_provider();

		if ( $label = $this->get_label() ) {
			$download_url = $label->get_download_url( $args );
		}

		/**
		 * Filter for shipping providers to adjust the label download URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type. `$provider` is related to the current shipping provider
		 * for the shipment (slug).
		 *
		 * Example hook name: `woocommerce_stc_return_shipment_get_dhl_label_download_url`
		 *
		 * @param string   $url The download URL.
		 * @param Shipment $shipment The current shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( "{$this->get_hook_prefix()}{$provider}label_download_url", $download_url, $this );
	}

	public function add_note( $note, $added_by_user = false ) {
		if ( $order = $this->get_order() ) {
			if ( is_callable( array( $order, 'add_order_note' ) ) ) {
				$order->add_order_note( $note, 0, $added_by_user );
			}
		}
	}

	/**
	 * Calculate totals based on contained items.
	 */
	protected function calculate_totals() {
		$total    = 0;
		$subtotal = 0;

		foreach ( $this->get_items() as $item ) {
			$total    += round( $item->get_total(), wc_get_price_decimals() );
			$subtotal += round( $item->get_subtotal(), wc_get_price_decimals() );
		}

		$this->set_total( $total );

		if ( empty( $subtotal ) ) {
			$subtotal = $total;
		}

		$this->set_subtotal( $subtotal );
	}

	public function delete( $force_delete = false ) {
		$this->delete_label( $force_delete );

		return parent::delete( $force_delete );
	}

	/**
	 * Save data to the database.
	 *
	 * @return integer shipment id
	 */
	public function save() {
		try {
			$this->calculate_totals();
			$is_new = false;

			if ( array_key_exists( 'packaging_id', $this->get_changes() ) || $this->is_editable() ) {
				$this->update_packaging();
			}

			if ( $this->data_store ) {
				// Trigger action before saving to the DB. Allows you to adjust object props before save.
				do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

				if ( $this->get_id() ) {
					$this->data_store->update( $this );
				} else {
					$this->data_store->create( $this );
					$is_new = true;
				}
			}

			if ( ! $this->get_id() ) {
				throw new \Exception( 'Error while saving shipment.' );
			}

			$this->save_items();

			if ( $cache = Helper::get_cache_object( 'shipments' ) ) {
				$cache->remove( $this->get_id() );
			}

			/**
			 * Trigger action after saving shipment to the DB.
			 *
			 * @param Shipment          $shipment The shipment object being saved.
			 * @param WC_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'woocommerce_after_' . $this->object_type . '_object_save', $this, $this->data_store );

			$hook_postfix = '';

			if ( 'simple' !== $this->get_type() ) {
				$hook_postfix = $this->get_type() . '_';
			}

			/**
			 * Trigger action after saving shipment to the DB.
			 *
			 * The dynamic portion of this hook, `$hook_postfix` is used to construct a
			 * unique hook for a shipment type.
			 *
			 * Example hook name: woocommerce_shiptastic_shipment_after_save
			 *
			 * @param Shipment $shipment The shipment object being saved.
			 * @param boolean  $is_new Indicator to determine whether this is a new shipment or not.
			 *
			 * @package Vendidero/Shiptastic
			 */
			do_action( "woocommerce_shiptastic_{$hook_postfix}shipment_after_save", $this, $is_new );

			$this->status_transition();
			$this->reset_content_data();
			$this->label = null;
		} catch ( Exception $e ) {
			/**
			 * This is a tweak to prevent the WooCommerce PayPal Payments Plugin compatibility script
			 * from breaking our code in case an error occurs while transmitting tracking data to PayPal.
			 * This tweak should only be included as long as the bug persists.
			 * @TODO Check whether the issue persists in next release cycles
			 *
			 * @see https://github.com/woocommerce/woocommerce-paypal-payments/issues/1020
			 */
			if ( is_a( $e, 'WooCommerce\PayPalCommerce\ApiClient\Exception\PayPalApiException' ) || is_a( $e, 'WooCommerce\PayPalCommerce\ApiClient\Exception\RuntimeException' ) ) {
				$this->status_transition();
				$this->reset_content_data();
				$this->label = null;
			} else {
				$logger = wc_get_logger();
				$logger->error(
					sprintf( 'Error saving shipment #%d', $this->get_id() ),
					array(
						'shipment' => $this,
						'error'    => $e,
					)
				);
			}
		}

		return $this->get_id();
	}
}
