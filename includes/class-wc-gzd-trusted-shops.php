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
		$this->api_url = 'http://www.trustedshops.com/api/ratings/v1/'. $this->id .'.xml';
		// Schedule
		if ( $this->is_enabled() )
			add_action( 'woocommerce_gzd_trusted_shops_reviews', array( $this, 'update_reviews' ) );
		// Add Badge to Footer
		if ( $this->enable_badge() )
			add_action( 'wp_footer', array( $this, 'add_badge' ), 5 );
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
	 * Gets Trusted Shops payment gateway by woocommerce payment id
	 *
	 * @param integer $payment_method_id
	 * @return string
	 */
	public function get_payment_gateway( $payment_method_id ) {
		return ( get_option( 'woocommerce_gzd_trusted_shops_gateway_' . $payment_method_id ) ) ? strtoupper( str_replace( '_', '', get_option( 'woocommerce_gzd_trusted_shops_gateway_' . $payment_method_id ) ) ) : '';
	}

	/**
	 * Checks whether current Shop is rateable via Trusted Shops
	 *
	 * @return boolean
	 */
	public function is_rateable() {
		return ( $this->review_enable == 'yes' ) ? true : false;
	}

	/**
	 * Returns the average rating by grabbing the rating from the cache
	 *
	 * @return array
	 */
	public function get_average_rating() {
		return ( get_option( 'woocommerce_gzd_trusted_shops_reviews_cache' ) ) ? get_option( 'woocommerce_gzd_trusted_shops_reviews_cache' ) : array();
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
	 * Returns the rating link
	 *
	 * @return string
	 */
	public function get_rating_link() {
		return 'https://www.trustedshops.de/bewertung/info_' . $this->id . '.html';
	}

	/**
	 * Checks whether a badge should be inserted
	 *
	 * @return boolean
	 */
	public function enable_badge() {
		return ( get_option( 'woocommerce_gzd_trusted_shops_badge_show' ) == 'yes' ) ? true : false;
	}

	/**
	 * Returns the badge display variant
	 *
	 * @return string
	 */
	public function get_badge_variant() {
		return get_option( 'woocommerce_gzd_trusted_shops_badge_variant' );
	}

	/**
	 * Adds the Badge by implementing js code
	 */
	public function add_badge() {
		if ( $this->is_enabled() ) {
			echo "<script type='text/javascript'>
			    (function () {
			    var _tsid = '" . $this->id . "';
			    _tsConfig = {
			        'yOffset': '10', //offset from page bottom
			        'variant': '" . $this->get_badge_variant() . "' //text, default, small, reviews
			    };
			    var _ts = document.createElement('script');
			    _ts.type = 'text/javascript';
			    _ts.charset = 'utf-8';
			    _ts.src = '//widgets.trustedshops.com/js/' + _tsid + '.js';
			    var __ts = document.getElementsByTagName('script')[0];
			    __ts.parentNode.insertBefore(_ts, __ts);
			    })();
			</script>";
		}
	}

	/**
	 * Returns average rating rich snippet html
	 *
	 * @return string
	 */
	public function get_average_rating_html() {
		$rating = $this->get_average_rating();
		$html = '';
		if ( !empty( $rating ) && $this->is_enabled() && $this->is_rateable() ) {
			$html = '
				<div itemscope itemtype="http://data-vocabulary.org/Review-aggregate" class="wc-gzd-trusted-shops-rating-widget">
					<a href="' . $this->get_certificate_link() . '" target="_blank"><span itemprop="itemreviewed"><strong>' . get_bloginfo( 'name' ) . '</strong></span></a>
					<div class="star-rating" title="' . sprintf( _x( 'Rated %s out of %s', 'trusted-shops', 'woocommerce-germanized' ), $rating['avg'], (int) $rating['max'] ) . '">
						<span style="width:' . ( ( $rating['avg'] / 5 ) * 100 ) . '%">
							<strong class="rating">' . esc_html( $rating['avg'] ) . '</strong> ' . sprintf( _x( 'out of %s', 'trusted-shops', 'woocommerce-germanized' ), (int) $rating[ 'max' ] ) . '
						</span>
					</div>
					<br/>
					<span itemprop="rating" itemscope itemtype="http://data-vocabulary.org/Rating">
		         		' . sprintf( _x( '&#216; <span itemprop="average">%s</span> of <span itemprop="best">%s</span> based on <span class="count">%s</span> <a href="%s" target="_blank">ratings</a>.', 'trusted-shops', 'woocommerce-germanized' ), $rating['avg'], (int) $rating['max'], $rating['count'], $this->get_rating_link() ) . '
		    		</span>
		   		</div>
		   	';
		}
		return $html;
	}

	/**
	 * Update Review Cache by grabbing information from xml file
	 */
	public function update_reviews() {
		$update = array();

		if ( WC_germanized()->trusted_shops->is_enabled() ) {

			if ( function_exists( 'curl_version' ) ) {
				$success = false;
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_HEADER, false );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_POST, false );
				curl_setopt( $ch, CURLOPT_URL, WC_germanized()->trusted_shops->api_url );
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
	 * Get Trusted Shops related Settings for Admin Interface
	 *
	 * @return array
	 */
	public function get_settings() {

		$payment_options = array( '' => __( 'None', 'woocommerce-germanized' ) ) + $this->gateways;

		$options = array(

			array( 'title' => _x( 'Trusted Shops Integration', 'trusted-shops', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'trusted_shops_options' ),

			array(
				'title'  => _x( 'Shop ID', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Insert your Shop ID here.', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_trusted_shops_id',
				'type'   => 'text',
				'css'   => 'min-width:300px;',
			),

			array(
				'title'  => _x( 'Enable Reviews?', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Enable this option if you want to let your customers rate your Shop via Trusted Shops', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_review_enable',
				'type'   => 'checkbox',
				'default' => 'yes',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Show Badge?', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => sprintf( _x( 'Show a Trusted Shops Badge on the right side of your Website. For more info please visit <a href="%s" target="_blank">Trusted Shops</a>', 'trusted-shops', 'woocommerce-germanized' ), 'http://www.trustedshops.de/shopbetreiber/integration/trustbadge.html' ),
				'id'   => 'woocommerce_gzd_trusted_shops_badge_show',
				'type'   => 'checkbox',
				'default' => 'no',
				'autoload'  => false
			),

			array(
				'title'  => _x( 'Badge Variant', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'Choose a Badge Variant', 'trusted-shops', 'woocommerce-germanized' ),
				'id'   => 'woocommerce_gzd_trusted_shops_badge_variant',
				'css'   => 'min-width:250px;',
				'default' => 'default',
				'type'   => 'select',
				'class'  => 'chosen_select',
				'options' => array(
					'default'  => _x( 'Default', 'trusted-shops', 'woocommerce-germanized' ),
					'text'   => _x( 'Text', 'trusted-shops', 'woocommerce-germanized' ),
					'small'  => _x( 'Small', 'trusted-shops', 'woocommerce-germanized' ),
					'reviews'  => _x( 'Reviews', 'trusted-shops', 'woocommerce-germanized' )
				),
				'desc_tip' =>  true,
			),

			array(
				'title'  => _x( 'Review Reminder after', 'trusted-shops', 'woocommerce-germanized' ),
				'desc'   => _x( 'If a customer chooses to rate your Shop later, how many days should be gone until customer receives an reminder email sent by Trusted Shops?', 'trusted-shops', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'   => 'woocommerce_gzd_trusted_shops_review_reminder_days',
				'type'   => 'number',
				'default' => 7,
				'autoload'      => false
			),

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

}

?>
