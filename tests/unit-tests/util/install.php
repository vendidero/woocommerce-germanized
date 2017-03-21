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

		WC_GZD_Install::install();

		$this->assertTrue( get_option( 'woocommerce_gzd_version' ) === WC_germanized()->version );
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