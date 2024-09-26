<?php
namespace Vendidero\Germanized\Shipments\Blocks\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;

/**
 * CartFeeSchema class.
 */
class SearchPickupLocationsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'search_pickup_locations';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'search-pickup-locations';

	/**
	 * Cart schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'pickup_locations' => array(
				'description' => _x( 'Available pickup locations', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'code'                         => array(
							'description' => _x( 'The location code.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'label'                        => array(
							'description' => _x( 'The location label.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'latitude'                     => array(
							'description' => _x( 'The location latitude.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'longitude'                    => array(
							'description' => _x( 'The location longitude.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'supports_customer_number'     => array(
							'description' => _x( 'Whether the location supports a customer number or not.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'default'     => false,
						),
						'customer_number_is_mandatory' => array(
							'description' => _x( 'Whether the customer number is mandatory or not.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'default'     => false,
						),
						'customer_number_field_label'  => array(
							'description' => _x( 'The customer number field label.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'type'                         => array(
							'description' => _x( 'The location type, e.g. locker.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'formatted_address'            => array(
							'description' => _x( 'The location\'s formatted address.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'address_replacements'         => array(
							'description' => _x( 'The location\'s address replacements.', 'shipments', 'woocommerce-germanized' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'address_1' => array(
										'description' => _x( 'The location address.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
										'default'     => '',
									),
									'address_2' => array(
										'description' => _x( 'The location address 2.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
										'default'     => '',
									),
									'postcode'  => array(
										'description' => _x( 'The location postcode.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'city'      => array(
										'description' => _x( 'The location city.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'country'   => array(
										'description' => _x( 'The location country.', 'shipments', 'woocommerce-germanized' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
							),
						),
					),
				),
			),
		);
	}

	public function get_item_response( $item ) {
		return array(
			'pickup_locations' => $item->pickup_locations,
		);
	}
}
