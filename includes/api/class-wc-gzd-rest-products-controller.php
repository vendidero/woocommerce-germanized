<?php
/**
 * Class WC_GZD_REST_Products_Controller
 *
 * @since 1.7.0
 */
class WC_GZD_REST_Products_Controller {

	public function __construct() {

		// v3
		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'prepare' ), 10, 3 );
			add_filter( 'woocommerce_rest_prepare_product_variation_object', array( $this, 'prepare' ), 10, 3 );

			add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'insert_update_v3' ), 10, 3 );
			add_filter( 'woocommerce_rest_pre_insert_product_variation_object', array( $this, 'insert_update_v3' ), 10, 3 );
		} else {
			add_filter( 'woocommerce_rest_prepare_product', array( $this, 'prepare' ), 10, 3 );
			add_action( 'woocommerce_rest_insert_product', array( $this, 'insert_update' ), 10, 3 );
			add_action( 'woocommerce_rest_save_product_variation', array( $this, 'save_variation' ), 10, 3 );
		}

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

		$schema_properties['delivery_time'] = array(
			'description' => __( 'Delivery Time', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['sale_price_label'] = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['sale_price_regular_label'] = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['unit'] = array(
			'description' => __( 'Unit', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['unit_price'] = array(
			'description' => __( 'Unit Price', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'base' => array(
					'description' => __( 'Unit Base', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'product' => array(
					'description' => __( 'Unit Product', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_auto' => array(
					'description' => __( 'Unit Auto Calculation', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' )
				),
				'price' => array(
					'description' => __( 'Current Unit Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_regular' => array(
					'description' => __( 'Unit Regular Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_sale' => array(
					'description' => __( 'Unit Sale Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_html' => array(
					'description' => __( 'Unit Price HTML', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
		$schema_properties['mini_desc'] = array(
			'description' => __( 'Small Cart Product Description', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['free_shipping'] = array(
			'description' => __( 'Deactivate the hint for additional shipping costs', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['service'] = array(
			'description' => __( 'Whether this product is a service or not', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['differential_taxation'] = array(
			'description' => __( 'Whether this product applies for differential taxation or not', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties'][ 'delivery_time' ] = array(
			'description' => __( 'Delivery Time', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['variations']['items']['properties'][ 'sale_price_label' ] = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['variations']['items']['properties'][ 'sale_price_regular_label' ] = array(
			'description' => __( 'Price Label', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'id' => array(
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
		$schema_properties['variations']['items']['properties'][ 'service' ] = array(
			'description' => __( 'Whether this product is a service or not', 'woocommerce-germanized' ),
			'type'        => 'boolean',
			'default'     => false,
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties'][ 'mini_desc' ] = array(
			'description' => __( 'Small Cart Product Description', 'woocommerce-germanized' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
		);
		$schema_properties['variations']['items']['properties'][ 'unit_price' ] = array(
			'description' => __( 'Unit Price', 'woocommerce-germanized' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'properties'  => array(
				'base' => array(
					'description' => __( 'Unit Base', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'product' => array(
					'description' => __( 'Unit Product', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_auto' => array(
					'description' => __( 'Unit Auto Calculation', 'woocommerce-germanized' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' )
				),
				'price' => array(
					'description' => __( 'Current Unit Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_regular' => array(
					'description' => __( 'Unit Regular Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_sale' => array(
					'description' => __( 'Unit Sale Price', 'woocommerce-germanized' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'price_html' => array(
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

			$data = $response->data;
			$data[ 'variations' ] = $this->set_product_variation_fields( $response->data[ 'variations' ], $product );
			$response->set_data( $data );

		}

		$response->set_data( array_merge( $response->data, $this->get_product_data( $product ) ) );

		return apply_filters( 'woocommerce_gzd_rest_prepare_product', $response, $product, $request );

	}

	public function insert_update_v3( $product, $request, $inserted ) {

		$product = $this->save_update_product_data( $request, $product );

		return $product;
	}

	public function insert_update( $post, $request, $inserted ) {

		$product = wc_get_product( $post );
		$product = $this->save_update_product_data( $request, $product );

		return $product;
	}

	public function save_variation( $variation_id, $menu_order, $request ) {

		$product = wc_get_product( $variation_id );
		$product = $this->save_update_product_data( $request, $product );

		return $product;
	}

	public function get_product_saveable_data( $request, $product ) {

		$data_saveable = WC_Germanized_Meta_Box_Product_Data::get_fields();
		$data = array();

		$data[ 'product-type' ] = $product->get_type();

		if ( isset( $request['delivery_time'] ) && is_array( $request['delivery_time'] ) ) {
			if ( isset( $request['delivery_time']['id'] ) ) {
				$data[ 'delivery_time' ] = intval( $request['delivery_time']['id'] );
			} elseif ( isset( $request['delivery_time']['slug'] ) ) {
				$data[ 'delivery_time' ] = sanitize_text_field( $request['delivery_time']['id'] );
			}
		}

		// Price Labels + Unit
		$meta_data = array(
			'sale_price_label' => WC_germanized()->price_labels,
			'sale_price_regular_label' => WC_germanized()->price_labels,
			'unit' => WC_germanized()->units,
		);

		foreach ( $meta_data as $meta => $taxonomy_obj ) {
			if ( isset( $request[$meta] ) && is_array( $request[$meta] ) ) {
				$term = null;
				if ( isset( $request[$meta]['id'] ) ) {
					$term = $taxonomy_obj->get_term_object( absint( $request[$meta]['id'] ), 'id' );
				}
				elseif ( isset( $request[$meta]['slug'] ) ) {
					$term = $taxonomy_obj->get_term_object( sanitize_text_field( $request[$meta]['slug'] ) );
				}
				if ( $term ) {
					$data[ '_' . $meta ] = $term->slug;
				}
			}
		}

		if ( isset( $request['unit_price'] ) && is_array( $request['unit_price'] ) ) {

			foreach ( $request['unit_price'] as $key => $val ) {

				if ( isset( $data_saveable[ '_unit_' . $key ] ) ) {
					$data[ '_unit_' . $key ] = sanitize_text_field( $val );
				}
			}

			if ( isset( $data[ '_unit_price_auto' ] ) && ! empty( $data[ '_unit_price_auto' ] ) ) {
				$data['_unit_price_auto'] = true;
			} elseif ( empty( $data[ '_unit_price_auto' ] ) ) {
				unset( $data['_unit_price_auto'] );
			} else {
				$data['_unit_price_auto'] = wc_gzd_get_crud_data( $product, '_unit_price_auto' );
			}

			if ( isset( $data['_unit_price_sale'] ) ) {
				$data['_sale_price'] = wc_gzd_get_crud_data( $product, 'sale_price' );
			}
		}

		if ( isset( $request['mini_desc'] ) ) {
			$data['_mini_desc'] = wc_gzd_sanitize_html_text_field( $request['mini_desc'] );
		}

		foreach( array( 'free_shipping', 'service', 'differential_taxation' ) as $bool_meta ) {

			if ( isset( $request[$bool_meta] ) ) {
				if ( ! empty( $request[$bool_meta] ) )
					$data[ "_{$bool_meta}" ] = true;
			} else {
				$data[ "_{$bool_meta}" ] = wc_gzd_get_crud_data( $product, "_{$bool_meta}", true );
			}

			// Do only add boolean values if is set so saving works (checkbox-style).
			if ( empty( $data[ "_{$bool_meta}" ] ) || ! $data[ "_{$bool_meta}" ] )
				unset( $data[ "_{$bool_meta}" ] );
		}

		return $data;
	}

	public function save_update_product_data( $request, $product ) {

		$data = $this->get_product_saveable_data( $request, $product );
		$data['save'] = false;

		$product = WC_Germanized_Meta_Box_Product_Data::save_product_data( $product, $data );

		return $product;
	}

	private function set_product_variation_fields( $variations, $product ) {

		foreach( $variations as $key => $variation ) {
			if( isset($variation[ 'id' ]) ) {
				$variations[ $key ] = array_merge( $variation, $this->get_product_data( wc_get_product( $variation[ 'id' ] ) ) );
			}
		}

		return $variations;
	}

	private function get_product_data( $product ) {

		$product = wc_gzd_get_gzd_product( $product );

		$data = array();

		if ( ! $product->is_type( 'variation' ) ) {
			$data[ 'unit' ]	= $this->prepare_term( WC_germanized()->units->get_term_object( wc_gzd_get_crud_data( $product, 'unit' ) ) );
		}

		// Unit Price
		$data[ 'unit_price' ] 	 = array(
			'base'			 	 => $product->get_unit_base(),
			'product'		 	 => $product->get_unit_products(),
			'price_auto'	 	 => $product->is_unit_price_calculated_automatically(),
			'price'	 	 		 => $product->get_unit_price(),
			'price_regular' 	 => $product->get_unit_regular_price(),
			'price_sale'	 	 => $product->get_unit_sale_price(),
			'price_html'	 	 => $product->get_unit_html(),
		);

		// Cart Mini Description
		$data[ 'mini_desc' ] = $product->get_mini_desc() ? $product->get_mini_desc() : '';

		// Sale Labels
		$data[ 'sale_price_label' ] = $this->prepare_term( WC_germanized()->price_labels->get_term_object( $product->get_sale_price_label() ) );
		$data[ 'sale_price_regular_label' ] = $this->prepare_term( WC_germanized()->price_labels->get_term_object( $product->get_sale_price_regular_label() ) );

		// Delivery Time
		$data[ 'delivery_time' ] = $this->prepare_term( $product->get_delivery_time_term() );

		if ( ! empty( $data[ 'delivery_time' ] ) ) {
			$data[ 'delivery_time' ][ 'html' ] = $product->get_delivery_time_html();
		}

		// Shipping costs hidden?
		$data[ 'free_shipping' ] = $product->has_free_shipping();

		// Shipping costs hidden?
		$data[ 'service' ] = $product->is_service();

		// Shipping costs hidden?
		$data[ 'differential_taxation' ] = $product->is_differential_taxed();

		return $data;
	}

	private function prepare_term( $term ) {

		if ( ! empty( $term ) && is_object( $term ) && ! is_wp_error( $term ) ) {
			return array(
				'id' => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return array();
	}

}
