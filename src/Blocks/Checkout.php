<?php
namespace Vendidero\Germanized\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Vendidero\Germanized\Blocks\PaymentGateways\DirectDebit;
use Vendidero\Germanized\Blocks\PaymentGateways\Invoice;
use Vendidero\Germanized\Package;
use Vendidero\Germanized\Utilities\CartCheckout;

final class Checkout {

	public function __construct() {
		$this->adjust_markup();
		$this->register_filters();
		$this->register_integrations();
		$this->register_endpoint_data();
		$this->register_validation_and_storage();
	}

	private function register_filters() {
		add_filter(
			'woocommerce_gzd_checkout_checkbox_is_checked',
			function ( $is_checked, $checkbox_id ) {
				if ( WC_germanized()->is_rest_api_request() ) {
					$checked = WC()->session ? WC()->session->get( 'checkout_checkboxes_checked', array() ) : array();

					if ( in_array( $checkbox_id, $checked, true ) ) {
						$is_checked = true;
					} else {
						$is_checked = false;
					}
				}

				return $is_checked;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_gzd_checkout_checkbox_is_visible',
			function ( $is_visible, $checkbox_id ) {
				if ( 'photovoltaic_systems' === $checkbox_id ) {
					if ( has_block( 'woocommerce/checkout' ) || ( WC()->session && WC_Germanized()->is_rest_api_request() && WC()->session->get( 'gzd_is_checkout_checkout', false ) ) ) {
						$is_visible = true;
					}
				}

				return $is_visible;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_get_item_data',
			function ( $item_data, $item ) {
				$needs_price_labels = CartCheckout::uses_checkout_block() || CartCheckout::uses_cart_block() || WC()->is_rest_api_request();

				if ( apply_filters( 'woocommerce_gzd_cart_checkout_needs_block_price_labels', $needs_price_labels ) ) {
					$labels = wc_gzd_get_checkout_shopmarks();

					if ( is_checkout() || has_block( 'woocommerce/checkout' ) ) {
						$labels = wc_gzd_get_checkout_shopmarks();
					} elseif ( is_cart() || has_block( 'woocommerce/cart' ) ) {
						$labels = wc_gzd_get_cart_shopmarks();
					}

					$label_item_data = array();

					foreach ( $labels as $label ) {
						if ( ! $label->is_enabled() ) {
							continue;
						}

						$callback  = $label->get_callback();
						$arg_count = $label->get_number_of_params();

						if ( 'differential_taxation' === $label->get_type() ) {
							add_filter( 'woocommerce_gzd_differential_taxation_notice_text_mark', '__return_false' );
							$callback  = 'woocommerce_gzd_template_differential_taxation_notice_cart';
							$arg_count = 0;
						}

						$args = array( '', $item, $item['key'] );

						if ( 2 === $arg_count ) {
							$args = array( $item, $item['key'] );
						} elseif ( 0 === $arg_count ) {
							$args = array();
						}

						ob_start();
						if ( $label->get_is_action() ) {
							call_user_func_array( $callback, $args );
						} else {
							echo wp_kses_post( call_user_func_array( $callback, $args ) );
						}
						$output = trim( ob_get_clean() );

						if ( ! empty( $output ) ) {
							$label_item_data[] = array(
								'key'     => 'gzd-' . $label->get_type(),
								'value'   => $output,
								'display' => '',
							);
						}
					}

					if ( ! empty( $label_item_data ) ) {
						$item_data = array_merge( $label_item_data, $item_data );
					}
				}

				return $item_data;
			},
			10000,
			2
		);
	}

	private function adjust_markup() {
		add_filter(
			'render_block',
			function ( $content, $block ) {
				/**
				 * Whether to disable the (structural) adjustments applied to the WooCommerce checkout block.
				 *
				 * @param boolean Whether to disable the checkout adjustments or not.
				 *
				 * @since 3.14.0
				 */
				if ( 'woocommerce/checkout' === $block['blockName'] ) {
					if ( ! apply_filters( 'woocommerce_gzd_disable_checkout_block_adjustments', false ) ) {
						$content               = str_replace( 'wp-block-woocommerce-checkout ', 'wp-block-woocommerce-checkout wc-gzd-checkout ', $content );
						$has_custom_gzd_submit = false;

						preg_match( '/<\/div>(\s*)<div[^<]*?data-block-name="woocommerce\/checkout-fields-block"/', $content, $matches );

						/**
						 * Latest Woo Checkout Block version inserts the total blocks before checkout fields
						 */
						if ( ! empty( $matches ) ) {
							$content               = str_replace( 'wc-gzd-checkout ', 'wc-gzd-checkout wc-gzd-checkout-v2 ', $content );
							$replacement           = '<div class="wc-gzd-checkout-submit"><div data-block-name="woocommerce/checkout-order-summary-block" class="wp-block-woocommerce-checkout-order-summary-block"></div><div data-block-name="woocommerce/checkout-actions-block" class="wp-block-woocommerce-checkout-actions-block"></div></div>' . $matches[0];
							$content               = preg_replace( '/<\/div>(\s*)<div[^<]*?data-block-name="woocommerce\/checkout-fields-block"/', $replacement, $content );
							$has_custom_gzd_submit = true;
						} else {
							/**
							 * Older Woo versions used to insert the total block as last item.
							 * Allow additional, optional whitespace at the end of the block content.
							 */
							preg_match( '/<\/div>(\s*)<\/div>(\s*)$/', $content, $matches );

							if ( ! empty( $matches ) ) {
								$replacement           = '<div class="wc-gzd-checkout-submit"><div data-block-name="woocommerce/checkout-order-summary-block" class="wp-block-woocommerce-checkout-order-summary-block"></div><div data-block-name="woocommerce/checkout-actions-block" class="wp-block-woocommerce-checkout-actions-block"></div></div></div></div>';
								$content               = preg_replace( '/<\/div>(\s*)<\/div>(\s*)$/', $replacement, $content );
								$has_custom_gzd_submit = true;
							}
						}

						/**
						 * Do only hide Woo submit button in case we've successfully placed the custom button.
						 */
						if ( $has_custom_gzd_submit ) {
							$content = str_replace( 'wc-gzd-checkout ', 'wc-gzd-checkout wc-gzd-checkout-has-custom-submit ', $content );
						}
					}

					if ( WC()->session ) {
						WC()->session->set( 'checkout_checkboxes_checked', array() );
						WC()->session->set( 'gzd_is_checkout_checkout', true );
					}
				} elseif ( 'woocommerce/cart' === $block['blockName'] ) {
					if ( WC()->session ) {
						WC()->session->set( 'gzd_is_checkout_checkout', false );
					}
				}

				return $content;
			},
			1000,
			2
		);
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new \Vendidero\Germanized\Blocks\Integrations\Checkout() );
			}
		);

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( $payment_method_registry ) {
				$payment_method_registry->register(
					Package::container()->get( Invoice::class )
				);

				$payment_method_registry->register(
					Package::container()->get( DirectDebit::class )
				);
			}
		);
	}

	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-germanized',
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
				'namespace'       => 'woocommerce-germanized',
				'schema_callback' => function () {
					return $this->get_checkout_schema();
				},
			)
		);

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'woocommerce-germanized-checkboxes',
				'callback'  => function ( $data ) {
					$checkboxes = isset( $data['checkboxes'] ) ? (array) wc_clean( wp_unslash( $data['checkboxes'] ) ) : array();

					$this->parse_checkboxes( $checkboxes );
				},
			)
		);
	}

	private function register_validation_and_storage() {
		$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : false;
		$hook_name  = '__experimental_woocommerce_blocks_validate_location_address_fields';

		if ( $wc_version && version_compare( $wc_version, '8.9.0', '>=' ) ) {
			$hook_name = 'woocommerce_blocks_validate_location_address_fields';
		}

		add_action(
			$hook_name,
			function ( $errors, $fields, $group ) {
				if ( 'never' !== get_option( 'woocommerce_gzd_checkout_validate_street_number' ) && function_exists( 'wc_gzd_split_shipment_street' ) ) {
					if ( 'billing' === $group && ! apply_filters( 'woocommerce_gzd_checkout_validate_billing_street_number', true ) ) {
						return $errors;
					}

					$country   = isset( $fields['country'] ) ? $fields['country'] : ( isset( $fields[ "{$group}_country" ] ) ? $fields[ "{$group}_country" ] : '' );
					$address_1 = isset( $fields['address_1'] ) ? $fields['address_1'] : ( isset( $fields[ "{$group}_address_1" ] ) ? $fields[ "{$group}_address_1" ] : '' );

					/**
					 * Somehow Woo calls the filter differently on my account address save action
					 * by handing over the registered fields instead of the actual values.
					 */
					if ( is_array( $country ) ) {
						$country   = isset( $_POST[ $group . '_country' ] ) ? wc_clean( wp_unslash( $_POST[ $group . '_country' ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$address_1 = isset( $_POST[ $group . '_address_1' ] ) ? wc_clean( wp_unslash( $_POST[ $group . '_address_1' ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}

					if ( ! empty( $country ) && ! empty( $address_1 ) && apply_filters( 'woocommerce_gzd_checkout_validate_street_number', true, $fields ) ) {
						$countries = array();

						if ( 'always' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
							$countries = array_keys( WC()->countries->get_allowed_countries() );
						} elseif ( 'base_only' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
							$countries = array( wc_gzd_get_base_country() );
						} elseif ( 'eu_only' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
							$countries = WC()->countries->get_european_union_countries();
						}

						$is_valid = true;

						if ( in_array( $country, $countries, true ) ) {
							$address_parts = wc_gzd_split_shipment_street( $address_1 );
							$is_valid      = '' === $address_parts['number'] ? false : true;
						}

						if ( ! apply_filters( 'woocommerce_gzd_checkout_is_valid_street_number', $is_valid, $fields ) ) {
							$errors->add(
								'invalid_address_1',
								apply_filters( 'woocommerce_gzd_checkout_invalid_street_number_error_message', __( 'Please check the street field and make sure to provide a valid street number.', 'woocommerce-germanized' ), $fields )
							);
						}
					}
				}

				return $errors;
			},
			10,
			3
		);

		add_action(
			'woocommerce_store_api_checkout_update_customer_from_request',
			function ( $customer, $request ) {
				if ( 'never' !== get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$billing  = $request['billing_address'];
					$shipping = $request['shipping_address'];

					if ( ! empty( $billing['address_1'] ) ) {
						$customer->set_billing_address_1( \WC_GZD_Checkout::instance()->format_address_1( $billing['address_1'] ) );
					}

					if ( ! empty( $shipping['address_1'] ) ) {
						$customer->set_shipping_address_1( \WC_GZD_Checkout::instance()->format_address_1( $shipping['address_1'] ) );
					}
				}
			},
			10,
			2
		);

		/**
		 * This hook does not contain any request data, therefor has only limited value.
		 */
		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			function ( $order ) {
				\WC_GZD_Checkout::instance()->order_meta( $order );
			},
			5
		);

		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order, $request ) {
				$this->validate( $order, $request );

				\WC_GZD_Checkout::instance()->order_store_checkbox_data( $order );
				\WC_GZD_Checkout::instance()->add_order_notes( $order );
			},
			10,
			2
		);
	}

	private function get_cart_schema() {
		return array(
			'applies_for_photovoltaic_system_vat_exempt' => array(
				'description' => __( 'Whether the cart applies for a photovoltaic system vat exempt or not.', 'woocommerce-germanized' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'photovoltaic_system_law_details'            => array(
				'description' => __( 'The current cart\'s photovoltaic system law details.', 'woocommerce-germanized' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'text' => array(
						'description' => __( 'The actual law, e.g. paragraph.', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'url'  => array(
						'description' => __( 'The URL to the law.', 'woocommerce-germanized' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'shipping_costs_notice'                      => array(
				'description' => __( 'Cart shipping costs notice.', 'woocommerce-germanized' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'checkboxes'                                 => array(
				'description' => __( 'List of cart checkboxes.', 'woocommerce-germanized' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'                       => array(
							'description' => __( 'Unique identifier for the checkbox within the cart.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name'                     => array(
							'description' => __( 'Checkbox name.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'label'                    => array(
							'description' => __( 'Checkbox label.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'default_checked'          => array(
							'description' => __( 'Checkbox checked status.', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'default'     => false,
						),
						'default_hidden'           => array(
							'description' => __( 'Checkbox hidden by default.', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'default'     => false,
						),
						'error_message'            => array(
							'description' => __( 'Checkbox error message.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'show_for_payment_methods' => array(
							'description' => __( 'Show for specific payment methods only.', 'woocommerce-germanized' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'wrapper_classes'          => array(
							'description' => __( 'Wrapper classes.', 'woocommerce-germanized' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'custom_styles'            => array(
							'description' => __( 'Custom styles.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'html_id'                  => array(
							'description' => __( 'HTML field id.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'has_checkbox'             => array(
							'description' => __( 'Whether to show a checkbox field or not.', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'is_required'              => array(
							'description' => __( 'Whether the checkbox is required or not.', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);
	}

	private function get_cart_data() {
		$checkboxes_for_api     = array();
		$checkboxes_force_print = array( 'privacy' );
		$customer               = wc()->customer;

		foreach ( $this->get_checkboxes() as $id => $checkbox ) {
			if ( ! $checkbox->is_printable() && ! in_array( $checkbox->get_id(), apply_filters( 'woocommerce_gzd_checkout_block_checkboxes_force_print_checkboxes', $checkboxes_force_print ), true ) ) {
				continue;
			}

			$checkboxes_for_api[] = array(
				'id'                       => $id,
				'name'                     => $checkbox->get_html_name(),
				'label'                    => $checkbox->get_label(),
				'wrapper_classes'          => array_diff( $checkbox->get_html_wrapper_classes(), array( 'validate-required', 'form-row' ) ),
				'custom_styles'            => $checkbox->get_html_style(),
				'error_message'            => apply_filters( 'woocommerce_gzd_checkout_block_checkbox_show_inline_error_message', true, $checkbox ) ? $checkbox->get_error_message() : '',
				'html_id'                  => $checkbox->get_html_id(),
				'has_checkbox'             => ! $checkbox->hide_input(),
				'show_for_payment_methods' => $checkbox->get_show_for_payment_methods(),
				'is_required'              => $checkbox->is_mandatory(),
				'default_checked'          => $checkbox->hide_input() ? true : false,
				'default_hidden'           => $checkbox->is_hidden(),
			);
		}

		return array(
			'applies_for_photovoltaic_system_vat_exempt' => wc_gzd_cart_applies_for_photovoltaic_system_vat_exemption(),
			'photovoltaic_system_law_details'            => wc_gzd_cart_get_photovoltaic_systems_law_details(),
			'checkboxes'                                 => $checkboxes_for_api,
			'shipping_costs_notice'                      => wc_gzd_get_shipping_costs_text(),
		);
	}

	private function get_checkout_schema() {
		return array(
			'checkboxes' => array(
				'description' => __( 'List of cart checkboxes.', 'woocommerce-germanized' ),
				'type'        => array( 'array', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array(
							'description' => __( 'Unique identifier for the checkbox within the cart.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'checked' => array(
							'description' => __( 'Checkbox checked status.', 'woocommerce-germanized' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);
	}

	private function get_checkboxes() {
		add_filter(
			'woocommerce_gzd_get_checkout_value',
			function ( $value, $key ) {
				$getter   = "get_{$key}";
				$customer = wc()->customer;

				if ( is_callable( array( $customer, $getter ) ) ) {
					$value = $customer->{ $getter }();
				}

				return $value;
			},
			10,
			2
		);

		$checkbox_manager = \WC_GZD_Legal_Checkbox_Manager::instance();

		return $checkbox_manager->get_checkboxes(
			array(
				'locations' => 'checkout',
				'sort'      => true,
			),
			'render'
		);
	}

	private function parse_checkboxes( $checkboxes ) {
		$checkbox_manager   = \WC_GZD_Legal_Checkbox_Manager::instance();
		$checkboxes_checked = array();

		foreach ( $checkboxes as $checkbox_data ) {
			$checkbox_data = wp_parse_args(
				$checkbox_data,
				array(
					'id'      => '',
					'checked' => false,
				)
			);

			$checkbox = $checkbox_manager->get_checkbox( $checkbox_data['id'] );

			if ( ! $checkbox ) {
				continue;
			}

			if ( true === filter_var( $checkbox_data['checked'], FILTER_VALIDATE_BOOLEAN ) ) {
				$checkboxes_checked[] = $checkbox_data['id'];
			}
		}

		WC()->session->set( 'checkout_checkboxes_checked', $checkboxes_checked );

		return $checkboxes_checked;
	}

	/**
	 * @param \WC_Order $order
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	private function validate( $order, $request ) {
		WC()->session->set( 'checkout_checkboxes_checked', array() );

		$data = $this->get_checkout_data_from_request( $request );

		if ( $this->has_checkout_data( 'checkboxes', $request ) ) {
			$checkboxes_checked = $this->parse_checkboxes( $data['checkboxes'] );

			foreach ( $this->get_checkboxes() as $id => $checkbox ) {
				if ( ! $checkbox->validate( in_array( $id, $checkboxes_checked, true ) ? 'yes' : '' ) ) {
					throw new RouteException( esc_html( "checkbox_{$id}" ), wp_kses_post( $checkbox->get_error_message() ), 400 );
				}
			}
		}
	}

	private function has_checkout_data( $param, $request ) {
		$request_data = isset( $request['extensions']['woocommerce-germanized'] ) ? (array) $request['extensions']['woocommerce-germanized'] : array();

		return isset( $request_data[ $param ] ) && null !== $request_data[ $param ];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	private function get_checkout_data_from_request( $request ) {
		$data = array_filter( isset( $request['extensions']['woocommerce-germanized'] ) ? (array) wc_clean( $request['extensions']['woocommerce-germanized'] ) : array() );

		$data = wp_parse_args(
			$data,
			array(
				'checkboxes' => array(),
			)
		);

		return $data;
	}
}
