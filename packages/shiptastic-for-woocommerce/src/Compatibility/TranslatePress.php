<?php

namespace Vendidero\Shiptastic\Compatibility;

use Vendidero\Shiptastic\Emails;
use Vendidero\Shiptastic\Interfaces\Compatibility;

defined( 'ABSPATH' ) || exit;

class TranslatePress implements Compatibility {

	public static function is_active() {
		return defined( 'TRP_PLUGIN_VERSION' );
	}

	public static function init() {
		add_action(
			'init',
			function () {
				foreach ( Emails::get_email_notification_hooks() as $notification ) {
					add_action( "{$notification}_notification", array( __CLASS__, 'setup_notification' ) );
				}
			}
		);
	}

	public static function setup_notification( $shipment_id ) {
		if ( $shipment = wc_stc_get_shipment( $shipment_id ) ) {
			if ( $shipment->get_order_id() ) {
				global $TRP_EMAIL_ORDER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

				$TRP_EMAIL_ORDER = $shipment->get_order_id(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			}
		}
	}
}
