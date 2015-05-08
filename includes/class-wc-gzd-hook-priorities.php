<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZD_Hook_Priorities {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $priorities = array();
	public $hooks = array();

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
		
		add_action( 'woocommerce_before_main_content', array( $this, 'renew_cache' ) );
	}

	public function init() {
		$this->priorities = array(
			'woocommerce_single_product_summary' => array( 
				'woocommerce_template_single_price' => 10,
			),
		);

		if ( get_option( 'woocommerce_gzd_hook_priorities' ) )
			$this->priorities = (array) get_option( 'woocommerce_gzd_hook_priorities' );

		$this->hooks = array(
			'single_price_unit' 		=> $this->get_priority( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' ) + 1,
			'single_legal_info' 		=> $this->get_priority( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' ) + 2,
		);
	}

	public function get_priority( $hook, $function ) {
		if ( isset( $this->priorities[ $hook ][ $function ] ) )
			return $this->priorities[ $hook ][ $function ];
		return false;
	}

	public function get_hook_priority( $hook ) {
		if ( isset( $this->hooks[ $hook ] ) )
			return $this->hooks[ $hook ];
		return false;
	}

	public function renew_cache() {
		global $wp_filter;

		if ( ! empty( $this->priorities ) ) {
			foreach ( $this->priorities as $hook => $functions ) {

				if ( ! is_array( $functions ) || ! isset( $wp_filter[ $hook ] ) )
					continue;

				foreach ( $wp_filter[ $hook ] as $prio => $func ) {
					
					foreach ( $functions as $function => $old_prio ) {
						
						if ( isset( $func[ $function ] ) ) {
							$this->priorities[ $hook ][ $function ] = $prio;
							break;
						}
					}
				}
			} 
		}
		if ( ! empty( $this->priorities ) )
			update_option( 'woocommerce_gzd_hook_priorities', $this->priorities );
		else
			delete_option( 'woocommerce_gzd_hook_priorities' );
		$this->init();
	}

}

WC_GZD_Hook_Priorities::instance();