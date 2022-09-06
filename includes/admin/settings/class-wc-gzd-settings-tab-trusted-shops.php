<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Shipments settings.
 *
 * @class        WC_GZD_Settings_Tab_Shipments
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Trusted_Shops extends WC_GZD_Settings_Tab {

	public function get_extension_name() {
		return 'trusted-shops-easy-integration-for-woocommerce';
	}

	public function needs_install() {
		return ! \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active();
	}

	public function get_description() {
		return __( 'Integrate the Trustmark, the Buyer Protection and authentic reviews from Trusted Shops.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return \Vendidero\Germanized\PluginsHelper::needs_trusted_shops_migration() ? __( 'Trusted Shops Easy Integration', 'woocommerce-germanized' ) : __( 'Trusted Shops', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'trusted_shops_easy_integration';
	}

	public function get_help_link() {
		return 'https://help.etrusted.com/hc/de/articles/360045848092';
	}

	public function get_sections() {
		if ( \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active() ) {
			return Vendidero\TrustedShopsEasyIntegration\Admin\Settings::get_sections();
		} else {
			return array(
				'' => __( 'General', 'woocommerce-germanized' ),
			);
		}
	}

	public function output() {
		if ( \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active() ) {
			Vendidero\TrustedShopsEasyIntegration\Admin\Settings::output();
		} else {
			parent::output();
		}
	}

	public function get_tab_settings( $current_section = '' ) {
		if ( \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active() ) {
			return Vendidero\TrustedShopsEasyIntegration\Admin\Settings::get_settings( $current_section );
		} else {
			return array();
		}
	}

	public function is_enabled() {
		if ( \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active() && get_option( 'ts_easy_integration_client_id' ) ) {
			return true;
		}

		return false;
	}
}
