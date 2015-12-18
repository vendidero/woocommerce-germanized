<?php

class WC_GZD_Checkout {

	public $custom_fields = array();
	public $custom_fields_admin = array();

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
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
		add_action( 'init', array( $this, 'init_fields' ) );
		add_filter( 'woocommerce_billing_fields', array( $this, 'set_custom_fields' ), 0, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'set_custom_fields_shipping' ), 0, 1 );
		// Add Fields to Order Edit Page
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_custom_fields_admin' ), 0, 1 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'set_custom_fields_admin' ), 0, 1 );
		// Save Fields on order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_fields' ) );
		// Add Title to billing address format
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'set_formatted_billing_address' ), 0, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_formatted_address' ), 0, 2 );
		// Add item desc to order
		add_action( 'woocommerce_order_add_product', array( $this, 'set_order_meta' ), 0, 5 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'set_order_meta_hidden' ), 0 );
		// Deactivate checkout shipping selection
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'remove_shipping_rates' ), 0 );
		// Add better fee taxation
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'do_fee_tax_calculation' ), PHP_INT_MAX, 1 );
		// Disallow user order cancellation
		if ( get_option( 'woocommerce_gzd_checkout_stop_order_cancellation' ) == 'yes' ) {
			add_filter( 'woocommerce_get_cancel_order_url', array( $this, 'cancel_order_url' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_get_cancel_order_url_raw', array( $this, 'cancel_order_url' ), PHP_INT_MAX, 1 );
			add_filter( 'user_has_cap', array( $this, 'disallow_user_order_cancellation' ), 15, 3 );
		}
		add_action( 'template_redirect', array( $this, 'init_gettext_replacement' ) );
		// Free Shipping auto select
		if ( get_option( 'woocommerce_gzd_display_checkout_free_shipping_select' ) == 'yes' )
			add_filter( 'woocommerce_package_rates', array( $this, 'free_shipping_auto_select' ) );
	}

	public function free_shipping_auto_select( $rates ) {
		if ( ( is_checkout() || is_cart() ) && isset( $rates['free_shipping'] ) ) {
			foreach ( $rates as $key => $value ) {
				if ( 'free_shipping' !== $key )
					unset( $rates[ $key ] );
			}
		}
		return $rates;
	}

	public function init_gettext_replacement() {
		if ( is_checkout() && get_option( 'woocommerce_gzd_customer_account_checkout_checkbox' ) == 'yes' )
			add_filter( 'gettext', array( $this, 'set_customer_account_checkbox_text' ), 10, 3 );
	}

	public function set_customer_account_checkbox_text( $translated, $original, $domain ) {
		$search = "Create an account?";
		if ( $domain === 'woocommerce' && $original === $search ) {
			remove_filter( 'gettext', array( $this, 'set_customer_account_checkbox_text' ), 10, 3 );
			return wc_gzd_get_legal_text( get_option( 'woocommerce_gzd_customer_account_text' ) );
		}
		return $translated;
	}

	public function get_order_payment_url( $order_id ) {
		
		$order = wc_get_order( $order_id );
		
		if ( $order->order_payment_info )
			return $order->order_payment_info;
		
		return false;
	}

	public function add_payment_link( $order_id ) {

		if ( get_option( 'woocommerce_gzd_order_pay_now_button' ) == 'no' )
			return false;
		
		$order = wc_get_order( $order_id );
		
		if ( ! $order->needs_payment() )
			return;
		
		if ( $url = $this->get_order_payment_url( $order_id ) )
			wc_get_template( 'order/order-pay-now-button.php', array( 'url' => $url, 'order_id' => $order_id ) );
	}

	public function disallow_user_order_cancellation( $allcaps, $caps, $args ) {
		if ( isset( $caps[0] ) ) {
			switch ( $caps[0] ) {
				case 'cancel_order' :
					$allcaps['cancel_order'] = false;
				break;
			}
		}
		return $allcaps;
	}

	public function cancel_order_url( $url ) {
		
		// Default to home url
		$return = get_permalink( wc_get_page_id( 'shop' ) );

		// Extract order id and use order success page as return url
		$search = preg_match( '/order_id=([0-9]+)/', $url, $matches );
		
		if ( $search && isset( $matches[1] ) ) {
			$order_id = absint( $matches[1] );
			$order = wc_get_order( $order_id );
			$return = $order->get_checkout_order_received_url();
		}
		
		return $return;
	}

	public function init_fields() {
		if ( get_option( 'woocommerce_gzd_checkout_address_field' ) == 'yes' ) {

			$this->custom_fields[ 'title' ] = array(
				'type' 	   => 'select',
				'required' => 1,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
				'options'  => apply_filters( 'woocommerce_gzd_title_options', array( 1 => __( 'Mr.', 'woocommerce-germanized' ), 2 => __( 'Ms.', 'woocommerce-germanized' ) ) ),
				'before'   => 'first_name',
				'group'    => array( 'billing', 'shipping' ),
			);

			$this->custom_fields_admin[ 'title' ] = array(
				'before'   => 'first_name',
				'type'     => 'select',
				'options'  => array( 1 => __( 'Mr.', 'woocommerce-germanized' ), 2 => __( 'Ms.', 'woocommerce-germanized' ) ),
				'show'     => false,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
			);

		}

		if ( get_option( 'woocommerce_gzd_checkout_phone_required' ) == 'no' ) {

			$this->custom_fields[ 'phone' ] = array(
				'before'   => '',
				'override' => true,
				'required' => false,
				'group'    => array( 'billing' )
			);

		}
	}

	/**
	 * Recalculate fee taxes to split tax based on different tax rates contained within cart
	 *  
	 * @param  WC_Cart $cart
	 */
	public function do_fee_tax_calculation( WC_Cart $cart ) {
		if ( get_option( 'woocommerce_gzd_fee_tax' ) != 'yes' )
			return;
		if ( ! empty( $cart->fees ) ) {
			$tax_shares = wc_gzd_get_cart_tax_share( 'fee' );
			foreach ( $cart->fees as $key => $fee ) {
				if ( ! $fee->taxable && get_option( 'woocommerce_gzd_fee_tax_force' ) != 'yes' )
					continue;	
				// Calculate gross price if necessary
				if ( $fee->taxable ) {
					$fee_tax_rates = WC_Tax::get_rates( $fee->tax_class );
					$fee_tax = WC_Tax::calc_tax( $fee->amount, $fee_tax_rates, false );
					$fee->amount += array_sum( $fee_tax );
				}
				// Set fee to nontaxable to avoid WooCommerce default tax calculation
				$fee->taxable = false;
				// Calculate tax class share
				if ( ! empty( $tax_shares ) ) {
					$fee_taxes = array();
					foreach ( $tax_shares as $rate => $class ) {
						$tax_rates = WC_Tax::get_rates( $rate );
						$tax_shares[ $rate ][ 'fee_tax_share' ] = $fee->amount * $class[ 'share' ];
						$tax_shares[ $rate ][ 'fee_tax' ] = WC_Tax::calc_tax( ( $fee->amount * $class[ 'share' ] ), $tax_rates, true );
						$fee_taxes += $tax_shares[ $rate ][ 'fee_tax' ];
					}
					foreach ( $tax_shares as $rate => $class ) {
						$cart->fees[ $key ]->tax_data = $cart->fees[ $key ]->tax_data + $class[ 'fee_tax' ];
					}
					// Add fee taxes to cart taxes
					foreach ( array_keys( $cart->taxes + $fee_taxes ) as $sub ) {
						$cart->taxes[ $sub ] = ( isset( $fee_taxes[ $sub ] ) ? $fee_taxes[ $sub ] : 0 ) + ( isset( $cart->taxes[ $sub ] ) ? $cart->taxes[ $sub ] : 0 );
					}
					// Update fee
					$cart->fees[ $key ]->tax = array_sum( $cart->fees[ $key ]->tax_data );
					$cart->fees[ $key ]->amount = $cart->fees[ $key ]->amount - $cart->fees[ $key ]->tax;
				}
			}
		}
	}

	/**
	 * Temporarily removes all shipping rates (except chosen one) from packages to only show chosen package within checkout. 
	 */
	public function remove_shipping_rates() {
		if ( get_option( 'woocommerce_gzd_display_checkout_shipping_rate_select' ) == 'no' )
			return;
		$packages = WC()->shipping->get_packages();
		foreach ( $packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( ! empty( $package[ 'rates' ] ) ) {
				foreach ( $package[ 'rates' ] as $key => $rate ) {
					if ( $key != $chosen_method )
						unset( WC()->shipping->packages[ $i ][ 'rates' ][ $key ] );
				}
			}	
		}
	}

	/**
	 * Adds product description to order meta
	 *  
	 * @param int $order_id 
	 * @param int $item_id  
	 * @param object $product  
	 * @param int $qty      
	 * @param array $args     
	 */
	public function set_order_meta( $order_id, $item_id, $product, $qty, $args ) {
		wc_add_order_item_meta( $item_id, '_units', wc_gzd_get_gzd_product( $product )->get_product_units_html() );
		wc_add_order_item_meta( $item_id, '_delivery_time', wc_gzd_get_gzd_product( $product )->get_delivery_time_html() );
		wc_add_order_item_meta( $item_id, '_item_desc', wc_gzd_get_gzd_product( $product )->get_mini_desc() );
		wc_add_order_item_meta( $item_id, '_unit_price', wc_gzd_get_gzd_product( $product )->get_unit_html( false ) );
	}

	/**
	 * Hide product description from order meta default output
	 *  
	 * @param array $metas
	 */
	public function set_order_meta_hidden( $metas ) {
		array_push( $metas, '_item_desc' );
		array_push( $metas, '_units' );
		array_push( $metas, '_delivery_time' );
		array_push( $metas, '_unit_price' );
		return $metas;
	}

	public function set_formatted_billing_address( $fields = array(), $order ) {

		if ( 'yes' !== get_option( 'woocommerce_gzd_checkout_address_field' ) )
			return $fields;

		if ( $order->billing_title )
			$fields[ 'title' ] = $this->get_customer_title( $order->billing_title );
		return $fields;
	}

	public function set_formatted_shipping_address( $fields = array(), $order ) {

		if ( 'yes' !== get_option( 'woocommerce_gzd_checkout_address_field' ) )
			return $fields;
		
		if ( $order->shipping_title )
			$fields[ 'title' ] = $this->get_customer_title( $order->shipping_title );
		return $fields;
	}

	public function get_customer_title( $option = 1 ) {
		return ( 1 == $option ? __( 'Mr.', 'woocommerce-germanized' ) : __( 'Ms.', 'woocommerce-germanized' ) );
	}

	public function set_formatted_address( $placeholder, $args ) {
		if ( isset( $args[ 'title' ] ) ) {
			$placeholder[ '{title}' ] = $args[ 'title' ];
			$placeholder[ '{title_upper}' ] = strtoupper( $args[ 'title' ] );
			$placeholder[ '{name}' ] = $placeholder[ '{title}' ] . ' ' . $placeholder[ '{name}' ];
			$placeholder[ '{name_upper}' ] = $placeholder[ '{title_upper}' ] . ' ' . $placeholder[ '{name_upper}' ];
		}
		return $placeholder;
	}

	public function set_custom_fields( $fields = array(), $type = 'billing' ) {
		$new = array();
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				if ( in_array( $type, $custom_field[ 'group' ] ) ) {
					if ( ! empty( $fields ) ) {
						foreach ( $fields as $name => $field ) {
							if ( $name == $type . '_' . $custom_field[ 'before' ] && ! isset( $custom_field[ 'override' ] ) )
								$new[ $type . '_' . $key ] = $custom_field;
							$new[ $name ] = $field;
							if ( $name == $type . '_' . $key && isset( $custom_field[ 'override' ] ) )
								$new[ $name ] = array_merge( $field, $custom_field );
						}
					}
				}
			}
		}
		return ( ! empty( $new ) ? $new : $fields );
	}

	public function set_custom_fields_shipping( $fields ) {
		return $this->set_custom_fields( $fields, 'shipping' );
	}

	public function set_custom_fields_admin( $fields = array() ) {
		$new = array();
		if ( ! empty( $this->custom_fields_admin ) ) {
			foreach ( $this->custom_fields_admin as $key => $custom_field ) {
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $name => $field ) {
						if ( $name == $custom_field[ 'before' ] && ! isset( $custom_field[ 'override' ] ) )
							$new[ $key ] = $custom_field;
						$new[ $name ] = $field;
					}
				}
			}
		}
		return ( ! empty( $new ) ? $new : $fields );
	}

	public function save_fields( $order_id ) {
		$checkout = WC()->checkout();
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				if ( ! empty( $custom_field[ 'group' ] ) && ! isset( $custom_field[ 'override' ] ) ) {
					foreach ( $custom_field[ 'group' ] as $group ) {
						$val = ( isset( $checkout->posted[ $group . '_' . $key ] ) ? $checkout->posted[ $group . '_' . $key ] : '' );
						if ( $group == 'shipping' || $group == 'billing' )
							$val = $checkout->get_posted_address_data( $key, $group );
						if ( ! empty( $val ) )
							update_post_meta( $order_id, '_' . $group . '_' . $key, sanitize_text_field( $val ) );
					}
				}
			}
		}
	}

}

WC_GZD_Checkout::instance();