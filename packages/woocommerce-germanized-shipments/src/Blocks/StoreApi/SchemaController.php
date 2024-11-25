<?php
namespace Vendidero\Germanized\Shipments\Blocks\StoreApi;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * SchemaController class.
 */
class SchemaController extends \Automattic\WooCommerce\StoreApi\SchemaController {

	/**
	 * Stores schema class instances.
	 *
	 * @var AbstractSchema[]
	 */
	protected $schemas = array();

	public function __construct() {
		$this->schemas = array(
			'v1' => array(
				Schemas\V1\SearchPickupLocationsSchema::IDENTIFIER => Schemas\V1\SearchPickupLocationsSchema::class,
			),
		);
	}

	/**
	 * Get a schema class instance.
	 *
	 * @throws \Exception If the schema does not exist.
	 *
	 * @param string $name Name of schema.
	 * @param int    $version API Version being requested.
	 * @return AbstractSchema A new instance of the requested schema.
	 */
	public function get( $name, $version = 1 ) {
		$schema = isset( $this->schemas[ "v{$version}" ][ $name ] ) ? $this->schemas[ "v{$version}" ][ $name ] : false;

		if ( $schema ) {
			$extend = Package::container()->get( \Automattic\WooCommerce\StoreApi\StoreApi::class )->container()->get( ExtendSchema::class );

			return new $schema( $extend, $this );
		} else {
			return Package::container()->get( \Automattic\WooCommerce\StoreApi\StoreApi::class )->container()->get( \Automattic\WooCommerce\StoreApi\SchemaController::class )->get( $name, $version );
		}
	}
}
