<?php
/**
 * Product Factory Class
 *
 * The WooCommerce product factory creating the right product object
 *
 * @class 		WC_Product_Factory
 * @version		2.3.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_GZD_Product_Factory extends WC_Product_Factory {

	/**
	 * get_product function.
	 *
	 * @param bool $the_product (default: false)
	 * @param array $args (default: array())
	 * @return WC_Product|bool false if the product cannot be loaded
	 */
	public function get_product( $the_product = false, $args = array() ) {
		$product = parent::get_product( $the_product, $args );
		if ( is_object( $product ) )
			$product->gzd_product = new WC_GZD_Product( $product );
		return $product;
	}

}
