<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_Woocommerce_Product_Bundles extends WC_GZD_Compatibility {

	public function __construct() {
		parent::__construct(
			'WooCommerce Product Bundles',
			'woocommerce-product-bundles/woocommerce-product-bundles.php'
		);
	}

	public function load() {
		add_filter( 'woocommerce_gzd_cart_item_tax_share_product', array( $this, 'switch_bundle_tax_share_product' ), 10, 4 );
	}

	/**
	 * If the bundled product is priced individually WC Product Bundles marks the item as virtual.
	 * In that case we are returning the original product to be matched agains Germanized tax share check.
	 *
	 * @param $product
	 * @param $item
	 * @param $item_key
	 * @param $type
	 *
	 * @return mixed
	 */
	public function switch_bundle_tax_share_product( $product, $item, $item_key, $type ) {
		if ( ! function_exists( 'wc_pb_get_bundled_item' ) ) {
			return $product;
		}

		if ( 'shipping' === $type ) {
			if ( isset( $item['bundled_item_id'] ) && ! empty( $item['bundled_item_id'] ) ) {
				if ( $bundled_item = wc_pb_get_bundled_item( $item['bundled_item_id'] ) ) {
					if ( $bundled_item->is_priced_individually() ) {
						return $bundled_item->product;
					}
				}
			}
		}

		return $product;
	}
}