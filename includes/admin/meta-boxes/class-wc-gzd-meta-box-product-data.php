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
			add_filter( 'product_type_options', array( __CLASS__, 'product_types' ), 10, 1 );
		}

		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'register_product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'food_tab' ) );

		add_action( 'woocommerce_product_object_updated_props', array( __CLASS__, 'update_terms' ), 10, 1 );
		add_action( 'woocommerce_before_product_object_save', array( __CLASS__, 'before_save' ), 10, 1 );

		add_action( 'woocommerce_product_bulk_edit_end', array( __CLASS__, 'bulk_edit' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( __CLASS__, 'bulk_save' ) );

        add_action( 'woocommerce_product_quick_edit_end', array( __CLASS__, 'quick_edit' ) );
        add_action( 'add_inline_data', array( __CLASS__, 'quick_edit_data' ), 10, 2 );
        add_action( 'woocommerce_product_quick_edit_save', array( __CLASS__, 'quick_edit_save' ) );

		/**
		 * Product duplication
		 */
		add_action( 'woocommerce_product_duplicate_before_save', array( __CLASS__, 'update_before_duplicate' ), 10, 2 );
	}

	public static function food_tab() {
		global $post, $thepostid, $product_object;

		$_gzd_product = wc_gzd_get_product( $product_object );
		?>
        <div id="food_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group show_if_simple show_if_external show_if_variable">
                <?php
                    woocommerce_wp_select( array(
                        'id'          => '_deposit_type',
                        'label'       => __( 'Deposit Type', 'woocommerce-germanized' ),
                        'options'     => array( "-1" => __( 'Select Deposit Type', 'woocommerce-germanized' ) ) + WC_germanized()->deposit_types->get_deposit_types(),
                        'desc_tip'    => true,
                        'description' => __( 'In case this product is reusable and has deposits, select the deposit type.', 'woocommerce-germanized' )
                    ) );

                    woocommerce_wp_text_input( array(
                        'id'          => '_deposit_quantity',
                        'label'       => __( 'Deposit Quantity', 'woocommerce-germanized' ),
                        'type'        => 'number',
                        'placeholder' => 1,
                        'custom_attributes' => array( 'min' => 1 ),
                        'desc_tip'    => true,
                        'description' => __( 'Number of units for deposit.', 'woocommerce-germanized' )
                    ) );
                ?>
            </div>

	        <?php do_action(  'woocommerce_gzd_edit_product_food_panel' ); ?>
        </div>
		<?php
	}

	public static function register_product_tab( $tabs ) {
		$tabs['food'] = array(
			'label'    => __( 'Food', 'woocommerce-germanized-pro' ),
			'target'   => 'food_product_data',
			'class'    => array( 'show_if_is_food' ),
			'priority' => 35,
		);

		return $tabs;
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return void
	 */
    public static function quick_edit_save( $product ) {
	    if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
            $delivery_time = isset( $_REQUEST['_delivery_time'] ) ? wc_clean( $_REQUEST['_delivery_time'] ) : '';

            if ( ! empty( $delivery_time ) ) {
                $needs_update = true;

                if ( $slug = wc_gzd_get_valid_product_delivery_time_slugs( $delivery_time ) ) {
                    $gzd_product->set_default_delivery_time_slug( $slug );
                }
            } else {
                $needs_update = true;
                $gzd_product->set_default_delivery_time_slug( '' );
            }

		    if ( isset( $_REQUEST['_unit'] ) ) {
			    $unit = wc_clean( $_REQUEST['_unit'] );

			    if ( ! empty( $unit ) ) {
				    $needs_update = true;

				    if ( '_no_unit' === $unit ) {
					    $gzd_product->set_unit( '' );
				    } elseif ( $term = get_term_by( 'slug', $unit, 'product_unit' ) ) {
					    $gzd_product->set_unit( $term->slug );
				    }
			    }
		    }

		    if ( $needs_update ) {
			    $gzd_product->get_wc_product()->save();
			    $gzd_product->save();
		    }
	    }
    }

    public static function quick_edit_data( $post, $post_type_object ) {
        if ( 'product' === $post_type_object->name ) {
            if ( $gzd_product = wc_gzd_get_product( $post ) ) {
                $default_delivery_time = $gzd_product->get_default_delivery_time( 'edit' );

                echo '
                    <div class="gzd_delivery_time_slug">' . esc_html( $default_delivery_time ? $default_delivery_time->slug : '' ) . '</div>
                    <div class="gzd_delivery_time_name">' . esc_html( $default_delivery_time ? $default_delivery_time->name : '' ) . '</div>
                    <div class="gzd_unit_slug">' . esc_html( $gzd_product->get_unit( 'edit' ) ) . '</div>
                ';
            }
        }
    }

    public static function quick_edit() {
        ?>
        <div class="inline-edit-group gzd_fields">
            <label class="gzd_delivery_time_field">
                <span class="title"><?php esc_html_e( 'Delivery Time', 'woocommerce-germanized' ); ?></span>
                <span class="input-text-wrap">
                    <select class="wc-gzd-delivery-time-select-placeholder" style="width: 100%; min-width: 150px;" name="_delivery_time"
                            data-minimum_input_length="1" data-allow_clear="true"
                            data-placeholder="<?php echo esc_attr( __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ) ); ?>"
                            data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false">
                    </select>
                </span>
            </label>
            <label class="gzd_unit_field">
                <span class="title"><?php esc_html_e( 'Unit', 'woocommerce-germanized' ); ?></span>
                <span class="input-text-wrap">
                    <select class="unit" name="_unit">
                        <option value="_no_unit"><?php _e( 'No unit', 'woocommerce-germanized' ); ?></option>
                        <?php
                        foreach ( WC_germanized()->units->get_units() as $key => $value ) {
                            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
                        }
                        ?>
                    </select>
                </span>
            </label>
        </div>
        <?php
    }

    public static function bulk_save( $product ) {
        if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
            $needs_update         = false;
            $change_delivery_time = isset( $_REQUEST['change_delivery_time'] ) ? absint( $_REQUEST['change_delivery_time'] ) : '';

	        if ( ! empty( $change_delivery_time ) && in_array( $change_delivery_time, array( 1, 2 ) ) ) {
		        $delivery_time = isset( $_REQUEST['_delivery_time'] ) ? wc_clean( $_REQUEST['_delivery_time'] ) : '';

		        if ( ! empty( $delivery_time ) && 1 === $change_delivery_time ) {
                    $needs_update = true;

			        if ( $slug = wc_gzd_get_valid_product_delivery_time_slugs( $delivery_time ) ) {
				        $gzd_product->set_default_delivery_time_slug( $slug );
			        }
		        } elseif ( 2 === $change_delivery_time ) {
			        $needs_update = true;

			        $gzd_product->set_default_delivery_time_slug( '' );
                }
	        }

	        if ( isset( $_REQUEST['_unit'] ) ) {
		        $unit = wc_clean( $_REQUEST['_unit'] );

		        if ( ! empty( $unit ) ) {
			        $needs_update = true;

			        if ( '_no_unit' === $unit ) {
				        $gzd_product->set_unit( '' );
			        } elseif ( $term = get_term_by( 'slug', $unit, 'product_unit' ) ) {
				        $gzd_product->set_unit( $term->slug );
			        }
		        }
	        }

            if ( $needs_update ) {
	            $gzd_product->get_wc_product()->save();
	            $gzd_product->save();
            }
        }
    }

    public static function bulk_edit() {
        ?>
        <div class="inline-edit-group delivery-time">
            <label class="alignleft">
                <span class="title"><?php _e( 'Delivery Time', 'woocommerce-germanized' ); ?></span>
                <span class="input-text-wrap">
						<select class="change_delivery_time change_to" name="change_delivery_time">
							<?php
							$options = array(
								''  => __( '— No change —', 'woocommerce-germanized' ),
								'1' => __( 'Change to:', 'woocommerce-germanized' ),
								'2' => __( 'No delivery time', 'woocommerce-germanized' ),
							);
							foreach ( $options as $key => $value ) {
								echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
							}
							?>
						</select>
					</span>
            </label>
            <label class="change-input">
                <select class="wc-product-search wc-gzd-delivery-time-search" style="width: 100%; min-width: 150px;" name="_delivery_time"
                        data-minimum_input_length="1" data-allow_clear="true"
                        data-placeholder="<?php echo esc_attr( __( '— No change —', 'woocommerce-germanized' ) ); ?>"
                        data-action="woocommerce_gzd_json_search_delivery_time" data-multiple="false">
                </select>
            </label>
        </div>
        <label class="alignleft">
            <span class="title"><?php _e( 'Unit', 'woocommerce-germanized' ); ?></span>
            <span class="input-text-wrap">
                <select class="unit" name="_unit">
                    <option value=""><?php _e( '— No change —', 'woocommerce-germanized' ); ?></option>
                    <option value="_no_unit"><?php _e( 'No unit', 'woocommerce-germanized' ); ?></option>
					<?php
                        foreach ( WC_germanized()->units->get_units() as $key => $value ) {
                            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
                        }
					?>
                </select>
            </span>
        </label>
        <?php
    }

	/**
	 * @param WC_Product $product
	 */
	public static function update_terms( $product ) {
	    if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
		    $gzd_product->save();
        }
    }

	/**
	 * @param WC_Product $duplicate
	 * @param WC_Product $product
	 */
	public static function update_before_duplicate( $duplicate, $product ) {
		if ( $gzd_product = wc_gzd_get_product( $product ) ) {
		    $gzd_duplicate = wc_gzd_get_product( $duplicate );

		    if ( $term = $gzd_product->get_default_delivery_time( 'edit' ) ) {
			    $gzd_duplicate->set_default_delivery_time_slug( $term->slug );
            }

		    $gzd_duplicate->set_country_specific_delivery_times( $gzd_product->get_country_specific_delivery_times( 'edit' ) );
            $gzd_duplicate->set_delivery_times_need_update();
		}
	}

	/**
	 * @param WC_Product $product
	 */
	public static function before_save( $product ) {
		$gzd_product = wc_gzd_get_product( $product );

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
		}
	}

	public static function product_types( $types ) {
		$types['service'] = array(
			'id'            => '_service',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Service', 'woocommerce-germanized' ),
			'description'   => __( 'Service products do not sell physical products.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		$types['used_good'] = array(
			'id'            => '_used_good',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Used Good', 'woocommerce-germanized' ),
			'description'   => __( 'Product is a used good.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		$types['defective_copy'] = array(
			'id'            => '_defective_copy',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Defective Copy', 'woocommerce-germanized' ),
			'description'   => __( 'Product has defects.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		$types['differential_taxation'] = array(
			'id'            => '_differential_taxation',
			'wrapper_class' => '',
			'label'         => __( 'Differential taxed', 'woocommerce-germanized' ),
			'description'   => __( 'Product applies to differential taxation based on §25a UStG.', 'woocommerce-germanized' ),
			'default'       => 'no'
		);

		$types['is_food'] = array(
			'id'            => '_is_food',
			'wrapper_class' => '',
			'label'         => __( 'Food', 'woocommerce-germanized' ),
			'description'   => __( 'This product is a food product.', 'woocommerce-germanized' ),
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
            'description' => '<span class="wc-gzd-premium-desc">' . __( 'Calculate unit prices automatically.', 'woocommerce-germanized' ) . '</span> <a href="https://vendidero.de/woocommerce-germanized#upgrade" target="_blank" class="wc-gzd-pro wc-gzd-pro-outlined">pro</a>'
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

		echo '<div class="options_group show_if_simple show_if_variable show_if_external">';

        self::output_warranty_upload();

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
		$delivery_time = $gzd_product->get_delivery_time( 'edit' );
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
            self::output_delivery_time_by_country( $product_object );

            // Free shipping
            woocommerce_wp_checkbox( array(
                'id'          => '_free_shipping',
                'label'       => __( 'Free shipping?', 'woocommerce-germanized' ),
                'description' => __( 'This option disables the "plus shipping costs" notice on product page', 'woocommerce-germanized' )
            ) );
	}

	public static function output_warranty_upload() {
		global $post, $thepostid, $product_object;

		$gzd_product = wc_gzd_get_product( $product_object );
		?>
        <p class="form-field wc-gzd-warranty-upload-wrapper">
            <label for="upload_warranty_button"><?php _e( 'Warranty (PDF)', 'woocommerce-germanized' ); ?></label>
            <a href="#" class="button upload_warranty_button" data-default-label="<?php echo esc_html__( 'Choose file', 'woocommerce-germanized' ); ?>" data-choose="<?php esc_attr_e( 'Choose file', 'woocommerce-germanized' ); ?>" data-update="<?php esc_attr_e( 'Select warranty file', 'woocommerce-germanized' ); ?>"><?php echo ( $gzd_product->has_warranty() ? $gzd_product->get_warranty_filename() : esc_html__( 'Choose file', 'woocommerce-germanized' ) ); ?></a>
            <input type="hidden" name="_warranty_attachment_id" value="<?php echo ( $gzd_product->has_warranty() ? $gzd_product->get_warranty_attachment_id() : '' ); ?>" class="wc-gzd-warranty-attachment" />
            <a href="#" class="wc-gzd-warranty-delete <?php echo ( ! $gzd_product->has_warranty() ? 'file-missing' : '' ); ?>"><?php _e( 'Delete', 'woocommerce-germanized' ); ?></a>
        </p>
        <?php
	}

	public static function get_available_delivery_time_countries() {
	    $countries    = WC()->countries->get_shipping_countries();
		$base_country = WC()->countries->get_base_country();

		if ( array_key_exists( $base_country, $countries ) ) {
		    unset( $countries[ $base_country] );
		}

		$eu_options = array(
            'EU-wide' => __( 'EU-wide', 'woocommerce-germanized' )
        );

		if ( wc_gzd_base_country_is_eu() ) {
		    $eu_options['Non-EU-wide'] = __( 'Non-EU-wide', 'woocommerce-germanized' );
		}

		return $eu_options + $countries;
	}

	public static function is_available_delivery_time_country( $country ) {
	    $countries = self::get_available_delivery_time_countries();

	    return array_key_exists( $country, $countries ) ? true : false;
	}

	public static function get_label_by_delivery_time_country( $country ) {
		$countries = self::get_available_delivery_time_countries();
		$label     = $country;

		if ( in_array( $country, array( 'EU-wide', 'Non-EU-wide' ) ) ) {
		    $label = $countries[ $country ];
		}

		return $label;
	}

	/**
	 * @param WC_Product $product_object
	 */
	public static function output_delivery_time_by_country( $product_object ) {
		$gzd_product               = wc_gzd_get_product( $product_object );
		$countries_left            = self::get_available_delivery_time_countries();
		$delivery_times            = $gzd_product->get_delivery_times( 'edit' );
		$delivery_times_by_country = $gzd_product->get_country_specific_delivery_times( 'edit' );
	    ?>

        <?php if ( ! empty( $delivery_times_by_country ) ) {
			foreach( $delivery_times_by_country as $country => $term_slug ) {
				$countries_left = array_diff_key( $countries_left, array( $country => '' ) );
				?>
                <p class="form-field field wc-gzd-country-specific-delivery-time-field _country_specific_delivery_times_-<?php echo esc_attr( $country ); ?>_field">
                    <label for="country_specific_delivery_times-<?php echo esc_attr( $country ); ?>"><?php printf( __( 'Delivery Time (%s)', 'woocommerce-germanized' ), esc_html( self::get_label_by_delivery_time_country( $country ) ) ); ?></label>
                    <?php
                        self::output_delivery_time_select2( array(
                            'name'        => "country_specific_delivery_times[$country]",
                            'placeholder' => __( 'Same as default', 'woocommerce-germanized' ),
                            'term'        => $delivery_times[ $term_slug ],
                            'id'          => 'country_specific_delivery_times-' . esc_attr( $country )
                        ) );
                    ?>
                    <span class="description">
                        <a href="#" class="dashicons dashicons-no-alt wc-gzd-remove-country-specific-delivery-time"><?php _e( 'remove', 'woocommerce-germanized' ); ?></a>
                    </span>
                </p>
                <?php
			}
		} ?>

        <div class="wc-gzd-new-country-specific-delivery-time-placeholder"></div>

        <p class="form-field wc-gzd-add-country-specific-delivery-time">
            <label>&nbsp;</label>
            <a href="#" class="wc-gzd-add-new-country-specific-delivery-time">+ <?php _e( 'Add country specific delivery time', 'woocommerce-germanized' ); ?></a>
        </p>

        <?php if ( ! empty( $countries_left ) ) : ?>
            <div class="wc-gzd-add-country-specific-delivery-time-template">
                <p class="form-field wc-gzd-country-specific-delivery-time-field">
                    <label for="country_specific_delivery_times">
                        <select class="enhanced select" name="new_country_specific_delivery_times_countries[]">
                            <option value="" selected="selected"><?php _e( 'Select country', 'woocommerce-germanized' ); ?></option>
                            <?php
                            foreach ( $countries_left as $country_code => $country_name ) {
                                echo '<option value="' . esc_attr( $country_code ) . '">' . esc_html( $country_name ) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                    <?php
                        self::output_delivery_time_select2( array(
                            'name'        => "new_country_specific_delivery_times_terms[]",
                            'placeholder' => __( 'Search for a delivery time&hellip;', 'woocommerce-germanized' ),
                        ) );
                    ?>
                    <span class="description">
                        <a href="#" class="dashicons dashicons-no-alt wc-gzd-remove-country-specific-delivery-time"><?php _e( 'remove', 'woocommerce-germanized' ); ?></a>
                    </span>
                </p>
            </div>
        <?php endif;
	}

	public static function get_fields() {
		return array(
			'product-type'                                  => '',
			'_unit'                                         => '',
			'_unit_base'                                    => '',
			'_unit_product'                                 => '',
			'_unit_price_auto'                              => '',
			'_unit_price_regular'                           => '',
			'_unit_price_sale'                              => '',
			'_deposit_type'                                 => '',
			'_deposit_quantity'                             => '',
			'_sale_price_label'                             => '',
			'_sale_price_regular_label'                     => '',
			'_mini_desc'                                    => '',
			'_defect_description'                           => '',
			'_warranty_attachment_id'                       => '',
			'delivery_time'                                 => '',
			'country_specific_delivery_times'               => '',
			'new_country_specific_delivery_times_countries' => '',
			'new_country_specific_delivery_times_terms'     => '',
			'_sale_price_dates_from'                        => '',
			'_sale_price_dates_to'                          => '',
			'_sale_price'                                   => '',
			'_free_shipping'                                => '',
			'_service'                                      => '',
			'_used_good'                                    => '',
			'_defective_copy'                               => '',
			'_differential_taxation'                        => '',
			'_is_food'                                      => '',
			'_min_age'                                      => '',
			'_nutrient_ids'                                 => '',
			'_nutrient_reference_value'                     => '',
			'_allergen_ids'                                 => '',
			'_ingredients'                                  => '',
			'_nutri_score'                                  => '',
			'_alcohol_content'                              => '',
			'_drained_weight'                               => '',
			'_net_filling_quantity'                         => '',
			'_food_distributor'                             => '',
			'_food_place_of_origin'                         => '',
			'_food_description'                             => '',
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
		self::save_product_data( $product, $data );

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

        $unit_price_auto = 'no';

        if ( isset( $data['_unit_price_auto'] ) ) {
	        /**
	         * Respect false/no to unset auto price calculation
	         */
            if ( false !== $data['_unit_price_auto'] && 'no' !== $data['_unit_price_auto'] ) {
                $unit_price_auto = 'yes';
            }
        }

		$gzd_product->set_unit_price_auto( $unit_price_auto );

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

	/**
	 * @param WC_GZD_Product $gzd_product
	 * @param $data
     *
     * @TODO need to check for REST API compatibility in case country-specific delivery times are missing during the request
	 */
	protected static function save_delivery_times( $gzd_product, $data ) {
		if ( isset( $data['delivery_time'] ) ) {
		    if ( $slug = wc_gzd_get_valid_product_delivery_time_slugs( $data['delivery_time'] ) ) {
			    $gzd_product->set_default_delivery_time_slug( $slug );
		    } else {
			    $gzd_product->set_default_delivery_time_slug( '' );
		    }
		} elseif( $gzd_product->get_default_delivery_time_slug() ) {
			$gzd_product->set_default_delivery_time_slug( '' );
		}

        $country_specific_delivery_times = $gzd_product->get_country_specific_delivery_times();

        $posted        = isset( $data['country_specific_delivery_times'] ) ? wc_clean( (array) $data['country_specific_delivery_times'] ) : array();
        $new_terms     = isset( $data['new_country_specific_delivery_times_terms'] ) ? wc_clean( (array) $data['new_country_specific_delivery_times_terms'] ) : array();
        $new_countries = isset( $data['new_country_specific_delivery_times_countries'] ) ? wc_clean( (array) $data['new_country_specific_delivery_times_countries'] ) : array();

        foreach( $country_specific_delivery_times as $country => $slug ) {
            // Maybe delete missing country-specific delivery times (e.g. removed by the user)
            if ( ! isset( $posted[ $country ] ) ) {
                unset( $country_specific_delivery_times[ $country ] );
            } else {
                if ( ! empty( $posted[ $country ] ) ) {
                    if ( $slug = wc_gzd_get_valid_product_delivery_time_slugs( $posted[ $country ] ) ) {
                        $country_specific_delivery_times[ $country ] = $slug;
                    } else {
                        unset( $country_specific_delivery_times[ $country ] );
                    }
                } else {
                    unset( $country_specific_delivery_times[ $country ] );
                }
            }
        }

		/**
		 * Allow posting/adding new pairs via country_specific_delivery_times too (e.g. REST API)
		 */
        $country_specific_delivery_times = array_replace_recursive( $country_specific_delivery_times, $posted );

		/**
		 * New countries added via separate request field (e.g. edit product page)
		 */
        foreach( $new_countries as $key => $country ) {
            if ( empty( $country ) ) {
                continue;
            }

            if ( ! array_key_exists( $country, $country_specific_delivery_times ) && isset( $new_terms[ $key ] ) ) {
                if ( $slug = wc_gzd_get_valid_product_delivery_time_slugs( $new_terms[ $key ] ) ) {
                    $country_specific_delivery_times[ $country ] = $slug;
                }
            }
        }

        foreach( $country_specific_delivery_times as $country => $slug ) {
            if ( ! wc_gzd_get_valid_product_delivery_time_slugs( $slug ) || ! self::is_available_delivery_time_country( $country ) ) {
                unset( $country_specific_delivery_times[ $country ] );
            }
        }

        $gzd_product->set_country_specific_delivery_times( $country_specific_delivery_times );
	}

	/**
	 * @param WC_Product $product
	 * @param $data
	 * @param false $is_variation
	 */
	public static function save_product_data( &$product, $data, $is_variation = false ) {
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
		$term_selects      = array( '_sale_price_label', '_sale_price_regular_label', '_deposit_type' );

		foreach ( $term_selects as $term_select ) {
			if ( isset( $data[ $term_select ] ) ) {
				$setter = "set{$term_select}";

				if ( is_callable( array( $gzd_product, $setter ) ) ) {
					if ( empty( $data[ $term_select ] ) || in_array( $data[ $term_select ], array( 'none', '-1' ) ) ) {
						$gzd_product->$setter( '' );
					} else {
						$gzd_product->$setter( wc_clean( $data[ $term_select ] ) );
					}
				}
			}
		}

		if ( isset( $data['_mini_desc'] ) ) {
			$gzd_product->set_mini_desc( $data['_mini_desc'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_mini_desc'] ) );
		}

		if ( isset( $data['_defect_description'] ) ) {
			$gzd_product->set_defect_description( $data['_defect_description'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_defect_description'] ) );
		}

		if ( isset( $data['_nutrient_ids'] ) ) {
			$gzd_product->set_nutrient_ids( (array) wc_clean( $data['_nutrient_ids'] ) );
		}

		if ( isset( $data['_nutrient_reference_value'] ) ) {
			$gzd_product->set_nutrient_reference_value( sanitize_key( wc_clean( $data['_nutrient_reference_value'] ) ) );
		}

		if ( isset( $data['_allergen_ids'] ) ) {
			$gzd_product->set_allergen_ids( array_map( 'absint', (array) wc_clean( $data['_allergen_ids'] ) ) );
		}

		if ( isset( $data['_ingredients'] ) ) {
			$gzd_product->set_ingredients( $data['_ingredients'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_ingredients'] ) );
		}

		if ( isset( $data['_nutri_score'] ) ) {
			$gzd_product->set_nutri_score( wc_clean( $data['_nutri_score'] ) );
		}

		if ( isset( $data['_alcohol_content'] ) ) {
			$gzd_product->set_alcohol_content( wc_clean( $data['_alcohol_content'] ) );
		}

		if ( isset( $data['_drained_weight'] ) ) {
			$gzd_product->set_drained_weight( wc_clean( $data['_drained_weight'] ) );
		}

		if ( isset( $data['_net_filling_quantity'] ) ) {
			$gzd_product->set_net_filling_quantity( wc_clean( $data['_net_filling_quantity'] ) );
		}

		if ( isset( $data['_food_distributor'] ) ) {
			$gzd_product->set_food_distributor( $data['_food_distributor'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_food_distributor'] ) );
		}

		if ( isset( $data['_food_description'] ) ) {
			$gzd_product->set_food_description( $data['_food_description'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_food_description'] ) );
		}

		if ( isset( $data['_food_place_of_origin'] ) ) {
			$gzd_product->set_food_place_of_origin( $data['_food_place_of_origin'] === '' ? '' : wc_gzd_sanitize_html_text_field( $data['_food_place_of_origin'] ) );
		}

        $warranty_attachment_id = isset( $data['_warranty_attachment_id'] ) ? absint( $data['_warranty_attachment_id'] ) : 0;

		if ( ! empty( $warranty_attachment_id ) && ( $warranty_post = get_post( $warranty_attachment_id ) ) ) {
			if ( $file_url = wp_get_attachment_url( $warranty_attachment_id ) ) {
				$filetype = wp_check_filetype( $file_url );

				if ( 'application/pdf' === $filetype['type'] ) {
					$gzd_product->set_warranty_attachment_id( $warranty_attachment_id );
				} else {
					$gzd_product->set_warranty_attachment_id( 0 );
				}
			}
		} else {
			$gzd_product->set_warranty_attachment_id( 0 );
		}

		if ( isset( $data['_min_age'] ) && array_key_exists( (int) $data['_min_age'], wc_gzd_get_age_verification_min_ages() ) ) {
			$gzd_product->set_min_age( absint( $data['_min_age'] ) );
		} else {
			$gzd_product->set_min_age( '' );
		}

		self::save_delivery_times( $gzd_product, $data );

        if ( isset( $data['_deposit_quantity'] ) ) {
	        $gzd_product->set_deposit_quantity( absint( $data['_deposit_quantity'] ) );
        }

		// Free shipping
		$gzd_product->set_free_shipping( isset( $data['_free_shipping'] ) ? 'yes' : 'no' );

		// Is a service?
		$gzd_product->set_service( isset( $data['_service'] ) ? 'yes' : 'no' );

		// Is a used good?
		$gzd_product->set_used_good( isset( $data['_used_good'] ) ? 'yes' : 'no' );

		// Is a defective copy?
		$gzd_product->set_defective_copy( isset( $data['_defective_copy'] ) ? 'yes' : 'no' );

		// Is food?
		$gzd_product->set_is_food( isset( $data['_is_food'] ) ? 'yes' : 'no' );

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
			$gzd_product->set_defect_description( '' );
		}

		$gzd_product->set_gzd_version( WC_GERMANIZED_VERSION );

		if ( $data['save'] ) {
			$product->save();
		}
	}
}

WC_Germanized_Meta_Box_Product_Data::instance();