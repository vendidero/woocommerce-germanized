<?php
/**
 * Tests for Products API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

class WC_GZD_Products_API extends WC_GZD_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_GZD_REST_Products_Controller();
		$this->user     = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test getting a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_get_product() {
		wp_set_current_user( $this->user );

		$simple   = WC_GZD_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $simple->get_id() ) );
		$product  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 'new-price', $product['sale_price_label']['slug'] );
		$this->assertEquals( 'old-price', $product['sale_price_regular_label']['slug'] );
		$this->assertEquals( '2-3-days', $product['delivery_time']['slug'] );
		$this->assertEquals( '1', $product['unit_price']['product'] );
		$this->assertEquals( '100.0', $product['unit_price']['price_regular'] );
		$this->assertEquals( '90.0', $product['unit_price']['price_sale'] );
		$this->assertEquals( 'This is a test', trim(strip_tags($product['mini_desc'])) );
		$this->assertEquals( true, $product['service'] );
		$this->assertEquals( true, $product['differential_taxation'] );
		$this->assertEquals( true, $product['free_shipping'] );

		$simple->delete( true );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product() {
		wp_set_current_user( $this->user );

		// test simple products
		$simple   = WC_GZD_Helper_Product::create_simple_product();
		$term = wp_insert_term( '3-4 days', 'product_delivery_time', array( 'slug' => '3-4-days' ) );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'delivery_time'             => array( 'id' => $term[ 'term_id' ] ),
			'unit_price'                => array( 'price_regular' => '80.0', 'price_sale' => '70.0' ),
			'differential_taxation'     => false,
		) );

		$response = $this->server->dispatch( $request );
		$data  = $response->get_data();

		// GET Product
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $simple->get_id() ) );
		$product  = $response->get_data();

		$this->assertEquals( '3-4-days', $product['delivery_time']['slug'] );
		$this->assertEquals( '80.0', $product['unit_price']['price_regular'] );
		$this->assertEquals( '70.0', $product['unit_price']['price_sale'] );

		$this->assertEquals( 'new-price', $product['sale_price_label']['slug'] );
		$this->assertEquals( 'old-price', $product['sale_price_regular_label']['slug'] );
		$this->assertEquals( '1', $product['unit_price']['product'] );
		$this->assertEquals( 'This is a test', trim(strip_tags($product['mini_desc'])) );
		$this->assertEquals( true, $product['free_shipping'] );
		$this->assertEquals( false, $product['differential_taxation'] );
		$this->assertEquals( true, $product['service'] );

		$simple->delete( true );
	}
}
