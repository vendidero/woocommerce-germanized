<?php

class WC_GZD_Order_Helper {

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

		// Add better incl tax display to order totals
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_tax_totals' ), 0, 3 );

		/**
		 * Recalculate order item unit price after tax adjustments.
		 */
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'recalculate_order_item_unit_price' ), 60, 1 );

		// Add Title to billing address format
		add_filter( 'woocommerce_order_formatted_billing_address', array(
			$this,
			'set_formatted_billing_address'
		), 0, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array(
			$this,
			'set_formatted_shipping_address'
		), 0, 2 );

		// Add title options to order address data
		add_filter( 'woocommerce_get_order_address', array( $this, 'add_order_address_data' ), 10, 3 );

		add_action( 'woocommerce_before_order_item_object_save', array( $this, 'on_order_item_update' ), 10 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'set_order_meta_hidden' ), 0 );

		add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'set_fee_split_tax_meta' ), 10, 4 );

		// Disallow user order cancellation
		if ( 'yes' === get_option( 'woocommerce_gzd_checkout_stop_order_cancellation' ) ) {

			add_filter( 'woocommerce_get_cancel_order_url', array( $this, 'cancel_order_url' ), 1500, 1 );
			add_filter( 'woocommerce_get_cancel_order_url_raw', array( $this, 'cancel_order_url' ), 1500, 1 );
			add_filter( 'user_has_cap', array( $this, 'disallow_user_order_cancellation' ), 15, 3 );

			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'remove_cancel_button' ), 10, 2 );

			// Remove order stock right after confirmation is sent
			add_action( 'woocommerce_germanized_order_confirmation_sent', array(
				$this,
				'maybe_reduce_order_stock'
			), 5, 1 );
		}
	}

	/**
	 * @param WC_Order_Item_Fee $item
	 * @param $fee_key
	 * @param $fee
	 * @param WC_Order $order
	 */
	public function set_fee_split_tax_meta( $item, $fee_key, $fee, $order ) {
		if ( isset( $fee->split_taxes ) && ! empty( $fee->split_taxes ) ) {
			$item->update_meta_data( '_split_taxes', $fee->split_taxes );
		}
	}

	public function remove_cancel_button( $actions, $order ) {
		if ( isset( $actions['cancel'] ) ) {
			unset( $actions['cancel'] );
		}

		return $actions;
	}

	public function maybe_reduce_order_stock( $order_id ) {
		wc_maybe_reduce_stock_levels( $order_id );
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
			$order    = wc_get_order( $order_id );

			/**
			 * Filter the order cancellation URL replacement when customer
			 * order cancellation was disabled in the Germanized settings.
			 * Defaults to the order-received page.
			 *
			 * @param string $url The return url.
			 * @param WC_Order $order The order object.
			 *
			 * @since 1.0.0
			 *
			 */
			$return = apply_filters( 'woocommerce_gzd_attempt_order_cancellation_url', add_query_arg( array( 'retry' => true ), $order->get_checkout_order_received_url(), $order ) );
		}

		return $return;
	}

	/**
	 * @param WC_Order_Item_Product $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function set_order_item_meta_crud( $item, $cart_item_key, $values, $order ) {
		$this->refresh_item_data( $item );
	}

	/**
	 * @param WC_Order_Item $item
	 */
	public function on_order_item_update( $item ) {
		/**
		 * Refresh item data in case product id changes or it is a new item.
		 */
		if ( $item->get_id() <= 0 || in_array( 'product_id', $item->get_changes() ) ) {
			$this->refresh_item_data( $item );
		}
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
		array_push( $metas, '_unit_price_raw' );
		array_push( $metas, '_unit_price_subtotal_raw' );
		array_push( $metas, '_unit_price_subtotal_net_raw' );
		array_push( $metas, '_unit_price_net_raw' );
		array_push( $metas, '_unit_product' );
		array_push( $metas, '_unit' );
		array_push( $metas, '_unit_base' );
		array_push( $metas, '_min_age' );

		return $metas;
	}

	public function refresh_item_data( $item ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) && ( $product = $item->get_product() ) ) {
			if ( $gzd_item = wc_gzd_get_order_item( $item ) ) {
				$gzd_product = wc_gzd_get_product( $product );

				$gzd_item->set_unit( $gzd_product->get_unit_name() );
				$gzd_item->set_unit_base( $gzd_product->get_unit_base() );
				$gzd_item->set_unit_product( $gzd_product->get_unit_product() );

				$gzd_item->recalculate_unit_price();

				$gzd_item->set_cart_description( $gzd_product->get_formatted_cart_description() );
				$gzd_item->set_delivery_time( $gzd_product->get_delivery_time_html() );
				$gzd_item->set_min_age( $gzd_product->get_min_age() );

				/**
				 * Add order item meta.
				 *
				 * Fires when Germanized adds order item meta.
				 *
				 * @param WC_Order_Item $item The order item.
				 * @param WC_Order $order The order.
				 * @param WC_GZD_Product $gzd_product The product object.
				 * @param WC_GZD_Order_Item $gzd_item The order item object.
				 *
				 * @since 1.8.9
				 */
				do_action( 'woocommerce_gzd_add_order_item_meta', $item, $item->get_order(), $gzd_product, $gzd_item );
			}
		}
	}

	public function add_order_address_data( $data, $type, $order ) {
		if ( WC_GZD_Customer_Helper::instance()->is_customer_title_enabled() ) {
			if ( $this->order_address_enable_customer_title( $data ) && ( $title = wc_gzd_get_order_customer_title( $order, $type ) ) ) {
				$data['title'] = $title;
			}
		}

		return $data;
	}

	/**
	 * @param $fields
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public function set_formatted_billing_address( $fields, $order ) {
		if ( ! WC_GZD_Customer_Helper::instance()->is_customer_title_enabled() ) {
			return $fields;
		}

		if ( $this->order_address_enable_customer_title( $fields ) && ( $title = wc_gzd_get_order_customer_title( $order, 'billing' ) ) ) {
			$fields['title'] = $title;
		}

		return $fields;
	}

	public function order_address_enable_customer_title( $fields ) {
		/**
		 * If no last name has been chosen, remove the title too
		 */
		if ( ! isset( $fields['last_name'] ) || empty( $fields['last_name'] ) ) {
			return false;
		}

		return true;
	}

	public function set_formatted_shipping_address( $fields, $order ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return $fields;
		}

		if ( ! WC_GZD_Customer_Helper::instance()->is_customer_title_enabled() ) {
			return $fields;
		}

		if ( $this->order_address_enable_customer_title( $fields ) && ( $title = wc_gzd_get_order_customer_title( $order, 'shipping' ) ) ) {
			$fields['title'] = $title;
		}

		return $fields;
	}

	/**
	 * Improve tax display within order totals
	 *
	 * @param array    $order_totals
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function order_item_tax_totals( $order_totals, $order, $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );

		// Set to formatted total without displaying tax info behind the price
		$order_totals['order_total']['value'] = $order->get_formatted_order_total();

		$tax_totals = array();

		if ( 'excl' === $tax_display ) {
			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $order->get_tax_totals() as $code => $tax ) {
					$key = sanitize_title( $code );

					if ( isset( $order_totals[ $key ] ) ) {
						$percentage = wc_gzd_get_order_tax_rate_percentage( $tax->rate_id, $order );

						if ( ! is_null( $percentage ) ) {
							$tax_totals[ $key ]          = $order_totals[ $key ];
							$tax_totals[ $key ]['label'] = wc_gzd_get_tax_rate_label( $percentage, 'excl' );
						}
					}
				}
			}
		} else {
			$tax_array = array();

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $order->get_tax_totals() as $code => $tax ) {
					$tax->rate = wc_gzd_get_order_tax_rate_percentage( $tax->rate_id, $order );

					if ( ! isset( $tax_array[ $tax->rate ] ) ) {
						$tax_array[ $tax->rate ] = array(
							'tax'      => $tax,
							'amount'   => $tax->amount,
							'contains' => array( $tax ),
						);
					} else {
						array_push( $tax_array[ $tax->rate ]['contains'], $tax );
						$tax_array[ $tax->rate ]['amount'] += $tax->amount;
					}
				}
			} else {

				$base_rate = WC_Tax::get_base_tax_rates();
				$rate      = reset( $base_rate );
				$rate_id   = key( $base_rate );

				$base_rate          = (object) $rate;
				$base_rate->rate_id = $rate_id;

				$tax_array[] = array(
					'tax'      => $base_rate,
					'contains' => array( $base_rate ),
					'amount'   => $order->get_total_tax(),
				);

				/**
				 * Make sure no zero taxes are added for small businesses
				 */
				if ( wc_gzd_is_small_business() && $order->get_total_tax() <= 0 ) {
					$tax_array = array();
				}
			}

			if ( ! empty( $tax_array ) ) {
				foreach ( $tax_array as $tax ) {
					/**
					 * Hide zero taxes
					 */
					if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax['amount'] <= 0 ) {
						continue;
					}

					$tax_totals[ 'tax_' . WC_Tax::get_rate_code( $tax['tax']->rate_id ) ] = array(
						'label' => wc_gzd_get_tax_rate_label( $tax['tax']->rate ),
						'value' => wc_price( $tax['amount'], array( 'currency' => $order->get_currency() ) ),
					);
				}
			}
		}

		if ( wc_gzd_show_taxes_before_total( 'order' ) ) {
			array_splice( $order_totals, -1, 0, $tax_totals );
		} else {
			$order_totals = array_merge( $order_totals, $tax_totals );
		}

		return $order_totals;
	}

	public function recalculate_order_item_unit_price( $order_item ) {
		if ( is_a( $order_item, 'WC_Order_Item_Product' ) ) {
			if ( $gzd_item = wc_gzd_get_order_item( $order_item ) ) {
				$gzd_item->recalculate_unit_price();
			}
		}
	}
}

WC_GZD_Order_Helper::instance();