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
	public function setUp() : void {
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
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $simple->get_id() ) );
		$product  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$country_specific = array_values( $product['country_specific_delivery_times'] );

		$this->assertEquals( 'new-price', $product['sale_price_label']['slug'] );
		$this->assertEquals( 'old-price', $product['sale_price_regular_label']['slug'] );

		$this->assertEquals( '2-3-days', $product['delivery_time']['slug'] );
		$this->assertEquals( '4-5-days', $country_specific[0]['slug'] );
		$this->assertEquals( 'AT', $country_specific[0]['country'] );
		$this->assertEquals( '3-4-days', $country_specific[1]['slug'] );
		$this->assertEquals( 'BG', $country_specific[1]['country'] );

		$this->assertEquals( '1', $product['unit_price']['product'] );
		$this->assertEquals( '100.0', $product['unit_price']['price_regular'] );
		$this->assertEquals( '90.0', $product['unit_price']['price_sale'] );
		$this->assertEquals( 'This is a test', trim( strip_tags( $product['mini_desc'] ) ) );
		$this->assertEquals( 'This is a defect desc', trim( strip_tags( $product['defect_description'] ) ) );
		$this->assertEquals( true, $product['service'] );
		$this->assertEquals( true, $product['used_good'] );
		$this->assertEquals( true, $product['defective_copy'] );
		$this->assertEquals( true, $product['differential_taxation'] );
		$this->assertEquals( true, $product['free_shipping'] );

		$simple->delete( true );
	}

	public function test_create_product() {
		wp_set_current_user( $this->user );

		$term      = wp_insert_term( '3-4 days', 'product_delivery_time', array( 'slug' => '3-4-days' ) );
		$sale_term = wp_insert_term( 'Test Sale', 'product_price_label', array( 'slug' => 'test-sale' ) );

		// Create simple.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$request->set_body_params(
			array(
				'type'                     => 'simple',
				'name'                     => 'Test Simple Product',
				'sku'                      => 'DUMMY SKU SIMPLE API',
				'regular_price'            => '10',
				'sale_price'               => '5',
				'shipping_class'           => 'test',
				'delivery_time'            => array( 'id' => $term['term_id'] ),
				'country_specific_delivery_times' => array(
					array(
						'slug'    => '4-5-days',
						'country' => 'CH'
					),
				),
				'unit_price'               => array( 'price_regular' => '80.0', 'price_sale' => '70.0' ),
				'mini_desc'                => 'This is a test',
				'defect_description'       => 'This is a defect desc',
				'sale_price_label'         => array( 'id' => $sale_term['term_id'] ),
				'sale_price_regular_label' => array( 'id' => $sale_term['term_id'] ),
				'differential_taxation'    => false,
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'test-sale', $data['sale_price_label']['slug'] );
		$this->assertEquals( '3-4-days', $data['delivery_time']['slug'] );
		$this->assertEquals( '4-5-days', $data['country_specific_delivery_times'][0]['slug'] );
		$this->assertEquals( 'CH', $data['country_specific_delivery_times'][0]['country'] );
		$this->assertEquals( 'test-sale', $data['sale_price_regular_label']['slug'] );
		$this->assertEquals( '80.0', $data['unit_price']['price_regular'] );
		$this->assertEquals( '70.0', $data['unit_price']['price_sale'] );
	}

	public function test_create_product_variation() {
		wp_set_current_user( $this->user );

		$variable  = WC_GZD_Helper_Product::create_variation_product();
		$sale_term = wp_insert_term( 'Test Sale', 'product_price_label', array( 'slug' => 'test-sale' ) );
		$term      = get_term_by( 'slug', '3-4-days', 'product_delivery_time' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $variable->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
				'regular_price' => '12',
				'sale_price'    => '10',
				'description'   => 'A medium size.',
				'attributes'    => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
				'delivery_time'            => array( 'id' => $term->term_id ),
				'country_specific_delivery_times' => array(
					array(
						'slug'    => '4-5-days',
						'country' => 'CH'
					),
				),
				'unit_price'               => array( 'price_regular' => '80.0', 'price_sale' => '70.0' ),
				'mini_desc'                => 'This is a test',
				'defect_description'       => 'This is a defect desc',
				'sale_price_label'         => array( 'id' => $sale_term['term_id'] ),
				'sale_price_regular_label' => array( 'id' => $sale_term['term_id'] ),
				'differential_taxation'    => false,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 'test-sale', $variation['sale_price_label']['slug'] );
		$this->assertEquals( '3-4-days', $variation['delivery_time']['slug'] );
		$this->assertEquals( '4-5-days', $variation['country_specific_delivery_times'][0]['slug'] );
		$this->assertEquals( 'CH', $variation['country_specific_delivery_times'][0]['country'] );
		$this->assertEquals( 'test-sale', $variation['sale_price_regular_label']['slug'] );
		$this->assertEquals( '80.0', $variation['unit_price']['price_regular'] );
		$this->assertEquals( '70.0', $variation['unit_price']['price_sale'] );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product() {
		wp_set_current_user( $this->user );

		// test simple products
		$simple = WC_GZD_Helper_Product::create_simple_product();
		$term   = get_term_by( 'slug', '3-4-days', 'product_delivery_time' );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'delivery_time'         => array( 'id' => $term->term_id ),
			'country_specific_delivery_times' => array(
				array(
					'slug'    => '4-5-days',
					'country' => 'CH'
				),
				array(
					'slug'    => '8-9-days',
					'country' => 'AT'
				),
			),
			'unit_price'            => array( 'price_regular' => '80.0', 'price_sale' => '70.0' ),
			'differential_taxation' => false,
		) );

		$response = $this->server->dispatch( $request );
		$product  = $response->get_data();

		$this->assertEquals( '3-4-days', $product['delivery_time']['slug'] );
		/**
		 * BE will be automatically dropped as it equals the newly added default delivery time (3-4 days)
		 */
		$this->assertEquals( '8-9-days', $product['country_specific_delivery_times'][0]['slug'] );
		$this->assertEquals( 'AT', $product['country_specific_delivery_times'][0]['country'] );
		$this->assertEquals( '4-5-days', $product['country_specific_delivery_times'][1]['slug'] );
		$this->assertEquals( 'CH', $product['country_specific_delivery_times'][1]['country'] );
		$this->assertEquals( 2, sizeof( $product['country_specific_delivery_times'] ) );

		$this->assertEquals( '80.0', $product['unit_price']['price_regular'] );
		$this->assertEquals( '70.0', $product['unit_price']['price_sale'] );

		$this->assertEquals( 'new-price', $product['sale_price_label']['slug'] );
		$this->assertEquals( 'old-price', $product['sale_price_regular_label']['slug'] );
		$this->assertEquals( '1', $product['unit_price']['product'] );
		$this->assertEquals( 'This is a test', trim( strip_tags( $product['mini_desc'] ) ) );
		$this->assertEquals( 'This is a defect desc', trim( strip_tags( $product['defect_description'] ) ) );
		$this->assertEquals( true, $product['free_shipping'] );
		$this->assertEquals( false, $product['differential_taxation'] );
		$this->assertEquals( true, $product['service'] );
		$this->assertEquals( true, $product['used_good'] );
		$this->assertEquals( true, $product['defective_copy'] );

		$simple->delete( true );
	}

	public function test_update_product_booleans() {
		wp_set_current_user( $this->user );

		// test simple products
		$simple = WC_GZD_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'unit_price' => array( 'price_regular' => '80.0', 'price_sale' => '70.0' ),
		) );

		$response = $this->server->dispatch( $request );
		$product  = $response->get_data();

		$this->assertEquals( '80.0', $product['unit_price']['price_regular'] );
		$this->assertEquals( '70.0', $product['unit_price']['price_sale'] );

		/**
		 * Make sure booleans are not unset in case not included within request
		 */
		$this->assertEquals( true, $product['differential_taxation'] );
		$this->assertEquals( true, $product['service'] );

		$simple->delete( true );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product_unit_auto() {
		wp_set_current_user( $this->user );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 9 );

		$data = array(
			'_unit'            => 'g',
			'_unit_base'       => '10',
			'_unit_product'    => '1',
			'_unit_price_auto' => true,
		);

		foreach ( $data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->save();

		$simple = wc_gzd_get_gzd_product( $product );

		// Update the product without inserting unit_price_auto
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'regular_price' => '20',
			'sale_price'    => '10',
		) );

		$response = $this->server->dispatch( $request );
		$product  = $response->get_data();

		$this->assertEquals( true, $product['unit_price']['price_auto'] );
		// $this->assertEquals( '100.0', $product['unit_price']['price'] );
		// $this->assertEquals( '200.0', $product['unit_price']['price_regular'] );

		$simple->delete( true );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.0.0
	 */
	public function test_update_product_variation() {
		wp_set_current_user( $this->user );

		$variable          = WC_GZD_Helper_Product::create_variation_product();
		$children          = $variable->get_children();
		$variation_id      = $children[0];
		$variation_product = wc_get_product( $variation_id );
		$term              = get_term_by( 'slug', '3-4-days', 'product_delivery_time' );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $variable->get_id() . '/variations/' . $variation_id );

		$request->set_body_params( array(
			'delivery_time' => array( 'id' => $term->term_id ),
			'country_specific_delivery_times' => array(
				array(
					'slug'    => '9-12 Days',
					'country' => 'AT'
				),
				array(
					'slug'    => '10-15 Days',
					'country' => 'DK'
				),
				array(
					'slug'    => '8-9 Days',
					'country' => 'BG'
				),
			),
			'unit_price'            => array( 'price_regular' => '80.0', 'price_sale' => '70.0', 'base' => 20 ),
			'mini_desc'             => 'This is a test',
			'defect_description'    => 'This is a defect desc',
			'differential_taxation' => true,
			'service'               => false,
			'used_good'             => false,
			'defective_copy'        => false,
		) );

		$response = $this->server->dispatch( $request );
		$product  = $response->get_data();

		$this->assertEquals( '3-4-days', $product['delivery_time']['slug'] );
		$this->assertEquals( '9-12-days', $product['country_specific_delivery_times'][0]['slug'] );
		$this->assertEquals( 'AT', $product['country_specific_delivery_times'][0]['country'] );
		$this->assertEquals( '8-9-days', $product['country_specific_delivery_times'][1]['slug'] );
		$this->assertEquals( 'BG', $product['country_specific_delivery_times'][1]['country'] );
		$this->assertEquals( '10-15-days', $product['country_specific_delivery_times'][2]['slug'] );
		$this->assertEquals( 'DK', $product['country_specific_delivery_times'][2]['country'] );
		$this->assertEquals( 3, sizeof( $product['country_specific_delivery_times'] ) );

		$this->assertEquals( '80.0', $product['unit_price']['price_regular'] );
		$this->assertEquals( '10', $product['unit_price']['base'] );
		$this->assertEquals( '1', $product['unit_price']['product'] );

		$this->assertEquals( 'new-price', $product['sale_price_label']['slug'] );
		$this->assertEquals( 'old-price', $product['sale_price_regular_label']['slug'] );
		$this->assertEquals( 'This is a test', trim( strip_tags( $product['mini_desc'] ) ) );
		$this->assertEquals( 'This is a defect desc', trim( strip_tags( $product['defect_description'] ) ) );
		$this->assertEquals( true, $product['free_shipping'] );
		$this->assertEquals( false, $product['service'] );
		$this->assertEquals( false, $product['defective_copy'] );
		$this->assertEquals( false, $product['used_good'] );

		$variable->delete( true );
	}

	public function test_update_product_leaves_terms() {
		wp_set_current_user( $this->user );

		// test simple products
		$simple    = WC_GZD_Helper_Product::create_simple_product();
		$sale_term = wp_insert_term( 'Test Sale', 'product_price_label', array( 'slug' => 'test-sale' ) );
		$term      = get_term_by( 'slug', '3-4-days', 'product_delivery_time' );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'delivery_time'            => array( 'id' => $term->term_id ),
			'sale_price_label'         => array( 'id' => $sale_term['term_id'] ),
			'sale_price_regular_label' => array( 'id' => $sale_term['term_id'] ),
			'differential_taxation'    => false,
		) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Test whether term data is being available after updating the product if no term data as transmitted
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'differential_taxation' => true,
		) );

		$response = $this->server->dispatch( $request );
		$product  = $response->get_data();

		$this->assertEquals( 'test-sale', $product['sale_price_label']['slug'] );
		$this->assertEquals( '3-4-days', $product['delivery_time']['slug'] );
		$this->assertEquals( '4-5-days', $product['country_specific_delivery_times'][0]['slug'] );
		$this->assertEquals( 'test-sale', $product['sale_price_regular_label']['slug'] );
		$this->assertEquals( true, $product['differential_taxation'] );

		$simple->delete( true );
	}

	public function test_delete_term_data() {
		wp_set_current_user( $this->user );

		// test simple products
		$simple  = WC_GZD_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $simple->get_id() );
		$request->set_body_params( array(
			'delivery_time'            => array(),
			'country_specific_delivery_times' => array(),
			'sale_price_label'         => array(),
			'sale_price_regular_label' => array(),
		) );

		$response = $this->server->dispatch( $request );
		$product  = $response->get_data();

		$this->assertEmpty( $product['delivery_time'] );
		$this->assertEmpty( $product['country_specific_delivery_times'] );
		$this->assertEmpty( $product['sale_price_label'] );
		$this->assertEmpty( $product['sale_price_regular_label'] );

		$simple->delete( true );
	}
}
