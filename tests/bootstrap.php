<?php
/**
 * WooCommerce Unit Tests Bootstrap
 *
 * @since 2.2
 */
class WC_GZD_Unit_Tests_Bootstrap {

	/** @var WC_GZD_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	public $wp_plugin_dir;

	public $wc_tests_dir;

	public $packages = array(
		'woocommerce-germanized-shipments/woocommerce-germanized-shipments.php' => '\Vendidero\Germanized\Shipments',
		'woocommerce-germanized-dhl/woocommerce-germanized-dhl.php'             => '\Vendidero\Germanized\DHL',
		'woocommerce-trusted-shops/woocommerce-trusted-shops.php'               => '\Vendidero\TrustedShops'
	);

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 2.2
	 */
	public function __construct() {
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );

		if ( file_exists( dirname( $this->plugin_dir ) . '/woocommerce/woocommerce.php' ) ) {
			// From plugin directory.
			$this->plugins_dir = dirname( $this->plugin_dir );
		} else {
			// Travis.
			$this->plugins_dir = getenv( 'WP_CORE_DIR' ) . '/wp-content/plugins';
		}

		$this->wc_tests_dir = $this->plugins_dir . '/woocommerce/tests';

		$this->setup_hooks();
		$this->includes();
	}

	protected function is_separate_package( $package ) {
		if ( file_exists( $this->plugins_dir . '/' . $package ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Setup hooks.
	 */
	protected function setup_hooks() {
		// Give access to tests_add_filter() function.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		tests_add_filter( 'woocommerce_gzd_dependencies_instance', function() {
			require_once( $this->tests_dir . '/framework/class-wc-gzd-dependencies-mock.php' );

			return WC_GZD_Dependencies_Mock::instance();
		} );

		tests_add_filter( 'muplugins_loaded', function() {
			require_once $this->plugins_dir . '/woocommerce/woocommerce.php';
			require_once $this->plugin_dir . '/woocommerce-germanized.php';

			foreach( $this->packages as $package_slug => $namespace ) {
				if ( $this->is_separate_package( $package_slug ) ) {
					require_once $this->plugins_dir . '/' . $package_slug;
				}
			}
		} );

		tests_add_filter( 'woocommerce_gzd_installed', function() {

			foreach( $this->packages as $package_slug => $namespace ) {
				if ( $this->is_separate_package( $package_slug ) ) {
					$classname = $namespace . '\Package';

					$classname::install_integration();
				}
			}
		} );

		tests_add_filter( 'setup_theme', function() {

			echo esc_html( 'Installing WooCommerce...' . PHP_EOL );

			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );

			include $this->plugins_dir . '/woocommerce/uninstall.php';

			WC_Install::install();

			echo esc_html( 'Installing Germanized...' . PHP_EOL );

			define( 'WC_GZD_REMOVE_ALL_DATA', true );
			include( $this->plugin_dir . '/uninstall.php' );

			WC_GZD_Install::install();

			$GLOBALS['wp_roles'] = null; // WPCS: override ok.
			wp_roles();
		} );
	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @since 2.2
	 */
	public function includes() {

		// Start up the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// factories
		require_once( $this->tests_dir . '/framework/factories/class-wc-gzd-unit-test-factory-for-webhook.php' );
		require_once( $this->tests_dir . '/framework/factories/class-wc-gzd-unit-test-factory-for-webhook-delivery.php' );

		// framework
		require_once( $this->tests_dir . '/framework/class-wc-gzd-unit-test-factory.php' );
		require_once( $this->tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php' );

		// test cases
		require_once( $this->tests_dir . '/includes/wp-http-testcase.php' );
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
	 * @return WC_GZD_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

WC_GZD_Unit_Tests_Bootstrap::instance();
