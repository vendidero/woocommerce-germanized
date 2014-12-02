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
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'output' ), 20, 2 );
		add_action( 'woocommerce_process_product_meta_variable', array( __CLASS__, 'save' ) , 10, 1 );
	}

	public static function output( $loop, $variation_data ) { 
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
				<input class="input-text wc_input_decimal" size="6" type="text" name="variable_unit_base[<?php echo $loop; ?>]" value="<?php echo ( isset( $variation_data['_unit_base'][0] ) ? $variation_data['_unit_base'][0] : '' );?>" placeholder="" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="variable_unit_price_regular"><?php echo __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>:</label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_regular[<?php echo $loop; ?>]" value="<?php echo ( isset( $variation_data['_unit_price_regular'][0] ) ? $variation_data['_unit_price_regular'][0] : '' );?>" placeholder="" />
			</td>
			<td>
				<label for="variable_unit_price_sale"><?php echo __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>:</label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_sale[<?php echo $loop; ?>]" value="<?php echo ( isset( $variation_data['_unit_price_sale'][0] ) ? $variation_data['_unit_price_sale'][0] : '' );?>" placeholder="" />
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
			<td colspan="2">
				<label for="variable_product_mini_desc"><?php echo __( 'Optional Mini Description', 'woocommerce-germanized' ); ?>:</label>
				<?php wp_editor( ( isset( $variation_data['_mini_desc'][0] ) ? $variation_data['_mini_desc'][0] : '' ), 'wc_gzd_product_mini_desc_' . $loop, array( 'textarea_name' => 'variable_product_mini_desc[' . $loop . ']', 'textarea_rows' => 5, 'media_buttons' => false, 'teeny' => true ) ); ?>
			</td>
		</tr>
		<?php
  	}

	public static function save( $post_id ) {
		if ( isset( $_POST[ 'variable_post_id' ] ) ) {
			$variable_post_id = $_POST['variable_post_id'];
			$variable_sku = $_POST['variable_sku'];
			$variable_unit = $_POST['variable_unit'];
			$variable_unit_base = $_POST['variable_unit_base'];
			$variable_unit_price_regular = $_POST['variable_unit_price_regular'];
			$variable_unit_price_sale = $_POST['variable_unit_price_sale'];
			$variable_delivery_time = $_POST['variable_delivery_time'];
			$variable_product_desc = $_POST['variable_product_mini_desc'];
			for ( $i = 0; $i < sizeof( $variable_post_id ); $i++ ) {
				$variation_id = (int) $variable_post_id[$i];
				if ( isset( $variable_unit[$i] ) ) {
					update_post_meta( $variation_id, '_unit', sanitize_text_field( $variable_unit[$i] ) );
				}
				if ( isset( $variable_unit_base[$i] ) ) {
					update_post_meta( $variation_id, '_unit_base', ( $variable_unit_base[$i] === '' ) ? '' : wc_format_decimal( $variable_unit_base[$i] ) );
				}
				if ( isset( $variable_unit_price_regular[$i] ) ) {
					update_post_meta( $variation_id, '_unit_price_regular', ( $variable_unit_price_regular[$i] === '' ) ? '' : wc_format_decimal( $variable_unit_price_regular[$i] ) );
					update_post_meta( $variation_id, '_unit_price', ( $variable_unit_price_regular[$i] === '' ) ? '' : wc_format_decimal( $variable_unit_price_regular[$i] ) );
				}
				if ( isset( $variable_product_desc[$i] ) ) {
					update_post_meta( $variation_id, '_mini_desc', esc_html( $variable_product_desc[$i] ) );
				}
				if ( isset( $variable_unit_price_sale[$i] ) ) {
					update_post_meta( $variation_id, '_unit_price_sale', '' );
					// Update Sale Price only if is on sale (Cron?!)
					if ( get_post_meta( $variation_id, '_unit_price', true ) != $variable_unit_price_regular[$i] && $variable_unit_price_sale[$i] !== '' ) {
						update_post_meta( $variation_id, '_unit_price_sale', ( $variable_unit_price_sale[$i] === '' ) ? '' : wc_format_decimal( $variable_unit_price_sale[$i] ) );
						update_post_meta( $variation_id, '_unit_price', ( $variable_unit_price_sale[$i] === '' ) ? '' : wc_format_decimal( $variable_unit_price_sale[$i] ) );
					}
				}
				$variable_delivery_time[$i] = ! empty( $variable_delivery_time[$i] ) ? (int) $variable_delivery_time[$i] : '';
				wp_set_object_terms( $variation_id, $variable_delivery_time[$i], 'product_delivery_time' );
			}
		}
	}

}

WC_Germanized_Meta_Box_Product_Data_Variable::init();