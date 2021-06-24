<?php

class WC_GZD_Checkout {

	public $custom_fields = array();
	public $custom_fields_admin = array();

	protected static $force_free_shipping_filter = false;

	protected static $_instance = null;

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
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
		add_filter( 'woocommerce_gzd_custom_title_field_value', array(
			$this,
			'set_title_field_mapping_editors'
		), 10, 1 );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'set_order_item_meta_crud' ), 0, 4 );

		// Deactivate checkout shipping selection
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'remove_shipping_rates' ), 0 );

		/**
		 * Split tax calculation for fees and shipping
		 */
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( $this, 'adjust_fee_taxes' ), 100, 3 );
		add_filter( 'woocommerce_package_rates', array( $this, 'adjust_shipping_taxes' ), 100, 2 );
		add_filter( 'woocommerce_cart_tax_totals', array( $this, 'fix_cart_shipping_tax_rounding' ), 100, 2 );

		if ( 'yes' === get_option( 'woocommerce_gzd_differential_taxation_disallow_mixed_carts' ) ) {
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prevent_differential_mixed_carts' ), 10, 3 );
		}

		// Free Shipping auto select
		if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_free_shipping_select' ) ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'free_shipping_auto_select' ) );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_free_shipping_filter' ) );
		}

		// Pay for order
		add_action( 'wp', array( $this, 'force_pay_order_redirect' ), 15 );

		if ( 'yes' === get_option( 'woocommerce_gzd_checkout_disallow_belated_payment_method_selection' ) ) {
			add_filter( 'woocommerce_get_checkout_payment_url', array(
				$this,
				'set_payment_url_to_force_payment'
			), 10, 2 );
		}

		add_action( 'woocommerce_checkout_create_order', array( $this, 'order_meta' ), 5, 1 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'order_parcel_delivery_data_transfer' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'order_age_verification' ), 20, 2 );

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

		if ( 'never' !== get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
			// Maybe force street number during checkout
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'maybe_force_street_number' ), 10, 2 );
		}
	}

	/**
	 * @param array     $data
	 * @param WP_Error $errors
	 */
	public function maybe_force_street_number( $data, $errors ) {
		if ( function_exists( 'wc_gzd_split_shipment_street' ) ) {
			if ( isset( $data['shipping_country'], $data['shipping_address_1'] ) && ! empty( $data['shipping_country'] ) ) {
				$countries = array();

				if ( 'always' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$countries = WC()->countries->get_allowed_countries();
				} elseif( 'base_only' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$countries = array( WC()->countries->get_base_country() );
				} elseif( 'eu_only' === get_option( 'woocommerce_gzd_checkout_validate_street_number' ) ) {
					$countries = WC()->countries->get_european_union_countries();
				}

				$is_valid          = true;
				$ship_to_different = isset( $data['ship_to_different_address'] ) ? $data['ship_to_different_address'] : false;
				$key               = ( $ship_to_different ? 'shipping' : 'billing' ) . '_address_1';

				// Force street number
				if ( in_array( $data['shipping_country'], $countries ) ) {
					$parts    = wc_gzd_split_shipment_street( $data['shipping_address_1'] );
					$is_valid = empty( $parts['number'] ) ? false : true;
				}

				if ( ! apply_filters( 'woocommerce_gzd_checkout_is_valid_street_number', $is_valid, $data ) ) {
					$errors->add( $key, apply_filters( 'woocommerce_gzd_checkout_invalid_street_number_error_message', __( 'Please check the street field and make sure to provide a valid street number.', 'woocommerce-germanized' ), $data ), array( 'id' => $key ) );
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
		if ( get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) == 'yes' ) {
			add_filter( 'woocommerce_get_cart_tax', array( $this, 'set_cart_tax_zero' ) );
		}
	}

	/**
	 * Removes the zero cart tax filter after get_cart_tax has been finished
	 */
	public function remove_cart_tax_zero_filter() {
		if ( get_option( 'woocommerce_gzd_display_hide_cart_tax_estimated' ) == 'yes' ) {
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
			foreach( $tax_totals as $key => $tax ) {
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
		if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			$tax_shares = wc_gzd_get_cart_tax_share( 'shipping', $order->get_items() );

			if ( sizeof( $tax_shares ) > 1 ) {
				$order->update_meta_data( '_has_split_tax', 'yes' );
			}

			$order->update_meta_data( '_additional_costs_include_tax', wc_bool_to_string( wc_gzd_additional_costs_include_tax() ) );
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
	public function order_parcel_delivery_data_transfer( $order, $posted ) {
		if ( $checkbox = wc_gzd_get_legal_checkbox( 'parcel_delivery' ) ) {

			if ( ! $checkbox->is_enabled() ) {
				return;
			}

			if ( ! wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( wc_gzd_get_chosen_shipping_rates( array( 'value' => 'id' ) ) ) ) {
				return;
			}

			$selected = false;

			if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) {
				$selected = true;
			} elseif( $checkbox->hide_input() ) {
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
			 *
			 */
			do_action( 'woocommerce_gzd_parcel_delivery_order_opted_in', $order->get_id(), $selected );
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
			if ( ! isset( $_POST[ $checkbox->get_html_name() ] ) && ! $checkbox->hide_input() ) {
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
			$url = add_query_arg( array( 'force_pay_order' => true ), $url );
		}

		return $url;
	}

	public function force_pay_order_redirect() {
		global $wp;

		if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
			return;
		}

		if ( is_wc_endpoint_url( 'order-pay' ) && isset( $_GET['force_pay_order'] ) ) {

			// Manipulate $_POST
			$order_key = $_GET['key'];
			$order_id  = absint( $wp->query_vars['order-pay'] );
			$order     = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			if ( $order->get_order_key() != $order_key ) {
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
			}
		}
	}

	public function maybe_disable_force_pay_script() {
		// Make sure we are not retrying to redirect if an error ocurred
		if ( wc_notice_count( 'error' ) > 0 ) {
			wp_safe_redirect( remove_query_arg( 'force_pay_order' ) );
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
				} elseif ( $rate->cost == 0 ) {
					$keep[] = $key;
				} elseif ( in_array( $key, $excluded ) ) {
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
				if ( ! in_array( $key, $keep ) ) {
					unset( $rates[ $key ] );
				}
			}

			foreach( $chosen_shipping_methods as $key => $rate ) {
				if ( ! in_array( $rate, $keep ) ) {
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

		if ( is_array( $disabled_methods ) && in_array( $order->get_payment_method(), $disabled_methods ) ) {
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
				$url = add_query_arg( array( 'force_pay_order' => true ), $url );
			}

			wc_get_template( 'order/order-pay-now-button.php', array( 'url' => $url, 'order_id' => $order_id ) );
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

			foreach( $fees as $key => $fee ) {

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
	 * @param WC_Shipping_Rate[] $rates
	 * @param $package
	 *
	 * @return mixed
	 */
	public function adjust_shipping_taxes( $rates, $package ) {

		if ( ! wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			foreach( $rates as $key => $rate ) {
				/**
				 * Reset split tax data
				 */
				$rates[ $key ]->add_meta_data( '_split_taxes', array() );
			}

			return $rates;
		}

		foreach( $rates as $key => $rate ) {
			$original_taxes = $rate->get_taxes();
			$original_cost  = $rate->get_cost();
			$tax_shares     = wc_gzd_get_cart_tax_share( 'shipping' );

			/**
			 * Reset split tax data
			 */
			$rates[ $key ]->add_meta_data( '_split_taxes', array() );

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
								'includes_tax'   => wc_gzd_additional_costs_include_tax()
							);

							$taxes = $taxes + $tax_class_taxes;
						}

						$rates[ $key ]->set_taxes( $taxes );
						$rates[ $key ]->add_meta_data( '_split_taxes', $taxable_amounts );
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

		return $rates;
	}

	/**
	 * @param $fee_taxes
	 * @param $fee
	 * @param WC_Cart_Totals $cart_totals
	 */
	public function adjust_fee_taxes( $fee_taxes, $fee, $cart_totals ) {

		if ( ! wc_gzd_enable_additional_costs_split_tax_calculation() ) {
			return $fee_taxes;
		}

		$calculate_taxes = wc_tax_enabled();

		// Do not calculate tax shares if tax calculation is disabled
		if ( ! $calculate_taxes ) {
			return $fee_taxes;
		}

		// In case the fee is not marked as taxable - allow skipping via filter
		if ( ! $fee->taxable && ! apply_filters( 'woocommerce_gzd_force_fee_tax_calculation', true, $fee ) ) {
			return $fee_taxes;
		}

		$tax_shares = wc_gzd_get_cart_tax_share( 'fee' );

		// Reset
		$fee->split_tax = array();

		/**
		 * Do not calculate fee taxes if tax shares are empty (e.g. zero-taxes only).
		 * In this case, remove fee taxes altogether.
		 */
		if ( empty( $tax_shares ) || WC()->customer->is_vat_exempt() ) {
			if ( wc_gzd_additional_costs_include_tax() ) {
				$total_tax  = array_sum( array_map( array( $this, 'round_line_tax_in_cents' ), $fee_taxes ) );
				$fee->total = $fee->total - $total_tax;

				/**
				 * In case the customer is a VAT exempt - use customer's tax rates
				 * to find the fee net price.
				 */
				if ( WC()->customer->is_vat_exempt() ) {
					$fee_rates  = WC_Tax::get_rates( '' );
					$fee_taxes  = WC_Tax::calc_inclusive_tax( $fee->total, $fee_rates );

					$fee->total = $fee->total - array_sum( $fee_taxes );
				}
			}

			return array();
		}

		// Calculate tax class share
		if ( ! empty( $tax_shares ) ) {
			$fee_taxes       = array();
			$taxable_amounts = array();

			foreach ( $tax_shares as $tax_class => $class ) {
				$tax_rates      = WC_Tax::get_rates( $tax_class );
				$taxable_amount = $fee->total * $class['share'];
				$tax_class_taxes = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
				$net_base        = wc_gzd_additional_costs_include_tax() ? ( $taxable_amount - array_sum( $tax_class_taxes ) ) : $taxable_amount;

				$taxable_amounts[ $tax_class ] = array(
					'taxable_amount' => $taxable_amount,
					'tax_share'      => $class['share'],
					'tax_rates'      => array_keys( $tax_rates ),
					'net_amount'     => $net_base,
					'includes_tax'   => wc_gzd_additional_costs_include_tax()
				);

				$fee_taxes = $fee_taxes + $tax_class_taxes;
			}

			$total_tax = array_sum( array_map( array( $this, 'round_line_tax_in_cents' ), $fee_taxes ) );

			if ( wc_gzd_additional_costs_include_tax() ) {
				$fee->total = $fee->total - $total_tax;
			}

			$fee->split_taxes = $taxable_amounts;
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
					if ( $key != $chosen_method ) {
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

			if ( ! empty( $args['title'] ) ) {
				$title = is_numeric( $args['title'] ) ? wc_gzd_get_customer_title( $args['title'] ) : $args['title'];

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
				$placeholder['{name}']        = $placeholder['{title}'] . ' ' . $placeholder['{name}'];
				$placeholder['{name_upper}']  = $placeholder['{title_upper}'] . ' ' . $placeholder['{name_upper}'];
			}
		}

		return $placeholder;
	}

	public function set_custom_fields( $fields = array(), $type = 'billing' ) {
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				$new = array();
				if ( in_array( $type, $custom_field['group'] ) ) {
					if ( ! empty( $fields ) ) {
						foreach ( $fields as $name => $field ) {
							if ( $name == $type . '_' . $custom_field['before'] && ! isset( $custom_field['override'] ) ) {
								$new[ $type . '_' . $key ] = $custom_field;
							}

							$new[ $name ] = $field;

							if ( $name == $type . '_' . $key && isset( $custom_field['override'] ) ) {
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
						if ( $name == $custom_field['before'] && ! isset( $custom_field['override'] ) ) {
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