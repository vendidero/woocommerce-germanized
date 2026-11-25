<?php

/**
 * Class MPN.
 *
 * Woo core (11.4+) MPN compatibility: Germanized `_ts_mpn` as fallback for the core `_mpn`.
 *
 * @package WooCommerce\Tests\Product
 */
class WC_GZD_Tests_Product_MPN extends WC_GZD_Unit_Test_Case {

	public function test_set_mpn_uses_germanized_meta() {
		$product = WC_Helper_Product::create_simple_product();
		wc_gzd_get_product( $product )->set_mpn( 'MPN-123' );
		$product->save();

		$this->assertEquals( 'MPN-123', get_post_meta( $product->get_id(), '_ts_mpn', true ) );
		$this->assertEquals( 'MPN-123', wc_gzd_get_product( wc_get_product( $product->get_id() ) )->get_mpn( 'edit' ) );
		$this->assertEquals( 'MPN-123', wc_gzd_get_product( wc_get_product( $product->get_id() ) )->get_mpn() );
	}

	public function test_core_mpn_fallback() {
		$product = WC_Helper_Product::create_simple_product();
		wc_gzd_get_product( $product )->set_mpn( 'MPN-123' );
		$product->save();

		$product = wc_get_product( $product->get_id() );

		// Empty core MPN falls back to the Germanized MPN.
		$this->assertEquals( 'MPN-123', WC_germanized()->add_mpn_fallback( '', $product ) );

		// The core MPN is preferred.
		$this->assertEquals( 'CORE-1', WC_germanized()->add_mpn_fallback( 'CORE-1', $product ) );
	}

	public function test_variation_inherits_parent_identifiers() {
		$parent = new WC_Product_Variable();
		wc_gzd_get_gzd_product( $parent )->set_mpn( 'PARENT-MPN' );
		wc_gzd_get_gzd_product( $parent )->set_gtin( 'PARENT-GTIN' );
		$parent->save();

		$own = new WC_Product_Variation();
		$own->set_parent_id( $parent->get_id() );
		wc_gzd_get_gzd_product( $own )->set_mpn( 'OWN-MPN' );
		wc_gzd_get_gzd_product( $own )->set_gtin( 'OWN-GTIN' );
		$own = wc_get_product( $own->save() );

		$inheriting = new WC_Product_Variation();
		$inheriting->set_parent_id( $parent->get_id() );
		$inheriting = wc_get_product( $inheriting->save() );

		// View context inherits the parent value, edit context does not.
		$this->assertEquals( 'PARENT-MPN', wc_gzd_get_gzd_product( $inheriting )->get_mpn() );
		$this->assertEquals( 'PARENT-GTIN', wc_gzd_get_gzd_product( $inheriting )->get_gtin() );
		$this->assertEquals( '', wc_gzd_get_gzd_product( $inheriting )->get_mpn( 'edit' ) );
		$this->assertEquals( '', wc_gzd_get_gzd_product( $inheriting )->get_gtin( 'edit' ) );

		// The variation's own value wins.
		$this->assertEquals( 'OWN-MPN', wc_gzd_get_gzd_product( $own )->get_mpn() );
		$this->assertEquals( 'OWN-GTIN', wc_gzd_get_gzd_product( $own )->get_gtin() );

		// The Woo core fallbacks inherit as well.
		$this->assertEquals( 'PARENT-MPN', WC_germanized()->add_mpn_fallback( '', $inheriting ) );
		$this->assertEquals( 'PARENT-GTIN', WC_germanized()->add_gtin_fallback( '', $inheriting ) );
		$this->assertEquals( 'OWN-MPN', WC_germanized()->add_mpn_fallback( '', $own ) );
		$this->assertEquals( 'CORE-1', WC_germanized()->add_mpn_fallback( 'CORE-1', $inheriting ) );
	}
}
