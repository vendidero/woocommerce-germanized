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

			if ( ! empty( $_GET['install_woocommerce_gzd_tax_rates'] ) )
				self::create_tax_rates();

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

		} elseif ( ! empty( $_GET['skip_update_woocommerce_gzd'] ) ) {

			// We no longer need to install pages
			delete_option( '_wc_gzd_needs_update' );
			delete_option( '_wc_gzd_needs_pages' );
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
		// Load Translation for default options
		$locale = apply_filters( 'plugin_locale', get_locale() );
		$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized.mo';
		if ( file_exists( WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo' ) )
			$mofile = WC_germanized()->plugin_path() . '/i18n/languages/woocommerce-germanized-' . $locale . '.mo';
		load_textdomain( 'woocommerce-germanized', $mofile );
		if ( ! WC_germanized()->is_woocommerce_activated() ) {
			deactivate_plugins( WC_GERMANIZED_PLUGIN_FILE );
			wp_die( sprintf( __( 'Please install <a href="%s" target="_blank">WooCommerce</a> before installing WooCommerce Germanized. Thank you!', 'woocommerce-germanized' ), 'http://wordpress.org/plugins/woocommerce/' ) );
		}
		$this->create_options();
		// Register post types
		include_once( 'class-wc-gzd-post-types.php' );
		WC_GZD_Post_types::register_taxonomies();

		$this->create_cron_jobs();

		// Virtual Tax Classes
		$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );
		if ( ! in_array( 'Virtual Rate', $tax_classes ) || ! in_array( 'Virtual Reduced Rate', $tax_classes ) ) {
			update_option( '_wc_gzd_needs_pages', 1 );
			if ( ! in_array( 'Virtual Rate', $tax_classes ) )
				array_push( $tax_classes, 'Virtual Rate' );
			if ( ! in_array( 'Virtual Reduced Rate', $tax_classes ) )
				array_push( $tax_classes, 'Virtual Reduced Rate' );
			update_option( 'woocommerce_tax_classes', implode( "\n", $tax_classes ) );
		}

		// Queue upgrades
		$current_version    = get_option( 'woocommerce_gzd_version', null );
		$current_db_version = get_option( 'woocommerce_gzd_db_version', null );

		if ( version_compare( $current_db_version, '1.0.4', '<' ) && null !== $current_db_version )
			update_option( '_wc_gzd_needs_update', 1 );
		else
			update_option( 'woocommerce_gzd_db_version', WC_germanized()->version );

		// Update version
		update_option( 'woocommerce_gzd_version', WC_germanized()->version );

		// Update activation date
		update_option( 'woocommerce_gzd_activation_date', date( 'Y-m-d' ) );

		// Add theme compatibility check
		delete_option( '_wc_gzd_hide_theme_notice' );

		delete_option( '_wc_gzd_hide_review_notice' );

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
		if ( ! empty( $_GET['install_woocommerce_gzd_tax_rates'] ) )
			self::create_tax_rates();

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

	public static function create_tax_rates() {

		global $wpdb;

		// Delete digital rates
		$wpdb->delete( $wpdb->prefix . 'woocommerce_tax_rates', array( 'tax_rate_class' => 'virtual-rate' ), array( '%s' ) );

		$rates = array(
			'BE' => 21,
			'BG' => 20,
			'CZ' => 21,
			'DK' => 25,
			'DE' => 19,
			'EE' => 20,
			'EL' => 23,
			'ES' => 21,
			'FR' => 20,
			'HR' => 25,
			'IE' => 23,
			'IT' => 22,
			'CY' => 19,
			'LV' => 21,
			'LT' => 21,
			'LU' => 15,
			'HU' => 27,
			'MT' => 18,
			'NL' => 21,
			'AT' => 20,
			'PL' => 23,
			'PT' => 23,
			'RO' => 24,
			'SI' => 22,
			'SK' => 20,
			'FI' => 24,
			'SE' => 25,
			'UK' => 20,
		);

		if ( ! empty( $rates ) ) {
			$count = 0;
			foreach ( $rates as $iso => $rate ) {
				$_tax_rate = array(
					'tax_rate_country'  => $iso,
					'tax_rate_state'    => '',
					'tax_rate'          => (string) number_format( (double) wc_clean( $rate ), 4, '.', '' ),
					'tax_rate_name'     => 'MwSt. ' . $iso . ' virtual',
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 0,
					'tax_rate_order'    => $count++,
					'tax_rate_class'    => 'virtual-rate'
				);
				// Check if standard rate exists
				if ( WC()->countries->get_base_country() == $iso ) {
					$base_rate = WC_Tax::get_shop_base_rate();
					$base_rate = reset( $base_rate );
					if ( ! empty( $base_rate ) )
						$_tax_rate[ 'tax_rate_name' ] = $base_rate[ 'label' ];
				}
				$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );
				$tax_rate_id = $wpdb->insert_id;
				do_action( 'woocommerce_tax_rate_added', $tax_rate_id, $_tax_rate );
			}
		}
		// Clear tax transients
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_wc_tax_rates%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_timeout_wc_tax_rates%' ) );
	}

	/**
	 * Updates WooCommerce Options if user chooses to automatically adapt german options
	 */
	public static function set_default_settings() {
		global $wpdb;

		$options = array(
			'woocommerce_default_country' 			 => 'DE',
			'woocommerce_currency' 					 => 'EUR',
			'woocommerce_currency_pos'				 => 'right_space',
			'woocommerce_price_thousand_sep' 	     => '.',
			'woocommerce_price_decimal_sep'     	 => ',',
			'woocommerce_price_num_decimals'		 => 2,
			'woocommerce_weight_unit'				 => 'kg',
			'woocommerce_dimension_unit'			 => 'cm',
			'woocommerce_calc_taxes'				 => 'yes',
			'woocommerce_prices_include_tax'		 => 'yes',
			'woocommerce_tax_display_cart'			 => 'incl',
			'woocommerce_tax_display_shop'			 => 'incl',
			'woocommerce_allowed_countries'	    	 => 'specific',
			'woocommerce_specific_allowed_countries' => array( 'DE' ),
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
			'tax_rate'          => number_format( (double) wc_clean( 19.0 ), 4, '.', '' ),
			'tax_rate_name'     => 'MwSt.',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => '',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => 1,
			'tax_rate_class'    => ''
		);
		$exists = $wpdb->get_results ( 'SELECT tax_rate_id FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . ' WHERE tax_rate LIKE "19%"' );
		if ( empty( $exists ) )
			$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		$_tax_rate[ 'tax_rate' ] = number_format( (double) wc_clean( 7.0 ), 4, '.', '' );
		$_tax_rate[ 'tax_rate_class' ] = 'reduced-rate';
		$_tax_rate[ 'tax_rate_name' ] = 'MwSt. 7%';

		$exists = $wpdb->get_results ( 'SELECT tax_rate_id FROM ' . $wpdb->prefix . 'woocommerce_tax_rates' . ' WHERE tax_rate LIKE "7%"' );
		if ( empty( $exists ) )
			$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		// Clear tax transients
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_wc_tax_rates%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s;", '_transient_timeout_wc_tax_rates%' ) );
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
				'name'    => _x( 'shipping-methods', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Shipping Methods', 'Page title', 'woocommerce-germanized' ),
				'content' => ''
			),
			'payment_methods' => array(
				'name'    => _x( 'payment-methods', 'Page slug', 'woocommerce-germanized' ),
				'title'   => _x( 'Payment Methods', 'Page title', 'woocommerce-germanized' ),
				'content' => '[' . apply_filters( 'woocommerce_gzd_payment_methods_shortcode_tag', 'woocommerce_gzd_payment_methods' ) . ']'
			),
		) );

		foreach ( $pages as $key => $page ) {
			wc_create_page( esc_sql( $page['name'] ), 'woocommerce_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '' );
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
		$options = array_merge( $settings->get_settings(), $settings->get_display_settings() );

		foreach ( $options as $value ) {
			if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
				$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
				add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
			}
		}
	}

}

endif;

return new WC_GZD_Install();
