<?php

namespace Vendidero\Shiptastic\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Vendidero\Shiptastic\Order;

/**
 * A class to cache order objects.
 */
class ShipmentOrderCache extends ObjectCache {

	/**
	 * Get the identifier for the type of the cached objects.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'stc-shipment-orders';
	}

	/**
	 * Get the id of an object to be cached.
	 *
	 * @param array|object $cache_object The object to be cached.
	 *
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $cache_object ) {
		return $cache_object->get_id();
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param array|object $cache_object The object to validate.
	 *
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $cache_object ): ?array {
		if ( ! $cache_object instanceof Order ) {
			return array( 'The supplied order is not an instance of Order, ' . gettype( $cache_object ) );
		}

		return null;
	}
}
