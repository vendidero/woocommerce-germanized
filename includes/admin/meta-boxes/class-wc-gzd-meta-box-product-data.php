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

	protected static $_instance = null;

	public static function instance() {
		
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		
		return self::$_instance;
	}

	private function __construct() {
		
		if ( is_admin() ) {
			add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'output' ) );
			add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping' ) );
			if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
				add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ), 20, 2 );
            } else {
			    add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ), 10, 1 );
            }
			add_filter( 'product_type_options', array( __CLASS__, 'service_type' ), 10, 1 );
		}

		/**
		 * Listen to product updates to actually transform term meta data to term relationships e.g. for product delivery time.
		 */
		add_action( 'woocommerce_update_product', array( __CLASS__, 'update_terms' ), 10, 1 );
		add_action( 'woocommerce_create_product', array( __CLASS__, 'update_terms' ), 10, 1 );

		add_action( 'woocommerce_update_product_variation', array( __CLASS__, 'update_terms' ), 10, 1 );
		add_action( 'woocommerce_create_product_variation', array( __CLASS__, 'update_terms' ), 10, 1 );
	}

	/**
     * Manipulating WooCommerce CRUD objects through REST API (saving) doesn't work
     * because we need to use filters which do only receive the product id as a parameter and not the actual
     * manipulated instance. That's why we need to temporarily store term data as product meta.
     * After saving the product this hook checks whether term relationships need to be updated or deleted.
     *
	 * @param $product_id
	 */
	public static function update_terms( $product_id ) {

		if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() )
		    return;

	    $product = wc_get_product( $product_id );

	    if ( $product->get_id() > 0 ) {
	        $taxonomies = array( 'product_delivery_time' );

	        foreach( $taxonomies as $taxonomy ) {

	            $term_data = $product->get_meta( '_' . $taxonomy, true );

	            if ( $term_data ) {
	                $term_data = ( is_numeric( $term_data ) ? absint( $term_data ) : $term_data );
		            wp_set_object_terms( $product->get_id(), $term_data, $taxonomy );
		            delete_post_meta( $product->get_id(), '_' . $taxonomy );
                } elseif ( $product->get_meta( '_delete_' . $taxonomy, true ) ) {
		            wp_delete_object_term_relationships( $product->get_id(), $taxonomy );
		            delete_post_meta( $product->get_id(), '_delete_' . $taxonomy );
	            }
            }
        }
    }

	public static function service_type( $types ) {

		$types[ 'service' ] = array(
			'id'            => '_service',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Service', 'woocommerce-germanized' ),
			'description'   => __( 'Service products do not sell physical products.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		return $types;
	}

	public static function output() {

		global $post, $thepostid;
		$thepostid = $post->ID;
		$_product = wc_get_product( $thepostid );

		echo '<div class="options_group show_if_simple show_if_external show_if_variable">';

		woocommerce_wp_select( array( 'id' => '_sale_price_label', 'label' => __( 'Sale Label', 'woocommerce-germanized' ), 'options' => array_merge( array( "-1" => __( 'Select Price Label', 'woocommerce-germanized' ) ), WC_germanized()->price_labels->get_labels() ), 'desc_tip' => true, 'description' => __( 'If the product is on sale you may want to show a price label right before outputting the old price to inform the customer.', 'woocommerce-germanized' ) ) );
		woocommerce_wp_select( array( 'id' => '_sale_price_regular_label', 'label' => __( 'Sale Regular Label', 'woocommerce-germanized' ), 'options' => array_merge( array( "-1" => __( 'Select Price Label', 'woocommerce-germanized' ) ), WC_germanized()->price_labels->get_labels() ), 'desc_tip' => true, 'description' => __( 'If the product is on sale you may want to show a price label right before outputting the new price to inform the customer.', 'woocommerce-germanized' ) ) );

		woocommerce_wp_select( array( 'id' => '_unit', 'label' => __( 'Unit', 'woocommerce-germanized' ), 'options' => array_merge( array( "-1" => __( 'Select unit', 'woocommerce-germanized' ) ), WC_germanized()->units->get_units() ), 'desc_tip' => true, 'description' => __( 'Needed if selling on a per unit basis', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_product', 'label' => __( 'Product Units', 'woocommerce-germanized' ), 'data_type' => 'decimal', 'desc_tip' => true, 'description' => __( 'Number of units included per default product price. Example: 1000 ml.', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_base', 'label' => __( 'Base Price Units', 'woocommerce-germanized' ), 'data_type' => 'decimal', 'desc_tip' => true, 'description' => __( 'Base price units. Example base price: 0,99 € / 100 ml. Insert 100 as base price unit amount.', 'woocommerce-germanized' ) ) );

		echo '</div>';

		if ( $_product->is_virtual() ) {

			// Show delivery time selection fallback if is virtual but delivery time should be visible on product
			$types = get_option( 'woocommerce_gzd_display_delivery_time_hidden_types', array() );

			if ( ! in_array( 'virtual', $types ) ) {

				// Remove default delivery time selection - otherwise input would exist 2 times
				remove_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping' ), 10 );
				self::output_shipping();

			}
		}

		echo '<div class="options_group show_if_simple show_if_external">';

		woocommerce_wp_checkbox( array( 'id' => '_unit_price_auto', 'label' => __( 'Calculation', 'woocommerce-germanized' ), 'description' => '<span class="wc-gzd-premium-desc">' . __( 'Calculate base prices automatically.', 'woocommerce-germanized' ) . '</span> <a href="https://vendidero.de/woocommerce-germanized#buy" target="_blank" class="wc-gzd-pro">pro</a>' ) );

		woocommerce_wp_text_input( array( 'id' => '_unit_price_regular', 'label' => __( 'Regular Base Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		woocommerce_wp_text_input( array( 'id' => '_unit_price_sale', 'label' => __( 'Sale Base Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		
		echo '</div>';

	}

	public static function output_delivery_time_select2( $args = array() ) {

	    $args = wp_parse_args( $args, array(
            'name' => 'delivery_time',
            'placeholder' => __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ),
            'term' => false,
            'id' => '',
            'style' => 'width: 50%',
        ) );

	    $args[ 'id' ] = empty( $args[ 'id' ] ) ? $args[ 'name' ] : $args[ 'id' ];

	    if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
	        ?>
            <select class="wc-product-search wc-gzd-delivery-time-search" style="<?php echo $args[ 'style' ]; ?>" id="<?php echo $args[ 'id' ]; ?>" name="<?php echo $args[ 'name' ]; ?>" data-minimum_input_length="1" data-allow_clear="true" data-placeholder="<?php echo $args[ 'placeholder' ]; ?>" data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false">
                <?php

                if ( $args[ 'term' ] ) {
                    echo '<option value="' . esc_attr( $args[ 'term' ]->term_id ) . '"' . selected( true, true, false ) . '>' . $args[ 'term' ]->name . '</option>';
                }

                ?>
            </select>
            <?php
        } else {
	        ?>
            <input type="hidden" class="wc-product-search wc-gzd-delivery-time-search" style="<?php echo $args[ 'style' ]; ?>" id="<?php echo $args[ 'id' ]; ?>" name="<?php echo $args[ 'name' ]; ?>" data-minimum_input_length="1" data-allow_clear="true" data-placeholder="<?php echo $args[ 'placeholder' ]; ?>" data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false" data-selected="<?php echo ( $args[ 'term' ] ? $args[ 'term' ]->name : '' ); ?>" value="<?php echo ( $args[ 'term' ] ? $args[ 'term' ]->term_id : '' ); ?>" />
            <?php
        }
    }

	public static function output_shipping() {

		global $post, $thepostid;

		$thepostid = $post->ID;
		$_product = wc_get_product( $thepostid );

		$delivery_time = wc_gzd_get_gzd_product( $_product )->delivery_time;

		?>	

		<p class="form-field">
			<label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>
            <?php
                self::output_delivery_time_select2( array(
                    'name' => 'delivery_time',
                    'placeholder' => __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ),
                    'term' => $delivery_time,
                ) );
            ?>
		</p>
		
		<?php

		// Free shipping
		woocommerce_wp_checkbox( array( 'id' => '_free_shipping', 'label' => __( 'Free shipping?', 'woocommerce-germanized' ), 'description' => __( 'This option disables the "plus shipping costs" notice on product page', 'woocommerce-germanized' ) ) );

	}

	public static function get_fields() {
		return array(
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
			'_service' => '',
		);
	}

	public static function save( $product ) {

	    if ( is_numeric( $product ) )
		    $product = wc_get_product( $product );

		$data = self::get_fields();

		foreach ( $data as $k => $v ) {
			$data[ $k ] = ( isset( $_POST[ $k ] ) ? $_POST[ $k ] : null );
		}

		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() )
			$data[ 'save' ] = false;

		$product = self::save_product_data( $product, $data );

		return $product;
	}

	public static function save_unit_price( $product, $data, $is_variation = false ) {

		$data = wp_parse_args( $data, array(
			'save' => true,
		) );

		if ( is_numeric( $product ) ) {
		    $product = wc_get_product( $product );
        }

		$product_type = ( ! isset( $data['product-type'] ) || empty( $data['product-type'] ) ) ? 'simple' : sanitize_title( stripslashes( $data['product-type'] ) );

		if ( isset( $data['_unit'] ) ) {

			if ( empty( $data['_unit'] ) || in_array( $data['_unit'], array( 'none', '-1' ) ) )
				$product = wc_gzd_unset_crud_meta_data( $product, '_unit' );
			else
				$product = wc_gzd_set_crud_meta_data( $product, '_unit', sanitize_text_field( $data['_unit'] ) );

		}

		if ( isset( $data['_unit_base'] ) ) {
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_base', ( $data['_unit_base'] === '' ) ? '' : wc_format_decimal( $data['_unit_base'] ) );
		}

		if ( isset( $data['_unit_product'] ) ) {
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_product', ( $data['_unit_product'] === '' ) ? '' : wc_format_decimal( $data['_unit_product'] ) );
		}

		$product = wc_gzd_set_crud_meta_data( $product, '_unit_price_auto', ( isset( $data['_unit_price_auto'] ) ) ? 'yes' : '' );
		
		if ( isset( $data['_unit_price_regular'] ) ) {
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price_regular', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
		}
		
		if ( isset( $data['_unit_price_sale'] ) ) {

			// Unset unit price sale if no product sale price has been defined
			if ( ! isset( $data['_sale_price'] ) || $data['_sale_price'] === '' )
				$data['_unit_price_sale'] = '';

			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price_sale', ( $data['_unit_price_sale'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_sale'] ) );
		}

		// Ignore variable data
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) && ! $is_variation ) {

			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price_regular', '' );
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price_sale', '' );
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price', '' );
			$product = wc_gzd_set_crud_meta_data( $product, '_unit_price_auto', '' );

		} else {

			$date_from = isset( $data['_sale_price_dates_from'] ) ? wc_clean( $data['_sale_price_dates_from'] ) : '';
			$date_to   = isset( $data['_sale_price_dates_to'] ) ? wc_clean( $data['_sale_price_dates_to'] ) : '';

			// Update price if on sale
			if ( isset( $data['_unit_price_sale'] ) ) {
				
				if ( '' !== $data['_unit_price_sale'] && '' == $date_to && '' == $date_from ) {
					$product = wc_gzd_set_crud_meta_data( $product, '_unit_price', wc_format_decimal( $data['_unit_price_sale'] ) );
				} else {
					$product = wc_gzd_set_crud_meta_data( $product, '_unit_price', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
				}

				if ( '' !== $data['_unit_price_sale'] && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
					$product = wc_gzd_set_crud_meta_data( $product, '_unit_price', wc_format_decimal( $data['_unit_price_sale'] ) );
				}

				if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) )
					$product = wc_gzd_set_crud_meta_data( $product, '_unit_price', ( $data['_unit_price_regular'] === '' ) ? '' : wc_format_decimal( $data['_unit_price_regular'] ) );
			}
		}

		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() && $data[ 'save' ] ) {
			$product->save();
		}

		return $product;
	}

	public static function save_product_data( $product, $data, $is_variation = false ) {

	    if ( is_numeric( $product ) )
	        $product = wc_get_product( $product );

		$data = apply_filters( 'woocommerce_gzd_product_saveable_data', $data, $product );
		$data = wp_parse_args( $data, array(
            'save' => true,
        ) );

		$unit_data = $data;
		$unit_data[ 'save' ] = false;
		$product = self::save_unit_price( $product, $unit_data, $is_variation );

		$product_type = ( ! isset( $data['product-type'] ) || empty( $data['product-type'] ) ) ? 'simple' : sanitize_title( stripslashes( $data['product-type'] ) );

		$sale_price_labels = array( '_sale_price_label', '_sale_price_regular_label' );

		foreach ( $sale_price_labels as $label ) {

			if ( isset( $data[$label] ) ) {

				if ( empty( $data[$label] ) || in_array( $data[$label], array( 'none', '-1' ) ) )
					$product = wc_gzd_unset_crud_meta_data( $product, $label );
				else
					$product = wc_gzd_set_crud_meta_data( $product, $label, sanitize_text_field( $data[$label] ) );
			}
		}
		
		if ( isset( $data[ '_mini_desc' ] ) ) {
			$product = wc_gzd_set_crud_meta_data( $product, '_mini_desc', ( $data[ '_mini_desc' ] === '' ? '' : sanitize_text_field( esc_html( $data[ '_mini_desc' ] ) ) ) );
		}

		if ( isset( $data[ 'delivery_time' ] ) && ! empty( $data[ 'delivery_time' ] ) ) {
			$product = wc_gzd_set_crud_term_data( $product, $data[ 'delivery_time' ], 'product_delivery_time' );
		} else {
			$product = wc_gzd_unset_crud_term_data( $product, 'product_delivery_time' );
		}

		// Free shipping
		$product = wc_gzd_set_crud_meta_data( $product, '_free_shipping', ( isset( $data['_free_shipping'] ) ) ? 'yes' : '' );

		// Free shipping
		$product = wc_gzd_set_crud_meta_data( $product, '_service', ( isset( $data['_service'] ) ) ? 'yes' : '' );

		// Ignore variable data
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) && ! $is_variation ) {
			$product = wc_gzd_set_crud_meta_data( $product, '_mini_desc', '' );
		}

		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() && $data[ 'save' ] ) {
			$product->save();
		}

		return $product;
	}

}

WC_Germanized_Meta_Box_Product_Data::instance();