<?php
/**
 * Packaging
 *
 * @package Vendidero/Shiptastic
 * @version 1.0.0
 */
namespace Vendidero\Shiptastic;

use Vendidero\Shiptastic\Interfaces\LabelConfigurationSet;
use Vendidero\Shiptastic\Labels\ConfigurationSetTrait;
use Vendidero\Shiptastic\ShippingProvider\Helper;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Packaging Class.
 */
class Packaging extends WC_Data implements LabelConfigurationSet {

	use ConfigurationSetTrait;

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'packaging';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @var object
	 */
	protected $data_store_name = 'packaging';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'packaging';

	/**
	 * Stores packaging data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'                => null,
		'weight'                      => 0,
		'max_content_weight'          => 0,
		'width'                       => 0,
		'weight_unit'                 => 'kg',
		'height'                      => 0,
		'length'                      => 0,
		'dimension_unit'              => 'cm',
		'inner_width'                 => 0,
		'inner_height'                => 0,
		'inner_length'                => 0,
		'order'                       => 0,
		'type'                        => '',
		'description'                 => '',
		'available_shipping_provider' => array(),
		'available_shipping_classes'  => array(),
		'configuration_sets'          => array(),
	);

	/**
	 * Get the packaging if ID is passed, otherwise the packaging is new and empty.
	 * This class should NOT be instantiated, but the `wc_stc_get_packaging` function should be used.
	 *
	 * @param int|object|Packaging $packaging packaging to read.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof Packaging ) {
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

			$this->set_weight_unit( wc_stc_get_packaging_weight_unit() );
			$this->set_dimension_unit( wc_stc_get_packaging_dimension_unit() );
		}
	}

	/**
	 * This method overwrites the base class's clone method to make it a no-op. In base class WC_Data, we are unsetting the meta_id to clone.
	 *
	 * @see WC_Abstract_Order::__clone()
	 */
	public function __clone() {}

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
		return 'woocommerce_shiptastic_packaging_';
	}

	/**
	 * Return the date this packaging was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Returns the packaging weight in kg.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'weight', $context );

		if ( 'view' === $context && ! empty( $weight ) && $this->get_weight_unit() !== wc_stc_get_packaging_weight_unit() ) {
			$weight = wc_get_weight( (float) $weight, wc_stc_get_packaging_weight_unit(), $this->get_weight_unit() );
		}

		return $weight;
	}

	/**
	 * Returns the packaging max content weight in kg.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_max_content_weight( $context = 'view' ) {
		$max_content_weight = $this->get_prop( 'max_content_weight', $context );

		if ( 'view' === $context && ! empty( $max_content_weight ) && $this->get_weight_unit() !== wc_stc_get_packaging_weight_unit() ) {
			$max_content_weight = wc_get_weight( (float) $max_content_weight, wc_stc_get_packaging_weight_unit(), $this->get_weight_unit() );
		}

		return $max_content_weight;
	}

	/**
	 * Returns the packaging order within its list.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_order( $context = 'view' ) {
		return $this->get_prop( 'order', $context );
	}

	/**
	 * Returns the packaging type e.g. box or letter.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Returns the packaging description.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Returns the packaging length in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_length( $context = 'view' ) {
		$length = $this->get_prop( 'length', $context );

		if ( 'view' === $context && ! empty( $length ) && $this->get_dimension_unit() !== wc_stc_get_packaging_dimension_unit() ) {
			$length = wc_get_dimension( (float) $length, wc_stc_get_packaging_dimension_unit(), $this->get_dimension_unit() );
		}

		return $length;
	}

	/**
	 * Returns the packaging width in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_width( $context = 'view' ) {
		$width = $this->get_prop( 'width', $context );

		if ( 'view' === $context && ! empty( $width ) && $this->get_dimension_unit() !== wc_stc_get_packaging_dimension_unit() ) {
			$width = wc_get_dimension( (float) $width, wc_stc_get_packaging_dimension_unit(), $this->get_dimension_unit() );
		}

		return $width;
	}

	/**
	 * Returns the packaging height in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_height( $context = 'view' ) {
		$height = $this->get_prop( 'height', $context );

		if ( 'view' === $context && ! empty( $height ) && $this->get_dimension_unit() !== wc_stc_get_packaging_dimension_unit() ) {
			$height = wc_get_dimension( (float) $height, wc_stc_get_packaging_dimension_unit(), $this->get_dimension_unit() );
		}

		return $height;
	}

	/**
	 * Returns the inner packaging length in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_inner_length( $context = 'view' ) {
		$inner_length = $this->get_prop( 'inner_length', $context );

		if ( 'view' === $context ) {
			if ( empty( $inner_length ) ) {
				$inner_length = $this->get_length( $context );
			} elseif ( $this->get_dimension_unit() !== wc_stc_get_packaging_dimension_unit() ) {
				$inner_length = wc_get_dimension( (float) $inner_length, wc_stc_get_packaging_dimension_unit(), $this->get_dimension_unit() );
			}
		}

		return $inner_length;
	}

	/**
	 * Returns the packaging inner width in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_inner_width( $context = 'view' ) {
		$inner_width = $this->get_prop( 'inner_width', $context );

		if ( 'view' === $context ) {
			if ( empty( $inner_width ) ) {
				$inner_width = $this->get_width( $context );
			} elseif ( $this->get_dimension_unit() !== wc_stc_get_packaging_dimension_unit() ) {
				$inner_width = wc_get_dimension( (float) $inner_width, wc_stc_get_packaging_dimension_unit(), $this->get_dimension_unit() );
			}
		}

		return $inner_width;
	}

	/**
	 * Returns the packaging inner height in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_inner_height( $context = 'view' ) {
		$inner_height = $this->get_prop( 'inner_height', $context );

		if ( 'view' === $context ) {
			if ( empty( $inner_height ) ) {
				$inner_height = $this->get_height( $context );
			} elseif ( $this->get_dimension_unit() !== wc_stc_get_packaging_dimension_unit() ) {
				$inner_height = wc_get_dimension( (float) $inner_height, wc_stc_get_packaging_dimension_unit(), $this->get_dimension_unit() );
			}
		}

		return $inner_height;
	}

	/**
	 * Returns the packaging weight unit.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_weight_unit( $context = 'view' ) {
		$unit = $this->get_prop( 'weight_unit', $context );

		if ( 'view' === $context && empty( $unit ) ) {
			$unit = 'kg';
		}

		return $unit;
	}

	/**
	 * Returns the packaging dimension unit.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_dimension_unit( $context = 'view' ) {
		$unit = $this->get_prop( 'dimension_unit', $context );

		if ( 'view' === $context && empty( $unit ) ) {
			$unit = 'cm';
		}

		return $unit;
	}

	public function has_inner_dimensions() {
		return ! empty( $this->get_inner_width( 'edit' ) ) || ! empty( $this->get_inner_length( 'edit' ) ) || ! empty( $this->get_inner_height( 'edit' ) );
	}

	public function has_shipping_class_restrictions() {
		$classes = $this->get_available_shipping_classes( 'edit' );

		return ! empty( $classes ) ? true : false;
	}

	public function has_shipping_provider_restrictions() {
		$providers = $this->get_available_shipping_provider( 'edit' );

		return ! empty( $providers ) ? true : false;
	}

	/**
	 * Returns the available shipping provider names.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_available_shipping_provider( $context = 'view' ) {
		$provider_names = $this->get_prop( 'available_shipping_provider', $context );

		if ( 'view' === $context && empty( $provider_names ) ) {
			$provider_names = array_keys( Helper::instance()->get_available_shipping_providers() );
		}

		return $provider_names;
	}

	/**
	 * Returns the available shipping classes.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_available_shipping_classes( $context = 'view' ) {
		$classes = $this->get_prop( 'available_shipping_classes', $context );

		if ( 'view' === $context && empty( $classes ) ) {
			$classes = array_keys( Package::get_shipping_classes() );
		}

		return $classes;
	}

	public function supports_shipping_class( $shipping_class ) {
		$classes  = $this->get_available_shipping_classes( 'edit' );
		$supports = false;

		if ( empty( $shipping_class ) && empty( $classes ) ) {
			$supports = true;
		} elseif ( empty( $classes ) || in_array( $shipping_class, $classes, true ) ) {
			$supports = true;
		}

		return $supports;
	}

	public function supports_shipping_provider( $provider ) {
		if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProvider' ) ) {
			$provider = $provider->get_name();
		}

		return apply_filters( "{$this->get_general_hook_prefix()}supports_shipping_provider", ( in_array( $provider, $this->get_available_shipping_provider(), true ) || empty( $provider ) ), $provider, $this );
	}

	public function has_dimensions() {
		$width  = $this->get_width();
		$length = $this->get_length();
		$height = $this->get_height();

		return ( ! empty( $width ) && ! empty( $length ) && ! empty( $height ) );
	}

	/**
	 * Returns dimensions.
	 *
	 * @return string|array
	 */
	public function get_dimensions() {
		return array(
			'length' => wc_format_decimal( $this->get_length(), false, true ),
			'width'  => wc_format_decimal( $this->get_width(), false, true ),
			'height' => wc_format_decimal( $this->get_height(), false, true ),
		);
	}

	/**
	 * Returns inner dimensions.
	 *
	 * @return string|array
	 */
	public function get_inner_dimensions() {
		return array(
			'length' => wc_format_decimal( $this->get_inner_length(), false, true ),
			'width'  => wc_format_decimal( $this->get_inner_width(), false, true ),
			'height' => wc_format_decimal( $this->get_inner_height(), false, true ),
		);
	}

	public function get_formatted_dimensions() {
		return wc_stc_format_shipment_dimensions( $this->get_dimensions(), wc_stc_get_packaging_dimension_unit() );
	}

	public function get_formatted_inner_dimensions() {
		return wc_stc_format_shipment_dimensions( $this->get_inner_dimensions(), wc_stc_get_packaging_dimension_unit() );
	}

	public function get_volume() {
		return (float) $this->get_length() * (float) $this->get_width() * (float) $this->get_height();
	}

	public function get_inner_volume() {
		return (float) $this->get_inner_length() * (float) $this->get_inner_width() * (float) $this->get_inner_height();
	}

	/**
	 * Set the date this packaging was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set packaging weight in kg.
	 *
	 * @param string $weight The weight.
	 */
	public function set_weight( $weight ) {
		$this->set_prop( 'weight', empty( $weight ) ? 0 : wc_format_decimal( $weight, 2, true ) );
	}

	/**
	 * Set packaging weight unit.
	 *
	 * @param string $unit The unit.
	 */
	public function set_weight_unit( $unit ) {
		$this->set_prop( 'weight_unit', $unit );
	}

	/**
	 * Set packaging dimension unit.
	 *
	 * @param string $unit The unit.
	 */
	public function set_dimension_unit( $unit ) {
		$this->set_prop( 'dimension_unit', $unit );
	}

	public function get_title() {
		$description = $this->get_description();
		$title       = sprintf(
			_x( '%1$s (%2$s, %3$s)', 'shipments-packaging-title', 'woocommerce-germanized' ),
			$description,
			$this->get_formatted_dimensions(),
			wc_stc_format_shipment_weight( wc_format_decimal( $this->get_weight(), false, true ), wc_stc_get_packaging_weight_unit() )
		);

		return apply_filters( "{$this->get_hook_prefix()}title", $title, $this );
	}

	/**
	 * Set packaging order.
	 *
	 * @param integer $order The order.
	 */
	public function set_order( $order ) {
		$this->set_prop( 'order', absint( $order ) );
	}

	/**
	 * Set packaging max content weight in kg.
	 *
	 * @param string $weight The weight.
	 */
	public function set_max_content_weight( $weight ) {
		$this->set_prop( 'max_content_weight', empty( $weight ) ? 0 : wc_format_decimal( $weight, 2, true ) );
	}

	/**
	 * Set packaging type
	 *
	 * @param string $type The type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set packaging description
	 *
	 * @param string $description The description.
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set packaging shipping providers
	 *
	 * @param array $provider_names The provider names
	 */
	public function set_available_shipping_provider( $provider_names ) {
		$this->set_prop( 'available_shipping_provider', array_filter( (array) $provider_names ) );
	}

	/**
	 * Set packaging shipping classes
	 *
	 * @param array $classes The shipping classes
	 */
	public function set_available_shipping_classes( $classes ) {
		$this->set_prop( 'available_shipping_classes', array_filter( array_map( 'absint', (array) $classes ) ) );
	}

	/**
	 * Set packaging width in cm.
	 *
	 * @param string $width The width.
	 */
	public function set_width( $width ) {
		$this->set_prop( 'width', empty( $width ) ? 0 : wc_format_decimal( $width, 1, true ) );
	}

	/**
	 * Set packaging length in cm.
	 *
	 * @param string $length The length.
	 */
	public function set_length( $length ) {
		$this->set_prop( 'length', empty( $length ) ? 0 : wc_format_decimal( $length, 1, true ) );
	}

	/**
	 * Set packaging height in cm.
	 *
	 * @param string $height The height.
	 */
	public function set_height( $height ) {
		$this->set_prop( 'height', empty( $height ) ? 0 : wc_format_decimal( $height, 1, true ) );
	}

	/**
	 * Set packaging inner width in cm.
	 *
	 * @param string $width The width.
	 */
	public function set_inner_width( $width ) {
		$this->set_prop( 'inner_width', empty( $width ) ? 0 : wc_format_decimal( $width, 1, true ) );
	}

	/**
	 * Set packaging inner length in cm.
	 *
	 * @param string $length The length.
	 */
	public function set_inner_length( $length ) {
		$this->set_prop( 'inner_length', empty( $length ) ? 0 : wc_format_decimal( $length, 1, true ) );
	}

	/**
	 * Set packaging inner height in cm.
	 *
	 * @param string $height The height.
	 */
	public function set_inner_height( $height ) {
		$this->set_prop( 'inner_height', empty( $height ) ? 0 : wc_format_decimal( $height, 1, true ) );
	}

	protected function get_configuration_set_setting_type() {
		return 'packaging';
	}

	public function save() {
		$changes = $this->get_changes();

		/**
		 * Maybe reset inner dimensions when changing outer dimensions.
		 */
		if ( ! empty( $changes ) ) {
			foreach ( array( 'length', 'width', 'height' ) as $dim ) {
				if ( isset( $changes[ $dim ] ) && ! isset( $changes[ "inner_{$dim}" ] ) ) {
					$this->{"set_inner_{$dim}"}( 0 );
				}
			}
		}

		$id = parent::save();

		if ( $cache = \Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'packagings' ) ) {
			$cache->remove( $this->get_id() );
		}

		return $id;
	}
}
