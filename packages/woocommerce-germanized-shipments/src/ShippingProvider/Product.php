<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

class Product {

	protected $id = '';

	protected $label = '';

	protected $shipping_provider = null;

	protected $shipping_provider_name = '';

	protected $countries = null;

	protected $zones = array();

	protected $shipment_types = array();

	protected $price = 0.0;

	protected $weight_unit = '';

	protected $weight = null;

	protected $dimension_unit = '';

	protected $length = null;

	protected $width = null;

	protected $height = null;

	protected $meta = array();

	protected $parent_id = 0;

	protected $internal_id = '';

	protected $description = '';

	public function __construct( $shipping_provider, $args = array() ) {
		if ( is_a( $shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ) {
			$this->shipping_provider      = $shipping_provider;
			$this->shipping_provider_name = $shipping_provider->get_name();
		} else {
			$this->shipping_provider_name = $shipping_provider;
		}

		$args = wp_parse_args(
			$args,
			array(
				'id'             => '',
				'internal_id'    => 0,
				'parent_id'      => 0,
				'label'          => '',
				'description'    => '',
				'shipment_types' => array( 'simple' ),
				'countries'      => null,
				'zones'          => array_keys( wc_gzd_get_shipping_label_zones() ),
				'price'          => 0.0,
				'weight'         => null,
				'length'         => null,
				'width'          => null,
				'height'         => null,
				'dimension_unit' => get_option( 'woocommerce_dimension_unit' ),
				'weight_unit'    => get_option( 'woocommerce_weight_unit' ),
				'meta'           => array(),
			)
		);

		if ( empty( $args['id'] ) ) {
			$args['id'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['id'] ) ) {
			throw new \Exception( esc_html_x( 'A product needs an id.', 'shipments', 'woocommerce-germanized' ), 500 );
		}

		$this->id             = $args['id'];
		$this->internal_id    = ! empty( $args['internal_id'] ) ? $args['internal_id'] : $this->id;
		$this->parent_id      = $args['parent_id'];
		$this->label          = $args['label'];
		$this->description    = $args['description'];
		$this->meta           = (array) $args['meta'];
		$this->shipment_types = array_filter( (array) $args['shipment_types'] );
		$this->countries      = is_null( $args['countries'] ) ? null : array_filter( (array) $args['countries'] );

		if ( ! empty( $this->countries ) ) {
			if ( 1 === count( $this->countries ) && Package::get_base_country() === $this->countries[0] ) {
				$args['zones'] = array( 'dom' );
			}

			if ( in_array( 'ALL_EU', $this->countries, true ) ) {
				$this->countries = array_diff( $this->countries, array( 'ALL_EU' ) );
				$this->countries = array_unique( array_merge( WC()->countries->get_european_union_countries(), $this->countries ) );
			}
		}

		$this->zones          = array_filter( (array) $args['zones'] );
		$this->price          = (float) wc_format_decimal( $args['price'] );
		$this->dimension_unit = $args['dimension_unit'];
		$this->weight_unit    = $args['weight_unit'];

		$this->set_min_max_prop( 'weight', $args['weight'] );
		$this->set_min_max_prop( 'length', $args['length'] );
		$this->set_min_max_prop( 'width', $args['width'] );
		$this->set_min_max_prop( 'height', $args['height'] );
	}

	private function set_min_max_prop( $prop, $default_value = null ) {
		if ( ! is_null( $default_value ) ) {
			$default_value = wp_parse_args(
				(array) $default_value,
				array(
					'min' => null,
					'max' => null,
				)
			);

			if ( is_null( $default_value['min'] ) && is_null( $default_value['max'] ) ) {
				$this->{$prop} = null;
			} else {
				$default_value['min'] = is_null( $default_value['min'] ) ? null : (float) wc_format_decimal( $default_value['min'] );
				$default_value['max'] = is_null( $default_value['max'] ) ? null : (float) wc_format_decimal( $default_value['max'] );

				$this->{$prop} = array(
					'min' => $default_value['min'],
					'max' => $default_value['max'],
				);
			}
		}
	}

	public function get_id() {
		return $this->id;
	}

	public function get_internal_id() {
		return $this->internal_id;
	}

	public function get_parent_id() {
		return $this->parent_id;
	}

	public function get_label() {
		return $this->label;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_price() {
		return $this->price;
	}

	public function get_dimension_unit() {
		return $this->dimension_unit;
	}

	public function get_weight_unit() {
		return $this->weight_unit;
	}

	public function supports_zone( $zone ) {
		return in_array( $zone, $this->zones, true );
	}

	public function get_meta( $prop = null, $default_value = false ) {
		if ( is_null( $prop ) ) {
			return $this->meta;
		} elseif ( array_key_exists( $prop, $this->meta ) ) {
			return $this->meta[ $prop ];
		} else {
			return $default_value;
		}
	}

	public function supports_country( $country, $postcode = '' ) {
		$supports_country = true;

		if ( is_array( $this->countries ) ) {
			// Northern Ireland
			if ( 'GB' === $country && 'BT' === strtoupper( substr( trim( $postcode ), 0, 2 ) ) ) {
				$country = 'IX';
			}

			$supports_country = in_array( $country, $this->countries, true );
		}

		return $supports_country;
	}

	public function supports_shipment_type( $type ) {
		return in_array( $type, $this->shipment_types, true );
	}

	protected function supports_min_max_dimension( $value, $dim = 'length', $unit = '' ) {
		if ( is_null( $this->{$dim} ) || ( is_null( $this->{$dim}['min'] ) && is_null( $this->{$dim}['max'] ) ) ) {
			return true;
		}

		$supports = true;
		$value    = (float) wc_format_decimal( $value );

		if ( '' === $unit ) {
			$unit = get_option( 'woocommerce_dimension_unit' );
		}

		$dim_in_local_unit = (float) wc_get_dimension( $value, $this->get_dimension_unit(), $unit );

		if ( ! is_null( $this->{$dim}['min'] ) && $dim_in_local_unit < $this->{$dim}['min'] ) {
			$supports = false;
		}

		if ( ! is_null( $this->{$dim}['max'] ) && $dim_in_local_unit > $this->{$dim}['max'] ) {
			$supports = false;
		}

		return $supports;
	}

	public function supports_weight( $weight, $unit = '' ) {
		if ( is_null( $this->weight ) || ( is_null( $this->weight['min'] ) && is_null( $this->weight['max'] ) ) ) {
			return true;
		}

		$supports = true;
		$weight   = (float) wc_format_decimal( $weight );

		if ( '' === $unit ) {
			$unit = get_option( 'woocommerce_weight_unit' );
		}

		$weight_in_local_unit = (float) wc_get_weight( $weight, $this->get_weight_unit(), $unit );

		if ( ! is_null( $this->weight['min'] ) && $weight_in_local_unit < $this->weight['min'] ) {
			$supports = false;
		}

		if ( ! is_null( $this->weight['max'] ) && $weight_in_local_unit > $this->weight['max'] ) {
			$supports = false;
		}

		return $supports;
	}

	public function supports_length( $length, $unit = '' ) {
		return $this->supports_min_max_dimension( $length, 'length', $unit );
	}

	public function supports_width( $width, $unit = '' ) {
		return $this->supports_min_max_dimension( $width, 'width', $unit );
	}

	public function supports_height( $height, $unit = '' ) {
		return $this->supports_min_max_dimension( $height, 'height', $unit );
	}

	public function get_formatted_dimensions( $type = 'length' ) {
		$formatted_dimension = '';

		if ( ! is_null( $this->{$type} ) ) {
			$min  = $this->{$type}['min'];
			$max  = $this->{$type}['max'];
			$unit = 'weight' === $type ? $this->get_weight_unit() : $this->get_dimension_unit();

			if ( ! is_null( $min ) && 0.0 !== $min ) {
				$formatted_dimension .= wc_format_localized_decimal( $min );
			}

			if ( ! is_null( $max ) ) {
				$formatted_dimension .= ( empty( $formatted_dimension ) ? sprintf( _x( 'until %s', 'dhl', 'woocommerce-germanized' ), wc_format_localized_decimal( $max ) ) : '-' . wc_format_localized_decimal( $max ) );
			}

			if ( ! empty( $formatted_dimension ) ) {
				$formatted_dimension .= ' ' . $unit;
			}
		}

		return $formatted_dimension;
	}

	public function get_formatted_weight() {
		return $this->get_formatted_dimensions( 'weight' );
	}

	public function supports( $filter_args = array() ) {
		$filter_args = wp_parse_args(
			$filter_args,
			array(
				'country'        => '',
				'zone'           => '',
				'shipment'       => false,
				'shipment_type'  => '',
				'weight'         => null,
				'weight_unit'    => '',
				'length'         => null,
				'width'          => null,
				'height'         => null,
				'dimension_unit' => '',
				'parent_id'      => null,
			)
		);

		$include_product = true;

		if ( ! is_null( $filter_args['parent_id'] ) && (string) $this->get_parent_id() !== (string) $filter_args['parent_id'] ) {
			$include_product = false;
		}

		if ( $include_product && ! empty( $filter_args['shipment'] ) && ( $shipment = wc_gzd_get_shipment( $filter_args['shipment'] ) ) ) {
			$include_product = $this->supports_shipment( $shipment );

			$filter_args['shipment_type']  = '';
			$filter_args['zone']           = '';
			$filter_args['country']        = '';
			$filter_args['weight']         = null;
			$filter_args['weight_unit']    = '';
			$filter_args['length']         = null;
			$filter_args['width']          = null;
			$filter_args['height']         = null;
			$filter_args['dimension_unit'] = '';
		}

		if ( $include_product && ! empty( $filter_args['country'] ) && ! $this->supports_country( $filter_args['country'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! empty( $filter_args['zone'] ) && ! $this->supports_zone( $filter_args['zone'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! empty( $filter_args['shipment_type'] ) && ! $this->supports_shipment_type( $filter_args['shipment_type'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! is_null( $filter_args['weight'] ) && ! $this->supports_weight( $filter_args['weight'], $filter_args['weight_unit'] ) ) {
			$include_product = false;
		}

		$dimensions = $this->get_real_dimensions( ( ! is_null( $filter_args['length'] ) ? $filter_args['length'] : 0 ), ( ! is_null( $filter_args['width'] ) ? $filter_args['width'] : 0 ), ( ! is_null( $filter_args['height'] ) ? $filter_args['height'] : 0 ) );

		if ( $include_product && ! is_null( $filter_args['length'] ) && ! $this->supports_length( $dimensions['length'], $filter_args['dimension_unit'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! is_null( $filter_args['width'] ) && ! $this->supports_length( $dimensions['width'], $filter_args['dimension_unit'] ) ) {
			$include_product = false;
		}

		if ( $include_product && ! is_null( $filter_args['height'] ) && ! $this->supports_length( $dimensions['height'], $filter_args['dimension_unit'] ) ) {
			$include_product = false;
		}

		return $include_product;
	}

	/**
	 * This helper makes sure that we do always use/compare
	 * the longest side as length.
	 *
	 * @param $length
	 * @param $width
	 * @param $height
	 *
	 * @return float[]
	 */
	private function get_real_dimensions( $length, $width, $height ) {
		$length = (float) wc_format_decimal( $length );
		$width  = (float) wc_format_decimal( $width );
		$height = (float) wc_format_decimal( $height );

		if ( $width > $length ) {
			$tmp_length = $length;

			$length = $width;
			$width  = $tmp_length;
		}

		return array(
			'length' => $length,
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return boolean
	 */
	public function supports_shipment( $shipment ) {
		$supports_shipment = true;

		if ( ! $this->supports_shipment_type( $shipment->get_type() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_zone( $shipment->get_shipping_zone() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_country( $shipment->get_country() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_weight( $shipment->get_total_weight(), $shipment->get_weight_unit() ) ) {
			$supports_shipment = false;
		}

		$dimensions = $this->get_real_dimensions( $shipment->get_length(), $shipment->get_width(), $shipment->get_height() );

		if ( $supports_shipment && ! $this->supports_length( $dimensions['length'], $shipment->get_dimension_unit() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_width( $dimensions['width'], $shipment->get_dimension_unit() ) ) {
			$supports_shipment = false;
		}

		if ( $supports_shipment && ! $this->supports_height( $dimensions['height'], $shipment->get_dimension_unit() ) ) {
			$supports_shipment = false;
		}

		return $supports_shipment;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_gzd_get_shipping_provider( $this->shipping_provider_name );
		}

		return $this->shipping_provider;
	}
}
