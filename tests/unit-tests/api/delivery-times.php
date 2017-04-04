<?php
/**
 * Tests for Products API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

class WC_GZD_Delivery_Times_API extends WC_GZD_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_GZD_REST_Product_Delivery_Times_Controller();
		$this->user     = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test getting a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_get_delivery_time() {
		wp_set_current_user( $this->user );

		$term = wp_insert_term( '7-8 days', 'product_delivery_time', array( 'slug' => '7-8-days' ) );

		$request  = new WP_REST_Request( 'GET', '/wc/v2/products/delivery_times/' . $term[ 'term_id' ] );
		$response = $this->server->dispatch( $request );

		$delivery_time  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( $delivery_time, array(
			'id' => $term[ 'term_id' ],
			'name' => '7-8 days',
			'slug' => '7-8-days',
			'description' => '',
			'count' => 0,
		) );

		wp_delete_term( $term[ 'term_id' ], 'product_delivery_time' );
	}
}
