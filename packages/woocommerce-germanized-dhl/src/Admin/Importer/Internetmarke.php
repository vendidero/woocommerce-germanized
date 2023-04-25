<?php

namespace Vendidero\Germanized\DHL\Admin\Importer;

use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Internetmarke {

	public static function is_available() {
		$options  = get_option( '_wcdpi_settings_general' );
		$imported = get_option( 'woocommerce_gzd_internetmarke_import_finished' );
		$user     = '';

		if ( $deutsche_post = Package::get_deutsche_post_shipping_provider() ) {
			$user = $deutsche_post->get_api_username();
		}

		return ( ( ! empty( $options ) && empty( $user ) && 'yes' !== $imported && Package::base_country_is_supported() ) ? true : false );
	}

	public static function is_plugin_enabled() {
		return defined( 'WCDPI_PLUGIN_FILE' ) ? true : false;
	}

	public static function import_settings() {
		$old_settings = array_merge( (array) get_option( '_wcdpi_settings_portokasse' ), (array) get_option( '_wcdpi_settings_internetmarke_1c4a' ) );

		$settings_mapping = array(
			'_wcdpi_portokasse_email'    => 'api_username',
			'_wcdpi_portokasse_password' => 'api_password',
		);

		$deutsche_post = Package::get_deutsche_post_shipping_provider();

		if ( ! $deutsche_post ) {
			return false;
		}

		// Bulk update settings
		foreach ( $settings_mapping as $setting_old_key => $setting_new_key ) {
			if ( isset( $old_settings[ $setting_old_key ] ) ) {
				$deutsche_post->update_setting( $setting_new_key, $old_settings[ $setting_old_key ] );
			}
		}

		$deutsche_post->save();
		return true;
	}
}
