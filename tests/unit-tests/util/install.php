<?php

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 */
class WC_GZD_Tests_Install extends WC_GZD_Unit_Test_Case {

	/**
	 * Test check version.
	 */
	public function test_check_version() {
		update_option( 'woocommerce_gzd_version', ( (float) WC_germanized()->version - 1 ) );
		update_option( 'woocommerce_gzd_db_version', WC_germanized()->version );
		WC_GZD_Install::check_version();

		$this->assertTrue( did_action( 'woocommerce_gzd_updated' ) === 1 );

		update_option( 'woocommerce_gzd_version', WC_germanized()->version );
		update_option( 'woocommerce_gzd_db_version', WC_germanized()->version );
		WC_Install::check_version();

		$this->assertTrue( did_action( 'woocommerce_gzd_updated' ) === 1 );
	}

	/**
	 * Test - install.
	 */
	public function test_install() {

		// clean existing install first
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_GZD_REMOVE_ALL_DATA', true );
		}

		include( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php' );

		add_filter( 'plugin_locale', array( $this, 'set_locale' ), 10, 2 );

		WC_GZD_Install::install();

		$this->assertTrue( get_option( 'woocommerce_gzd_version' ) === WC_germanized()->version );
		$this->assertEquals( 'Jetzt kaufen', get_option( 'woocommerce_gzd_order_submit_btn_text' ) );

		// Check if package settings are added too (e.g. DHL, Shipments)
		$this->assertEquals( 'yes', get_option( 'woocommerce_gzd_shipments_auto_enable' ) );
		$this->assertEquals( 'V01PAK', get_option( 'woocommerce_gzd_dhl_label_default_product_dom' ) );

		// Check if Tables are installed
		global $wpdb;

		// Shipments
		$table_name = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_gzd_shipments'" );
		$this->assertEquals( "{$wpdb->prefix}woocommerce_gzd_shipments", $table_name );

		// DHL
		$table_name = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_gzd_dhl_labels'" );
		$this->assertEquals( "{$wpdb->prefix}woocommerce_gzd_dhl_labels", $table_name );

		remove_filter( 'plugin_locale', array( $this, 'set_locale' ), 10 );
	}

	public function set_locale( $locale, $textdomain ) {
		if ( 'woocommerce-germanized' === $textdomain ) {
			return 'de_DE';
		}
		return $locale;
	}

	/**
	 * Test - create pages.
	 */
	public function test_create_pages() {
		// Clear options
		delete_option( 'woocommerce_revocation_page_id' );
		delete_option( 'woocommerce_terms_page_id' );
		delete_option( 'woocommerce_shipping_costs_page_id' );
		delete_option( 'woocommerce_payment_methods_page_id' );
		delete_option( 'woocommerce_imprint_page_id' );
		delete_option( 'woocommerce_data_security_page_id' );

		WC_GZD_Install::create_pages();

		$this->assertGreaterThan( 0, get_option( 'woocommerce_revocation_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_terms_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_shipping_costs_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_payment_methods_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_imprint_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_data_security_page_id' ) );
	}
}