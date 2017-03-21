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
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/products/' . $simple->get_id() ) );
		$product  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( array(
			'id'            => $simple->get_id(),
			'delivery_time' => array(),
			'sale_price_label' => array(),
			'sale_price_regular_label' => array(),
			'unit_price' => array(),
			'unit' => array(),
			'mini_desc' => '',
			'free_shipping' => ''
		), $product );
	}
}
