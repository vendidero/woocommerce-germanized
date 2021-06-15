<?php
/**
 * Adds unit price and delivery time to Product metabox.
 *
 * @author        Vendidero
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
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {

		if ( is_admin() ) {
			add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'output' ) );
			add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping' ) );

			add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ), 10, 1 );
			add_filter( 'product_type_options', array( __CLASS__, 'service_type' ), 10, 1 );
		}

		add_action( 'woocommerce_before_product_object_save', array( __CLASS__, 'on_save' ), 10, 1 );
		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'after_save' ), 10, 1 );

		/**
		 * Product duplication
		 */
		add_action( 'woocommerce_product_duplicate_before_save', array( __CLASS__, 'update_before_duplicate' ), 10, 2 );
	}

	/**
	 * @param WC_Product $duplicate
	 * @param WC_Product $product
	 */
	public static function update_before_duplicate( $duplicate, $product ) {
		if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {

			if ( $delivery_time = $gzd_product->get_delivery_time() ) {
				$duplicate->update_meta_data( '_product_delivery_time', $delivery_time->term_id );
			}
		}
	}

	/**
     * This method adjusts product data after saving a newly created product (e.g. through REST API).
     *
	 * @param WC_Product $product
	 */
	public static function after_save( $product ) {

	    // Do not update products on checkout - seems to cause problems with WPML
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		if ( $product && $product->get_id() > 0 && 'yes' === $product->get_meta( '_gzd_needs_after_save' ) ) {
		    self::adjust_product( $product );

		    $product->delete_meta_data( '_gzd_needs_after_save' );
		    $product->save();
		}
    }

	/**
	 * @param WC_Product $product
	 */
    protected static function adjust_product( $product ) {

	    $gzd_product = wc_gzd_get_product( $product );
	    $taxonomies  = array( 'product_delivery_time' );
	    $has_changed = false;

	    foreach ( $taxonomies as $taxonomy ) {
		    $term_data = $product->get_meta( '_' . $taxonomy, true );

		    if ( $term_data ) {
			    $term_data = ( is_numeric( $term_data ) ? absint( $term_data ) : $term_data );
			    wp_set_object_terms( $product->get_id(), $term_data, $taxonomy );

			    $product->delete_meta_data( '_' . $taxonomy );

			    $has_changed = true;
		    } elseif ( $product->get_meta( '_delete_' . $taxonomy, true ) ) {
			    wp_delete_object_term_relationships( $product->get_id(), $taxonomy );

			    $product->delete_meta_data( '_delete_' . $taxonomy );

			    $has_changed = true;
		    }
	    }

	    // Update unit price based on whether the product is on sale or not
	    if ( $gzd_product->has_unit() ) {

		    /**
		     * Filter to adjust unit price data before saving a product.
		     *
		     * @param array $data The unit price data.
		     * @param WC_Product $product The product object.
		     *
		     * @since 1.8.5
		     */
		    $data = apply_filters( 'woocommerce_gzd_save_display_unit_price_data', array(
			    '_unit_price_regular' => $gzd_product->get_unit_price_regular(),
			    '_unit_price_sale'    => $gzd_product->get_unit_price_sale(),
		    ), $product );

		    // Make sure we update automatically calculated prices
		    $gzd_product->set_unit_price_regular( $data['_unit_price_regular'] );
		    $gzd_product->set_unit_price_sale( $data['_unit_price_sale'] );

		    // Lets update the display price
		    if ( $product->is_on_sale() ) {
			    $gzd_product->set_unit_price( $data['_unit_price_sale'] );
		    } else {
			    $gzd_product->set_unit_price( $data['_unit_price_regular'] );
		    }

		    $has_changed = true;
	    }

	    return $has_changed;
    }

	/**
	 * Manipulating WooCommerce CRUD objects through REST API (saving) doesn't work
	 * because we need to use filters which do only receive the product id as a parameter and not the actual
	 * manipulated instance. That's why we need to temporarily store term data as product meta.
	 * After saving the product this hook checks whether term relationships need to be updated or deleted.
	 *
	 * @param WC_Product $product
	 */
	public static function on_save( $product ) {

		// Do not update products on checkout - seems to cause problems with WPML
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		if ( $product && $product->get_id() > 0 ) {
			self::adjust_product( $product );
		} elseif( $product && $product->get_id() <= 0 ) {
		    $product->update_meta_data( '_gzd_needs_after_save', 'yes' );
        }
	}

	public static function service_type( $types ) {

		$types['service'] = array(
			'id'            => '_service',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Service', 'woocommerce-germanized' ),
			'description'   => __( 'Service products do not sell physical products.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		$types['differential_taxation'] = array(
			'id'            => '_differential_taxation',
			'wrapper_class' => '',
			'label'         => __( 'Diff. Taxation', 'woocommerce-germanized' ),
			'description'   => __( 'Product applies to differential taxation based on §25a UStG.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		return $types;
	}

	public static function output() {

		global $post, $thepostid, $product_object;

		$_gzd_product = wc_gzd_get_product( $product_object );
		$age_select   = wc_gzd_get_age_verification_min_ages_select();

		echo '<div class="options_group show_if_simple show_if_external show_if_variable">';

		woocommerce_wp_select( array(
            'id'          => '_sale_price_label',
            'label'       => __( 'Sale Label', 'woocommerce-germanized' ),
            'options'     => array_merge( array( "-1" => __( 'Select Price Label', 'woocommerce-germanized' ) ), WC_germanized()->price_labels->get_labels() ),
            'desc_tip'    => true,
            'description' => __( 'If the product is on sale you may want to show a price label right before outputting the old price to inform the customer.', 'woocommerce-germanized' )
		) );
		woocommerce_wp_select( array(
            'id'          => '_sale_price_regular_label',
            'label'       => __( 'Sale Regular Label', 'woocommerce-germanized' ),
            'options'     => array_merge( array( "-1" => __( 'Select Price Label', 'woocommerce-germanized' ) ), WC_germanized()->price_labels->get_labels() ),
            'desc_tip'    => true,
            'description' => __( 'If the product is on sale you may want to show a price label right before outputting the new price to inform the customer.', 'woocommerce-germanized' )
		) );

		woocommerce_wp_select( array(
            'id'          => '_unit',
            'label'       => __( 'Unit', 'woocommerce-germanized' ),
            'options'     => array_merge( array( "-1" => __( 'Select unit', 'woocommerce-germanized' ) ), WC_germanized()->units->get_units() ),
            'desc_tip'    => true,
            'description' => __( 'Needed if selling on a per unit basis', 'woocommerce-germanized' )
		) );
		woocommerce_wp_text_input( array(
            'id'          => '_unit_product',
            'label'       => __( 'Product Units', 'woocommerce-germanized' ),
            'data_type'   => 'decimal',
            'desc_tip'    => true,
            'description' => __( 'Number of units included per default product price. Example: 1000 ml.', 'woocommerce-germanized' )
		) );
		woocommerce_wp_text_input( array(
            'id'          => '_unit_base',
            'label'       => __( 'Unit Price Units', 'woocommerce-germanized' ),
            'data_type'   => 'decimal',
            'desc_tip'    => true,
            'description' => __( 'Unit price units. Example unit price: 0,99 € / 100 ml. Insert 100 as unit price unit amount.', 'woocommerce-germanized' )
		) );

		echo '</div>';

		if ( $product_object->is_virtual() ) {

			// Show delivery time selection fallback if is virtual but delivery time should be visible on product
			$types = get_option( 'woocommerce_gzd_display_delivery_time_hidden_types', array() );

			if ( ! in_array( 'virtual', $types ) ) {
				// Remove default delivery time selection - otherwise input would exist 2 times
				remove_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'output_shipping' ), 10 );
				self::output_shipping();
			}
		}

		echo '<div class="options_group show_if_simple show_if_external">';

		woocommerce_wp_checkbox( array(
            'id'          => '_unit_price_auto',
            'label'       => __( 'Calculation', 'woocommerce-germanized' ),
            'description' => '<span class="wc-gzd-premium-desc">' . __( 'Calculate unit prices automatically.', 'woocommerce-germanized' ) . '</span> <a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="wc-gzd-pro">pro</a>'
		) );
		woocommerce_wp_text_input( array(
            'id'        => '_unit_price_regular',
            'label'     => __( 'Regular Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')',
            'data_type' => 'price'
		) );
		woocommerce_wp_text_input( array(
            'id'        => '_unit_price_sale',
            'label'     => __( 'Sale Unit Price', 'woocommerce-germanized' ) . ' (' . get_woocommerce_currency_symbol() . ')',
            'data_type' => 'price'
		) );

		echo '</div>';

		echo '<div class="options_group show_if_simple show_if_external show_if_variable show_if_booking">';

		woocommerce_wp_select( array(
            'id'          => '_min_age',
            'label'       => __( 'Minimum Age', 'woocommerce-germanized' ),
            'desc_tip'    => true,
            'description' => __( 'Adds an age verification checkbox while purchasing this product.', 'woocommerce-germanized' ),
            'options'     => $age_select
		) );

		echo '</div>';
	}

	public static function output_delivery_time_select2( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'name'        => 'delivery_time',
			'placeholder' => __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ),
			'term'        => false,
			'id'          => '',
			'style'       => 'width: 50%',
		) );

		$args['id'] = empty( $args['id'] ) ? $args['name'] : $args['id'];
		?>
        <select class="wc-product-search wc-gzd-delivery-time-search" style="<?php echo $args['style']; ?>"
                id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>"
                data-minimum_input_length="1" data-allow_clear="true"
                data-placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
                data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false">
			<?php if ( $args['term'] ) {
				echo '<option value="' . esc_attr( $args['term']->term_id ) . '"' . selected( true, true, false ) . '>' . $args['term']->name . '</option>';
			} ?>
        </select>
		<?php
	}

	public static function output_shipping() {
		global $post, $thepostid, $product_object;

		$gzd_product   = wc_gzd_get_product( $product_object );
		$delivery_time = $gzd_product->get_delivery_time();
		?>

        <p class="form-field">
            <label for="delivery_time"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></label>
			<?php
			self::output_delivery_time_select2( array(
				'name'        => 'delivery_time',
				'placeholder' => __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ),
				'term'        => $delivery_time,
			) );
			?>
        </p>

		<?php
		// Free shipping
		woocommerce_wp_checkbox( array( 'id'          => '_free_shipping',
		                                'label'       => __( 'Free shipping?', 'woocommerce-germanized' ),
		                                'description' => __( 'This option disables the "plus shipping costs" notice on product page', 'woocommerce-germanized' )
		) );
	}

	public static function get_fields() {
		return array(
			'product-type'              => '',
			'_unit'                     => '',
			'_unit_base'                => '',
			'_unit_product'             => '',
			'_unit_price_auto'          => '',
			'_unit_price_regular'       => '',
			'_unit_price_sale'          => '',
			'_sale_price_label'         => '',
			'_sale_price_regular_label' => '',
			'_mini_desc'                => '',
			'delivery_time'             => '',
			'_sale_price_dates_from'    => '',
			'_sale_price_dates_to'      => '',
			'_sale_price'               => '',
			'_free_shipping'            => '',
			'_service'                  => '',
			'_differential_taxation'    => '',
			'_min_age'                  => '',
		);
	}

	public static function save( $product ) {

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		$data = self::get_fields();

		foreach ( $data as $k => $v ) {
			$data[ $k ] = ( isset( $_POST[ $k ] ) ? $_POST[ $k ] : null );
		}

		$data['save'] = false;
		$product      = self::save_product_data( $product, $data );

		return $product;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	public static function get_default_product_data( $product ) {
		$fields = array(
			'product-type'           => $product->get_type(),
			'_sale_price_dates_from' => '',
			'_sale_price_dates_to'   => '',
			'_is_on_sale'            => $product->is_on_sale(),
			'_sale_price'            => $product->get_sale_price(),
		);

		if ( is_a( $fields['_sale_price_dates_from'], 'WC_DateTime' ) ) {
			$fields['_sale_price_dates_from'] = $fields['_sale_price_dates_from']->date_i18n();
		}

		if ( is_a( $fields['_sale_price_dates_to'], 'WC_DateTime' ) ) {
			$fields['_sale_price_dates_to'] = $fields['_sale_price_dates_to']->date_i18n();
		}

		return $fields;
	}

	public static function save_unit_price( &$product, $data, $is_variation = false ) {

		$data = wp_parse_args( $data, array(
			'save'    => true,
			'is_rest' => false,
		) );

		if ( $data['is_rest'] ) {
			$data = array_replace_recursive( static::get_default_product_data( $product ), $data );
		}

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		$gzd_product  = wc_gzd_get_product( $product );
		$product_type = ( ! isset( $data['product-type'] ) || empty( $data['product-type'] ) ) ? 'simple' : sanitize_title( stripslashes( $data['product-type'] ) );

		if ( isset( $data['_unit'] ) ) {
			if ( empty( $data['_unit'] ) || in_array( $data['_unit'], array( 'none', '-1' ) ) ) {
				$gzd_product->set_unit( '' );
			} else {
				$gzd_product->set_unit( wc_clean( $data['_unit'] ) );
			}
		}

		if ( isset( $data['_unit_base'] ) ) {
			$gzd_product->set_unit_base( $data['_unit_base'] );
		}

		if ( isset( $data['_unit_product'] ) ) {
			$gzd_product->set_unit_product( $data['_unit_product'] );
		}

		$gzd_product->set_unit_price_auto( ( isset( $data['_unit_price_auto'] ) ) ? 'yes' : 'no' );

		if ( isset( $data['_unit_price_regular'] ) ) {
			$gzd_product->set_unit_price_regular( $data['_unit_price_regular'] );
			$gzd_product->set_unit_price( $data['_unit_price_regular'] );
		}

		if ( isset( $data['_unit_price_sale'] ) ) {
			// Unset unit price sale if no product sale price has been defined
			if ( ! isset( $data['_sale_price'] ) || $data['_sale_price'] === '' ) {
				$data['_unit_price_sale'] = '';
			}

			$gzd_product->set_unit_price_sale( $data['_unit_price_sale'] );
		}

		// Ignore variable data
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) && ! $is_variation ) {

			$gzd_product->set_unit_price_regular( '' );
			$gzd_product->set_unit_price_sale( '' );
			$gzd_product->set_unit_price( '' );
			$gzd_product->set_unit_price_auto( false );

		} else {

			$date_from  = isset( $data['_sale_price_dates_from'] ) ? wc_clean( $data['_sale_price_dates_from'] ) : '';
			$date_to    = isset( $data['_sale_price_dates_to'] ) ? wc_clean( $data['_sale_price_dates_to'] ) : '';
			$is_on_sale = isset( $data['_is_on_sale'] ) ? $data['_is_on_sale'] : null;

			// Update price if on sale
			if ( isset( $data['_unit_price_sale'] ) ) {
				if ( ! is_null( $is_on_sale ) ) {
					if ( $is_on_sale ) {
						$gzd_product->set_unit_price( $data['_unit_price_sale'] );
					} else {
						$gzd_product->set_unit_price( $data['_unit_price_regular'] );
					}
				} else {
					if ( '' !== $data['_unit_price_sale'] && '' == $date_to && '' == $date_from ) {
						$gzd_product->set_unit_price( $data['_unit_price_sale'] );
					} else {
						$gzd_product->set_unit_price( $data['_unit_price_regular'] );
					}

					if ( '' !== $data['_unit_price_sale'] && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
						$gzd_product->set_unit_price( $data['_unit_price_sale'] );
					}

					if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
						$gzd_product->set_unit_price( $data['_unit_price_regular'] );
					}
				}
			}
		}

		if ( $data['save'] ) {
			$product->save();
		}
	}

	public static function save_product_data( $product, $data, $is_variation = false ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		$data = wp_parse_args( $data, array(
			'is_rest' => false,
		) );

		if ( $data['is_rest'] ) {
			$data = array_replace_recursive( static::get_default_product_data( $product ), $data );
		}

		/**
		 * Filter that allows adjusting Germanized product data before saving.
		 *
		 * @param array $data Product data to be saved.
		 * @param WC_Product $product The product object.
		 *
		 * @since 1.8.5
		 *
		 */
		$data = apply_filters( 'woocommerce_gzd_product_saveable_data', $data, $product );

		$data = wp_parse_args( $data, array(
			'save'    => true,
			'is_rest' => false,
		) );

		$unit_data         = $data;
		$unit_data['save'] = false;

		self::save_unit_price( $product, $unit_data, $is_variation );

		$gzd_product       = wc_gzd_get_product( $product );
		$product_type      = ( ! isset( $data['product-type'] ) || empty( $data['product-type'] ) ) ? 'simple' : sanitize_title( stripslashes( $data['product-type'] ) );
		$sale_price_labels = array( '_sale_price_label', '_sale_price_regular_label' );

		foreach ( $sale_price_labels as $label ) {
			if ( isset( $data[ $label ] ) ) {
				$setter = "set{$label}";

				if ( is_callable( array( $gzd_product, $setter ) ) ) {
					if ( empty( $data[ $label ] ) || in_array( $data[ $label ], array( 'none', '-1' ) ) ) {
						$gzd_product->$setter( '' );
					} else {
						$gzd_product->$setter( wc_clean( $data[ $label ] ) );
					}
				}
			}
		}

		if ( isset( $data['_mini_desc'] ) ) {
			$gzd_product->set_mini_desc( $data['_mini_desc'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_mini_desc'] ) );
		}

		if ( isset( $data['_min_age'] ) && array_key_exists( (int) $data['_min_age'], wc_gzd_get_age_verification_min_ages() ) ) {
			$gzd_product->set_min_age( absint( $data['_min_age'] ) );
		} else {
			$gzd_product->set_min_age( '' );
		}

		if ( isset( $data['delivery_time'] ) && ! empty( $data['delivery_time'] ) ) {
			$product->update_meta_data( '_product_delivery_time', $data['delivery_time'] );
		} else {
			$product->update_meta_data( '_delete_product_delivery_time', true );
		}

		// Free shipping
		$gzd_product->set_free_shipping( isset( $data['_free_shipping'] ) ? 'yes' : 'no' );

		// Is a service?
		$gzd_product->set_service( isset( $data['_service'] ) ? 'yes' : 'no' );

		// Applies to differential taxation?
		$gzd_product->set_differential_taxation( isset( $data['_differential_taxation'] ) ? 'yes' : 'no' );

		if ( $gzd_product->is_differential_taxed() ) {
			/**
			 * Filter the tax status of a differential taxed product.
			 *
			 * @param string     $tax_status The tax status, e.g. none or shipping.
             * @param WC_Product $product The product instance.
			 *
			 * @since 3.0.7
			 */
		    $tax_status_diff = apply_filters( 'woocommerce_gzd_product_differential_taxed_tax_status', 'none', $product );

			$product->set_tax_status( $tax_status_diff );
		}

		// Ignore variable data
		if ( in_array( $product_type, array( 'variable', 'grouped' ) ) && ! $is_variation ) {
			$gzd_product->set_mini_desc( '' );
		}

		if ( $data['save'] ) {
			$product->save();
		}

		return $product;
	}
}

WC_Germanized_Meta_Box_Product_Data::instance();