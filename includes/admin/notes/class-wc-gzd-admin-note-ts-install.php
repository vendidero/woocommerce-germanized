<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_GZD_Admin_Note_TS_Install.
 */
class WC_GZD_Admin_Note_TS_Install extends WC_GZD_Admin_Note {

	public function get_fallback_notice_type() {
		return 'notice-warning';
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( 'yes' === get_option( 'woocommerce_gzd_is_ts_standalone_update' ) && ! \Vendidero\Germanized\PluginsHelper::is_trusted_shops_plugin_active() ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'ts_install';
	}

	public function get_title() {
		return __( 'New Trusted Shops Integration', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'You seem to be using our Trusted Shops integration. This integration is no longer supported by Germanized. Therefor we strongly encourage you to migrate to the new integration. By doing so, we will automatically install the separate Trusted Shops Easy Integration Plugin for WooCommerce.', 'woocommerce-germanized' );
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		$buttons = array(
			array(
				'url'        => 'https://vendidero.de/dokument/trusted-shops-migration',
				'title'      => __( 'Migration guide', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
			array(
				'url'          => add_query_arg( 'wc-gzd-check-install_ts', true, admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ),
				'title'        => __( 'Start migration now', 'woocommerce-germanized' ),
				'target'       => '_self',
				'is_primary'   => true,
				'nonce_action' => 'wc-gzd-check-install_ts',
			),
		);

		return $buttons;
	}
}
