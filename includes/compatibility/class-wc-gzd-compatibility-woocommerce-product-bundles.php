<?php

/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class        WC_GZD_WPML_Helper
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Product_Bundles extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce Product Bundles';
	}

	public static function get_path() {
		return 'woocommerce-product-bundles/woocommerce-product-bundles.php';
	}

	public function load() {
		add_filter( 'woocommerce_gzd_cart_item_tax_share_product', array(
			$this,
			'switch_bundle_tax_share_product'
		), 10, 4 );

		add_filter( 'woocommerce_gzd_product_types_supporting_unit_prices', array(
			$this,
			'enable_unit_prices'
		), 10, 1 );

		/**
		 * Add tax, unit price shopmarks to bundled item prices
		 */
		add_filter( 'woocommerce_bundled_item_details', array( $this, 'register_item_price_filters' ), 0, 2 );
		add_filter( 'woocommerce_bundled_item_details', array( $this, 'unregister_item_price_filters' ), 500, 2 );

		/**
		 * Add single product shopmarks to the bundle total price
		 */
		add_action( 'woocommerce_after_bundle_price', array( $this, 'output_bundle_shopmarks' ), 10 );
	}

	public function output_bundle_shopmarks() {
		foreach( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$callback = $shopmark->get_callback();

			if ( function_exists( $callback ) && $shopmark->is_enabled() && in_array( $shopmark->get_type(), array( 'unit_price', 'legal', 'tax', 'shipping_costs' ) ) ) {
				call_user_func( $callback );
			}
		}
	}

	/**
	 * @param \WC_Bundled_Item    $bundled_item
	 * @param \WC_Product_Bundle  $bundle
	 */
	public function register_item_price_filters( $bundled_item, $bundle ) {
		add_filter( 'woocommerce_bundled_item_price_html', array( $this, 'add_price_suffixes' ), 10, 3 );
	}

	protected function replace_p_tags( $html ) {
		return str_replace( array( '<p', '</p>' ), array( '<span', '</span>' ), $html );
	}

	public function add_price_suffixes( $price, $org_price, $org_product ) {
		global $product;
		if ( $product = $org_product->get_product() ) {
			ob_start();
			woocommerce_gzd_template_single_tax_info();
			$legal = ob_get_clean();

			ob_start();
			woocommerce_gzd_template_single_price_unit();
			$unit = ob_get_clean();

			$price = $price . '<span class="wc-gzd-legal-price-info">' . $this->replace_p_tags( $unit ) . $this->replace_p_tags( $legal ) . '</span>';
		}

		return $price;
	}

	/**
	 * @param \WC_Bundled_Item    $bundled_item
	 * @param \WC_Product_Bundle  $bundle
	 */
	public function unregister_item_price_filters( $bundled_item, $bundle ) {
		remove_filter( 'woocommerce_bundled_item_price_html', array( $this, 'add_price_suffixes' ), 10 );
	}

	public function enable_unit_prices( $types ) {
		$types[] = 'bundle';

		return $types;
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