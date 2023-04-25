<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Checkout {

	public $custom_fields       = array();
	public $custom_fields_admin = array();

	protected static $force_free_shipping_filter = false;

	protected static $_instance = null;

	protected $checkout_data = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		add_action( 'init', array( $this, 'init_fields' ), 30 );

		add_filter( 'woocommerce_billing_fields', array( $this, 'set_custom_fields' ), 0, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'set_custom_fields_shipping' ), 0, 1 );

		// Add Fields to Order Edit Page
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_custom_fields_admin_billing' ), 0, 1 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'set_custom_fields_admin_shipping' ), 0, 1 );

		// Format tax rate labels (percentage) in case prices during checkout are shown excl tax
		add_filter( 'woocommerce_cart_tax_totals', array( $this, 'set_cart_excluding_tax_labels' ), 10, 2 );

		// Save Fields on order
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_fields' ) );

		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_formatted_address' ), 0, 2 );

		// Support Checkout Field Managers (which are unable to map options to values)
		add_filter(
			'woocommerce_gzd_custom_title_field_value',
			array(
				$this,
				'set_title_field_mapping_editors',
			),
			10,
			1
		);

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'set_order_item_meta_crud' ), 0, 4 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'set_order_item_meta_crud' ), 1000, 4 );

		// Deactivate checkout shipping selection
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'remove_shipping_rates' ), 0 );

		/**
		 * Split tax calculation for fees and shipping
		 */
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( $this, 'adjust_fee_taxes' ), 100, 3 );
		add_filter( 'woocommerce_package_rates', array( $this, 'adjust_shipping_taxes' ), 100, 2 );
		add_filter( 'woocommerce_shipping_method_add_rate_args', array( $this, 'maybe_remove_default_shipping_taxes' ), 500, 2 );
		add_filter( 'oss_shipping_costs_include_taxes', array( $this, 'shipping_costs_include_taxes' ), 10 );
		add_filter( 'woocommerce_cart_tax_totals', array( $this, 'fix_cart_shipping_tax_rounding' ), 100, 2 );

		/**
		 * Tax additional costs based on the main service. This filter is necessary to
		 * make sure that WC_Tax::get_shipping_tax_rates() (during cart recalculation) returns the right shipping rates.
		 */
		add_filter( 'option_woocommerce_shipping_tax_class', array( $this, 'maybe_adjust_default_shipping_tax_class' ), 10 );

		if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_disallow_mixed_carts' ) ) {
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prevent_differential_mixed_carts' ), 10, 3 );
		}

		// Free Shipping auto select
		if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_free_shipping_select' ) ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'free_shipping_auto_select' ), 300 );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_free_shipping_filter' ) );
		}

		// Pay for order
		add_action( 'wp', array( $this, 'force_pay_order_redirect' ), 15 );

		if ( 'yes' === get_option( 'woocommerce_gzd_checkout_disallow_belated_payment_method_selection' ) ) {
			add_filter(
				'woocommerce_get_checkout_payment_url',
				array(
					$this,
					'set_payment_url_to_force_payment',
				),
				10,
				2
			);
		}

		add_action( 'woocommerce_checkout_create_order', array( $this, 'order_meta' ), 5, 1 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'order_store_checkbox_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'order_age_verification' ), 20, 2 );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'add_order_notes' ), 20 );

		// Make sure that, just like in Woo core, the order submit button gets refreshed
		// Use a high priority to let other plugins do their adjustments beforehand
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'refresh_order_submit' ), 150, 1 );

		// Unsure whether this could lead to future problems - tax classes with same name wont be merged anylonger
		// add_filter( 'woocommerce_rate_code', array( $this, 'prevent_tax_name_merge' ), 1000, 2 );

		// Hide cart estimated text if chosen
		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'hide_cart_estimated_text' ) );
		add_action( 'woocommerce_after_cart_totals', array( $this, 'remove_cart_tax_zero_filter' ) );

		// Remove cart subtotal filter
		add_action( 'template_redirect', array( $this, 'maybe_remove_shopmark_filters' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'maybe_remove_shopmark_filters' ) );

		// Hide the newly introduced state field for Germany since Woo 6.3
		add_filter( 'woocommerce_states', array( __CLASS__, 'filter_de_states' ) );

		if ( 'never' !== get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
			// Maybe force street number during checkout
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'maybe_force_street_number' ), 10, 2 );
		}

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_adjust_photovoltaic_cart_data' ), 15 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'refresh_photovoltaic_systems_notice' ), 10, 1 );

		/**
		 * Other services (e.g. virtual, services) are not taxable in northern ireland
		 */
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_remove_northern_ireland_taxes' ), 15 );
	}

	public function maybe_remove_northern_ireland_taxes( $cart ) {
		$tax_location = \Vendidero\EUTaxHelper\Helper::get_taxable_location();

		if ( \Vendidero\EUTaxHelper\Helper::is_northern_ireland( $tax_location[0], $tax_location[2] ) ) {
			foreach ( $cart->get_cart() as $cart_item_key => $values ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

				if ( wc_gzd_get_product( $_product )->is_other_service() ) {
					$_product->set_tax_status( 'none' );
				}
			}
		}
	}

	public function maybe_adjust_default_shipping_tax_class( $value ) {
		if ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() && WC()->cart && did_action( 'woocommerce_before_calculate_totals' ) ) {
			$main_tax_class = wc_gzd_get_cart_main_service_tax_class( 'shipping' );

			return $main_tax_class;
		}

		return $value;
	}

	public function get_checkout_value( $key ) {
		$value = null;

		if ( is_null( $this->checkout_data ) ) {
			/**
			 * Use raw post data in case available as only certain billing/shipping address
			 * specific data is available during AJAX requests in get_posted_data.
			 */
			if ( isset( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$posted = array();
				parse_str( $_POST['post_data'], $posted ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$this->checkout_data = wc_clean( wp_unslash( $posted ) );
			} elseif ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				/**
				 * get_posted_data() does only include core Woo data, no third-party data included.
				 */
				$this->checkout_data = WC()->checkout()->get_posted_data();
			}
		}

		/**
		 * Fallback to customer data (or posted data in case available).
		 */
		if ( null === $value ) {
			$value = WC()->checkout()->get_value( $key );
		}

		/**
		 * If checkout data is available - force overriding
		 */
		if ( $this->checkout_data ) {
			if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$value = isset( $this->checkout_data[ $key ] ) ? $this->checkout_data[ $key ] : WC()->checkout()->get_value( $key );
			} else {
				$value = isset( $this->checkout_data[ $key ] ) ? $this->checkout_data[ $key ] : null;
			}

			/**
			 * Do only allow retrieving shipping-related data in case shipping address is activated
			 */
			if ( 'shipping_' === substr( $key, 0, 9 ) ) {
				if ( ! isset( $this->checkout_data['ship_to_different_address'] ) || ! $this->checkout_data['ship_to_different_address'] || wc_ship_to_billing_address_only() ) {
					$value = null;
				}
			}
		}

		return $value;
	}

	public function refresh_photovoltaic_systems_notice( $fragments ) {
		$fragments['.wc-gzd-photovoltaic-systems-notice'] = '';

		if ( 'yes' === get_option( 'woocommerce_gzd_photovoltaic_systems_checkout_info' ) ) {
			if ( wc_gzd_cart_applies_for_photovoltaic_system_vat_exemption() ) {
				ob_start();
				woocommerce_gzd_template_photovoltaic_systems_checkout_notice();
				$html = ob_get_clean();

				$fragments['.wc-gzd-photovoltaic-systems-notice'] = $html;
			} elseif ( wc_gzd_cart_contains_photovoltaic_system() ) {
				$fragments['.wc-gzd-photovoltaic-systems-notice'] = '<div class="wc-gzd-photovoltaic-systems-notice"></div>';
			}
		}

		return $fragments;
	}

	/**
	 * @param WC_Cart $cart
	 *
	 * @return void
	 */
	public function maybe_adjust_photovoltaic_cart_data( $cart ) {
		if ( ! wc_gzd_base_country_supports_photovoltaic_system_vat_exempt() ) {
			return;
		}

		if ( $checkbox = wc_gzd_get_legal_checkbox( 'photovoltaic_systems' ) ) {
			if ( $checkbox->is_enabled() ) {
				$value   = self::instance()->get_checkout_value( $checkbox->get_html_name() ) ? self::instance()->get_checkout_value( $checkbox->get_html_name() ) : '';
				$visible = ! empty( self::instance()->get_checkout_value( $checkbox->get_html_name() . '-field' ) ) ? true : false;

				if ( $visible && ( ! empty( $value ) || $checkbox->hide_input() ) && wc_gzd_cart_applies_for_photovoltaic_system_vat_exemption() ) {
					foreach ( $cart->get_cart() as $cart_item_key => $values ) {
						$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

						if ( wc_gzd_get_product( $_product )->is_photovoltaic_system() ) {
							if ( wc_prices_include_tax() && 'yes' === get_option( 'woocommerce_gzd_photovoltaic_systems_net_price' ) ) {
								$price         = $_product->get_price();
								$excluding_tax = wc_get_price_excluding_tax(
									$_product,
									array(
										'qty'   => 1,
										'price' => $price,
									)
								);
								$_product->set_price( $excluding_tax );
							}

							$_product->set_tax_class( get_option( 'woocommerce_gzd_photovoltaic_systems_zero_tax_class', 'zero-rate' ) );
						}
					}
				} elseif ( apply_filters( 'woocommerce_gzd_photovoltaic_systems_remove_zero_tax_class_for_non_exemptions', ( ! is_cart() ) ) ) {
					foreach ( $cart->get_cart() as $cart_item_key => $values ) {
						$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

						if ( wc_gzd_get_product( $_product )->is_photovoltaic_system() ) {
							$zero_tax_class = get_option( 'woocommerce_gzd_photovoltaic_systems_zero_tax_class', 'zero-rate' );

							/**
							 * In case the checkbox was not checked but the photovoltaic product has the zero tax class applied
							 * e.g. to show the zero-taxed price within the shop adjust the tax class accordingly.
							 */
							if ( $zero_tax_class === $_product->get_tax_class() ) {
								$_product->set_tax_class( apply_filters( 'woocommerce_gzd_default_photovoltaic_systems_non_exemption_tax_class', '', $_product ) );

								/**
								 * In case prices include tax allow treating the zero taxed default price as net price and add taxes on top
								 * of the current price.
								 */
								if ( wc_prices_include_tax() && apply_filters( 'woocommerce_gzd_photovoltaic_systems_price_excludes_taxes_for_non_exemption', true ) ) {
									$price = $_product->get_price();
									add_filter( 'woocommerce_prices_include_tax', array( $this, 'prevent_prices_include_tax' ), 999 );
									$including_tax = wc_get_price_including_tax(
										$_product,
										array(
											'qty'   => 1,
											'price' => $price,
										)
									);
									remove_filter( 'woocommerce_prices_include_tax', array( $this, 'prevent_prices_include_tax' ), 999 );
									$_product->set_price( $including_tax );
								}
							}
						}
					}
				}
			}
		}
	}

	public function prevent_prices_include_tax() {
		return false;
	}

	public static function filter_de_states( $states ) {
		if ( apply_filters( 'woocommerce_gzd_disable_de_checkout_state_select', ( is_checkout() ) ) && isset( $states['DE'] ) ) {
			$states['DE'] = array();
		}

		return $states;
	}

	/**
	 * @param array     $data
	 * @param WP_Error $errors
	 */
	public function maybe_force_street_number( $data, $errors ) {
		if ( function_exists( 'wc_gzd_split_shipment_street' ) ) {
			$ship_to_different  = isset( $data['ship_to_different_address'] ) ? $data['ship_to_different_address'] : false;
			$shipping_country   = $ship_to_different && isset( $data['shipping_country'] ) ? $data['shipping_country'] : $data['billing_country'];
			$shipping_address_1 = $ship_to_different && isset( $data['shipping_address_1'] ) ? $data['shipping_address_1'] : $data['billing_address_1'];

			if ( ! empty( $shipping_country ) && ! empty( $shipping_address_1 ) && apply_filters( 'woocommerce_gzd_checkout_validate_street_number', true, $data ) ) {
				$countries = array();

				if ( 'always' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$countries = array_keys( WC()->countries->get_allowed_countries() );
				} elseif ( 'base_only' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$countries = array( wc_gzd_get_base_country() );
				} elseif ( 'eu_only' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$countries = WC()->countries->get_european_union_countries();
				}

				$is_shipping_valid = true;
				$field_key         = ( $ship_to_different ? 'shipping' : 'billing' ) . '_address_1';

				if ( in_array( $shipping_country, $countries, true ) ) {
					$shipping_parts    = wc_gzd_split_shipment_street( $shipping_address_1 );
					$is_shipping_valid = empty( $shipping_parts['number'] ) ? false : true;

					/**
					 * In case shipping to another address is chosen make sure to validate the separate billing address as well.
					 */
					if ( true === $ship_to_different && isset( $data['billing_address_1'] ) && apply_filters( 'woocommerce_gzd_checkout_validate_billing_street_number', true ) ) {
						$billing_parts    = wc_gzd_split_shipment_street( $data['billing_address_1'] );
						$is_billing_valid = empty( $billing_parts['number'] ) ? false : true;

						if ( ! apply_filters( 'woocommerce_gzd_checkout_is_valid_billing_street_number', $is_billing_valid, $data ) ) {
							$errors->add( 'billing_address_1_validation', apply_filters( 'woocommerce_gzd_checkout_invalid_billing_street_number_error_message', __( 'Please check the street field and make sure to provide a valid street number.', 'woocommerce-germanized' ), $data ), array( 'id' => 'billing_address_1' ) );
						}
					}
				}

				if ( ! apply_filters( 'woocommerce_gzd_checkout_is_valid_street_number', $is_shipping_valid, $data ) ) {
					$errors->add( $field_key . '_validation', apply_filters( 'woocommerce_gzd_checkout_invalid_street_number_error_message', __( 'Please check the street field and make sure to provide a valid street number.', 'woocommerce-germanized' ), $data ), array( 'id' => $field_key ) );
				}
			}
		}
	}

	/**
	 * Remove cart unit price subtotal filter
	 */
	public function maybe_remove_shopmark_filters() {
		if ( is_cart() || is_checkout() ) {

			foreach ( wc_gzd_get_checkout_shopmarks() as $shopmark ) {
				$shopmark->remove();
			}

			foreach ( wc_gzd_get_cart_shopmarks() as $shopmark ) {
				$shopmark->remove();
			}

			if ( is_cart() ) {
				foreach ( wc_gzd_get_cart_shopmarks() as $shopmark ) {
					$shopmark->execute();
				}
			} elseif ( is_checkout() ) {
				foreach ( wc_gzd_get_checkout_shopmarks() as $shopmark ) {
					$shopmark->execute();
				}
			}
		}
	}

	/**
	 * Prevent tax class merging. Could lead to future problems - not yet implemented
	 *
	 * @param string $code tax class code
	 * @param int $rate_id
	 *
	 * @return string          unique tax class code
	 */
	public function prevent_tax_name_merge( $code, $rate_id ) {
		return $code . '-' . $rate_id;
	}

	/**
	 * Calls a filter to temporarily set cart tax to zero. This is only done to hide the cart tax estimated text.
	 * Filter is being remove right after get_cart_tax - check has been finished within cart-totals.php
	 */
	public function hide_cart_estimated_text() {
		if ( 'yes' === get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) ) {
			add_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
		}
	}

	/**
	 * Removes the zero cart tax filter after get_cart_tax has been finished
	 */
	public function remove_cart_tax_zero_filter() {
		if ( 'yes' === get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) ) {
			remove_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
		}
	}

	/**
	 * This will set the cart tax to zero
	 *
	 * @param float $tax current's cart tax
	 *
	 * @return int
	 */
	public function set_cart_tax_zero( $tax ) {
		return 0;
	}

	/**
	 * @param array $tax_totals
	 * @param WC_Cart $cart
	 *
	 * @return mixed
	 */
	public function set_cart_excluding_tax_labels( $tax_totals, $cart ) {
		if ( ! empty( $tax_totals ) && ! $cart->display_prices_including_tax() && 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
			foreach ( $tax_totals as $key => $tax ) {
				$rate = wc_gzd_get_tax_rate( $tax->tax_rate_id );

				if ( ! $rate ) {
					continue;
				}

				if ( ! empty( $rate ) && isset( $rate->tax_rate ) ) {
					$tax_totals[ $key ]->label = wc_gzd_get_tax_rate_label( $rate->tax_rate, 'excl' );
				}
			}
		}

		return $tax_totals;
	}

	/**
	 * Flag the order as supporting split tax.
	 *
	 * @param WC_Order $order
	 */
	public function order_meta( $order ) {
		if ( wc_gzd_additional_costs_include_tax() ) {
			$order->update_meta_data( '_additional_costs_include_tax', 'yes' );
		}

		if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			$tax_shares = wc_gzd_get_cart_tax_share( 'shipping', $order->get_items() );

			if ( count( $tax_shares ) > 1 ) {
				$order->update_meta_data( '_has_split_tax', 'yes' );
			}
		} elseif ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
			$order->update_meta_data( '_additional_costs_taxed_based_on_main_service', 'yes' );
			$order->update_meta_data( '_additional_costs_taxed_based_on_main_service_by', wc_gzd_additional_costs_taxes_detect_main_service_by() );
			$order->update_meta_data( '_additional_costs_taxed_based_on_main_service_tax_class', wc_gzd_get_cart_main_service_tax_class() );
		}
	}

	public function prevent_differential_mixed_carts( $has_passed, $product_id, $quantity ) {
		if ( $gzd_product = wc_gzd_get_gzd_product( $product_id ) ) {

			$cart_count            = WC()->cart->get_cart_contents_count();
			$contains_differential = wc_gzd_cart_contains_differential_taxed_product();

			if ( $gzd_product->is_differential_taxed() ) {

				if ( $cart_count > 0 && ! $contains_differential ) {
					wc_add_notice( __( 'Sorry, but differential taxed products cannot be purchased with normal products at the same time.', 'woocommerce-germanized' ), 'error' );
					$has_passed = false;
				}
			} else {

				if ( $cart_count > 0 && $contains_differential ) {
					wc_add_notice( __( 'Sorry, but normal products cannot be purchased together with differential taxed products at the same time.', 'woocommerce-germanized' ), 'error' );
					$has_passed = false;
				}
			}
		}

		return $has_passed;
	}

	public function refresh_order_submit( $fragments ) {
		$args = array(
			'include_nonce' => false,
		);

		if ( ! isset( $fragments['.woocommerce-checkout-payment'] ) ) {
			$args['include_nonce'] = true;
		}

		// Get checkout order submit fragment
		ob_start();
		woocommerce_gzd_template_order_submit( $args );
		$wc_gzd_order_submit = ob_get_clean();

		$fragments['.wc-gzd-order-submit'] = $wc_gzd_order_submit;

		return $fragments;
	}

	/**
	 * @param WC_Order $order
	 * @param $posted
	 */
	public function order_store_checkbox_data( $order, $posted ) {
		if ( $checkbox = wc_gzd_get_legal_checkbox( 'parcel_delivery' ) ) {
			if ( $checkbox->is_enabled() && $order->has_shipping_address() && wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( wc_gzd_get_chosen_shipping_rates( array( 'value' => 'id' ) ) ) ) {
				$selected = false;

				if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$selected = true;
				} elseif ( $checkbox->hide_input() ) {
					$selected = true;
				}

				$order->update_meta_data( '_parcel_delivery_opted_in', $selected ? 'yes' : 'no' );

				/**
				 * Parcel delivery notification.
				 *
				 * Execute whenever the parcel delivery notification data is stored for a certain order.
				 *
				 * @param int $order_id The order id.
				 * @param bool $selected True if the checkbox was checked. False otherwise.
				 *
				 * @since 1.7.2
				 */
				do_action( 'woocommerce_gzd_parcel_delivery_order_opted_in', $order->get_id(), $selected );
			}
		}

		if ( $checkbox = wc_gzd_get_legal_checkbox( 'photovoltaic_systems' ) ) {
			if ( $checkbox->is_enabled() && wc_gzd_cart_contains_photovoltaic_system() ) {
				$value   = WC()->checkout()->get_value( $checkbox->get_html_name() );
				$visible = WC()->checkout()->get_value( $checkbox->get_html_name() . '-field' );

				if ( $visible && ( ! empty( $value ) || $checkbox->hide_input() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$order->update_meta_data( '_photovoltaic_systems_opted_in', 'yes' );

					/**
					 * Customer has opted in to the photovoltaic systems' checkbox.
					 *
					 * Execute whenever a customer has opted in to the photovoltaic systems' checkbox.
					 *
					 * @param int $order_id The order id.
					 *
					 * @since 3.12.0
					 */
					do_action( 'woocommerce_gzd_photovoltaic_systems_opted_in', $order->get_id() );
				} else {
					$order->update_meta_data( '_photovoltaic_systems_opted_in', 'no' );
				}
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function add_order_notes( $order ) {
		if ( 'yes' === $order->get_meta( '_parcel_delivery_opted_in' ) ) {
			$order->add_order_note( __( 'The customer has opted-in to the parcel delivery checkbox.', 'woocommerce-germanized' ) );
		}

		if ( 'yes' === $order->get_meta( '_photovoltaic_systems_opted_in' ) ) {
			$order->add_order_note( __( 'This order applies for a photovoltaic systems VAT exemption.', 'woocommerce-germanized' ) );
		}

		if ( $order->get_meta( '_min_age' ) ) {
			$order->add_order_note( sprintf( __( 'A minimum age of %s years is required for this order.', 'woocommerce-germanized' ), wc_gzd_get_order_min_age( $order ) ) );
		}
	}

	/**
	 * @param WC_Order $order
	 * @param $posted
	 */
	public function order_age_verification( $order, $posted ) {
		if ( $checkbox = wc_gzd_get_legal_checkbox( 'age_verification' ) ) {

			if ( ! $checkbox->is_enabled() ) {
				return;
			}

			if ( ! wc_gzd_cart_needs_age_verification( $order->get_items() ) ) {
				return;
			}

			$min_age = wc_gzd_cart_get_age_verification_min_age( $order->get_items() );

			if ( ! $min_age ) {
				return;
			}

			// Checkbox has not been checked
			if ( ! isset( $_POST[ $checkbox->get_html_name() ] ) && ! $checkbox->hide_input() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$order->update_meta_data( '_min_age', $min_age );
		}
	}

	public function set_payment_url_to_force_payment( $url, $order ) {

		/**
		 * Filter to optionally disable forced pay order redirection.
		 * If forced pay order is enabled Germanized auto submits the pay order form with the
		 * previously selected payment method to allow redirecting the customer to the payment provider.
		 *
		 * @param bool $enable Set to `false` to disable forced redirection.
		 * @param WC_Order $order The order instance.
		 *
		 * @since 1.9.10
		 *
		 */
		if ( strpos( $url, 'pay_for_order' ) !== false && apply_filters( 'woocommerce_gzd_enable_force_pay_order', true, $order ) ) {
			$url = esc_url_raw( add_query_arg( array( 'force_pay_order' => true ), $url ) );
		}

		return $url;
	}

	public function force_pay_order_redirect() {
		global $wp;

		if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
			return;
		}

		if ( is_wc_endpoint_url( 'order-pay' ) && isset( $_GET['force_pay_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Manipulate $_POST
			$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id  = absint( $wp->query_vars['order-pay'] );
			$order     = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			if ( $order->get_order_key() !== $order_key ) {
				return;
			}

			// Check if gateway is available - otherwise don't force redirect - would lead to errors in pay_action
			$gateways = WC()->payment_gateways->get_available_payment_gateways();

			if ( ! isset( $gateways[ $order->get_payment_method() ] ) ) {
				return;
			}

			/** This filter is documented in includes/class-wc-gzd-checkout.php */
			if ( apply_filters( 'woocommerce_gzd_enable_force_pay_order', true, $order ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_force_pay_script' ), 20 );
				add_action( 'woocommerce_after_pay_action', array( $this, 'maybe_disable_force_pay_script' ), 20 );

				if ( ! defined( 'WC_GZD_FORCE_PAY_ORDER' ) ) {
					define( 'WC_GZD_FORCE_PAY_ORDER', true );
				}
			}
		}
	}

	public function maybe_disable_force_pay_script() {
		// Make sure we are not retrying to redirect if an error ocurred
		if ( wc_notice_count( 'error' ) > 0 ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( 'force_pay_order' ) ) );
			exit;
		}
	}

	public function enqueue_force_pay_script() {
		wp_enqueue_script( 'wc-gzd-force-pay-order' );
	}

	public function set_free_shipping_filter( $cart ) {
		self::$force_free_shipping_filter = true;
	}

	public function free_shipping_auto_select( $rates ) {
		$do_check = is_checkout() || is_cart() || self::$force_free_shipping_filter;

		if ( ! $do_check ) {
			return $rates;
		}

		$keep     = array();
		$hide     = false;
		$excluded = get_option( 'woocommerce_gzd_display_checkout_free_shipping_excluded', array() );

		// Check for cost-free shipping
		foreach ( $rates as $key => $rate ) {
			if ( is_a( $rate, 'WC_Shipping_Rate' ) ) {
				/**
				 * Filter to exclude certain shipping rates from being hidden as soon as free shipping option
				 * is available.
				 *
				 * @param bool $is_excluded Whether the rate is excluded or not.
				 * @param string $instance The shipping rate instance.
				 * @param WC_Shipping_Rate $rate The shipping rate.
				 *
				 * @since 3.0.0
				 *
				 */
				$is_excluded = apply_filters( 'woocommerce_gzd_exclude_from_force_free_shipping', false, $key, $rate );

				if ( 'free_shipping' === $rate->method_id ) {
					$keep[] = $key;
					$hide   = true;
				} elseif ( 'local_pickup' === $rate->method_id ) {
					$keep[] = $key;
				} elseif ( 0.0 === floatval( $rate->cost ) ) {
					$keep[] = $key;
				} elseif ( in_array( $key, $excluded ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$keep[] = $key;
				} elseif ( $is_excluded ) {
					$keep[] = $key;
				}
			}
		}

		// Unset all other rates
		if ( ! empty( $keep ) && $hide ) {
			$chosen_shipping_methods = array();

			// Unset chosen shipping method to avoid key errors
			if ( isset( WC()->session ) && ! is_null( WC()->session ) ) {
				$chosen_shipping_methods = (array) WC()->session->get( 'chosen_shipping_methods' );
			}

			foreach ( $rates as $key => $rate ) {
				if ( ! in_array( $key, $keep ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					unset( $rates[ $key ] );
				}
			}

			foreach ( $chosen_shipping_methods as $key => $rate ) {
				if ( ! in_array( $rate, $keep ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					unset( $chosen_shipping_methods[ $key ] );
				}
			}

			if ( isset( WC()->session ) && ! is_null( WC()->session ) ) {
				WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
			}
		}

		return $rates;
	}

	public function add_payment_link( $order_id ) {
		if ( is_a( $order_id, 'WC_Order' ) ) {
			$order = $order_id;
		} else {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		$enabled = true;

		if ( get_option( 'woocommerce_gzd_order_pay_now_button' ) === 'no' ) {
			$enabled = false;
		}

		if ( ! $order->needs_payment() ) {
			$enabled = false;
		}

		$disabled_methods = get_option( 'woocommerce_gzd_order_pay_now_button_disabled_methods', array() );

		if ( is_array( $disabled_methods ) && in_array( $order->get_payment_method(), $disabled_methods, true ) ) {
			$enabled = false;
		}

		/**
		 * Filters whether to show the pay now button for a certain order.
		 *
		 * ```php
		 * function ex_show_order_button( $show, $order_id ) {
		 *      if ( $order = wc_get_order( $order_id ) {
		 *          // Check the order and decide whether to enable or disable button
		 *          return false;
		 *      }
		 *
		 *      return $show;
		 * }
		 * add_filter( 'woocommerce_gzd_show_order_pay_now_button', 'ex_show_order_button', 10, 2 );
		 * ```
		 *
		 * @param bool $enabled Whether to enable the button or not.
		 * @param int $order_id The order id.
		 *
		 * @since 1.9.10
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_show_order_pay_now_button', $enabled, $order_id ) ) {
			$url = $order->get_checkout_payment_url();

			/**
			 * Filter whether to add the `force_pay_order` parameter to the URL to allow
			 * automatically redirecting the customer to the chosen payment provider after
			 * clicking the link.
			 *
			 * @param bool $enable Set to `false` to disable.
			 * @param int $order_id The order id.
			 *
			 * @since 1.9.10
			 *
			 */
			if ( apply_filters( 'woocommerce_gzd_add_force_pay_order_parameter', true, $order_id ) ) {
				$url = esc_url_raw( add_query_arg( array( 'force_pay_order' => true ), $url ) );
			}

			wc_get_template(
				'order/order-pay-now-button.php',
				array(
					'url'      => $url,
					'order_id' => $order_id,
				)
			);
		}
	}

	public function init_fields() {
		if ( WC_GZD_Customer_Helper::instance()->is_customer_title_enabled() ) {
			$this->custom_fields['title'] = array(
				'type'     => 'select',
				'required' => false,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
				'options'  => wc_gzd_get_customer_title_options(),
				'default'  => 0,
				'before'   => 'first_name',
				'group'    => array( 'billing', 'shipping' ),
				'priority' => 0,
			);

			$this->custom_fields_admin['title'] = array(
				'before'   => 'first_name',
				'type'     => 'select',
				'options'  => wc_gzd_get_customer_title_options(),
				'default'  => 0,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
				'show'     => false,
				'priority' => 0,
			);
		}

		/**
		 * Filter to adjust custom checkout-related admin fields.
		 *
		 * This filter may be used to output certain checkout fields within admin order screen.
		 *
		 * @param array $custom_fields Array of fields.
		 * @param WC_GZD_Checkout $checkout The checkout instance.
		 *
		 * @since 1.0.0
		 *
		 */
		$this->custom_fields_admin = apply_filters( 'woocommerce_gzd_custom_checkout_admin_fields', $this->custom_fields_admin, $this );

		/**
		 * Filter to adjust custom checkout-related frontend fields.
		 *
		 * This filter may be used to output certain checkout fields within the checkout.
		 *
		 * @param array $custom_fields Array of fields.
		 * @param WC_GZD_Checkout $checkout The checkout instance.
		 *
		 * @since 1.0.0
		 *
		 */
		$this->custom_fields = apply_filters( 'woocommerce_gzd_custom_checkout_fields', $this->custom_fields, $this );
	}

	public function set_title_field_mapping_editors( $val ) {
		$titles = array_flip( wc_gzd_get_customer_title_options() );
		$values = $titles;

		if ( isset( $values[ $val ] ) ) {
			return $values[ $val ];
		}

		return $val;
	}

	protected function remove_fee_taxes( $cart ) {
		$fees = $cart->get_fees();

		if ( ! empty( $fees ) ) {
			$new_fees = array();

			foreach ( $fees as $key => $fee ) {

				if ( $fee->taxable ) {
					$fee->taxable  = false;
					$fee->total    = wc_format_decimal( $fee->amount + $fee->tax, '' );
					$fee->amount   = $fee->total;
					$fee->tax      = 0;
					$fee->tax_data = array();
				}

				$new_fees[ $key ] = $fee;
			}

			$cart->fees_api()->set_fees( $new_fees );
			$cart->set_fee_tax( 0 );
			$cart->set_fee_taxes( array() );

			$fee_total = array_sum( wp_list_pluck( $new_fees, 'total' ) );

			$cart->set_fee_total( wc_format_decimal( $fee_total, '' ) );
		}
	}

	/**
	 * This filter is important to get the right (rounded) per tax rate tax amounts.
	 *
	 * By default Woo does round shipping taxes differently as shipping costs
	 * are treated as net prices. Germanized does treat shipping costs as gross in
	 * case prices include tax.
	 *
	 * @param $taxes
	 * @param WC_Cart $cart
	 */
	public function fix_cart_shipping_tax_rounding( $taxes, $cart ) {
		if ( ! wc_gzd_additional_costs_include_tax() ) {
			return $taxes;
		}

		// Remove the current filter before calling get_tax_totals to prevent infinite loops
		remove_filter( 'woocommerce_cart_tax_totals', array( $this, 'fix_cart_shipping_tax_rounding' ), 100 );
		// Remove shipping taxes to prevent different rounding for them within WC_Cart::get_tax_totals
		add_filter( 'woocommerce_cart_get_shipping_taxes', array( $this, 'remove_shipping_taxes' ), 10 );
		// Make sure that total taxes still include shipping taxes
		add_filter( 'woocommerce_cart_get_taxes', array( $this, 'maybe_remove_shipping_tax_filter' ), 10, 2 );

		$taxes = $cart->get_tax_totals();

		// Remove cart tax filter
		remove_filter( 'woocommerce_cart_get_taxes', array( $this, 'maybe_remove_shipping_tax_filter' ), 10 );
		// Remove shipping tax filter
		remove_filter( 'woocommerce_cart_get_shipping_taxes', array( $this, 'remove_shipping_taxes' ), 10 );
		// Re add the filter
		add_filter( 'woocommerce_cart_tax_totals', array( $this, 'fix_cart_shipping_tax_rounding' ), 100, 2 );

		return $taxes;
	}

	/**
	 * @param $taxes
	 * @param WC_Cart $cart
	 */
	public function maybe_remove_shipping_tax_filter( $taxes, $cart ) {
		remove_filter( 'woocommerce_cart_get_shipping_taxes', array( $this, 'remove_shipping_taxes' ), 10 );
		remove_filter( 'woocommerce_cart_get_taxes', array( $this, 'maybe_remove_shipping_tax_filter' ), 10 );

		return $cart->get_taxes();
	}

	public function remove_shipping_taxes( $taxes ) {
		return array();
	}

	/**
	 * @param $args
	 * @param WC_Shipping_Method $method
	 *
	 * @return mixed
	 */
	public function maybe_remove_default_shipping_taxes( $args, $method ) {
		/**
		 * Prevent shipping methods from individually calculating taxes (e.g. as per custom incl/excl tax settings)
		 * as Germanized handles tax calculation globally for all shipping methods.
		 */
		if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			if ( ! empty( $args['taxes'] ) && apply_filters( 'woocommerce_gzd_disable_custom_shipping_method_tax_calculation', true, $method ) ) {
				$args['cost']  = $args['cost'] + array_sum( $args['taxes'] );
				$args['taxes'] = '';
			}
		}

		return $args;
	}

	/**
	 * Tell the OSS package that shipping costs include tax for improved compatibility.
	 *
	 * @return bool
	 */
	public function shipping_costs_include_taxes() {
		return wc_gzd_additional_costs_include_tax();
	}

	/**
	 * @param WC_Shipping_Rate[] $rates
	 * @param $package
	 *
	 * @return mixed
	 */
	public function adjust_shipping_taxes( $rates, $package ) {
		if ( wc_gzd_enable_additional_costs_split_tax_calculation() || wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
			foreach ( $rates as $key => $rate ) {
				$original_taxes = $rate->get_taxes();
				$original_cost  = $rate->get_cost();

				/**
				 * Prevent bugs in plugins like Woo Subscriptions which
				 * apply the woocommerce_package_rates filter twice (which might lead to costs being reduced twice).
				 *
				 * Store the original shipping costs (before removing tax) within the object.
				 */
				if ( isset( $rate->original_cost ) ) {
					$original_cost = $rate->original_cost;
				} else {
					$rate->original_cost = $original_cost;
				}

				if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
					$tax_shares = apply_filters( 'woocommerce_gzd_shipping_tax_shares', wc_gzd_get_cart_tax_share( 'shipping' ), $rate );

					/**
					 * Reset split tax data
					 */
					$rates[ $key ]->add_meta_data( '_split_taxes', array() );
					$rates[ $key ]->add_meta_data( '_tax_shares', array() );

					/**
					 * Calculate split taxes if the cart contains more than one tax rate.
					 * Tax rounding (e.g. for subtotal) is handled by WC_Cart_Totals::get_shipping_from_cart
					 */
					if ( apply_filters( 'woocommerce_gzd_force_additional_costs_taxation', true ) ) {
						if ( $rate->get_shipping_tax() > 0 ) {
							if ( ! empty( $tax_shares ) ) {
								$taxes           = array();
								$taxable_amounts = array();

								foreach ( $tax_shares as $tax_class => $class ) {
									$tax_rates       = WC_Tax::get_rates( $tax_class );
									$taxable_amount  = $original_cost * $class['share'];
									$tax_class_taxes = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
									$net_base        = wc_gzd_additional_costs_include_tax() ? ( $taxable_amount - array_sum( $tax_class_taxes ) ) : $taxable_amount;

									$taxable_amounts[ $tax_class ] = array(
										'taxable_amount' => $taxable_amount,
										'tax_share'      => $class['share'],
										'tax_rates'      => array_keys( $tax_rates ),
										'net_amount'     => $net_base,
										'includes_tax'   => wc_gzd_additional_costs_include_tax(),
									);

									$taxes = $taxes + $tax_class_taxes;
								}

								$rates[ $key ]->set_taxes( $taxes );
								$rates[ $key ]->add_meta_data( '_split_taxes', $taxable_amounts );
								$rates[ $key ]->add_meta_data( '_tax_shares', $tax_shares );
							} elseif ( 0 === WC()->cart->get_total_tax() ) {
								$rates[ $key ]->set_taxes( array() );
							} else {
								$original_tax_rates = array_keys( $original_taxes );

								if ( ! empty( $original_tax_rates ) ) {
									$tax_rates = WC_Tax::get_shipping_tax_rates();

									if ( ! empty( $tax_rates ) ) {
										$taxes = WC_Tax::calc_tax( $original_cost, $tax_rates, wc_gzd_additional_costs_include_tax() );
										$rates[ $key ]->set_taxes( $taxes );
									}
								}
							}
						}
					}
				} elseif ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
					$main_tax_class = wc_gzd_get_cart_main_service_tax_class( 'shipping' );

					if ( $rate->get_shipping_tax() > 0 ) {
						if ( false !== $main_tax_class ) {
							$tax_rates      = WC_Tax::get_rates( $main_tax_class );
							$taxable_amount = $original_cost;
							$taxes          = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );

							$rates[ $key ]->set_taxes( $taxes );
						} elseif ( 0 === WC()->cart->get_total_tax() ) {
							$rates[ $key ]->set_taxes( array() );
						} else {
							$original_tax_rates = array_keys( $original_taxes );

							if ( ! empty( $original_tax_rates ) ) {
								$tax_rates = WC_Tax::get_shipping_tax_rates();

								if ( ! empty( $tax_rates ) ) {
									$taxes = WC_Tax::calc_tax( $original_cost, $tax_rates, wc_gzd_additional_costs_include_tax() );
									$rates[ $key ]->set_taxes( $taxes );
								}
							}
						}
					}
				}

				/**
				 * Convert shipping costs to gross prices in case prices include tax
				 */
				if ( wc_gzd_additional_costs_include_tax() ) {
					$tax_total = array_sum( $rates[ $key ]->get_taxes() );
					$new_cost  = $original_cost - $tax_total;

					if ( WC()->customer->is_vat_exempt() ) {
						$shipping_rates = WC_Tax::get_shipping_tax_rates();
						$shipping_taxes = WC_Tax::calc_inclusive_tax( $original_cost, $shipping_rates );
						$new_cost       = ( $new_cost - array_sum( $shipping_taxes ) );
					}

					$rates[ $key ]->set_cost( $new_cost );
				}
			}
		}

		if ( ! wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			foreach ( $rates as $key => $rate ) {
				$meta_data = $rates[ $key ]->get_meta_data();

				/**
				 * Reset meta data in case it exists
				 */
				if ( array_key_exists( '_split_taxes', $meta_data ) ) {
					$rates[ $key ]->add_meta_data( '_split_taxes', array() );
					$rates[ $key ]->add_meta_data( '_tax_shares', array() );
				}
			}
		}

		return $rates;
	}

	/**
	 * @param $fee_taxes
	 * @param $fee
	 * @param WC_Cart_Totals $cart_totals
	 */
	public function adjust_fee_taxes( $fee_taxes, $fee, $cart_totals ) {
		if ( ! wc_gzd_enable_additional_costs_split_tax_calculation() && ! wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
			return $fee_taxes;
		}

		if ( ! wc_tax_enabled() ) {
			return $fee_taxes;
		}

		// In case the fee is not marked as taxable - allow skipping via filter
		if ( ! $fee->taxable && ! apply_filters( 'woocommerce_gzd_force_fee_tax_calculation', true, $fee ) ) {
			return $fee_taxes;
		}

		// Do not calculate tax shares if tax calculation is disabled
		if ( apply_filters( 'woocommerce_gzd_skip_fee_split_tax_calculation', false, $fee ) ) {
			return $fee_taxes;
		}

		$disable_fee_taxes = WC()->customer->is_vat_exempt();

		if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			$tax_shares = apply_filters( 'woocommerce_gzd_fee_tax_shares', wc_gzd_get_cart_tax_share( 'fee' ), $fee );

			// Reset
			$fee->split_tax  = array();
			$fee->tax_shares = array();

			$disable_fee_taxes = $disable_fee_taxes || empty( $tax_shares );

			// Calculate tax class share
			if ( ! $disable_fee_taxes ) {
				$fee_taxes       = array();
				$taxable_amounts = array();

				foreach ( $tax_shares as $tax_class => $class ) {
					$tax_rates       = WC_Tax::get_rates( $tax_class );
					$taxable_amount  = $fee->total * $class['share'];
					$tax_class_taxes = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
					$net_base        = wc_gzd_additional_costs_include_tax() ? ( $taxable_amount - array_sum( $tax_class_taxes ) ) : $taxable_amount;

					$taxable_amounts[ $tax_class ] = array(
						'taxable_amount' => $taxable_amount,
						'tax_share'      => $class['share'],
						'tax_rates'      => array_keys( $tax_rates ),
						'net_amount'     => $net_base,
						'includes_tax'   => wc_gzd_additional_costs_include_tax(),
					);

					$fee_taxes = $fee_taxes + $tax_class_taxes;
				}

				$total_tax = array_sum( array_map( array( $this, 'round_line_tax_in_cents' ), $fee_taxes ) );

				if ( wc_gzd_additional_costs_include_tax() ) {
					$fee->total = $fee->total - $total_tax;
				}

				$fee->split_taxes = $taxable_amounts;
				$fee->tax_shares  = $tax_shares;
			}
		} elseif ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
			$main_tax_class    = wc_gzd_get_cart_main_service_tax_class( 'fee' );
			$disable_fee_taxes = $disable_fee_taxes || false === $main_tax_class;

			if ( ! $disable_fee_taxes ) {
				$tax_rates      = WC_Tax::get_rates( $main_tax_class );
				$taxable_amount = $fee->total;
				$fee_taxes      = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
				$total_tax      = array_sum( array_map( array( $this, 'round_line_tax_in_cents' ), $fee_taxes ) );
				$fee->tax_class = $main_tax_class;

				if ( isset( $fee->object ) ) {
					$fee->object->tax_class = $fee->tax_class;
				}

				if ( wc_gzd_additional_costs_include_tax() ) {
					$fee->total = $fee->total - $total_tax;
				}
			}
		}

		/**
		 * Do not calculate fee taxes if tax shares are empty (e.g. zero-taxes only).
		 * In this case, remove fee taxes altogether.
		 */
		if ( $disable_fee_taxes ) {
			if ( apply_filters( 'woocommerce_gzd_fee_costs_include_tax', wc_gzd_additional_costs_include_tax(), $fee ) ) {
				$total_tax  = array_sum( array_map( array( $this, 'round_line_tax_in_cents' ), $fee_taxes ) );
				$fee->total = $fee->total - $total_tax;

				/**
				 * In case the customer is a VAT exempt - use customer's tax rates
				 * to find the fee net price.
				 */
				if ( WC()->customer->is_vat_exempt() ) {
					$tax_class_default = '';

					if ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
						$tax_class_default = wc_gzd_get_cart_main_service_tax_class( 'fee' );

						if ( false === $tax_class_default ) {
							$tax_class_default = '';
						}
					}

					$fee_rates = WC_Tax::get_rates( $tax_class_default );
					$fee_taxes = WC_Tax::calc_inclusive_tax( $fee->total, $fee_rates );

					$fee->total = $fee->total - array_sum( $fee_taxes );
				}
			}

			return array();
		}

		return $fee_taxes;
	}

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * @since  3.2.6
	 * @param  float $value    Tax value.
	 * @param  bool  $in_cents Whether precision of value is in cents.
	 * @return float
	 */
	public function round_line_tax( $value, $in_cents = false ) {
		if ( 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
			$value = wc_round_tax_total( $value, $in_cents ? 0 : null );
		}

		return $value;
	}

	public function round_line_tax_in_cents( $value ) {
		return $this->round_line_tax( $value, true );
	}

	/**
	 * Temporarily removes all shipping rates (except chosen one) from packages to only show chosen package within checkout.
	 */
	public function remove_shipping_rates() {
		if ( 'no' === get_option( 'woocommerce_gzd_display_checkout_shipping_rate_select' ) ) {
			return;
		}

		$packages = WC()->shipping->get_packages();

		foreach ( $packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			if ( ! empty( $package['rates'] ) ) {
				foreach ( $package['rates'] as $key => $rate ) {
					if ( $key !== $chosen_method ) {
						unset( WC()->shipping->packages[ $i ]['rates'][ $key ] );
					}
				}
			}
		}
	}

	/**
	 * @param WC_Order_Item_Product $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function set_order_item_meta_crud( $item, $cart_item_key, $values, $order ) {
		WC_GZD_Order_Helper::instance()->refresh_item_data( $item );
	}

	public function set_formatted_address( $placeholder, $args ) {
		if ( ! WC_GZD_Customer_Helper::instance()->is_customer_title_enabled() ) {
			return $placeholder;
		}

		if ( isset( $args['title'] ) ) {
			if ( '' !== $args['title'] ) {
				$title = wc_gzd_get_customer_title( $args['title'] );

				/**
				 * Ugly hack to force accusative in addresses
				 */
				if ( __( 'Mr.', 'woocommerce-germanized' ) === $title ) {
					$title = _x( 'Mr.', 'customer-title-male-address', 'woocommerce-germanized' );
				}

				$args['title'] = $title;
			}

			$placeholder['{title}']       = $args['title'];
			$placeholder['{title_upper}'] = strtoupper( $args['title'] );

			if ( strpos( $placeholder['{name}'], '{title}' ) === false ) {
				$placeholder['{name}']       = $placeholder['{title}'] . ' ' . $placeholder['{name}'];
				$placeholder['{name_upper}'] = $placeholder['{title_upper}'] . ' ' . $placeholder['{name_upper}'];
			}
		}

		return $placeholder;
	}

	public function set_custom_fields( $fields = array(), $type = 'billing' ) {
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				$new = array();
				if ( in_array( $type, $custom_field['group'], true ) ) {
					if ( ! empty( $fields ) ) {
						foreach ( $fields as $name => $field ) {
							if ( $name === $type . '_' . $custom_field['before'] && ! isset( $custom_field['override'] ) ) {
								$new[ $type . '_' . $key ] = $custom_field;
							}

							$new[ $name ] = $field;

							if ( $name === $type . '_' . $key && isset( $custom_field['override'] ) ) {
								$new[ $name ] = array_merge( $field, $custom_field );
							}
						}
					}
				}

				if ( ! empty( $new ) ) {
					$fields = $new;
				}
			}
		}

		return $fields;
	}

	public function set_custom_fields_shipping( $fields ) {
		return $this->set_custom_fields( $fields, 'shipping' );
	}

	public function set_custom_fields_admin( $fields = array(), $type = 'billing' ) {
		$new = array();

		if ( ! empty( $this->custom_fields_admin ) ) {
			foreach ( $this->custom_fields_admin as $key => $custom_field ) {
				$new = array();

				if ( isset( $custom_field['address_type'] ) && $custom_field['address_type'] !== $type ) {
					continue;
				}

				if ( ! empty( $fields ) ) {
					foreach ( $fields as $name => $field ) {
						if ( $name === $custom_field['before'] && ! isset( $custom_field['override'] ) ) {
							$new[ $key ] = $custom_field;
						}
						$new[ $name ] = $field;
					}
				}

				if ( ! empty( $new ) ) {
					$fields = $new;
				}
			}
		}

		return $fields;
	}

	public function set_custom_fields_admin_billing( $fields = array() ) {
		return $this->set_custom_fields_admin( $fields, 'billing' );
	}

	public function set_custom_fields_admin_shipping( $fields = array() ) {
		return $this->set_custom_fields_admin( $fields, 'shipping' );
	}

	/**
	 * @param WC_Order $order
	 */
	public function save_fields( $order ) {
		$checkout = WC()->checkout();
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				if ( ! empty( $custom_field['group'] ) && ! isset( $custom_field['override'] ) ) {
					foreach ( $custom_field['group'] as $group ) {
						$val = $checkout->get_posted_address_data( $key, $group );

						if ( ! empty( $val ) ) {

							/**
							 * Filter the value for a custom checkout field before saving.
							 * `$key` corresponds to the field id e.g. title.
							 *
							 * @param mixed $value The field value.
							 *
							 * @since 1.0.0
							 *
							 */
							$order->update_meta_data( '_' . $group . '_' . $key, apply_filters( 'woocommerce_gzd_custom_' . $key . '_field_value', wc_clean( $val ) ) );
						}
					}
				}
			}
		}
	}
}

WC_GZD_Checkout::instance();
