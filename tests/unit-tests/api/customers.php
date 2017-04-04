<?php
/**
 * Tests for Products API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

class WC_GZD_Customers_API extends WC_GZD_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_GZD_REST_Customers_Controller();
	}

	/**
	 * Test getting a single product.
	 *
	 * @since 3.0.0
	 */
	public function test_get_customer() {
		wp_set_current_user( 1 );

		$simple   = WC_GZD_Helper_Customer::create_customer();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/' . $simple->get_id() ) );
		$customer  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 1, $customer['billing']['title'] );
		$this->assertEquals( 1, $customer['shipping']['title'] );
		$this->assertEquals( true, $customer['shipping']['parcelshop'] );
		$this->assertEquals( '123456', $customer['shipping']['parcelshop_post_number'] );

		$this->assertEquals( array(
			'holder' => 'Holder',
			'iban' => 'DE2424242424',
			'bic' => 'DEU234242',
		), $customer['direct_debit'] );

		$simple->delete(true);
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	*/
	public function test_update_customer() {
		wp_set_current_user( 1 );

		// test simple products
		$simple   = WC_GZD_Helper_Customer::create_customer();

		$request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' . $simple->get_id() );
		$request->set_body_params( array(
			'direct_debit'      => array( 'holder' => 'John Doe', 'iban' => 'AT242424', 'bic' => 'A424242' ),
			'billing'           => array( 'title' => 2 ),
			'shipping'          => array( 'title' => 2, 'parcelshop' => '', 'parcelshop_post_number' => '3242421' ),
		) );

		$response = $this->server->dispatch( $request );
		$data  = $response->get_data();

		// GET Product
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/' . $simple->get_id() ) );
		$customer  = $response->get_data();

		$this->assertEquals( 2, $customer['billing']['title'] );
		$this->assertEquals( 2, $customer['shipping']['title'] );
		$this->assertEquals( false, $customer['shipping']['parcelshop'] );
		$this->assertEquals( '3242421', $customer['shipping']['parcelshop_post_number'] );

		$this->assertEquals( array(
			'holder' => 'John Doe',
			'iban' => 'AT242424',
			'bic' => 'A424242',
		), $customer['direct_debit'] );

		$simple->delete( true );
	}
}
