<?php
namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

class CacheHelper {

	/**
	 * Hook in methods.
	 */
	public static function init() {}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		return \WC_Cache_Helper::get_cache_prefix( $group );
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 3.9.0
	 */
	public static function invalidate_cache_group( $group ) {
		return \WC_Cache_Helper::invalidate_cache_group( $group );
	}

	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching( $type = '' ) {
		if ( ! is_blog_installed() ) {
			return;
		}

		/**
		 * Clear/disable object cache if available
		 */
		if ( apply_filters( 'storeabill_force_object_cache_flush', true, $type ) ) {
			wp_using_ext_object_cache( false );
			wp_cache_flush();
			wp_cache_init();

			if ( function_exists( 'w3tc_objectcache_flush' ) ) {
				w3tc_objectcache_flush();
			}

			if ( function_exists( 'w3tc_dbcache_flush' ) ) {
				w3tc_dbcache_flush();
			}
		}

		self::set_nocache_constants();
		nocache_headers();
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		wc_maybe_define_constant( 'DONOTCACHEPAGE', true );
		wc_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		wc_maybe_define_constant( 'DONOTCACHEDB', true );

		return $return;
	}
}
