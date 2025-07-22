<?php

namespace Vendidero\Germanized;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Useful to install standalone plugins associated with Germanized, e.g.
 * One Stop Shop for WooCommerce.
 */
class PluginsHelper {

	private static $active_plugins = null;

	private static $plugins = null;

	public static function init() {
		add_filter( 'all_plugins', array( __CLASS__, 'filter_bundled_plugin_names' ) );
		add_action( 'activated_plugin', array( __CLASS__, 'observe_plugin_activation' ) );
		add_action( 'ts_easy_integration_connected', array( __CLASS__, 'observe_ts_integration_connect' ) );
	}

	/**
	 * After the first successful connection to TS new gen has been detected, remove the migration note.
	 *
	 * @return void
	 */
	public static function observe_ts_integration_connect() {
		delete_option( 'woocommerce_gzd_is_ts_standalone_update' );
	}

	public static function needs_trusted_shops_migration() {
		return get_option( 'woocommerce_gzd_trusted_shops_id' ) && did_action( 'woocommerce_gzd_package_woocommerce-trusted-shops_init' );
	}

	/**
	 * Delete the option which indicates that the OSS plugin needs to be installed
	 * after a Germanized update.
	 *
	 * @param $plugin
	 *
	 * @return void
	 */
	public static function observe_plugin_activation( $plugin ) {
		if ( strstr( self::get_plugin_slug( $plugin ), 'one-stop-shop-woocommerce' ) ) {
			delete_option( 'woocommerce_gzd_is_oss_standalone_update' );
		} elseif ( strstr( self::get_plugin_slug( $plugin ), 'shiptastic-for-woocommerce' ) ) {
			delete_option( 'woocommerce_gzd_is_shiptastic_standalone_update' );
		} elseif ( strstr( self::get_plugin_slug( $plugin ), 'shiptastic-integration-for-dhl' ) ) {
			delete_option( 'woocommerce_gzd_is_shiptastic_dhl_standalone_update' );
		}
	}

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
	 * @return bool
	 */
	public static function is_oss_plugin_active() {
		return self::is_plugin_active( 'one-stop-shop-woocommerce' );
	}

	/**
	 * @return bool
	 */
	public static function is_shiptastic_plugin_active() {
		return self::is_plugin_active( 'shiptastic-for-woocommerce' );
	}

	/**
	 * @return bool
	 */
	public static function is_shiptastic_dhl_plugin_active() {
		return self::is_plugin_active( 'shiptastic-integration-for-dhl' );
	}

	/**
	 * @return bool
	 */
	public static function is_oss_plugin_installed() {
		return self::is_plugin_installed( 'one-stop-shop-woocommerce' );
	}

	/**
	 * @return bool
	 */
	public static function is_trusted_shops_plugin_active() {
		return self::is_plugin_active( 'trusted-shops-easy-integration-for-woocommerce' );
	}

	/**
	 * @return bool
	 */
	public static function is_woocommerce_plugin_active() {
		return apply_filters( 'woocommerce_gzd_is_woocommerce_activated', self::is_plugin_active( 'woocommerce' ) );
	}

	/**
	 * @return bool
	 */
	public static function is_pro_version_active() {
		return self::is_plugin_active( 'woocommerce-germanized-pro' );
	}

	protected static function is_pro_plugin( $plugin ) {
		return in_array( self::get_plugin_slug( $plugin ), array( 'woocommerce-germanized-pro' ), true );
	}

	public static function get_pro_version_product_id() {
		return 148;
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
	 * @param string $license_key
	 *
	 * @return true|\WP_Error
	 */
	protected static function install_and_activate_plugin( $plugin, $license_key = '' ) {
		self::clear_cache();

		if ( self::is_plugin_installed( $plugin ) ) {
			$result = self::activate_plugins( $plugin );
		} else {
			$result = self::install_plugins( $plugin, $license_key );

			if ( ! wc_gzd_wp_error_has_errors( $result['errors'] ) ) {
				$result = self::activate_plugins( $plugin );
			}
		}

		return ( ! wc_gzd_wp_error_has_errors( $result['errors'] ) ? true : $result['errors'] );
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function install_or_activate_extension( $extension_name, $license_key = '' ) {
		if ( self::is_plugin_whitelisted( $extension_name ) ) {
			return self::install_and_activate_plugin( $extension_name, $license_key );
		} else {
			return new \WP_Error( 'not_whitelisted', __( 'This plugin is not allowed to be installed.', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function install_or_activate_oss() {
		return self::install_or_activate_extension( 'one-stop-shop-woocommerce' );
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function install_or_activate_trusted_shops() {
		return self::install_or_activate_extension( 'trusted-shops-easy-integration-for-woocommerce' );
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function install_or_activate_shiptatic() {
		return self::install_or_activate_extension( 'shiptastic-for-woocommerce' );
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function install_or_activate_shiptatic_dhl() {
		return self::install_or_activate_extension( 'shiptastic-integration-for-dhl' );
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

		return self::is_plugin_installed( $plugin ) ? sprintf( __( 'Please <a href="%1$s">activate the %2$s &raquo;</a> plugin', 'woocommerce-germanized' ), esc_url( $install_url ), $plugin_name ) : sprintf( __( 'Please <a href="%1$s">install the %2$s &raquo;</a> plugin', 'woocommerce-germanized' ), esc_url( $install_url ), $plugin_name );
	}

	public static function add_vd_signature_trusted_keys( $keys ) {
		$keys[] = '5AJRLVJJyHHrr9FSgJIBDcKyOu2TCLY5kDO2kVhGAnU=';

		return $keys;
	}

	protected static function get_vd_download_url() {
		return 'https://download.vendidero.de/api/v1/';
	}

	public static function add_vd_signature_hosts( $hosts ) {
		$url     = @wp_parse_url( self::get_vd_download_url() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$hosts[] = $url['host'];

		return $hosts;
	}

	public static function adjust_vd_signature_url( $signature_url, $url ) {
		if ( strstr( $url, self::get_vd_download_url() ) ) {
			$signature_url = str_replace( 'latest/download', 'latest/downloadSignature', $url );
		}

		return $signature_url;
	}

	/**
	 * Install an array of plugins.
	 *
	 * @param array $plugins Plugins to install.
	 * @param string $license_key
	 *
	 * @return array|\WP_Error
	 */
	protected static function install_plugins( $plugins, $license_key = '' ) {
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
			} elseif ( ! self::is_plugin_whitelisted( $plugin ) ) {
				continue;
			}

			$start_time    = microtime( true );
			$download_link = '';

			if ( self::is_pro_plugin( $plugin ) ) {
				$pro_product_id = self::get_pro_version_product_id();

				$api_url = add_query_arg(
					array(
						'id'     => $pro_product_id,
						'key'    => md5( $license_key ),
						'domain' => esc_url( self::get_current_domain() ),
					),
					self::get_vd_download_url() . "releases/{$pro_product_id}/latest"
				);

				$response = wp_remote_get(
					esc_url_raw( $api_url ),
					array(
						'redirection' => 5,
						'headers'     => array( 'user-agent' => 'Vendidero/Germanized/' . Package::get_version() ),
					)
				);

				if ( is_wp_error( $response ) ) {
					foreach ( $response->get_error_messages() as $code => $message ) {
						$errors->add( $plugin, $message, $response->get_error_data( $code ) );
					}
					continue;
				} else {
					$response_code = wp_remote_retrieve_response_code( $response );
					$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! in_array( $response_code, array( 200, 201, 202 ), true ) || ! isset( $response_body['payload']['package'] ) ) {
						if ( isset( $response_body['notice'] ) ) {
							foreach ( (array) $response_body['notice'] as $notice ) {
								$errors->add( $plugin, $notice );
							}
						} elseif ( isset( $response_body['error'] ) ) {
							$errors->add( $plugin, $response_body['error'] );
						} else {
							$errors->add(
								$plugin,
								sprintf(
								/* translators: %s: plugin slug (example: woocommerce-services) */
									__( 'The requested plugin %s could not be installed. Plugin API call failed.', 'woocommerce-germanized' ),
									self::get_plugin_name( $plugin )
								)
							);
						}
					} else {
						add_filter( 'wp_trusted_keys', array( __CLASS__, 'add_vd_signature_trusted_keys' ) );
						add_filter( 'wp_signature_hosts', array( __CLASS__, 'add_vd_signature_hosts' ) );
						add_filter( 'wp_signature_url', array( __CLASS__, 'adjust_vd_signature_url' ), 10, 2 );

						$download_link = $response_body['payload']['package'];
					}
				}
			} else {
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
					do_action( 'woocommerce_gzd_plugins_install_api_error', $slug, $api );

					$errors->add(
						$plugin,
						sprintf(
							/* translators: %s: plugin slug (example: woocommerce-services) */
							__( 'The requested plugin %s could not be installed. Plugin API call failed.', 'woocommerce-germanized' ),
							self::get_plugin_name( $plugin )
						)
					);

					continue;
				}

				$download_link = $api->download_link;
			}

			if ( $download_link ) {
				$upgrader           = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
				$result             = $upgrader->install( $download_link );
				$results[ $plugin ] = $result;
				$time[ $plugin ]    = round( ( microtime( true ) - $start_time ) * 1000 );

				if ( is_wp_error( $result ) || is_null( $result ) ) {
					do_action( 'woocommerce_gzd_plugins_install_error', $slug, $api, $result, $upgrader );

					$errors->add(
						$plugin,
						sprintf(
							/* translators: %s: plugin slug (example: woocommerce-services) */
							__( 'The requested plugin %s could not be installed. Upgrader install failed.', 'woocommerce-germanized' ),
							self::get_plugin_name( $plugin )
						)
					);
					continue;
				}

				do_action( 'woocommerce_gzd_installed_plugin', $plugin, $license_key );

				$installed_plugins[] = $plugin;
			}
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
		return array(
			'woocommerce'                    => __( 'WooCommerce', 'woocommerce-germanized' ),
			'one-stop-shop-woocommerce'      => __( 'One Stop Shop', 'woocommerce-germanized' ),
			'trusted-shops-easy-integration-for-woocommerce' => __( 'Trusted Shops', 'woocommerce-germanized' ),
			'shiptastic-for-woocommerce'     => __( 'Shiptastic', 'woocommerce-germanized' ),
			'shiptastic-integration-for-dhl' => __( 'DHL for Shiptastic', 'woocommerce-germanized' ),
			'woocommerce-germanized-pro'     => __( 'Germanized for WooCommerce Pro', 'woocommerce-germanized' ),
		);
	}

	public static function get_current_domain( $format = false ) {
		$domain = home_url( '/' );

		if ( $format ) {
			$domain = self::format_domain( $domain );
		}

		return $domain;
	}

	protected static function format_domain( $domain ) {
		$domain = esc_url_raw( $domain );
		$parsed = @wp_parse_url( $domain ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( empty( $parsed ) || empty( $parsed['host'] ) ) {
			return '';
		}

		// Remove www. prefix
		$parsed['host'] = str_replace( 'www.', '', $parsed['host'] );
		$domain         = $parsed['host'];

		return $domain;
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
					sprintf( __( 'The requested plugin `%s` is not yet installed.', 'woocommerce-germanized' ), $slug )
				);
				continue;
			}

			$result = activate_plugin( $path );
			if ( ! is_null( $result ) ) {
				do_action( 'woocommerce_gzd_plugins_activate_error', $slug, $result );

				$errors->add(
					$plugin,
					/* translators: %s: plugin slug (example: woocommerce-services) */
					sprintf( __( 'The requested plugin `%s` could not be activated.', 'woocommerce-germanized' ), $slug )
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

		if ( ! empty( $activated_plugins ) ) {
			do_action( 'woocommerce_gzd_plugins_activated', $data );
		}

		return $data;
	}

	public static function filter_bundled_plugin_names( $plugins ) {
		$built_in_plugins = array(
			'one-stop-shop-woocommerce/one-stop-shop-woocommerce.php' => esc_html__( 'One Stop Shop', 'woocommerce-germanized' ),
			'trusted-shops-easy-integration-for-woocommerce/trusted-shops-easy-integration-for-woocommerce.php' => esc_html__( 'Trusted Shops', 'woocommerce-germanized' ),
			'shiptastic-for-woocommerce/shiptastic-for-woocommerce.php' => esc_html__( 'Shiptastic', 'woocommerce-germanized' ),
			'ups-for-shiptastic/ups-for-shiptastic.php' => esc_html__( 'UPS for Shiptastic', 'woocommerce-germanized' ),
			'shiptastic-integration-for-dhl/shiptastic-integration-for-dhl.php' => esc_html__( 'DHL for Shiptastic', 'woocommerce-germanized' ),
		);

		foreach ( $built_in_plugins as $plugin_slug => $name ) {
			if ( array_key_exists( $plugin_slug, $plugins ) ) {
				$plugins[ $plugin_slug ]['Name'] = sprintf( esc_html__( 'Germanized for WooCommerce: %s', 'woocommerce-germanized' ), $name );
			}
		}

		return $plugins;
	}

	public static function clear_cache() {
		self::$plugins        = null;
		self::$active_plugins = null;
	}
}
