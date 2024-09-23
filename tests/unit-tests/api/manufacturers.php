<?php
/**
 * Tests for Manufacturers API.
 *
 * @package Germanized\Tests\API
 * @since 3.9.0
 */

class WC_GZD_Manufacturers_API extends WC_GZD_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() : void {
		parent::setUp();
		$this->endpoint = new WC_GZD_REST_Product_Manufacturers_Controller();
		$this->user     = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test creating a deposit type.
	 *
	 * @since 3.9.0
	 */
	public function test_get_manufacturer() {
		wp_set_current_user( $this->user );

		$term = wp_insert_term( 'vendidero', 'product_manufacturer' );

		if ( ! is_wp_error( $term ) ) {
			update_term_meta( $term['term_id'], 'formatted_address', "vendidero Gmbh\nMusterstr. 36\n12207 Berlin" );
			update_term_meta( $term['term_id'], 'formatted_eu_address', "vendidero Gmbh\nMusterstr. 36\n12207 Berlin" );
		}

		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/manufacturers/' . $term['term_id'] );
		$response = $this->server->dispatch( $request );

		$manufacturer = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( $manufacturer, array(
			'id'                   => $term['term_id'],
			'name'                 => 'vendidero',
			'slug'                 => 'vendidero',
			'description'          => '',
			'count'                => 0,
			'formatted_address'    => "vendidero Gmbh\nMusterstr. 36\n12207 Berlin",
			'formatted_eu_address' => "vendidero Gmbh\nMusterstr. 36\n12207 Berlin",
		) );

		wp_delete_term( $term['term_id'], 'product_manufacturer' );
	}
}
