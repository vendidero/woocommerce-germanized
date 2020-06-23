<?php

/**
 * Class WC_GZD_REST_Products_Controller
 *
 * @since 1.7.0
 */
class WC_GZD_REST_Products_Controller {

	public function __construct() {
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'prepare' ), 10, 3 );
		add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'prepare' ), 10, 3 );

		add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'insert_update' ), 10, 3 );
		add_filter( 'woocommerce_rest_pre_insert_product_variation_object', array( $this, 'insert_update' ), 10, 3 );

		add_filter( 'woocommerce_rest_product_schema', array( $this, 'schema' ) );
	}

	/**
	 * Extend schema.
	 *
	 * @wp-hook woocommerce_rest_customer_schema
	 *
	 * @param array $schema_properties Data used to create the customer.
	 *
	 * @return array
	 */
	public function schema( $schema_properties ) {

		$schema_properties['delivery_time']                                                 = array(
			'description' => __( 'Delivery Time', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Delivery Time ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Delivery Time Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Delivery Time Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'html' => array(
					'description' => __( 'Delivery Time HTML', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
		$schema_properties['sale_price_label']                                              = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Price Label ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Price Label Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Price Label Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				)
			)
		);
		$schema_properties['sale_price_regular_label']                                      = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Price Label ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Price Label Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Price Label Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				)
			)
		);
		$schema_properties['unit']                                                          = array(
			'description' => __( 'Unit', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Unit ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Unit Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Unit Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				)
			)
		);
		$schema_properties['unit_price']                                                    = array(
			'description' => __( 'Unit Price', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'base'          => array(
					'description' => __( 'Unit Base', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'product'       => array(
					'description' => __( 'Unit Product', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_auto'    => array(
					'description' => __( 'Unit Auto Calculation', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' )
				),
				'price'         => array(
					'description' => __( 'Current Unit Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_regular' => array(
					'description' => __( 'Unit Regular Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_sale'    => array(
					'description' => __( 'Unit Sale Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_html'    => array(
					'description' => __( 'Unit Price HTML', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
		$schema_properties['mini_desc']                                                     = array(
			'description' => __( 'Small Cart Product Description', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['free_shipping']                                                 = array(
			'description' => __( 'Deactivate the hint for additional shipping costs', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['min_age']                                              = array(
			'description' => __( 'Age verification minimum age.', 'woocommerce-germanized' ),
			'type'        => 'string',
			'enum'        => array_merge( array( '' ), array_keys( wc_gzd_get_age_verification_min_ages() ) ),
			'default'     => '',
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['service']                                                       = array(
			'description' => __( 'Whether this product is a service or not', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['differential_taxation']                                         = array(
			'description' => __( 'Whether this product applies for differential taxation or not', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties']['delivery_time']            = array(
			'description' => __( 'Delivery Time', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Delivery Time ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Delivery Time Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Delivery Time Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'html' => array(
					'description' => __( 'Delivery Time HTML', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
		$schema_properties['variations']['items']['properties']['sale_price_label']         = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Price Label ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Price Label Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Price Label Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				)
			)
		);
		$schema_properties['variations']['items']['properties']['sale_price_regular_label'] = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id'   => array(
					'description' => __( 'Price Label ID', 'woocommerce-germanized' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Price Label Name', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Price Label Slug', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				)
			)
		);
		$schema_properties['variations']['items']['properties']['service']                  = array(
			'description' => __( 'Whether this product is a service or not', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties']['mini_desc']                = array(
			'description' => __( 'Small Cart Product Description', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties']['min_age']                  = array(
			'description' => __( 'Age verification minimum age.', 'woocommerce-germanized' ),
			'type'        => 'string',
			'enum'        => array_merge( array( '' ), array_keys( wc_gzd_get_age_verification_min_ages() ) ),
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties']['unit_price']               = array(
			'description' => __( 'Unit Price', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'base'          => array(
					'description' => __( 'Unit Base', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'product'       => array(
					'description' => __( 'Unit Product', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_auto'    => array(
					'description' => __( 'Unit Auto Calculation', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' )
				),
				'price'         => array(
					'description' => __( 'Current Unit Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_regular' => array(
					'description' => __( 'Unit Regular Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_sale'    => array(
					'description' => __( 'Unit Sale Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_html'    => array(
					'description' => __( 'Unit Price HTML', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);

		return $schema_properties;
	}

	public function prepare( $response, $post, $request ) {
		$product = wc_get_product( $post );

		// Add variations to variable products.
		if ( $product->is_type( 'variable' ) && $product->has_child() ) {

			$data               = $response->data;
			$data['variations'] = $this->set_product_variation_fields( $response->data['variations'], $product );
			$response->set_data( $data );

		}

		$response->set_data( array_merge( $response->data, $this->get_product_data( $product ) ) );

		/**
		 * Filter to adjust the REST response after preparing the product.
		 *
		 * @param WP_REST_Response $response The response.
		 * @param WC_Product $product The product object.
		 * @param WP_REST_Request $request The request object.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_rest_prepare_product', $response, $product, $request );
	}

	public function insert_update( $product, $request, $inserted ) {
		$product = $this->save_update_product_data( $request, $product );

		return $product;
	}

	/**
	 * @param $request
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public function get_product_saveable_data( $request, $product ) {

		$data_saveable = WC_Germanized_Meta_Box_Product_Data::get_fields();
		$gzd_product   = wc_gzd_get_product( $product );
		$data          = array();

		$data['product-type'] = $product->get_type();

		// Delivery time
		$current = get_the_terms( $product->get_id(), 'product_delivery_time' );
		$default = '';

		if ( ! empty( $current ) ) {
			$default = $current[0]->term_id;
		}

		$data['delivery_time'] = $this->get_term_data( isset( $request['delivery_time'] ) ? $request['delivery_time'] : false, $default );

		// Price Labels + Unit
		$meta_data = array(
			'sale_price_label'         => WC_germanized()->price_labels,
			'sale_price_regular_label' => WC_germanized()->price_labels,
			'unit'                     => WC_germanized()->units,
		);

		foreach ( $meta_data as $meta => $taxonomy_obj ) {
			$current = 0;
			$getter  = "get_{$meta}";

			if ( is_callable( array( $gzd_product, $getter ) ) ) {
				$current = $gzd_product->$getter();
			}

			$term_data           = $this->get_term_data( isset( $request[ $meta ] ) ? $request[ $meta ] : false, $current );
			$data[ '_' . $meta ] = '';

			if ( ! empty( $term_data ) ) {
				$term = $taxonomy_obj->get_term_object( $term_data, ( is_numeric( $term_data ) ? 'id' : 'slug' ) );

				if ( $term ) {
					$data[ '_' . $meta ] = $term->slug;
				}
			}
		}

		// Set Unit Price Checkbox to current product value
		$data['_unit_price_auto'] = $gzd_product->get_unit_price_auto();

		if ( isset( $request['unit_price'] ) && is_array( $request['unit_price'] ) ) {

			foreach ( $request['unit_price'] as $key => $val ) {
				if ( isset( $data_saveable[ '_unit_' . $key ] ) ) {
					$data[ '_unit_' . $key ] = sanitize_text_field( $val );
				}
			}

			if ( isset( $data['_unit_price_auto'] ) ) {
				if ( ! empty( $data['_unit_price_auto'] ) ) {
					$data['_unit_price_auto'] = true;
				}
			} else {
				$data['_unit_price_auto'] = $gzd_product->get_unit_price_auto();
			}

			// Do only add boolean values if is set so saving works (checkbox-style).
			if ( empty( $data['_unit_price_auto'] ) || ! $data['_unit_price_auto'] ) {
				unset( $data['_unit_price_auto'] );
			}

			if ( isset( $data['_unit_price_sale'] ) ) {
				$data['_sale_price'] = $product->get_sale_price();
			}
		}

		if ( isset( $request['mini_desc'] ) ) {
			$data['_mini_desc'] = wc_gzd_sanitize_html_text_field( $request['mini_desc'] );
		}

		if ( isset( $request['min_age'] ) ) {
			$data['_min_age'] = esc_attr( $request['min_age'] );
		}

		foreach ( array( 'free_shipping', 'service', 'differential_taxation' ) as $bool_meta ) {
			if ( isset( $request[ $bool_meta ] ) ) {
				if ( ! empty( $request[ $bool_meta ] ) ) {
					$data["_{$bool_meta}"] = true;
				}
			} else {
				$getter = "get_{$bool_meta}";

				if ( is_callable( array( $gzd_product, $getter ) ) ) {
					$data["_{$bool_meta}"] = $gzd_product->$getter();
				}
			}

			// Do only add boolean values if is set so saving works (checkbox-style).
			if ( empty( $data["_{$bool_meta}"] ) || ! $data["_{$bool_meta}"] ) {
				unset( $data["_{$bool_meta}"] );
			}
		}

		return $data;
	}

	/**
	 * Makes sure that term data uses default data if no request data was received. Deletes the term data by returning an empty string if request data is empty.
	 *
	 * @param $request_data
	 * @param int $current
	 *
	 * @return array|int|string
	 */
	protected function get_term_data( $request_data, $current = 0 ) {
		$data = '';

		if ( false === $request_data ) {
			$data = $current;
		} elseif ( is_array( $request_data ) && isset( $request_data['id'] ) ) {
			$data = absint( $request_data['id'] );
		} elseif ( is_array( $request_data ) && isset( $request_data['slug'] ) ) {
			$data = wc_clean( $request_data['slug'] );
		}

		return $data;
	}

	public function save_update_product_data( $request, $product ) {
		$data            = $this->get_product_saveable_data( $request, $product );
		$data['is_rest'] = true;
		$data['save']    = false;

		$product = WC_Germanized_Meta_Box_Product_Data::save_product_data( $product, $data );

		return $product;
	}

	private function set_product_variation_fields( $variations, $product ) {
		foreach ( $variations as $key => $variation ) {
			if ( isset( $variation['id'] ) ) {
				$variations[ $key ] = array_merge( $variation, $this->get_product_data( wc_get_product( $variation['id'] ) ) );
			}
		}

		return $variations;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	private function get_product_data( $product ) {

		$gzd_product = wc_gzd_get_product( $product );
		$data        = array();

		if ( ! $product->is_type( 'variation' ) ) {
			$data['unit'] = $this->prepare_term( WC_germanized()->units->get_term_object( $gzd_product->get_unit() ) );
		}

		// Unit Price
		$data['unit_price'] = array(
			'base'          => $gzd_product->get_unit_base(),
			'product'       => $gzd_product->get_unit_product(),
			'price_auto'    => $gzd_product->is_unit_price_calculated_automatically(),
			'price'         => $gzd_product->get_unit_price(),
			'price_regular' => $gzd_product->get_unit_price_regular(),
			'price_sale'    => $gzd_product->get_unit_price_sale(),
			'price_html'    => $gzd_product->get_unit_price_html(),
		);

		// Cart Mini Description
		$data['mini_desc'] = $gzd_product->get_cart_description() ? $gzd_product->get_cart_description() : '';

		// Age verification
		$data['min_age'] = $gzd_product->get_min_age( 'edit' );

		// Sale Labels
		$data['sale_price_label']         = $this->prepare_term( WC_germanized()->price_labels->get_term_object( $gzd_product->get_sale_price_label() ) );
		$data['sale_price_regular_label'] = $this->prepare_term( WC_germanized()->price_labels->get_term_object( $gzd_product->get_sale_price_regular_label() ) );

		// Delivery Time
		$data['delivery_time'] = $this->prepare_term( $gzd_product->get_delivery_time_term() );

		if ( ! empty( $data['delivery_time'] ) ) {
			$data['delivery_time']['html'] = $gzd_product->get_delivery_time_html();
		}

		// Shipping costs hidden?
		$data['free_shipping'] = $gzd_product->has_free_shipping();

		// Shipping costs hidden?
		$data['service'] = $gzd_product->is_service();

		// Shipping costs hidden?
		$data['differential_taxation'] = $gzd_product->is_differential_taxed();

		return $data;
	}

	private function prepare_term( $term ) {
		if ( ! empty( $term ) && is_object( $term ) && ! is_wp_error( $term ) ) {
			return array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return array();
	}
}
