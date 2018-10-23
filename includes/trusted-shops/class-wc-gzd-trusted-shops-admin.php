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

		// Default settings
		add_filter( 'woocommerce_gzd_installation_default_settings', array( $this, 'set_installation_settings' ), 10, 1 );

		// After Install
		add_action( 'woocommerce_gzd_installed', array( $this, 'create_attribute' ) );

		// Review Collector
		add_action( 'wc_germanized_settings_section_after_trusted_shops', array( $this, 'review_collector_export' ), 0 );
		add_action( 'admin_init', array( $this, 'review_collector_export_csv' ) );

		add_action( 'woocommerce_gzd_load_trusted_shops_script', array( $this, 'load_scripts' ) );

		// Add custom fields
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'output_fields' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'output_variation_fields' ), 20, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ) , 0, 2 );

		if ( ! wc_ts_woocommerce_supports_crud() ) {
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_fields' ), 20, 2 );
		} else {
			add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ), 10, 1 );
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
                <label for="variable_ts_gtin"><?php echo _x( 'GTIN', 'trusted-shops', 'woocommerce-germanized' );?> <?php echo wc_ts_help_tip( _x( 'GTIN desc', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
                <input class="input-text" type="text" name="variable_ts_gtin[<?php echo $loop; ?>]" value="<?php echo ( ! empty( $variation_data[ '_ts_gtin' ] ) ? esc_attr( $variation_data[ '_ts_gtin' ] ) : '' );?>" placeholder="<?php echo esc_attr( wc_ts_get_crud_data( $_parent, '_ts_gtin' ) ); ?>" />
            </p>
            <p class="form-row form-row-last">
                <label for="variable_ts_mpn"><?php echo _x( 'MPN', 'trusted-shops', 'woocommerce-germanized' );?> <?php echo wc_ts_help_tip( _x( 'MPN desc', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
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

		woocommerce_wp_text_input( array( 'id' => '_ts_gtin', 'label' => _x( 'GTIN', 'trusted-shops', 'woocommerce-germanized' ), 'data_type' => 'text', 'desc_tip' => true, 'description' => _x( 'GTIN desc', 'trusted-shops', 'woocommerce-germanized' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_ts_mpn', 'label' => _x( 'MPN', 'trusted-shops', 'woocommerce-germanized' ), 'data_type' => 'text', 'desc_tip' => true, 'description' => _x( 'MPN desc', 'trusted-shops', 'woocommerce-germanized' ) ) );

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

		$screen = get_current_screen();
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = $this->base->plugin->plugin_url() . '/assets/';
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
		$sections[ 'trusted_shops' ] = _x( 'Trusted Shops Options', 'trusted-shops', 'woocommerce-germanized' );
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
        return array(
	        'woocommerce_gzd_trusted_shops_id'               => '',
	        'woocommerce_gzd_trusted_shops_integration_mode' => '',
        );
    }

	/**
	 * Get Trusted Shops related Settings for Admin Interface
	 *
	 * @return array
	 */
	public function get_settings() {

		$payment_options   = array( '' => __( 'None', 'woocommerce-germanized' ) ) + $this->base->gateways;
		$attributes        = wc_get_attribute_taxonomies();
		$linked_attributes = array();
			
		// Set attributes
		foreach ( $attributes as $attribute ) {
			$linked_attributes[ $attribute->attribute_name ] = $attribute->attribute_label;
		}

		$options = array(

			array(
                'title'             => _x( 'Trusted Shops Integration', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => sprintf( _x( 'Need help? %s', 'trusted-shops', 'woocommerce-germanized' ), '<a href="https://support.trustedshops.com/de/apps/woocommerce" class="button button-secondary" target="_blank">' . _x( 'Step-by-step tutorial', 'trusted-shops', 'woocommerce-germanized' ) .'</a>' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_options'
            ),

			array(
				'title'             => _x( 'Trusted Shops ID', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => _x( 'The Trusted Shops ID is a unique identifier for your shop. You can find your Trusted Shops ID in your confirmation email after signing up.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_id',
				'type'              => 'text',
				'css'               => 'min-width:300px;',
			),

			array(
				'title'             => _x( 'Mode', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_integration_mode',
				'type'              => 'select',
				'class'             => 'chosen_select',
				'options'           => array(
					'standard'      => _x( 'Standard Mode', 'trusted-shops', 'woocommerce-germanized' ),
					'expert'        => _x( 'Expert Mode', 'trusted-shops', 'woocommerce-germanized' ),
				),
				'default'           => 'standard',
                'custom_attributes' => array( 'data-sidebar' => 'wc-ts-sidebar-reviews' ),
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_options' ),

			array(	'title' => _x( 'Configure your Trustbadge', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_badge_options', 'desc' => sprintf( _x( '<a href="%s" target="_blank">Here</a> you can find a step-by-step introduction.', 'trusted-shops', 'woocommerce-germanized' ), $this->get_trusted_url( $this->base->urls[ 'integration' ] ) ) ),

			array(
				'title'             => _x( 'Show Trustbadge', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_enable',
				'type'              => 'gzd_toggle',
				'default'           => 'no'
			),

			array(
				'title'             => _x( 'Variant', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_variant',
				'type'              => 'select',
				'class'             => 'chosen_select',
				'options'           => array(
					'hide_reviews'  => _x( 'Display Trustbadge without review stars', 'trusted-shops', 'woocommerce-germanized' ),
					'standard'      => _x( 'Display Trustbadge with review stars', 'trusted-shops', 'woocommerce-germanized' ),
					'disable'       => _x( 'Don’t show Trustbadge', 'trusted-shops', 'woocommerce-germanized' ),
				),
				'default'           => 'standard'
			),

			array(
				'title'             => _x( 'Y-Offset', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Adjust the y-axis position of your Trustbadge from 0-250 (pixel) vertically on low right hand side of your shop.', 'trusted-shops', 'woocommerce-germanized' ),
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
				'desc'              => _x( 'Use shortcodes e.g. {variant} to dynamically insert your options. You may of couse replace them with static code.', 'trusted-shops', 'woocommerce-germanized' ),
				'css'               => 'width: 100%; min-height: 150px',
				'default'           => $this->base->get_trustbadge_code( false ),
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_badge_options' ),

			array(
                'title'             => _x( 'Configure Product Reviews', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_review_sticker_options'
            ),

			array(
				'title'             => _x( 'Show Shop Reviews Sticker', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => _x( 'This is a desc', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_enable',
				'type'              => 'gzd_toggle',
				'default'           => 'no'
			),

			array(
				'title'             => _x( 'Background Color', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Desc tip', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_bg_color',
				'type'              => 'color',
				'default'           => '#FFDC0F',
			),

			array(
				'title'             => _x( 'Font', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_font',
				'type'              => 'select',
				'class'             => 'chosen_select',
				'options'           => $this->get_font_families(),
				'default'           => 'arial'
			),

			array(
				'title'             => _x( 'Number Ratings', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'          => _x( '', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_number',
				'type'              => 'number',
				'desc'              => __( 'Reviews in change', 'trusted-shops', 'woocommerce-germanized' ),
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
				'title'             => _x( 'Show Ratings better than', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'          => _x( '', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_sticker_better_than',
				'type'              => 'number',
				'desc'              => __( 'Reviews in change', 'trusted-shops', 'woocommerce-germanized' ),
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
				'desc'              => _x( 'Use shortcodes e.g. {variant} to dynamically insert your options. You may of couse replace them with static code.', 'trusted-shops', 'woocommerce-germanized' ),
				'css'               => 'width: 100%; min-height: 150px',
				'default'           => $this->base->get_review_sticker_code( false ),
			),

			array(
				'title'             => _x( 'Google Organic Search', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_enable',
				'type'              => 'gzd_toggle',
				'default'           => 'no'
			),

			array(
				'title' 	        => _x( 'Activate Rich Snippets', 'trusted-shops', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Category', 'trusted-shops', 'woocommerce-germanized' ),
				'id' 	            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_category',
				'type' 		        => 'checkbox',
				'default'	        => 'no',
				'checkboxgroup'	    => 'start',
			),

			array(
				'desc' 		        => _x( 'Product Page', 'trusted-shops', 'woocommerce-germanized' ),
				'id' 	            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_product',
				'type' 		        => 'checkbox',
				'default'	        => 'no',
				'checkboxgroup'	    => '',
			),

			array(
				'desc' 		        => _x( 'Homepage', 'trusted-shops', 'woocommerce-germanized' ),
				'id' 	            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_home',
				'type' 		        => 'checkbox',
				'default'	        => 'no',
				'checkboxgroup'	    => 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_review_sticker_options' ),

			array(
                'title'             => _x( 'Configure Product Reviews', 'trusted-shops', 'woocommerce-germanized' ),
                'desc'              => _x( 'To use ratings', 'trusted-shops', 'woocommerce-germanized' ),
                'type'              => 'title',
                'id'                => 'trusted_shops_reviews_options'
            ),

			array(
				'title'             => _x( 'Product Reviews', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => _x( '(WooCommerce reviews are being replaced)', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'More Traffic, less returns: Make sure to unlock unlimited Product Reviews in your Trusted Shops plan.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_reviews_enable',
				'type'              => 'gzd_toggle',
				'default'           => 'no'
			),

			array(
				'title'             => _x( 'Product Review Sticker', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => _x( 'Show Product Reviews on Product Detail page on Reviews tab.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_enable',
				'type'              => 'checkbox',
				'default'           => 'no'
			),

			array(
				'title'             => _x( 'Tab text', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_tab_text',
				'type'              => 'text',
				'default'           => _x( 'Product reviews', 'trusted-shops', 'woocommerce-germanized' ),
			),

			array(
				'title'             => _x( 'Border Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_border_color',
				'type'              => 'color',
				'default'           => '#FFDC0F',
			),

			array(
				'title'             => _x( 'Background Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_bg_color',
				'type'              => 'color',
				'default'           => '#FFFFFF',
			),

			array(
				'title'             => _x( 'Star Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_star_color',
				'type'              => 'color',
				'default'           => '#C0C0C0',
			),

			array(
				'title'             => _x( 'Star Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_star_size',
				'type'              => 'number',
				'default'           => '15',
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
				'title'             => _x( 'Product Sticker Code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_code',
				'type'              => 'textarea',
				'css'               => 'width: 100%; min-height: 150px',
				'default'           => $this->base->get_product_sticker_code( false ),
			),

			array(
				'title'             => _x( 'Product Review Stars', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => _x( 'Show Star ratings on Product Detail Page below your Product Name.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_enable',
				'type'              => 'checkbox',
				'default'           => 'no'
			),

			array(
				'title'             => _x( 'Star Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_star_color',
				'type'              => 'color',
				'default'           => '#FFDC0F',
			),

			array(
				'title'             => _x( 'Star Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_star_size',
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
				'title'             => _x( 'Font Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_font_size',
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
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_code',
				'type'              => 'textarea',
				'css'               => 'width: 100%; min-height: 150px',
				'default'           => $this->base->get_product_widget_code( false ),
			),

			array(
				'title'             => _x( 'Google', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => _x( 'Configure reviews for Google Shopping.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_google_shopping_enable',
				'type'              => 'checkbox',
				'default'           => 'no'
			),

			array(
				'title'             => _x( 'Brand Attribute', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'              => sprintf( _x( 'This is the brand name of the product. By setting this variable you can improve your data analysis possibilities. If you create individual products and do not have a GTIN, you can pass the brand name along with the MPN to use Google Integration. Please choose from the product attributes which you have manually customized <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'edit.php?post_type=product&page=product_attributes' ) ),
				'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_brand_attribute',
				'css'               => 'min-width:250px;',
				'default'           => 'brand',
				'type'              => 'select',
				'class'             => 'chosen_select',
				'options'           => $linked_attributes,
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_reviews_options' ),

		);

		$options = array_merge( $options, array(
			array(	'title' => _x( 'Assign payment methods', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_payment_options' ),
		) );

		$payment_gateways = WC()->payment_gateways->payment_gateways();

		foreach ( $payment_gateways as $gateway ) {
			$default = '';

			switch ( $gateway->id ) {
				case 'bacs':
					$default = 'prepayment';
					break;
				case 'paypal':
					$default = 'paypal';
					break;
				case 'cod':
					$default = 'cash_on_delivery';
					break;
				case 'cheque':
					$default = 'cash_on_delivery';
					break;
				case 'mijireh_checkout':
					$default = 'credit_card';
					break;
				case 'direct-debit':
					$default = 'direct_debit';
					break;
				default:
					$default = $gateway->id;
			}

			array_push( $options, array(
				'title'         => empty( $gateway->method_title ) ? ucfirst( $gateway->id ) : $gateway->method_title,
				'desc'          => sprintf( _x( 'Choose a Trusted Shops Payment Gateway linked to WooCommerce Payment Gateway %s', 'trusted-shops', 'woocommerce-germanized' ), empty( $gateway->method_title ) ? ucfirst( $gateway->id ) : $gateway->method_title ),
				'desc_tip'      => true,
				'id'            => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_gateway_' . $gateway->id,
				'css'           => 'min-width:250px;',
				'default'       => $default,
				'type'          => 'select',
				'class'         => 'chosen_select',
				'options'       => $payment_options,
				'autoload'      => false
			) );
		}

		array_push( $options, array( 'type' => 'sectionend', 'id' => 'trusted_shops_payment_options' ) );

		if ( $this->base->supports( 'reminder' ) ) {

			$options = array_merge( $options, array(

				array(
					'title'             => _x( 'Configure Review Reminders', 'trusted-shops', 'woocommerce-germanized' ),
					'desc'              => _x( 'Review Reminder text', 'trusted-shops', 'woocommerce-germanized' ),
					'type'              => 'title',
					'id'                => 'trusted_shops_review_reminder_options'
				),

				array(
					'title'             => _x( 'Enable Review Reminder', 'trusted-shops', 'woocommerce-germanized' ),
					'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_enable',
                    'type'              => 'gzd_toggle',
					'default'           => 'no',
					'custom_attributes' => array(),
					'autoload'          => false
				),

				array(
					'title'             => _x( 'Order status', 'trusted-shops', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Decide how many days after an order the email review reminder will be sent.', 'trusted-shops', 'woocommerce-germanized' ),
					'default'           => 'wc-completed',
					'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_status',
					'type'              => 'select',
					'class'             => 'chosen_select',
                    'options'           => $this->get_order_statuses(),
				),

				array(
					'title'             => _x( 'Days until reminder', 'trusted-shops', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Decide how many days after an order the email review reminder will be sent.', 'trusted-shops', 'woocommerce-germanized' ),
					'default'           => 7,
					'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_days',
					'type'              => 'number',
					'custom_attributes' => array(
						'step'          => '1',
						'min'           => 1,
						'data-validate' => 'integer',
					),
				),

				array(
					'title'             => _x( 'Opt-In Checkbox', 'trusted-shops', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Decide how many days after an order the email review reminder will be sent.', 'trusted-shops', 'woocommerce-germanized' ),
					'default'           => '',
					'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_checkbox',
					'type'              => 'html',
                    'html'              => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized&section=checkboxes&checkbox_id=review_reminder' ) . '" class="button button-secondary" target="_blank">' . _x( 'Edit checkbox', 'trusted-shops', 'woocommerce-germanized' ) . '</a>',
				),

				array(
					'title'             => _x( 'Enable opt out', 'trusted-shops', 'woocommerce-germanized' ),
					'desc'              => _x( 'Allow the user to opt-out by clicking on a link within the order confirmation.', 'trusted-shops', 'woocommerce-germanized' ),
					'default'           => 'yes',
					'id'                => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_opt_out',
					'type'              => 'checkbox',
				),

				array( 'type' => 'sectionend', 'id' => 'trusted_shops_review_reminder_options' ),

			) );
		}

		return $options;

	}

	public function get_sidebar() {
		ob_start();
		?>
			<div class="wc-gzd-admin-settings-sidebar wc-gzd-admin-settings-sidebar-trusted-shops">
                <div class="wc-ts-sidebar wc-ts-sidebar-active" id="wc-ts-sidebar-default">
                    <h3><?php echo _x( 'About Trusted Shops', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
                    <a href="<?php echo $this->get_signup_url( $this->base->urls[ 'signup' ] ); ?>" target="_blank"><img style="width: 100%; height: auto" src="<?php echo $this->base->plugin->plugin_url(); ?>/assets/images/trusted-shops-b.jpg" /></a>
                    <a class="button button-primary" href="<?php echo $this->get_signup_url( $this->base->urls[ 'signup' ] ); ?>" target="_blank"><?php echo _x( 'Get your account', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
                </div>
                <div class="wc-ts-sidebar" id="wc-ts-sidebar-reviews">
                    <h3>Reviews Sidebar</h3>
                </div>
			</div>
		<?php
		
		$html = ob_get_clean();
		return $html;
	}

	public function before_save( $settings ) {
		if ( ! empty( $settings ) ) {
			
			foreach ( $settings as $setting ) {
				
				// Update reviews & snippets if new ts id has been inserted
				if ( isset( $_POST[ 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_id' ] ) && $_POST[ 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_id' ] != $this->base->id ) {
					update_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_snippets', 1 );
					update_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews', 1 );
				}
				
				if ( $setting[ 'id' ] == 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_widget_enable' ) {
					if ( ! empty( $_POST[ 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_widget_enable' ] ) && ! $this->base->is_review_widget_enabled() )
						update_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews', 1 );
				} elseif ( $setting[ 'id' ] == 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_enable' ) {
					if ( ! empty( $_POST[ 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_enable' ] ) && ! $this->base->is_rich_snippets_enabled() )
						update_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_snippets', 1 );
				}
			}
		}
	}

	public function after_save( $settings ) {
		
		$this->base->refresh();

		if ( get_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_integration_mode' ) === 'standard' ) {
			// Delete code snippets
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_code' );
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_code' );
			delete_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_code' );
		}

		// Disable Reviews if Trusted Shops review collection has been enabled
		if ( get_option( 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_enable_reviews' ) === 'yes' )
			update_option( 'woocommerce_enable_review_rating', 'no' );
		
		if ( get_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews' ) ) {
			$this->base->get_dependency( 'schedule' )->update_review_widget();
		}
		
		if ( get_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_snippets' ) )
			$this->base->get_dependency( 'schedule' )->update_reviews();
		
		delete_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_reviews' );
		delete_option( '_woocommerce_' . $this->base->option_prefix . 'trusted_shops_update_snippets' );
	}

	public function review_collector_export_csv() {
		
		if ( ! isset( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'wc_' . $this->base->option_prefix . 'trusted-shops-export' || ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'wc_' . $this->base->option_prefix . 'trusted-shops-export' && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc_' . $this->base->option_prefix . 'trusted-shops-export' ) ) )
			return;
		
		$interval_d = ( ( isset( $_GET[ 'interval' ] ) && ! empty( $_GET[ 'interval' ] ) ) ? absint( $_GET[ 'interval' ] ) : 30 );
		$days_to_send = ( ( isset( $_GET[ 'days' ] ) && ! empty( $_GET[ 'days' ] ) ) ? absint( $_GET[ 'days' ] ) : 5 );

		if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
		    include_once( 'class-wc-gzd-trusted-shops-review-exporter.php' );

		    $exporter = new WC_GZD_Trusted_Shops_Review_Exporter();
		    $exporter->set_days_until_send( $days_to_send );
		    $exporter->set_interval_days( $interval_d );

		    $exporter->export();
        }
	}

	public function review_collector_export() {

		if ( ! wc_gzd_get_dependencies()->woocommerce_version_supports_crud() )
		    return;
		?>
		<h3><?php echo _x( 'Review Collector', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
        <p class="description"><?php printf( _x( 'Export your customer data and ask consumers for a review with the Trusted Shops <a href="%s" target="_blank">Review Collector</a>.', 'trusted-shops', 'woocommerce-germanized' ), 'https://www.trustedshops.com/tsb2b/sa/ratings/batchRatingRequest.seam?prefLang=' . substr( get_bloginfo( 'language' ), 0, 2 ) ); ?></p>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_gzd_trusted_shops_review_collector"><?php echo _x( 'Export customer data', 'trusted-shops', 'woocommerce-germanized' ); ?> <?php echo wc_ts_help_tip( _x( 'Test tip', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
					</th>
					<td class="forminp forminp-select forminp-review-collector">
						<select name="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector" id="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector" class="chosen_select">
							<option value="30"><?php echo _x( '30 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="60"><?php echo _x( '60 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="90"><?php echo _x( '90 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
						</select>
                        <div class="trusted-shops-review-collector-wrap">
                            <div class="review-collector-days">
                                <label for="woocommerce_gzd_trusted_shops_review_collector"><?php echo _x( 'Days until notice will be sent', 'trusted-shops', 'woocommerce-germanized' ); ?> <?php echo wc_ts_help_tip( _x( 'Test tip', 'trusted-shops', 'woocommerce-germanized' ) ); ?></label>
                                <input type="number" value="5" min="1" data-validate="integer" name="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector_days_to_send" id="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector_days_to_send" />
                            </div>
                            <div class="review-collector-buttons">
                                <a class="button button-secondary" id="wc-gzd-trusted-shops-export" data-href-org="<?php echo admin_url( '?action=wc_' . $this->base->option_prefix . 'trusted-shops-export&_wpnonce=' . wp_create_nonce( 'wc_' . $this->base->option_prefix . 'trusted-shops-export' ) ); ?>" href="#"><?php echo _x( 'Start export', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
                            </div>
                        </div>
                    </td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private function get_signup_url( $url, $args = array() ) {
		
		$args = array_merge( $this->base->signup_params, $args );

		$args = wp_parse_args( $args, array(
			'utm_content' => 'marketing-page',
			'utm_medium' => 'software-app',
		) );

		return add_query_arg( $args, $url );
	}

	private function get_trusted_url( $url, $args = array() ) {

		$args = array_merge( $this->base->et_params, $args );

		$args = wp_parse_args( $args, array(
			'utm_term' => substr( get_locale(), 0, 2 ),
			'utm_medium' => 'link',
			'utm_source' => 'shopsoftwarebackend',
			'shop_id' => $this->base->ID,
		) );

		return add_query_arg( $args, $url );
	}

}