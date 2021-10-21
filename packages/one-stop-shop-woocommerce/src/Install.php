<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'one_stop_shop_woocommerce', null );
		update_option( 'one_stop_shop_woocommerce', Package::get_version() );

		if ( ! Package::is_integration() ) {
			if ( ! Package::has_dependencies() ) {
				ob_start();
				Package::dependency_notice();
				$notice = ob_get_clean();

				wp_die( $notice );
			}

			self::add_options();
		}
	}

	private static function add_options() {
		foreach( Settings::get_sections() as $section ) {
			foreach( Settings::get_settings( $section ) as $setting ) {
				if ( isset( $setting['default'] ) && isset( $setting['id'] ) ) {
					wp_cache_delete( $setting['id'], 'options' );

					$autoload = isset( $setting['autoload'] ) ? (bool) $setting['autoload'] : true;
					add_option( $setting['id'], $setting['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}
	}
}
