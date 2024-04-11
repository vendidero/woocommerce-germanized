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
	 * Test check version.
	 */
	public function test_version_compare() {
		$this->assertTrue( \Vendidero\Germanized\PluginsHelper::compare_versions( '3.1.3', '3.1', '>' ) );
		$this->assertFalse( \Vendidero\Germanized\PluginsHelper::compare_versions( '3.0.9', '3.1', '>' ) );

		$this->assertTrue( \Vendidero\Germanized\PluginsHelper::compare_versions( '2.9.9', '3.0', '<' ) );
		$this->assertFalse( \Vendidero\Germanized\PluginsHelper::compare_versions( '2.9.9', '2.9', '<' ) );
		$this->assertTrue( \Vendidero\Germanized\PluginsHelper::compare_versions( '2.9.9', '3.0', '<' ) );
	}

	/**
	 * Test major version
	 */
	public function test_major_version() {
		$this->assertEquals( '3.10', \Vendidero\Germanized\PluginsHelper::get_major_version( '3.10.1' ) );
		$this->assertEquals( '3.10', \Vendidero\Germanized\PluginsHelper::get_major_version( '3.10' ) );
		$this->assertEquals( '3.0', \Vendidero\Germanized\PluginsHelper::get_major_version( '3.0.0' ) );
		$this->assertEquals( '3.1', \Vendidero\Germanized\PluginsHelper::get_major_version( '3.1.8' ) );
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
		$this->assertEquals( array( 'customer_processing_order', 'customer_new_account', 'customer_new_account_activation' ), get_option( 'woocommerce_gzd_mail_attach_terms' ) );

		// Check if package settings are added too (e.g. DHL, Shipments)
		$this->assertEquals( 'yes', get_option( 'woocommerce_gzd_shipments_auto_enable' ) );

		$config_set = wc_gzd_get_shipping_provider( 'dhl' )->get_configuration_set( array( 'zone' => 'dom', 'shipment_type' => 'simple' ) );
		$this->assertEquals( 'V01PAK', $config_set->get_product() );

		// Check if Tables are installed
		global $wpdb;

		// Shipments
		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_gzd_shipments'" );
		$this->assertEquals( "{$wpdb->prefix}woocommerce_gzd_shipments", $table_name );

		// DHL
		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_gzd_dhl_im_products'" );
		$this->assertEquals( "{$wpdb->prefix}woocommerce_gzd_dhl_im_products", $table_name );

		remove_filter( 'plugin_locale', array( $this, 'set_locale' ), 10 );
	}

	/**
	 * Test - install.
	 */
	public function test_install_non_built_in_providers() {
		// clean existing installation first
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_GZD_REMOVE_ALL_DATA', true );
		}

		include( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php' );

		remove_all_filters( 'woocommerce_gzd_shipping_provider_class_names' );

		update_option( 'woocommerce_gzd_shipments_shipper_address_country', 'AT' );
		update_option( 'woocommerce_gzd_dhl_version', '1.0' );
		update_option( 'woocommerce_default_country', 'AT' );

		var_dump( \Vendidero\Germanized\Shipments\Package::get_base_country() );
		var_dump( \Vendidero\Germanized\DHL\Package::has_dependencies() );

		WC_GZD_Install::install();

		$this->assertEquals( false, wc_gzd_get_shipping_provider( 'dhl' ) );
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