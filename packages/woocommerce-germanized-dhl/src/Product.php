<?php

namespace Vendidero\Germanized\DHL;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Product
 *
 * @class 		Product
 * @version		1.0.0
 * @author 		Vendidero
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
	 */
	public function __construct( $product ) {
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

	protected function get_forced_parent_product() {
		if ( $this->product->is_type( 'variation' ) ) {
			return wc_get_product( $this->product->get_parent_id() );
		}

		return $this->product;
	}

	public function get_hs_code() {
		return $this->get_forced_parent_product()->get_meta( '_dhl_hs_code', true );
	}

	public function get_manufacture_country() {
		return $this->get_forced_parent_product()->get_meta( '_dhl_manufacture_country', true );
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
		$this->product->update_meta_data( '_dhl_hs_code', $code );
	}

	public function set_manufacture_country( $country ) {
		$this->product->update_meta_data( '_dhl_manufacture_country', $country );
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
