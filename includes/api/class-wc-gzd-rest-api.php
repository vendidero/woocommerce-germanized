<?php
/**
 * REST Support for Germanized Product Meta
 *
 * @author 		Vendidero
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_GZD_REST_API {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {

		add_filter( 'woocommerce_rest_prepare_product', array( $this, 'set_product_fields' ), 20, 3 );
		add_action( 'woocommerce_rest_insert_product', array( $this, 'save_update_product_fields' ), 20, 3 );
		add_action( 'woocommerce_rest_save_product_variation', array( $this, 'save_product_variation' ), 20, 3 );

	}

	public function save_product_variation( $variation_id, $menu_order, $request ) {

		$product = wc_get_product( $variation_id );
		$this->save_update_product_data( $request, $product );

	}

	public function save_update_product_fields( $post, $request, $inserted ) {

		$product = wc_get_product( $post );
		$this->save_update_product_data( $request, $product );
	
	}

	public function save_update_product_data( $request, $product ) {

		$data = WC_Germanized_Meta_Box_Product_Data::get_fields();
		$checkboxes = array( '_unit_price_auto', '_free_shipping' );
		$data[ 'product-type' ] = $product->get_type();

		foreach ( $data as $key => $val ) {
			
			unset( $data[ $key ] );
			
			$api_field = ( substr( $key, 0, 1 ) === '_' ? substr( $key, 1 ) : $key );
			
			if ( isset( $request[ $api_field ] ) ) {
				$data[ $key ] = $request[ $api_field ];
			}
		}

		// For checkboxes set the default value if no value has been transmitted
		foreach ( $checkboxes as $key ) {
			
			if ( ! isset( $data[ $key ] ) && get_post_meta( $product->id, $key, true ) )
				$data[ $key ] = get_post_meta( $product->id, $key, true );
		}

		WC_Germanized_Meta_Box_Product_Data::save_product_data( isset( $product->variation_id ) ? $product->variation_id : $product->id, $data );
	}

	public function set_product_fields( $response, $post, $request ) {
		
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

	private function set_product_variation_fields( $variations, $product ) {

		foreach( $variations as $key => $variation ) {
			$variations[ $key ] = array_merge( $variation, $this->get_product_data( wc_get_product( $variation[ 'id' ] ) ) );
		}

		return $variations;
	}

	private function get_product_data( $product ) {

		$product = wc_gzd_get_gzd_product( $product );

		$data = array();

		// Unit Price
		$data[ 'unit_price' ] = $product->unit_price;
		$data[ 'unit_regular_price' ] = $product->get_unit_regular_price();
		$data[ 'unit_sale_price' ] = $product->get_unit_sale_price();
		$data[ 'unit' ] = $product->get_unit();
		$data[ 'unit_base' ] = $product->unit_base;
		$data[ 'unit_product' ] = $product->unit_product;
		$data[ 'unit_price_html' ] = $product->get_unit_html();
		$data[ 'unit_price_auto' ] = $product->unit_price_auto === 'yes' ? true : false;

		// Cart Mini Description
		$data[ 'mini_desc' ] = $product->get_mini_desc();

		// Sale Labels
		$data[ 'sale_price_label' ] = $product->get_sale_price_label();
		$data[ 'sale_price_regular_label' ] = $product->get_sale_price_regular_label();

		// Delivery Time
		$data[ 'delivery_time' ] = $product->get_delivery_time();
		$data[ 'delivery_time_html' ] = $product->get_delivery_time_html();

		// Shipping costs hidden?
		$data[ 'free_shipping' ] = $product->has_free_shipping();

		return $data;
	}

}

WC_GZD_REST_API::instance();