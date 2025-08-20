<?php
/**
 * Tests for Deposit Types API.
 *
 * @package Germanized\Tests\API
 * @since 3.9.0
 */

class WC_GZD_Deposit_Types_API extends WC_GZD_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() : void {
		parent::setUp();
		$this->endpoint = new WC_GZD_REST_Product_Deposit_Types_Controller();
		$this->user     = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test creating a deposit type.
	 *
	 * @since 3.9.0
	 */
	public function test_get_deposit_type() {
		wp_set_current_user( $this->user );

		$term = wp_insert_term( 'Can', 'product_deposit_type' );

		if ( ! is_wp_error( $term ) ) {
			update_term_meta( $term['term_id'], 'deposit', wc_format_decimal( 0.25, '' ) );
			update_term_meta( $term['term_id'], 'deposit_packaging_type', 'disposable' );
		}

		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/deposit_types/' . $term['term_id'] );
		$response = $this->server->dispatch( $request );

		$delivery_time = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( $delivery_time, array(
			'id'                   => $term['term_id'],
			'name'                 => 'Can',
			'slug'                 => 'can',
			'description'          => '',
			'count'                => 0,
			'deposit'              => '0.25',
			'tax_status'           => 'taxable',
			'packaging_type'       => 'disposable',
			'packaging_type_title' => WC_germanized()->deposit_types->get_packaging_type_title( 'disposable' ),
		) );

		wp_delete_term( $term['term_id'], 'product_deposit_type' );
	}
}
