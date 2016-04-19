<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Trusted Shops implementation. This Class manages review collection if enabled.
 *
 * @class   WC_GZD_Trusted_Shops
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Trusted_Shops {

	/**
	 * Shops ID
	 *
	 * @var mixed
	 */
	public $id;
	/**
	 * Trusted Shops Payment Gateways
	 *
	 * @var array
	 */
	/**
	 * Trusted Shops Partner ID of WooCommerce Germanized
	 * @var string
	 */
	public $partner_id = 'WooCommerceGermanized';
	public $et_params = array( 'etcc_med' => 'part', 'etcc_cmp' => 'sofpar', 'etcc_par' => 'woo', 'etcc_mon' => 11 );
	/**
	 * Trusted Shops Plugin Version
	 * @var string
	 */
	public $version = '1.0.0';
	public $gateways;
	/**
	 * API URL for review collection
	 *
	 * @var string
	 */
	public $api_url;

	/**
	 * Sets Trusted Shops payment gateways and establishes hooks
	 */
	public function __construct() {
		
		// Refresh TS ID + API URL
		$this->refresh();
		
		$this->gateways = apply_filters( 'woocommerce_trusted_shops_gateways', array(
				'prepayment' => _x( 'Prepayment', 'trusted-shops', 'woocommerce-germanized' ),
				'cash_on_delivery' => _x( 'Cash On Delivery', 'trusted-shops', 'woocommerce-germanized' ),
				'credit_card' => _x( 'Credit Card', 'trusted-shops', 'woocommerce-germanized' ),
				'paypal' => _x( 'Paypal', 'trusted-shops', 'woocommerce-germanized' ),
				'invoice' => _x( 'Invoice', 'trusted-shops', 'woocommerce-germanized' ),
				'direct_debit' => _x( 'Direct Debit', 'trusted-shops', 'woocommerce-germanized' ),
				'financing' =>  _x( 'Financing', 'trusted-shops', 'woocommerce-germanized' ),
			)
		);

		// Schedule
		if ( $this->is_rich_snippets_enabled() ) {
			add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'update_reviews' ) );
			if ( empty( $this->reviews_cache ) )
				add_action( 'init', array( $this, 'update_reviews' ) );
		}
		
		if ( $this->is_review_widget_enabled() ) {
			add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'update_review_widget' ) );
			if ( empty( $this->review_widget_attachment ) )
				add_action( 'init', array( $this, 'update_review_widget' ) );
		}
		
		if ( $this->is_review_reminder_enabled() )
			add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'send_mails' ) );
		
		// Register Section
		add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'register_section' ), 1 );
		add_filter( 'woocommerce_gzd_get_settings_trusted_shops', array( $this, 'get_settings' ) );
		add_filter( 'woocommerce_gzd_get_sidebar_trusted_shops', array( $this, 'get_sidebar' ) );
		add_action( 'woocommerce_gzd_before_save_section_trusted_shops', array( $this, 'before_save' ), 0, 1 );
		add_action( 'woocommerce_gzd_after_save_section_trusted_shops', array( $this, 'after_save' ), 0, 1 );
		
		// Review Collector Export
		add_action( 'wc_germanized_settings_section_after_trusted_shops', array( $this, 'review_collector_export' ), 0 );
		add_action( 'admin_init', array( $this, 'review_collector_export_csv' ) );

		// Template actions
		if ( $this->is_enabled() )
			add_action( 'after_setup_theme', array( $this, 'template_hooks' ), 13 );

		if ( $this->is_product_reviews_enabled() ) {
			add_filter( 'woocommerce_product_tabs', array( $this, 'remove_review_tab' ), 40, 1 );
		}

		if ( $this->is_product_sticker_enabled() ) {
			add_filter( 'woocommerce_product_tabs', array( $this, 'review_tab' ), 50, 1 );
		}
		
		if ( $this->is_product_widget_enabled() ) {
			add_filter( 'woocommerce_gzd_template_name', array( $this, 'set_product_widget_template' ), 50, 1 );
		}
	}

	public function refresh() {
		$this->id = get_option( 'woocommerce_gzd_trusted_shops_id' );
		$this->api_url = 'http://api.trustedshops.com/rest/public/v2/shops/'. $this->id .'/quality.json';
	}

	/**
	 * Get Trusted Shops Options
	 *
	 * @param string  $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return get_option( 'woocommerce_gzd_trusted_shops_' . $key );
	}

	/**
	 * Checks whether a certain Trusted Shops Option isset
	 *
	 * @param string  $key
	 * @return boolean
	 */
	public function __isset( $key ) {
		return ( ! get_option( 'woocommerce_gzd_trusted_shops_' . $key ) ) ? false : true;
	}

	/**
	 * Checks whether Trusted Shops is enabled
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return ( $this->id ) ? true : false;
	}

	/**
	 * Checks whether Trusted Shops Rich Snippets are enabled
	 * 
	 * @return boolean
	 */
	public function is_rich_snippets_enabled() {
		return ( $this->rich_snippets_enable === 'yes' && $this->is_enabled() ? true : false );
	}

	/**
	 * Checks whether review widget is enabled
	 *  
	 * @return boolean
	 */
	public function is_review_widget_enabled() {
		return ( $this->review_widget_enable === 'yes' && $this->is_enabled() ? true : false );
	}

	public function is_review_reminder_enabled() {
		return ( $this->review_reminder_enable === 'yes' && $this->is_enabled() ? true : false );
	}

	public function is_product_reviews_enabled() {
		return ( $this->enable_reviews === 'yes' && $this->is_enabled() ? true : false );
	}

	public function is_product_sticker_enabled() {
		return ( $this->is_product_reviews_enabled() && $this->product_sticker_enable === 'yes' ? true : false );
	}

	public function is_product_widget_enabled() {
		return ( $this->is_product_reviews_enabled() && $this->product_widget_enable === 'yes' ? true : false );
	}

	/**
	 * Gets Trusted Shops payment gateway by woocommerce payment id
	 *
	 * @param integer $payment_method_id
	 * @return string
	 */
	public function get_payment_gateway( $payment_method_id ) {
		return ( get_option( 'woocommerce_gzd_trusted_shops_gateway_' . $payment_method_id ) ) ? strtoupper( get_option( 'woocommerce_gzd_trusted_shops_gateway_' . $payment_method_id ) ) : '';
	}

	/**
	 * Returns the average rating by grabbing the rating from the cache
	 *
	 * @return array
	 */
	public function get_average_rating() {
		return ( $this->reviews_cache ? $this->reviews_cache : array() );
	}

	/**
	 * Returns the certificate link
	 *
	 * @return string
	 */
	public function get_certificate_link() {
		return 'https://www.trustedshops.com/shop/certificate.php?shop_id=' . $this->id;
	}

	/**
	 * Returns add new rating link
	 * 
	 * @return string
	 */
	public function get_new_review_link( $email, $order_id ) {
		return 'https://www.trustedshops.de/bewertung/bewerten_' . $this->id . '.html&buyerEmail=' . urlencode( base64_encode( $email ) ) . '&shopOrderID=' . urlencode( base64_encode( $order_id ) );
	}

	/**
	 * Returns the rating link
	 *
	 * @return string
	 */
	public function get_rating_link() {
		return 'https://www.trustedshops.de/bewertung/info_' . $this->id . '.html';
	}

	/**
	 * Gets the attachment id of review widget graphic
	 *  
	 * @return mixed
	 */
	public function get_review_widget_attachment() {
		return ( ! $this->review_widget_attachment ? false : $this->review_widget_attachment );
	}

	public function get_template( $name ) {
		$html = "";
		ob_start();
		wc_get_template( 'trusted-shops/' . str_replace( '_', '-', $name ) . '-tpl.php' );
		$html = ob_get_clean();
		return preg_replace('/^\h*\v+/m', '', strip_tags( $html ) );
	}

	public function get_script( $name, $replace = true, $args = array() ) {
		$script = $this->get_template( $name );

		if ( $this->expert_mode === 'yes' )
			$script = $this->{$name . "_code"};

		if ( $replace ) {

			$args = wp_parse_args( $args, array(
				'id' => $this->id,
				'locale' => get_bloginfo( 'language' ),
			) );

			foreach ( $args as $key => $arg ) {
				$script = str_replace( '{' . $key . '}', $arg, $script );
			}

		}

		return $script;
	}

	public function get_product_sticker_code( $replace = true, $args = array() ) {
		if ( $replace ) {

			$args = wp_parse_args( $args, array(
				'border_color' => $this->product_sticker_border_color,
				'star_color' => $this->product_sticker_star_color,
				'star_size' => $this->product_sticker_star_size,
			) );

		}

		return $this->get_script( 'product_sticker', $replace, $args );
	}

	public function get_product_widget_code( $replace = true, $args = array() ) {
		if ( $replace ) {

			$args = wp_parse_args( $args, array(
				'star_color' => $this->product_widget_star_color,
				'star_size' => $this->product_widget_star_size,
				'font_size' => $this->product_widget_font_size,
			) );

		}

		return $this->get_script( 'product_widget', $replace, $args );
	}

	public function get_trustbadge_code( $replace = true, $args = array() ) {
		if ( $replace ) {

			$args = wp_parse_args( $args, array(
				'offset' => $this->trustbadge_y,
				'variant' => $this->trustbadge_hide_reviews === 'yes' ? 'default' : 'reviews',
			) );

		}

		return $this->get_script( 'trustbadge', $replace, $args );
	}

		public function set_product_widget_template( $template ) {
		if ( in_array( $template, array( 'single-product/rating.php', 'loop/rating.php' ) ) )
			$template = 'trusted-shops/product-widget.php';

		return $template;
	}

	public function remove_review_tab( $tabs ) {
		if ( isset( $tabs[ 'reviews' ] ) )
			unset( $tabs[ 'reviews' ] );
		return $tabs;
	}

	public function review_tab( $tabs ) {
		$tabs[ 'trusted_shops_reviews' ] = array(
			'title' => _x( 'Reviews', 'trusted-shops', 'woocommerce-germanized' ),
			'priority' => 30,
			'callback' => array( $this, 'template_product_sticker' ),
		);
		return $tabs;
	}

	public function template_hooks() {
		
		add_action( 'woocommerce_thankyou', array( $this, 'template_thankyou' ), 10, 1 );
		
		if ( $this->standard_trustbadge === 'yes' )
			add_action( 'wp_footer', array( $this, 'template_trustbadge' ), 5 );

	}

	public function template_product_sticker( $template ) {
		wc_get_template( 'trusted-shops/product-sticker.php' );
	}

	public function template_trustbadge() {
		wc_get_template( 'trusted-shops/trustbadge.php' );
	}

	public function template_thankyou( $order_id ) {
		wc_get_template( 'trusted-shops/thankyou.php', array( 'order_id' => $order_id ) );
	}

	public function register_section( $sections ) {
		$sections[ 'trusted_shops' ] = _x( 'Trusted Shops Options', 'trusted-shops', 'woocommerce-germanized' );
		return $sections;
	}

	/**
	 * Update Review Cache by grabbing information from xml file
	 */
	public function update_reviews() {
		$update = array();
		if ( $this->is_enabled() ) {
			if ( function_exists( 'curl_version' ) ) {
				$success = false;
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_HEADER, false );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_POST, false );
				curl_setopt( $ch, CURLOPT_URL, $this->api_url );
				$output = curl_exec( $ch );
				$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
				if ( ! curl_errno( $ch ) && $httpcode != 503 )
					$success = true;
				curl_close( $ch );
				if ( $success ) {
					$output = json_decode( $output, true );
					$reviews = $output[ 'response' ][ 'data' ][ 'shop' ][ 'qualityIndicators' ][ 'reviewIndicator' ];
					$update[ 'count' ] = (string) $reviews[ 'activeReviewCount' ];
					$update[ 'avg' ] = (float) $reviews[ 'overallMark' ];
					$update[ 'max' ] = '5.00';
				}
			}
		}
		update_option( 'woocommerce_gzd_trusted_shops_reviews_cache', $update );
	}

	/**
	 * Updates the review widget graphic and saves it as an attachment
	 */
	public function update_review_widget() {
		$filename = $this->id . '.gif';
		$raw_data = file_get_contents( 'https://www.trustedshops.com/bewertung/widget/widgets/' . $filename );
		$uploads = wp_upload_dir( date( 'Y-m' ) );
		if ( is_wp_error( $uploads ) )
			return;
		$filepath = $uploads['path'] . '/' . $filename;
  		file_put_contents( $filepath, $raw_data );
  		$attachment = array(
  			'guid' => $uploads[ 'url' ] . '/' . basename( $filepath ),
  			'post_mime_type' => 'image/gif',
  			'post_title' => _x( 'Trusted Shops Customer Reviews', 'trusted-shops', 'woocommerce-germanized' ),
  			'post_content' => '',
  			'post_status' => 'publish',
  		);
		if ( ! $this->get_review_widget_attachment() ) {
			$attachment_id = wp_insert_attachment( $attachment , $filepath );
			update_option( 'woocommerce_gzd_trusted_shops_review_widget_attachment', $attachment_id );
		} else {
			$attachment_id = $this->get_review_widget_attachment();
			update_attached_file( $attachment_id, $filepath );
			$attachment[ 'ID' ] = $attachment_id;
			wp_update_post( $attachment );
		}
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
		wp_update_attachment_metadata( $attachment_id, $attach_data );
	}

	/**
	 * Send review reminder mails after x days
	 */
	public function send_mails() {
		$order_query = new WP_Query(
			array( 
				'post_type'   => 'shop_order', 
				'post_status' => array( 'wc-completed' ), 
				'showposts'   => -1,
				'meta_query'  => array(
					array(
						'key'     => '_trusted_shops_review_mail_sent',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		while ( $order_query->have_posts() ) {
			$order_query->next_post();
			$order = wc_get_order( $order_query->post->ID );
			$diff = WC_germanized()->get_date_diff( $order->completed_date, date( 'Y-m-d H:i:s' ) );
			if ( $diff[ 'd' ] >= (int) $this->review_reminder_days ) {

				if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_trusted_shops' ) ) {
					$mail->trigger( $order->id );
					update_post_meta( $order->id, '_trusted_shops_review_mail_sent', 1 );
				}
				
			}
		}
	}

	/**
	 * Get Trusted Shops related Settings for Admin Interface
	 *
	 * @return array
	 */
	public function get_settings() {

		$payment_options = array( '' => __( 'None', 'woocommerce-germanized' ) ) + $this->gateways;

		$options = array(

			array( 'title' => _x( 'Trusted Shops Integration', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_options' ),

			array(
				'title'  => _x( 'TS-ID', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert your Trusted Shops ID here.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_trusted_shops_id',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Expert View', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => __( 'Choose if you want to manually adjust Trusted Shops code snippets.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_expert_mode',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_options' ),

			array(	'title' => _x( 'Configure the Trustbadge for your shop', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_badge_options', 'desc' => sprintf( _x( 'You\'ll find a step-by-step instruction for your shopsoftware in our integration center. <a href="%s" target="_blank">Click here</a>', 'trusted-shops', 'woocommerce-germanized' ), $this->get_trusted_url( 'integration/', 'trustbadge' ) ) ),

			array(
				'title'  => _x( 'Standard Trustbadge', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => __( 'Display standard variant of the trustbadge on home page.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_standard_trustbadge',
				'type'   => 'checkbox',
				'default' => 'yes'
			),

			array(
				'title'  => _x( 'Hide Reviews', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => __( 'Display trustbadge without reviews.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_trustbadge_hide_reviews',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Y-Offset', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Adjust the y-axis position of your Trustbadge from 0-250 (pixel) vertically on low right hand side of your shop.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_trusted_shops_trustbadge_y',
				'type'   => 'number',
				'default' => '0',
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'Trustbadge code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_gzd_trusted_shops_trustbadge_code',
				'type'   => 'textarea',
				'desc' => sprintf( _x( 'Learn more about relevant <a href="%s" target="_blank">variables</a>.', 'trusted-shops', 'woocommerce-germanized' ), $this->get_trusted_url( 'integration/', 'trustbadge' ) ),
				'css' => 'width: 100%; min-height: 80px',
				'default' => $this->get_trustbadge_code( false ),
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_badge_options' ),

			array(	'title' => _x( 'Configure Customer Reviews', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_reviews_options' ),

			array(
				'title'  => _x( 'Reviews', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Collect Product Reviews. This options replaces default WooCommerce Reviews.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Please make sure that Product Reviews are included in your Trusted Shops plan. If you\'re not sure, have a look at your Trusted Shops account or contact Trusted Shops.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_enable_reviews',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Reviews Tab', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Show Comments on Product Detail page in Extra Reviews Tab.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_sticker_enable',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Border Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_sticker_border_color',
				'type'   => 'color',
				'default' => '#FFDC0F',
			),

			array(
				'title'  => _x( 'Star Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_sticker_star_color',
				'type'   => 'color',
				'default' => '#C0C0C0',
			),

			array(
				'title'  => _x( 'Product Sticker Code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_gzd_trusted_shops_product_sticker_code',
				'type'   => 'textarea',
				'css' => 'width: 100%; min-height: 80px',
				'desc' => sprintf( _x( 'Learn more about relevant <a href="%s" target="_blank">variables</a>.', 'trusted-shops', 'woocommerce-germanized' ), 'https://www.trustedshops.de/shopbetreiber/integration/product-reviews/' ),
				'default' => $this->get_product_sticker_code( false ),
			),

			array(
				'title'  => _x( 'Star Ratings', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Show Star ratings on Product Detail page under your Product Name.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_widget_enable',
				'type'   => 'checkbox',
				'default' => 'no'
			),

			array(
				'title'  => _x( 'Star Color', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_widget_star_color',
				'type'   => 'color',
				'default' => '#FFDC0F',
			),

			array(
				'title'  => _x( 'Star Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_widget_star_size',
				'type'   => 'number',
				'default' => '15',
				'desc' => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'Font Size', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_product_widget_font_size',
				'type'   => 'number',
				'desc' => __( 'px', 'trusted-shops', 'woocommerce-germanized' ),
				'default' => '12',
				'css'   => 'max-width:60px;',
			),

			array(
				'title'  => _x( 'Product Widget Code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_gzd_trusted_shops_product_widget_code',
				'type'   => 'textarea',
				'css' => 'width: 100%; min-height: 80px',
				'desc' => sprintf( _x( 'Learn more about relevant <a href="%s" target="_blank">variables</a>.', 'trusted-shops', 'woocommerce-germanized' ), 'https://www.trustedshops.de/shopbetreiber/integration/product-reviews/' ),
				'default' => $this->get_product_widget_code( false ),
			),

			array(
				'title'  => _x( 'Review Widget', 'trusted-shops', 'woocommerce-germanized' ),
				'desc' => _x( 'Enable Review Widget', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip'   => sprintf( _x( 'This option will enable a Widget which shows your Trusted Shops Reviews as a graphic. You may configure your Widgets <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'widgets.php' ) ),
				'id'   => 'woocommerce_gzd_trusted_shops_review_widget_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Rich Snippets', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Enable Rich Snippets Widget.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'This option will update your reviews received via Trusted Shops once per day and enables a Widget to show your reviews as Rich Snippets', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_rich_snippets_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Review Reminder', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'Send a one-time email review reminder to your customers.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'widgets.php' ) ),
				'id'   => 'woocommerce_gzd_trusted_shops_review_reminder_enable',
				'type'   => 'checkbox',
				'default' => 'no',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Days until reminder', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Decide how many days after an order the email review reminder will be sent.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'default' => 7,
				'id'   => 'woocommerce_gzd_trusted_shops_review_reminder_days',
				'type'   => 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_reviews_options' ),

			array(	'title' => _x( 'Assign payment methods', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_payment_options' ),

		);

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
			}

			array_push( $options, array(
				'title'  => empty( $gateway->method_title ) ? ucfirst( $gateway->id ) : $gateway->method_title,
				'desc'   => sprintf( _x( 'Choose a Trusted Shops Payment Gateway linked to WooCommerce Payment Gateway %s', 'trusted-shops', 'woocommerce-germanized' ), empty( $gateway->method_title ) ? ucfirst( $gateway->id ) : $gateway->method_title ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_trusted_shops_gateway_' . $gateway->id,
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
		return '<div class="wc-gzd-admin-settings-sidebar"><h3>' . _x( 'About Trusted Shops', 'trusted-shops', 'woocommerce-germanized' ) . '</h3><a href="' . $this->get_trusted_url( 'integration/', 'membership' ) . '" target="_blank"><img style="width: 100%; height: auto" src="' . WC_germanized()->plugin_url() . '/assets/images/trusted-shops-b.png" /></a></div>';
	}

	public function before_save( $settings ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				// Update reviews & snippets if new ts id has been inserted
				if ( isset( $_POST[ 'woocommerce_gzd_trusted_shops_id' ] ) && $_POST[ 'woocommerce_gzd_trusted_shops_id' ] != $this->id ) {
					update_option( '_woocommerce_gzd_trusted_shops_update_snippets', 1 );
					update_option( '_woocommerce_gzd_trusted_shops_update_reviews', 1 );
				}
				if ( $setting[ 'id' ] == 'woocommerce_gzd_trusted_shops_review_widget_enable' ) {
					if ( ! empty( $_POST[ 'woocommerce_gzd_trusted_shops_review_widget_enable' ] ) && ! $this->is_review_widget_enabled() )
						update_option( '_woocommerce_gzd_trusted_shops_update_reviews', 1 );
				} else if ( $setting[ 'id' ] == 'woocommerce_gzd_trusted_shops_rich_snippets_enable' ) {
					if ( ! empty( $_POST[ 'woocommerce_gzd_trusted_shops_rich_snippets_enable' ] ) && ! $this->is_rich_snippets_enabled() )
						update_option( '_woocommerce_gzd_trusted_shops_update_snippets', 1 );
				}
			}
		}
	}

	public function after_save( $settings ) {
		
		$this->refresh();

		if ( get_option( 'woocommerce_gzd_trusted_shops_expert_mode' ) === 'no' ) {
			// Delete code snippets
			delete_option( 'woocommerce_gzd_trusted_shops_trustbadge_code' );
			delete_option( 'woocommerce_gzd_trusted_shops_product_sticker_code' );
			delete_option( 'woocommerce_gzd_trusted_shops_product_widget_code' );
		}

		// Disable Reviews if Trusted Shops review collection has been enabled
		if ( get_option( 'woocommerce_gzd_trusted_shops_enable_reviews' ) === 'yes' )
			update_option( 'woocommerce_enable_review_rating', 'no' );
		
		if ( get_option( '_woocommerce_gzd_trusted_shops_update_reviews' ) )
			$this->update_review_widget();
		
		if ( get_option( '_woocommerce_gzd_trusted_shops_update_snippets' ) )
			$this->update_reviews();
		
		delete_option( '_woocommerce_gzd_trusted_shops_update_reviews' );
		delete_option( '_woocommerce_gzd_trusted_shops_update_snippets' );
	}

	public function review_collector_export_csv() {
		
		if ( ! isset( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'wc-gzd-trusted-shops-export' || ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'wc-gzd-trusted-shops-export' && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc-gzd-trusted-shops-export' ) ) )
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
			array_push( $data, array( $order->billing_email, $order->id, $order->billing_first_name, $order->billing_last_name ) );
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
						<select name="woocommerce_gzd_trusted_shops_review_collector" id="woocommerce_gzd_trusted_shops_review_collector" class="chosen_select">
							<option value="30"><?php echo _x( '30 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="60"><?php echo _x( '60 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
							<option value="90"><?php echo _x( '90 days', 'trusted-shops', 'woocommerce-germanized' ); ?></option>
						</select>
						<p><a class="button button-secondary" id="wc-gzd-trusted-shops-export" data-href-org="<?php echo admin_url( '?action=wc-gzd-trusted-shops-export&_wpnonce=' . wp_create_nonce( 'wc-gzd-trusted-shops-export' ) ); ?>" href="#"><?php echo _x( 'Start export', 'trusted-shops', 'woocommerce-germanized' ); ?></a></p>
						<p class="description"><?php printf( _x( 'Export your customer data and ask consumers for a review with the Trusted Shops <a href="%s" target="_blank">Review Collector</a>.', 'trusted-shops', 'woocommerce-germanized' ), 'https://www.trustedshops.com/tsb2b/sa/ratings/batchRatingRequest.seam?prefLang=' . substr( get_bloginfo( 'language' ), 0, 2 ) ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	private function get_trusted_url( $base = 'integration/', $context = 'trustbadge' ) {
		$url = 'https://www.trustedshops.com/' . $base . '?shop_id=' . esc_attr( $this->id ) . '&backend_language=' . esc_attr( substr( get_bloginfo( 'language' ), 0, 2) ) . '&shopsw=' . esc_attr( $this->partner_id ) . '&shopsw_version=' . esc_attr( WC_GERMANIZED_VERSION ) . '&plugin_version=' . esc_attr( $this->version ) . 'context=' . esc_attr( $context );
		if ( ! empty( $this->et_params ) ) {
			foreach ( $this->et_params as $key => $param )
				$url .= '&' . esc_attr( $key ) . '=' . esc_attr( $param );
		}
		return $url;
	}

}

?>
