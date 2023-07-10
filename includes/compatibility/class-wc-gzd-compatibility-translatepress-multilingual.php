<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Compatibility_TranslatePress_Multilingual extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'TranslatePress - Multilingual';
	}

	public static function get_path() {
		return 'translatepress-multilingual/index.php';
	}

	public function load() {
		if ( class_exists( '\Vendidero\Germanized\Shipments\Emails' ) ) {
			foreach ( \Vendidero\Germanized\Shipments\Emails::register_email_notifications( array() ) as $notification ) {
				add_action( "{$notification}_notification", array( $this, 'setup_notification' ) );
			}
		}
	}

	public function setup_notification( $shipment_id ) {
		if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			if ( $shipment->get_order_id() ) {
				global $TRP_EMAIL_ORDER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

				$TRP_EMAIL_ORDER = $shipment->get_order_id(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			}
		}
	}
}
