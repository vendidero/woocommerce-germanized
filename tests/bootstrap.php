<?php
/**
 * WooCommerce Unit Tests Bootstrap
 *
 * @since 2.2
 */
class WC_GZD_Unit_Tests_Bootstrap {

	/** @var WC_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	public $wc_plugin_dir;

	public $wc_tests_dir;

	public $woo_tests_dir;

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 2.2
	 */
	public function __construct() {

		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );

		// Ensure server variable is set for WP email functions.
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}

		$this->tests_dir     = dirname( __FILE__ );
		$this->plugin_dir    = dirname( $this->tests_dir );

		$this->wp_tests_dir  = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) :  $this->plugin_dir . '/tmp/wordpress-tests-lib';
		$this->woo_tests_dir  = getenv( 'WOO_TESTS_DIR' ) ? getenv( 'WOO_TESTS_DIR' ) :  $this->plugin_dir . '/tmp/woocommerce';

		$this->wc_plugin_dir = $this->woo_tests_dir . '/';
		$this->wc_tests_dir  = $this->wc_plugin_dir . 'tests';

		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );

		tests_add_filter( 'woocommerce_gzd_dependencies_instance', array( $this, 'mock_dependencies' ) );

		// load WC
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_wc_germanized' ) );

		// install WC
		tests_add_filter( 'init', array( $this, 'install_wc_germanized' ) );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load WC testing framework
		$this->includes();
	}

	public function mock_dependencies() {
		// load the WP testing environment
		require_once( $this->tests_dir . '/framework/class-wc-gzd-dependencies-mock.php' );
		return WC_GZD_Dependencies_Mock::instance();
	}

	/**
	 * Load WooCommerce.
	 *
	 * @since 2.2
	 */
	public function load_wc_germanized() {
		require_once( $this->wc_plugin_dir . '/woocommerce.php' );
		require_once( $this->plugin_dir . '/woocommerce-germanized.php' );
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded.
	 *
	 * @since 2.2
	 */
	public function install_wc_germanized() {

		global $wp_roles;

		// clean existing install first
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );

		include( $this->wc_plugin_dir . '/uninstall.php' );

		WC_Install::install();

		require_once( $this->plugin_dir . '/woocommerce-germanized.php' );

		define( 'WC_GZD_REMOVE_ALL_DATA', true );
		include( $this->plugin_dir . '/uninstall.php' );

		WC_GZD_Install::install();

		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$wp_roles = new WP_Roles();

		echo "Installing WooCommerce and Germanized..." . PHP_EOL;
	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @since 2.2
	 */
	public function includes() {

		// factories
		require_once( $this->tests_dir . '/framework/factories/class-wc-gzd-unit-test-factory-for-webhook.php' );
		require_once( $this->tests_dir . '/framework/factories/class-wc-gzd-unit-test-factory-for-webhook-delivery.php' );

		// framework
		require_once( $this->tests_dir . '/framework/class-wc-gzd-unit-test-factory.php' );
		require_once( $this->tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php' );

		// test cases
		require_once( $this->tests_dir . '/framework/class-wc-gzd-unit-test-case.php' );
		require_once( $this->tests_dir . '/framework/class-wc-gzd-rest-unit-test-case.php' );

		// Helpers
		require_once( $this->wc_tests_dir . '/framework/helpers/class-wc-helper-product.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-gzd-helper-product.php' );

		require_once( $this->wc_tests_dir . '/framework/helpers/class-wc-helper-customer.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-gzd-helper-customer.php' );

		require_once( $this->wc_tests_dir . '/framework/helpers/class-wc-helper-shipping.php' );
		require_once( $this->wc_tests_dir . '/framework/helpers/class-wc-helper-order.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-gzd-helper-order.php' );
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 2.2
	 * @return WC_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

WC_GZD_Unit_Tests_Bootstrap::instance();
