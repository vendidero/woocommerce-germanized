<?php

namespace Vendidero\Germanized\DHL;

use Exception;
use Vendidero\Germanized\DHL\ShippingProvider\ShippingMethod;
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

	protected static $localized_scripts = array();

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_scripts' ) );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

		add_action( 'wp_head', array( __CLASS__, 'add_inline_styles' ), 50 );

		add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'add_shipping_fields' ), 10 );
		add_filter( 'woocommerce_admin_shipping_fields', array( __CLASS__, 'add_admin_shipping_fields' ), 10 );

		/**
		 * Checkout Hooks
		 */
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'manipulate_checkout_fields' ), 10 );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_checkout' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'maybe_adjust_order_data' ), 10, 2 );
		add_filter( 'woocommerce_get_order_address', array( __CLASS__, 'add_order_address_data' ), 10, 3 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'refresh_shipping_data_session' ), 10 );
		add_action( 'wp_ajax_nopriv_woocommerce_gzd_dhl_parcel_locator_refresh_shipping_data', array( __CLASS__, 'ajax_refresh_shipping_data' ) );
		add_action( 'wp_ajax_woocommerce_gzd_dhl_parcel_locator_refresh_shipping_data', array( __CLASS__, 'ajax_refresh_shipping_data' ) );

		add_filter( 'woocommerce_checkout_posted_data', array( __CLASS__, 'format_address_data' ), 10 );

		// Shipment Pickup
		add_filter( 'woocommerce_gzd_shipment_send_to_external_pickup', array( __CLASS__, 'shipment_has_pickup' ), 10, 3 );

		/**
		 * MyAccount Hooks
		 */
		add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'manipulate_address_fields' ), 20, 1 );
		add_filter( 'woocommerce_process_myaccount_field_shipping_address_type', array( __CLASS__, 'validate_address_fields' ), 10, 1 );
		add_filter( 'woocommerce_process_myaccount_field_shipping_dhl_postnumber', array( __CLASS__, 'validate_address_postnumber' ), 10, 1 );

		/**
		 * Profile Hooks
		 */
		add_filter( 'woocommerce_customer_meta_fields', array( __CLASS__, 'admin_profile_fields' ), 10, 1 );

		/**
		 * Address Hooks
		 */
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'set_formatted_shipping_address' ), 20, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( __CLASS__, 'set_formatted_billing_address' ), 20, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'set_formatted_address' ), 20, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'set_address_format' ), 20 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'set_user_address' ), 10, 3 );

		/**
		 * Street number validation
		 */
		add_filter( 'woocommerce_gzd_checkout_is_valid_street_number', array( __CLASS__, 'street_number_is_valid' ), 10, 2 );

		add_action(
			'init',
			function() {
				if ( self::has_map() ) {
					add_action( 'wp_footer', array( __CLASS__, 'add_form' ), 50 );

					add_action( 'wp_ajax_nopriv_woocommerce_gzd_dhl_parcelfinder_search', array( __CLASS__, 'ajax_search' ) );
					add_action( 'wp_ajax_woocommerce_gzd_dhl_parcelfinder_search', array( __CLASS__, 'ajax_search' ) );
				}

				add_action( 'wp_ajax_nopriv_woocommerce_gzd_dhl_parcel_locator_validate_address', array( __CLASS__, 'ajax_validate_address' ) );
				add_action( 'wp_ajax_woocommerce_gzd_dhl_parcel_locator_validate_address', array( __CLASS__, 'ajax_validate_address' ) );
			}
		);
	}

	public static function street_number_is_valid( $is_valid, $data ) {
		if ( isset( $data['shipping_address_type'] ) && 'dhl' === $data['shipping_address_type'] ) {
			$is_valid = true;
		}

		return $is_valid;
	}

	public static function refresh_shipping_data_session( $fragments ) {
		self::get_shipping_method_data();

		return $fragments;
	}

	public static function admin_profile_fields( $fields ) {

		if ( ! self::is_available() ) {
			return $fields;
		}

		$fields['shipping']['fields']['shipping_address_type']   = array(
			'label'       => _x( 'Address Type', 'dhl', 'woocommerce-germanized' ),
			'type'        => 'select',
			'options'     => self::get_address_types(),
			'description' => _x( 'Select whether delivery to DHL locations should be enabled.', 'dhl', 'woocommerce-germanized' ),
			'class'       => '',
			'priority'    => 50,
		);
		$fields['shipping']['fields']['shipping_dhl_postnumber'] = array(
			'label'       => _x( 'Postnumber', 'dhl', 'woocommerce-germanized' ),
			'type'        => 'text',
			'description' => _x( 'In case delivery to packstation is selected please fill in the corresponding DHL post number.', 'dhl', 'woocommerce-germanized' ),
			'priority'    => 60,
		);
		return $fields;
	}

	public static function manipulate_address_fields( $fields ) {
		global $wp;

		if ( ! self::is_available() ) {
			return $fields;
		}

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( wc_clean( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
			return $fields;
		}

		$nonce_key = ( isset( $_REQUEST['woocommerce-edit-address-nonce'] ) ? 'woocommerce-edit-address-nonce' : '_wpnonce' );

		if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] || empty( $_REQUEST[ $nonce_key ] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST[ $nonce_key ] ), 'woocommerce-edit_address' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return $fields;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return $fields;
		}

		if ( isset( $_POST['shipping_address_type'] ) && 'dhl' === $_POST['shipping_address_type'] ) {
			$country   = isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '';
			$field_key = self::get_pickup_address_field_by_country( $country );

			$fields[ "shipping_{$field_key}" ]['label'] = self::get_pickup_type_address_label();
		}

		return $fields;
	}

	public static function validate_address_postnumber( $value ) {
		if ( ! self::is_available() ) {
			return $value;
		}

		$shipping_country      = isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipping_address_type = isset( $_POST['shipping_address_type'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_type'] ) ) : 'regular'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Not a supported country
		if ( ! in_array( $shipping_country, self::get_supported_countries(), true ) ) {
			return '';
		}

		if ( 'dhl' === $shipping_address_type ) {
			$args = array(
				'address_1'  => isset( $_POST['shipping_address_1'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_1'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'address_2'  => isset( $_POST['shipping_address_2'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_2'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'postnumber' => isset( $_POST['shipping_dhl_postnumber'] ) ? wc_clean( wp_unslash( $_POST['shipping_dhl_postnumber'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'postcode'   => isset( $_POST['shipping_postcode'] ) ? wc_clean( wp_unslash( $_POST['shipping_postcode'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'city'       => isset( $_POST['shipping_city'] ) ? wc_clean( wp_unslash( $_POST['shipping_city'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'country'    => isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);

			$result = self::validate_address( $args );

			if ( is_wp_error( $result ) ) {
				return '';
			}
		} else {
			return '';
		}

		return self::remove_whitespace( $value );
	}

	public static function validate_address_fields( $value ) {
		if ( ! self::is_available() ) {
			return $value;
		}

		$shipping_country      = isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shipping_address_type = isset( $_POST['shipping_address_type'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_type'] ) ) : 'regular'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Not a supported country
		if ( ! in_array( $shipping_country, self::get_supported_countries(), true ) ) {
			return 'regular';
		}

		if ( ! array_key_exists( $shipping_address_type, self::get_address_types() ) ) {
			wc_add_notice( _x( 'Invalid address type.', 'dhl', 'woocommerce-germanized' ), 'error' );
		}

		if ( 'dhl' === $shipping_address_type ) {
			$args = array(
				'address_1'  => isset( $_POST['shipping_address_1'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_1'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'address_2'  => isset( $_POST['shipping_address_2'] ) ? wc_clean( wp_unslash( $_POST['shipping_address_2'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'postnumber' => isset( $_POST['shipping_dhl_postnumber'] ) ? wc_clean( wp_unslash( $_POST['shipping_dhl_postnumber'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'postcode'   => isset( $_POST['shipping_postcode'] ) ? wc_clean( wp_unslash( $_POST['shipping_postcode'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'city'       => isset( $_POST['shipping_city'] ) ? wc_clean( wp_unslash( $_POST['shipping_city'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'country'    => isset( $_POST['shipping_country'] ) ? wc_clean( wp_unslash( $_POST['shipping_country'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);

			$result = self::validate_address( $args );

			if ( is_wp_error( $result ) ) {
				foreach ( $result->get_error_messages() as $mesage ) {
					wc_add_notice( $mesage, 'error' );
				}
			}
		}

		return $value;
	}

	/**
	 * @param $send_to_pickup
	 * @param $types
	 * @param Shipment $shipment
	 */
	public static function shipment_has_pickup( $send_to_pickup, $types, $shipment ) {
		$data = $shipment->get_address();

		if ( isset( $data['address_type'] ) && 'dhl' === $data['address_type'] ) {
			$address_field_key = self::get_pickup_address_field_by_country( $shipment->get_country() );
			$address_getter    = "get_{$address_field_key}";
			$address_field_val = is_callable( array( $shipment, $address_getter ) ) ? $shipment->{$address_getter}() : '';
			$keyword_id        = self::extract_pickup_keyword_id( $address_field_val );

			if ( ! empty( $types ) ) {
				/**
				 * Missing indicator, e.g. Packstation, Postfiliale in address field - lookup actual name via API
				 * and add the prefix to the address field.
				 */
				if ( is_numeric( $keyword_id ) ) {
					$pickup_address_details = self::is_valid_pickup_address(
						array(
							'country'   => $shipment->get_country(),
							'address_1' => $shipment->get_address_1(),
							'address_2' => $shipment->get_address_2(),
							'postcode'  => $shipment->get_postcode(),
						)
					);

					if ( ! is_wp_error( $pickup_address_details ) ) {
						$address_field_val = $pickup_address_details['name'];
					}
				}

				foreach ( $types as $type ) {
					if ( wc_gzd_dhl_is_pickup_type( $address_field_val, $type ) ) {
						return true;
					}
				}
			} elseif ( ! empty( $keyword_id ) ) {
				return true;
			}
		}

		return $send_to_pickup;
	}

	public static function get_postnumber_by_shipment( $shipment ) {
		if ( is_numeric( $shipment ) ) {
			$shipment = wc_gzd_get_shipment( $shipment );
		}

		$postnumber = '';

		if ( $shipment ) {
			$address = $shipment->get_address();

			if ( isset( $address['dhl_postnumber'] ) ) {
				$postnumber = $address['dhl_postnumber'];
			}
		}

		return self::remove_whitespace( $postnumber );
	}

	/**
	 * @param WC_Order $order
	 * @param $data
	 */
	public static function maybe_adjust_order_data( $order, $data ) {
		if ( ! self::order_has_pickup( $order ) ) {
			$order->delete_meta_data( '_shipping_dhl_postnumber' );
			$order->update_meta_data( '_shipping_dhl_address_type', 'regular' );
		}
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
		$codes = apply_filters( 'woocommerce_gzd_dhl_parcel_locator_excluded_gateways', array( 'cod' ) );

		return $codes;
	}

	/**
	 * @param $data
	 * @param $type
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function add_order_address_data( $data, $type, $order ) {
		if ( 'shipping' === $type ) {
			if ( self::order_has_pickup( $order ) ) {
				$data['dhl_postnumber'] = self::get_postnumber_by_order( $order );
				$data['address_type']   = self::get_shipping_address_type_by_order( $order );
			}
		}

		return $data;
	}

	public static function set_address_format( $formats ) {
		foreach ( self::get_supported_countries() as $country ) {

			if ( ! array_key_exists( $country, $formats ) ) {
				continue;
			}

			$format = $formats[ $country ];
			$format = str_replace( '{name}', "{name}\n{dhl_postnumber}", $format );

			$formats[ $country ] = $format;
		}

		return $formats;
	}

	public static function set_formatted_shipping_address( $fields, $order ) {
		if ( ! empty( $fields ) && is_array( $fields ) ) {
			$fields['dhl_postnumber'] = '';

			if ( wc_gzd_dhl_order_has_pickup( $order ) ) {
				$fields['dhl_postnumber'] = self::get_postnumber_by_order( $order );
			}
		}

		return $fields;
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
				if ( array_key_exists( $type, self::get_address_types() ) ) {
					$address_type = $type;
				}
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

	public static function get_pickup_type_by_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$pickup_type = '';

		if ( $order ) {
			if ( 'dhl' === self::get_shipping_address_type_by_order( $order ) ) {
				$address_value = self::get_pickup_address_by_order( $order );

				if ( $address_value ) {
					$pickup_types = wc_gzd_dhl_get_pickup_types();

					foreach ( $pickup_types as $pickup_tmp_type => $label ) {
						if ( wc_gzd_dhl_is_pickup_type( $address_value, $pickup_tmp_type ) ) {
							$pickup_type = $pickup_tmp_type;
							break;
						}
					}

					if ( empty( $pickup_type ) ) {
						$pickup_number = preg_replace( '/[^0-9]/', '', $address_value );

						if ( ! empty( $pickup_number ) ) {
							$pickup_type = 'packstation';
						}
					}
				}
			}
		}

		/**
		 * Filter to adjust the DHL pickup type e.g. packstation for a certain order.
		 *
		 * @see wc_gzd_dhl_get_pickup_types()
		 *
		 * @param string   $pickup_type The pickup type.
		 * @param WC_Order $order The order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_order_pickup_type', $pickup_type, $order );
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

	public static function set_formatted_billing_address( $fields, $order ) {
		if ( ! empty( $fields ) && is_array( $fields ) ) {
			$fields['dhl_postnumber'] = '';
		}

		return $fields;
	}

	public static function set_formatted_address( $placeholder, $args ) {
		if ( isset( $args['dhl_postnumber'] ) ) {
			$placeholder['{dhl_postnumber}']       = $args['dhl_postnumber'];
			$placeholder['{dhl_postnumber_upper}'] = strtoupper( $args['dhl_postnumber'] );
		} else {
			$placeholder['{dhl_postnumber}'] = '';
			$placeholder['{dhl_postnumber}'] = '';
		}
		return $placeholder;
	}

	public static function set_user_address( $address, $customer_id, $name ) {
		if ( 'shipping' === $name ) {
			if ( $post_number = self::get_postnumber_by_user( $customer_id ) ) {
				$address['dhl_postnumber'] = $post_number;
			}
		}
		return $address;
	}

	public static function manipulate_checkout_fields() {
		if ( 'dhl' === WC()->checkout()->get_value( 'shipping_address_type' ) ) {
			add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'switch_street_label' ), 10, 1 );
		} else {
			remove_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'switch_street_label' ), 10 );
		}
	}

	public static function switch_street_label( $fields ) {
		$fields['shipping']['shipping_address_1']['label'] = self::get_type_text( ' / ' );

		return $fields;
	}

	protected static function get_rate_with_instance_id( $rate_id ) {
		if ( strpos( $rate_id, ':' ) === false ) {
			$rate_id = $rate_id . ':0';
		}

		return $rate_id;
	}

	public static function get_shipping_method_data( $from_session = false ) {

		if ( $from_session ) {
			return WC()->session->get( 'dhl_shipping_method_data', array() );
		}

		if ( WC()->session ) {
			unset( WC()->session->dhl_shipping_method_data );
		}

		$packages = WC()->shipping()->get_packages();
		$data     = array();

		foreach ( $packages as $package ) {
			$rates = $package['rates'];

			foreach ( $rates as $rate ) {
				if ( $method = wc_gzd_dhl_get_shipping_method( $rate ) ) {
					$supports = array();

					foreach ( wc_gzd_dhl_get_pickup_types() as $pickup_type => $title ) {
						$getter = "is_{$pickup_type}_enabled";

						if ( $method->$getter() ) {
							$supports[] = $pickup_type;
						}
					}

					$data[ self::get_rate_with_instance_id( $rate->id ) ] = array(
						'supports'             => $supports,
						'address_type_options' => self::get_address_types( $method ),
						'finder_button'        => self::get_button( $method ),
						'street_label'         => self::get_pickup_type_address_label( $method ),
						'street_placeholder'   => self::get_pickup_type_address_placeholder( $method ),
					);
				}
			}
		}

		if ( WC()->session ) {
			WC()->session->set( 'dhl_shipping_method_data', $data );
		}

		return $data;
	}

	protected static function remove_whitespace( $str ) {
		return trim( preg_replace( '/\s+/', '', $str ) );
	}

	public static function format_address_data( $data ) {
		if ( isset( $data['shipping_dhl_postnumber'] ) ) {
			$data['shipping_dhl_postnumber'] = self::remove_whitespace( $data['shipping_dhl_postnumber'] );
		}

		return $data;
	}

	public static function validate_checkout() {
		if ( ! self::is_available() ) {
			return;
		}

		$data   = WC_Checkout::instance()->get_posted_data();
		$errors = new WP_Error();

		// Validate input only if "ship to different address" flag is set
		if ( ! isset( $data['ship_to_different_address'] ) || ! $data['ship_to_different_address'] ) {
			return;
		}

		$shipping_country      = isset( $data['shipping_country'] ) ? $data['shipping_country'] : '';
		$shipping_address_type = isset( $data['shipping_address_type'] ) ? wc_clean( $data['shipping_address_type'] ) : 'regular';

		if ( empty( $shipping_address_type ) ) {
			$shipping_address_type = 'regular';
		}

		// Not a supported country
		if ( ! in_array( $shipping_country, self::get_supported_countries(), true ) ) {
			$data['shipping_dhl_postnumber'] = '';
			return;
		}

		if ( ! array_key_exists( $shipping_address_type, self::get_address_types() ) ) {
			$errors->add( 'validation', _x( 'Invalid address type.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( 'dhl' === $shipping_address_type ) {
			$args = array(
				'address_1'  => isset( $data['shipping_address_1'] ) ? wc_clean( $data['shipping_address_1'] ) : '',
				'address_2'  => isset( $data['shipping_address_2'] ) ? wc_clean( $data['shipping_address_2'] ) : '',
				'postnumber' => isset( $data['shipping_dhl_postnumber'] ) ? wc_clean( $data['shipping_dhl_postnumber'] ) : '',
				'postcode'   => isset( $data['shipping_postcode'] ) ? wc_clean( $data['shipping_postcode'] ) : '',
				'city'       => isset( $data['shipping_city'] ) ? wc_clean( $data['shipping_city'] ) : '',
				'country'    => isset( $data['shipping_country'] ) ? wc_clean( $data['shipping_country'] ) : '',
			);

			$result = self::validate_address( $args );

			if ( is_wp_error( $result ) ) {
				foreach ( $result->get_error_messages() as $mesage ) {
					$errors->add( 'validation', $mesage );
				}
			}
		}

		if ( wc_gzd_dhl_wp_error_has_errors( $errors ) ) {
			foreach ( $errors->get_error_messages() as $message ) {
				wc_add_notice( $message, 'error' );
			}
		}
	}

	protected static function get_available_pickup_types() {
		$pickup_types = wc_gzd_dhl_get_pickup_types();

		if ( isset( $pickup_types['packstation'] ) && ! self::is_packstation_enabled() ) {
			unset( $pickup_types['packstation'] );
		}

		if ( isset( $pickup_types['postoffice'] ) && ! self::is_postoffice_enabled() ) {
			unset( $pickup_types['postoffice'] );
		}

		if ( isset( $pickup_types['parcelshop'] ) && ! self::is_parcelshop_enabled() ) {
			unset( $pickup_types['parcelshop'] );
		}

		return $pickup_types;
	}

	protected static function validate_address( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'address_1'  => '',
				'address_2'  => '',
				'postnumber' => '',
				'postcode'   => '',
				'city'       => '',
				'country'    => '',
			)
		);

		$has_postnumber = false;

		if ( ! empty( $args['postnumber'] ) ) {
			$has_postnumber = true;

			// Do only allow numeric input
			$args['postnumber'] = preg_replace( '/[^0-9]/', '', $args['postnumber'] );
		}

		$error          = new WP_Error();
		$is_packstation = false;
		$field_key      = self::get_pickup_address_field_by_country( $args['country'] );
		$keyword_id     = self::extract_pickup_keyword_id( $args[ $field_key ] );

		/**
		 * A number is required for the packstation/parcelshop/postoffice
		 */
		if ( empty( $keyword_id ) ) {
			$error->add( 'validation', sprintf( _x( 'Please provide a valid pickup location within the %s field.', 'dhl', 'woocommerce-germanized' ), self::get_type_text( ' / ' ) ) );
		} else {
			$result = self::is_valid_pickup_address( $args, true );

			if ( is_wp_error( $result ) ) {
				$error = $result;
			} else {
				if ( 'DE' === $args['country'] ) {
					if ( wc_gzd_dhl_is_pickup_type( $args['address_1'], 'packstation' ) ) {
						$is_packstation = true;

						if ( ! self::is_packstation_enabled() ) {
							$error->add( 'validation', _x( 'Sorry, but delivery to packstation is not available.', 'dhl', 'woocommerce-germanized' ) );
							$is_packstation = false;
						}
					} elseif ( wc_gzd_dhl_is_pickup_type( $args['address_1'], 'parcelshop' ) ) {
						if ( ! self::is_parcelshop_enabled() ) {
							$error->add( 'validation', _x( 'Sorry, but delivery to parcel shops is not available.', 'dhl', 'woocommerce-germanized' ) );
						}
					} elseif ( wc_gzd_dhl_is_pickup_type( $args['address_1'], 'postoffice' ) ) {
						if ( ! self::is_postoffice_enabled() ) {
							$error->add( 'validation', _x( 'Sorry, but delivery to post offices is not available.', 'dhl', 'woocommerce-germanized' ) );
						}
					}

					if ( $has_postnumber ) {
						$post_number_len = strlen( $args['postnumber'] );

						if ( $post_number_len < 6 || $post_number_len > 12 ) {
							$error->add( 'validation', _x( 'Your DHL customer number (Post number) is not valid. Please check your number.', 'dhl', 'woocommerce-germanized' ) );
						}
					} elseif ( $is_packstation && empty( $args['postnumber'] ) ) {
						$error->add( 'validation', _x( 'Your DHL customer number (Post number) is needed to ship to a packstation.', 'dhl', 'woocommerce-germanized' ) );
					}
				}
			}
		}

		return wc_gzd_dhl_wp_error_has_errors( $error ) ? $error : true;
	}

	public static function add_inline_styles() {
		// load scripts on checkout page only
		if ( ! is_checkout() && ! is_wc_endpoint_url( 'edit-address' ) ) {
			return;
		}

		echo '<style type="text/css">#shipping_dhl_postnumber_field, #shipping_address_type_field { display: none; }</style>';
	}

	public static function localize_printed_scripts() {
		/**
		 * Do not check for localized script as this script needs to be loaded in footer to make sure
		 * that shipping method data (packages etc.) exist. This may lead to duplicate localizations (which is not a bug).
		 */
		if ( wp_script_is( 'wc-gzd-parcel-locator-dhl' ) ) {
			self::$localized_scripts[] = 'wc-gzd-parcel-locator-dhl';

			wp_localize_script(
				'wc-gzd-parcel-locator-dhl',
				'wc_gzd_dhl_parcel_locator_params',
				array(
					'ajax_url'                  => admin_url( 'admin-ajax.php' ),
					'parcel_locator_nonce'      => wp_create_nonce( 'dhl-parcel-locator' ),
					'parcel_locator_data_nonce' => wp_create_nonce( 'dhl-parcel-locator-shipping-data' ),
					'supported_countries'       => self::get_supported_countries(),
					'pickup_address_field_keys' => self::get_pickup_address_fields(),
					'excluded_gateways'         => self::get_excluded_gateways(),
					'methods'                   => is_checkout() ? self::get_shipping_method_data() : array(),
					'is_checkout'               => is_checkout(),
					'pickup_types'              => wc_gzd_dhl_get_pickup_types(),
					'i18n'                      => array_merge( wc_gzd_dhl_get_pickup_types(), array() ),
					'wrapper'                   => is_checkout() ? '.woocommerce-checkout' : '.woocommerce-address-fields',
				)
			);
		}

		if ( ! in_array( 'wc-gzd-parcel-finder-dhl', self::$localized_scripts, true ) && wp_script_is( 'wc-gzd-parcel-finder-dhl' ) ) {
			self::$localized_scripts[] = 'wc-gzd-parcel-finder-dhl';

			wp_localize_script(
				'wc-gzd-parcel-finder-dhl',
				'wc_gzd_dhl_parcel_finder_params',
				array(
					'parcel_finder_nonce' => wp_create_nonce( 'dhl-parcel-finder' ),
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'packstation_icon'    => Package::get_assets_url() . '/img/packstation.png',
					'parcelshop_icon'     => Package::get_assets_url() . '/img/parcelshop.png',
					'postoffice_icon'     => Package::get_assets_url() . '/img/post_office.png',
					'api_key'             => self::get_setting( 'map_api_password' ),
					'wrapper'             => is_checkout() ? '.woocommerce-checkout' : '.woocommerce-address-fields',
					'i18n'                => array_merge(
						wc_gzd_dhl_get_pickup_types(),
						array(
							'branch'      => _x( 'Branch', 'dhl', 'woocommerce-germanized' ),
							'post_number' => _x( 'Postnumber ', 'dhl', 'woocommerce-germanized' ),
						)
					),
				)
			);
		}
	}

	public static function add_scripts() {
		// load scripts on checkout page only
		if ( ! is_checkout() && ! is_wc_endpoint_url( 'edit-address' ) ) {
			return;
		}

		$deps   = array( 'jquery' );
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( is_checkout() ) {
			array_push( $deps, 'wc-checkout' );
		} else {
			$deps = array_merge( $deps, array( 'woocommerce', 'wc-address-i18n' ) );
		}

		wp_register_script( 'wc-gzd-parcel-locator-dhl', Package::get_assets_url() . '/js/parcel-locator' . $suffix . '.js', $deps, Package::get_version(), true );
		wp_register_script( 'wc-gzd-parcel-finder-dhl', Package::get_assets_url() . '/js/parcel-finder' . $suffix . '.js', array( 'jquery-blockui', 'wc-gzd-parcel-locator-dhl' ), Package::get_version(), true );

		wp_register_style( 'wc-gzd-parcel-finder-dhl', Package::get_assets_url() . '/css/parcel-finder' . $suffix . '.css', array(), Package::get_version() );

		if ( self::has_map() ) {
			wp_enqueue_script( 'wc-gzd-parcel-finder-dhl' );
			wp_enqueue_style( 'wc-gzd-parcel-finder-dhl' );
		}

		wp_enqueue_script( 'wc-gzd-parcel-locator-dhl' );
	}

	protected static function disable_method_setting() {
		$disable_method_check = ( is_account_page() || is_admin() );
		$is_forced_checkout   = isset( $_POST['is_checkout'] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_POST['is_checkout'] ) ) ) : true; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( wp_doing_ajax() && $is_forced_checkout ) {
			$disable_method_check = false;
		}

		return $disable_method_check;
	}

	protected static function get_setting( $key, $check_method = true ) {
		$option_key = 'parcel_pickup_' . $key;

		/**
		 * Exclude non-overridable options to make sure they might be used for DP too (e.g. packstation maps)
		 */
		if ( ! in_array( $option_key, array( 'parcel_pickup_map_enable', 'parcel_pickup_map_api_password', 'parcel_pickup_map_max_results' ), true ) ) {
			if ( $check_method && ( $method = wc_gzd_dhl_get_current_shipping_method() ) ) {
				if ( 'parcel_pickup_packstation_enable' === $option_key ) {
					return wc_bool_to_string( $method->is_packstation_enabled() );
				} elseif ( 'parcel_pickup_parcelshop_enable' === $option_key ) {
					return wc_bool_to_string( $method->is_parcelshop_enabled() );
				} elseif ( 'parcel_pickup_postoffice_enable' === $option_key ) {
					return wc_bool_to_string( $method->is_postoffice_enabled() );
				} elseif ( ! self::disable_method_setting() ) {
					/**
					 * Explicitly call available pickup getters instead of generic get_option method
					 * to support DP adjustments (packstation).
					 */
					return $method->get_option( $option_key );
				}
			}
		}

		$setting = Package::get_setting( $option_key );

		return $setting;
	}

	public static function is_enabled() {
		return Package::base_country_supports( 'pickup' );
	}

	public static function is_available( $check_shipping_method = true ) {
		return self::is_packstation_enabled( $check_shipping_method ) || self::is_parcelshop_enabled( $check_shipping_method ) || self::is_postoffice_enabled( $check_shipping_method );
	}

	public static function is_postoffice_enabled( $check_shipping_method = true ) {
		return 'yes' === self::get_setting( 'postoffice_enable', $check_shipping_method );
	}

	public static function is_packstation_enabled( $check_shipping_method = true ) {
		return 'yes' === self::get_setting( 'packstation_enable', $check_shipping_method );
	}

	public static function is_parcelshop_enabled( $check_shipping_method = true ) {
		return 'yes' === self::get_setting( 'parcelshop_enable', $check_shipping_method );
	}

	public static function has_map( $check_shipping_method = true ) {
		if ( Package::is_debug_mode() ) {
			return ( 'yes' === self::get_setting( 'map_enable', $check_shipping_method ) && Package::is_dhl_enabled() );
		} else {
			$api_key = self::get_setting( 'map_api_password', false );

			return ( 'yes' === self::get_setting( 'map_enable', $check_shipping_method ) && ! empty( $api_key ) && Package::is_dhl_enabled() );
		}
	}

	public static function get_max_results() {
		return self::get_setting( 'map_max_results' );
	}

	protected static function get_street_placeholder( $method = false ) {
		$type = '';
		$text = '';

		if ( ( $method && $method->is_packstation_enabled() ) || ( ! $method && self::is_packstation_enabled() ) ) {
			$type = wc_gzd_dhl_get_pickup_type( 'packstation' );
		} elseif ( ( $method && $method->is_parcelshop_enabled() ) || ( ! $method && self::is_parcelshop_enabled() ) ) {
			$type = wc_gzd_dhl_get_pickup_type( 'parcelshop' );
		} elseif ( ( $method && $method->is_postoffice_enabled() ) || ( ! $method && self::is_postoffice_enabled() ) ) {
			$type = wc_gzd_dhl_get_pickup_type( 'postoffice' );
		}

		if ( ! empty( $type ) ) {
			$text = sprintf( _x( 'e.g. %s 456', 'dhl', 'woocommerce-germanized' ), $type );
		}

		return $text;
	}

	/**
	 * @param string $sep
	 * @param bool $plural
	 * @param bool|ShippingMethod $method
	 *
	 * @return string
	 */
	protected static function get_type_text( $sep = '', $plural = true, $method = false ) {
		$search_types = '';

		if ( empty( $sep ) ) {
			$sep = '&amp;';
		}

		if ( ( $method && $method->is_packstation_enabled() ) || ( ! $method && self::is_packstation_enabled() ) ) {
			$search_types .= _x( 'Packstation', 'dhl', 'woocommerce-germanized' );
		}

		if ( ( $method && ( $method->is_parcelshop_enabled() || $method->is_postoffice_enabled() ) ) || ( ! $method && ( self::is_parcelshop_enabled() || self::is_postoffice_enabled() ) ) ) {
			$branch_type   = ( $plural ) ? _x( 'Branches', 'dhl', 'woocommerce-germanized' ) : _x( 'Branch', 'dhl', 'woocommerce-germanized' );
			$search_types .= ( ! empty( $search_types ) ? ' ' . $sep . ' ' . $branch_type : $branch_type );
		}

		return $search_types;
	}

	public static function add_admin_shipping_fields( $fields ) {
		$fields['address_type'] = array(
			'label'   => _x( 'Address Type', 'dhl', 'woocommerce-germanized' ),
			'type'    => 'select',
			'show'    => false,
			'options' => self::get_address_types(),
		);

		$fields['dhl_postnumber'] = array(
			'label' => _x( 'DHL customer number (Post number)', 'dhl', 'woocommerce-germanized' ),
			'show'  => false,
			'type'  => 'text',
		);

		return $fields;
	}

	public static function get_address_types( $method = false ) {
		return array(
			'regular' => _x( 'Regular Address', 'dhl', 'woocommerce-germanized' ),
			'dhl'     => self::get_type_text( ' / ', true, $method ),
		);
	}

	public static function add_shipping_fields( $fields ) {
		/**
		 * On initial render make sure to not check the actual shipping method options for availability.
		 * Otherwise if the initial shipping method does not support DHL the fields are not even added to the checkout form.
		 */
		if ( self::is_available( false ) ) {
			$fields['shipping_address_type'] = array(
				'label'    => _x( 'Address Type', 'dhl', 'woocommerce-germanized' ),
				'required' => false,
				'type'     => 'select',
				'class'    => array( 'shipping-dhl-address-type' ),
				'clear'    => true,
				'priority' => 5,
				'options'  => self::get_address_types(),
				'default'  => 'regular',
			);

			$fields['shipping_dhl_postnumber'] = array(
				'label'       => _x( 'DHL customer number (Post number)', 'dhl', 'woocommerce-germanized' ),
				'required'    => false,
				'type'        => 'text',
				'class'       => array( 'shipping-dhl-postnumber' ),
				'description' => _x( 'Not yet a DHL customer?', 'dhl', 'woocommerce-germanized' ) . '<br/><a href="https://www.dhl.de/de/privatkunden/kundenkonto/registrierung.html" target="_blank">' . _x( 'Register now', 'dhl', 'woocommerce-germanized' ) . '</a>',
				'clear'       => true,
				'priority'    => 45,
			);

			$fields['shipping_address_1']['custom_attributes']                             = ( isset( $fields['shipping_address_1']['custom_attributes'] ) ? $fields['shipping_address_1']['custom_attributes'] : array() );
			$fields['shipping_address_1']['custom_attributes']['data-label-dhl']           = self::get_pickup_type_address_label();
			$fields['shipping_address_1']['custom_attributes']['data-label-regular']       = $fields['shipping_address_1']['label'];
			$fields['shipping_address_1']['custom_attributes']['data-placeholder-dhl']     = self::get_pickup_type_address_placeholder();
			$fields['shipping_address_1']['custom_attributes']['data-placeholder-regular'] = isset( $fields['shipping_address_1']['placeholder'] ) ? $fields['shipping_address_1']['placeholder'] : '';
			$fields['shipping_address_1']['custom_attributes']['data-desc-dhl']            = self::get_button();

			if ( isset( $fields['shipping_address_2'] ) ) {
				$fields['shipping_address_2']['custom_attributes']                             = ( isset( $fields['shipping_address_2']['custom_attributes'] ) ? $fields['shipping_address_2']['custom_attributes'] : array() );
				$fields['shipping_address_2']['custom_attributes']['data-label-dhl']           = self::get_pickup_type_address_label();
				$fields['shipping_address_2']['custom_attributes']['data-label-regular']       = $fields['shipping_address_2']['label'];
				$fields['shipping_address_2']['custom_attributes']['data-placeholder-dhl']     = self::get_pickup_type_address_placeholder();
				$fields['shipping_address_2']['custom_attributes']['data-placeholder-regular'] = isset( $fields['shipping_address_2']['placeholder'] ) ? $fields['shipping_address_2']['placeholder'] : '';
				$fields['shipping_address_2']['custom_attributes']['data-desc-dhl']            = self::get_button();
			}
		}

		return $fields;
	}

	protected static function get_pickup_type_address_label( $method = false ) {
		/**
		 * Filter to adjust the pickup type address label added
		 * to the address field when a certain pickup type was chosen.
		 *
		 * @param string            $pickup_type_text The pickup type text.
		 * @param boolean|ShippingMethod $method The shipping method object if available.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_pickup_type_address_label', self::get_type_text( ' / ', false, $method ), $method );
	}

	protected static function get_pickup_type_address_placeholder( $method = false ) {
		/**
		 * Filter to adjust the pickup type address placeholder added
		 * to the address field when a certain pickup type was chosen.
		 *
		 * @param string            $pickup_type_text The pickup type placeholder text.
		 * @param boolean|ShippingMethod $method The shipping method object if available.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		return apply_filters( 'woocommerce_gzd_dhl_pickup_type_address_placeholder', self::get_street_placeholder( $method ), $method );
	}

	protected static function get_icon( $type = 'packstation' ) {
		return Package::get_assets_url() . '/img/' . $type . '.png';
	}

	protected static function get_button_text( $method = false ) {
		$text = sprintf( _x( 'Search %s', 'dhl', 'woocommerce-germanized' ), self::get_type_text( '', true, $method ) );

		return $text;
	}

	protected static function get_button( $method = false ) {
		$text = self::get_button_text( $method );

		if ( self::has_map() ) {
			return '<a class="dhl-parcel-finder-link gzd-dhl-parcel-shop-modal" href="javascript:;">' . esc_html( $text ) . '</a>';
		} else {
			return '<a href="' . esc_url( self::get_pickup_locator_link() ) . '" class="dhl-parcel-finder-link dhl-parcel-finder-plain-link" target="_blank">' . esc_html( $text ) . '</a>';
		}
	}

	protected static function get_pickup_locator_link() {
		return 'https://www.dhl.de/de/privatkunden/dhl-standorte-finden.html';
	}

	public static function add_button() {
		echo self::get_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function add_form() {

		if ( ! is_checkout() && ! is_wc_endpoint_url( 'edit-address' ) ) {
			return;
		}

		$args = array(
			'img_packstation'        => self::get_icon( 'packstation' ),
			'img_postoffice'         => self::get_icon( 'post_office' ),
			'img_parcelshop'         => self::get_icon( 'parcelshop' ),
			'is_packstation_enabled' => self::is_packstation_enabled(),
			'is_postoffice_enabled'  => self::is_postoffice_enabled(),
			'is_parcelshop_enabled'  => self::is_parcelshop_enabled(),
		);

		wc_get_template( 'checkout/dhl/parcel-finder.php', $args, Package::get_template_path(), Package::get_path() . '/templates/' );
	}

	public static function ajax_refresh_shipping_data() {
		check_ajax_referer( 'dhl-parcel-locator-shipping-data', 'security' );

		wp_send_json(
			array(
				'methods' => self::get_shipping_method_data( true ),
				'success' => true,
			)
		);
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

	public static function get_pickup_address_fields() {
		$countries = array();

		foreach ( self::get_supported_countries() as $country ) {
			$countries[ $country ] = self::get_pickup_address_field_by_country( $country );
		}

		return $countries;
	}

	/**
	 * @param $args
	 * @param $report_invalid_address
	 *
	 * @return array|WP_Error
	 */
	public static function is_valid_pickup_address( $args, $report_invalid_address = false ) {
		$address_data = wp_parse_args(
			$args,
			array(
				'country'   => Package::get_base_country(),
				'address_1' => '',
				'address_2' => '',
				'postcode'  => '',
			)
		);

		foreach ( $address_data as $address_k => $address_d ) {
			$address_data[ $address_k ] = trim( $address_d );
		}

		$address_field  = self::get_pickup_address_field_by_country( $args['country'] );
		$error          = new WP_Error();
		$pickup_address = array(
			'name'     => $address_data[ $address_field ],
			'address'  => $address_data['address_1'],
			'postcode' => $address_data['postcode'],
			'city'     => $address_data['city'],
		);

		if ( ! apply_filters( 'woocommerce_gzd_dhl_validate_pickup_address', true, $args, $report_invalid_address ) ) {
			return $pickup_address;
		}

		$keyword_id = self::extract_pickup_keyword_id( $address_data[ $address_field ] );

		if ( ! empty( $address_data[ $address_field ] ) && ! empty( $address_data['postcode'] ) && ! empty( $keyword_id ) ) {
			$cache_key      = 'woocommerce_gzd_dhl_pickup_' . sanitize_key( $keyword_id ) . '_' . sanitize_key( $address_data['country'] ) . '_' . $address_data['postcode'];
			$pickup_address = get_transient( $cache_key );

			if ( false === $pickup_address ) {
				try {
					$result = Package::get_api()->get_finder_api()->find_by_id( $keyword_id, $address_data['country'], $address_data['postcode'] );

					$pickup_address = array(
						'name'     => trim( $result->gzd_name ),
						'address'  => trim( $result->place->address->streetAddress ),
						'postcode' => trim( $result->place->address->postalCode ),
						'city'     => trim( $result->place->address->addressLocality ),
					);

					set_transient( $cache_key, $pickup_address, DAY_IN_SECONDS );
				} catch ( \Exception $e ) {
					if ( 404 === $e->getCode() ) {
						$error->add( 'pickup', sprintf( _x( 'The pickup location you\'ve chosen cannot be found.', 'dhl', 'woocommerce-germanized' ) ) );

						set_transient( $cache_key, array(), DAY_IN_SECONDS );
					}
				}
			} elseif ( empty( $pickup_address ) ) {
				$error->add( 'pickup', sprintf( _x( 'The pickup location you\'ve chosen cannot be found.', 'dhl', 'woocommerce-germanized' ) ) );
			}

			if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
				return $error;
			}

			if ( $report_invalid_address ) {
				if ( 'address_1' !== $address_field ) {
					if ( $pickup_address['address'] !== $address_data['address_1'] ) {
						$error->add( 'pickup_address', sprintf( _x( 'Your pickup address seems invalid. Did you mean %s?', 'dhl', 'woocommerce-germanized' ), esc_html( $pickup_address['address'] ) ) );
					}
				}

				if ( $pickup_address['city'] !== $address_data['city'] ) {
					$error->add( 'pickup_city', sprintf( _x( 'Your pickup city seems invalid. Did you mean %s?', 'dhl', 'woocommerce-germanized' ), esc_html( $pickup_address['city'] ) ) );
				}
			}

			if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
				return $error;
			}

			return $pickup_address;
		}

		if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return $pickup_address;
	}

	public static function ajax_validate_address() {
		check_ajax_referer( 'dhl-parcel-locator', 'security' );

		$address      = isset( $_POST['address'] ) ? wc_clean( wp_unslash( $_POST['address'] ) ) : '';
		$address_data = wp_parse_args(
			$address,
			array(
				'country'   => Package::get_base_country(),
				'address_1' => '',
				'address_2' => '',
				'postcode'  => '',
			)
		);

		$address_field = self::get_pickup_address_field_by_country( $address_data['country'] );

		$response = array(
			'valid'     => true,
			'address_1' => $address_data['address_1'],
			'address_2' => $address_data['address_2'],
			'success'   => true,
			'messages'  => '',
		);

		$result = self::is_valid_pickup_address( $address_data );

		if ( is_wp_error( $result ) ) {
			$response['valid']    = false;
			$response['messages'] = '';

			foreach ( $result->get_error_messages() as $message ) {
				$response['messages'] .= '<li>' . wp_kses_post( $message ) . '</li>';
			}
		} else {
			$response[ $address_field ] = $result['name'];

			if ( 'DE' !== $address_data['country'] ) {
				$response['address_1'] = $result['address'];
				$response['city']      = $result['city'];
			}
		}

		wp_send_json( $response );
	}

	public static function ajax_search() {
		check_ajax_referer( 'dhl-parcel-finder', 'security' );

		$parcelfinder_country  = isset( $_POST['dhl_parcelfinder_country'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_country'] ) ) : Package::get_base_country();
		$parcelfinder_postcode = isset( $_POST['dhl_parcelfinder_postcode'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_postcode'] ) ) : '';
		$parcelfinder_city     = isset( $_POST['dhl_parcelfinder_city'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_city'] ) ) : '';
		$parcelfinder_address  = isset( $_POST['dhl_parcelfinder_address'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_address'] ) ) : '';

		$packstation_filter = wc_string_to_bool( isset( $_POST['dhl_parcelfinder_packstation_filter'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_packstation_filter'] ) ) : 'no' );
		$parcelshop_filter  = wc_string_to_bool( isset( $_POST['dhl_parcelfinder_parcelshop_filter'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_parcelshop_filter'] ) ) : 'no' );
		$postoffice_filter  = wc_string_to_bool( isset( $_POST['dhl_parcelfinder_postoffice_filter'] ) ? wc_clean( wp_unslash( $_POST['dhl_parcelfinder_postoffice_filter'] ) ) : 'no' );

		try {
			$args = array(
				'address' => $parcelfinder_address,
				'zip'     => $parcelfinder_postcode,
				'city'    => $parcelfinder_city,
				'country' => empty( $parcelfinder_country ) ? Package::get_base_country() : $parcelfinder_country,
			);

			$error = new WP_Error();
			$types = array();

			if ( $packstation_filter && self::is_packstation_enabled() ) {
				$types[] = 'packstation';
			}

			if ( $parcelshop_filter && self::is_parcelshop_enabled() ) {
				$types[] = 'parcelshop';
			}

			if ( $postoffice_filter && self::is_postoffice_enabled() ) {
				$types[] = 'postoffice';
			}

			$parcel_res = Package::get_api()->get_parcel_location( $args, $types );

			if ( empty( $parcel_res ) ) {
				$error->add( 404, _x( 'No DHL locations found', 'dhl', 'woocommerce-germanized' ) );
			}

			if ( ! wc_gzd_dhl_wp_error_has_errors( $error ) ) {
				wp_send_json(
					array(
						'parcel_shops' => $parcel_res,
						'success'      => true,
					)
				);
			} else {
				wp_send_json(
					array(
						'success'  => false,
						'messages' => $error->get_error_messages(),
					)
				);
			}
		} catch ( Exception $e ) {
			$error = sprintf( _x( 'There was an error while communicating with DHL. Please manually find a %1$s or %2$s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . esc_url( self::get_pickup_locator_link() ) . '" target="_blank">' . _x( 'DHL location', 'dhl', 'woocommerce-germanized' ) . '</a>', '<a class="dhl-retry-search" href="#">' . _x( 'retry', 'dhl', 'woocommerce-germanized' ) . '</a>' );

			wp_send_json(
				array(
					'success' => false,
					'message' => Package::is_debug_mode() ? $e->getMessage() : $error,
				)
			);
		}

		wp_die();
	}
}
