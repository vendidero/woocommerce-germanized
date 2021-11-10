<?php

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class WC_GZD_Tests_GZD_Product extends WC_GZD_Unit_Test_Case {

	function test_wc_gzd_get_product() {
		$product = WC_Helper_Product::create_simple_product();
		$copy    = wc_get_product( $product->get_id() );

		$this->assertInstanceOf( 'WC_GZD_Product', wc_gzd_get_product( $copy ) );
	}

	function test_product_data() {

		$gzd_product = WC_GZD_Helper_Product::create_simple_product();

		$this->assertEquals( '90.0', $gzd_product->get_unit_price() );
		$this->assertEquals( '90.0', $gzd_product->get_unit_price_sale() );
		$this->assertEquals( '100.0', $gzd_product->get_unit_price_regular() );

		$this->assertEquals( '10', $gzd_product->get_unit_base() );
		$this->assertEquals( '1', $gzd_product->get_unit_product() );
		$this->assertEquals( 'g', $gzd_product->get_unit() );

		$this->assertEquals( true, $gzd_product->has_unit() );
		$this->assertEquals( true, $gzd_product->is_on_unit_sale() );
		$this->assertEquals( true, $gzd_product->has_unit_product() );

		$this->assertEquals( 'This is a test', trim( strip_tags( $gzd_product->get_cart_description() ) ) );

		$this->assertEquals( true, $gzd_product->is_service() );
		$this->assertEquals( true, $gzd_product->is_differential_taxed() );

		$this->assertEquals( 'New Price:', $gzd_product->get_sale_price_label_name() );
		$this->assertEquals( 'Old Price:', $gzd_product->get_sale_price_regular_label_name() );
		$this->assertEquals( '2-3 Days', $gzd_product->get_delivery_time_name() );
		$this->assertEquals( '2-3-days', $gzd_product->get_delivery_time()->slug );

		$this->assertEquals( true, $gzd_product->has_free_shipping() );
	}

	function test_default_delivery_time() {
		$gzd_product = WC_GZD_Helper_Product::create_simple_product();

		$this->assertEquals( '2-3-days', $gzd_product->get_delivery_time()->slug );

		$term_data     = wp_insert_term( '8-9 Days', 'product_delivery_time' );
		$term_data_eu  = wp_insert_term( '10-12 Days', 'product_delivery_time' );
		$term_data_int = wp_insert_term( '14-20 Days', 'product_delivery_time' );

		update_option( 'woocommerce_default_country', 'DE' );
		update_option( 'woocommerce_gzd_default_delivery_time', $term_data['term_id'] );
		update_option( 'woocommerce_gzd_default_delivery_time_eu', $term_data_eu['term_id'] );
		update_option( 'woocommerce_gzd_default_delivery_time_third_countries', $term_data_int['term_id'] );

		WC()->customer->set_shipping_country( 'DE' );

		$gzd_product->set_default_delivery_time_slug( '' );
		$gzd_product->set_country_specific_delivery_times( array() );
		$gzd_product->save();

		$this->assertEquals( '8-9-days', $gzd_product->get_delivery_time()->slug );
		$this->assertFalse( $gzd_product->get_delivery_time( 'edit' ) );

		WC()->customer->set_shipping_country( 'AT' );
		$this->assertEquals( '10-12-days', $gzd_product->get_delivery_time()->slug );

		WC()->customer->set_shipping_country( 'DE' );
		$this->assertEquals( '8-9-days', $gzd_product->get_delivery_time()->slug );

		WC()->customer->set_shipping_country( 'CH' );
		$this->assertEquals( '14-20-days', $gzd_product->get_delivery_time()->slug );

		update_option( 'woocommerce_gzd_default_delivery_time_eu', '' );
		WC()->customer->set_shipping_country( 'AT' );
		$this->assertEquals( '8-9-days', $gzd_product->get_delivery_time()->slug );

		// Make sure default delivery time is respected and preferred
		$gzd_product->set_default_delivery_time_slug( '2-3-days' );
		$gzd_product->save();

		update_option( 'woocommerce_gzd_default_delivery_time_eu', $term_data_eu['term_id'] );
		WC()->customer->set_shipping_country( 'AT' );
		$this->assertEquals( '2-3-days', $gzd_product->get_delivery_time()->slug );

		// Restore default to prevent side-effects
		WC()->customer->set_shipping_country( 'US' );
	}

	function test_country_specific_delivery_time() {
		$gzd_product = WC_GZD_Helper_Product::create_simple_product();

		$this->assertEquals( '4-5-days', $gzd_product->get_delivery_time_by_country( 'AT' )->slug );
		$this->assertEquals( '2-3-days', $gzd_product->get_delivery_time()->slug );

		$term_data              = wp_insert_term( '8-9 Days', 'product_delivery_time' );
		$country_specific       = $gzd_product->get_country_specific_delivery_times();
		$country_specific['BE'] = '8-9-days';

		$gzd_product->set_country_specific_delivery_times( $country_specific );
		$gzd_product->save();

		$this->assertEquals( '8-9-days', $gzd_product->get_delivery_time_by_country( 'BE' )->slug );

		WC()->customer->set_shipping_country( 'BE' );
		$this->assertEquals( '8-9-days', $gzd_product->get_delivery_time()->slug );
	}

	function test_new_country_specific_delivery_time() {
		$gzd_product = WC_GZD_Helper_Product::create_simple_product();

		$this->assertEquals( '4-5-days', $gzd_product->get_delivery_time_by_country( 'AT' )->slug );
		$this->assertEquals( '2-3-days', $gzd_product->get_delivery_time()->slug );

		$country_specific       = $gzd_product->get_country_specific_delivery_times();
		$country_specific['BE'] = '10-12 Days';

		$gzd_product->set_country_specific_delivery_times( $country_specific );
		$gzd_product->save();

		$this->assertEquals( '10-12-days', $gzd_product->get_delivery_time_by_country( 'BE' )->slug );
	}
}