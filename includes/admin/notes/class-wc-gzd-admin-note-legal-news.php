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
		return sprintf( __( 'The European Online Dispute Resolution (ODR) Platform is discontinued as of 20 July 2025. We tried to remove the note from the [gzd_complaints] shortcode automatically by adjusting the <a href="%s">option selected</a>. Please make sure that your current text (whether you are using the shortcode in your legal texts or a static text) does not include references to the ODR platform.', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=disputes' ) );
	}

	public function get_actions() {
		return array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=disputes' ),
				'title'      => __( 'Review your settings', 'woocommerce-germanized' ),
				'target'     => '_self',
				'is_primary' => true,
			),
		);
	}
}
