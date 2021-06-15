<?php
/**
 * Adds unit price and delivery time to variable Product metabox.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds unit price and delivery time to variable Product metabox.
 *
 * @class       WC_Germanized_Meta_Box_Product_Data_Variable
 * @author        Vendidero
 * @version     1.0.0
 */
class WC_Germanized_Meta_Box_Product_Data_Variable {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		if ( is_admin() ) {
			add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'output' ), 20, 3 );
			add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save' ), 0, 2 );
			add_action( 'woocommerce_variation_options', array( __CLASS__, 'service' ), 0, 3 );
		}
	}

	public static function service( $loop, $variation_data, $variation ) {
		$_product    = wc_get_product( $variation );
		$gzd_product = wc_gzd_get_product( $_product );
		$is_service  = $gzd_product->get_service();
		?>
        <label><input type="checkbox" class="checkbox variable_service"
                      name="variable_service[<?php echo $loop; ?>]" <?php checked( $is_service ? 'yes' : 'no', 'yes' ); ?> /> <?php _e( 'Service', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( __( 'Service products do not sell physical products.', 'woocommerce-germanized' ) ); ?>
        </label>
		<?php
	}

	protected static function get_delivery_time_wrapper_classes() {
		$delivery_time_classes = array( 'hide_if_variation_virtual' );
		$hidden_types          = get_option( 'woocommerce_gzd_display_delivery_time_hidden_types', array() );

		if ( ! in_array( 'virtual', $hidden_types ) ) {
		    $delivery_time_classes = array_diff( $delivery_time_classes, array( 'hide_if_variation_virtual' ) );
		}

		return implode( ' ', $delivery_time_classes );
	}

	public static function output( $loop, $variation_data, $variation ) {
		$_product           = wc_get_product( $variation );
		$_parent            = wc_get_product( $_product->get_parent_id() );
		$gzd_product        = wc_gzd_get_product( $_product );
		$gzd_parent_product = wc_gzd_get_product( $_parent );
		$delivery_time      = $gzd_product->get_delivery_time( 'edit' );

		?>

        <div class="variable_pricing_labels">
            <p class="form-row form-row-first">
                <label><?php _e( 'Sale Label', 'woocommerce-germanized' ); ?></label>
                <select name="variable_sale_price_label[<?php echo $loop; ?>]">
                    <option value="" <?php selected( empty( $gzd_product->get_sale_price_label( 'edit' ) ), true ); ?>><?php _e( 'Same as Parent', 'woocommerce-germanized' ); ?></option>
					<?php foreach ( WC_germanized()->price_labels->get_labels() as $key => $value ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === $gzd_product->get_sale_price_label( 'edit' ), true ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
                </select>
            </p>

            <p class="form-row form-row-last">
                <label><?php _e( 'Sale Regular Label', 'woocommerce-germanized' ); ?></label>
                <select name="variable_sale_price_regular_label[<?php echo $loop; ?>]">
                    <option value="" <?php selected( empty( $gzd_product->get_sale_price_regular_label( 'edit' ) ), true ); ?>><?php _e( 'Same as Parent', 'woocommerce-germanized' ); ?></option>
					<?php foreach ( WC_germanized()->price_labels->get_labels() as $key => $value ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === $gzd_product->get_sale_price_regular_label( 'edit' ), true ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
                </select>
            </p>
        </div>

        <div class="variable_pricing_unit">
            <p class="form-row form-row-first">
                <input type="hidden" name="variable_parent_unit_product[<?php echo $loop; ?>]"
                       class="wc-gzd-parent-unit_product" value=""/>
                <input type="hidden" name="variable_parent_unit[<?php echo $loop; ?>]" class="wc-gzd-parent-unit"
                       value=""/>
                <input type="hidden" name="variable_parent_unit_base[<?php echo $loop; ?>]"
                       class="wc-gzd-parent-unit_base" value=""/>

                <label for="variable_unit_product"><?php echo __( 'Product Units', 'woocommerce-germanized' ); ?><?php echo wc_help_tip( __( 'Number of units included per default product price. Example: 1000 ml. Leave blank to use parent value.', 'woocommerce-germanized' ) ); ?></label>
                <input class="input-text wc_input_decimal" size="6" type="text"
                       name="variable_unit_product[<?php echo $loop; ?>]"
                       value="<?php echo( ! empty( $gzd_product->get_unit_product( 'edit' ) ) ? esc_attr( wc_format_localized_decimal( $gzd_product->get_unit_product( 'edit' ) ) ) : '' ); ?>"
                       placeholder="<?php echo esc_attr( wc_format_localized_decimal( $gzd_parent_product->get_unit_product( 'edit' ) ) ); ?>"/>
            </p>
            <p class="form-row form-row-last _unit_price_auto_field">
                <label for="variable_unit_price_auto_<?php echo $loop; ?>"><?php echo __( 'Calculation', 'woocommerce-germanized' ); ?></label>
                <input class="input-text wc_input_price" id="variable_unit_price_auto_<?php echo $loop; ?>"
                       type="checkbox" name="variable_unit_price_auto[<?php echo $loop; ?>]"
                       value="yes" <?php checked( 'yes', $gzd_product->get_unit_price_auto( 'edit' ) ? 'yes' : 'no' ); ?> />
                <span class="description">
					<span class="wc-gzd-premium-desc"><?php echo __( 'Calculate unit prices automatically', 'woocommerce-germanized' ); ?></span>
					<a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="wc-gzd-pro">pro</a>
				</span>
            </p>
            <p class="form-row form-row-first">
                <label for="variable_unit_price_regular"><?php echo __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
                <input class="input-text wc_input_price" size="5" type="text"
                       name="variable_unit_price_regular[<?php echo $loop; ?>]"
                       value="<?php echo( ! empty( $gzd_product->get_unit_price_regular( 'edit' ) ) ? esc_attr( wc_format_localized_price( $gzd_product->get_unit_price_regular( 'edit' ) ) ) : '' ); ?>"
                       placeholder=""/>
            </p>
            <p class="form-row form-row-last">
                <label for="variable_unit_price_sale"><?php echo __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?></label>
                <input class="input-text wc_input_price" size="5" type="text"
                       name="variable_unit_price_sale[<?php echo $loop; ?>]"
                       value="<?php echo( ! empty( $gzd_product->get_unit_price_sale( 'edit' ) ) ? esc_attr( wc_format_localized_price( $gzd_product->get_unit_price_sale( 'edit' ) ) ) : '' ); ?>"
                       placeholder=""/>
            </p>
            <p class="form-row form-row-first wc-gzd-unit-price-disabled-notice notice notice-warning">
				<?php printf( __( 'To enable unit prices on variation level please choose a unit and unit price units within %s.', 'woocommerce-germanized' ), '<a href="#general_product_data" class="wc-gzd-general-product-data-tab">' . __( 'general product data', 'woocommerce-germanized' ) . '</a>' ); ?>
            </p>
        </div>
        <div class="variable_shipping_time variable_delivery_time <?php echo esc_attr( self::get_delivery_time_wrapper_classes() ); ?>">
            <p class="form-row form-row-first">
                <label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>

				<?php WC_Germanized_Meta_Box_Product_Data::output_delivery_time_select2( array(
					'name'        => 'variable_delivery_time[' . $loop . ']',
					'id'          => 'variable_delivery_time_' . $loop,
					'placeholder' => __( 'Same as parent', 'woocommerce-germanized' ),
					'term'        => $delivery_time,
					'style'       => 'width: 100%',
				) ); ?>
            </p>
        </div>

        <div class="variable_min_age">
            <p class="form-row form-row-last">
                <label><?php _e( 'Minimum Age', 'woocommerce-germanized' ); ?></label>
                <select name="variable_min_age[<?php echo $loop; ?>]">
                    <option value="" <?php selected( $gzd_product->get_min_age( 'edit' ) === '', true ); ?>><?php _e( 'Same as Parent', 'woocommerce-germanized' ); ?></option>
					<?php foreach ( wc_gzd_get_age_verification_min_ages_select() as $key => $value ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === (int) $gzd_product->get_min_age( 'edit' ), true ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
                </select>
            </p>
        </div>

        <div class="variable_cart_mini_desc">
            <p class="form-row form-row-full">
                <label for="variable_mini_desc"><?php echo __( 'Optional Mini Description', 'woocommerce-germanized' ); ?></label>
                <textarea rows="3" style="width: 100%" name="variable_mini_desc[<?php echo $loop; ?>]"
                          id="variable_mini_desc_<?php echo $loop; ?>"
                          class="variable_mini_desc"><?php echo htmlspecialchars_decode( $gzd_product->get_mini_desc( 'edit' ) ); ?></textarea>
            </p>
        </div>
		<?php
	}

	public static function save( $variation_id, $i ) {

		$data = array(
			'_unit_product'             => '',
			'_unit_price_auto'          => '',
			'_unit_price_regular'       => '',
			'_sale_price_label'         => '',
			'_sale_price_regular_label' => '',
			'_unit_price_sale'          => '',
			'_parent_unit_product'      => '',
			'_parent_unit'              => '',
			'_parent_unit_base'         => '',
			'_mini_desc'                => '',
			'_service'                  => '',
			'delivery_time'             => '',
			'_min_age'                  => '',
		);

		foreach ( $data as $k => $v ) {
			$data_k     = 'variable' . ( substr( $k, 0, 1 ) === '_' ? '' : '_' ) . $k;
			$data[ $k ] = ( isset( $_POST[ $data_k ][ $i ] ) ? $_POST[ $data_k ][ $i ] : null );
		}

		$product            = wc_get_product( $variation_id );
		$product_parent     = wc_get_product( $product->get_parent_id() );
		$gzd_product        = wc_gzd_get_product( $product );
		$gzd_parent_product = wc_gzd_get_product( $product_parent );

		// Check if parent has unit_base + unit otherwise ignore data
		if ( empty( $data['_parent_unit'] ) || empty( $data['_parent_unit_base'] ) ) {
			$data['_unit_price_auto']    = '';
			$data['_unit_price_regular'] = '';
			$data['_unit_price_sale']    = '';
		}

		// If parent has no unit, delete unit_product as well
		if ( empty( $data['_parent_unit'] ) ) {
			$data['_unit_product'] = '';
		}

		$data['product-type']           = $product_parent->get_type();
		$data['_sale_price_dates_from'] = $_POST['variable_sale_price_dates_from'][ $i ];
		$data['_sale_price_dates_to']   = $_POST['variable_sale_price_dates_to'][ $i ];
		$data['_sale_price']            = $_POST['variable_sale_price'][ $i ];

		$product = WC_Germanized_Meta_Box_Product_Data::save_product_data( $product, $data, true );
	}
}

WC_Germanized_Meta_Box_Product_Data_Variable::instance();