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
	
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {

		if ( is_admin() ) {
			add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'output' ), 20, 3 );
			add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save' ) , 0, 2 );
			add_action( 'woocommerce_variation_options', array( __CLASS__, 'service' ), 0, 3 );
		}
	}

	public static function service( $loop, $variation_data, $variation ) {

		$_product = wc_get_product( $variation );
		$variation_id = wc_gzd_get_crud_data( $_product, 'id' );
		$is_service = get_post_meta( $variation_id, '_service', true );

		?>
		<label><input type="checkbox" class="checkbox variable_service" name="variable_service[<?php echo $loop; ?>]" <?php checked( $is_service !== '' ? $is_service : '', 'yes' ); ?> /> <?php _e( 'Service', 'woocommerce-germanized' ); ?> <?php echo wc_gzd_help_tip( __( 'Service products do not sell physical products.', 'woocommerce-germanized' ) ); ?></label>
		<?php
	}

	public static function output( $loop, $variation_data, $variation ) {

		$_product = wc_get_product( $variation );

		$_parent = wc_get_product( wc_gzd_get_crud_data( $_product, 'parent' ) );
		$variation_id = wc_gzd_get_crud_data( $_product, 'id' );

		$variation_meta   = get_post_meta( $variation_id );
		$variation_data	  = array();

		$variation_fields = array(
			'_unit' 					=> '',
			'_unit_base' 				=> '',
			'_unit_product' 			=> '',
			'_unit_price_auto' 			=> '',
			'_unit_price_regular' 		=> '',
			'_unit_price_auto' 			=> '',
			'_unit_price_sale' 			=> '',
			'_sale_price_label'			=> '',
			'_sale_price_regular_label' => '',
			'_mini_desc' 				=> ''
		);

		foreach ( $variation_fields as $field => $value ) {
			$variation_data[ $field ] = isset( $variation_meta[ $field ][0] ) ? maybe_unserialize( $variation_meta[ $field ][0] ) : $value;
		}

		$delivery_time = get_the_terms( $variation_id, 'product_delivery_time' );
		
		if ( $delivery_time && ! empty( $delivery_time ) && is_array( $delivery_time ) )
			$delivery_time = $delivery_time[0];

		?>

		<div class="variable_pricing_labels">

			<p class="form-row form-row-first">
				<label><?php _e( 'Sale Label', 'woocommerce-germanized' ); ?></label>
				<select name="variable_sale_price_label[<?php echo $loop; ?>]">
					<option value="" <?php selected( empty( $variation_data[ '_sale_price_label' ] ) , true ); ?>><?php _e( 'Same as Parent', 'woocommerce-germanized' ); ?></option>
					<?php foreach ( WC_germanized()->price_labels->get_labels() as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === $variation_data[ '_sale_price_label' ], true ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="form-row form-row-last">
				<label><?php _e( 'Sale Regular Label', 'woocommerce-germanized' ); ?></label>
				<select name="variable_sale_price_regular_label[<?php echo $loop; ?>]">
					<option value="" <?php selected( empty( $variation_data[ '_sale_price_regular_label' ] ), true ); ?>><?php _e( 'Same as Parent', 'woocommerce-germanized' ); ?></option>
					<?php foreach ( WC_germanized()->price_labels->get_labels() as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === $variation_data[ '_sale_price_regular_label' ], true ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

		</div>

		<div class="variable_pricing_unit">
			<p class="form-row form-row-first">
				
				<input type="hidden" name="variable_parent_unit_product[<?php echo $loop; ?>]" class="wc-gzd-parent-unit_product" value="" />
				<input type="hidden" name="variable_parent_unit[<?php echo $loop; ?>]" class="wc-gzd-parent-unit" value="" />
				<input type="hidden" name="variable_parent_unit_base[<?php echo $loop; ?>]" class="wc-gzd-parent-unit_base" value="" />

				<label for="variable_unit_product"><?php echo __( 'Product Units', 'woocommerce-germanized' );?> <?php echo wc_gzd_help_tip( __( 'Number of units included per default product price. Example: 1000 ml. Leave blank to use parent value.', 'woocommerce-germanized' ) ); ?></label>
				<input class="input-text wc_input_decimal" size="6" type="text" name="variable_unit_product[<?php echo $loop; ?>]" value="<?php echo ( ! empty( $variation_data[ '_unit_product' ] ) ? esc_attr( wc_format_localized_decimal( $variation_data[ '_unit_product' ] ) ) : '' );?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( wc_gzd_get_gzd_product( $_parent )->unit_product ) ); ?>" />
			</p>
			<p class="form-row form-row-last _unit_price_auto_field">
				<label for="variable_unit_price_auto_<?php echo $loop; ?>"><?php echo __( 'Calculation', 'woocommerce-germanized' ); ?></label>
				<input class="input-text wc_input_price" id="variable_unit_price_auto_<?php echo $loop; ?>" type="checkbox" name="variable_unit_price_auto[<?php echo $loop; ?>]" value="yes" <?php checked( 'yes', $variation_data[ '_unit_price_auto' ] );?> />
				<span class="description">
					<span class="wc-gzd-premium-desc"><?php echo __( 'Calculate unit prices automatically', 'woocommerce-germanized' ); ?></span>
					<a href="https://vendidero.de/woocommerce-germanized#buy" target="_blank" class="wc-gzd-pro">pro</a>
				</span>
			</p>
			<p class="form-row form-row-first">
				<label for="variable_unit_price_regular"><?php echo __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_regular[<?php echo $loop; ?>]" value="<?php echo ( ! empty( $variation_data[ '_unit_price_regular' ] ) ? esc_attr( wc_format_localized_price( $variation_data[ '_unit_price_regular' ] ) ) : '' );?>" placeholder="" />
			</p>
			<p class="form-row form-row-last">
				<label for="variable_unit_price_sale"><?php echo __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
				<input class="input-text wc_input_price" size="5" type="text" name="variable_unit_price_sale[<?php echo $loop; ?>]" value="<?php echo ( ! empty( $variation_data[ '_unit_price_sale' ] ) ? esc_attr( wc_format_localized_price( $variation_data[ '_unit_price_sale' ] ) ) : '' );?>" placeholder="" />
			</p>
		</div>
		<div class="variable_shipping_time hide_if_variation_virtual">
			<p class="form-row form-row-first">
				<label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>

                <?php
                    WC_Germanized_Meta_Box_Product_Data::output_delivery_time_select2( array(
                        'name' => 'variable_delivery_time[' . $loop . ']',
                        'id' => 'variable_delivery_time_' . $loop,
                        'placeholder' => __( 'Same as parent', 'woocommerce-germanized' ),
                        'term' => $delivery_time,
                        'style' => 'width: 100%',
                    ) );
                ?>
			</p>
		</div>
		<div class="variable_cart_mini_desc">
			<p class="form-row form-row-full">
				<label for="variable_mini_desc"><?php echo __( 'Optional Mini Description', 'woocommerce-germanized' ); ?></label>
				<textarea rows="3" style="width: 100%" name="variable_mini_desc[<?php echo $loop;?>]" id="variable_mini_desc_<?php echo $loop;?>" class="variable_mini_desc"><?php echo htmlspecialchars_decode( $variation_data[ '_mini_desc' ] ); ?></textarea>
				<?php // wp_editor( htmlspecialchars_decode( wc_gzd_get_gzd_product( $_product )->mini_desc ), 'wc_gzd_product_mini_desc_' . $loop, array( 'textarea_name' => 'variable_mini_desc[' . $loop . ']', 'textarea_rows' => 5, 'media_buttons' => false, 'teeny' => true ) ); ?>
			</p>
		</div>
		<?php
  	}

	public static function save( $variation_id, $i ) {

		$data = array(
			'_unit_product' => '',
			'_unit_price_auto' => '',
			'_unit_price_regular' => '',
			'_sale_price_label' => '',
			'_sale_price_regular_label' => '',
			'_unit_price_sale' => '',
			'_parent_unit_product' => '',
			'_parent_unit' => '',
			'_parent_unit_base' => '',
			'_mini_desc' => '',
			'_service' => '',
			'delivery_time' => '',
		);

		foreach ( $data as $k => $v ) {
			
			$data_k = 'variable' . ( substr( $k, 0, 1) === '_' ? '' : '_' ) . $k;
			$data[ $k ] = ( isset( $_POST[ $data_k ][$i] ) ? $_POST[ $data_k ][$i] : null );

		}

		$product = wc_get_product( $variation_id );
		$product_parent = wc_get_product( wc_gzd_get_crud_data( $product, 'parent' ) );

		// Check if parent has unit_base + unit otherwise ignore data
		if ( empty( $data[ '_parent_unit' ] ) || empty( $data[ '_parent_unit_base' ] ) ) {

			$data[ '_unit_price_auto' ] = '';
			$data[ '_unit_price_regular' ] = '';
			$data[ '_unit_price_sale' ] = '';
		}

		// If parent has no unit, delete unit_product as well
		if ( empty( $data[ '_parent_unit' ] ) ) {
			$data[ '_unit_product' ] = '';
		}

		$data[ 'product-type' ] = $product_parent->get_type();
		$data[ '_sale_price_dates_from' ] = $_POST['variable_sale_price_dates_from'][$i];
		$data[ '_sale_price_dates_to' ] = $_POST['variable_sale_price_dates_to'][$i];
		$data[ '_sale_price' ] = $_POST['variable_sale_price'][$i];

		$product = WC_Germanized_Meta_Box_Product_Data::save_product_data( $product, $data, true );
	}
}

WC_Germanized_Meta_Box_Product_Data_Variable::instance();