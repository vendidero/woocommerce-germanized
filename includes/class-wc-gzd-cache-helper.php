<?php
/**
 * WC_GZD_Cache_Helper class.
 *
 * @class        WC_GZD_Cache_Helper
 * @version        1.9.8
 * @package        WooCommerce_Germanized/Classes
 * @category    Class
 * @author        vendidero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_GZD_Cache_Helper.
 */
class WC_GZD_Cache_Helper {

	/**
	 * This function shall flush caches for some widely spread Caching Plugins.
	 *
	 * @param string $type the cache type
	 * @param array $cache_args additional arguments e.g. cache_type
	 */
	public static function maybe_flush_cache( $type = 'db', $cache_args = array() ) {
		// Support W3 Total Cache flushing
		if ( function_exists( 'w3tc_' . $type . 'cache_flush' ) ) {
			call_user_func( 'w3tc_' . $type . 'cache_flush' );
		}

		/**
		 * Flush cache action.
		 *
		 * Trigger the flush cache action to indicate that Germanized wants to flush the cache.
		 *
		 * @param string $type Cache type e.g. db.
		 * @param array $cache_args Additional arguments.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_maybe_flush_cache', $type, $cache_args );
	}
}
