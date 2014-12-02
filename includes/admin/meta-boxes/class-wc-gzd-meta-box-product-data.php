<?php
/**
 * Adds unit price and delivery time to Product metabox.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Germanized_Meta_Box_Product_Data
 */
class WC_Germanized_Meta_Box_Product_Data {
	
	public static function init() {
		add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'output' ));
		add_action( 'woocommerce_process_product_meta_simple', array( __CLASS__, 'save' ), 1 );
		add_action( 'woocommerce_process_product_meta_external', array( __CLASS__, 'save' ), 1 );
	}

	public static function output() {
		woocommerce_wp_select( array( 'id' => '_unit', 'label' => __( 'Unit', 'woocommerce-germanized' ), 'options' => array_merge( array( 'none' => __( 'Select unit', 'woocommerce-germanized' ) ), WC_germanized()->units->get_units() ), 'desc_tip' => true, 'description' => __( 'Needed if selling on a per unit basis', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_base', 'label' => __( 'Unit Base', 'woocommerce-germanized' ), 'data_type' => 'decimal', 'desc_tip' => true, 'description' => __( 'Unit price per amount (e.g. 100)', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_price_regular', 'label' => __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_price_sale', 'label' => __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
	}

	public static function save($post_id) {
		if ( isset( $_POST['_unit'] ) ) {
			update_post_meta( $post_id, '_unit', sanitize_text_field( $_POST['_unit'] ) );
		}
		if ( isset( $_POST['_unit_base'] ) ) {
			update_post_meta( $post_id, '_unit_base', ( $_POST['_unit_base'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_base'] ) );
		}
		if ( isset( $_POST['_unit_price_regular'] ) ) {
			update_post_meta( $post_id, '_unit_price_regular', ( $_POST['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_regular'] ) );
			update_post_meta( $post_id, '_unit_price', ( $_POST['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_regular'] ) );
		}
		if ( isset( $_POST['_unit_price_sale'] ) ) {
			update_post_meta( $post_id, '_unit_price_sale', '' );
			// Update Sale Price only if is on sale (Cron?!)
			if ( get_post_meta( $post_id, '_price', true ) != $_POST['_regular_price'] && $_POST['_unit_price_sale'] !== '' ) {
				update_post_meta( $post_id, '_unit_price_sale', ( $_POST['_unit_price_sale'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_sale'] ) );
				update_post_meta( $post_id, '_unit_price', ( $_POST['_unit_price_sale'] === '' ) ? '' : wc_format_decimal( $_POST['_unit_price_sale'] ) );
			}
		}
		if ( isset( $_POST[ '_mini_desc' ] ) ) {
			update_post_meta( $post_id, '_mini_desc', esc_html( $_POST[ '_mini_desc' ] ) );
		}
	}

}

WC_Germanized_Meta_Box_Product_Data::init();