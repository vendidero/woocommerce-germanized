<?php
namespace Vendidero\Shiptastic\Blocks\StoreApi;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;

/**
 * RoutesController class.
 */
class RoutesController {
	/**
	 * Stores schema_controller.
	 *
	 * @var SchemaController
	 */
	protected $schema_controller;

	/**
	 * Stores routes.
	 *
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema controller class passed to each route.
	 */
	public function __construct( SchemaController $schema_controller ) {
		$this->schema_controller = $schema_controller;
		$this->routes            = array(
			'v1' => array(),
		);

		$this->routes['v1'] = array_merge(
			$this->routes['v1'],
			array(
				Routes\V1\CartSearchPickupLocations::IDENTIFIER => Routes\V1\CartSearchPickupLocations::class,
			)
		);
	}

	/**
	 * Register all Store API routes. This includes routes under specific version namespaces.
	 */
	public function register_all_routes() {
		$this->register_routes( 'v1', 'wc/store' );
		$this->register_routes( 'v1', 'wc/store/v1' );
	}

	/**
	 * Get a route class instance.
	 *
	 * Each route class is instantized with the SchemaController instance, and its main Schema Type.
	 *
	 * @throws \Exception If the schema does not exist.
	 * @param string $name Name of schema.
	 * @param string $version API Version being requested.
	 * @return AbstractRoute
	 */
	public function get( $name, $version = 'v1' ) {
		$route = isset( $this->routes[ $version ][ $name ] ) ? $this->routes[ $version ][ $name ] : false;

		if ( ! $route ) {
			throw new \Exception( esc_html( "{$name} {$version} route does not exist" ) );
		}

		return new $route(
			$this->schema_controller,
			$this->schema_controller->get( $route::SCHEMA_TYPE, $route::SCHEMA_VERSION )
		);
	}

	/**
	 * Register defined list of routes with WordPress.
	 *
	 * @param string $version API Version being registered.
	 * @param string $ns Overrides the default route namespace.
	 */
	protected function register_routes( $version = 'v1', $namespace = 'wc/store/v1' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.namespaceFound
		if ( ! isset( $this->routes[ $version ] ) ) {
			return;
		}

		$route_identifiers = array_keys( $this->routes[ $version ] );

		foreach ( $route_identifiers as $route ) {
			$route_instance = $this->get( $route, $version );
			$route_instance->set_namespace( $namespace );

			register_rest_route(
				$route_instance->get_namespace(),
				$route_instance->get_path(),
				$route_instance->get_args()
			);
		}
	}
}
