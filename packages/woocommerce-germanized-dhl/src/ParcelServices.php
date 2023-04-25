<?php

namespace Vendidero\Germanized\DHL;

use Exception;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class ParcelServices {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_scripts' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_fees' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'create_order' ), 10 );
		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'order_totals' ), 10, 2 );

		add_action( 'woocommerce_review_order_after_payment', array( __CLASS__, 'maybe_output_fields' ), 500 );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'maybe_output_fields_before_submit' ), 500 );

		add_action( 'woocommerce_gzd_dhl_preferred_service_fields', array( __CLASS__, 'add_fields' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'fragments' ), 10 );
	}

	protected static function cart_needs_shipping() {
		return WC()->cart && WC()->cart->needs_shipping();
	}

	public static function fragments( $fragments ) {
		ob_start();
		self::add_fields();
		$html = ob_get_clean();

		$fragments['.dhl-preferred-service-content'] = $html;

		return $fragments;
	}

	public static function maybe_output_fields() {
		if ( function_exists( 'wc_gzd_checkout_adjustments_disabled' ) && ! wc_gzd_checkout_adjustments_disabled() ) {
			do_action( 'woocommerce_gzd_dhl_preferred_service_fields' );
		}
	}

	public static function maybe_output_fields_before_submit() {
		if ( ( function_exists( 'wc_gzd_checkout_adjustments_disabled' ) && wc_gzd_checkout_adjustments_disabled() ) || ! function_exists( 'wc_gzd_checkout_adjustments_disabled' ) ) {
			do_action( 'woocommerce_gzd_dhl_preferred_service_fields' );
		}
	}

	public static function order_totals( $total_rows, $order ) {
		$new_rows = array();

		if ( $dhl_order = wc_gzd_dhl_get_order( $order ) ) {
			if ( $dhl_order->has_preferred_day() ) {
				$new_rows['preferred_day'] = array(
					'label' => _x( 'Delivery day', 'dhl', 'woocommerce-germanized' ),
					'value' => wc_format_datetime( $dhl_order->get_preferred_day(), wc_date_format() ),
				);
			}

			if ( $dhl_order->has_preferred_time() ) {
				$new_rows['preferred_time'] = array(
					'label' => _x( 'Delivery time', 'dhl', 'woocommerce-germanized' ),
					'value' => $dhl_order->get_preferred_time(),
				);
			}

			if ( $dhl_order->has_preferred_location() ) {
				$new_rows['preferred_location'] = array(
					'label' => _x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ),
					'value' => $dhl_order->get_preferred_location(),
				);
			} elseif ( $dhl_order->has_preferred_neighbor() ) {
				$new_rows['preferred_neighbor'] = array(
					'label' => _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
					'value' => $dhl_order->get_preferred_neighbor_formatted_address(),
				);
			}

			if ( $dhl_order->has_preferred_delivery_type() ) {
				$new_rows['preferred_delivery_type'] = array(
					'label' => _x( 'Delivery Type', 'dhl', 'woocommerce-germanized' ),
					'value' => self::get_preferred_delivery_type_title( $dhl_order->get_preferred_delivery_type() ),
				);
			}
		}

		if ( ! empty( $new_rows ) ) {

			// Instert before payment method
			$insert_before = array_search( 'payment_method', array_keys( $total_rows ), true );

			// If no payment method, insert before order total
			if ( empty( $insert_before ) ) {
				$insert_before = array_search( 'order_total', array_keys( $total_rows ), true );
			}

			if ( empty( $insert_before ) ) {
				$total_rows += $new_rows;
			} else {
				$first_array = array_splice( $total_rows, 0, $insert_before );
				$total_rows  = array_merge( $first_array, $new_rows, $total_rows );
			}
		}

		return $total_rows;
	}

	public static function create_order( $order ) {
		if ( self::is_preferred_option_available() ) {
			$data = self::get_data();

			if ( ! wc_gzd_dhl_wp_error_has_errors( $data['errors'] ) ) {
				$dhl_order = new Order( $order );

				if ( ! empty( $data['preferred_day'] ) ) {
					$dhl_order->set_preferred_day( $data['preferred_day'] );
				}

				if ( ! empty( $data['preferred_delivery_type'] ) ) {
					$dhl_order->set_preferred_delivery_type( $data['preferred_delivery_type'] );
				}

				if ( 'place' === $data['preferred_location_type'] && ! empty( $data['preferred_location'] ) ) {
					$dhl_order->set_preferred_location( $data['preferred_location'] );
				} elseif ( 'neighbor' === $data['preferred_location_type'] && ! empty( $data['preferred_location_neighbor_name'] ) && ! empty( $data['preferred_location_neighbor_address'] ) ) {
					$dhl_order->set_preferred_neighbor( $data['preferred_location_neighbor_name'] );
					$dhl_order->set_preferred_neighbor_address( $data['preferred_location_neighbor_address'] );
				}
			}
		}
	}

	protected static function get_posted_data() {
		$original_post_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// POST information is either in a query string-like variable called 'post_data'...
		if ( isset( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			parse_str( wp_unslash( $_POST['post_data'] ), $post_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		} else {
			$post_data = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		$_POST = $post_data;

		$posted_data = \WC_Checkout::instance()->get_posted_data();
		$posted_data = array_merge( wc_clean( $post_data ), (array) $posted_data );

		$_POST = $original_post_data;

		return $posted_data;
	}

	public static function validate( $data, $errors ) {
		if ( self::cart_needs_shipping() && self::is_preferred_option_available() ) {
			$data = self::get_data();

			if ( wc_gzd_dhl_wp_error_has_errors( $data['errors'] ) ) {
				foreach ( $data['errors']->get_error_messages() as $message ) {
					$errors->add( 'validation', $message );
				}
			}
		}
	}

	/**
	 * @param \WC_Cart $cart
	 */
	public static function add_fees( $cart ) {
		if ( ! $_POST || ( is_admin() && ! wp_doing_ajax() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( self::cart_needs_shipping() && self::is_preferred_option_available() ) {
			$data = self::get_data();

			try {
				if ( ! empty( $data['preferred_day'] ) ) {
					if ( ! empty( $data['preferred_day_cost'] ) ) {
						$cart->add_fee( _x( 'DHL Delivery day', 'dhl', 'woocommerce-germanized' ), $data['preferred_day_cost'], true );
					}
				}

				if ( $data['preferred_delivery_type_enabled'] && ! empty( $data['preferred_delivery_type'] ) && 'home' === $data['preferred_delivery_type'] ) {
					if ( ! empty( $data['preferred_home_delivery_cost'] ) ) {
						$cart->add_fee( _x( 'DHL Home Delivery', 'dhl', 'woocommerce-germanized' ), $data['preferred_home_delivery_cost'], true );
					}
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}
	}

	public static function add_scripts() {

		// load scripts on checkout page only
		if ( ! is_checkout() ) {
			return;
		}

		$deps   = array( 'jquery', 'wc-checkout', 'jquery-tiptip' );
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );
		wp_register_script( 'wc-gzd-preferred-services-dhl', Package::get_assets_url() . '/js/preferred-services' . $suffix . '.js', $deps, Package::get_version(), true );
		wp_register_style( 'wc-gzd-preferred-services-dhl', Package::get_assets_url() . '/css/preferred-services' . $suffix . '.css', array(), Package::get_version() );

		$excluded_gateways = self::get_excluded_payment_gateways();

		wp_localize_script(
			'wc-gzd-preferred-services-dhl',
			'wc_gzd_dhl_preferred_services_params',
			array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'payment_gateways_excluded' => empty( $excluded_gateways ) ? false : true,
			)
		);

		wp_enqueue_script( 'wc-gzd-preferred-services-dhl' );
		wp_enqueue_style( 'wc-gzd-preferred-services-dhl' );
	}

	protected static function get_excluded_payment_gateways() {
		return (array) self::get_setting( 'payment_gateways_excluded' );
	}
	protected static function payment_gateway_supports_services( $method ) {
		$methods = self::get_excluded_payment_gateways();

		if ( ! empty( $methods ) && in_array( $method, $methods, true ) ) {
			return false;
		}

		return true;
	}

	protected static function get_preferred_home_delivery_cost() {
		$cost = self::get_setting( 'home_delivery_cost' );

		if ( empty( $cost ) ) {
			$cost = 0;
		} else {
			$cost = wc_format_decimal( $cost );
		}

		return $cost;
	}

	protected static function get_preferred_day_cost() {
		$cost = self::get_setting( 'PreferredDay_cost' );

		if ( empty( $cost ) ) {
			$cost = 0;
		} else {
			$cost = wc_format_decimal( $cost );
		}

		return $cost;
	}

	public static function is_preferred_day_excluded( $day ) {
		if ( 'yes' === self::get_setting( 'PreferredDay_exclusion_' . $day ) ) {
			return true;
		}

		return false;
	}

	public static function get_preferred_day_cutoff_time() {
		$time = self::get_setting( 'PreferredDay_cutoff_time' );

		return $time;
	}

	public static function get_preferred_day_preparation_days() {
		$days = self::get_setting( 'PreferredDay_preparation_days' );

		if ( empty( $days ) || $days < 0 ) {
			$days = 0;
		}

		return $days;
	}

	protected static function is_preferred_enabled() {
		return self::is_preferred_day_enabled() || self::is_preferred_location_enabled() || self::is_preferred_neighbor_enabled();
	}

	protected static function preferred_services_available( $customer_country = '' ) {
		$customer_country = empty( $customer_country ) ? WC()->customer->get_shipping_country() : $customer_country;

		return 'DE' === $customer_country;
	}

	public static function get_cdp_countries() {
		return array( 'DK' );
	}

	public static function is_pddp_available( $country, $postcode = '' ) {
		$is_available = false;
		$country      = wc_strtoupper( $country );
		$postcode     = wc_normalize_postcode( $postcode );

		if ( 'GB' === $country && 'BT' !== strtoupper( substr( trim( $postcode ), 0, 2 ) ) ) {
			$is_available = true;
		}

		return $is_available;
	}

	public static function is_cdp_available( $customer_country = '' ) {
		$customer_country = empty( $customer_country ) && WC()->customer ? WC()->customer->get_shipping_country() : $customer_country;
		$is_available     = false;

		// Preferred options are only for Germany customers
		if ( in_array( $customer_country, self::get_cdp_countries(), true ) ) {
			$is_available = true;
		}

		return $is_available;
	}

	protected static function is_preferred_option_available() {
		$chosen_payment_method = (array) WC()->session->get( 'chosen_payment_method' );
		$display_preferred     = false;

		if ( self::is_enabled() ) {
			if ( self::preferred_services_available() && self::is_preferred_enabled() ) {
				$display_preferred = true;
			} elseif ( self::is_cdp_available() && self::is_preferred_delivery_type_enabled() ) {
				$display_preferred = true;
			}

			foreach ( $chosen_payment_method as $key => $method ) {
				if ( ! self::payment_gateway_supports_services( $method ) ) {
					$display_preferred = false;
					break;
				}
			}
		}

		return $display_preferred;
	}

	protected static function get_data() {
		$post_data        = self::get_posted_data();
		$customer_country = isset( $post_data['shipping_country'] ) ? wc_clean( wp_unslash( $post_data['shipping_country'] ) ) : '';

		$data = array(
			'preferred_day_enabled'               => false,
			'preferred_location_enabled'          => false,
			'preferred_neighbor_enabled'          => false,
			'preferred_delivery_type_enabled'     => false,
			'preferred_day'                       => '',
			'preferred_location_type'             => 'none',
			'preferred_location'                  => '',
			'preferred_location_neighbor_name'    => '',
			'preferred_location_neighbor_address' => '',
			'preferred_day_options'               => WC()->session->get( 'dhl_preferred_day_options', array() ),
			'preferred_day_cost'                  => self::get_preferred_day_cost(),
			'preferred_delivery_type'             => '',
			'preferred_home_delivery_cost'        => self::get_preferred_home_delivery_cost(),
			'preferred_delivery_types'            => self::get_preferred_delivery_types(),
		);

		$data['errors'] = new WP_Error();

		if ( self::preferred_services_available( $customer_country ) ) {
			if ( is_null( $data['preferred_day_options'] ) ) {
				self::refresh_day_session();
				$data['preferred_day_options'] = WC()->session->get( 'dhl_preferred_day_options' );
			}
		}

		try {
			if ( self::preferred_services_available( $customer_country ) ) {
				if ( self::is_preferred_day_enabled() ) {
					$data['preferred_day_enabled'] = true;

					if ( isset( $post_data['dhl_preferred_day'] ) && ! empty( $post_data['dhl_preferred_day'] ) ) {
						$day = wc_clean( wp_unslash( $post_data['dhl_preferred_day'] ) );

						if ( wc_gzd_dhl_is_valid_datetime( $day, 'Y-m-d' ) ) {
							if ( ! empty( $data['preferred_day_options'] ) ) {
								if ( array_key_exists( $day, $data['preferred_day_options'] ) ) {
									$data['preferred_day'] = $day;
								}
							}
						}

						if ( empty( $data['preferred_day'] ) ) {
							$data['errors']->add( 'validation', _x( 'Sorry, but the delivery day you have chosen is no longer available.', 'dhl', 'woocommerce-germanized' ) );
						}
					}
				}

				if ( self::is_preferred_neighbor_enabled() ) {
					$data['preferred_neighbor_enabled'] = true;
				}

				if ( self::is_preferred_location_enabled() ) {
					$data['preferred_location_enabled'] = true;
				}

				if ( self::is_preferred_location_enabled() || self::is_preferred_neighbor_enabled() ) {
					if ( isset( $post_data['dhl_preferred_location_type'] ) && 'none' !== $post_data['dhl_preferred_location_type'] ) {
						if ( self::is_preferred_location_enabled() && 'place' === $post_data['dhl_preferred_location_type'] ) {
							$location = isset( $post_data['dhl_preferred_location'] ) ? wc_clean( wp_unslash( $post_data['dhl_preferred_location'] ) ) : '';

							$data['preferred_location_type'] = 'place';

							if ( ! empty( $location ) ) {
								$data['preferred_location'] = $location;
							} else {
								$data['errors']->add( 'validation', _x( 'Please choose a drop-off location.', 'dhl', 'woocommerce-germanized' ) );
							}
						} elseif ( self::is_preferred_neighbor_enabled() && 'neighbor' === $post_data['dhl_preferred_location_type'] ) {
							$name    = isset( $post_data['dhl_preferred_location_neighbor_name'] ) ? wc_clean( wp_unslash( $post_data['dhl_preferred_location_neighbor_name'] ) ) : '';
							$address = isset( $post_data['dhl_preferred_location_neighbor_address'] ) ? wc_clean( wp_unslash( $post_data['dhl_preferred_location_neighbor_address'] ) ) : '';

							$data['preferred_location_type'] = 'neighbor';

							if ( ! empty( $name ) && ! empty( $address ) ) {
								$data['preferred_location_neighbor_name']    = $name;
								$data['preferred_location_neighbor_address'] = $address;
							} else {
								$data['errors']->add( 'validation', _x( 'Please choose name and address of your preferred neighbor.', 'dhl', 'woocommerce-germanized' ) );
							}
						}
					}
				}
			}

			if ( self::is_cdp_available( $customer_country ) && self::is_preferred_delivery_type_enabled() ) {
				$data['preferred_delivery_type_enabled'] = true;
				$data['preferred_delivery_type']         = self::get_default_preferred_delivery_type();

				if ( isset( $post_data['dhl_preferred_delivery_type'] ) && array_key_exists( $post_data['dhl_preferred_delivery_type'], self::get_preferred_delivery_types() ) ) {
					$data['preferred_delivery_type'] = wc_clean( wp_unslash( $post_data['dhl_preferred_delivery_type'] ) );
				}
			}

			return $data;
		} catch ( Exception $e ) {
			return $data;
		}
	}

	protected static function refresh_day_session() {
		WC()->session->set( 'dhl_preferred_day_options', array() );

		if ( self::is_preferred_day_enabled() ) {
			$shipping_postcode = WC()->customer->get_shipping_postcode();

			try {
				if ( ! empty( $shipping_postcode ) ) {
					WC()->session->set( 'dhl_preferred_day_options', Package::get_api()->get_preferred_available_days( $shipping_postcode ) );
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}
	}

	public static function add_fields( $with_wrapper = true ) {
		echo '<div class="dhl-preferred-service-content">';

		if ( self::cart_needs_shipping() && self::is_preferred_option_available() ) {
			$data             = self::get_data();
			$data['logo_url'] = Package::get_assets_url() . '/img/dhl-official.png';

			if ( self::preferred_services_available() && self::is_preferred_day_enabled() ) {
				self::refresh_day_session();
				$data['preferred_day_options'] = WC()->session->get( 'dhl_preferred_day_options' );
			}

			wc_get_template( 'checkout/dhl/preferred-services.php', $data, Package::get_template_path(), Package::get_path() . '/templates/' );
		}

		echo '</div>';
	}

	public static function is_enabled() {
		return Package::base_country_supports( 'services' );
	}

	public static function is_preferred_day_enabled() {
		return wc_string_to_bool( self::get_setting( 'PreferredDay_enable' ) );
	}

	public static function is_preferred_time_enabled() {
		return wc_string_to_bool( self::get_setting( 'PreferredTime_enable' ) );
	}

	public static function is_preferred_location_enabled() {
		return wc_string_to_bool( self::get_setting( 'PreferredLocation_enable' ) );
	}

	public static function is_preferred_delivery_type_enabled() {
		return wc_string_to_bool( self::get_setting( 'PreferredDeliveryType_enable' ) );
	}

	public static function is_preferred_neighbor_enabled() {
		return wc_string_to_bool( self::get_setting( 'PreferredNeighbour_enable' ) );
	}

	public static function get_default_preferred_delivery_type() {
		return self::get_setting( 'default_delivery_type' ) ? self::get_setting( 'default_delivery_type' ) : 'home';
	}

	public static function get_preferred_delivery_types() {
		return array(
			'cdp'  => _x( 'Shop', 'dhl delivery type context', 'woocommerce-germanized' ),
			'home' => _x( 'Home', 'dhl delivery type context', 'woocommerce-germanized' ),
		);
	}

	public static function get_preferred_delivery_type_title( $type ) {
		$types = self::get_preferred_delivery_types();

		if ( array_key_exists( $type, $types ) ) {
			return $types[ $type ];
		} else {
			return '';
		}
	}

	protected static function get_setting( $key ) {
		if ( strpos( $key, 'Preferred' ) === false ) {
			$key = 'preferred_' . $key;
		}

		if ( $method = wc_gzd_dhl_get_current_shipping_method() ) {
			if ( $method->has_option( $key ) ) {
				return $method->get_option( $key );
			} elseif ( strpos( $key, '_enable' ) !== false ) {
				return false;
			}
		}

		$setting = Package::get_setting( $key );

		return $setting;
	}
}
