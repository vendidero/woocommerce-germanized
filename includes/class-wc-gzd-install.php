<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Install' ) ) :

/**
 * Installation related functions and hooks
 *
 * @class 		WC_GZD_Install
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Install {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		register_activation_hook( WC_GERMANIZED_PLUGIN_FILE, array( $this, 'install' ) );
		add_action( 'admin_init', array( $this, 'install_actions' ) );
		add_action( 'admin_init', array( $this, 'check_version' ), 5 );
	}

	/**
	 * check_version function.
	 *
	 * @access public
	 * @return void
	 */
	public function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_gzd_version' ) != WC_germanized()->version || get_option( 'woocommerce_gzd_db_version' ) != WC_germanized()->version ) ) {
			$this->install();
			do_action( 'woocommerce_gzd_updated' );
		}
	}

	/**
	 * Install actions such as installing pages when a button is clicked.
	 */
	public function install_actions() {
		// Install - Add pages button
		if ( ! empty( $_GET['install_woocommerce_gzd'] ) ) {

			if ( ! empty( $_GET['install_woocommerce_gzd_pages'] ) )
				self::create_pages();

			if ( ! empty( $_GET['install_woocommerce_gzd_settings'] ) )
				self::set_default_settings();

			// We no longer need to install pages
			delete_option( '_wc_gzd_needs_pages' );
			delete_transient( '_wc_gzd_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wc-gzd-about&wc-gzd-installed=true' ) );
			exit;

		// Skip button
		} elseif ( ! empty( $_GET['skip_install_woocommerce_gzd'] ) ) {

			// We no longer need to install pages
			delete_option( '_wc_gzd_needs_pages' );
			delete_transient( '_wc_gzd_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wc-gzd-about' ) );
			exit;

		// Update button
		} elseif ( ! empty( $_GET['do_update_woocommerce_gzd'] ) ) {

			$this->update();

			// Update complete
			delete_option( '_wc_gzd_needs_pages' );
			delete_option( '_wc_gzd_needs_update' );
			delete_transient( '_wc_gzd_activation_redirect' );

			// What's new redirect
			wp_redirect( admin_url( 'index.php?page=wc-gzd-about&wc-gzd-updated=true' ) );
			exit;
		}
	}

	/**
	 * Install WC_Germanized
	 */
	public function install() {
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			deactivate_plugins( WC_GERMANIZED_PLUGIN_FILE );
			wp_die( sprintf( __( 'Please install <a href="%s" target="_blank">WooCommerce</a> before installing WooCommerce Germanized. Thank you!', 'woocommerce-germanized' ), 'http://wordpress.org/plugins/woocommerce/' ) );
		}
		$this->create_options();
		// Register post types
		include_once( 'class-wc-gzd-post-types.php' );
		WC_GZD_Post_types::register_taxonomies();

		$this->create_cron_jobs();

		// Queue upgrades
		$current_version    = get_option( 'woocommerce_gzd_version', null );
		$current_db_version = get_option( 'woocommerce_gzd_db_version', null );

		update_option( 'woocommerce_gzd_db_version', WC_germanized()->version );

		// Update version
		update_option( 'woocommerce_gzd_version', WC_germanized()->version );

		// Check if pages are needed
		if ( wc_get_page_id( 'revocation' ) < 1 ) {
			update_option( '_wc_gzd_needs_pages', 1 );
		}

		// Flush rules after install
		flush_rewrite_rules();

		// Redirect to welcome screen
		set_transient( '_wc_gzd_activation_redirect', 1, 60 * 60 );
	}

	/**
	 * Handle updates
	 */
	public function update() {
		// Do updates
		$current_db_version = get_option( 'woocommerce_gzd_db_version' );
		update_option( 'woocommerce_gzd_db_version', WC_germanized()->version );
	}

	/**
	 * Create cron jobs (clear them first)
	 */
	private function create_cron_jobs() {
		// Cron jobs
		wp_clear_scheduled_hook( 'woocommerce_gzd_trusted_shops_reviews' );
		wp_schedule_event( time(), 'twicedaily', 'woocommerce_gzd_trusted_shops_reviews' );
		
		wp_clear_scheduled_hook( 'woocommerce_gzd_ekomi' );
		wp_schedule_event( time(), 'daily', 'woocommerce_gzd_ekomi' );
	}

	/**
	 * Updates WooCommerce Options if user chooses to automatically adapt german options
	 */
	public static function set_default_settings() {
		global $wpdb;

		$options = array(
			'woocommerce_default_country' 			 => 'DE',
			'woocommerce_currency' 					 => 'EUR',
			'woocommerce_price_thousand_sep' 	     => '.',
			'woocommerce_price_decimal_sep'     	 => ',',
			'woocommerce_price_num_decimals'		 => 2,
			'woocommerce_weight_unit'				 => 'kg',
			'woocommerce_dimension_unit'			 => 'cm',
			'woocommerce_calc_taxes'				 => 1,
			'woocommerce_prices_include_tax'		 => 'yes',
			'woocommerce_tax_display_cart'			 => 'incl',
			'woocommerce_allowed_countries'	    	 => 'specific',
			'woocommerce_specific_allowed_countries' => 'DE',
		);
		if ( !empty($options ) ) {
			foreach ( $options as $key => $option ) {
				update_option( $key, $option );
			}
		}
		// Tax Rates
		$_tax_rate = array(
			'tax_rate_country'  => 'DE',
			'tax_rate_state'    => '',
			'tax_rate'          => 19.0,
			'tax_rate_name'     => 'Mwst.',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => '',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => 1,
			'tax_rate_class'    => ''
		);
		$exists = $wpdb->get_results ( 'SELECT tax_rate_id FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . ' WHERE tax_rate = 19' );
		if ( empty( $exists ) )
			$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		$_tax_rate[ 'tax_rate' ] = 7.0;
		$_tax_rate[ 'tax_rate_class' ] = 'reduced-rate';
		$_tax_rate[ 'tax_rate_name' ] = 'Mwst. 7%';

		$exists = $wpdb->get_results ( 'SELECT tax_rate_id FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . ' WHERE tax_rate = 7' );
		if ( empty( $exists ) )
			$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );
 	}

	/**
	 * Create pages that the plugin relies on, storing page id's in variables.
	 *
	 * @access public
	 * @return void
	 */
	public static function create_pages() {
		$pages = apply_filters( 'woocommerce_gzd_create_pages', array(
			'data_security' => array(
				'name'    => _x( 'data-security', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Data Security Statement', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'imprint' => array(
				'name'    => _x( 'imprint', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Imprint', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'terms' => array(
				'name'    => _x( 'terms', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Terms & Conditions', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'revocation' => array(
				'name'    => _x( 'revocation', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Power of Revocation', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'shipping_costs' => array(
				'name'    => _x( 'shipping-costs', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Shipping Costs', 'Page title', 'woocommerce-germanized' ),
				'content' => '[' . apply_filters( 'woocommerce_gzd_shipping_costs_shortcode_tag', 'woocommerce_gzd_shipping_costs' ) . ']'
			),
			'payment_methods' => array(
				'name'    => _x( 'payment-methods', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Payment Methods', 'Page title', 'woocommerce' ),
				'content' => '[' . apply_filters( 'woocommerce_gzd_payment_methods_shortcode_tag', 'woocommerce_gzd_payment_methods' ) . ']'
			),
			'shipping_methods' => array(
				'name'    => _x( 'shipping-methods', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Shipping Methods', 'Page title', 'woocommerce' ),
				'content' => ''
			),
		) );

		foreach ( $pages as $key => $page ) {
			wc_create_page( esc_sql( $page['name'] ), 'woocommerce_gzd_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '' );
		}

	}

	/**
	 * Default options
	 *
	 * Sets up the default options used on the settings page
	 *
	 * @access public
	 */
	function create_options() {
		// Include settings so that we can run through defaults
		include_once( WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php' );
		include_once( 'admin/settings/class-wc-gzd-settings-germanized.php' );

		$settings = new WC_GZD_Settings_Germanized();

		foreach ( $settings->get_settings() as $value ) {
			if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
				$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
			}
		}
	}

}

endif;

return new WC_GZD_Install();
