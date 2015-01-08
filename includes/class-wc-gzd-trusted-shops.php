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
	public $partner_id;
	public $et_params = array();
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
		$this->partner_id = 'WooCommerceGermanized';
		$this->id = get_option( 'woocommerce_gzd_trusted_shops_id' );
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
		$this->et_params = array( 'etcc_med' => 'part', 'etcc_cmp' => 'sofpar', 'etcc_par' => 'woo', 'etcc_mon' => 11 );
		$this->api_url = 'http://www.trustedshops.com/api/ratings/v1/'. $this->id .'.xml';
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
		//add_action( 'init', array( $this, 'send_mails' ) );
		// Add Badge to Footer
		if ( $this->is_enabled() && $this->get_badge_js() )
			add_action( 'wp_footer', array( $this, 'add_badge' ), 5 );
		// Register Section
		add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'register_section' ), 1 );
		add_filter( 'woocommerce_gzd_get_settings_trusted_shops', array( $this, 'get_settings' ) );
		add_filter( 'woocommerce_gzd_get_sidebar_trusted_shops', array( $this, 'get_sidebar' ) );
		add_action( 'woocommerce_gzd_before_save_section_trusted_shops', array( $this, 'before_save' ), 0, 1 );
		add_action( 'woocommerce_gzd_before_save_section_trusted_shops', array( $this, 'after_save' ), 0, 1 );
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
		return ( $this->rich_snippets_enable == 'yes' && $this->is_enabled() ? true : false );
	}

	/**
	 * Checks whether review widget is enabled
	 *  
	 * @return boolean
	 */
	public function is_review_widget_enabled() {
		return ( $this->review_widget_enable == 'yes' && $this->is_enabled() ? true : false );
	}

	public function is_review_reminder_enabled() {
		return ( $this->review_reminder_enable == 'yes' && $this->is_enabled() ? true : false );
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
	 * Returs badge js code
	 * 
	 * @return string
	 */
	public function get_badge_js() {
		return ( $this->badge_code ? $this->badge_code : false );
	}

	/**
	 * Adds the Badge by implementing js code
	 */
	public function add_badge() {
		if ( $this->is_enabled() ) {
			echo "<script type='text/javascript'>
			    " . $this->get_badge_js() . "
			</script>";
		}
	}

	/**
	 * Gets the attachment id of review widget graphic
	 *  
	 * @return mixed
	 */
	public function get_review_widget_attachment() {
		return ( ! $this->review_widget_attachment ? false : $this->review_widget_attachment );
	}

	/**
	 * Returns average rating rich snippet html
	 *
	 * @return string
	 */
	public function get_average_rating_html() {
		$rating = $this->get_average_rating();
		$html = '';
		if ( !empty( $rating ) && $this->is_rich_snippets_enabled() ) {
			$html = '
				<div itemscope itemtype="http://data-vocabulary.org/Review-aggregate" class="wc-gzd-trusted-shops-rating-widget">
					<a href="' . $this->get_rating_link() . '" target="_blank" title="' . sprintf( _x( '%s ratings', 'trusted-shops', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ) . '"><span itemprop="itemreviewed"><strong>' . get_bloginfo( 'name' ) . '</strong></span></a>
					<div class="star-rating" title="' . sprintf( _x( 'Rated %s out of %s', 'trusted-shops', 'woocommerce-germanized' ), $rating['avg'], (int) $rating['max'] ) . '">
						<span style="width:' . ( ( $rating['avg'] / 5 ) * 100 ) . '%">
							<strong class="rating">' . esc_html( $rating['avg'] ) . '</strong> ' . sprintf( _x( 'out of %s', 'trusted-shops', 'woocommerce-germanized' ), (int) $rating[ 'max' ] ) . '
						</span>
					</div>
					<br/>
					<span itemprop="rating" itemscope itemtype="http://data-vocabulary.org/Rating">
		         		' . sprintf( _x( '%s of %s based on %s <a href="%s" target="_blank">ratings</a>.', 'trusted-shops', 'woocommerce-germanized' ), '&#216; <span itemprop="average">' . $rating['avg'] . '</span>', '<span itemprop="best">' . (int) $rating['max'] . '</span>', '<span class="count" itemprop="votes">' . $rating['count'] . '</span>', $this->get_rating_link() ) . '
		    		</span>
		   		</div>
		   	';
		}
		return $html;
	}

	/**
	 * Returns the review widget html
	 *  
	 * @return string 
	 */
	public function get_review_widget_html() {
		return ( $this->get_review_widget_attachment() ? '<a href="' . $this->get_rating_link() . '" target="_blank" title="' . _x( 'Show customer reviews', 'trusted-shops', 'woocommerce-germanized' ) . '">' . wp_get_attachment_image( $this->get_review_widget_attachment(), 'full' ) . '</a>' : false );
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
				if ( !curl_errno( $ch ) && $httpcode != 503 )
					$success = true;
				curl_close( $ch );
				if ( $success ) {
					$xml = new SimpleXMLElement( $output );
					$xPath = '/shop/ratings/result[@name="average"]';
					$avg = $xml->xpath( $xPath );
					if ( ! empty( $avg[0] ) ) {
						$update['avg'] = ( float ) $avg[0];
						$update['max'] = '5.00';
						$update['count'] = ( string ) $xml->ratings["amount"][0];
					}	
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
			array( 'post_type' => 'shop_order', 'post_status' => array( 'wc-completed' ), 'meta_query' =>
				array(
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
				$mails = WC()->mailer()->get_emails();
				if ( !empty( $mails ) ) {
					foreach ( $mails as $mail ) {
						if ( $mail->id == 'customer_trusted_shops' ) {
							$mail->trigger( $order->id );
							update_post_meta( $order->id, '_trusted_shops_review_mail_sent', 1 );
						}
					}
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

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_options' ),

			array(	'title' => _x( 'Configure the Trustbadge for your shop', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_badge_options', 'desc' => sprintf( _x( 'You\'ll find a step-by-step instruction for your shopsoftware in our integration center. <a href="%s" target="_blank">Click here</a>', 'trusted-shops', 'woocommerce-germanized' ), $this->get_trusted_url( 'integration/', 'trustbadge' ) ) ),

			array(
				'title'  => _x( 'Trustbadge code', 'trusted-shops', 'woocommerce-germanized' ),
				'id'     => 'woocommerce_gzd_trusted_shops_badge_code',
				'type'   => 'textarea',
				'custom_attributes'  => array( 'placeholder' => _x( 'Fill in your trustbadge code here', 'trusted-shops', 'woocommerce-germanized' ), 'data-after' => _x( 'If no further steps were required in the integration center, the Trustbadge is already displayed in your shop.', 'trusted-shops', 'woocommerce-germanized' ) ),
				'css' => 'width: 100%; min-height: 80px',
				'autoload'  => false
			),

			array( 'type' => 'sectionend', 'id' => 'trusted_shops_badge_options' ),

			array(	'title' => _x( 'Configure Customer Reviews', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_reviews_options' ),

			array(
				'title'  => _x( 'Enable Review Widget', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'This option will enable a Widget which shows your Trusted Shops Reviews as a graphic. You may configure your Widgets <a href="%s">here</a>.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'widgets.php' ) ),
				'id'   => 'woocommerce_gzd_trusted_shops_review_widget_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Enable Rich Snippets for Google', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'This option will update your reviews received via Trusted Shops once per day and enables a Widget to show your reviews as Rich Snippets.', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_rich_snippets_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Enable Review Reminder', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'This option will enable a one-time email review reminder being sent to your customer.', 'trusted-shops', 'woocommerce-germanized' ), admin_url( 'widgets.php' ) ),
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
				'id'   => 'woocommerce_gzd_trusted_review_reminder_days',
				'type'   => 'number',
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
		if ( !empty( $settings ) ) {
			foreach ( $settings as $setting ) {
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
		if ( get_option( '_woocommerce_gzd_trusted_shops_update_reviews' ) )
			$this->update_reviews();
		if ( get_option( '_woocommerce_gzd_trusted_shops_update_snippets' ) )
			$this->update_review_widget();
		delete_option( '_woocommerce_gzd_trusted_shops_update_reviews' );
		delete_option( '_woocommerce_gzd_trusted_shops_update_snippets' );
	}

	public function register_section( $sections ) {
		$sections[ 'trusted_shops' ] = _x( 'Trusted Shops Options', 'trusted-shops', 'woocommerce-germanized' );
		return $sections;
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
