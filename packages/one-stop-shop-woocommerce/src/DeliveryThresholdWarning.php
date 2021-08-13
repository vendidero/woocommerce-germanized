<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

class DeliveryThresholdWarning extends AdminNote {

	public static function get_actions() {
		return array_merge( array(
			array(
				'target'     => '',
				'title'      => _x( 'See details', 'oss', 'woocommerce-germanized' ),
				'url'        => Settings::get_settings_url(),
				'is_primary' => true,
			)
		), parent::get_actions() );
	}

	public static function get_content() {
		return Admin::get_threshold_notice_content();
	}

	public static function get_title() {
		return Admin::get_threshold_notice_title();
	}

	public static function is_enabled() {
		$is_enabled = parent::is_enabled();

		return $is_enabled && Package::enable_auto_observer() && Package::observer_report_needs_notification();
	}

	public static function get_id() {
		return 'delivery-threshold-warning-' . date( 'Y' );
	}
}
