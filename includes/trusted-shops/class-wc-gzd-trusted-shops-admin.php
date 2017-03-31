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
		
		$this->base = $base;
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
	}

	public function create_attribute() {

		$attributes = array( 
			'gtin' => _x( 'GTIN', 'trusted-shops', 'woocommerce-germanized' ), 
			'brand' => _x( 'Brand', 'trusted-shops', 'woocommerce-germanized' ), 
			'mpn' => _x( 'MPN', 'trusted-shops', 'woocommerce-germanized' ),
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
			'option_prefix' => $this->base->option_prefix,
			'script_prefix' => $this->script_prefix,
		) );

		wp_enqueue_script( 'wc-' . $this->script_prefix . 'admin-trusted-shops' );

	}

	public function register_section( $sections ) {
		$sections[ 'trusted_shops' ] = _x( 'Trusted Shops Options', 'trusted-shops', 'woocommerce-germanized' );
		return $sections;
	}

	/**
	 * Get Trusted Shops related Settings for Admin Interface
	 *
	 * @return array
	 */
	public function get_settings() {

		$payment_options = array( '' => __( 'None', 'woocommerce-germanized' ) ) + $this->base->gateways;
		$attributes = wc_get_attribute_taxonomies();
		$linked_attributes = array();
			
		// Set attributes
		foreach ( $attributes as $attribute ) {
			$linked_attributes[ $attribute->attribute_name ] = $attribute->attribute_label;
		}

		$options = array(

			array( 'title' => _x( 'Trusted Shops Integration', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_options' ),

			array(
				'title'  => _x( 'Trusted Shops ID', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'The Trusted Shops ID is a unique identifier for your shop. You can find your Trusted Shops ID in your confirmation email after signing up.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_id',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Mode', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_integration_mode',
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => array( 
					'standard' => _x( 'Standard Mode', 'trusted-shops', 'woocommerce-germanized' ),
					'expert' => _x( 'Expert Mode', 'trusted-shops', 'woocommerce-germanized' ),
				),
				'default' => 'standard'
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_options' ),

			array(	'title' => _x( 'Configure your Trustbadge', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_badge_options', 'desc' => sprintf( _x( '<a href="%s" target="_blank">Here</a> you can find a step-by-step introduction.', 'trusted-shops', 'woocommerce-germanized' ), $this->get_trusted_url( $this->base->urls[ 'integration' ] ) ) ),

			array(
				'title'  => _x( 'Variant', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_variant',
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => array( 
					'hide_reviews' => _x( 'Display Trustbadge without review stars', 'trusted-shops', 'woocommerce-germanized' ),
					'standard' => _x( 'Display Trustbadge with review stars', 'trusted-shops', 'woocommerce-germanized' ),
					'disable' => _x( 'Don’t show Trustbadge', 'trusted-shops', 'woocommerce-germanized' ),
				),
				'default' => 'standard'
			),

			array(
				'title'  => _x( 'Y-Offset', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Adjust the y-axis position of your Trustbadge from 0-250 (pixel) vertically on low right hand side of your shop.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_y',
				'type'   => 'number',
				'desc' => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
				'default' => '0',
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'Trustbadge code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_trustbadge_code',
				'type'   => 'textarea',
				'desc_tip' => true,
				'desc' => _x( 'Use shortcodes e.g. {variant} to dynamically insert your options. You may of couse replace them with static code.', 'trusted-shops', 'woocommerce-germanized' ),
				'css' => 'width: 100%; min-height: 150px',
				'default' => $this->base->get_trustbadge_code( false ),
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_badge_options' ),

			array(	'title' => _x( 'Configure Product Reviews', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_reviews_options' ),

			array(
				'title'  => _x( 'Product Reviews', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Collect Product Reviews. This options replaces default WooCommerce Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'More Traffic, less returns: Make sure to unlock unlimited Product Reviews in your Trusted Shops plan.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_enable_reviews',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Product Review Sticker', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Show Product Reviews on Product Detail page on Reviews tab.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_enable',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Border Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_border_color',
				'type'   => 'color',
				'default' => '#FFDC0F',
			),

			array(
				'title'  => _x( 'Star Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_star_color',
				'type'   => 'color',
				'default' => '#C0C0C0',
			),

			array(
				'title'  => _x( 'Star Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_star_size',
				'type'   => 'number',
				'default' => '15',
				'desc' => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'Product Sticker Code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_sticker_code',
				'type'   => 'textarea',
				'css' => 'width: 100%; min-height: 150px',
				'default' => $this->base->get_product_sticker_code( false ),
			),

			array(
				'title'  => _x( 'Product Review Stars', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Show Star ratings on Product Detail Page below your Product Name.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_enable',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Star Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_star_color',
				'type'   => 'color',
				'default' => '#FFDC0F',
			),

			array(
				'title'  => _x( 'Star Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_star_size',
				'type'   => 'number',
				'default' => '15',
				'desc' => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'Font Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_font_size',
				'type'   => 'number',
				'desc' => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
				'default' => '12',
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'GTIN Attribute', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'This is the product identification code for trade items. If you use Google Shopping and want to display your review stars in Shopping and payed product ads, this information is necessary for Google. Please choose from the product attributes which you have manually customized <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'edit.php?post_type=product&page=product_attributes' ) ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_gtin_attribute',
				'css'   => 'min-width:250px;',
				'default' => 'gtin',
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => $linked_attributes,
			),

			array(
				'title'  => _x( 'Brand Attribute', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'This is the brand name of the product. By setting this variable you can improve your data analysis possibilities. If you create individual products and do not have a GTIN, you can pass the brand name along with the MPN to use Google Integration. Please choose from the product attributes which you have manually customized <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'edit.php?post_type=product&page=product_attributes' ) ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_brand_attribute',
				'css'   => 'min-width:250px;',
				'default' => 'brand',
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => $linked_attributes,
			),

			array(
				'title'  => _x( 'MPN Attribute', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'Number that associates the product to its manufacturer. If you create individual products and do not have a GTIN, you can pass the MPN along with the brand name to use Google Integration. Please choose from the product attributes which you have manually customized <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'edit.php?post_type=product&page=product_attributes' ) ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_mpn_attribute',
				'css'   => 'min-width:250px;',
				'default' => 'mpn',
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => $linked_attributes,
			),

			array(
				'title'  => _x( 'Product Review Code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_product_widget_code',
				'type'   => 'textarea',
				'css' => 'width: 100%; min-height: 150px',
				'default' => $this->base->get_product_widget_code( false ),
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_reviews_options' ),

			array(	'title' => _x( 'Additional Options', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_additional_options' ),

			array(
				'title'  => _x( 'Review Widget', 'trusted-shops', 'woocommerce-germanized' ),
				'desc' => _x( 'Enable Review Widget', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'   => sprintf( _x( 'This option will enable a Widget which shows your Trusted Shops Reviews as a graphic. You may configure your Widgets <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'widgets.php' ) ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_widget_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Rich Snippets', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Enable Rich Snippets Widget.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'This option will update your reviews received via Trusted Shops once per day and enables a Widget to show your reviews as Rich Snippets', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_rich_snippets_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

		);

		if ( $this->base->supports( 'reminder' ) ) {

			$options = array_merge( $options, array(

				array(
					'title'  => _x( 'Review Reminder', 'trusted-shops', 'woocommerce-germanized' ),
					'desc'   => sprintf( _x( 'Send a one-time email review reminder to your customers.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'widgets.php' ) ),
					'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_enable',
					'type'   => 'checkbox',
					'default' => 'no',
					'autoload'  => false
				),

				array(
					'title'  => _x( 'Days until reminder', 'trusted-shops', 'woocommerce-germanized' ),
					'desc'   => _x( 'Decide how many days after an order the email review reminder will be sent.', 'trusted-shops', 'woocommerce-germanized' ),
					'desc_tip' => true,
					'default' => 7,
					'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_review_reminder_days',
					'type'   => 'number',
					'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
				)

			) );

		}

		$options = array_merge( $options, array(

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_additional_options' ),

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
				'title'  => empty( $gateway->method_title ) ? ucfirst( $gateway->id ) : $gateway->method_title,
				'desc'   => sprintf( _x( 'Choose a Trusted Shops Payment Gateway linked to WooCommerce Payment Gateway %s', 'trusted-shops', 'woocommerce-germanized' ), empty( $gateway->method_title ) ? ucfirst( $gateway->id ) : $gateway->method_title ),
				'desc_tip' => true,
				'id'   => 'woocommerce_' . $this->base->option_prefix . 'trusted_shops_gateway_' . $gateway->id,
				'css'   => 'min-width:250px;',
				'default' => $default,
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => $payment_options,
				'autoload'      => false
			) );
		}

		array_push( $options, array( 'type' => 'sectionend', 'id' => 'trusted_shops_options' ) );

		return $options;

	}

	public function get_sidebar() {
		ob_start();
		?>
			<div class="wc-gzd-admin-settings-sidebar wc-gzd-admin-settings-sidebar-trusted-shops">
				<h3><?php echo _x( 'About Trusted Shops', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
				<a href="<?php echo $this->get_signup_url( $this->base->urls[ 'signup' ] ); ?>" target="_blank"><img style="width: 100%; height: auto" src="<?php echo $this->base->plugin->plugin_url(); ?>/assets/images/trusted-shops-b.jpg" /></a>
				<a class="button button-primary" href="<?php echo $this->get_signup_url( $this->base->urls[ 'signup' ] ); ?>" target="_blank"><?php echo _x( 'Get your account', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
				<div class="wc-gzd-trusted-shops-expert-mode-note">
					<p><?php echo _x( 'Use additional options to customize your Trusted Shops Integration or use the latest code version here. E.g.:', 'trusted-shops', 'woocommerce-germanized' ); ?></p>
					<ul>
						<li><?php echo _x( 'Place your Trustbadge wherever you want', 'trusted-shops', 'woocommerce-germanized' ); ?></li>
						<li><?php echo _x( 'Deactivate mobile use', 'trusted-shops', 'woocommerce-germanized' ); ?></li>
						<li><?php echo _x( 'Jump from your Product Reviews stars directly to your Product Reviews', 'trusted-shops', 'woocommerce-germanized' ); ?></li>
					</ul>
					<p><?php echo sprintf( _x( '<a href="%s" target="_blank">Learn more</a> about <a href="%s" target="_blank">Trustbadge</a> options and <a href="%s" target="_blank">Product Reviews</a> configuration.', 'trusted-shops', 'woocommerce-germanized' ), $this->get_trusted_url( $this->base->urls[ 'integration' ] ), $this->base->urls[ 'trustbadge_custom' ], $this->base->urls[ 'reviews' ] ); ?></p>
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

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=review-collector.csv' );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' ); 
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );	

		$date = date( 'Y-m-d', strtotime( '-' . $interval_d . ' days') );
		$order_query = new WP_Query(
			array( 
				'post_type'   => 'shop_order', 
				'post_status' => array( 'wc-completed' ), 
				'showposts'   => -1,
				'date_query'  => array(
					array(
						'after' => $date,
					),
				),
			)
		);

		$data = array();
		
		while ( $order_query->have_posts() ) {
			$order_query->next_post();
			$order = wc_get_order( $order_query->post->ID );
			array_push( $data, array( 
				wc_gzd_get_crud_data( $order, 'billing_email' ), 
				wc_gzd_get_crud_data( $order, 'id' ), 
				wc_gzd_get_crud_data( $order, 'billing_first_name' ), 
				wc_gzd_get_crud_data( $order, 'billing_last_name' ) 
			) );
		}

		$write = $this->prepare_csv_data( $data );
	   	$df = fopen( "php://output", 'w' );
		foreach ( $write as $row )
			fwrite( $df, $row );
	    fclose( $df );
	    
	    exit();
	}

	public function prepare_csv_data( $row ) {
		foreach ( $row as $key => $row_data ) {
			foreach ( $row_data as $rkey => $rvalue )
				$row[ $key ][ $rkey ] = $this->encode_csv_data( str_replace( '"', '\"', $rvalue ) );
			$row[ $key ] = implode( ",", $row[ $key ] ) . "\n";
		}
		return $row;
	}

	public function encode_csv_data( $string ) {
		return iconv( get_option( 'blog_charset' ), 'Windows-1252', $string );
	}

	public function review_collector_export() {
		?>
		<h3><?php echo _x( 'Review Collector', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="woocommerce_gzd_trusted_shops_review_collector"><?php echo _x( 'Export customer data', 'trusted-shops', 'woocommerce-germanized' ); ?></label>
					</th>
					<td class="forminp forminp-select">
						<select name="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector" id="woocommerce_<?php echo $this->base->option_prefix; ?>trusted_shops_review_collector" class="chosen_select">
							<option value="30"><?php echo _x( '30 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="60"><?php echo _x( '60 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="90"><?php echo _x( '90 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
						</select>
						<p><a class="button button-secondary" id="wc-gzd-trusted-shops-export" data-href-org="<?php echo admin_url( '?action=wc_' . $this->base->option_prefix . 'trusted-shops-export&_wpnonce=' . wp_create_nonce( 'wc_' . $this->base->option_prefix . 'trusted-shops-export' ) ); ?>" href="#"><?php echo _x( 'Start export', 'trusted-shops', 'woocommerce-germanized' ); ?></a></p>
						<p class="description"><?php printf( _x( 'Export your customer data and ask consumers for a review with the Trusted Shops <a href="%s" target="_blank">Review Collector</a>.', 'trusted-shops', 'woocommerce-germanized' ), 'https://www.trustedshops.com/tsb2b/sa/ratings/batchRatingRequest.seam?prefLang=' . substr( get_bloginfo( 'language' ), 0, 2 ) ); ?></p>
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