<?php

defined( 'ABSPATH' ) || exit;

/**
 * Product Factory Class
 *
 * The WooCommerce product factory creating the right product object
 *
 * @class        WC_Product_Factory
 * @version        2.3.0
 * @package        WooCommerce/Classes
 * @category    Class
 * @author        WooThemes
 */
class WC_GZD_Product_Factory {

	private $weak_product_list = null;

	/**
	 * get_product function.
	 *
	 * @param bool $the_product (default: false)
	 * @param array $args (default: array())
	 *
	 * @return WC_Product|bool false if the product cannot be loaded
	 */
	public function get_product( $the_product = false ) {
		$product = $this->get_product_standalone( $the_product );

		if ( is_object( $product ) ) {
			$product->gzd_product = $this->get_gzd_product( $product );
		}

		return $product;
	}

	/**
	 * @return WeakMap
	 */
	protected function get_product_list() {
		if ( is_null( $this->weak_product_list ) ) {
			$this->weak_product_list = new WeakMap();
		}

		return $this->weak_product_list;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return WC_GZD_Product
	 */
	public function get_cached_gzd_product( $product ) {
		/**
		 * Use WeakMap to build product cache instead of using dynamic class
		 * properties which are starting to be deprecated in PHP 8.2.
		 */
		if ( version_compare( PHP_VERSION, '8.0.0', '>=' ) ) {
			$product_list = $this->get_product_list();

			if ( ! isset( $product_list[ $product ] ) || ! is_a( $product_list[ $product ], 'WC_GZD_Product' ) ) {
				$product_list[ $product ] = $this->get_gzd_product( $product );
			}

			$product_list[ $product ]->set_wc_product( $product );

			return $product_list[ $product ];
		} else {
			if ( ! isset( $product->gzd_product ) || ! is_a( $product->gzd_product, 'WC_GZD_Product' ) ) {
				$product->gzd_product = WC_germanized()->product_factory->get_gzd_product( $product );
			}

			return $product->gzd_product;
		}
	}

	/**
	 * Gets product without injecting gzd_product. Only available as fallback
	 *
	 * @param bool $the_product (default: false)
	 * @param array $args (default: array())
	 *
	 * @return WC_Product|bool false if the product cannot be loaded
	 */
	public function get_product_standalone( $the_product = false ) {
		return wc_get_product( $the_product );
	}

	/**
	 * Returns and locates the WC_GZD_Product Object based on product type.
	 *
	 * @param WC_Product $product the product
	 *
	 * @return WC_GZD_Product
	 */
	public function get_gzd_product( $product ) {
		$type      = $product->get_type();
		$classname = 'WC_GZD_Product_' . ucfirst( $type );

		/**
		 * Filter the classname for the Germanized product implementation.
		 *
		 * @param string $classname The classname.
		 * @param string $type The product type.
		 *
		 * @since 1.0.0
		 *
		 */
		$classname = apply_filters( 'woocommerce_gzd_product_classname', $classname, $type );

		if ( class_exists( $classname ) ) {
			return new $classname( $product );
		}

		return new WC_GZD_Product( $product );
	}
}
