<?php
namespace Vendidero\Shiptastic\Blocks;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Vendidero\Shiptastic\Blocks\StoreApi\SchemaController;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\PickupDelivery;

final class Checkout {

	/**
	 * @var SchemaController
	 */
	private $schema_controller = null;

	public function __construct( $schema_controller ) {
		$this->schema_controller = $schema_controller;

		$this->register_endpoint_data();
		$this->register_integrations();
		$this->register_validation_and_storage();
	}

	private function register_validation_and_storage() {
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order, $request ) {
				$this->validate_checkout_data( $order, $request );
			},
			10,
			2
		);
	}

	private function has_checkout_data( $param, $request ) {
		$request_data = isset( $request['extensions']['woocommerce-shiptastic'] ) ? (array) $request['extensions']['woocommerce-shiptastic'] : array();

		return isset( $request_data[ $param ] ) && null !== $request_data[ $param ];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	private function get_checkout_data_from_request( $request ) {
		$data = array_filter( (array) wc_clean( $request['extensions']['woocommerce-shiptastic'] ) );
		$data = wp_parse_args(
			$data,
			array(
				'pickup_location'                 => '',
				'pickup_location_customer_number' => '',
			)
		);

		$data['pickup_location_customer_number'] = trim( preg_replace( '/\s+/', '', $data['pickup_location_customer_number'] ) );

		return $data;
	}

	/**
	 * @param \WC_Order $order
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	private function validate_checkout_data( $order, $request ) {
		$stc_data                        = $this->get_checkout_data_from_request( $request );
		$pickup_location                 = false;
		$pickup_location_customer_number = '';

		if ( $this->has_checkout_data( 'pickup_location', $request ) && ! empty( $stc_data['pickup_location'] ) ) {
			$pickup_location_code            = $stc_data['pickup_location'];
			$pickup_location_customer_number = $stc_data['pickup_location_customer_number'];
			$supports_customer_number        = false;
			$customer_number_is_mandatory    = false;
			$is_valid                        = false;
			$pickup_location                 = false;
			$address_data                    = array(
				'country'   => $order->get_shipping_country(),
				'postcode'  => $order->get_shipping_postcode(),
				'city'      => $order->get_shipping_city(),
				'address_1' => $order->get_shipping_address_1(),
			);

			if ( $provider = wc_stc_get_order_shipping_provider( $order ) ) {
				if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
					$query_args                    = PickupDelivery::get_pickup_delivery_cart_args();
					$query_args['payment_gateway'] = $order->get_payment_method();

					if ( $provider->supports_pickup_location_delivery( $address_data, $query_args ) ) {
						$pickup_location              = $provider->get_pickup_location_by_code( $pickup_location_code );
						$is_valid                     = $provider->is_valid_pickup_location( $pickup_location_code );
						$supports_customer_number     = $pickup_location ? $pickup_location->supports_customer_number() : false;
						$customer_number_is_mandatory = $pickup_location ? $pickup_location->customer_number_is_mandatory() : false;
					}
				}
			}

			if ( ! $is_valid || ! $pickup_location ) {
				throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pickup_location_unknown', esc_html_x( 'Sorry, your current pickup location is not supported.', 'shipments', 'woocommerce-germanized' ), 400 );
			} elseif ( $supports_customer_number && ( ! empty( $pickup_location_customer_number ) || $customer_number_is_mandatory ) ) {
				if ( ! $validation = $pickup_location->customer_number_is_valid( $pickup_location_customer_number ) ) {
					if ( is_a( $validation, 'WP_Error' ) ) {
						throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pickup_location_customer_number_invalid', wp_kses_post( $validation->get_error_message() ), 400 );
					} else {
						throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pickup_location_customer_number_invalid', esc_html_x( 'Sorry, your pickup location customer number is invalid.', 'shipments', 'woocommerce-germanized' ), 400 );
					}
				}
			}

			$pickup_location_code = $pickup_location->get_code();
			$pickup_location->replace_address( $order );

			$order->update_meta_data( '_pickup_location_code', $pickup_location_code );
			$order->update_meta_data( '_pickup_location_address', $pickup_location->get_address() );

			if ( $supports_customer_number ) {
				$order->update_meta_data( '_pickup_location_customer_number', $pickup_location_customer_number );
			}
		}

		if ( $order->get_customer_id() ) {
			$wc_customer = new \WC_Customer( $order->get_customer_id() );

			$wc_customer->update_meta_data( 'pickup_location_code', '' );
			$wc_customer->update_meta_data( 'pickup_location_customer_number', '' );

			if ( $pickup_location ) {
				$wc_customer->update_meta_data( 'pickup_location_code', $pickup_location->get_code() );
				$pickup_location->replace_address( $wc_customer );

				if ( $pickup_location->supports_customer_number() ) {
					$wc_customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );
				}
			}

			$wc_customer->save();
		}

		if ( $customer = wc()->customer ) {
			$customer->update_meta_data( 'pickup_location_code', '' );
			$customer->update_meta_data( 'pickup_location_customer_number', '' );

			if ( $pickup_location ) {
				$customer->update_meta_data( 'pickup_location_code', $pickup_location->get_code() );
				$pickup_location->replace_address( $customer );

				if ( $pickup_location->supports_customer_number() ) {
					$customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );
				}
			}

			$customer->save();
		}
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( Package::container()->get( Integrations\CheckoutPickupLocationSelect::class ) );
			}
		);
	}

	/**
	 * Use woocommerce-shiptastic as namespace to not conflict with the
	 * shiptastic-for-woocommerce textdomain which might get replaced within js files
	 * while bundling the package.
	 *
	 * @return void
	 */
	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-shiptastic',
				'data_callback'   => function () {
					return $this->get_cart_data();
				},
				'schema_callback' => function () {
					return $this->get_cart_schema();
				},
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-shiptastic',
				'schema_callback' => function () {
					return $this->get_checkout_schema();
				},
			)
		);
	}

	private function get_checkout_schema() {
		return array(
			'pickup_location'                 => array(
				'description' => _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'pickup_location_customer_number' => array(
				'description' => _x( 'Pickup location customer number', 'shipments', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
		);
	}

	private function get_cart_schema() {
		$pickup_location_schema = $this->schema_controller->get( 'search-pickup-locations' )->get_item_schema();

		$schema = array(
			'pickup_location_delivery_available'      => array(
				'description' => _x( 'Whether pickup location delivery is available', 'shipments', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'default_pickup_location'                 => array(
				'description' => _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
				'readonly'    => true,
			),
			'default_pickup_location_customer_number' => array(
				'description' => _x( 'Pickup location customer number', 'shipments', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
				'readonly'    => true,
			),
		);

		$schema['pickup_locations'] = $pickup_location_schema['properties']['pickup_locations'];

		return $schema;
	}

	private function get_cart_data() {
		$customer     = wc()->customer;
		$provider     = false;
		$is_available = false;
		$locations    = array();

		if ( PickupDelivery::is_available() ) {
			$shipping_method = wc_stc_get_current_shipping_provider_method();

			if ( $shipping_method ) {
				$provider = $shipping_method->get_shipping_provider_instance();
			}

			if ( $provider && is_a( $provider, '\Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
				$address = array(
					'postcode'  => $customer->get_shipping_postcode(),
					'country'   => $customer->get_shipping_country(),
					'address_1' => $customer->get_shipping_address_1(),
					'city'      => $customer->get_shipping_city(),
				);

				$query_args   = PickupDelivery::get_pickup_delivery_cart_args();
				$is_available = $provider->supports_pickup_location_delivery( $address, $query_args );

				if ( $is_available ) {
					$locations = $provider->get_pickup_locations( $address, $query_args );
				}
			}
		}

		return array(
			'pickup_location_delivery_available'      => $is_available && ! empty( $locations ),
			'default_pickup_location'                 => WC()->customer->get_meta( 'pickup_location_code' ),
			'default_pickup_location_customer_number' => WC()->customer->get_meta( 'pickup_location_customer_number' ),
			'pickup_locations'                        => array_map(
				function ( $location ) {
					return $location->get_data();
				},
				$locations
			),
		);
	}
}
