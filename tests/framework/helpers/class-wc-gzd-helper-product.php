<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_GZD_Helper_Product {

	public static function create_simple_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price(10);
		$product->set_sale_price(9);

		$data = array(
			'_unit' => 'g',
			'_unit_base' => '10',
			'_unit_product' => '1',
			'_unit_price_regular' => '100.0',
			'_unit_price_sale' => '90.0',
			'_unit_price' => '90.00',
			'_unit_price_auto' => false,
			'_sale_price_label' => 'new-price',
			'_sale_price_regular_label' => 'old-price',
			'_mini_desc' => 'This is a test',
			'_free_shipping' => 'yes',
			'_service' => 'yes',
			'_differential_taxation' => 'yes'
		);

		foreach( $data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->save();

		wp_set_object_terms( $product->get_id(), '2-3 Days', 'product_delivery_time' );

		return wc_gzd_get_gzd_product( $product );
	}

	public static function create_variation_product() {
		$product           = WC_Helper_Product::create_variation_product();
		$children          = $product->get_children();
		$variation_id      = $children[0];

		$data = array(
			'_unit' => 'g',
			'_unit_base' => '10',
			'_unit_product' => '1',
			'_sale_price_label' => 'new-price',
			'_sale_price_regular_label' => 'old-price',
			'_free_shipping' => 'yes',
			'_service' => 'yes',
			'_differential_taxation' => 'yes'
		);

		foreach( $data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->save();

		wp_set_object_terms( $product->get_id(), '2-3 Days', 'product_delivery_time' );

		return wc_gzd_get_gzd_product( $product );
	}
}