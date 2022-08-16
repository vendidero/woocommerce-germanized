<?php
/**
 * Packaging
 *
 * @package Vendidero/Germanized/Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments;

use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Packaging Class.
 */
class Packaging extends WC_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'packaging';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'packaging';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'packaging';

	/**
	 * Stores packaging data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'       => null,
		'weight'             => 0,
		'max_content_weight' => 0,
		'width'              => 0,
		'height'             => 0,
		'length'             => 0,
		'order'              => 0,
		'type'               => '',
		'description'        => '',
	);

	/**
	 * Get the packaging if ID is passed, otherwise the packaging is new and empty.
	 * This class should NOT be instantiated, but the `wc_gzd_get_packaging` function should be used.
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
		}
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 *
	 * @since 3.2.0
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
		return 'woocommerce_gzd_packaging_';
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
		return $this->get_prop( 'weight', $context );
	}

	/**
	 * Returns the packaging max content weight in kg.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_max_content_weight( $context = 'view' ) {
		return $this->get_prop( 'max_content_weight', $context );
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
		return $this->get_prop( 'length', $context );
	}

	/**
	 * Returns the packaging width in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	/**
	 * Returns the packaging height in cm.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
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

	public function get_formatted_dimensions() {
		return wc_gzd_format_shipment_dimensions( $this->get_dimensions(), wc_gzd_get_packaging_dimension_unit() );
	}

	public function get_volume() {
		return (float) $this->get_length() * (float) $this->get_width() * (float) $this->get_height();
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

	public function get_title() {
		$description = $this->get_description();

		return sprintf(
			_x( '%1$s (%2$s, %3$s)', 'shipments-packaging-title', 'woocommerce-germanized' ),
			$description,
			$this->get_formatted_dimensions(),
			wc_gzd_format_shipment_weight( wc_format_decimal( $this->get_weight(), false, true ), wc_gzd_get_packaging_weight_unit() )
		);
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
}
