<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Variation Class
 *
 * @class 		WC_GZD_Product_Variation
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Product_Variation extends WC_Product_Variation {

	/**
	 * Construct the Product by calling parent constructor and injecting WC_GZD_Product. Add variation level meta keys for delivery time and unit price.
	 *
	 * @param int|WC_Product|WP_Post $product Product ID, post object, or product object
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
		$this->variation_level_meta_data[ 'unit' ] = 0;
		$this->variation_level_meta_data[ 'unit_price' ] = 0;
		$this->variation_level_meta_data[ 'unit_base' ] = '';
		$this->variation_level_meta_data[ 'unit_price_regular' ] = 0;
		$this->variation_level_meta_data[ 'unit_price_sale' ] = 0;
		$this->variation_level_meta_data[ 'mini_desc' ] = '';
		$this->variation_level_meta_data[ 'gzd_product' ] = NULL;
	}

	public function get_level_meta_data() {
		return $this->variation_level_meta_data;
	}

	/**
	 * Returns the current products delivery time term without falling back to default terms
	 * 
	 * @return bool|object false returns false if term does not exist otherwise returns term object
	 */
	public function get_delivery_time() {
		$terms = wp_get_post_terms( $this->variation_id, 'product_delivery_time' );
		if ( is_wp_error( $terms ) || empty( $terms ) )
			return false;
		return $terms[ 0 ];
	}

	/**
	 * Implement getter to grab delivery time
	 *  
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( $key == 'delivery_time' )
			return $this->get_delivery_time();
		return parent::__get( $key );
	}

}
