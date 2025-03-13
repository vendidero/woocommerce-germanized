<?php

namespace Vendidero\Shiptastic\API;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $api_instances = array();

	/**
	 * @param $api_type
	 *
	 * @return false|\Vendidero\Shiptastic\Interfaces\Api
	 */
	public static function get_api( $api_type, $sandbox = null ) {
		$instance = false;

		if ( '_sandbox' === substr( $api_type, -8 ) ) {
			$sandbox  = true;
			$api_type = substr( $api_type, 0, -8 );
		}

		if ( ! array_key_exists( $api_type, self::$api_instances ) ) {
			self::register_api( $api_type );
		}

		if ( array_key_exists( $api_type, self::$api_instances ) ) {
			$instance = self::$api_instances[ $api_type ];

			if ( ! is_null( $sandbox ) ) {
				$instance->set_is_sandbox( $sandbox );
			}
		}

		return $instance;
	}

	protected static function register_api( $api_type ) {
		$instance = apply_filters( "shiptastic_register_api_instance_{$api_type}", null );

		if ( is_a( $instance, '\Vendidero\Shiptastic\Interfaces\Api' ) ) {
			self::$api_instances[ $api_type ] = $instance;
		}
	}
}
