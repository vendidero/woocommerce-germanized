<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_GZD_Admin_Note_OSS_Install.
 */
class WC_GZD_Admin_Note_OSS_Install extends WC_GZD_Admin_Note {

	public function get_fallback_notice_type() {
		return 'notice-warning';
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( 'yes' === get_option( 'woocommerce_gzd_is_oss_standalone_update' ) && ! \Vendidero\Germanized\PluginsHelper::is_oss_plugin_active() ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'oss_install';
	}

	public function get_title() {
		return __( 'OSS plugin is missing', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'We have determined that you either participate in the OSS procedure or have the delivery threshold monitored automatically. These features are only included in our separate One Stop Shop plugin, which you should install. Technically, nothing changes except that this is now a separate plugin not included in Germanized.', 'woocommerce-germanized' );
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		$buttons   = array();
		$buttons[] = array(
			'url'          => add_query_arg( 'wc-gzd-check-install_oss', true, admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ),
			'title'        => __( 'Install now', 'woocommerce-germanized' ),
			'target'       => '_self',
			'is_primary'   => true,
			'nonce_action' => 'wc-gzd-check-install_oss',
		);

		return $buttons;
	}
}
