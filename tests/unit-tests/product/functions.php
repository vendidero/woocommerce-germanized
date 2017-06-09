<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class WC_GZD_Tests_Product_Functions extends WC_GZD_Unit_Test_Case {

	/**
	 * Tests wc_gzd_product_matches_extended_type().
	 *
	 * @since 3.0.0
	 */
	public function test_wc_gzd_product_matches_extended_type() {

		$simple = WC_Helper_Product::create_simple_product();
		$variable = WC_Helper_Product::create_variation_product();

		$virtual = WC_Helper_Product::create_simple_product();
		$virtual->set_virtual( true );
		$virtual->save();

		$downloadable = WC_Helper_Product::create_simple_product();
		$downloadable->set_downloadable( true );
		$downloadable->save();

		$this->assertEquals( true, wc_gzd_product_matches_extended_type( 'simple', $simple ) );
		$this->assertEquals( true, wc_gzd_product_matches_extended_type( 'variable', $variable ) );
		$this->assertEquals( true, wc_gzd_product_matches_extended_type( 'virtual', $virtual ) );
		$this->assertEquals( true, wc_gzd_product_matches_extended_type( 'downloadable', $downloadable ) );

		$this->assertEquals( true, wc_gzd_product_matches_extended_type( array( 'simple', 'external' ), $simple ) );
		$this->assertEquals( false, wc_gzd_product_matches_extended_type( 'external', $simple ) );

	}
}
