<?php
/**
 * Loads Germanized packages from the /packages directory. These are packages developed outside of core.
 *
 * @package Vendidero/Germanized
 */

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

/**
 * Packages class.
 *
 * @since 3.0.0
 */
class Packages {

	/**
	 * Static-only class.
	 */
	private function __construct() {
	}

	/**
	 * Array of package names and their main package classes.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $packages = array(
		'shiptastic-for-woocommerce'     => '\\Vendidero\\Shiptastic\\Package',
		'shiptastic-integration-for-dhl' => '\\Vendidero\\Shiptastic\\DHL\\Package',
	);

	/**
	 * Init the package loader.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ) );
	}

	public static function get_packages() {
		return self::$packages;
	}

	/**
	 * Callback for WordPress init hook.
	 */
	public static function on_init() {
		self::load_packages();
	}

	/**
	 * Checks a package exists by looking for it's directory.
	 *
	 * @param string $package Package name.
	 *
	 * @return boolean
	 */
	public static function package_exists( $package ) {
		return file_exists( dirname( __DIR__ ) . '/packages/' . $package );
	}

	/**
	 * Loads packages after plugins_loaded hook.
	 *
	 * Each package should include an init file which loads the package so it can be used by core.
	 */
	protected static function load_packages() {
		add_filter( 'woocommerce_shiptastic_is_integration', '__return_true' );

		foreach ( self::$packages as $package_name => $package_class ) {
			if ( ! self::package_exists( $package_name ) ) {
				self::missing_package( $package_name );
				continue;
			}

			/**
			 * Do not load the Shiptastic (DHL) package in case the separate, standalone plugin is active or the default
			 * option after a bundled-install has not been set.
			 *
			 * @TODO remove after removing the package from Germanized core in 4.0.0.
			 */
			if ( 'shiptastic-for-woocommerce' === $package_name ) {
				if ( false === self::load_shipping_package() || ( PluginsHelper::is_shiptastic_plugin_active() && defined( 'WC_STC_IS_STANDALONE_PLUGIN' ) ) || ( ! get_option( 'woocommerce_shiptastic_auto_enable' ) && ! get_option( 'woocommerce_gzd_shipments_auto_enable' ) ) ) {
					continue;
				} elseif ( version_compare( get_option( 'woocommerce_gzd_db_version', '1.0.0' ), '3.20.0', '>=' ) && 'yes' !== get_option( 'woocommerce_gzd_is_shiptastic_standalone_update' ) ) {
					/**
					 * After the DB update has been completed, check the temp option which indicates the necessity to load the bundled package.
					 */
					continue;
				}
			} elseif ( 'shiptastic-integration-for-dhl' === $package_name ) {
				if ( false === self::load_shipping_package() || ( PluginsHelper::is_shiptastic_dhl_plugin_active() && defined( 'WC_STC_INTEGRATION_FOR_DHL_IS_STANDALONE_PLUGIN' ) ) || ( ! get_option( 'woocommerce_shiptastic_auto_enable' ) && ! get_option( 'woocommerce_gzd_shipments_auto_enable' ) ) ) {
					continue;
				} elseif ( version_compare( get_option( 'woocommerce_gzd_db_version', '1.0.0' ), '3.20.0', '>=' ) && 'yes' !== get_option( 'woocommerce_gzd_is_shiptastic_dhl_standalone_update' ) ) {
					/**
					 * After the DB update has been completed, check the temp option which indicates the necessity to load the bundled package.
					 */
					continue;
				}
			}

			/**
			 * Prevent calling init twice in case feature plugin is installed
			 */
			if ( ! has_action( 'plugins_loaded', array( $package_class, 'init' ) ) ) {
				call_user_func( array( $package_class, 'init' ) );

				$package_hook_name = sanitize_key( $package_name );

				do_action( "woocommerce_gzd_package_{$package_hook_name}_init" );
			}
		}
	}

	public static function load_shipping_package() {
		return apply_filters( 'woocommerce_gzd_shipments_enabled', true );
	}

	/**
	 * If a package is missing, add an admin notice.
	 *
	 * @param string $package Package name.
	 */
	protected static function missing_package( $package ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				sprintf(
				/* Translators: %s package name. */
					esc_html__( 'Missing the Germanized %s package', 'woocommerce-germanized' ),
					'<code>' . esc_html( $package ) . '</code>'
				) . ' - ' . esc_html__( 'Your installation of Germanized is incomplete. If you installed Germanized from GitHub, please refer to this document to set up your development environment: https://github.com/vendidero/woocommerce-germanized/wiki/How-to-set-up-a-Germanized-development-environment', 'woocommerce-germanized' )
			);
		}
		add_action(
			'admin_notices',
			function () use ( $package ) {
				?>
				<div class="notice notice-error">
					<p>
						<strong>
							<?php
							printf(
							/* Translators: %s package name. */
								esc_html__( 'Missing the Germanized %s package', 'woocommerce-germanized' ),
								'<code>' . esc_html( $package ) . '</code>'
							);
							?>
						</strong>
						<br>
						<?php
						printf(
						/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of Germanized is incomplete. If you installed Germanized from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce-germanized' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
