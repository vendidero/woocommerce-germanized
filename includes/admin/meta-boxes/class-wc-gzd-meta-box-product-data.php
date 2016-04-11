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
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'output' ) );
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping' ) );
		add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'output_pricing' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ), 20, 2 );
	}

	public static function output_pricing() {

		global $post, $thepostid;
		$thepostid = $post->ID;
		$_product = wc_get_product( $thepostid );

		woocommerce_wp_select( array( 'id' => '_sale_price_label', 'label' => __( 'Sale Label', 'woocommerce-germanized' ), 'options' => array_merge( array( "-1" => __( 'Select Price Label', 'woocommerce-germanized' ) ), WC_germanized()->price_labels->get_labels() ), 'desc_tip' => true, 'description' => __( 'If the product is on sale you may want to show a price label right before outputting the old price to inform the customer.', 'woocommerce-germanized' ) ) );
		woocommerce_wp_select( array( 'id' => '_sale_price_regular_label', 'label' => __( 'Sale Regular Label', 'woocommerce-germanized' ), 'options' => array_merge( array( "-1" => __( 'Select Price Label', 'woocommerce-germanized' ) ), WC_germanized()->price_labels->get_labels() ), 'desc_tip' => true, 'description' => __( 'If the product is on sale you may want to show a price label right before outputting the new price to inform the customer.', 'woocommerce-germanized' ) ) );

	}

	public static function output() {

		global $post, $thepostid;
		$thepostid = $post->ID;
		$_product = wc_get_product( $thepostid );

		// Still output sale price labels if is a variable product
		if ( $_product->is_type( 'variable' ) )
			self::output_pricing();

		woocommerce_wp_select( array( 'id' => '_unit', 'label' => __( 'Unit', 'woocommerce-germanized' ), 'options' => array_merge( array( "-1" => __( 'Select unit', 'woocommerce-germanized' ) ), WC_germanized()->units->get_units() ), 'desc_tip' => true, 'description' => __( 'Needed if selling on a per unit basis', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_product', 'label' => __( 'Product Units', 'woocommerce-germanized' ), 'data_type' => 'decimal', 'desc_tip' => true, 'description' => __( 'Number of units included per default product price. Example: 1000 ml.', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_base', 'label' => __( 'Base Price Units', 'woocommerce-germanized' ), 'data_type' => 'decimal', 'desc_tip' => true, 'description' => __( 'Base price units. Example base price: 0,99 € / 100 ml. Insert 100 as base price unit amount.', 'woocommerce-germanized' ) ) );

		if ( $_product->is_virtual() ) {

			// Show delivery time selection fallback if is virtual but delivery time should be visible on product
			$types = get_option( 'woocommerce_gzd_display_delivery_time_hidden_types', array() );

			if ( ! in_array( 'virtual', $types ) ) {

				// Remove default delivery time selection - otherwise input would exist 2 times
				remove_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping' ), 10 );
				self::output_shipping();

			}
		}

		echo '<div class="show_if_simple show_if_external">';

		woocommerce_wp_checkbox( array( 'id' => '_unit_price_auto', 'label' => __( 'Calculation', 'woocommerce-germanized' ), 'description' => '<span class="wc-gzd-premium-desc">' . __( 'Calculate base prices automatically.', 'woocommerce-germanized' ) . '</span> <a href="https://vendidero.de/woocommerce-germanized#buy" target="_blank" class="wc-gzd-pro">pro</a>' ) );

		woocommerce_wp_text_input( array( 'id' => '_unit_price_regular', 'label' => __( 'Regular Base Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_price_sale', 'label' => __( 'Sale Base Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		
		echo '</div>';

	}

	public static function output_shipping() {

		global $post, $thepostid;

		$thepostid = $post->ID;
		$_product = wc_get_product( $thepostid );

		$delivery_time = wc_gzd_get_gzd_product( $_product )->delivery_time;

		?>	

		<p class="form-field">
			<label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>
			<input type="hidden" class="wc-product-search wc-gzd-delivery-time-search" style="width: 50%" id="delivery_time" name="delivery_time" data-minimum_input_length="1" data-allow_clear="true" data-placeholder="<?php _e( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ); ?>" data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false" data-selected="<?php echo ( $delivery_time ? $delivery_time->name : '' ); ?>" value="<?php echo ( $delivery_time ? $delivery_time->term_id : '' ); ?>" />
		</p>
		
		<?php

		// Free shipping
		woocommerce_wp_checkbox( array( 'id' => '_free_shipping', 'label' => __( 'Free shipping?', 'woocommerce-germanized' ), 'description' => __( 'This option disables the "plus shipping costs" notice on product page', 'woocommerce-germanized' ) ) );

	}

	public static function save( $post_id ) {

		$product = wc_get_product( $post_id );

		$data = array(
			'product-type' => '',
			'_unit' => '',
			'_unit_base' => '',
			'_unit_product' => '',
			'_unit_price_auto' => '',
			'_unit_price_regular' => '',
			'_unit_price_sale' => '',
			'_sale_price_label' => '',
			'_sale_price_regular_label' => '',
			'_mini_desc' => '',
			'delivery_time' => '',
			'_sale_price_dates_from' => '',
			'_sale_price_dates_to' => '',
			'_sale_price' => '',
			'_free_shipping' => '',
		);

		foreach ( $data as $k => $v ) {
			$data[ $k ] = ( isset( $_POST[ $k ] ) ? $_POST[ $k ] : null );
		}

		self::save_product_data( $post_id, $data );

	}

	public static function save_product_data( $post_id, $data, $is_variation = false ) {

		$data = apply_filters( 'woocommerce_gzd_product_saveable_data', $data, $post_id );

		$product_type = ( ! isset( $data['product-type'] ) || empty( $data['product-type'] ) ) ? 'simple' : sanitize_title( stripslashes( $data['product-type'] ) );

		if ( isset( $data['_unit'] ) ) {

			if ( empty( $data['_unit'] ) || in_array( $data['_unit'], array( 'none', '-1' ) ) )
				delete_post_meta( $post_id, '_unit' );
			else
				update_post_meta( $post_id, '_unit', sanitize_text_field( $data['_unit'] ) );

		}

		$sale_price_labels = array( '_sale_price_label', '_sale_price_regular_label' );

		foreach ( $sale_price_labels as $label ) {

			if ( isset( $data[$label] ) ) {

				if ( empty( $data[$label] ) || in_array( $data[$label], array( 'none', '-1' ) ) )
					delete_post_meta( $post_id, $label );
				else
					update_post_meta( $post_id, $label, sanitize_text_field( $data[$label] ) );

			}

		}
		
		if ( isset( $data['_unit_base'] ) ) {
			update_post_meta( $post_id, '_unit_base', ( $data['_unit_base'] === '' ) ? '' : wc_format_decimal( $data['_unit_base'] ) );
		}

		if ( isset( $data['_unit_product'] ) ) {
			update_post_meta( $post_id, '_unit_product', ( $data['_unit_product'] === '' ) ? '' : wc_format_decimal( $data['_unit_product'] ) );
		}

		update_post_meta( $post_id, '_unit_price_auto', ( isset( $data['_unit_price_auto'] ) ) ? 'yes' : '' );
		
		if ( isset( $data['_unit_price_regular'] ) ) {
			update_post_meta( $post_id, '_unit_price_regular', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
			update_post_meta( $post_id, '_unit_price', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
		}
		
		if ( isset( $data['_unit_price_sale'] ) ) {

			// Unset unit price sale if no product sale price has been defined
			if ( ! isset( $data['_sale_price'] ) || $data['_sale_price'] === '' )
				$data['_unit_price_sale'] = '';

			update_post_meta( $post_id, '_unit_price_sale', ( $data['_unit_price_sale'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_sale'] ) );
		}
		
		if ( isset( $data[ '_mini_desc' ] ) ) {
			update_post_meta( $post_id, '_mini_desc', ( $data[ '_mini_desc' ] === '' ? '' : esc_html( $data[ '_mini_desc' ] ) ) );
		}

		if ( isset( $data[ 'delivery_time' ] ) && ! empty( $data[ 'delivery_time' ] ) ) {
			if ( ! is_numeric( $data[ 'delivery_time' ] ) )
				wp_set_post_terms( $post_id, sanitize_text_field( $data[ 'delivery_time' ] ), 'product_delivery_time' );
			else
				wp_set_object_terms( $post_id, absint( $data[ 'delivery_time' ] ) , 'product_delivery_time' );
		} else {
			wp_delete_object_term_relationships( $post_id, 'product_delivery_time' );
		}

		// Free shipping
		update_post_meta( $post_id, '_free_shipping', ( isset( $data['_free_shipping'] ) ) ? 'yes' : '' );

		// Ignore variable data
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) && ! $is_variation ) {

			update_post_meta( $post_id, '_unit_price_regular', '' );
			update_post_meta( $post_id, '_unit_price_sale', '' );
			update_post_meta( $post_id, '_unit_price', '' );
			update_post_meta( $post_id, '_unit_price_auto', '' );
			update_post_meta( $post_id, '_mini_desc', '' );

		} else {

			$date_from = isset( $data['_sale_price_dates_from'] ) ? wc_clean( $data['_sale_price_dates_from'] ) : '';
			$date_to   = isset( $data['_sale_price_dates_to'] ) ? wc_clean( $data['_sale_price_dates_to'] ) : '';

			// Update price if on sale
			if ( '' !== $data['_unit_price_sale'] && '' == $date_to && '' == $date_from ) {
				update_post_meta( $post_id, '_unit_price', wc_format_decimal( $data['_unit_price_sale'] ) );
			} else {
				update_post_meta( $post_id, '_unit_price', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
			}

			if ( '' !== $data['_unit_price_sale'] && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				update_post_meta( $post_id, '_unit_price', wc_format_decimal( $data['_unit_price_sale'] ) );
			}

			if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) )
				update_post_meta( $post_id, '_unit_price', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
	
		}

	}

}

WC_Germanized_Meta_Box_Product_Data::init();