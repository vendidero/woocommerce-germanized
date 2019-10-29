<?php
/**
 * Tests for Products API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

class WC_GZD_Orders_API extends WC_GZD_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_GZD_REST_Orders_Controller();
		$this->user     = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test getting a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_get_order() {
		wp_set_current_user( $this->user );

		$order    = WC_GZD_Helper_Order::create_order();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() ) );
		$order    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 1, $order['billing']['title'] );
		$this->assertEquals( 1, $order['shipping']['title'] );

		$this->assertEquals( array(
			'holder'     => 'Holder',
			'iban'       => 'DE2424242424',
			'bic'        => 'DEU234242',
			'mandate_id' => '123456',
		), $order['direct_debit'] );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	 */
	public function test_update_order() {
		wp_set_current_user( $this->user );

		// test simple products
		$simple = WC_GZD_Helper_Order::create_order();

		$request = new WP_REST_Request( 'PUT', '/wc/v2/orders/' . $simple->get_id() );
		$request->set_body_params( array(
			'direct_debit' => array( 'holder'     => 'John Doe',
			                         'iban'       => 'AT242424',
			                         'bic'        => 'A424242',
			                         'mandate_id' => '123'
			),
			'billing'      => array( 'title' => 2 ),
			'shipping'     => array( 'title' => 2 ),
		) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// GET Product
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/orders/' . $simple->get_id() ) );
		$customer = $response->get_data();

		$this->assertEquals( 2, $customer['billing']['title'] );
		$this->assertEquals( 2, $customer['shipping']['title'] );

		$this->assertEquals( array(
			'holder'     => 'John Doe',
			'iban'       => 'AT242424',
			'bic'        => 'A424242',
			'mandate_id' => '123',
		), $customer['direct_debit'] );
	}
}
