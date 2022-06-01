<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Encryption extends WC_GZD_Admin_Note {

	public function get_fallback_notice_type() {
		return 'notice-warning';
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'encryption';
	}

	public function get_title() {
		return __( 'Encryption key is missing', 'woocommerce-germanized' );
	}

	public function get_content() {
		$content = WC_GZD_Secret_Box_Helper::get_encryption_key_notice();

		return $content;
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		$buttons = array(
			array(
				'url'        => 'https://vendidero.de/dokument/verschluesselung-sensibler-daten',
				'title'      => __( 'Learn more', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);

		if ( WC_GZD_Secret_Box_Helper::supports_auto_insert() ) {
			$buttons[] = array(
				'url'          => add_query_arg( 'wc-gzd-check-encryption_key_insert', true, admin_url( 'admin.php?page=wc-settings&tab=germanized' ) ),
				'title'        => __( 'Auto insert', 'woocommerce-germanized' ),
				'target'       => '_self',
				'is_primary'   => true,
				'nonce_action' => 'wc-gzd-check-encryption_key_insert',
			);
		}

		return $buttons;
	}
}
