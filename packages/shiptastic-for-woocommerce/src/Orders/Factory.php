<?php
/**
 * Label Factory
 *
 * The label factory creates the right label objects.
 *
 * @version 1.0.0
 */
namespace Vendidero\Shiptastic\Orders;

use Vendidero\Shiptastic\Caches\Helper;
use Vendidero\Shiptastic\Order;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Order factory class
 */
class Factory {

	private static $order_list = null;

	/**
	 * Get order.
	 *
	 * @param  mixed $order
	 * @return Order|bool
	 */
	public static function get_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			try {
				if ( $cache = Helper::get_cache_object( 'shipment-orders' ) ) {
					$shipment_order = $cache->get( $order->get_id() );

					if ( ! is_null( $shipment_order ) ) {
						return $shipment_order;
					}
				}

				$shipment_order = new Order( $order );

				if ( $cache = Helper::get_cache_object( 'shipment-orders' ) ) {
					$cache->set( $shipment_order, $order->get_id() );
				}

				return $shipment_order;
			} catch ( Exception $e ) {
				wc_caught_exception( $e, __FUNCTION__, array( $order ) );
				return false;
			}
		} elseif ( is_a( $order, 'Vendidero\Shiptastic\Order' ) ) {
			return $order;
		}

		return false;
	}
}
