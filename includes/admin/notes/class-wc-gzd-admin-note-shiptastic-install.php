<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_GZD_Admin_Note_Shiptastic_Install.
 */
class WC_GZD_Admin_Note_Shiptastic_Install extends WC_GZD_Admin_Note {

	public function get_fallback_notice_type() {
		return 'notice-warning';
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( 'yes' === get_option( 'woocommerce_gzd_is_shiptastic_standalone_update' ) && ! \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'shiptastic_install';
	}

	public function get_title() {
		return __( 'Shiptastic plugin is missing', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'We have determined that you may currently be using the shipments feature bundled within Germanized to create/manage shipments, returns, shipping service providers and more. These features are now part of our separate plugin called Shiptastic. Starting with Germanized 4.0, this plugin will, by default, not be included in Germanized any longer. We strongly advise you to install the separate plugin now to prevent losing access to those features in one of our next updates.', 'woocommerce-germanized' );
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		$install_title = _x( 'Shiptastic', 'shipments', 'woocommerce-germanized' );

		if ( 'yes' === get_option( 'woocommerce_gzd_is_shiptastic_dhl_standalone_update' ) ) {
			$install_title = _x( 'Shiptastic & DHL/Deutsche Post Integration', 'shipments', 'woocommerce-germanized' );
		}

		$buttons = array(
			array(
				'url'          => add_query_arg( 'wc-gzd-check-install_shiptastic', true, admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ),
				'title'        => sprintf( __( 'Install %s now', 'woocommerce-germanized' ), $install_title ),
				'target'       => '_self',
				'is_primary'   => true,
				'nonce_action' => 'wc-gzd-check-install_shiptastic',
			),
		);

		return $buttons;
	}
}
