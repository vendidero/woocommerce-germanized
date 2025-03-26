<?php

namespace Vendidero\Shiptastic;

use Vendidero\Shiptastic\ShippingProvider\Helper;
use Vendidero\Shiptastic\ShippingProvider\Placeholder;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Useful to install standalone plugins associated with the base package.
 */
class Extensions {

	private static $active_plugins = null;

	private static $plugins = null;

	/**
	 * Get the path to the plugin file relative to the plugins directory from the plugin slug.
	 *
	 * E.g. 'woocommerce' returns 'woocommerce/woocommerce.php'
	 *
	 * @param string $slug Plugin slug to get path for.
	 *
	 * @return string|false
	 */
	public static function get_plugin_path_from_slug( $slug ) {
		if ( strstr( $slug, '/' ) ) {
			$slug = self::get_plugin_slug( $slug );
		}

		$res = preg_grep( self::get_plugin_search_regex( $slug ), array_keys( self::get_plugins() ) );

		return false !== $res && count( $res ) > 0 ? array_values( $res )[0] : false;
	}

	protected static function get_plugin_slug( $path ) {
		$path_parts = explode( '/', $path );

		return $path_parts[0];
	}

	/**
	 * Get an array of installed plugin slugs.
	 *
	 * @return array
	 */
	public static function get_installed_plugin_slugs() {
		return array_map( array( __CLASS__, 'get_plugin_slug' ), array_keys( self::get_plugins() ) );
	}

	/**
	 * Get an array of installed plugins with their file paths as a key value pair.
	 *
	 * @return array
	 */
	public static function get_installed_plugins_paths() {
		$plugins           = self::get_plugins();
		$installed_plugins = array();

		foreach ( $plugins as $path => $plugin ) {
			$installed_plugins[ self::get_plugin_slug( $path ) ] = $path;
		}

		return $installed_plugins;
	}

	/**
	 * Get an array of active plugin slugs.
	 *
	 * @return array
	 */
	public static function get_active_plugin_slugs() {
		return array_map( array( __CLASS__, 'get_plugin_slug' ), self::get_active_plugins() );
	}

	/**
	 * Use a regex to find the actual plugin. This regex ignores
	 * plugin path suffixes, e.g. is able to detect plugin paths like woocommerce-2/woocommerce.php
	 *
	 * @param string $slug May either be a slug-only, e.g. woocommerce or a path like woocommerce-multilingual/wpml-woocommerce.php
	 *
	 * @return string
	 */
	private static function get_plugin_search_regex( $slug ) {
		$path_part = $slug;
		$slug_part = $slug;

		if ( strstr( $slug, '/' ) ) {
			$parts = explode( '/', $slug );

			if ( ! empty( $parts ) && 2 === count( $parts ) ) {
				$path_part = $parts[0];
				$slug_part = preg_replace( '/\.\w+$/', '', $parts[1] ); // remove .php
			} else {
				$slug = self::get_plugin_slug( $slug );

				$path_part = $slug;
				$slug_part = $slug;
			}
		}

		return '/^' . $path_part . '.*\/' . $slug_part . '.php$/';
	}

	/**
	 * Checks if a plugin is installed.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed( $plugin ) {
		$res = preg_grep( self::get_plugin_search_regex( $plugin ), array_keys( self::get_plugins() ) );

		return false !== $res && count( $res ) > 0 ? true : false;
	}

	protected static function get_plugins() {
		if ( is_null( self::$plugins ) ) {
			self::$plugins = get_plugins();
		}

		return self::$plugins;
	}

	protected static function get_active_plugins() {
		if ( is_null( self::$active_plugins ) ) {
			$active_plugins = get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
			}

			self::$active_plugins = $active_plugins;
		}

		return self::$active_plugins;
	}

	/**
	 * Checks if a plugin is active.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $plugin ) {
		$res = preg_grep( self::get_plugin_search_regex( $plugin ), self::get_active_plugins() );

		return false !== $res && count( $res ) > 0 ? true : false;
	}

	/**
	 * @param string $name
	 * @param string $extension_name
	 *
	 * @return bool
	 */
	public static function is_provider_integration_active( $name, $extension_name ) {
		return apply_filters( 'woocommerce_shiptastic_is_provider_integration_active', self::is_plugin_active( $extension_name ), $name, $extension_name );
	}

	/**
	 * @return bool
	 */
	public static function is_woocommerce_plugin_active() {
		return apply_filters( 'woocommerce_shiptastic_is_woocommerce_activated', self::is_plugin_active( 'woocommerce' ) );
	}

	/**
	 * Get plugin name.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return array|false
	 */
	public static function get_plugin_name( $plugin ) {
		if ( $plugin_info = self::get_plugin_data( $plugin ) ) {
			return $plugin_info['Name'];
		} elseif ( self::is_plugin_whitelisted( $plugin ) ) {
			return self::get_whitelisted_plugins()[ $plugin ];
		} else {
			return ucwords( str_replace( '-', ' ', $plugin ) );
		}
	}

	/**
	 * Get plugin data.
	 *
	 * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
	 *
	 * @return array|false
	 */
	public static function get_plugin_data( $plugin ) {
		$plugin_path = self::get_plugin_path_from_slug( $plugin );
		$plugins     = self::get_plugins();

		return isset( $plugins[ $plugin_path ] ) ? $plugins[ $plugin_path ] : false;
	}

	/**
	 * @param $version
	 *
	 * @return string
	 */
	protected static function parse_version( $version ) {
		$version = preg_replace( '#(\.0+)+($|-)#', '', $version );

		// Remove/ignore beta, alpha, rc status from version strings
		$version = trim( preg_replace( '#(beta|alpha|rc)#', ' ', $version ) );

		// Make sure version has at least 2 signs, e.g. 3 -> 3.0
		if ( strlen( $version ) === 1 ) {
			$version = $version . '.0';
		}

		return $version;
	}

	public static function get_major_version( $version ) {
		$expl_ver = explode( '.', $version );

		return implode( '.', array_slice( $expl_ver, 0, 2 ) );
	}

	/**
	 * This method removes additional accuracy from $ver2 if this version is more accurate than $main_ver.
	 *
	 * @param $main_ver
	 * @param $ver2
	 * @param $operator
	 *
	 * @return bool
	 */
	public static function compare_versions( $main_ver, $ver2, $operator ) {
		$expl_main_ver = explode( '.', $main_ver );
		$expl_ver2     = explode( '.', $ver2 );

		// Check if ver2 string is more accurate than main_ver
		if ( 2 === count( $expl_main_ver ) && count( $expl_ver2 ) > 2 ) {
			$new_ver_2 = array_slice( $expl_ver2, 0, 2 );
			$ver2      = implode( '.', $new_ver_2 );
		}

		return version_compare( $main_ver, $ver2, $operator );
	}

	public static function get_plugin_version( $plugin ) {
		$data = self::get_plugin_data( $plugin );

		return ( $data && isset( $data['Version'] ) ) ? self::parse_version( $data['Version'] ) : false;
	}

	/**
	 * @param $plugin
	 *
	 * @return bool
	 */
	protected static function install_and_activate_plugin( $plugin ) {
		self::clear_cache();

		$result = array(
			'errors' => new \WP_Error(),
		);

		if ( self::is_plugin_installed( $plugin ) ) {
			$result = self::activate_plugins( $plugin );
		} elseif ( ! self::is_plugin_installed( $plugin ) ) {
			$result = self::install_plugins( $plugin );

			if ( ! wc_stc_wp_error_has_errors( $result['errors'] ) ) {
				$result = self::activate_plugins( $plugin );
			}
		}

		return ( ! wc_stc_wp_error_has_errors( $result['errors'] ) ? true : false );
	}

	/**
	 * @return bool
	 */
	public static function install_or_activate_extension( $extension_name ) {
		if ( self::is_plugin_whitelisted( $extension_name ) ) {
			return self::install_and_activate_plugin( $extension_name );
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public static function install_or_activate_oss() {
		return self::install_or_activate_extension( 'one-stop-shop-woocommerce' );
	}

	/**
	 * @return bool
	 */
	public static function install_or_activate_trusted_shops() {
		return self::install_or_activate_extension( 'trusted-shops-easy-integration-for-woocommerce' );
	}

	public static function get_plugin_manual_install_message( $plugin ) {
		if ( self::is_plugin_active( $plugin ) || ! self::is_plugin_whitelisted( $plugin ) ) {
			return '';
		}

		$plugin_name = self::get_plugin_name( $plugin );
		$install_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'install-plugin',
					'plugin' => $plugin,
				),
				admin_url( 'update.php' )
			),
			'install-plugin_' . $plugin
		);

		if ( self::is_plugin_installed( $plugin ) ) {
			$plugin_path = self::get_plugin_path_from_slug( $plugin );
			$install_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'activate',
						'plugin' => rawurlencode( $plugin_path ),
					),
					admin_url( 'plugins.php' )
				),
				'activate-plugin_' . $plugin_path
			);
		}

		return self::is_plugin_installed( $plugin ) ? sprintf( _x( 'Please <a href="%1$s">activate the %2$s &raquo;</a> plugin', 'shipments', 'woocommerce-germanized' ), esc_url( $install_url ), $plugin_name ) : sprintf( _x( 'Please <a href="%1$s">install the %2$s &raquo;</a> plugin', 'shipments', 'woocommerce-germanized' ), esc_url( $install_url ), $plugin_name );
	}

	/**
	 * Install an array of plugins.
	 *
	 * @param array $plugins Plugins to install.
	 * @return array|\WP_Error
	 */
	protected static function install_plugins( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			$plugins = array( $plugins );
		}

		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/admin.php';
		include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		include_once ABSPATH . '/wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';
		include_once ABSPATH . '/wp-admin/includes/class-automatic-upgrader-skin.php';

		$existing_plugins  = self::get_installed_plugins_paths();
		$installed_plugins = array();
		$results           = array();
		$time              = array();
		$errors            = new \WP_Error();

		foreach ( $plugins as $plugin ) {
			$slug = sanitize_key( $plugin );

			if ( isset( $existing_plugins[ $slug ] ) ) {
				$installed_plugins[] = $plugin;
				continue;
			} elseif ( ! self::is_plugin_whitelisted( $plugin ) ) {
				continue;
			}

			$start_time = microtime( true );

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'sections' => false,
					),
				)
			);

			if ( is_wp_error( $api ) ) {
				do_action( 'woocommerce_shiptastic_plugins_install_api_error', $slug, $api );

				$errors->add(
					$plugin,
					sprintf(
						/* translators: %s: plugin slug (example: woocommerce-services) */
						_x( 'The requested plugin `%s` could not be installed. Plugin API call failed.', 'shipments', 'woocommerce-germanized' ),
						$slug
					)
				);

				continue;
			}

			$upgrader           = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
			$result             = $upgrader->install( $api->download_link );
			$results[ $plugin ] = $result;
			$time[ $plugin ]    = round( ( microtime( true ) - $start_time ) * 1000 );

			if ( is_wp_error( $result ) || is_null( $result ) ) {
				do_action( 'woocommerce_shiptastic_plugins_install_error', $slug, $api, $result, $upgrader );

				$errors->add(
					$plugin,
					sprintf(
						/* translators: %s: plugin slug (example: woocommerce-services) */
						_x( 'The requested plugin `%s` could not be installed. Upgrader install failed.', 'shipments', 'woocommerce-germanized' ),
						$slug
					)
				);
				continue;
			}

			$installed_plugins[] = $plugin;
		}

		$data = array(
			'installed' => $installed_plugins,
			'results'   => $results,
			'errors'    => $errors,
			'time'      => $time,
		);

		self::clear_cache();

		return $data;
	}

	public static function is_plugin_whitelisted( $plugin ) {
		return array_key_exists( $plugin, self::get_whitelisted_plugins() );
	}

	protected static function get_whitelisted_plugins() {
		$whitelisted = array(
			'woocommerce' => _x( 'WooCommerce', 'shipments', 'woocommerce-germanized' ),
		);

		foreach ( Helper::instance()->get_available_shipping_provider_integrations() as $integration ) {
			if ( ! empty( $integration->get_extension_name() ) && ! $integration->is_pro() ) {
				$whitelisted[ $integration->get_extension_name() ] = $integration->get_title();
			}
		}

		return $whitelisted;
	}

	/**
	 * Activate the requested plugins.
	 *
	 * @param array $plugins Plugins.
	 * @return array Plugin Status
	 */
	protected static function activate_plugins( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			$plugins = array( $plugins );
		}

		require_once ABSPATH . '/wp-admin/includes/plugin.php';

		// the mollie-payments-for-woocommerce plugin calls `WP_Filesystem()` during it's activation hook, which crashes without this include.
		require_once ABSPATH . '/wp-admin/includes/file.php';

		$plugin_paths      = self::get_installed_plugins_paths();
		$errors            = new \WP_Error();
		$activated_plugins = array();

		foreach ( $plugins as $plugin ) {
			if ( ! self::is_plugin_whitelisted( $plugin ) ) {
				continue;
			}

			$slug = $plugin;
			$path = isset( $plugin_paths[ $slug ] ) ? $plugin_paths[ $slug ] : false;

			if ( ! $path ) {
				$errors->add(
					$plugin,
					/* translators: %s: plugin slug (example: woocommerce-services) */
					sprintf( _x( 'The requested plugin `%s` is not yet installed.', 'shipments', 'woocommerce-germanized' ), $slug )
				);
				continue;
			}

			$result = activate_plugin( $path );
			if ( ! is_null( $result ) ) {
				do_action( 'woocommerce_shiptastic_plugins_activate_error', $slug, $result );

				$errors->add(
					$plugin,
					/* translators: %s: plugin slug (example: woocommerce-services) */
					sprintf( _x( 'The requested plugin `%s` could not be activated.', 'shipments', 'woocommerce-germanized' ), $slug )
				);
				continue;
			}

			$activated_plugins[] = $plugin;
		}

		$data = array(
			'activated' => $activated_plugins,
			'active'    => self::get_active_plugin_slugs(),
			'errors'    => $errors,
		);

		self::clear_cache();

		return $data;
	}

	public static function clear_cache() {
		self::$plugins        = null;
		self::$active_plugins = null;
	}
}
