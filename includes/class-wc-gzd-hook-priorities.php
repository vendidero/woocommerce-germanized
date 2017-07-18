<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZD_Hook_Priorities {

	/**
	 * Single instance of WC_GZD_Hook_Priorities
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $priorities = array();
	public $default_priorities = array();
	public $hooks = array();
	public $queue = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}
	
	public function __construct() {
		
		$this->init();
		
		add_action( 'after_setup_theme', array( $this, 'renew_cache' ), 1 );
		add_action( 'after_setup_theme', array( $this, 'change_priority_queue' ), 2 );
	}

	public function init() {
		// Default priorities used within WooCommerce (not customized by themes)
		$this->default_priorities = array(
			'woocommerce_single_product_summary' => array( 
				'woocommerce_template_single_price' => 10,
			),
			'woocommerce_checkout_order_review' => array(
				'woocommerce_order_review' 		=> 10,
				'woocommerce_checkout_payment'  => 20,
			),
			'woocommerce_thankyou' => array( 
				'woocommerce_order_details_table' => 10,
			),
		);

		$this->priorities = $this->default_priorities;

		// Load custom theme priorities
		if ( get_option( 'woocommerce_gzd_hook_priorities' ) )
			$this->priorities = (array) get_option( 'woocommerce_gzd_hook_priorities' );

		$this->hooks = array(
			'single_price_unit' 		            => $this->get_priority( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' ) + 1,
			'single_legal_info' 		            => $this->get_priority( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' ) + 2,
			'single_delivery_time_info'             => 27,
			'single_small_business_info'            => 30,
			'single_product_units'      	        => 5,
			'loop_price_unit'			            => 10,
			'loop_tax_info'				            => 6,
			'loop_shipping_costs_info'	 	        => 7,
			'loop_delivery_time_info'               => 8,
			'loop_product_units'			        => 9,
			'cart_subtotal_unit_price'		        => 0,
			'cart_product_unit_price'               => 0,
			'cart_product_differential_taxation'    => 9,
			'cart_product_units'	 		        => 10,
			'cart_product_delivery_time'	        => 11,
			'cart_product_item_desc' 		        => 12,
			'cart_small_business_info'		        => 0,
			'checkout_small_business_info'	        => 25,
			'checkout_product_units' 		        => 0,
			'checkout_product_delivery_time'        => 0,
			'checkout_product_item_desc'	        => 0,
			'checkout_edit_data_notice'             => 0,
			'checkout_payment'				        => 10,
			'checkout_order_review'			        => 20,
			'checkout_order_submit'			        => 21,
			'checkout_legal'				        => 2,
			'checkout_set_terms'			        => 3,
			'checkout_digital_checkbox'		        => 4,
			'checkout_service_checkbox'		        => 5,
			'checkout_direct_debit'			        => 6,
			'order_product_units'	 		        => 1,
			'order_product_delivery_time'	        => 2,
			'order_product_item_desc'		        => 3,
			'order_product_unit_price' 		        => 0,
			'order_pay_now_button'			        => 0,
			'email_product_differential_taxation'   => 0,
			'email_product_unit_price'		        => 1,
			'email_product_units'		 	        => 2,
			'email_product_delivery_time'	        => 3,
			'email_product_item_desc'		        => 4,
			'gzd_footer_vat_info'			        => 0,
			'footer_vat_info'				        => 5,
			'gzd_footer_sale_info'			        => 0,
			'footer_sale_info' 			 	        => 5,
		);

	}

	/**
	 * Returns the priority for critical hooks (see $this->priorities) which may be customized by a theme
	 */
	public function get_priority( $hook, $function ) {
		if ( isset( $this->priorities[ $hook ][ $function ] ) )
			return $this->priorities[ $hook ][ $function ];
		return false;
	}

	/**
	 * Returns the priority for a custom wc germanized frontend hook
	 */
	public function get_hook_priority( $hook, $suppress_filters = false ) {
		if ( isset( $this->hooks[ $hook ] ) )
			return ( ! $suppress_filters ? apply_filters( 'wc_gzd_frontend_hook_priority', $this->hooks[ $hook ], $hook, $this ) : $this->hooks[ $hook ] );
		return false;
	}

	public function get_hook_priorities() {
		return $this->hooks;
	}

	/**
	 * This changes the hook priority by overriding customizations made by the theme
	 */
	public function change_priority( $hook, $function, $new_prio ) {
		if ( ! $this->get_priority( $hook, $function ) )
			return false;
		$this->queue[] = array( 'hook' => $hook, 'function' => $function, 'new_prio' => $new_prio );
	}

	/**
	 * Hooked by after_setup_theme. Not to be called directly
	 */
	public function change_priority_queue() {
		if ( empty( $this->queue ) )
			return false;
		foreach ( $this->queue as $queue ) {
			remove_action( $queue[ 'hook' ], $queue[ 'function' ], $this->get_priority( $queue[ 'hook' ], $queue[ 'function' ] ) );
			add_action( $queue[ 'hook' ], $queue[ 'function' ], $queue[ 'new_prio' ] );
		}
	}

	/**
	 * Regenerates the hook priority cache (checks for theme customizations)
	 */
	public function renew_cache() {
		$this->priorities = $this->default_priorities;

		if ( ! empty( $this->priorities ) ) {
			
			foreach ( $this->priorities as $hook => $functions ) {

				foreach ( $functions as $function => $old_prio ) {
					
					$prio = has_action( $hook, $function );
					
					if ( ! $prio )
						$prio = has_filter( $hook, $function );
					
					if ( $prio )
						$this->priorities[ $hook ][ $function ] = $prio;
				}				
			} 
		}

		if ( ! empty( $this->priorities ) )
			update_option( 'woocommerce_gzd_hook_priorities', $this->priorities );
		else
			delete_option( 'woocommerce_gzd_hook_priorities' );

	}

}

WC_GZD_Hook_Priorities::instance();