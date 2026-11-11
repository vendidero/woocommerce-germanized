<?php

/**
 * Product attribute checkout visibility via the REST API - must behave the same for
 * Woo < 10.6 (deprecated attribute helper) and Woo >= 10.6 (attribute extra data).
 */
class WC_GZD_Product_Attributes_API extends WC_GZD_REST_Unit_Test_Case {

	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user );
	}

	protected function create_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Attribute API test' );
		$product->set_regular_price( 10 );

		$color = new WC_GZD_Product_Attribute();
		$color->set_name( 'Color' );
		$color->set_options( array( 'Red', 'Blue' ) );
		$color->set_position( 0 );
		$color->set_visible( true );
		$color->set_checkout_visible( true );

		$size = new WC_GZD_Product_Attribute();
		$size->set_name( 'Size' );
		$size->set_options( array( 'S', 'M' ) );
		$size->set_position( 1 );
		$size->set_visible( true );
		$size->set_checkout_visible( false );

		$product->set_attributes( array( $color, $size ) );
		$product->save();

		return wc_get_product( $product->get_id() );
	}

	protected function get_response_attributes( $product_id ) {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product_id ) );

		$this->assertEquals( 200, $response->get_status() );

		$attributes = array();

		foreach ( $response->get_data()['attributes'] as $attribute ) {
			$attributes[ $attribute['name'] ] = $attribute;
		}

		return $attributes;
	}

	protected function update( $product_id, $attributes ) {
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product_id );
		$request->set_body_params( array( 'attributes' => $attributes ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		return $response;
	}

	public function test_get_product_contains_checkout_visibility() {
		$product    = $this->create_product();
		$attributes = $this->get_response_attributes( $product->get_id() );

		$this->assertArrayHasKey( 'checkout_visible', $attributes['Color'] );
		$this->assertTrue( $attributes['Color']['checkout_visible'] );
		$this->assertFalse( $attributes['Size']['checkout_visible'] );
	}

	public function test_update_attributes_without_checkout_visibility_preserves_it() {
		$product = $this->create_product();

		$this->update(
			$product->get_id(),
			array(
				array( 'name' => 'Color', 'options' => array( 'Red', 'Blue', 'Green' ), 'position' => 0, 'visible' => true ),
				array( 'name' => 'Size', 'options' => array( 'S', 'M' ), 'position' => 1, 'visible' => true ),
			)
		);

		$attributes = $this->get_response_attributes( $product->get_id() );

		$this->assertSame( array( 'Red', 'Blue', 'Green' ), $attributes['Color']['options'] );
		$this->assertTrue( $attributes['Color']['checkout_visible'] );
		$this->assertFalse( $attributes['Size']['checkout_visible'] );
	}

	public function test_update_checkout_visibility() {
		$product = $this->create_product();

		$this->update(
			$product->get_id(),
			array(
				array( 'name' => 'Color', 'options' => array( 'Red', 'Blue' ), 'position' => 0, 'visible' => true, 'checkout_visible' => false ),
				array( 'name' => 'Size', 'options' => array( 'S', 'M' ), 'position' => 1, 'visible' => true, 'checkout_visible' => true ),
			)
		);

		$attributes = $this->get_response_attributes( $product->get_id() );

		$this->assertFalse( $attributes['Color']['checkout_visible'] );
		$this->assertTrue( $attributes['Size']['checkout_visible'] );

		// Also verify persisted data (not only the response) via the helper.
		$product    = wc_get_product( $product->get_id() );
		$stored     = $product->get_attributes();
		$this->assertFalse( WC_GZD_Product_Attribute_Helper::instance()->get_attribute( $stored['color'], $product )->is_checkout_visible() );
		$this->assertTrue( WC_GZD_Product_Attribute_Helper::instance()->get_attribute( $stored['size'], $product )->is_checkout_visible() );
	}
}
