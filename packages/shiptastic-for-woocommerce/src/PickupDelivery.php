<?php

namespace Vendidero\Shiptastic;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Exception;
use WC_Order;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class PickupDelivery {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'woocommerce_order_formatted_shipping_address', array( __CLASS__, 'set_formatted_shipping_address' ), 20, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'set_formatted_customer_shipping_address' ), 10, 3 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'formatted_shipping_replacements' ), 20, 2 );
		add_filter( 'woocommerce_get_order_address', array( __CLASS__, 'register_order_address_customer_number' ), 20, 3 );
		add_filter( 'woocommerce_order_get_shipping_address_2', array( __CLASS__, 'register_order_address_customer_number_fallback' ), 20, 2 );
		add_filter( 'woocommerce_order_get_formatted_shipping_address', array( __CLASS__, 'indicate_order_pickup_location_delivery' ), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 100 );

		add_action( 'woocommerce_after_edit_account_address_form', array( __CLASS__, 'register_customer_address_modal' ) );
		add_action( 'woocommerce_after_save_address_validation', array( __CLASS__, 'register_customer_address_validation' ), 10, 4 );
		add_filter( 'woocommerce_address_to_edit', array( __CLASS__, 'register_customer_address_fields' ), 10, 2 );

		add_action( 'woocommerce_after_checkout_form', array( __CLASS__, 'pickup_location_search_modal' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'register_classic_checkout_fields' ), 10 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'register_order_review_fragments' ), 10, 1 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'register_classic_checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'register_classic_checkout_order_data' ), 10, 2 );

		add_action( 'wp_ajax_woocommerce_stc_search_pickup_locations', array( __CLASS__, 'search_pickup_locations' ) );
		add_action( 'wp_ajax_nopriv_woocommerce_stc_search_pickup_locations', array( __CLASS__, 'search_pickup_locations' ) );
		add_action( 'wc_ajax_woocommerce_stc_search_pickup_locations', array( __CLASS__, 'search_pickup_locations' ) );

		add_filter( 'woocommerce_form_field_wc_shiptastic_current_pickup_location', array( __CLASS__, 'register_current_pickup_location_field' ), 10, 4 );
		add_filter( 'woocommerce_form_field_wc_shiptastic_pickup_location', array( __CLASS__, 'register_pickup_location_field' ), 10, 4 );
		add_filter( 'woocommerce_form_field_wc_shiptastic_pickup_location_customer_number', array( __CLASS__, 'register_pickup_location_customer_number_field' ), 10, 4 );
		add_filter( 'woocommerce_form_field_wc_shiptastic_pickup_location_notice', array( __CLASS__, 'register_pickup_location_notice_field' ), 10, 4 );

		add_filter( 'woocommerce_customer_meta_fields', array( __CLASS__, 'register_admin_profile_fields' ), 50 );
		add_filter( 'woocommerce_admin_shipping_fields', array( __CLASS__, 'register_pickup_location_admin_fields' ), 10, 3 );
	}

	public static function register_admin_profile_fields( $fields ) {
		if ( ! self::is_enabled() ) {
			return $fields;
		}

		$fields['shipping']['fields']['pickup_location_code'] = array(
			'label'       => _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ),
			'description' => _x( 'The number of a valid pickup location.', 'shipments', 'woocommerce-germanized' ),
		);

		$fields['shipping']['fields']['pickup_location_customer_number'] = array(
			'label'       => _x( 'Pickup customer number', 'shipments', 'woocommerce-germanized' ),
			'description' => _x( 'The customer number, if needed, for the pickup location.', 'shipments', 'woocommerce-germanized' ),
		);

		return $fields;
	}

	public static function register_customer_address_modal() {
		self::pickup_location_search_modal( 'customer' );
	}

	public static function register_customer_address_validation( $user_id, $address_type, $address, $customer ) {
		if ( ! self::is_available() ) {
			return;
		}

		$pickup_location_code            = isset( $_POST['current_pickup_location'] ) ? wc_clean( wp_unslash( $_POST['current_pickup_location'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pickup_location_customer_number = isset( $_POST['pickup_location_customer_number'] ) ? wc_clean( wp_unslash( $_POST['pickup_location_customer_number'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $pickup_location_code ) ) {
			$pickup_location_code = '';
		}

		$customer->update_meta_data( 'pickup_location_code', '' );
		$customer->update_meta_data( 'pickup_location_customer_number', '' );

		if ( ! empty( $pickup_location_code ) ) {
			if ( $provider = wc_stc_get_customer_preferred_shipping_provider( $customer ) ) {
				if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
					$address_data = array(
						'country'   => $customer->get_shipping_country(),
						'postcode'  => $customer->get_shipping_postcode(),
						'city'      => $customer->get_shipping_city(),
						'address_1' => $customer->get_shipping_address_1(),
					);

					if ( $provider->supports_pickup_location_delivery( $address_data ) ) {
						if ( $pickup_location = $provider->get_pickup_location_by_code( $pickup_location_code, $address_data ) ) {
							if ( $pickup_location->supports_customer_number() ) {
								if ( ! empty( $pickup_location_customer_number ) || $pickup_location->customer_number_is_mandatory() ) {
									if ( ! $validation = $pickup_location->customer_number_is_valid( $pickup_location_customer_number ) ) {
										if ( is_a( $validation, 'WP_Error' ) ) {
											wc_add_notice( $validation->get_error_message(), 'error' );
										} else {
											wc_add_notice( _x( 'Sorry, your pickup location customer number is invalid.', 'shipments', 'woocommerce-germanized' ), 'error' );
										}

										return;
									}
								}

								$customer->update_meta_data( 'pickup_location_code', $pickup_location_code );
								$customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );

								$pickup_location->replace_address( $customer );
							}
						}
					}
				}
			}
		}
	}

	public static function register_customer_address_fields( $address_fields, $load_address ) {
		if ( ! self::is_available() ) {
			return $address_fields;
		}

		if ( 'shipping' === $load_address ) {
			$pickup_delivery_data = self::get_pickup_location_data( 'customer' );

			$address_fields['shipping_pickup_location_notice'] = array(
				'type'             => 'wc_shiptastic_pickup_location_notice',
				'hidden'           => $pickup_delivery_data['supports_pickup_delivery'] ? false : true,
				'current_location' => $pickup_delivery_data['current_location'],
				'label'            => '',
				'priority'         => 61,
				'required'         => false,
				'value'            => '',
			);

			$address_fields['pickup_location_customer_number'] = array(
				'type'             => 'wc_shiptastic_pickup_location_customer_number',
				'label'            => $pickup_delivery_data['customer_number_field_label'],
				'current_location' => $pickup_delivery_data['current_location'],
				'default'          => $pickup_delivery_data['current_location_customer_number'],
				'priority'         => 62,
				'value'            => $pickup_delivery_data['current_location_customer_number'],
			);

			$address_fields['current_pickup_location'] = array(
				'type'             => 'wc_shiptastic_current_pickup_location',
				'current_location' => $pickup_delivery_data['current_location'],
				'default'          => $pickup_delivery_data['current_location_code'],
				'label'            => '',
				'value'            => $pickup_delivery_data['current_location'] ? $pickup_delivery_data['current_location']->get_code() : '',
			);
		}

		return $address_fields;
	}

	public static function get_pickup_location_data( $context = 'checkout', $retrieve_locations = false, $address_args = array(), $current_provider = '' ) {
		$customer   = wc()->customer;
		$query_args = array();
		$provider   = false;
		$result     = array(
			'address'                          => array(),
			'provider'                         => '',
			'supports_pickup_delivery'         => false,
			'current_location_code'            => '',
			'current_location'                 => null,
			'current_location_customer_number' => '',
			'customer_number_field_label'      => _x( 'Customer Number', 'shipments', 'woocommerce-germanized' ),
			'locations'                        => array(),
		);

		if ( $method = wc_stc_get_current_shipping_provider_method() ) {
			$provider = $method->get_shipping_provider_instance();
		}

		if ( 'customer' === $context ) {
			$customer = new \WC_Customer( get_current_user_id() );
			$provider = wc_stc_get_customer_preferred_shipping_provider( $customer );
		} elseif ( 'checkout' === $context ) {
			$query_args = self::get_pickup_delivery_cart_args();
		}

		if ( ! empty( $current_provider ) ) {
			$provider = wc_stc_get_shipping_provider( $current_provider );
		}

		if ( $customer ) {
			$result['address'] = wp_parse_args(
				$address_args,
				array(
					'country'   => $customer->get_shipping_country() ? $customer->get_shipping_country() : $customer->get_billing_country(),
					'state'     => $customer->get_shipping_state() ? $customer->get_shipping_state() : $customer->get_billing_state(),
					'city'      => $customer->get_shipping_city() ? $customer->get_shipping_city() : $customer->get_billing_city(),
					'postcode'  => $customer->get_shipping_postcode() ? $customer->get_shipping_postcode() : $customer->get_billing_postcode(),
					'address_1' => $customer->get_shipping_address_1() ? $customer->get_shipping_address_1() : $customer->get_billing_address_1(),
				)
			);
		} else {
			$result['address'] = wp_parse_args(
				$address_args,
				array(
					'country'   => '',
					'state'     => '',
					'city'      => '',
					'postcode'  => '',
					'address_1' => '',
				)
			);
		}

		$result['provider'] = $provider ? $provider->get_name() : '';

		if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
			if ( $provider->supports_pickup_location_delivery( $result['address'], $query_args ) ) {
				$result['supports_pickup_delivery'] = true;

				if ( $retrieve_locations ) {
					$result['locations'] = $provider->get_pickup_locations( $result['address'], $query_args );
				}

				$current_location_code = self::get_pickup_location_code_by_customer( $customer );

				if ( $current_location_code ) {
					if ( $current_location = $provider->get_pickup_location_by_code( $current_location_code, $result['address'] ) ) {
						if ( $retrieve_locations ) {
							$result['locations'][] = $current_location;
						}

						if ( $current_location->supports_customer_number() ) {
							$result['customer_number_field_label']      = $current_location->get_customer_number_field_label();
							$result['current_location_customer_number'] = self::get_pickup_location_customer_number_by_customer( $customer );
						}

						$result['current_location_code'] = $current_location_code;
						$result['current_location']      = $current_location;
					}
				}
			}
		}

		return $result;
	}

	public static function pickup_location_search_modal( $context = 'checkout' ) {
		if ( ! self::is_available() ) {
			return;
		}

		$pickup_delivery_data = self::get_pickup_location_data( $context, true );
		?>
		<div class="wc-stc-modal-background"></div>

		<div class="wc-stc-modal-content" data-id="pickup-location" style="display: none;">
			<div class="wc-stc-modal-content-inner">
				<header>
					<h4><?php echo esc_html_x( 'Choose a pickup location', 'shipments', 'woocommerce-germanized' ); ?></h4>
					<a class="wc-stc-modal-close" href="#"><?php echo esc_html_x( 'Close modal', 'shipments', 'woocommerce-germanized' ); ?></a>
				</header>
				<article>
					<form id="wc-shiptastic-pickup-location-search-form" method="post">
						<?php do_action( 'woocommerce_shiptastic_pickup_delivery_modal_before_fields' ); ?>

						<div class="pickup-location-search-fields-wrapper">
							<?php
							woocommerce_form_field(
								'pickup_location_address',
								array(
									'label'        => _x( 'Street address', 'shipments', 'woocommerce-germanized' ),
									/* translators: use local order of street name and house number. */
									'placeholder'  => esc_attr_x( 'House number and street name', 'shipments', 'woocommerce-germanized' ),
									'class'        => array( 'form-row-first', 'address-field' ),
									'autocomplete' => 'address-line1',
									'id'           => 'pickup-location-address',
								)
							);
							?>

							<?php
							woocommerce_form_field(
								'pickup_location_postcode',
								array(
									'label'        => _x( 'Postcode', 'shipments', 'woocommerce-germanized' ),
									'validate'     => array( 'postcode' ),
									'class'        => array( 'form-row-last', 'address-field' ),
									'autocomplete' => 'postal-code',
									'id'           => 'pickup-location-postcode',
									'default'      => $pickup_delivery_data['address']['postcode'],
									'required'     => true,
								)
							);
							?>

							<button type="submit" class="button <?php echo esc_attr( wc_stc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_stc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" id="wc-shiptastic-search-pickup-location-submit" aria-label="<?php echo esc_html_x( 'Search', 'shipments', 'woocommerce-germanized' ); ?>"><?php echo esc_html_x( 'Search', 'shipments', 'woocommerce-germanized' ); ?></button>
						</div>

						<div class="pickup-location-search-results">
							<?php
							woocommerce_form_field(
								'pickup_location',
								array(
									'type'             => 'wc_shiptastic_pickup_location',
									'provider'         => $pickup_delivery_data['provider'],
									'locations'        => $pickup_delivery_data['locations'],
									'current_location' => $pickup_delivery_data['current_location'],
									'default'          => $pickup_delivery_data['current_location'] ? $pickup_delivery_data['current_location']->get_code() : '',
									'label'            => '',
								)
							);
							?>
						</div>

						<div class="pickup-location-search-actions">
							<a href="#" class="pickup-location-remove <?php echo esc_attr( $pickup_delivery_data['current_location'] ? '' : 'hidden' ); ?>" aria-label="<?php echo esc_html_x( 'Remove pickup location', 'shipments', 'woocommerce-germanized' ); ?>" role="button"><?php echo esc_html_x( 'Remove pickup location', 'shipments', 'woocommerce-germanized' ); ?></a>
							<a href="#" class="submit-pickup-location <?php echo esc_attr( $pickup_delivery_data['current_location'] ? '' : 'hidden' ); ?> button <?php echo esc_attr( wc_stc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_stc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" aria-label="<?php echo esc_html_x( 'Choose pickup location', 'shipments', 'woocommerce-germanized' ); ?>" role="button"><?php echo esc_html_x( 'Choose pickup location', 'shipments', 'woocommerce-germanized' ); ?></a>

							<?php do_action( 'woocommerce_shiptastic_pickup_delivery_modal_actions' ); ?>
						</div>

						<?php wp_nonce_field( 'wc-shiptastic-search-pickup-location' ); ?>
					</form>
				</article>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $field_id
	 * @param string $code
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function update_order_pickup_location_code( $field_id, $code, $order ) {
		$code = wc_clean( $code );

		if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
			$order->delete_meta_data( '_pickup_location_address' );

			if ( $provider = $shipment_order->get_shipping_provider() ) {
				if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
					if ( $location = $provider->get_pickup_location_by_code( $code, $order->get_address( 'shipping' ) ) ) {
						$code = $location->get_code();
						$order->update_meta_data( '_pickup_location_address', $location->get_address() );
					}
				}
			}
		}

		$order->update_meta_data( '_pickup_location_code', $code );

		return $code;
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

		if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
			if ( $shipment_order->supports_pickup_location() ) {
				$fields['pickup_location_code'] = array(
					'label'           => _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ),
					'type'            => 'text',
					'id'              => '_pickup_location_code',
					'show'            => false,
					'value'           => $shipment_order->get_pickup_location_code(),
					'update_callback' => array( __CLASS__, 'update_order_pickup_location_code' ),
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

	public static function register_pickup_location_notice_field( $field, $key, $args, $value ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'               => $key,
				'hidden'           => true,
				'current_location' => null,
			)
		);
		ob_start();
		?>
		<div class="form-row form-row-wide pickup_location_notice <?php echo esc_attr( $args['hidden'] ? 'hidden' : '' ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" data-priority="<?php echo esc_attr( $args['priority'] ); ?>">
			<div class="choose-pickup-location" <?php echo ( $args['current_location'] ? 'style="display: none;"' : '' ); ?>>
				<p>
					<span class="pickup-location-notice-title"><?php echo esc_html_x( 'Not at home?', 'shipments', 'woocommerce-germanized' ); ?></span>
					<a href="#" class="pickup-location-notice-link wc-stc-modal-launcher" data-modal-id="pickup-location" aria-label="<?php echo esc_html_x( 'Choose a pickup location', 'shipments', 'woocommerce-germanized' ); ?>" role="button"><?php echo esc_html_x( 'Choose a pickup location', 'shipments', 'woocommerce-germanized' ); ?></a>

					<?php do_action( 'woocommerce_shiptastic_after_pickup_location_choose_notice', $args ); ?>
				</p>
			</div>
			<div class="currently-shipping-to" <?php echo ( ! $args['current_location'] ? 'style="display: none;"' : '' ); ?>>
				<p>
					<span class="currently-shipping-to-title"><?php printf( esc_html_x( 'Currently shipping to:', 'shipments', 'woocommerce-germanized' ) ); ?></span>
					<a href="#" class="pickup-location-notice-link pickup-location-manage-link wc-stc-modal-launcher" data-modal-id="pickup-location" role="button"><?php echo wp_kses_post( $args['current_location'] ? $args['current_location']->get_label() : '' ); ?></a>
				</p>
				<a href="#" class="pickup-location-remove" aria-label="<?php echo esc_html_x( 'Remove pickup location', 'shipments', 'woocommerce-germanized' ); ?>" role="button"><?php echo esc_html_x( 'Remove pickup location', 'shipments', 'woocommerce-germanized' ); ?></a>
			</div>
		</div>
		<?php
		$field = ob_get_clean();

		return $field;
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

	public static function register_current_pickup_location_field( $field, $key, $args, $value ) {
		$args = wp_parse_args(
			$args,
			array(
				'locations'         => array(),
				'id'                => $key,
				'priority'          => '',
				'value'             => null,
				'required'          => false,
				'custom_attributes' => array(),
				'current_location'  => null,
				'hidden'            => true,
				'class'             => array(),
			)
		);

		$args['type']                                       = 'hidden';
		$args['return']                                     = true;
		$args['custom_attributes']['data-current-location'] = $args['current_location'] ? wp_json_encode( $args['current_location']->get_data() ) : '';

		do_action( 'woocommerce_shiptastic_current_pickup_location_field_rendered' );

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
				'provider'          => '',
				'required'          => false,
				'custom_attributes' => array(),
				'current_location'  => null,
				'hidden'            => true,
				'class'             => array(),
			)
		);

		$args['options']                             = array();
		$args['custom_attributes']['data-locations'] = array();
		$args['type']                                = 'select';
		$args['placeholder']                         = _x( 'No pickup location found', 'shipments', 'woocommerce-germanized' );
		$args['return']                              = true;

		foreach ( $args['locations'] as $location ) {
			$args['options'][ $location->get_code() ]                             = $location->get_formatted_address();
			$args['custom_attributes']['data-locations'][ $location->get_code() ] = $location->get_data();
		}

		if ( $args['current_location'] ) {
			if ( ! array_key_exists( $args['current_location']->get_code(), $args['options'] ) ) {
				$args['options'][ $args['current_location']->get_code() ]                             = $location->get_formatted_address();
				$args['custom_attributes']['data-locations'][ $args['current_location']->get_code() ] = $args['current_location']->get_data();
			}

			$args['default'] = $args['current_location']->get_code();
		}

		if ( empty( $args['options'] ) ) {
			$args['options'][] = _x( 'Search pickup locations', 'shipments', 'woocommerce-germanized' );
		}

		$args['custom_attributes']['data-locations'] = wp_json_encode( $args['custom_attributes']['data-locations'] );
		$args['custom_attributes']['data-provider']  = $args['provider'];

		if ( count( $args['options'] ) > 0 ) {
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

		$pickup_location_code            = isset( $data['current_pickup_location'] ) ? trim( wc_clean( $data['current_pickup_location'] ) ) : '';
		$pickup_location_customer_number = isset( $data['pickup_location_customer_number'] ) ? trim( wc_clean( $data['pickup_location_customer_number'] ) ) : '';
		$pickup_location                 = false;

		if ( empty( $pickup_location_code ) ) {
			$pickup_location_code = '';
		}

		if ( ! empty( $pickup_location_code ) ) {
			if ( $provider = wc_stc_get_order_shipping_provider( $order ) ) {
				if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
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
							$order->update_meta_data( '_pickup_location_address', $pickup_location->get_address() );

							if ( $pickup_location->supports_customer_number() ) {
								$order->update_meta_data( '_pickup_location_customer_number', $pickup_location_customer_number );
							}

							$pickup_location->replace_address( $order );
						}
					}
				}
			}
		}

		if ( $order->get_customer_id() ) {
			$wc_customer = new \WC_Customer( $order->get_customer_id() );

			$wc_customer->update_meta_data( 'pickup_location_code', '' );
			$wc_customer->update_meta_data( 'pickup_location_customer_number', '' );

			if ( $pickup_location ) {
				$wc_customer->update_meta_data( 'pickup_location_code', $pickup_location_code );
				$pickup_location->replace_address( $wc_customer );

				if ( $pickup_location->supports_customer_number() ) {
					$wc_customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );
				}
			}

			$wc_customer->save();
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

		$pickup_location_code            = isset( $data['current_pickup_location'] ) ? trim( wc_clean( $data['current_pickup_location'] ) ) : '';
		$pickup_location_customer_number = isset( $data['pickup_location_customer_number'] ) ? wc_clean( $data['pickup_location_customer_number'] ) : '';

		if ( empty( $pickup_location_code ) ) {
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

			if ( $method = wc_stc_get_current_shipping_provider_method() ) {
				if ( $provider = $method->get_shipping_provider_instance() ) {
					if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
						$pickup_location = $provider->get_pickup_location_by_code( $pickup_location_code );
						$is_valid        = $provider->is_valid_pickup_location( $pickup_location_code );

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

		$pickup_delivery_data = self::get_pickup_location_data( 'checkout', true );
		$locations            = array();

		if ( ! empty( $pickup_delivery_data['locations'] ) ) {
			foreach ( $pickup_delivery_data['locations'] as $location ) {
				$locations[ $location->get_code() ] = $location->get_data();
			}
		}

		$fragments['.wc-shiptastic-current-provider']          = $pickup_delivery_data['provider'];
		$fragments['.wc-shiptastic-pickup-locations']          = wp_json_encode( $locations );
		$fragments['.wc-shiptastic-pickup-location-supported'] = $pickup_delivery_data['supports_pickup_delivery'];

		return $fragments;
	}

	public static function search_pickup_locations() {
		check_ajax_referer( 'wc-shiptastic-search-pickup-location' );

		$postcode  = isset( $_POST['pickup_location_postcode'] ) ? wc_clean( wp_unslash( $_POST['pickup_location_postcode'] ) ) : '';
		$address_1 = isset( $_POST['pickup_location_address'] ) ? wc_clean( wp_unslash( $_POST['pickup_location_address'] ) ) : '';
		$context   = isset( $_POST['context'] ) ? wc_clean( wp_unslash( $_POST['context'] ) ) : 'checkout';
		$context   = in_array( $context, array( 'customer', 'checkout' ), true ) ? $context : 'checkout';

		if ( empty( $postcode ) ) {
			$postcode = wc()->customer->get_shipping_postcode() ? wc()->customer->get_shipping_postcode() : wc()->customer->get_billing_postcode();
		}

		$pickup_data = self::get_pickup_location_data(
			$context,
			true,
			array(
				'postcode'  => $postcode,
				'address_1' => $address_1,
			)
		);

		$locations = array();

		if ( ! empty( $pickup_data['locations'] ) ) {
			foreach ( $pickup_data['locations'] as $location ) {
				$locations[ $location->get_code() ] = $location->get_data();
			}
		}

		wp_send_json(
			array(
				'success'   => true,
				'locations' => $locations,
			)
		);
	}

	protected static function is_edit_address_page() {
		global $wp;

		return is_account_page() && isset( $wp->query_vars['edit-address'] );
	}

	public static function register_assets() {
		if ( ( ! is_checkout() && ! self::is_edit_address_page() ) || ! self::is_available() ) {
			return;
		}

		Package::register_script( 'wc-shiptastic-modal', 'static/modal.js', array( 'jquery' ) );
		Package::register_script( 'wc-shiptastic-pickup-locations', 'static/pickup-locations.js', array( 'jquery', 'woocommerce', 'selectWoo', 'wc-shiptastic-modal' ) );

		// Register admin styles.
		wp_register_style( 'woocommerce_shiptastic_pickup_locations', Package::get_assets_url( 'static/pickup-locations-styles.css' ), array(), Package::get_version() );
		wp_enqueue_style( 'woocommerce_shiptastic_pickup_locations' );

		wp_localize_script(
			'wc-shiptastic-pickup-locations',
			'wc_shiptastic_pickup_locations_params',
			array(
				'wc_ajax_url'                     => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'context'                         => self::is_edit_address_page() ? 'customer' : 'checkout',
				'i18n_managed_by_pickup_location' => sprintf( _x( 'Managed by %1$s', 'shipments', 'woocommerce-germanized' ), '<a href="#" class="pickup-location-notice-link wc-stc-modal-launcher" data-modal-id="pickup-location">' . _x( 'pickup location', 'shipments', 'woocommerce-germanized' ) . '</a>' ),
				'i18n_pickup_location_delivery_unavailable' => _x( 'Pickup location delivery is not available any longer. Please review your shipping address.', 'shipments', 'woocommerce-germanized' ),
			)
		);

		wp_enqueue_script( 'wc-shiptastic-pickup-locations' );
	}

	public static function get_excluded_gateways() {
		$excluded_gateways = array( 'cod' );

		if ( ! is_admin() ) {
			$excluded_gateways[] = 'amazon_payments_advanced';
		}

		/**
		 * Filter to disable pickup delivery for certain gateways.
		 *
		 * @param array $gateways Array of gateway IDs to exclude.
		 */
		$excluded_gateways = apply_filters( 'woocommerce_shiptastic_pickup_delivery_excluded_gateways', $excluded_gateways );

		return $excluded_gateways;
	}

	public static function get_pickup_delivery_cart_args() {
		if ( ! wc()->cart ) {
			return array(
				'max_weight'      => 0.0,
				'max_dimensions'  => array(
					'length' => 0.0,
					'width'  => 0.0,
					'height' => 0.0,
				),
				'payment_gateway' => '',
				'shipping_method' => false,
			);
		}

		$max_weight      = wc_get_weight( (float) wc()->cart->get_cart_contents_weight(), wc_stc_get_packaging_weight_unit() );
		$shipping_method = wc_stc_get_current_shipping_provider_method();
		$max_dimensions  = array(
			'length' => 0.0,
			'width'  => 0.0,
			'height' => 0.0,
		);

		if ( $shipping_method && is_a( $shipping_method->get_method(), 'Vendidero\Shiptastic\ShippingMethod\ShippingMethod' ) ) {
			$controller              = new CartController();
			$cart                    = wc()->cart;
			$has_calculated_shipping = $cart->show_shipping();
			$shipping_packages       = $has_calculated_shipping ? $controller->get_shipping_packages() : array();
			$current_rate_id         = wc_stc_get_current_shipping_method_id();

			if ( isset( $shipping_packages[0]['rates'][ $current_rate_id ] ) ) {
				$rate = $shipping_packages[0]['rates'][ $current_rate_id ];

				if ( is_a( $rate, 'WC_Shipping_Rate' ) ) {
					$meta = $rate->get_meta_data();

					if ( isset( $meta['_packages'] ) ) {
						$max_weight = 0.0;

						foreach ( (array) $meta['_packages'] as $package_data ) {
							$packaging_id = $package_data['packaging_id'];

							if ( $packaging = wc_stc_get_packaging( $packaging_id ) ) {
								$package_weight = (float) wc_get_weight( $package_data['weight'], wc_stc_get_packaging_weight_unit(), 'g' );

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
				if ( $product = wc_shiptastic_get_product( $values['data'] ) ) {
					if ( $product->has_dimensions() ) {
						$length = (float) wc_get_dimension( (float) $product->get_shipping_length(), wc_stc_get_packaging_dimension_unit() );
						$width  = (float) wc_get_dimension( (float) $product->get_shipping_width(), wc_stc_get_packaging_dimension_unit() );
						$height = (float) wc_get_dimension( (float) $product->get_shipping_height(), wc_stc_get_packaging_dimension_unit() );

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
			'payment_gateway' => Package::get_current_payment_gateway(),
			'shipping_method' => $shipping_method,
		);
	}

	public static function is_enabled() {
		return apply_filters( 'woocommerce_shiptastic_enable_pickup_delivery', true );
	}

	public static function is_available() {
		$available = self::is_enabled();

		if ( is_checkout() && ( wc()->cart && ! wc()->cart->needs_shipping() ) ) {
			$available = false;
		}

		if ( 'billing_only' === get_option( 'woocommerce_ship_to_destination' ) ) {
			$available = false;
		}

		return apply_filters( 'woocommerce_shiptastic_pickup_delivery_available', $available );
	}

	public static function register_classic_checkout_fields( $fields ) {
		if ( ! self::is_available() || ! wc()->customer ) {
			return $fields;
		}

		$pickup_delivery_data = self::get_pickup_location_data( 'checkout' );

		$fields['billing']['billing_pickup_location_notice'] = array(
			'type'             => 'wc_shiptastic_pickup_location_notice',
			'hidden'           => $pickup_delivery_data['supports_pickup_delivery'] && ! $pickup_delivery_data['current_location'] ? false : true,
			'current_location' => $pickup_delivery_data['current_location'],
			'label'            => '',
			'priority'         => 61,
			'required'         => false,
		);

		$fields['shipping']['shipping_pickup_location_notice'] = array(
			'type'             => 'wc_shiptastic_pickup_location_notice',
			'hidden'           => $pickup_delivery_data['supports_pickup_delivery'] ? false : true,
			'current_location' => $pickup_delivery_data['current_location'],
			'label'            => '',
			'priority'         => 61,
			'required'         => false,
		);

		$fields['shipping']['pickup_location_customer_number'] = array(
			'type'             => 'wc_shiptastic_pickup_location_customer_number',
			'label'            => $pickup_delivery_data['customer_number_field_label'],
			'current_location' => $pickup_delivery_data['current_location'],
			'default'          => $pickup_delivery_data['current_location_customer_number'],
			'priority'         => 62,
		);

		$enable_order_notes_field            = apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) );
		$current_pickup_location_field_group = 'order';

		if ( apply_filters( 'woocommerce_shiptastic_render_current_pickup_location_in_billing', ! $enable_order_notes_field ) ) {
			$current_pickup_location_field_group = 'billing';
		}

		$current_pickup_location_field = array(
			'type'             => 'wc_shiptastic_current_pickup_location',
			'current_location' => $pickup_delivery_data['current_location'],
			'default'          => $pickup_delivery_data['current_location_code'],
			'label'            => '',
		);

		$fields[ $current_pickup_location_field_group ]['current_pickup_location'] = $current_pickup_location_field;

		if ( 'order' === $current_pickup_location_field_group ) {
			add_action(
				'woocommerce_after_order_notes',
				function ( $checkout ) use ( $current_pickup_location_field ) {
					if ( ! did_action( 'woocommerce_shiptastic_current_pickup_location_field_rendered' ) ) {
						woocommerce_form_field( 'current_pickup_location', $current_pickup_location_field, $checkout->get_value( 'current_pickup_location' ) );
					}
				}
			);
		}

		return $fields;
	}

	/**
	 * @param $fields
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function set_formatted_shipping_address( $fields, $order ) {
		if ( ! self::is_enabled() ) {
			return $fields;
		}

		if ( ! empty( $fields ) && is_array( $fields ) ) {
			$shipment_order  = wc_stc_get_shipment_order( $order );
			$customer_number = $shipment_order->get_pickup_location_customer_number();

			if ( $shipment_order->has_pickup_location() && ! empty( $customer_number ) ) {
				$fields['pickup_location_customer_number'] = $customer_number;
			}
		}

		return $fields;
	}

	public static function set_formatted_customer_shipping_address( $address, $customer_id, $name ) {
		if ( ! self::is_enabled() ) {
			return $address;
		}

		if ( 'shipping' === $name ) {
			if ( self::is_pickup_location_delivery_available_for_customer( $customer_id ) ) {
				if ( $customer_number = self::get_pickup_location_customer_number_by_customer( $customer_id ) ) {
					$address['pickup_location_customer_number'] = $customer_number;
				}
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

		return apply_filters( 'woocommerce_shiptastic_shipment_customer_pickup_location_customer_number', $customer_number, $customer );
	}

	protected static function get_customer( $customer_id = false ) {
		$customer = false;

		if ( is_numeric( $customer_id ) ) {
			$customer = new \WC_Customer( $customer_id );
		} elseif ( is_a( $customer_id, 'WC_Customer' ) ) {
			$customer = $customer_id;
		} elseif ( is_user_logged_in() ) {
			$customer = new \WC_Customer( get_current_user_id() );
		} elseif ( wc()->customer ) {
			$customer = wc()->customer;
		}

		return $customer;
	}

	public static function is_pickup_location_delivery_available_for_customer( $customer_id ) {
		$supports_pickup_delivery = false;

		if ( $customer = self::get_customer( $customer_id ) ) {
			$address           = $customer->get_shipping();
			$shipping_provider = wc_stc_get_customer_preferred_shipping_provider( $customer->get_id() );

			if ( $shipping_provider ) {
				if ( is_a( $shipping_provider, 'Vendidero\Shiptastic\ShippingProvider\Auto' ) ) {
					if ( $shipping_provider->supports_pickup_location_delivery( $address ) ) {
						$supports_pickup_delivery = true;
					}
				}
			}
		}

		return apply_filters( 'woocommerce_shiptastic_shipment_pickup_location_delivery_available_for_customer', $supports_pickup_delivery, $customer_id );
	}

	public static function get_pickup_location_code_by_customer( $customer_id = false ) {
		$customer    = self::get_customer( $customer_id );
		$pickup_code = '';

		if ( ! $customer ) {
			return '';
		}

		if ( $customer->get_meta( 'pickup_location_code' ) ) {
			$pickup_code = $customer->get_meta( 'pickup_location_code' );
		}

		return apply_filters( 'woocommerce_shiptastic_shipment_customer_pickup_location_code', $pickup_code, $customer );
	}

	public static function get_pickup_location_code_by_user( $customer_id = false ) {
		return self::get_pickup_location_code_by_customer( $customer_id );
	}

	/**
	 * @param string $address
	 * @param array $raw_address
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function indicate_order_pickup_location_delivery( $address, $raw_address, $order ) {
		if ( ! empty( $address ) && ( $shipment_order = wc_stc_get_shipment_order( $order ) ) ) {
			if ( $shipment_order->has_pickup_location() ) {
				if ( $provider = $shipment_order->get_shipping_provider() ) {
					if ( is_a( $provider, 'Vendidero\Shiptastic\Interfaces\ShippingProviderAuto' ) ) {
						if ( ! $provider->replace_shipping_address_by_pickup_location() ) {
							$pickup_location_address = $shipment_order->get_pickup_location_address();

							if ( ! empty( $pickup_location_address ) ) {
								$pickup_location_address['first_name'] = $raw_address['first_name'];
								$pickup_location_address['last_name']  = $raw_address['last_name'];

								$formatted_address = WC()->countries->get_formatted_address( $pickup_location_address );
								$address           = $formatted_address;
							}
						}
					}
				}
			}
		}

		return $address;
	}

	/**
	 * @param string $address_2
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function register_order_address_customer_number_fallback( $address_2, $order ) {
		if ( ! self::is_enabled() ) {
			return $address_2;
		}

		if ( empty( $address_2 ) ) {
			if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
				if ( $customer_number = $shipment_order->get_pickup_location_customer_number() ) {
					$address_2 = $customer_number;
				}
			}
		}

		return $address_2;
	}

	/**
	 * @param array $address
	 * @param $address_type
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function register_order_address_customer_number( $address, $address_type, $order ) {
		if ( ! self::is_enabled() ) {
			return $address;
		}

		if ( 'shipping' === $address_type ) {
			if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
				if ( $customer_number = $shipment_order->get_pickup_location_customer_number() ) {
					$address['pickup_location_customer_number'] = $customer_number;

					/**
					 * For compatibility (e.g. third-party plugins) register the customer number
					 * as address_2 field too.
					 */
					if ( empty( $address['address_2'] ) ) {
						$address['address_2'] = $customer_number;
					}
				}
			}
		}

		return $address;
	}

	public static function formatted_shipping_replacements( $fields, $args ) {
		if ( ! self::is_enabled() ) {
			return $fields;
		}

		if ( isset( $args['pickup_location_customer_number'] ) && ! empty( $args['pickup_location_customer_number'] ) ) {
			$fields['{name}'] = $fields['{name}'] . "\n" . $args['pickup_location_customer_number'];

			if ( isset( $fields['{address_2}'] ) && isset( $args['address_2'] ) && $args['address_2'] === $args['pickup_location_customer_number'] ) {
				$fields['{address_2}'] = '';
			}
		}

		return $fields;
	}
}
