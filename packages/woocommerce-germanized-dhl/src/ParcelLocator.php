<?php

namespace Vendidero\Germanized\DHL;

use Exception;
use Vendidero\Germanized\Shipments\Interfaces\ShippingProvider;
use Vendidero\Germanized\Shipments\PickupDelivery;
use Vendidero\Germanized\Shipments\Shipment;
use WC_Checkout;
use WC_Order;
use WP_Error;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class ParcelLocator {

	public static function init() {
		add_filter( 'woocommerce_gzd_shipment_order_pickup_location_code', array( __CLASS__, 'legacy_pickup_location_code' ), 10, 2 );
		add_filter( 'woocommerce_gzd_shipment_order_pickup_location_customer_number', array( __CLASS__, 'legacy_pickup_location_customer_number' ), 10, 2 );

		add_filter( 'woocommerce_shipment_get_pickup_location_customer_number', array( __CLASS__, 'legacy_shipment_postnumber' ), 10, 2 );
		add_filter( 'woocommerce_gzd_shipment_customer_pickup_location_customer_number', array( __CLASS__, 'legacy_user_postnumber' ), 10, 2 );

		add_action( 'woocommerce_after_save_address_validation', array( __CLASS__, 'remove_legacy_customer_data' ), 10, 4 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'remove_legacy_order_data' ), 50 );
	}

	public static function remove_legacy_order_data( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( $order->get_meta( '_shipping_dhl_postnumber' ) ) {
				$order->delete_meta_data( '_shipping_dhl_postnumber' );
				$order->delete_meta_data( '_shipping_address_type' );

				$order->save();
			}
		}
	}

	/**
	 * @param $user_id
	 * @param $address_type
	 * @param $address
	 * @param \WC_Customer $customer
	 *
	 * @return void
	 */
	public static function remove_legacy_customer_data( $user_id, $address_type, $address, $customer ) {
		if ( 'shipping' === $address_type ) {
			$customer->delete_meta_data( 'shipping_dhl_postnumber' );
			$customer->delete_meta_data( 'shipping_address_type' );
			$customer->delete_meta_data( 'shipping_parcelshop_post_number' );
		}
	}

	/**
	 * @param string $pickup_code
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function legacy_pickup_location_code( $pickup_code, $order ) {
		if ( empty( $pickup_code ) ) {
			if ( self::order_has_pickup( $order ) ) {
				$keyword_id = self::extract_pickup_keyword_id( self::get_pickup_address_by_order( $order ) );

				if ( ! empty( $keyword_id ) ) {
					$pickup_code = $keyword_id;
				}
			}
		}

		return $pickup_code;
	}

	/**
	 * @param string $pickup_code
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function legacy_pickup_location_customer_number( $customer_number, $order ) {
		if ( empty( $customer_number ) ) {
			if ( self::order_has_pickup( $order ) ) {
				$customer_number = self::get_postnumber_by_order( $order );
			}
		}

		return $customer_number;
	}

	/**
	 * @param $customer_number
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	public static function legacy_shipment_postnumber( $customer_number, $shipment ) {
		if ( empty( $customer_number ) ) {
			$address = $shipment->get_address();

			if ( isset( $address['dhl_postnumber'] ) ) {
				$customer_number = $address['dhl_postnumber'];
			}
		}

		return $customer_number;
	}

	/**
	 * @param $customer_number
	 * @param \WC_Customer $customer
	 *
	 * @return string
	 */
	public static function legacy_user_postnumber( $customer_number, $customer ) {
		if ( empty( $customer_number ) ) {
			if ( $customer->get_id() > 0 && self::get_postnumber_by_user( $customer->get_id() ) ) {
				$customer_number = self::get_postnumber_by_user( $customer->get_id() );
			}
		}

		return $customer_number;
	}

	public static function get_postnumber_by_shipment( $shipment ) {
		if ( is_numeric( $shipment ) ) {
			$shipment = wc_gzd_get_shipment( $shipment );
		}

		return self::remove_whitespace( $shipment->get_pickup_location_customer_number() );
	}

	public static function get_supported_countries() {
		$countries = array( 'DE', 'AT' );

		/**
		 * Check if the address_2 field has been removed, e.g. via customizer as
		 * the address_2 field is necessary for non-DE pickup stations.
		 */
		if ( 'hidden' === get_option( 'woocommerce_checkout_address_2_field', 'optional' ) ) {
			$countries = array( 'DE' );
		}

		/**
		 * Filter to enable DHL parcel shop delivery for certain countries.
		 *
		 * @param array $country_codes Array of country codes which support DHL parcel shop delivery.
		 *
		 * @package Vendidero/Germanized/DHL
		 */
		$codes = apply_filters( 'woocommerce_gzd_dhl_parcel_locator_countries', $countries );

		return $codes;
	}

	public static function get_excluded_gateways() {
		/**
		 * Filter to disable DHL parcel shop delivery for certain gateways.
		 *
		 * @param array $gateways Array of gateway IDs to exclude.
		 *
		 * @package Vendidero/Germanized/DHL
		 */
		$codes = apply_filters( 'woocommerce_gzd_dhl_parcel_locator_excluded_gateways', PickupDelivery::get_excluded_gateways() );

		return $codes;
	}

	public static function get_postnumber_by_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$post_number = '';

		if ( $order ) {
			if ( $order->get_meta( '_shipping_dhl_postnumber' ) ) {
				$post_number = $order->get_meta( '_shipping_dhl_postnumber' );
			}
		}

		/**
		 * Filter to adjust the DHL postnumber for a certain order.
		 *
		 * @param string   $post_number The post number.
		 * @param WC_Order $order The order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_order_postnumber', $post_number, $order );
	}

	public static function get_shipping_address_type_by_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$address_type = 'regular';

		if ( $order ) {
			if ( $type = $order->get_meta( '_shipping_address_type' ) ) {
				$address_type = $type;
			}
		}

		return $address_type;
	}

	public static function get_pickup_address_by_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$address = '';

		if ( $order ) {
			$address_field        = self::get_pickup_address_field_by_country( $order->get_shipping_country() );
			$address_field_getter = 'get_shipping_' . $address_field;

			if ( is_callable( array( $order, $address_field_getter ) ) ) {
				$address = $order->{$address_field_getter}();
			}
		}

		return $address;
	}

	public static function order_has_pickup( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$has_pickup = false;

		if ( $order ) {
			$address_type = self::get_shipping_address_type_by_order( $order );
			$keyword_id   = self::extract_pickup_keyword_id( self::get_pickup_address_by_order( $order ) );
			$country      = $order->get_shipping_country();

			if ( ! empty( $country ) && in_array( $country, self::get_supported_countries(), true ) && 'dhl' === $address_type && ! empty( $keyword_id ) ) {
				$has_pickup = true;
			}
		}

		return $has_pickup;
	}

	public static function get_postnumber_by_user( $user ) {
		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		$post_number = '';

		if ( $user ) {

			if ( get_user_meta( $user->ID, 'shipping_dhl_postnumber', true ) ) {
				$post_number = get_user_meta( $user->ID, 'shipping_dhl_postnumber', true );
			}

			if ( get_user_meta( $user->ID, 'shipping_parcelshop_post_number', true ) ) {
				$post_number = get_user_meta( $user->ID, 'shipping_parcelshop_post_number', true );
			}
		}

		/**
		 * Filter to adjust the DHL postnumber for a certain user.
		 *
		 * @param string   $post_number The post number.
		 * @param WP_User $user The user object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_user_postnumber', $post_number, $user );
	}

	protected static function remove_whitespace( $str ) {
		return trim( preg_replace( '/\s+/', '', $str ) );
	}

	/**
	 * @param $key
	 * @param string $provider_name
	 *
	 * @return mixed
	 */
	protected static function get_setting( $key, $provider_name = false ) {
		$option_key = 'parcel_pickup_' . $key;

		if ( $provider_name ) {
			$option_key = $provider_name . '_' . $option_key;
		}

		$setting = Package::get_setting( $option_key );

		return $setting;
	}

	public static function is_enabled() {
		return Package::base_country_supports( 'pickup' );
	}

	public static function is_available( $provider = false ) {
		return self::is_packstation_enabled( $provider ) || self::is_parcelshop_enabled( $provider ) || self::is_postoffice_enabled( $provider );
	}

	protected static function shipping_provider_supports_locations( $provider, $location_type = 'packstation' ) {
		if ( 'packstation' === $location_type ) {
			$is_supported = in_array( $provider, array( 'dhl', 'deutsche_post' ), true );
		} else {
			$is_supported = in_array( $provider, array( 'dhl' ), true );
		}

		return apply_filters( 'woocommerce_gzd_dhl_provider_supports_pickup_location', $is_supported, $provider, $location_type );
	}

	public static function is_postoffice_enabled( $provider = false ) {
		$is_enabled = 'yes' === self::get_setting( 'postoffice_enable', $provider );

		if ( false !== $provider ) {
			if ( ! self::shipping_provider_supports_locations( $provider, 'postoffice' ) ) {
				$is_enabled = false;
			}
		}

		return $is_enabled;
	}

	public static function is_packstation_enabled( $provider = false ) {
		$is_enabled = 'yes' === self::get_setting( 'packstation_enable', $provider );

		if ( false !== $provider ) {
			if ( ! self::shipping_provider_supports_locations( $provider, 'packstation' ) ) {
				$is_enabled = false;
			}
		}

		return $is_enabled;
	}

	public static function is_parcelshop_enabled( $provider = false ) {
		$is_enabled = 'yes' === self::get_setting( 'parcelshop_enable', $provider );

		if ( false !== $provider ) {
			if ( ! self::shipping_provider_supports_locations( $provider, 'parcelshop' ) ) {
				$is_enabled = false;
			}
		}

		return $is_enabled;
	}

	public static function get_max_results() {
		return Package::get_dhl_shipping_provider()->get_pickup_locations_max_results();
	}

	public static function extract_pickup_keyword_id( $str ) {
		$keyword_id = '';

		preg_match_all( '/([A-Z]{2}-)?[0-9]+/', $str, $matches );

		if ( $matches && count( $matches ) > 0 ) {
			if ( isset( $matches[0][0] ) ) {
				$keyword_id = $matches[0][0];
			}
		}

		return $keyword_id;
	}

	public static function get_pickup_address_field_by_country( $country = '' ) {
		$country = '' === $country ? Package::get_base_country() : $country;

		return 'DE' === $country ? 'address_1' : 'address_2';
	}
}
