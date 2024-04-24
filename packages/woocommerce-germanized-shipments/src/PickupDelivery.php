<?php

namespace Vendidero\Germanized\Shipments;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use \Exception;
use WC_Order;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class PickupDelivery {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'set_formatted_shipping_address' ), 20, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'set_formatted_user_shipping_address' ), 10, 3 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'formatted_shipping_replacements' ), 20, 2 );

		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'register_classic_checkout_fields' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'register_order_review_fragments' ), 10, 1 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'register_classic_checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'register_classic_checkout_order_data' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_classic_checkout_scripts' ), 100 );

		add_filter( 'woocommerce_form_field_wc_gzd_shipments_pickup_location', array( __CLASS__, 'register_pickup_location_field' ), 10, 4 );
		add_filter( 'woocommerce_form_field_wc_gzd_shipments_pickup_location_customer_number', array( __CLASS__, 'register_pickup_location_customer_number_field' ), 10, 4 );

		add_filter( 'woocommerce_admin_shipping_fields', array( __CLASS__, 'register_pickup_location_admin_fields' ), 10, 3 );
	}

	public static function register_pickup_location_admin_fields( $fields, $order = null, $context = 'edit' ) {
		if ( is_null( $order ) && version_compare( wc()->version, '8.6.0', '<' ) ) {
			global $theorder;

			if ( isset( $theorder ) ) {
				$order = $theorder;
			}
		}

		if ( ! $order instanceof \WC_Order ) {
			return $fields;
		}

		if ( $shipment_order = wc_gzd_get_shipment_order( $order ) ) {
			if ( $shipment_order->supports_pickup_location() ) {
				$fields['pickup_location_code'] = array(
					'label' => _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ),
					'type'  => 'text',
					'id'    => '_pickup_location_code',
					'show'  => false,
					'value' => $shipment_order->get_pickup_location_code(),
				);

				$fields['pickup_location_customer_number'] = array(
					'label' => _x( 'Pickup customer number', 'shipments', 'woocommerce-germanized' ),
					'show'  => false,
					'id'    => '_pickup_location_customer_number',
					'type'  => 'text',
					'value' => $shipment_order->get_pickup_location_customer_number(),
				);
			}
		}

		return $fields;
	}

	public static function register_pickup_location_customer_number_field( $field, $key, $args, $value ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'               => $key,
				'priority'         => '',
				'current_location' => null,
				'required'         => false,
				'hidden'           => true,
				'class'            => array(),
			)
		);

		if ( $args['current_location'] ) {
			if ( $args['current_location']->supports_customer_number() ) {
				$args['hidden'] = false;

				if ( $args['current_location']->customer_number_is_mandatory() ) {
					$args['required'] = true;
				}
			}
		}

		$args['type']   = 'text';
		$args['label']  = _x( 'Customer Number', 'shipments', 'woocommerce-germanized' );
		$args['return'] = true;

		if ( $args['hidden'] ) {
			$args['class'][] = 'hidden';
		}

		$field = woocommerce_form_field( $key, $args, $value );

		return $field;
	}

	public static function register_pickup_location_field( $field, $key, $args, $value ) {
		$args = wp_parse_args(
			$args,
			array(
				'locations'         => array(),
				'id'                => $key,
				'priority'          => '',
				'value'             => null,
				'required'          => false,
				'custom_attributes' => array(),
				'hidden'            => true,
				'class'             => array(),
			)
		);

		$args['options']                             = array(
			'' => _x( 'None', 'shipments-default-pickup-location', 'woocommerce-germanized' ),
		);
		$args['custom_attributes']['data-locations'] = array();
		$args['type']                                = 'select';
		$args['placeholder']                         = _x( 'Select pickup location', 'shipments', 'woocommerce-germanized' );
		$args['return']                              = true;

		foreach ( $args['locations'] as $location ) {
			$args['options'][ $location->get_code() ]                             = $location->get_formatted_address();
			$args['custom_attributes']['data-locations'][ $location->get_code() ] = $location->get_data();
		}

		$args['custom_attributes']['data-locations'] = wp_json_encode( $args['custom_attributes']['data-locations'] );

		if ( count( $args['options'] ) > 1 ) {
			$args['hidden'] = false;
		}

		if ( $args['hidden'] ) {
			$args['class'][] = 'hidden';
		}

		$field = woocommerce_form_field( $key, $args, $value );

		return $field;
	}

	/**
	 * @param WC_Order $order
	 * @param array $data
	 *
	 * @return void
	 */
	public static function register_classic_checkout_order_data( $order, $data ) {
		if ( ! self::is_available() ) {
			return;
		}

		$pickup_location_code            = isset( $data['pickup_location'] ) ? trim( wc_clean( $data['pickup_location'] ) ) : '';
		$pickup_location_customer_number = isset( $data['pickup_location_customer_number'] ) ? trim( wc_clean( $data['pickup_location_customer_number'] ) ) : '';

		if ( '-1' === $pickup_location_code || empty( $pickup_location_code ) ) {
			$pickup_location_code = '';
		}

		if ( ! empty( $pickup_location_code ) ) {
			if ( $provider = wc_gzd_get_order_shipping_provider( $order ) ) {
				if ( is_a( $provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
					$address_data = array(
						'country'   => $order->get_shipping_country(),
						'postcode'  => $order->get_shipping_postcode(),
						'city'      => $order->get_shipping_city(),
						'address_1' => $order->get_shipping_address_1(),
					);

					$query_args = self::get_pickup_delivery_cart_args();

					if ( $provider->supports_pickup_location_delivery( $address_data, $query_args ) ) {
						$pickup_location = $provider->get_pickup_location_by_code( $pickup_location_code, $address_data );

						if ( $pickup_location ) {
							$order->update_meta_data( '_pickup_location_code', $pickup_location_code );

							if ( $pickup_location->supports_customer_number() ) {
								$order->update_meta_data( '_pickup_location_customer_number', $pickup_location_customer_number );
							}

							$pickup_location->replace_address( $order );

							if ( $order->get_customer_id() ) {
								$wc_customer = new \WC_Customer( $order->get_customer_id() );

								$wc_customer->update_meta_data( 'pickup_location_code', $pickup_location_code );
								$pickup_location->replace_address( $wc_customer );

								if ( $pickup_location->supports_customer_number() ) {
									$wc_customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );
								}

								$wc_customer->save();
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param array $data
	 * @param \WP_Error $errors
	 *
	 * @return void
	 */
	public static function register_classic_checkout_validation( $data, $errors ) {
		if ( ! self::is_available() ) {
			return;
		}

		$pickup_location_code            = isset( $data['pickup_location'] ) ? trim( wc_clean( $data['pickup_location'] ) ) : '';
		$pickup_location_customer_number = isset( $data['pickup_location_customer_number'] ) ? wc_clean( $data['pickup_location_customer_number'] ) : '';

		if ( '-1' === $pickup_location_code || empty( $pickup_location_code ) ) {
			$pickup_location_code = '';
		}

		if ( ! empty( $pickup_location_code ) ) {
			$supports_customer_number     = false;
			$customer_number_is_mandatory = false;
			$is_valid                     = false;
			$pickup_location              = false;
			$address_data                 = array(
				'country'   => isset( $data['shipping_country'] ) ? $data['shipping_country'] : $data['billing_country'],
				'postcode'  => isset( $data['shipping_postcode'] ) ? $data['shipping_postcode'] : $data['billing_postcode'],
				'city'      => isset( $data['shipping_city'] ) ? $data['shipping_city'] : $data['billing_city'],
				'address_1' => isset( $data['shipping_address_1'] ) ? $data['shipping_address_1'] : $data['billing_address_1'],
			);

			if ( $method = wc_gzd_get_current_shipping_provider_method() ) {
				if ( $provider = $method->get_shipping_provider_instance() ) {
					if ( is_a( $provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
						$pickup_location = $provider->get_pickup_location_by_code( $pickup_location_code, $address_data );
						$is_valid        = $provider->is_valid_pickup_location( $pickup_location_code, $address_data );

						$supports_customer_number     = $pickup_location ? $pickup_location->supports_customer_number() : false;
						$customer_number_is_mandatory = $pickup_location ? $pickup_location->customer_number_is_mandatory() : false;
					}
				}
			}

			if ( ! $is_valid || ! $pickup_location ) {
				$errors->add( 'pickup_location_unknown', _x( 'Sorry, your current pickup location is not supported.', 'shipments', 'woocommerce-germanized' ), array( 'id' => 'pickup_location' ) );
			} elseif ( $supports_customer_number && ( ! empty( $pickup_location_customer_number ) || $customer_number_is_mandatory ) ) {
				if ( ! $validation = $pickup_location->customer_number_is_valid( $pickup_location_customer_number ) ) {
					if ( is_a( $validation, 'WP_Error' ) ) {
						$errors->add( 'pickup_location_customer_number_invalid', $validation->get_error_message(), array( 'id' => 'pickup_location_customer_number' ) );
					} else {
						$errors->add( 'pickup_location_customer_number_invalid', _x( 'Sorry, your pickup location customer number is invalid.', 'shipments', 'woocommerce-germanized' ), array( 'id' => 'pickup_location_customer_number' ) );
					}
				}
			}
		}
	}

	public static function register_order_review_fragments( $fragments ) {
		if ( ! self::is_available() ) {
			return $fragments;
		}

		$locations = array();

		if ( $method = wc_gzd_get_current_shipping_provider_method() ) {
			if ( $provider = $method->get_shipping_provider_instance() ) {
				if ( is_a( $provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
					$address = array(
						'country'   => wc()->customer->get_shipping_country(),
						'postcode'  => wc()->customer->get_shipping_postcode(),
						'address_1' => wc()->customer->get_shipping_address_1(),
					);

					$query_args = self::get_pickup_delivery_cart_args();

					if ( $provider->supports_pickup_location_delivery( $address, $query_args ) ) {
						$locations = $provider->get_pickup_locations( $address, $query_args );
					}
				}
			}
		}

		if ( ! empty( $locations ) ) {
			$new_locations = array();

			foreach ( $locations as $location ) {
				$new_locations[ $location->get_code() ] = $location->get_data();
			}

			$locations = $new_locations;
		}

		$fragments['.gzd-shipments-pickup-locations'] = wp_json_encode( $locations );

		return $fragments;
	}

	public static function register_classic_checkout_scripts() {
		if ( ! is_checkout() || ! self::is_available() ) {
			return;
		}

		wp_register_script( 'wc-gzd-shipments-pickup-locations', Package::get_assets_url( 'static/pickup-locations.js' ), array( 'jquery', 'wc-checkout' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_add_inline_style( 'woocommerce-layout', '#pickup_location_field.hidden, #pickup_location_customer_number_field.hidden { display: none; } #pickup_location_field .select2-selection__clear { margin-right: 5px; padding: 0 3px; }' );

		wp_localize_script(
			'wc-gzd-shipments-pickup-locations',
			'wc_gzd_shipments_pickup_locations_params',
			array()
		);

		wp_enqueue_script( 'wc-gzd-shipments-pickup-locations' );
	}

	public static function get_pickup_delivery_cart_args() {
		$max_weight      = wc_get_weight( (float) wc()->cart->get_cart_contents_weight(), wc_gzd_get_packaging_weight_unit() );
		$shipping_method = wc_gzd_get_current_shipping_provider_method();
		$max_dimensions  = array(
			'length' => 0.0,
			'width'  => 0.0,
			'height' => 0.0,
		);

		if ( $shipping_method && is_a( $shipping_method->get_method(), 'Vendidero\Germanized\Shipments\ShippingMethod\ShippingMethod' ) ) {
			$controller              = new CartController();
			$cart                    = wc()->cart;
			$has_calculated_shipping = $cart->show_shipping();
			$shipping_packages       = $has_calculated_shipping ? $controller->get_shipping_packages() : array();
			$current_rate_id         = wc_gzd_get_current_shipping_method_id();

			if ( isset( $shipping_packages[0]['rates'][ $current_rate_id ] ) ) {
				$rate = $shipping_packages[0]['rates'][ $current_rate_id ];

				if ( is_a( $rate, 'WC_Shipping_Rate' ) ) {
					$meta = $rate->get_meta_data();

					if ( isset( $meta['_packages'] ) ) {
						$max_weight = 0.0;

						foreach ( (array) $meta['_packages'] as $package_data ) {
							$packaging_id = $package_data['packaging_id'];

							if ( $packaging = wc_gzd_get_packaging( $packaging_id ) ) {
								$package_weight = (float) wc_get_weight( $package_data['weight'], wc_gzd_get_packaging_weight_unit(), 'g' );

								if ( (float) $packaging->get_length() > $max_dimensions['length'] ) {
									$max_dimensions['length'] = (float) $packaging->get_length();
								}
								if ( (float) $packaging->get_width() > $max_dimensions['width'] ) {
									$max_dimensions['width'] = (float) $packaging->get_width();
								}
								if ( (float) $packaging->get_height() > $max_dimensions['height'] ) {
									$max_dimensions['height'] = (float) $packaging->get_height();
								}

								if ( $package_weight > $max_weight ) {
									$max_weight = $package_weight;
								}
							}
						}
					}
				}
			}
		}

		if ( empty( $max_dimensions['length'] ) ) {
			foreach ( wc()->cart->get_cart() as $values ) {
				if ( $product = wc_gzd_shipments_get_product( $values['data'] ) ) {
					if ( $product->has_dimensions() ) {
						$length = (float) wc_get_dimension( (float) $product->get_shipping_length(), wc_gzd_get_packaging_dimension_unit() );
						$width  = (float) wc_get_dimension( (float) $product->get_shipping_width(), wc_gzd_get_packaging_dimension_unit() );
						$height = (float) wc_get_dimension( (float) $product->get_shipping_height(), wc_gzd_get_packaging_dimension_unit() );

						if ( $length > $max_dimensions['length'] ) {
							$max_dimensions['length'] = $length;
						}
						if ( $width > $max_dimensions['width'] ) {
							$max_dimensions['width'] = $width;
						}
						if ( $height > $max_dimensions['height'] ) {
							$max_dimensions['height'] = $height;
						}
					}
				}
			}
		}

		return array(
			'max_weight'      => $max_weight,
			'max_dimensions'  => $max_dimensions,
			'payment_gateway' => WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '',
		);
	}

	public static function is_enabled() {
		return apply_filters( 'woocommerce_gzd_shipments_enable_pickup_delivery', true );
	}

	public static function is_available() {
		$available = self::is_enabled();

		if ( wc()->cart && ! wc()->cart->needs_shipping() ) {
			$available = false;
		}

		if ( 'billing_only' === get_option( 'woocommerce_ship_to_destination' ) ) {
			$available = false;
		}

		return apply_filters( 'woocommerce_gzd_shipments_pickup_delivery_available', $available );
	}

	public static function register_classic_checkout_fields( $fields ) {
		if ( ! self::is_available() || ! wc()->customer ) {
			return $fields;
		}

		$locations                   = array();
		$current_location_code       = '';
		$current_customer_number     = '';
		$current_location            = null;
		$customer_number_field_label = _x( 'Customer Number', 'shipments', 'woocommerce-germanized' );

		if ( $method = wc_gzd_get_current_shipping_provider_method() ) {
			if ( $provider = $method->get_shipping_provider_instance() ) {
				if ( is_a( $provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
					$address = array(
						'country'   => wc()->customer->get_shipping_country(),
						'postcode'  => wc()->customer->get_shipping_postcode(),
						'address_1' => wc()->customer->get_shipping_address_1(),
					);

					$query_args = self::get_pickup_delivery_cart_args();

					if ( $provider->supports_pickup_location_delivery( $address, $query_args ) ) {
						$locations             = $provider->get_pickup_locations( $address, $query_args );
						$current_location_code = self::get_pickup_location_code_by_user();

						if ( $current_location_code ) {
							$current_location = $provider->get_pickup_location_by_code( $current_location_code, $address );

							if ( ! $current_location ) {
								$current_location_code = '';
							} else {
								$locations[] = $current_location;

								if ( $current_location->supports_customer_number() ) {
									$current_customer_number     = self::get_pickup_location_customer_number_by_customer();
									$customer_number_field_label = $current_location->get_customer_number_field_label();
								}
							}
						}
					}
				}
			}
		}

		$fields['order']['pickup_location'] = array(
			'type'      => 'wc_gzd_shipments_pickup_location',
			'locations' => $locations,
			'default'   => $current_location_code,
			'label'     => _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ),
		);

		$fields['order']['pickup_location_customer_number'] = array(
			'type'             => 'wc_gzd_shipments_pickup_location_customer_number',
			'label'            => $customer_number_field_label,
			'current_location' => $current_location,
			'default'          => $current_customer_number,
		);

		return $fields;
	}

	/**
	 * @param $fields
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function set_formatted_shipping_address( $fields, $order ) {
		if ( ! empty( $fields ) && is_array( $fields ) ) {
			$shipment_order  = wc_gzd_get_shipment_order( $order );
			$customer_number = $shipment_order->get_pickup_location_customer_number();

			if ( $shipment_order->has_pickup_location() && ! empty( $customer_number ) ) {
				$fields['pickup_location_customer_number'] = $customer_number;
			}
		}

		return $fields;
	}

	public static function set_formatted_user_shipping_address( $address, $customer_id, $name ) {
		if ( 'shipping' === $name ) {
			if ( $customer_number = self::get_pickup_location_customer_number_by_customer( $customer_id ) ) {
				$address['pickup_location_customer_number'] = $customer_number;
			}
		}

		return $address;
	}

	public static function get_pickup_location_customer_number_by_customer( $customer_id = false ) {
		$customer        = self::get_customer( $customer_id );
		$customer_number = '';

		if ( ! $customer ) {
			return '';
		}

		if ( $customer->get_meta( 'pickup_location_customer_number' ) ) {
			$customer_number = $customer->get_meta( 'pickup_location_customer_number' );
		}

		return apply_filters( 'woocommerce_gzd_shipment_customer_pickup_location_customer_number', $customer_number, $customer );
	}

	protected static function get_customer( $customer_id = false ) {
		$customer = false;

		if ( is_numeric( $customer_id ) ) {
			$customer = new \WC_Customer( $customer_id );
		} elseif ( is_a( $customer_id, 'WC_Customer' ) ) {
			$customer = $customer_id;
		} elseif ( wc()->customer ) {
			$customer = wc()->customer;
		}

		return $customer;
	}

	public static function get_pickup_location_code_by_user( $customer_id = false ) {
		$customer    = self::get_customer( $customer_id );
		$pickup_code = '';

		if ( ! $customer ) {
			return '';
		}

		if ( $customer->get_meta( 'pickup_location_code' ) ) {
			$pickup_code = $customer->get_meta( 'pickup_location_code' );
		}

		return apply_filters( 'woocommerce_gzd_shipment_customer_pickup_location_code', $pickup_code, $customer );
	}

	public static function formatted_shipping_replacements( $fields, $args ) {
		if ( isset( $args['pickup_location_customer_number'] ) && ! empty( $args['pickup_location_customer_number'] ) ) {
			$fields['{name}'] = $fields['{name}'] . "\n" . $args['pickup_location_customer_number'];
		}

		return $fields;
	}
}
