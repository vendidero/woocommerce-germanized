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

        add_action( 'woocommerce_variable_product_bulk_edit_actions', array( __CLASS__, 'bulk_edit' ), 10 );
        add_action( 'woocommerce_bulk_edit_variations', array( __CLASS__, 'bulk_save' ), 10, 4 );
	}

    public static function bulk_save( $bulk_action, $data, $product_id, $variations ) {
        $actions = array(
            'variable_unit_product',
            'variable_unit_auto',
            'variable_delivery_time'
        );

        if ( in_array( $bulk_action, $actions ) ) {
	        if ( method_exists( __CLASS__, "bulk_action_$bulk_action" ) ) {
		        call_user_func( array( __CLASS__, "bulk_action_$bulk_action" ), $variations, $data );
	        }
        }
    }

    protected static function bulk_action_variable_delivery_time( $variations, $data ) {
        if ( isset( $data['value'] ) ) {
	        $slug = '';

            if ( ! empty( wc_clean( $data['value'] ) ) ) {
	            $slug = wc_gzd_get_valid_product_delivery_time_slugs( wc_clean( $data['value'] ) );
            }

	        foreach ( $variations as $variation_id ) {
		        if ( $variation = wc_get_product( $variation_id ) ) {
			        $gzd_variation = wc_gzd_get_gzd_product( $variation );
			        $gzd_variation->set_default_delivery_time_slug( $slug );

			        $gzd_variation->save();
			        $variation->save();
		        }
	        }
        }
    }

	protected static function bulk_action_variable_unit_product( $variations, $data ) {
		if ( isset( $data['value'] ) ) {
			$products = '';

			if ( ! empty( wc_clean( $data['value'] ) ) ) {
				$products = wc_format_decimal( wc_clean( $data['value'] ) );
			}

			foreach ( $variations as $variation_id ) {
				if ( $variation = wc_get_product( $variation_id ) ) {
					$gzd_variation = wc_gzd_get_gzd_product( $variation );
					$gzd_variation->set_unit_product( $products );

					$variation->save();
				}
			}
		}
	}

	protected static function bulk_action_variable_unit_auto( $variations, $data ) {
		foreach ( $variations as $variation_id ) {
			if ( $variation = wc_get_product( $variation_id ) ) {
				$gzd_variation = wc_gzd_get_gzd_product( $variation );
                $gzd_variation->set_unit_price_auto( ( $gzd_variation->is_unit_price_auto() ? false : true ) );

                $variation->save();
			}
		}
	}

    public static function bulk_edit() {
        ?>
        <optgroup label="<?php esc_attr_e( 'Unit Price', 'woocommerce-germanized' ); ?>">
            <option value="variable_unit_product"><?php esc_html_e( 'Set product units', 'woocommerce-germanized' ); ?></option>
            <?php if ( WC_germanized()->is_pro() ) : ?>
                <option value="variable_unit_auto"><?php esc_html_e( 'Toggle auto calculation', 'woocommerce-germanized' ); ?></option>
            <?php endif; ?>
        </optgroup>
        <optgroup label="<?php esc_attr_e( 'Delivery Time', 'woocommerce-germanized' ); ?>">
            <option value="variable_delivery_time"><?php esc_html_e( 'Set delivery time', 'woocommerce-germanized' ); ?></option>
        </optgroup>
        <?php
    }

	public static function service( $loop, $variation_data, $variation ) {
		$_product    = wc_get_product( $variation );
		$gzd_product = wc_gzd_get_product( $_product );
		$is_service  = $gzd_product->get_service();
		?>
        <label>
            <input type="checkbox" class="checkbox variable_service" name="variable_service[<?php echo $loop; ?>]" <?php checked( $is_service ? 'yes' : 'no', 'yes' ); ?> /> <?php _e( 'Service', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( __( 'Service products do not sell physical products.', 'woocommerce-germanized' ) ); ?>
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
		$_product                  = wc_get_product( $variation );
		$_parent                   = wc_get_product( $_product->get_parent_id() );
		$gzd_product               = wc_gzd_get_product( $_product );
		$gzd_parent_product        = wc_gzd_get_product( $_parent );
		$delivery_time             = $gzd_product->get_delivery_time( 'edit' );
		$countries_left            = WC_Germanized_Meta_Box_Product_Data::get_available_delivery_time_countries();
		$delivery_times            = $gzd_product->get_delivery_times( 'edit' );
		$delivery_times_by_country = $gzd_product->get_country_specific_delivery_times( 'edit' );
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
                <input type="hidden" name="variable_parent_unit_product[<?php echo $loop; ?>]" class="wc-gzd-parent-unit_product" value=""/>
                <input type="hidden" name="variable_parent_unit[<?php echo $loop; ?>]" class="wc-gzd-parent-unit" value=""/>
                <input type="hidden" name="variable_parent_unit_base[<?php echo $loop; ?>]" class="wc-gzd-parent-unit_base" value=""/>

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
					<a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="wc-gzd-pro wc-gzd-pro-outlined">pro</a>
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
            <p class="form-row form-row-full">
                <label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>
				<?php
                    WC_Germanized_Meta_Box_Product_Data::output_delivery_time_select2( array(
                        'name'        => 'variable_delivery_time[' . $loop . ']',
                        'id'          => 'variable_delivery_time_' . $loop,
                        'placeholder' => __( 'Same as parent', 'woocommerce-germanized' ),
                        'term'        => $delivery_time,
                        'style'       => 'width: 100%',
                    ) );
                ?>
            </p>

	        <?php if ( ! empty( $delivery_times_by_country ) ) {
		        foreach( $delivery_times_by_country as $country => $term_slug ) {
			        $countries_left = array_diff_key( $countries_left, array( $country => '' ) );
			        ?>
                    <p class="form-row form-row-full wc-gzd-country-specific-delivery-time-field wc-gzd-country-specific-delivery-time-field-variation">
                        <label for="country_specific_delivery_times-<?php echo esc_attr( $country ); ?>"><?php printf( __( 'Delivery Time (%s)', 'woocommerce-germanized' ), esc_html( WC_Germanized_Meta_Box_Product_Data::get_label_by_delivery_time_country( $country )  ) ); ?></label>
				        <?php
                            WC_Germanized_Meta_Box_Product_Data::output_delivery_time_select2( array(
                                'name'        => "variable_country_specific_delivery_times[{$loop}][{$country}]",
                                'placeholder' => __( 'Same as parent', 'woocommerce-germanized' ),
                                'term'        => $delivery_times[ $term_slug ],
                                'id'          => "variable_country_specific_delivery_times{$loop}-" . esc_attr( $country ),
                                'style'       => 'width: 100%',
                            ) );
				        ?>
                        <span class="description">
                        <a href="#" class="dashicons dashicons-no-alt wc-gzd-remove-country-specific-delivery-time"><?php _e( 'remove', 'woocommerce-germanized' ); ?></a>
                    </span>
                    </p>
			        <?php
		        }
	        } ?>

	        <?php if ( ! empty( $countries_left ) ) : ?>

                <div class="wc-gzd-new-country-specific-delivery-time-placeholder"></div>

                <p class="form-row wc-gzd-add-country-specific-delivery-time">
                    <label>&nbsp;</label>
                    <a href="#" class="wc-gzd-add-new-country-specific-delivery-time">+ <?php _e( 'Add country specific delivery time', 'woocommerce-germanized' ); ?></a>
                </p>

                <div class="wc-gzd-add-country-specific-delivery-time-template">
                    <p class="form-row form-row-full wc-gzd-country-specific-delivery-time-field wc-gzd-country-specific-delivery-time-field-variation wc-gzd-add-country-specific-delivery-time-field-variation">
                        <label for="country_specific_delivery_times">
                            <select class="enhanced select short" name="variable_new_country_specific_delivery_times_countries[<?php echo $loop; ?>][]">
                                <option value="" selected="selected"><?php _e( 'Select country', 'woocommerce-germanized' ); ?></option>
                                <?php
                                foreach ( $countries_left as $country_code => $country_name ) {
                                    echo '<option value="' . esc_attr( $country_code ) . '">' . esc_html( $country_name ) . '</option>';
                                }
                                ?>
                            </select>
                        </label>
                        <?php
                            WC_Germanized_Meta_Box_Product_Data::output_delivery_time_select2( array(
                                'name'        => "variable_new_country_specific_delivery_times_terms[{$loop}][]",
                                'placeholder' => __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ),
                            ) );
                        ?>
                        <span class="description">
                            <a href="#" class="dashicons dashicons-no-alt wc-gzd-remove-country-specific-delivery-time"><?php _e( 'remove', 'woocommerce-germanized' ); ?></a>
                        </span>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="variable_min_age">
            <p class="form-row form-row-full">
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
                      class="variable_mini_desc"><?php echo htmlspecialchars_decode( $gzd_product->get_mini_desc( 'edit' ) ); ?>
                </textarea>
            </p>
        </div>
		<?php
	}

	public static function save( $variation_id, $i ) {
		$data = array(
			'_unit_product'                                 => '',
			'_unit_price_auto'                              => '',
			'_unit_price_regular'                           => '',
			'_sale_price_label'                             => '',
			'_sale_price_regular_label'                     => '',
			'_unit_price_sale'                              => '',
			'_parent_unit_product'                          => '',
			'_parent_unit'                                  => '',
			'_parent_unit_base'                             => '',
			'_mini_desc'                                    => '',
			'_service'                                      => '',
			'delivery_time'                                 => '',
			'country_specific_delivery_times'               => '',
			'new_country_specific_delivery_times_countries' => '',
			'new_country_specific_delivery_times_terms'     => '',
			'_min_age'                                      => '',
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

		WC_Germanized_Meta_Box_Product_Data::save_product_data( $product, $data, true );
	}
}

WC_Germanized_Meta_Box_Product_Data_Variable::instance();