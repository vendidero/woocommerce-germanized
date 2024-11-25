<?php
namespace Vendidero\Germanized\Shipments\Blocks\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Vendidero\Germanized\Shipments\PickupDelivery;

/**
 * CartAddItem class.
 */
class CartSearchPickupLocations extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'search-pickup-locations';

	/**
	 * The route's schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'search-pickup-locations';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/search-pickup-locations';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'address'  => array(
						'description' => _x( 'The address to search for.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'properties'  => array(
							'postcode'  => array(
								'description' => _x( 'The postcode.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'address_1' => array(
								'description' => _x( 'The street address.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'country'   => array(
								'description' => _x( 'The country code.', 'shipments', 'woocommerce-germanized' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'provider' => array(
						'description' => _x( 'The shipping provider.', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => false ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$address = wp_parse_args(
			(array) wc_clean( wp_unslash( $request['address'] ) ),
			array(
				'country'   => '',
				'address_1' => '',
				'postcode'  => '',
			)
		);

		$provider = wc_clean( wp_unslash( $request['provider'] ) );
		$results  = PickupDelivery::get_pickup_location_data( 'checkout', true, $address, $provider );

		$result                   = new \stdClass();
		$result->pickup_locations = array_map(
			function ( $location ) {
				return $location->get_data();
			},
			$results['locations']
		);

		$response = rest_ensure_response( $this->schema->get_item_response( $result ) );
		$response->set_status( 201 );

		return $response;
	}
}
