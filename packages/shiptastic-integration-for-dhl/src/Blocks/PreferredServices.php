<?php
namespace Vendidero\Shiptastic\DHL\Blocks;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\ParcelServices;

final class PreferredServices {

	public function __construct() {
		$this->register_endpoint_data();
		$this->register_integrations();
		$this->register_validation_and_storage();
	}

	private function register_validation_and_storage() {
		/**
		 * Use this hook to make sure fees are registered when (re)calculating cart.
		 */
		add_action(
			'woocommerce_store_api_checkout_update_customer_from_request',
			function ( $customer, $request ) {
				$request_data = $this->get_checkout_data_from_request( $request );

				add_filter(
					'woocommerce_stc_dhl_checkout_get_current_payment_method',
					function () use ( $request ) {
						$payment_method = wc_clean( wp_unslash( $request['payment_method'] ? $request['payment_method'] : '' ) );

						return $payment_method;
					}
				);

				add_filter(
					'woocommerce_stc_dhl_checkout_parcel_services_data',
					function ( $data ) use ( $request_data, $customer ) {
						$data['shipping_country'] = $customer->get_shipping_country();

						foreach ( $request_data as $k => $d ) {
							$data[ "dhl_{$k}" ] = $d;
						}

						return $data;
					}
				);
			},
			10,
			2
		);

		/**
		 * Validation and storage
		 */
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order, $request ) {
				$request_data = $this->get_checkout_data_from_request( $request );
				$customer     = wc()->customer;

				add_filter(
					'woocommerce_stc_dhl_checkout_get_current_payment_method',
					function () use ( $request ) {
						$payment_method = wc_clean( wp_unslash( $request['payment_method'] ? $request['payment_method'] : '' ) );

						return $payment_method;
					}
				);

				add_filter(
					'woocommerce_stc_dhl_checkout_parcel_services_data',
					function ( $data ) use ( $request_data, $customer ) {
						$data['shipping_country'] = $customer->get_shipping_country();

						foreach ( $request_data as $k => $d ) {
							$data[ "dhl_{$k}" ] = $d;
						}

						return $data;
					}
				);

				$errors = new \WP_Error();
				ParcelServices::validate( array(), $errors );

				if ( wc_stc_shipment_wp_error_has_errors( $errors ) ) {
					foreach ( $errors->get_error_messages() as $error ) {
						throw new RouteException( 'dhl_error', wp_kses_post( $error ), 400 );
					}
				}

				ParcelServices::create_order( $order );
			},
			10,
			2
		);
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( Package::container()->get( \Vendidero\Shiptastic\DHL\Blocks\Integrations\PreferredServices::class ) );
			}
		);
	}

	/**
	 * Use woocommerce-stc-dhl as namespace to not conflict with the
	 * shiptastic-integration-for-dhl textdomain which might get replaced within js files
	 * while bundling the package.
	 *
	 * @return void
	 */
	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-stc-dhl',
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
				'namespace'       => 'woocommerce-stc-dhl',
				'schema_callback' => function () {
					return $this->get_checkout_schema();
				},
			)
		);

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'woocommerce-stc-dhl-checkout-fees',
				'callback'  => function ( $data ) {
					$dhl = wp_parse_args(
						wc_clean( wp_unslash( $data ) ),
						array(
							'preferred_day'           => '',
							'preferred_delivery_type' => '',
						)
					);

					WC()->session->set( 'dhl_preferred_day', $dhl['preferred_day'] );
					WC()->session->set( 'dhl_preferred_delivery_type', $dhl['preferred_delivery_type'] );
				},
			)
		);
	}

	private function get_cart_schema() {
		return array(
			'preferred_day_enabled'           => array(
				'description' => _x( 'Preferred day enabled', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_day_cost'              => array(
				'description' => _x( 'Preferred day costs', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_day'                   => array(
				'description' => _x( 'Preferred day', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_home_delivery_cost'    => array(
				'description' => _x( 'Preferred delivery costs', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_delivery_type_enabled' => array(
				'description' => _x( 'Preferred delivery type enabled', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_delivery_type'         => array(
				'description' => _x( 'Preferred delivery type', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_location_enabled'      => array(
				'description' => _x( 'Preferred location enabled', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_neighbor_enabled'      => array(
				'description' => _x( 'Preferred neighbor enabled', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'preferred_days'                  => array(
				'description' => _x( 'Available preferred days', 'dhl', 'woocommerce-germanized' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'day'      => array(
							'description' => _x( 'The preferred day.', 'dhl', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'week_day' => array(
							'description' => _x( 'The formatted week day.', 'dhl', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date'     => array(
							'description' => _x( 'The preferred day date.', 'dhl', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);
	}

	private function get_cart_data() {
		$customer                = wc()->customer;
		$preferred_days          = array();
		$preferred_day           = '';
		$preferred_delivery_type = ParcelServices::get_default_preferred_delivery_type();

		if ( ParcelServices::is_preferred_day_enabled() && 'DE' === $customer->get_shipping_country() ) {
			$api_preferred_days = array();
			$shipping_postcode  = WC()->customer->get_shipping_postcode();

			if ( ! empty( $shipping_postcode ) ) {
				try {
					$api_preferred_days = Package::get_api()->get_preferred_available_days( $shipping_postcode );
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}

			foreach ( $api_preferred_days as $key => $preferred_day ) {
				$key          = empty( $key ) ? '' : $key;
				$week_day_num = empty( $key ) ? '-' : esc_html( date_i18n( 'j', strtotime( $key ) ) );

				$preferred_days[] = array(
					'day'      => $week_day_num,
					'week_day' => $preferred_day,
					'date'     => $key,
				);
			}

			$preferred_day = WC()->session->get( 'dhl_preferred_day' ) ? WC()->session->get( 'dhl_preferred_day' ) : '';

			if ( ! empty( $preferred_day ) && ! array_key_exists( $preferred_day, $api_preferred_days ) ) {
				WC()->session->set( 'dhl_preferred_day', '' );
				$preferred_day = '';
			}
		}

		if ( ParcelServices::is_preferred_delivery_type_enabled() && in_array( $customer->get_shipping_country(), ParcelServices::get_cdp_countries(), true ) ) {
			if ( WC()->session->get( 'dhl_preferred_delivery_type' ) ) {
				$preferred_delivery_type = WC()->session->get( 'dhl_preferred_delivery_type' );

				if ( ! array_key_exists( $preferred_delivery_type, ParcelServices::get_preferred_delivery_types() ) ) {
					$preferred_delivery_type = ParcelServices::get_default_preferred_delivery_type();
					WC()->session->set( 'dhl_preferred_delivery_type', '' );
				}
			}
		}

		$money_formatter = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\StoreApi\StoreApi::class )->container()->get( ExtendSchema::class )->get_formatter( 'money' );

		return array(
			'preferred_day_enabled'           => ParcelServices::is_preferred_day_enabled(),
			'preferred_day'                   => $preferred_day,
			'preferred_location_enabled'      => ParcelServices::is_preferred_location_enabled(),
			'preferred_neighbor_enabled'      => ParcelServices::is_preferred_neighbor_enabled(),
			'preferred_delivery_type_enabled' => ParcelServices::is_preferred_delivery_type_enabled(),
			'preferred_delivery_type'         => $preferred_delivery_type,
			'preferred_days'                  => $preferred_days,
			'preferred_day_cost'              => $money_formatter->format( ParcelServices::get_preferred_day_cost() ),
			'preferred_home_delivery_cost'    => $money_formatter->format( ParcelServices::get_preferred_home_delivery_cost() ),
		);
	}

	private function get_checkout_schema() {
		return array(
			'preferred_day'                       => array(
				'description' => _x( 'Preferred day', 'dhl', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'preferred_location_type'             => array(
				'description' => _x( 'Preferred location type', 'dhl', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'preferred_location'                  => array(
				'description' => _x( 'Preferred location', 'dhl', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'preferred_location_neighbor_name'    => array(
				'description' => _x( 'Preferred neighbor name', 'dhl', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'preferred_location_neighbor_address' => array(
				'description' => _x( 'Preferred neighbor name', 'dhl', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'preferred_delivery_type'             => array(
				'description' => _x( 'Preferred delivery type', 'dhl', 'woocommerce-germanized' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
		);
	}

	private function has_checkout_data( $param, $request ) {
		$request_data = isset( $request['extensions']['woocommerce-stc-dhl'] ) ? (array) $request['extensions']['woocommerce-stc-dhl'] : array();

		return isset( $request_data[ $param ] ) && null !== $request_data[ $param ];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	private function get_checkout_data_from_request( $request ) {
		$data = array_filter( isset( $request['extensions']['woocommerce-stc-dhl'] ) ? (array) wc_clean( $request['extensions']['woocommerce-stc-dhl'] ) : array() );

		$data = wp_parse_args(
			$data,
			array(
				'preferred_day'              => '',
				'preferred_location_type'    => '',
				'preferred_location'         => '',
				'preferred_neighbor_name'    => '',
				'preferred_neighbor_address' => '',
				'preferred_delivery_type'    => '',
			)
		);

		return $data;
	}
}
