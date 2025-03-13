<?php

namespace Vendidero\Shiptastic\Packing;

use Vendidero\Shiptastic\Interfaces\PackingBox;
use Vendidero\Shiptastic\Packaging;

defined( 'ABSPATH' ) || exit;

class PackagingBox implements PackingBox {

	/**
	 * @var Packaging
	 */
	protected $packaging = null;

	protected $dimensions = array();

	protected $inner_dimensions = array();

	protected $max_weight = 0;

	protected $weight = 0;

	protected $costs = 0.0;

	/**
	 * Box constructor.
	 *
	 * @param Packaging $packaging
	 */
	public function __construct( $packaging ) {
		$this->packaging = $packaging;

		$width  = empty( $this->packaging->get_width() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_width() );
		$length = empty( $this->packaging->get_length() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_length() );
		$depth  = empty( $this->packaging->get_height() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_height() );

		$this->dimensions = array(
			'width'  => (int) floor( (float) wc_get_dimension( $width, 'mm', wc_stc_get_packaging_dimension_unit() ) ),
			'length' => (int) floor( (float) wc_get_dimension( $length, 'mm', wc_stc_get_packaging_dimension_unit() ) ),
			'depth'  => (int) floor( (float) wc_get_dimension( $depth, 'mm', wc_stc_get_packaging_dimension_unit() ) ),
		);

		if ( $this->packaging->has_inner_dimensions() ) {
			$inner_width  = empty( $this->packaging->get_inner_width() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_inner_width() );
			$inner_length = empty( $this->packaging->get_inner_length() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_inner_length() );
			$inner_depth  = empty( $this->packaging->get_inner_height() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_inner_height() );

			$this->inner_dimensions = array(
				'width'  => (int) floor( (float) wc_get_dimension( $inner_width, 'mm', wc_stc_get_packaging_dimension_unit() ) ),
				'length' => (int) floor( (float) wc_get_dimension( $inner_length, 'mm', wc_stc_get_packaging_dimension_unit() ) ),
				'depth'  => (int) floor( (float) wc_get_dimension( $inner_depth, 'mm', wc_stc_get_packaging_dimension_unit() ) ),
			);
		}

		$weight       = empty( $this->packaging->get_weight() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_weight() );
		$this->weight = (int) floor( (float) wc_get_weight( $weight, 'g', wc_stc_get_packaging_weight_unit() ) );

		$max_content_weight = empty( $this->packaging->get_max_content_weight() ) ? 0 : (float) wc_format_decimal( $this->packaging->get_max_content_weight() );
		$this->max_weight   = (int) floor( (float) wc_get_weight( $max_content_weight, 'g', wc_stc_get_packaging_weight_unit() ) );

		/**
		 * If no max weight was chosen - use 50kg as fallback
		 */
		if ( empty( $this->max_weight ) ) {
			$this->max_weight = 50000;
		}
	}

	public function set_costs( $costs ) {
		$this->costs = (float) wc_format_decimal( $costs );
	}

	public function get_costs() {
		return $this->costs;
	}

	public function has_shipping_class_restrictions() {
		return $this->get_packaging()->has_shipping_class_restrictions();
	}

	public function has_shipping_provider_restrictions() {
		return $this->get_packaging()->has_shipping_provider_restrictions();
	}

	public function get_id() {
		return $this->packaging->get_id();
	}

	/**
	 * Reference for box type (e.g. SKU or description).
	 */
	public function getReference(): string {
		return (string) $this->packaging->get_title();
	}

	/**
	 * Outer width in mm.
	 */
	public function getOuterWidth(): int {
		return $this->dimensions['width'];
	}

	/**
	 * @return Packaging
	 */
	public function get_packaging() {
		return $this->packaging;
	}

	/**
	 * Outer length in mm.
	 */
	public function getOuterLength(): int {
		return $this->dimensions['length'];
	}

	/**
	 * Outer depth in mm.
	 */
	public function getOuterDepth(): int {
		return $this->dimensions['depth'];
	}

	/**
	 * Empty weight in g.
	 */
	public function getEmptyWeight(): int {
		return $this->weight;
	}

	/**
	 * Returns the threshold by which the inner dimension gets reduced
	 * in comparison to the outer dimension.
	 *
	 * @param string $type
	 *
	 * @return float
	 */
	public function get_inner_dimension_buffer( $value, $type = 'width' ) {
		if ( 'yes' === get_option( 'woocommerce_shiptastic_enable_auto_packing' ) ) {
			if ( apply_filters( 'woocommerce_shiptastic_packaging_inner_dimension_use_percentage_buffer', ( 'percentage' === get_option( 'woocommerce_shiptastic_packing_inner_buffer_type' ) ), $type, $this ) ) {
				$buffer            = (float) wc_format_decimal( get_option( 'woocommerce_shiptastic_packing_inner_percentage_buffer', '0.5' ), 2 );
				$percentage_buffer = apply_filters( 'woocommerce_shiptastic_packaging_inner_dimension_percentage_buffer', $buffer, $type, $this ) / 100;
				$value             = $value - ( $value * $percentage_buffer );
			} else {
				$buffer       = absint( get_option( 'woocommerce_shiptastic_packing_inner_fixed_buffer', 5 ) );
				$fixed_buffer = apply_filters( 'woocommerce_shiptastic_packaging_inner_dimension_fixed_buffer_mm', $buffer, $type, $this );
				$value        = $value - $fixed_buffer;
			}
		}

		return (float) max( $value, 0 );
	}

	/**
	 * Inner width in mm.
	 */
	public function getInnerWidth(): int {
		if ( ! empty( $this->inner_dimensions ) ) {
			$width = $this->inner_dimensions['width'];
		} else {
			$width = $this->get_inner_dimension_buffer( $this->dimensions['width'], 'width' );
		}

		return $width;
	}

	/**
	 * Inner length in mm.
	 */
	public function getInnerLength(): int {
		if ( ! empty( $this->inner_dimensions ) ) {
			$length = $this->inner_dimensions['length'];
		} else {
			$length = $this->get_inner_dimension_buffer( $this->dimensions['length'], 'length' );
		}

		return $length;
	}

	/**
	 * Inner depth in mm.
	 */
	public function getInnerDepth(): int {
		if ( ! empty( $this->inner_dimensions ) ) {
			$depth = $this->inner_dimensions['depth'];
		} else {
			$depth = $this->get_inner_dimension_buffer( $this->dimensions['depth'], 'depth' );
		}

		return $depth;
	}

	/**
	 * Max weight the packaging can hold in g.
	 */
	public function getMaxWeight(): int {
		return $this->max_weight;
	}
}
