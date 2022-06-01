<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Update extends WC_GZD_Admin_Note {

	public function get_name() {
		return 'update';
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( 1 === (int) get_option( '_wc_gzd_needs_update' ) && current_user_can( 'manage_woocommerce' ) ) {
			$is_disabled = false;
		}

		return $is_disabled;
	}

	public function get_title() {
		return __( 'Germanized Data Update Required', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'We just need to update your install to the latest version.', 'woocommerce-germanized' );
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		return array(
			array(
				'url'          => add_query_arg( 'do_update_woocommerce_gzd', 'true', admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ),
				'title'        => __( 'Run the updater', 'woocommerce-germanized' ),
				'target'       => '_self',
				'nonce_name'   => 'wc_gzd_db_update_nonce',
				'nonce_action' => 'wc_gzd_db_update',
			),
		);
	}
}
