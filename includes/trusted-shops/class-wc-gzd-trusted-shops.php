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
	 * TS ID
	 * @var mixed
	 */
	public $id;
	
	/**
	 * Trusted Shops URL Parameters 
	 * @var array
	 */
	public $et_params = array();

	/**
	 * Trusted Shops Signup URL Parameters
	 * @var array
	 */
	public $signup_params = array();

	/**
	 * Class Prefix for auto dependency loading
	 * @var string
	 */
	public $prefix = '';

	/**
	 * Option prefix for DB strings
	 * @var string
	 */
	public $option_prefix = '';

	/**
	 * Support functionality
	 * @var array
	 */
	public $supports = array();

	/**
	 * Admin URLs
	 * @var array
	 */
	public $urls = array();
	
	/**
	 * WooCommerce Gateway mapping
	 * @var array
	 */
	public $gateways = array();

	/**
	 * Parent Plugin
	 * @var null
	 */
	public $plugin = null;

	/**
	 * Trusted Shops Version
	 * @var string
	 */
	public $version = '1.1.0';

	/**
	 * API URL for review collection
	 * @var string
	 */
	public $api_url;

	/**
	 * Sets Trusted Shops payment gateways and load dependencies
	 */
	public function __construct( $plugin, $params = array() ) {
		
		$this->plugin = $plugin;

		$args = wp_parse_args( $params, array(
			'et_params' 	=> array(),
			'signup_params' => array(),
			'prefix' 		=> '',
			'urls' 			=> array(),
			'supports' 		=> array( 'reminder' ),
		) );

		foreach ( $args as $arg => $val ) {
			$this->$arg = $val;
		}

		$this->option_prefix = strtolower( $this->prefix );

		// Refresh TS ID + API URL
		$this->refresh();

		if ( is_admin() )
			$this->get_dependency( 'admin' );

		$this->get_dependency( 'schedule' );
		$this->get_dependency( 'shortcodes' );
		$this->get_dependency( 'widgets' );
		$this->get_dependency( 'template_hooks' );

		if ( $this->is_enabled() )
			add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_assets' ), 50 );

		add_action( 'init', array( $this, 'setup_payment_options' ) );

	}

	public function setup_payment_options() {
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
	}

	public function load_frontend_assets() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = $this->plugin->plugin_url() . '/assets/css';
		$script_prefix = str_replace( '_', '-', $this->option_prefix );

		wp_register_style( 'woocommerce-' . $script_prefix . 'trusted-shops', $assets_path . '/woocommerce-' . $script_prefix . 'trusted-shops' . $suffix . '.css', false, $this->plugin->version );
		wp_enqueue_style( 'woocommerce-' . $script_prefix . 'trusted-shops' );

	}

	public function get_dependency_name( $name ) {
		$classname = 'WC_' . $this->prefix . 'Trusted_Shops_' . ucwords( str_replace( '-', '_', strtolower( $name ) ) );
		return $classname;
	}

	public function get_dependency( $name ) {
		$classname = $this->get_dependency_name( $name );
		return call_user_func_array( array( $classname, 'instance' ), array( $this ) );
	}

	public function refresh() {
		$this->id = get_option( 'woocommerce_' . $this->option_prefix . 'trusted_shops_id' );
		$this->api_url = 'http://api.trustedshops.com/rest/public/v2/shops/'. $this->id .'/quality.json';
	}

	/**
	 * Get Trusted Shops Options
	 *
	 * @param string  $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return get_option( 'woocommerce_' . $this->option_prefix . 'trusted_shops_' . $key );
	}

	/**
	 * Checks whether a certain Trusted Shops Option isset
	 *
	 * @param string  $key
	 * @return boolean
	 */
	public function __isset( $key ) {
		return ( ! get_option( 'woocommerce_' . $this->option_prefix . 'trusted_shops_' . $key ) ) ? false : true;
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
		return ( $this->review_reminder_enable === 'yes' && $this->supports( 'reminder' ) && $this->is_enabled() ? true : false );
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

	public function supports( $type ) {
		return ( in_array( $type, $this->supports ) ? true : false );
	}

	/**
	 * Gets Trusted Shops payment gateway by woocommerce payment id
	 *
	 * @param integer $payment_method_id
	 * @return string
	 */
	public function get_payment_gateway( $payment_method_id ) {
		return ( $this->{"gateway_$payment_method_id"} ? strtoupper( $this->{"gateway_$payment_method_id"} ) : '' );
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

		if ( $this->integration_mode === 'expert' )
			$script = $this->{$name . "_code"};

		if ( $replace ) {

			$args = wp_parse_args( $args, array(
				'id' => $this->id,
				'locale' => $this->get_locale(),
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
				'element' => '#ts_product_widget',
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
				'variant' => $this->trustbadge_variant === 'standard' ? 'reviews' : 'default',
				'disable' => $this->trustbadge_variant === 'disable' ? 'true' : 'false',
			) );

		}

		return $this->get_script( 'trustbadge', $replace, $args );
	}

	public function get_locale() {

		$supported = array(
			'de' => 'de_DE',
			'en' => 'en_GB',
			'fr' => 'fr_FR',
			'es' => 'es_ES',
			'it' => 'it_IT',
			'nl' => 'nl_NL',
			'pl' => 'pl_PL',
		);

		$locale = 'en_GB';
		$base = substr( get_locale(), 0, 2 );

		if ( isset( $supported[ $base ] ) )
			$locale = $supported[ $base ];

		return $locale;

	}

}

?>
