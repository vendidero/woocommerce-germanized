<?php

namespace Vendidero\Shiptastic\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

class Helper {

	private static $disabled = array();

	private static $caches = array();

	public static function init() {
		add_action(
			'woocommerce_after_order_object_save',
			function ( $order_id ) {
				self::flush_order_cache( $order_id );
			}
		);

		add_action(
			'woocommerce_before_delete_order',
			function ( $order_id ) {
				self::flush_order_cache( $order_id );
			}
		);
	}

	public static function flush_order_cache( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! is_callable( array( $order, 'get_id' ) ) ) {
			return false;
		}

		if ( $cache = self::get_cache_object( 'shipment-orders' ) ) {
			return $cache->remove( $order->get_id() );
		}

		return false;
	}

	public static function is_enabled( $type ) {
		if ( ! class_exists( '\Automattic\WooCommerce\Caching\ObjectCache' ) ) {
			return false;
		}

		$is_enabled = ! in_array( $type, self::$disabled, true );

		if ( 'shipment-orders' === $type ) {
			$is_enabled = false;

			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && is_callable( array( 'Automattic\WooCommerce\Utilities\OrderUtil', 'orders_cache_usage_is_enabled' ) ) && OrderUtil::orders_cache_usage_is_enabled() ) {
				$is_enabled = true;
			}
		}

		return apply_filters( "woocommerce_shiptastic_enable_{$type}_cache", $is_enabled, $type );
	}

	public static function disable( $type ) {
		self::$disabled[] = $type;
	}

	public static function enable( $type ) {
		self::$disabled = array_diff( self::$disabled, array( $type ) );
	}

	protected static function get_types() {
		return array(
			'shipments'          => '\Vendidero\Shiptastic\Caches\ShipmentCache',
			'packagings'         => '\Vendidero\Shiptastic\Caches\PackagingCache',
			'shipment-labels'    => '\Vendidero\Shiptastic\Caches\ShipmentLabelCache',
			'shipping-providers' => '\Vendidero\Shiptastic\Caches\ShippingProviderCache',
			'shipment-orders'    => '\Vendidero\Shiptastic\Caches\ShipmentOrderCache',
		);
	}

	/**
	 * @param string $type
	 *
	 * @return false|ObjectCache
	 */
	public static function get_cache_object( $type ) {
		$types = self::get_types();

		if ( version_compare( WC()->version, '8.0.0', '<' ) ) {
			return false;
		}

		if ( ! self::is_enabled( $type ) || ! array_key_exists( $type, $types ) ) {
			return false;
		}

		if ( ! array_key_exists( $type, self::$caches ) ) {
			self::$caches[ $type ] = new $types[ $type ]();
		}

		return self::$caches[ $type ];
	}
}
