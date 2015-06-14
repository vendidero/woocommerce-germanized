<?php
/**
 * Adds unit price and delivery time to variable Product metabox.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds unit price and delivery time to variable Product metabox.
 *
 * @class       WC_Germanized_Meta_Box_Product_Data_Variable
 * @author 		Vendidero
 * @version     1.0.0
 */
class WC_Germanized_Meta_Box_Product_Data_Variable {
	
	public static function init() {
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'output' ), 20, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save' ) , 0, 2 );
	}

	public static function output( $loop, $variation_data, $variation ) {
		if ( version_compare( WC()->version, '2.3', '<' ) )
			return self::output_pre( $loop, $variation_data );
		$_product = wc_get_product( $variation );
		$variation_id = $_product->variation_id;
		$delivery_time = $_product->gzd_product->delivery_time;
		?>
		<div class="variable_pricing_unit">
			<p class="form-row form-row-first">
				<label><?php _e( 'Unit', 'woocommerce-germanized' ); ?>:</label>
				<select name="variable_unit[<?php echo $loop; ?>]">
					<option value="parent" <?php selected( is_null( isset( $_product->gzd_product->unit ) ? $_product->gzd_product->unit : null ), true ); ?>><?php _e( 'None', 'woocommerce-germanized' ); ?></option>
					<?php
					foreach ( WC_germanized()->units->get_units() as $key => $value )
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === ( isset( $_product->gzd_product->unit ) ? $_product->gzd_product->unit : '' ) , true, false ) . '>' . esc_html( $value ) . '</option>';
				?></select>
			</p>
			<p class="form-row form-row-last">
				<label for="variable_unit_base"><?php echo __( 'Unit Base', 'woocommerce-germanized' );?>:</label>
				<input class="input-text wc_input_decimal" size="6" type="text" name="variable_unit_base[<?php echo $loop; ?>]" value="<?php echo ( isset( $_product->gzd_product->unit_base ) ? esc_attr( wc_format_localized_decimal( $_product->gzd_product->unit_base ) ) : '' );?>" placeholder="" />
			</p>
			<p class="form-row form-row-first">
				<label for="variable_unit_price_regular"><?php echo __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>:</label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_regular[<?php echo $loop; ?>]" value="<?php echo ( isset( $_product->gzd_product->unit_price_regular ) ? esc_attr( wc_format_localized_price( $_product->gzd_product->unit_price_regular ) ) : '' );?>" placeholder="" />
			</p>
			<p class="form-row form-row-last">
				<label for="variable_unit_price_sale"><?php echo __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>:</label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_sale[<?php echo $loop; ?>]" value="<?php echo ( isset( $_product->gzd_product->unit_price_sale ) ? esc_attr( wc_format_localized_price( $_product->gzd_product->unit_price_sale ) ) : '' );?>" placeholder="" />
			</p>
		</div>
		<div class="variable_shipping_time hide_if_variation_virtual">
			<p class="form-row form-row-first">
				<label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>
				<input type="hidden" class="wc-product-search wc-gzd-delivery-time-search" style="width: 100%" id="variable_delivery_time_<?php echo $loop; ?>" name="variable_delivery_time[<?php echo $loop; ?>]" data-minimum_input_length="1" data-allow_clear="true" data-placeholder="<?php _e( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ); ?>" data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false" data-selected="<?php echo ( $delivery_time ? $delivery_time->name : '' ); ?>" value="<?php echo ( $delivery_time ? $delivery_time->term_id : '' ); ?>" />
			</p>
			
		</div>
		<div class="variable_cart_mini_desc">
			<p class="form-row form-row-full">
				<label for="variable_product_mini_desc"><?php echo __( 'Optional Mini Description', 'woocommerce-germanized' ); ?>:</label>
				<?php wp_editor( htmlspecialchars_decode( $_product->gzd_product->mini_desc ), 'wc_gzd_product_mini_desc_' . $loop, array( 'textarea_name' => 'variable_product_mini_desc[' . $loop . ']', 'textarea_rows' => 5, 'media_buttons' => false, 'teeny' => true ) ); ?>
			</p>
		</div>
		<?php
  	}

  	/**
  	 * Variable Products meta for WC pre 2.3
  	 */
  	public static function output_pre( $loop, $variation_data ) {
  		$variation_id = isset( $variation_data[ 'variation_post_id' ] ) ? $variation_data[ 'variation_post_id' ] : -1;
		$delivery_times = get_the_terms( $variation_id, 'product_delivery_time' );
		$delivery_time = ( $delivery_times && ! is_wp_error( $delivery_times ) ) ? current( $delivery_times )->term_id : '';
		?>
		<tr>
			<td>
				<label><?php _e( 'Unit', 'woocommerce-germanized' ); ?>:</label>
				<select name="variable_unit[<?php echo $loop; ?>]">
					<option value="parent" <?php selected( is_null( isset( $variation_data['_unit'][0] ) ? $variation_data['_unit'][0] : null ), true ); ?>><?php _e( 'None', 'woocommerce-germanized' ); ?></option>
					<?php
					foreach ( WC_germanized()->units->get_units() as $key => $value )
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === ( isset( $variation_data['_unit'][0] ) ? $variation_data['_unit'][0] : '' ) , true, false ) . '>' . esc_html( $value ) . '</option>';
				?></select>
			</td>
			<td>
				<label for="variable_unit_base"><?php echo __( 'Unit Base', 'woocommerce-germanized' );?>:</label>
				<input class="input-text wc_input_decimal" size="6" type="text" name="variable_unit_base[<?php echo $loop; ?>]" value="<?php echo ( isset( $variation_data['_unit_base'][0] ) ? esc_attr( wc_format_localized_decimal( $variation_data['_unit_base'][0] ) ) : '' );?>" placeholder="" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="variable_unit_price_regular"><?php echo __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>:</label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_regular[<?php echo $loop; ?>]" value="<?php echo ( isset( $variation_data['_unit_price_regular'][0] ) ? esc_attr( wc_format_localized_price( $variation_data['_unit_price_regular'][0] ) ) : '' );?>" placeholder="" />
			</td>
			<td>
				<label for="variable_unit_price_sale"><?php echo __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>:</label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_sale[<?php echo $loop; ?>]" value="<?php echo ( isset( $variation_data['_unit_price_sale'][0] ) ? esc_attr( wc_format_localized_price( $variation_data['_unit_price_sale'][0] ) ) : '' );?>" placeholder="" />
			</td>
		</tr>
		<tr>
			<td class="hide_if_variation_virtual">
				<label><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?>:</label> 
				<?php
				$args = array(
					'taxonomy' 			=> 'product_delivery_time',
					'hide_empty'		=> 0,
					'show_option_none' 	=> __( 'None', 'woocommerce-germanized' ),
					'name' 				=> 'variable_delivery_time[' . $loop . ']',
					'id'				=> '',
					'selected'			=> isset( $delivery_time ) ? esc_attr( $delivery_time ) : '',
					'echo'				=> 0
				);
				echo wp_dropdown_categories( $args );
				?>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="variable_cart_mini_desc_pre">
				<label for="variable_product_mini_desc"><?php echo __( 'Optional Mini Description', 'woocommerce-germanized' ); ?>:</label>
				<?php wp_editor( htmlspecialchars_decode( ( isset( $variation_data['_mini_desc'][0] ) ? $variation_data['_mini_desc'][0] : '' ) ), 'wc_gzd_product_mini_desc_' . $loop, array( 'textarea_name' => 'variable_product_mini_desc[' . $loop . ']', 'textarea_rows' => 5, 'media_buttons' => false, 'teeny' => true ) ); ?>
			</td>
		</tr>
		<?php
  	}

	public static function save( $variation_id, $i ) {
		
		$saveable = array(
			'unit' 				 => ( isset( $_POST[ 'variable_unit' ] ) ? $_POST[ 'variable_unit' ] : array() ),
			'unit_base' 		 => ( isset( $_POST[ 'variable_unit_base' ] ) ? $_POST[ 'variable_unit_base' ] : array() ),
			'unit_price_regular' => ( isset( $_POST[ 'variable_unit_price_regular' ] ) ? $_POST[ 'variable_unit_price_regular' ] : array() ),
			'unit_price_sale' 	 => ( isset( $_POST[ 'variable_unit_price_sale' ] ) ? $_POST[ 'variable_unit_price_sale' ] : array() ),
			'delivery_time' 	 => ( isset( $_POST[ 'variable_delivery_time' ] ) ? $_POST[ 'variable_delivery_time' ] : array() ),
			'product_desc'		 => ( isset( $_POST[ 'variable_product_mini_desc' ] ) ? $_POST[ 'variable_product_mini_desc' ] : array() ),
		);

		foreach ( $saveable as $key => $item ) {
			
			if ( ! isset( $item[ $i ] ) || empty( $item[ $i ] ) ) {
				
				unset( $saveable[ $key ][ $i ] );
				delete_post_meta( $variation_id, '_' . $key );
			
			}

		} 

		if ( isset( $saveable[ 'unit' ][ $i ] ) ) {
			update_post_meta( $variation_id, '_unit', sanitize_text_field( $saveable[ 'unit' ][ $i ] ) );
		}

		if ( isset( $saveable[ 'unit_base' ][ $i ] ) ) {
			update_post_meta( $variation_id, '_unit_base', wc_format_decimal( $saveable[ 'unit_base' ][ $i ] ) );
		}
		
		if ( isset( $saveable[ 'unit_price_regular' ][ $i ] ) ) {
			update_post_meta( $variation_id, '_unit_price_regular', wc_format_decimal( $saveable[ 'unit_price_regular' ][ $i ] ) );
			update_post_meta( $variation_id, '_unit_price', wc_format_decimal( $saveable[ 'unit_price_regular' ][ $i ] ) );
		}
		
		if ( isset( $saveable[ 'product_desc' ][ $i ] ) ) {
			update_post_meta( $variation_id, '_mini_desc', esc_html( $saveable[ 'product_desc' ][ $i ] ) );
		}
		
		if ( isset( $saveable[ 'unit_price_sale' ][ $i ] ) ) {
			
			update_post_meta( $variation_id, '_unit_price_sale', wc_format_decimal( $saveable[ 'unit_price_sale' ][ $i ] ) );
			update_post_meta( $variation_id, '_unit_price', wc_format_decimal( $saveable[ 'unit_price_sale' ][ $i ] ) );
			
		} else {

			if ( isset( $saveable[ 'unit_price_regular' ][ $i ] ) ) {
			
				update_post_meta( $variation_id, '_unit_price_regular', wc_format_decimal( $saveable[ 'unit_price_regular' ][ $i ] ) );
				update_post_meta( $variation_id, '_unit_price', wc_format_decimal( $saveable[ 'unit_price_regular' ][ $i ] ) );
			
			}

		}

		if ( isset( $saveable[ 'delivery_time' ][ $i ] ) ) {
			
			if ( ! is_numeric( $saveable[ 'delivery_time' ][ $i ] ) )
				wp_set_post_terms( $variation_id, sanitize_text_field( $saveable[ 'delivery_time' ][ $i ] ), 'product_delivery_time' );
			else
				wp_set_object_terms( $variation_id, absint( $saveable[ 'delivery_time' ][ $i ] ), 'product_delivery_time' );
		
		} else {
		
			wp_delete_object_term_relationships( $variation_id, 'product_delivery_time' );
		
		}
	
	}

}

WC_Germanized_Meta_Box_Product_Data_Variable::init();