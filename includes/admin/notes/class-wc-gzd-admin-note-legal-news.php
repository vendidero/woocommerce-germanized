<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Note_Legal_News extends WC_GZD_Admin_Note {

	public function get_name() {
		return 'legal_news';
	}

	public function is_disabled() {
		$is_disabled = parent::is_disabled();

		if ( ! $is_disabled && 'yes' === get_option( '_wc_gzd_has_legal_news' ) && current_user_can( 'manage_woocommerce' ) ) {
			$is_disabled = false;
		} else {
			$is_disabled = true;
		}

		return $is_disabled;
	}

	public function dismiss( $and_note = true ) {
		parent::dismiss( $and_note );

		delete_option( '_wc_gzd_has_legal_news' );
	}

	public function get_title() {
		return __( 'Attention: New Regulations', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'You\'ve probably heard about the new EU labels for guarantees which will be mandatory starting September 27. Please check how the labels are displayed in the frontend. If your store doesn\'t need (e.g. b2b only) the labels, disable their display in the settings.', 'woocommerce-germanized' );
	}

	public function get_actions() {
		return array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&section=guarantee_labels' ),
				'title'      => __( 'Review your settings', 'woocommerce-germanized' ),
				'target'     => '_self',
				'is_primary' => true,
			),
		);
	}
}
