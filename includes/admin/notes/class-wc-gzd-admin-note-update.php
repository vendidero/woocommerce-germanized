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

		if ( get_option( '_wc_gzd_needs_update' ) == 1 && current_user_can( 'manage_woocommerce' ) ) {
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

	public function is_dismissable() {
		return false;
	}

	public function get_actions() {
		return array(
			array(
				'url'    => add_query_arg( 'do_update_woocommerce_gzd', 'true', admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ),
				'title'  => __( 'Run the updater', 'woocommerce-germanized' ),
				'target' => '_self',
			)
		);
	}
}
