<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

class PrintFormat {

	protected $id = '';

	protected $label = '';

	protected $description = '';

	protected $shipping_provider = null;

	protected $shipping_provider_name = '';

	protected $products = null;

	protected $shipment_types = array();

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
				'name'           => '',
				'label'          => '',
				'description'    => '',
				'products'       => null,
				'shipment_types' => wc_gzd_get_shipment_types(),
			)
		);

		if ( empty( $args['id'] ) ) {
			$args['id'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['id'] ) ) {
			throw new \Exception( esc_html_x( 'A print format needs an id.', 'shipments', 'woocommerce-germanized' ), 500 );
		}

		$this->id             = $args['id'];
		$this->label          = $args['label'];
		$this->description    = $args['description'];
		$this->products       = is_null( $args['products'] ) ? null : array_filter( (array) $args['products'] );
		$this->shipment_types = array_filter( (array) $args['shipment_types'] );
	}

	public function get_id() {
		return $this->id;
	}

	public function get_label() {
		return $this->label;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_products() {
		return is_null( $this->products ) ? array() : $this->products;
	}

	public function get_shipment_types() {
		return $this->shipment_types;
	}

	public function supports_product( $product ) {
		return is_null( $this->products ) ? true : in_array( $product, $this->get_products(), true );
	}

	public function supports_shipment_type( $type ) {
		return in_array( $type, $this->shipment_types, true );
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

		return $supports_shipment;
	}

	public function supports( $filter_args = array() ) {
		$filter_args = wp_parse_args(
			$filter_args,
			array(
				'shipment'      => false,
				'shipment_type' => '',
				'product'       => '',
				'products'      => array(),
				'product_id'    => '',
			)
		);

		if ( ! empty( $filter_args['product_id'] ) ) {
			$filter_args['products'] = array_merge( $filter_args['products'], (array) $filter_args['product_id'] );
		}

		if ( ! empty( $filter_args['product'] ) && is_a( $filter_args['product'], '\Vendidero\Germanized\Shipments\ShippingProvider\Product' ) ) {
			$filter_args['products'] = array_merge( $filter_args['products'], (array) $filter_args['product']->get_id() );
		}

		$include = true;

		if ( $include && ! empty( $filter_args['shipment'] ) && ( $shipment = wc_gzd_get_shipment( $filter_args['shipment'] ) ) ) {
			$include = $this->supports_shipment( $shipment );

			$filter_args['shipment_type'] = '';
		}

		if ( $include && ! empty( $filter_args['shipment_type'] ) && ! $this->supports_shipment_type( $filter_args['shipment_type'] ) ) {
			$include = false;
		}

		if ( $include && ! empty( $filter_args['products'] ) ) {
			$supports_product = false;

			foreach ( $filter_args['products'] as $product_id ) {
				if ( $this->supports_product( $product_id ) ) {
					$supports_product = true;
					break;
				}
			}

			if ( ! $supports_product ) {
				$include = false;
			}
		}

		return $include;
	}

	public function get_shipping_provider() {
		if ( is_null( $this->shipping_provider ) ) {
			$this->shipping_provider = wc_gzd_get_shipping_provider( $this->shipping_provider_name );
		}

		return $this->shipping_provider;
	}
}
