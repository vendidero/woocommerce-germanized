<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZD_Dependencies {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $loadable = true;

	public $plugins = array();

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

		$this->plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() )
			$this->plugins = array_merge( $this->plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		
		if ( ! $this->is_woocommerce_activated() )
			$this->loadable = false;

	}

	public function get_plugin_version( $plugin_slug ) {
		return get_option( $plugin_slug . '_version' );
	}

	public function is_plugin_activated( $plugin ) {
		return in_array( $plugin, $this->plugins ) || array_key_exists( $plugin, $this->plugins );
	}

	/**
	 * Checks if WooCommerce is activated
	 *  
	 * @return boolean true if WooCommerce is activated
	 */
	public function is_woocommerce_activated() {
		return $this->is_plugin_activated( 'woocommerce/woocommerce.php' );
	}

	public function is_wpml_activated() {
		return $this->is_plugin_activated( 'sitepress-multilingual-cms/sitepress.php' );
	}

	public function is_loadable() {
		return $this->loadable;
	}

}

WC_GZD_Dependencies::instance();