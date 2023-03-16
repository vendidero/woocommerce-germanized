<?php

namespace Vendidero\Germanized\Shipments\Packing;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $packaging = null;

	/**
	 * @param false $id
	 *
	 * @return PackagingBox[]|boolean|PackagingBox
	 */
	public static function get_available_packaging( $id = false ) {
		if ( is_null( self::$packaging ) ) {
			self::$packaging = array();

			foreach ( wc_gzd_get_packaging_list() as $packaging ) {
				self::$packaging[ $packaging->get_id() ] = new PackagingBox( $packaging );
			}
		}

		if ( $id ) {
			return array_key_exists( $id, self::$packaging ) ? self::$packaging[ $id ] : false;
		}

		return self::$packaging;
	}
}
