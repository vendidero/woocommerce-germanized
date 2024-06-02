<?php

namespace Vendidero\Germanized\Shipments\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Vendidero\Germanized\Shipments\Packaging;

/**
 * A class to cache order objects.
 */
class PackagingCache extends ObjectCache {

	/**
	 * Get the identifier for the type of the cached objects.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'packagings';
	}

	/**
	 * Get the id of an object to be cached.
	 *
	 * @param array|object $object The object to be cached.
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $object ) {
		return $object->get_id();
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param array|object $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $object ): ?array {
		if ( ! $object instanceof Packaging ) {
			return array( 'The supplied packaging is not an instance of Packaging, ' . gettype( $object ) );
		}

		return null;
	}
}
