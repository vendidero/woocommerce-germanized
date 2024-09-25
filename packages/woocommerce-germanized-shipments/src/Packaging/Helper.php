<?php

namespace Vendidero\Germanized\Shipments\Packaging;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $packaging = null;

	protected static $packaging_lookup = null;

	/**
	 * @return \Vendidero\Germanized\Shipments\Packaging[]
	 */
	public static function get_all_packaging() {
		if ( is_null( self::$packaging ) ) {
			self::$packaging        = array();
			self::$packaging_lookup = array();

			try {
				$data_store = \WC_Data_Store::load( 'packaging' );

				foreach ( $data_store->get_all_packaging() as $key => $packaging ) {
					if ( $the_packaging = wc_gzd_get_packaging( $packaging ) ) {
						self::$packaging[ $key ]                            = $the_packaging;
						self::$packaging_lookup[ $the_packaging->get_id() ] = $key;
					}
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		return self::$packaging;
	}

	/**
	 * @param $args
	 *
	 * @return \Vendidero\Germanized\Shipments\Packaging[]
	 */
	public static function get_packaging_list( $args = array() ) {
		$the_list  = self::get_all_packaging();
		$all_types = array_keys( wc_gzd_get_packaging_types() );
		$args      = wp_parse_args(
			$args,
			array(
				'type'              => $all_types,
				'shipping_provider' => '',
			)
		);

		if ( ! is_array( $args['type'] ) ) {
			$args['type'] = array_filter( array( $args['type'] ) );
		}

		$types = array_filter( wc_clean( $args['type'] ) );
		$types = empty( $types ) ? $all_types : $types;

		foreach ( $the_list as $key => $packaging ) {
			if ( ! in_array( $packaging->get_type(), $types, true ) ) {
				unset( $the_list[ $key ] );
				continue;
			}

			if ( ! empty( $args['shipping_provider'] ) && ! $packaging->supports_shipping_provider( $args['shipping_provider'] ) ) {
				unset( $the_list[ $key ] );
			}
		}

		return array_values( $the_list );
	}

	public static function clear_cache() {
		self::$packaging        = null;
		self::$packaging_lookup = null;
	}
}
