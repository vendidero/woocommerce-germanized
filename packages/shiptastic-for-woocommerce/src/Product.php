<?php

namespace Vendidero\Shiptastic;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Product
 *
 * @class       Product
 * @version     1.0.0
 * @author      Vendidero
 */
class Product {

	/**
	 * The actual product object
	 *
	 * @var WC_Product
	 */
	protected $product;

	/**
	 * @param WC_Product $product
	 *
	 * @throws \Exception
	 */
	public function __construct( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! is_a( $product, 'WC_Product' ) ) {
			throw new \Exception( esc_html_x( 'Invalid product.', 'shipments', 'woocommerce-germanized' ) );
		}

		$this->product = $product;
	}

	/**
	 * Returns the Woo WC_Product original object
	 *
	 * @return object|WC_Product
	 */
	public function get_product() {
		return $this->product;
	}

	public function is_variation() {
		return $this->get_product()->is_type( 'variation' );
	}

	protected function get_forced_parent_product() {
		if ( $this->is_variation() ) {
			if ( $parent = wc_get_product( $this->product->get_parent_id() ) ) {
				return $parent;
			}
		}

		return $this->product;
	}

	public function get_ship_separately_via( $context = 'view' ) {
		$data = $this->get_forced_parent_product()->get_meta( '_ship_separately_via', true, $context );

		return $data;
	}

	public function is_shipped_separately( $context = 'view' ) {
		$separate = $this->get_ship_separately_via( $context );

		return ! empty( $separate );
	}

	public function get_shipping_length( $context = 'view' ) {
		$length = $this->get_product()->get_meta( '_shipping_length', true, $context );

		if ( 'view' === $context && '' === $length ) {
			$length = $this->get_product()->get_length();

			if ( $this->is_variation() && '' === $length ) {
				$length = wc_shiptastic_get_product( $this->get_forced_parent_product() )->get_shipping_length( $context );
			}
		}

		return $length;
	}

	public function get_shipping_width( $context = 'view' ) {
		$width = $this->get_product()->get_meta( '_shipping_width', true, $context );

		if ( 'view' === $context && '' === $width ) {
			$width = $this->get_product()->get_width();

			if ( $this->is_variation() && '' === $width ) {
				$width = wc_shiptastic_get_product( $this->get_forced_parent_product() )->get_shipping_width( $context );
			}
		}

		return $width;
	}

	public function get_shipping_height( $context = 'view' ) {
		$height = $this->get_product()->get_meta( '_shipping_height', true, $context );

		if ( 'view' === $context && '' === $height ) {
			$height = $this->get_product()->get_height();

			if ( $this->is_variation() && '' === $height ) {
				$height = wc_shiptastic_get_product( $this->get_forced_parent_product() )->get_shipping_height( $context );
			}
		}

		return $height;
	}

	public function get_customs_description( $context = 'view' ) {
		$data = $this->get_forced_parent_product()->get_meta( '_customs_description', true, $context );

		return $data;
	}

	public function is_non_returnable( $context = 'view' ) {
		$is_non_returnable = wc_string_to_bool( $this->get_forced_parent_product()->get_meta( '_is_non_returnable', true, $context ) );

		return $is_non_returnable;
	}

	public function get_hs_code( $context = 'view' ) {
		$legacy_data = $this->get_forced_parent_product()->get_meta( '_dhl_hs_code', true, $context );
		$data        = $this->get_forced_parent_product()->get_meta( '_hs_code', true, $context );

		if ( '' === $data && ! empty( $legacy_data ) ) {
			$data = $legacy_data;
		}

		return $data;
	}

	public function get_manufacture_country( $context = 'view' ) {
		$legacy_data = $this->get_forced_parent_product()->get_meta( '_dhl_manufacture_country', true, $context );
		$data        = $this->get_forced_parent_product()->get_meta( '_manufacture_country', true, $context );

		if ( '' === $data && ! empty( $legacy_data ) ) {
			$data = $legacy_data;
		}

		if ( '' === $data && 'view' === $context ) {
			return Package::get_base_country();
		}

		return $data;
	}

	public function get_main_category() {
		$ids       = $this->get_forced_parent_product()->get_category_ids();
		$term_name = '';

		if ( ! empty( $ids ) ) {
			foreach ( $ids as $term_id ) {
				$term = get_term( $term_id, 'product_cat' );

				if ( empty( $term->slug ) ) {
					continue;
				}

				$term_name = $term->name;
				break;
			}
		}

		return $term_name;
	}

	public function set_hs_code( $code ) {
		$this->product->update_meta_data( '_hs_code', $code );
	}

	public function set_manufacture_country( $country ) {
		$this->product->update_meta_data( '_manufacture_country', substr( wc_strtoupper( $country ), 0, 2 ) );
	}

	public function set_ship_separately_via( $shipping_provider ) {
		$this->product->update_meta_data( '_ship_separately_via', $shipping_provider );
	}

	public function set_shipping_length( $length ) {
		$this->product->update_meta_data( '_shipping_length', wc_format_decimal( $length ) );
	}

	public function set_shipping_width( $width ) {
		$this->product->update_meta_data( '_shipping_width', wc_format_decimal( $width ) );
	}

	public function set_shipping_height( $height ) {
		$this->product->update_meta_data( '_shipping_height', wc_format_decimal( $height ) );
	}

	public function set_customs_description( $description ) {
		$this->product->update_meta_data( '_customs_description', $description );
	}

	public function set_is_non_returnable( $is_non_returnable ) {
		$this->product->update_meta_data( '_is_non_returnable', wc_bool_to_string( $is_non_returnable ) );
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		if ( method_exists( $this->product, $method ) ) {
			return call_user_func_array( array( $this->product, $method ), $args );
		}

		return false;
	}
}
