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
		$this->variation_level_meta_data[ 'delivery_time' ] = '';
		$this->variation_level_meta_data[ 'unit' ] = 0;
		$this->variation_level_meta_data[ 'unit_price' ] = 0;
		$this->variation_level_meta_data[ 'unit_base' ] = '';
		$this->variation_level_meta_data[ 'unit_price_regular' ] = 0;
		$this->variation_level_meta_data[ 'unit_price_sale' ] = 0;
		$this->variation_level_meta_data[ 'mini_desc' ] = '';
		$this->variation_level_meta_data[ 'gzd_product' ] = NULL;
	}

	/**
	 * Implement getter to grab delivery time
	 *  
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		$value = parent::__get($key);
		if ( $key == 'delivery_time' )
			$value = get_the_terms( $this->variation_id, 'product_delivery_time' );
		return $value;
	}

}
