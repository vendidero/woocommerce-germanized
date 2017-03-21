<?php

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class WC_GZD_Tests_GZD_Product extends WC_GZD_Unit_Test_Case {

	function test_product_injection() {

		$product = WC_Helper_Product::create_simple_product();
		$copy = wc_get_product( $product->get_id() );

		$this->assertInstanceOf( 'WC_GZD_Product', $copy->gzd_product );
	}

	function test_product_data() {

		$gzd_product = WC_GZD_Helper_Product::create_simple_product();

		$this->assertEquals( '90.0', $gzd_product->get_unit_price() );
		$this->assertEquals( '90.0', $gzd_product->get_unit_sale_price() );
		$this->assertEquals( '100.0', $gzd_product->get_unit_regular_price() );
		$this->assertEquals( '10', $gzd_product->unit_base );
		$this->assertEquals( '1', $gzd_product->get_unit_products() );
		$this->assertEquals( 'g', $gzd_product->get_unit() );
		$this->assertEquals( true, $gzd_product->has_unit() );
		$this->assertEquals( true, $gzd_product->is_on_unit_sale() );
		$this->assertEquals( true, $gzd_product->has_product_units() );

		$this->assertEquals( 'This is a test', trim( strip_tags( $gzd_product->get_mini_desc() ) ) );

		$this->assertEquals( true, $gzd_product->is_service() );

		$this->assertEquals( 'New Price:', $gzd_product->get_sale_price_label() );
		$this->assertEquals( 'Old Price:', $gzd_product->get_sale_price_regular_label() );
		$this->assertEquals( '2-3 Days', $gzd_product->get_delivery_time()->name );

		$this->assertEquals( true, $gzd_product->has_free_shipping() );

	}
}