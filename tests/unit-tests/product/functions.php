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
		$this->assertEquals( true, wc_gzd_product_matches_extended_type( array(
			'downloadable',
			'service'
		), $downloadable ) );

		$this->assertEquals( true, wc_gzd_product_matches_extended_type( array( 'simple', 'external' ), $simple ) );
		$this->assertEquals( false, wc_gzd_product_matches_extended_type( 'external', $simple ) );
	}

	public function test_wc_gzd_recalculate_unit_price() {
		$simple = WC_Helper_Product::create_simple_product();
		$simple->set_regular_price( 10 );
		$simple->set_price( 5 );
		$simple->set_sale_price( 5 );

		$gzd_product = wc_gzd_get_gzd_product( $simple );
		$gzd_product->set_unit_base( 100 );
		$gzd_product->set_unit( 'ml' );
		$gzd_product->set_unit_product( 1000 );

		$prices = wc_gzd_recalculate_unit_price( array( 'tax_mode' => 'excl' ), $gzd_product );

		$this->assertEquals( 0.5, $prices['sale'] );
		$this->assertEquals( 0.5, $prices['unit'] );
		$this->assertEquals( 1, $prices['regular'] );

		$prices = wc_gzd_recalculate_unit_price( array(
			'tax_mode'      => 'incl',
			'regular_price' => '50',
			'sale_price'    => '50',
			'price'         => '50',
			'base'          => '100',
			'products'      => '50'
		), $gzd_product );

		$this->assertEquals( 100, $prices['sale'] );
		$this->assertEquals( 100, $prices['unit'] );
		$this->assertEquals( 100, $prices['regular'] );
	}
}
