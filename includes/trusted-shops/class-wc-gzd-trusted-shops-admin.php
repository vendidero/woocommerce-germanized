<?php

class WC_GZD_Trusted_Shops_Admin {

	protected static $_instance = null;

	public $base = null;

	public $script_prefix = '';

	public static function instance( $base ) {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $base );

		return self::$_instance;
	}

	private function __construct( $base ) {
		
		$this->base          = $base;
		$this->script_prefix = str_replace( '_', '-', $this->base->option_prefix );

		// Register Section
		add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'register_section' ), 1 );
		add_filter( 'woocommerce_gzd_get_settings_trusted_shops', array( $this, 'get_settings' ) );
		add_filter( 'woocommerce_gzd_get_sidebar_trusted_shops', array( $this, 'get_sidebar' ) );
		add_action( 'woocommerce_gzd_before_save_section_trusted_shops', array( $this, 'before_save' ), 0, 1 );
		add_action( 'woocommerce_gzd_after_save_section_trusted_shops', array( $this, 'after_save' ), 0, 1 );

		add_action( 'wc_germanized_settings_section_before_trusted_shops', array( $this, 'wpml_notice' ) );

		// Default settings
		add_filter( 'woocommerce_gzd_installation_default_settings', array( $this, 'set_installation_settings' ), 10, 1 );

		// After Install
		add_action( 'woocommerce_gzd_installed', array( $this, 'create_attribute' ) );

		// Review Collector
		add_action( 'wc_germanized_settings_section_after_trusted_shops', array( $this, 'review_collector_export' ), 0 );
		add_action( 'admin_init', array( $this, 'review_collector_export_csv' ) );

		add_action( 'woocommerce_gzd_load_trusted_shops_script', array( $this, 'load_scripts' ) );

        add_action( 'woocommerce_product_options_general_product_data', array( $this, 'output_fields' ) );
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'output_variation_fields' ), 20, 3 );
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ) , 0, 2 );

        if ( ! wc_ts_woocommerce_supports_crud() ) {
            add_action( 'woocommerce_process_product_meta', array( $this, 'save_fields' ), 20, 2 );
        } else {
            add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ), 10, 1 );
        }
	}

	public function wpml_notice() {
	    if ( $this->base->is_multi_language_setup() ) {
	        $is_default_language = false;
	        $compatibility       = $this->base->get_multi_language_compatibility();
            $default_language    = strtoupper( $compatibility->get_default_language() );
            $current_language    = strtoupper( $compatibility->get_current_language() );

            if ( $current_language == $default_language ) {
                $is_default_language = true;
            }

	        include_once( 'admin/views/html-wpml-notice.php' );
        }
    }

	public function output_variation_fields( $loop, $variation_data, $variation ) {
		$_product         = wc_get_product( $variation );
		$_parent          = wc_get_product( wc_ts_get_crud_data( $_product, 'parent' ) );
		$variation_id     = wc_ts_get_crud_data( $_product, 'id' );
		$variation_meta   = get_post_meta( $variation_id );
		$variation_data	  = array();

		$variation_fields = array(
			'_ts_gtin' 		        => '',
			'_ts_mpn' 				=> '',
		);

		foreach ( $variation_fields as $field => $value ) {
			$variation_data[ $field ] = isset( $variation_meta[ $field ][0] ) ? maybe_unserialize( $variation_meta[ $field ][0] ) : $value;
		}

		?>

        <div class="variable_gzd_ts_labels">
            <p class="form-row form-row-first">
                <label for="variable_ts_gtin"><?php echo _x( 'GTIN', 'trusted-shops', 'woocommerce-germanized' );?> <?php echo wc_ts_help_tip( _x( 'ID that allows your products to be identified worldwide. If you want to display your Trusted Shops Product Reviews in Google Shopping and paid Google adverts, Google needs the GTIN.', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
                <input class="input-text" type="text" name="variable_ts_gtin[<?php echo $loop; ?>]" value="<?php echo ( ! empty( $variation_data[ '_ts_gtin' ] ) ? esc_attr( $variation_data[ '_ts_gtin' ] ) : '' );?>" placeholder="<?php echo esc_attr( wc_ts_get_crud_data( $_parent, '_ts_gtin' ) ); ?>" />
            </p>
            <p class="form-row form-row-last">
                <label for="variable_ts_mpn"><?php echo _x( 'MPN', 'trusted-shops', 'woocommerce-germanized' );?> <?php echo wc_ts_help_tip( _x( 'If you don\'t have a GTIN for your products, you can pass the brand name and the MPN on to Google to use the Trusted Shops Google Integration.', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
                <input class="input-text" type="text" name="variable_ts_mpn[<?php echo $loop; ?>]" value="<?php echo ( ! empty( $variation_data[ '_ts_mpn' ] ) ? esc_attr( $variation_data[ '_ts_mpn' ] ) : '' );?>" placeholder="<?php echo esc_attr( wc_ts_get_crud_data( $_parent, '_ts_mpn' ) ); ?>" />
            </p>
        </div>

        <?php
    }

    public function save_variation_fields( $variation_id, $i ) {
	    $data = array(
		    '_ts_gtin' => '',
		    '_ts_mpn'  => '',
	    );

	    foreach ( $data as $k => $v ) {
		    $data_k     = 'variable' . ( substr( $k, 0, 1) === '_' ? '' : '_' ) . $k;
		    $data[ $k ] = ( isset( $_POST[ $data_k ][$i] ) ? $_POST[ $data_k ][$i] : null );
	    }

	    $product        = wc_get_product( $variation_id );

	    foreach( $data as $key => $value ) {
	        $product = wc_ts_set_crud_data( $product, $key, $value );
        }

	    if ( wc_ts_woocommerce_supports_crud() ) {
		    $product->save();
	    }
    }

	public function output_fields() {
		echo '<div class="options_group show_if_simple show_if_external show_if_variable">';

		woocommerce_wp_text_input( array( 'id' => '_ts_gtin', 'label' => _x( 'GTIN', 'trusted-shops', 'woocommerce-germanized' ), 'data_type' => 'text', 'desc_tip' => true, 'description' => _x( 'ID that allows your products to be identified worldwide. If you want to display your Trusted Shops Product Reviews in Google Shopping and paid Google adverts, Google needs the GTIN.', 'trusted-shops', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_ts_mpn', 'label' => _x( 'MPN', 'trusted-shops', 'woocommerce-germanized' ), 'data_type' => 'text', 'desc_tip' => true, 'description' => _x( 'If you don\'t have a GTIN for your products, you can pass the brand name and the MPN on to Google to use the Trusted Shops Google Integration.', 'trusted-shops', 'woocommerce-germanized' ) ) );

		echo '</div>';
    }

    public function save_fields( $product ) {
	    if ( is_numeric( $product ) )
		    $product = wc_get_product( $product );

	    if ( isset( $_POST['_ts_gtin'] ) ) {
	        $product = wc_ts_set_crud_data( $product, '_ts_gtin', wc_clean( $_POST['_ts_gtin'] ) );
        }

        if ( isset( $_POST['_ts_mpn'] ) ) {
	        $product = wc_ts_set_crud_data( $product, '_ts_mpn', wc_clean( $_POST['_ts_mpn'] ) );
        }

	    if ( wc_ts_woocommerce_supports_crud() ) {
		    $product->save();
	    }
    }

	public function create_attribute() {
		$attributes = array(
			'brand' => _x( 'Brand', 'trusted-shops', 'woocommerce-germanized' ),
		);

		// Create the taxonomy
		global $wpdb;
		delete_transient( 'wc_attribute_taxonomies' );

		foreach ( $attributes as $attribute_name => $title ) {
			if ( ! in_array( 'pa_' . $attribute_name, wc_get_attribute_taxonomy_names() ) ) {
				$attribute = array(
					'attribute_label'   => $title,
					'attribute_name'    => $attribute_name,
					'attribute_type'    => 'text',
					'attribute_orderby' => 'menu_order',
					'attribute_public'  => 0
				);
			
				$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
				delete_transient( 'wc_attribute_taxonomies' );
			}
		}
	}

	public function set_installation_settings( $settings ) {
		return array_merge( $settings, $this->get_settings() );
	}

	public function load_scripts() {
		$screen            = get_current_screen();
		$suffix            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path       = $this->base->plugin->plugin_url() . '/assets/';
		$admin_script_path = $assets_path . 'js/admin/';

		wp_register_style( 'woocommerce-' . $this->script_prefix . 'trusted-shops-admin', $assets_path . 'css/woocommerce-' . $this->script_prefix . 'trusted-shops-admin' . $suffix . '.css', false, $this->base->plugin->version );
		wp_enqueue_style( 'woocommerce-' . $this->script_prefix . 'trusted-shops-admin' );

		wp_register_script( 'wc-' . $this->script_prefix . 'admin-trusted-shops', $admin_script_path . 'trusted-shops' . $suffix . '.js', array( 'jquery', 'woocommerce_settings' ), $this->base->plugin->version, true );
		wp_localize_script( 'wc-' . $this->script_prefix . 'admin-trusted-shops', 'trusted_shops_params', array(
			'option_prefix'           => $this->base->option_prefix,
			'script_prefix'           => $this->script_prefix,
            'i18n_error_mandatory'    => _x( 'This field is mandatory', 'trusted-shops', 'woocommerce-germanized' ),
		) );

		wp_enqueue_script( 'wc-' . $this->script_prefix . 'admin-trusted-shops' );
	}

	public function register_section( $sections ) {
		$sections['trusted_shops'] = _x( 'Trusted Shops Options', 'trusted-shops', 'woocommerce-germanized' );

		return $sections;
	}

	public function get_font_families() {
	    return array(
            'Arial'        => _x( 'Arial', 'trusted-shops', 'woocommerce-germanized' ),
            'Geneva'       => _x( 'Geneva', 'trusted-shops', 'woocommerce-germanized' ),
            'Georgia'      => _x( 'Georgia', 'trusted-shops', 'woocommerce-germanized' ),
            'Helvetica'    => _x( 'Helvetica', 'trusted-shops', 'woocommerce-germanized' ),
            'Sans-serif'   => _x( 'Sans-serif', 'trusted-shops', 'woocommerce-germanized' ),
            'Serif'        => _x( 'Serif', 'trusted-shops', 'woocommerce-germanized' ),
            'Trebuchet MS' => _x( 'Trebuchet MS', 'trusted-shops', 'woocommerce-germanized' ),
            'Verdana'      => _x( 'Verdana', 'trusted-shops', 'woocommerce-germanized' ),
        );
    }

    public function get_order_statuses() {
	    return wc_get_order_statuses();
    }

    public function get_translatable_settings() {
	    $translatable = array();

	    foreach( $this->get_settings_array() as $setting ) {
	        if ( isset( $setting['id'] ) && ! in_array( $setting['type'], array( 'title', 'sectionend' ) )  ) {
	            $translatable[ $setting['id'] ] = '';
            }
        }

        return $translatable;
    }

    protected function get_settings_array( $defaults = array() ) {
	    $settings = array(
            array(
                'title'             => _x( 'Trusted Shops Integration', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => sprintf( _x( 'Do you need help with integrating your Trustbadge? %s', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . $this->get_trusted_url( 'https://support.trustedshops.com/en/apps/woocommerce' ) . '" class="button button-secondary" target="_blank">' . _x( 'To the step-by-step instructions', 'trusted-shops', 'woocommerce-germanized' ) .'</a>' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_options'
            ),

            array(
                'title'             => _x( 'Trusted Shops ID', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => _x( 'The Trusted Shops ID is a unique identifier for your shop. You can find your Trusted Shops ID in your My Trusted Shops account.', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => true,
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_id',
                'type'              => 'text',
                'custom_attributes' => array( 'data-sidebar' => 'wc-ts-sidebar-default' ),
                'css'               => 'min-width:300px;',
            ),

            array(
                'title'             => _x( 'Edit Mode', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_integration_mode',
                'desc_tip'          => _x( 'The advanced configuration is for users with programming skills. Here you can create even more individual settings.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'select',
                'class'             => 'chosen_select',
                'options'           => array(
                    'standard'      => _x( 'Standard configuration', 'trusted-shops', 'woocommerce-germanized' ),
                    'expert'        => _x( 'Advanced configuration', 'trusted-shops', 'woocommerce-germanized' ),
                ),
                'default'           => 'standard',
            ),

            array( 'type' => 'sectionend', 'id' => 'trusted_shops_options' ),

            array(
                'title'             => _x( 'Configure your Trustbadge', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_badge_options',
            ),

            array(
                'title'             => _x( 'Display Trustbadge', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_enable',
                'desc_tip'          => _x( 'Display the Trustbadge on all the pages of your shop.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'gzd_toggle',
                'custom_attributes' => array( 'data-sidebar' => 'wc-ts-sidebar-trustbadge' ),
                'default'           => 'no'
            ),

            array(
                'title'             => _x( 'Variant', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_variant',
                'desc_tip'          => _x( 'You can display your Trustbadge with or without Review Stars.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'select',
                'class'             => 'chosen_select',
                'options'           => array(
                    'standard'      => _x( 'Display Trustbadge with review stars', 'trusted-shops', 'woocommerce-germanized' ),
                    'hide_reviews'  => _x( 'Display Trustbadge without review stars', 'trusted-shops', 'woocommerce-germanized' ),
                ),
                'default'           => 'standard'
            ),

            array(
                'title'             => _x( 'Vertical Offset', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Choose the distance that the Trustbadge will appear from the bottom-right corner of the screen.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_y',
                'type'              => 'number',
                'desc'              => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
                'default'           => '0',
                'custom_attributes' => array(
                    'step'              => '1',
                    'min'               => 0,
                    'data-validate'     => 'integer',
                    'data-validate-msg' => sprintf( _x( 'Please choose a non-negative number (at least %d)', 'trusted-shops', 'woocommerce-germanized' ), 0 ),
                ),
                'css'               => 'max-width:60px;',
            ),

            array(
                'title'             => _x( 'Trustbadge code', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_code',
                'type'              => 'textarea',
                'desc_tip'          => true,
                'desc'              => _x( 'The advanced configuration is for users with programming skills. Here you can create even more individual settings.', 'trusted-shops', 'woocommerce-germanized' ),
                'css'               => 'width: 100%; min-height: 150px',
                'default'           => '',
            ),

            array( 'type' => 'sectionend', 'id' => 'trusted_shops_badge_options' ),

            array(
                'title'             => _x( 'Configure your Shop Reviews', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_review_sticker_options'
            ),

            array(
                'title'             => _x( 'Display Shop Review Sticker', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'To display the Shop Review Sticker, you have to assign the widget "Trusted Shops Review Sticker".', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => sprintf( _x( 'Assign widget %s', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . admin_url( 'widgets.php' ) . '" target="_blank">' . _x( 'here', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_enable',
                'type'              => 'gzd_toggle',
                'custom_attributes' => array( 'data-sidebar' => 'wc-ts-sidebar-shop-reviews' ),
                'default'           => 'no'
            ),

            array(
                'title'             => _x( 'Background color', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Choose the background color for your Review Sticker.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_bg_color',
                'type'              => 'color',
                'default'           => '#FFDC0F',
            ),

            array(
                'title'             => _x( 'Font', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_font',
                'desc_tip'          => _x( 'Choose the font for your Review Sticker.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'select',
                'class'             => 'chosen_select',
                'default'           => 'arial'
            ),

            array(
                'title'             => _x( 'Number of reviews displayed', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Display x alternating Shop Reviews in your Shop Review Sticker. You can display between 1 and 5 alternating Shop Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_number',
                'type'              => 'number',
                'desc'              => _x( 'Show x alternating reviews', 'trusted-shops', 'woocommerce-germanized' ),
                'default'           => '5',
                'custom_attributes' => array(
                    'step'              => '1',
                    'min'               => 1,
                    'max'               => 5,
                    'data-validate'     => 'integer',
                    'data-validate-msg' => sprintf( _x( 'Please choose a non-negative number between %d and %d', 'trusted-shops', 'woocommerce-germanized' ),1, 5 ),
                ),
                'css'               => 'max-width:60px;',
            ),

            array(
                'title'             => _x( 'Minimum rating displayed', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Only show Shop Reviews with a minimum rating of x stars. ', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_better_than',
                'type'              => 'number',
                'desc'              => _x( 'Star(s)', 'trusted-shops', 'woocommerce-germanized' ),
                'default'           => '3',
                'custom_attributes' => array(
                    'step'              => '1',
                    'min'               => 1,
                    'max'               => 5,
                    'data-validate'     => 'integer',
                    'data-validate-msg' => sprintf( _x( 'Please choose a non-negative number between %d and %d', 'trusted-shops', 'woocommerce-germanized' ), 1, 5 ),
                ),
                'css'               => 'max-width:60px;',
            ),

            array(
                'title'             => _x( 'Sticker code', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_code',
                'type'              => 'textarea',
                'desc_tip'          => true,
                'desc'              => _x( 'The advanced configuration is for users with programming skills. Here you can perform even more individual settings.', 'trusted-shops', 'woocommerce-germanized' ),
                'css'               => 'width: 100%; min-height: 150px',
                'default'           => '',
            ),

            array(
                'title'             => _x( 'Google Organic Search', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Activate this option to give Google the opportunity to show your Shop Reviews in Google organic search results.', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => _x( 'By activating this option, rich snippets will be integrated in the selected pages so your shop review stars may be displayed in Google organic search results. If you use Product Reviews and already activated rich snippets  in expert mode, we recommend integrating rich snippets for Shop Reviews on category pages only.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_enable',
                'type'              => 'gzd_toggle',
                'default'           => 'no'
            ),

            array(
                'title' 	        => _x( 'Activate rich snippets on', 'trusted-shops', 'woocommerce-germanized' ),
                'desc' 		        => _x( 'category pages', 'trusted-shops', 'woocommerce-germanized' ),
                'id' 	            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_category',
                'type' 		        => 'checkbox',
                'default'	        => 'no',
                'checkboxgroup'	    => 'start',
            ),

            array(
                'desc' 		        => _x( 'product pages', 'trusted-shops', 'woocommerce-germanized' ),
                'id' 	            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_product',
                'type' 		        => 'checkbox',
                'default'	        => 'no',
                'checkboxgroup'	    => '',
            ),

            array(
                'desc' 		        => _x( 'homepage (not recommended)', 'trusted-shops', 'woocommerce-germanized' ),
                'id' 	            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_home',
                'type' 		        => 'checkbox',
                'default'	        => 'no',
                'checkboxgroup'	    => 'end',
            ),

            array(
                'title'             => _x( 'Rich snippets code', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_code',
                'type'              => 'textarea',
                'desc_tip'          => true,
                'desc'              => _x( 'The advanced configuration is for users with programming skills. Here you can create even more individual settings.', 'trusted-shops', 'woocommerce-germanized' ),
                'css'               => 'width: 100%; min-height: 150px',
                'default'           => '',
            ),

            array( 'type' => 'sectionend', 'id' => 'trusted_shops_review_sticker_options' ),

            array(
                'title'             => _x( 'Configure your Product Reviews ', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => sprintf( _x( 'To use Product Reviews, activate them in your %s first.', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . $this->get_trusted_url( 'https://www.trustedshops.com/uk/shop/login.html', array( 'lang_mapping' => array( 'en' => 'uk' ) ) ) . '" target="_blank">' . _x( 'Trusted Shops package', 'trusted-shops', 'woocommerce-germanized' ) .'</a>' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_reviews_options'
            ),

            array(
                'title'             => _x( 'Collect Product Reviews', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => _x( '(WooCommerce Product Reviews will be replaced)', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Show Product Reviews on the product page in a separate tab, just as shown on the picture on the right.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_reviews_enable',
                'type'              => 'gzd_toggle',
                'custom_attributes' => array( 'data-sidebar' => 'wc-ts-sidebar-product-reviews' ),
                'default'           => 'no'
            ),

            array(
                'title'             => _x( 'Reviews', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'You can choose a name for the tab with your Product Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => _x( 'Show Product Reviews on the product detail page in an additional tab.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_enable',
                'type'              => 'checkbox',
                'default'           => 'no'
            ),

            array(
                'title'             => _x( 'Name of Product Reviews tab', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'You can choose a name for the tab with your Product Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_tab_text',
                'type'              => 'text',
                'default'           => _x( 'Product reviews', 'trusted-shops', 'woocommerce-germanized' ),
            ),

            array(
                'title'             => _x( 'Border color', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Set the color for the frame around your Product Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_border_color',
                'type'              => 'color',
                'default'           => '#FFDC0F',
            ),

            array(
                'title'             => _x( 'Background color', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Set the background color for your Product Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_bg_color',
                'type'              => 'color',
                'default'           => '#FFFFFF',
            ),

            array(
                'title'             => _x( 'Star color', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Set the color for the Product Review stars in your Product Reviews tab.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_star_color',
                'type'              => 'color',
                'default'           => '#C0C0C0',
            ),

            array(
                'title'             => _x( 'Star size', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_star_size',
                'type'              => 'number',
                'default'           => '15',
                'desc'              => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Set the size for the Product Review stars in your Product Reviews tab.', 'trusted-shops', 'woocommerce-germanized' ),
                'css'               => 'max-width:60px;',
                'custom_attributes' => array(
                    'step'              => '1',
                    'min'               => 0,
                    'data-validate'     => 'integer',
                    'data-validate-msg' => sprintf( _x( 'Please choose a non-negative number (at least %d)', 'trusted-shops', 'woocommerce-germanized' ), 0 ),
                ),
            ),

            array(
                'title'             => _x( 'Product Sticker Code', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_code',
                'desc_tip'          => _x( 'The advanced configuration is for users with programming skills. Here you can perform even more individual settings.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'textarea',
                'css'               => 'width: 100%; min-height: 150px',
                'default'           => '',
            ),

            array(
                'title'             => _x( 'jQuerySelector', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Please choose where your Product Reviews shall be displayed on the Product detail page.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_selector',
                'type'              => 'text',
                'default'           => '#ts_product_sticker',
            ),

            array(
                'title'             => _x( 'Rating stars', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => _x( 'Show star ratings on the product detail page below your product name.', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Display Product Review stars on product pages below the product name, just as shown in the picture on the right.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_enable',
                'type'              => 'checkbox',
                'default'           => 'no'
            ),

            array(
                'title'             => _x( 'Star color', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_star_color',
                'desc_tip'          => _x( 'Set the color for the review stars, that are displayed on the product page, below your product name.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'color',
                'default'           => '#FFDC0F',
            ),

            array(
                'title'             => _x( 'Star size', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_star_size',
                'desc_tip'          => _x( 'Set the size for the review stars that are displayed on the product page, below your product name.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'number',
                'default'           => '14',
                'desc'              => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
                'css'               => 'max-width:60px;',
                'custom_attributes' => array(
                    'step'              => '1',
                    'min'               => 0,
                    'data-validate'     => 'integer',
                    'data-validate-msg' => sprintf( _x( 'Please choose a non-negative number (at least %d)', 'trusted-shops', 'woocommerce-germanized' ), 0 ),
                ),
            ),

            array(
                'title'             => _x( 'Font size', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_font_size',
                'desc_tip'          => _x( 'Set the font size for the text that goes with your review stars.', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'number',
                'desc'              => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
                'default'           => '12',
                'css'               => 'max-width:60px;',
                'custom_attributes' => array(
                    'step'              => '1',
                    'min'               => 0,
                    'data-validate'     => 'integer',
                    'data-validate-msg' => sprintf( _x( 'Please choose a non-negative number (at least %d)', 'trusted-shops', 'woocommerce-germanized' ), 0 ),
                ),
            ),

            array(
                'title'             => _x( 'Product Review Code', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'The advanced configuration is for users with programming skills. Here you can perform even more individual settings.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_code',
                'type'              => 'textarea',
                'css'               => 'width: 100%; min-height: 150px',
                'default'           => '',
            ),

            array(
                'title'             => _x( 'jQuerySelector', 'trusted-shops', 'woocommerce-germanized' ),
                'desc_tip'          => _x( 'Please choose where your Product Review Stars shall be displayed on the Product Detail page.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_selector',
                'type'              => 'text',
                'default'           => '#ts_product_widget',
            ),

            array(
                'title'             => _x( 'Brand attribute', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => sprintf( _x( 'Create brand attribute %s', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . admin_url( 'edit.php?post_type=product&page=product_attributes' ) . '" target="_blank">' . _x( 'here', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ),
                'desc_tip'          => _x( 'Brand name of the product. By passing this information on to Google, you improve your chances of having Google identify your products. Assign your brand attribute. If your products don\'t have a GTIN, you can pass on the brand name and the MPN to use Google Integration.', 'trusted-shops', 'woocommerce-germanized' ),
                'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_brand_attribute',
                'css'               => 'min-width:250px;',
                'default'           => 'brand',
                'type'              => 'select',
                'class'             => 'chosen_select_nostd',
                'custom_attributes' => array( 'data-placeholder' => _x( 'None', 'trusted-shops', 'woocommerce-germanized' ) ),
            ),

            array( 'type' => 'sectionend', 'id' => 'trusted_shops_reviews_options' ),
        );

        if ( $this->base->supports( 'reminder' ) ) {

            $settings = array_merge( $settings, array(

                array(
                    'title'             => _x( 'Configure your Review Requests', 'trusted-shops', 'woocommerce-germanized' ),
                    'desc'              => _x( '7 days after an order has been placed, Trusted Shops automatically sends an invite to your customers. If you want to set a different time for sending automatic Review Requests, please activate the option below. If you want to send review requests with legal certainty, you need your customers\' consent to receive Review Requests. You also have to include an option to unsubscribe.', 'trusted-shops', 'woocommerce-germanized' ),
                    'type'              => 'title',
                    'id'                => 'trusted_shops_review_reminder_options',
                ),

                array(
                    'title'             => _x( 'Enable Review Requests', 'trusted-shops', 'woocommerce-germanized' ),
                    'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_enable',
                    'type'              => 'gzd_toggle',
                    'default'           => 'no',
                    'custom_attributes' => array( 'data-sidebar' => 'wc-ts-sidebar-review-reminder' ),
                    'autoload'          => false
                ),

                array(
                    'title'             => _x( 'WooCommerce status', 'trusted-shops', 'woocommerce-germanized' ),
                    'desc_tip'          => _x( 'We recommend choosing the order status that you set when your products have been shipped.', 'trusted-shops', 'woocommerce-germanized' ),
                    'default'           => 'wc-completed',
                    'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_status',
                    'type'              => 'select',
                    'class'             => 'chosen_select',
                ),

                array(
                    'title'             => _x( 'Days until Review Request', 'trusted-shops', 'woocommerce-germanized' ),
                    'desc_tip'          => _x( 'Set the number of days to wait after an order has reached the order status you selected above before having a review request sent to your customers.', 'trusted-shops', 'woocommerce-germanized' ),
                    'default'           => 7,
                    'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_days',
                    'type'              => 'number',
                    'custom_attributes' => array(
                        'step'          => '1',
                        'min'           => 0,
                        'data-validate' => 'integer',
                    ),
                ),

                array(
                    'title'             => _x( 'Permission via checkbox', 'trusted-shops', 'woocommerce-germanized' ),
                    'desc_tip'          => _x( 'If the checkbox is activated, only customers who gave their consent will receive Review Requests.', 'trusted-shops', 'woocommerce-germanized' ),
                    'default'           => '',
                    'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_checkbox',
                    'type'              => 'html',
                    'html'              => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized&section=checkboxes&checkbox_id=review_reminder' ) . '" class="button button-secondary" target="_blank">' . _x( 'Edit checkbox', 'trusted-shops', 'woocommerce-germanized' ) . '</a>',
                ),

                array(
                    'title'             => _x( 'Unsubscribe via link', 'trusted-shops', 'woocommerce-germanized' ),
                    'desc'              => _x( 'Allows the customer to unsubscribe from Review Requests.', 'trusted-shops', 'woocommerce-germanized' ),
                    'default'           => 'yes',
                    'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_opt_out',
                    'type'              => 'checkbox',
                ),

                array( 'type' => 'sectionend', 'id' => 'trusted_shops_review_reminder_options' ),

            ) );
        }

        if ( ! empty( $defaults ) ) {
            foreach( $settings as $key => $setting ) {
                if ( isset( $setting['id'] ) ) {
                    foreach( $defaults as $setting_id => $default ) {
                        if ( $setting_id === $setting['id'] ) {
                            $settings[ $key ] = array_replace_recursive( $setting, $default );
                        }
                    }
                }
            }
        }

        return $settings;
    }

	/**
	 * Get Trusted Shops related Settings for Admin Interface
	 *
	 * @return array
	 */
	public function get_settings() {

		$attributes        = wc_get_attribute_taxonomies();
		$linked_attributes = array();
			
		// Set attributes
		foreach ( $attributes as $attribute ) {
			$linked_attributes[ $attribute->attribute_name ] = $attribute->attribute_label;
		}

		// Add empty option placeholder to allow clearing
        $linked_attributes = array_merge( array( '' => '' ), $linked_attributes );

		$update_settings = array(
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_code' => array(
                'default' => $this->base->get_trustbadge_code( false ),
            ),
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_font' => array(
                'options' => $this->get_font_families(),
            ),
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_code' => array(
                'default' => $this->base->get_review_sticker_code( false ),
            ),
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_code' => array(
                'default' => $this->base->get_rich_snippets_code( false ),
            ),
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_code' => array(
                'default' => $this->base->get_product_sticker_code( false ),
            ),
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_code' => array(
                'default' => $this->base->get_product_widget_code( false ),
            ),
            'woocommerce_' . $this->base->option_prefix . 'trusted_shops_brand_attribute' => array(
                'options' => $linked_attributes,
            ),
        );

		if ( $this->base->supports( 'reminder' ) ) {
		    $update_settings['woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_status'] = array(
                'options' => $this->get_order_statuses(),
            );
		}

        $settings = $this->get_settings_array( $update_settings );

        return $settings;
	}

	public function get_image( $img ) {
	    $language   = $this->base->get_language();
	    $endings    = array( '.jpg', '.png' );
	    $last       = substr( $img, -4 );
	    $ending     = '';

	    if ( in_array( $last, $endings ) ) {
	        $ending = $last;
	        $img    = substr( $img, 0, -4 );
        }

		$new_img    = $img . '_' . $language . $ending;

	    return $this->base->plugin->plugin_url() . '/assets/images/ts/' . $new_img;
    }

	public function get_sidebar() {
		ob_start();
		?>
			<div class="wc-gzd-admin-settings-sidebar wc-gzd-admin-settings-sidebar-trusted-shops">

                <div class="wc-ts-sidebar wc-ts-sidebar-active" id="wc-ts-sidebar-default">
                    <h3><?php echo _x( 'How does Trusted Shops make your shop better?', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
                    <a href="<?php echo $this->get_signup_url(); ?>" target="_blank"><img style="width: 100%; height: auto" src="<?php echo $this->get_image( 'ts.png' ); ?>" /></a>
                    <a class="button button-primary" href="<?php echo $this->get_signup_url(); ?>" target="_blank"><?php echo _x( 'Get your account', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
                </div>

                <div class="wc-ts-sidebar wc-ts-sidebar-flex" id="wc-ts-sidebar-trustbadge">
                    <div class="wc-ts-sidebar-left wc-ts-sidebar-container">
                        <img src="<?php echo $this->get_image( 'ts_trustbadge_trustmark_reviews.png' ); ?>" />
                        <span class="wc-ts-sidebar-desc"><?php echo _x( 'Display Trustbadge with review stars', 'trusted-shops', 'woocommerce-germanized' ); ?></span>
                    </div>
                    <div class="wc-ts-sidebar-right wc-ts-sidebar-container">
                        <img src="<?php echo $this->get_image( 'ts_trustbadge_trustmark-only.png' ); ?>" />
                        <span class="wc-ts-sidebar-desc"><?php echo _x( 'Display Trustbadge without review stars', 'trusted-shops', 'woocommerce-germanized' ); ?></span>
                    </div>
                </div>

                <div class="wc-ts-sidebar" id="wc-ts-sidebar-shop-reviews">
                    <img style="width: 100%; height: auto" src="<?php echo $this->get_image( 'ts_shop_review_sticker.jpg' ); ?>" />
                </div>

                <div class="wc-ts-sidebar" id="wc-ts-sidebar-product-reviews">
                    <img style="width: 100%; height: auto" src="<?php echo $this->get_image( 'ts_product_reviews.jpg' ); ?>" />
                    <span class="wc-ts-sidebar-desc"><?php echo _x( 'Product Reviews on the product detail page in an additional tab', 'trusted-shops', 'woocommerce-germanized' ); ?></span>

                    <img style="width: 100%; height: auto" src="<?php echo $this->get_image( 'ts_woo.jpg' ); ?>" />
                    <span class="wc-ts-sidebar-desc"><?php echo _x( 'Show Star-Ratings on the product detail page below your product name', 'trusted-shops', 'woocommerce-germanized' ); ?></span>
                </div>

                <div class="wc-ts-sidebar" id="wc-ts-sidebar-review-reminder">
                    <p><?php echo _x( 'Please note: If you want to send review requests through WooCommerce, you should deactivate automated review requests through Trusted Shops. To do so, please go to your My Trusted Shops account. Log in and go to Reviews >  Settings and deactivate "Collect reviews automatically"', 'trusted-shops', 'woocommerce-germanized' ); ?></p>
                    <a class="button button-secondary" href="<?php echo $this->get_trusted_url( 'https://www.trustedshops.com/tsb2b/sa/ratings/shopRatingWidgetSettings.seam' ); ?>" target="_blank"><?php echo _x( 'To your My Trusted Shops account', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
                </div>

                <div class="wc-ts-sidebar" id="wc-ts-sidebar-review-collector">
                    <p><?php echo _x( 'Export your customer information here and upload it in the Trusted Shops Review Collector. To do so go to your My Trusted Shops account. Log in and go to Reviews > Shop Reviews > Review Collector', 'trusted-shops', 'woocommerce-germanized' ); ?></p>
                    <a class="button button-secondary" href="<?php echo $this->get_trusted_url( 'https://www.trustedshops.com/tsb2b/sa/ratings/reviewCollector/reviewCollector.seam' ); ?>" target="_blank"><?php echo _x( 'To the Trusted Shops Review Collector', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
                </div>
			</div>
		<?php
		
		$html = ob_get_clean();
		return $html;
	}

	public function before_save( $settings ) {
		// Update reviews if new ts id has been inserted
		if ( isset( $_POST['woocommerce_' . $this->base->option_prefix . 'trusted_shops_id'] ) && $_POST['woocommerce_' . $this->base->option_prefix . 'trusted_shops_id'] != $this->base->id ) {
			update_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews', 1 );
		}
	}

	public function after_save( $settings ) {
		$this->base->refresh();

		if ( get_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_integration_mode' ) === 'standard' ) {
		    // Delete code snippets
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_code' );
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_code' );
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_code' );
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_code' );
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_code' );
		}

		// Disable Reviews if Trusted Shops review collection has been enabled
		if ( get_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_reviews_enable' ) === 'yes' ) {
			update_option( 'woocommerce_enable_review_rating', 'no' );
		}
		
		if ( get_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews' ) ) {
			$this->base->get_dependency( 'schedule' )->update_reviews();
		}
		
		delete_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews' );
	}

	public function review_collector_export_csv() {
	    if ( ! current_user_can( 'manage_woocommerce' ) )
	        return;
		
		if ( ! isset( $_GET['action'] ) || $_GET['action'] != 'wc_' . $this->base->option_prefix . 'trusted-shops-export' || ( isset( $_GET['action'] ) && $_GET['action'] == 'wc_' . $this->base->option_prefix . 'trusted-shops-export' && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc_' . $this->base->option_prefix . 'trusted-shops-export' ) ) )
			return;
		
		$interval_d   = ( ( isset( $_GET['interval'] ) && ! empty( $_GET['interval'] ) ) ? absint( $_GET['interval'] ) : 30 );
		$days_to_send = ( ( isset( $_GET['days'] ) && ! empty( $_GET['days'] ) ) ? absint( $_GET['days'] ) : 5 );
        $status       = ( ( isset( $_GET['status'] ) && ! empty( $_GET['status'] ) ) ? wc_clean( $_GET['status'] ) : '' );

        if ( wc_ts_woocommerce_supports_crud() ) {
		    include_once( 'class-wc-gzd-trusted-shops-review-exporter.php' );

		    $exporter = new WC_GZD_Trusted_Shops_Review_Exporter();
		    $exporter->set_days_until_send( $days_to_send );
		    $exporter->set_interval_days( $interval_d );

		    if ( ! empty( $status ) ) {
                $exporter->set_statuses( array( $status ) );
            }

            if ( isset( $_GET['lang'] ) && ! empty( $_GET['lang'] ) ) {
                $exporter->set_lang( wc_clean( $_GET['lang'] ) );
            }

		    $exporter->export();
        }
	}

	public function review_collector_export() {

	    $href_org = admin_url();
	    $href_org = add_query_arg( array(
            'action'   => 'wc_' . $this->base->option_prefix . 'trusted-shops-export',
            '_wpnonce' => wp_create_nonce( 'wc_' . $this->base->option_prefix . 'trusted-shops-export' ),
            'lang'     => $this->base->is_multi_language_setup() ? $this->base->get_multi_language_compatibility()->get_current_language() : '',
        ), $href_org );

		if ( ! wc_ts_woocommerce_supports_crud() )
		    return;
		?>
		<h2><?php echo _x( 'Review Collector', 'trusted-shops', 'woocommerce-germanized' ); ?></h2>
        <div id="trusted_shops_review_collector_options-description">
            <p class="description"><?php printf( _x( 'Want to collect reviews for orders that were placed before your Trusted Shops Integration? No problem. Export old orders here and upload them in your %s.', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . $this->get_trusted_url( 'https://www.trustedshops.com/tsb2b/sa/ratings/reviewCollector/reviewCollector.seam' ) . '" target="_blank">' . _x( 'My Trusted Shops account', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ); ?></p>
        </div>
        <table class="form-table">
			<tbody>
				<tr valign="top">
                    <th scope="row" class="titledesc">
						<label for="woocommerce_gzd_trusted_shops_review_collector"><?php echo _x( 'Export orders', 'trusted-shops', 'woocommerce-germanized' ); ?> <?php echo wc_ts_help_tip( _x( 'Export your customer and order information of the last x days and upload them in your My Trusted Shops Account.', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
					</th>
					<td class="forminp forminp-select forminp-review-collector">
						<select data-sidebar="wc-ts-sidebar-review-collector" name="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector" id="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector" class="chosen_select">
							<option value="30"><?php echo _x( '30 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="60"><?php echo _x( '60 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="90"><?php echo _x( '90 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
						</select>
                        <span class="description"><?php printf( _x( 'Upload customer and order information %s.', 'trusted-shops', 'woocommerce-germanized' ), '<a href="' . $this->get_trusted_url( 'https://www.trustedshops.com/tsb2b/sa/ratings/reviewCollector/reviewCollector.seam' ) . '" target="_blank">' . _x( 'here', 'trusted-shops', 'woocommerce-germanized' ) . '</a>' ); ?></span>
                        <div class="trusted-shops-review-collector-wrap">
                            <div class="review-collector-days">
                                <label for="woocommerce_gzd_trusted_shops_review_collector"><?php echo _x( 'Days until reminder mail', 'trusted-shops', 'woocommerce-germanized' ); ?> <?php echo wc_ts_help_tip( _x( 'Set the number of days to wait after the order date before having a Review Request sent to your customers.', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
                                <input type="number" value="5" min="1" data-validate="integer" name="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector_days_to_send" id="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector_days_to_send" />
                            </div>
                            <div class="review-collector-buttons">
                                <a class="button button-secondary" id="wc-gzd-trusted-shops-export" data-href-org="<?php echo esc_url( $href_org ); ?>" href="#"><?php echo _x( 'Start export', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
                            </div>
                        </div>
                    </td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private function get_signup_url( $url = '', $args = array() ) {
	    $args = wp_parse_args( $args, array(
            'params' => false,
        ) );

		$url = empty( $url ) ? $this->base->signup_url : $url;

		return $this->get_trusted_url( $url, $args );
	}

	private function get_trusted_url( $url, $args = array() ) {
        $param_args = $this->base->et_params;
        $args       = wp_parse_args( $args, array(
            'utm_term'     => substr( get_locale(), 0, 2 ),
            'shop_id'      => $this->base->ID,
            'params'       => false,
            'lang_mapping' => array(),
        ) );

	    $current_lang = $this->base->get_language();

	    $base_lang    = isset( $args['lang_mapping']['en'] ) ? $args['lang_mapping']['en'] : 'en';
	    $current_lang = isset( $args['lang_mapping'][ $current_lang ] ) ? $args['lang_mapping'][ $current_lang ] : $current_lang;
	    $url          = str_replace( "/{$base_lang}/", '/' . $current_lang . '/', $url );

		if ( 'gzd_' === $this->base->option_prefix && substr( $url, -11 ) === 'woocommerce' ) {
		    $url = str_replace( 'woocommerce', 'woocommerce_germanized', $url );
        }

		if ( $args['params'] ) {
		    $param_args = array_replace_recursive( $param_args, array(
		        'utm_term' => $args['utm_term'],
                'shop_id' => $args['shop_id'],
            ) );

            return add_query_arg( $param_args, $url );
        } else {
		    return $url;
        }
	}

}