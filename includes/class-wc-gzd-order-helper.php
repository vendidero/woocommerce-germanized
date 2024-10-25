<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Order_Helper {

	protected static $_instance = null;

	protected $order_item_map = null;

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
		// Add better incl tax display to order totals
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_tax_totals' ), 0, 3 );

		/**
		 * Recalculate order item unit price after tax adjustments.
		 */
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'recalculate_order_item_unit_price' ), 60, 1 );

		// Add Title to billing address format
		add_filter(
			'woocommerce_order_formatted_billing_address',
			array(
				$this,
				'set_formatted_billing_address',
			),
			0,
			2
		);

		add_filter(
			'woocommerce_order_formatted_shipping_address',
			array(
				$this,
				'set_formatted_shipping_address',
			),
			0,
			2
		);

		// Add title options to order address data
		add_filter( 'woocommerce_get_order_address', array( $this, 'add_order_address_data' ), 10, 3 );

		add_action( 'woocommerce_before_order_item_object_save', array( $this, 'on_order_item_update' ), 10 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'set_order_meta_hidden' ), 0 );

		add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'set_fee_split_tax_meta' ), 10, 4 );

		add_action( 'woocommerce_before_order_object_save', array( $this, 'set_order_version' ), 10 );

		// The woocommerce_before_order_object_save hook might fail in case an order has been created manually
		add_action( 'woocommerce_new_order', array( $this, 'on_create_order' ), 10 );

		// Disallow user order cancellation
		if ( 'yes' === get_option( 'woocommerce_gzd_checkout_stop_order_cancellation' ) ) {
			add_filter( 'woocommerce_get_cancel_order_url', array( $this, 'cancel_order_url' ), 1500, 1 );
			add_filter( 'woocommerce_get_cancel_order_url_raw', array( $this, 'cancel_order_url' ), 1500, 1 );
			add_filter( 'user_has_cap', array( $this, 'disallow_user_order_cancellation' ), 15, 3 );

			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'remove_cancel_button' ), 10, 2 );

			// Remove order stock right after confirmation is sent
			add_action(
				'woocommerce_germanized_order_confirmation_sent',
				array(
					$this,
					'maybe_reduce_order_stock',
				),
				5,
				1
			);
		}

		/**
		 * Other services (e.g. virtual, services) are not taxable in northern ireland
		 */
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'maybe_remove_northern_ireland_taxes' ), 10, 2 );

		/**
		 * WooCommerce automatically creates a full refund after the order status changes to refunded.
		 * Make sure to create a refund ourselves before Woo does and include item-related (e.g. taxes) refund data.
		 * This way accounting and OSS reports are much more precise. Only relevant in case shop owners manually
		 * mark an order as refunded without doing a refund before.
		 *
		 * @see wc_order_fully_refunded
		 */
		add_action( 'woocommerce_order_status_refunded', array( $this, 'create_refund_with_items' ), 5 );

		/**
		 * Additional costs calculation
		 */
		if ( wc_gzd_enable_additional_costs_split_tax_calculation() || wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
			add_action( 'woocommerce_order_before_calculate_totals', array( $this, 'tmp_store_order_item_copy_before_calculate_totals' ), 500, 2 );
			add_action(
				'woocommerce_order_after_calculate_totals',
				function ( $and_taxes, $order ) {
					if ( $and_taxes ) {
						$this->order_item_map = null;

						foreach ( $order->get_items( array( 'shipping', 'fee' ) ) as $item ) {
							$item->delete_meta_data( '_internal_gzd_key' );
						}
					}
				},
				10,
				2
			);

			add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $this, 'adjust_additional_costs_item_taxes' ), 10, 2 );
			add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array( $this, 'adjust_additional_costs_item_taxes' ), 10, 2 );
			add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'adjust_additional_costs_item_taxes' ), 10, 2 );
			add_action(
				'woocommerce_order_before_calculate_totals',
				function () {
					add_filter( 'woocommerce_order_get_shipping_total', array( $this, 'force_shipping_total_exact' ), 10, 2 );
				},
				500,
				2
			);
			add_action(
				'woocommerce_order_after_calculate_totals',
				function () {
					remove_filter( 'woocommerce_order_get_shipping_total', array( $this, 'force_shipping_total_exact' ), 10 );
				},
				500
			);
		} else {
			add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $this, 'remove_additional_costs_item_meta' ), 10, 2 );
			add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array( $this, 'remove_additional_costs_item_meta' ), 10, 2 );
			add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'remove_additional_costs_item_meta' ), 10, 2 );
		}
	}

	/**
	 * @param WC_Order_Item $item
	 * @param WC_Order_Item|false $old_item
	 *
	 * @return float
	 */
	protected function get_item_total( $item, $old_item = false ) {
		// Let's grab a fresh copy (loaded from DB) to make sure we are not dependent on Woo's calculated taxes in $item.
		if ( $old_item ) {
			$item_total = wc_format_decimal( floatval( $old_item->get_total() ) );

			if ( wc_gzd_additional_costs_include_tax() ) {
				$item_total += wc_format_decimal( floatval( $old_item->get_total_tax() ) );
			}
		} else {
			$item_total     = wc_format_decimal( floatval( $item->get_total() ) );
			$is_adding_item = wp_doing_ajax() && isset( $_POST['action'] ) && in_array( wp_unslash( $_POST['action'] ), array( 'woocommerce_add_order_fee', 'woocommerce_add_order_shipping' ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			/**
			 * When adding a fee through the admin panel, Woo by default calculates taxes
			 * based on the fee's tax class (which by default is standard). Ignore the tax data on first call.
			 */
			if ( ! $is_adding_item && wc_gzd_additional_costs_include_tax() ) {
				$item_total += wc_format_decimal( floatval( $item->get_total_tax() ) );
			}
		}

		return $item_total;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function get_order_taxable_location( $order ) {
		return \Vendidero\EUTaxHelper\Helper::get_order_taxable_location( $order );
	}

	/**
	 * @param WC_Order $order
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_order_tax_share( $order, $type = 'shipping' ) {
		return wc_gzd_get_cart_tax_share( $type, $order->get_items() );
	}

	/**
	 * When (re-) calculation order totals Woo does round shipping total to current price decimals.
	 * That is not the case within cart/checkout and leads to rounding issues. This filter forces recalculating
	 * the exact shipping total instead of using the already calculated shipping total amount while calculating order totals.
	 *
	 * @param $total
	 * @param WC_Order $order
	 *
	 * @see WC_Abstract_Order::calculate_totals()
	 *
	 */
	public function force_shipping_total_exact( $total, $order ) {
		$total = 0;

		foreach ( $order->get_shipping_methods() as $method ) {
			$total += floatval( $method->get_total() );
		}

		return $total;
	}

	/**
	 * @param WC_Order_Item $item
	 *
	 * @return false|WC_Order_Item_Fee|WC_Order_Item_Shipping
	 */
	protected function get_order_item_before_calculate_totals( $item ) {
		$order    = $item->get_order();
		$old_item = $order ? $order->get_item( $item->get_id() ) : false;
		$map      = is_null( $this->order_item_map ) ? array() : $this->order_item_map;
		$map      = wp_parse_args(
			$map,
			array(
				'shipping' => array(),
				'fee'      => array(),
			)
		);

		$target_map   = array();
		$internal_key = $item->get_id() > 0 ? $item->get_id() : $item->get_meta( '_internal_gzd_key' );

		if ( is_a( $item, 'WC_Order_Item_Shipping' ) ) {
			$target_map = $map['shipping'];
		} elseif ( is_a( $item, 'WC_Order_Item_Fee' ) ) {
			$target_map = $map['fee'];
		}

		if ( array_key_exists( $internal_key, $target_map ) ) {
			$old_item = $target_map[ $internal_key ];
		}

		return $old_item;
	}

	/**
	 * Within the new block-based checkout store API, Woo additionally calls WC_Abstract_Order::calculate_totals() after
	 * passing line items from the cart. This will recalculate taxes too which conflicts with additional costs calculation.
	 *
	 * As a tweak we need to store a cloned copy of the relevant items before (re) calculating taxes to actually determine
	 * the original shipping/fee total. For existing/persisting orders this may be done by force-reloading the item from DB.
	 *
	 * @param $and_taxes
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function tmp_store_order_item_copy_before_calculate_totals( $and_taxes, $order ) {
		if ( $and_taxes ) {
			$this->order_item_map = array(
				'shipping' => array(),
				'fee'      => array(),
			);

			foreach ( $order->get_shipping_methods() as $k => $item ) {
				$item->update_meta_data( '_internal_gzd_key', $k );

				$this->order_item_map['shipping'][ $k ] = clone $item;
			}

			foreach ( $order->get_fees() as $k => $item ) {
				$item->update_meta_data( '_internal_gzd_key', $k );

				$this->order_item_map['fee'][ $k ] = clone $item;
			}
		}
	}

	/**
	 * @param WC_Order_Item $item
	 *
	 * @return void
	 */
	public function remove_additional_costs_item_meta( $item ) {
		$item->delete_meta_data( '_split_taxes' );
		$item->delete_meta_data( '_tax_shares' );

		if ( $order = $item->get_order() ) {
			$order->delete_meta_data( '_has_split_tax' );
			$order->delete_meta_data( '_additional_costs_include_tax' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service_tax_class' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service_by' );

			$order->save();
		}
	}

	/**
	 * @param WC_Order_Item $item
	 * @param array $calculate_tax_for
	 */
	public function adjust_additional_costs_item_taxes( $item, $calculate_tax_for = array() ) {
		if ( ! wc_tax_enabled() || ! in_array( $item->get_type(), array( 'fee', 'shipping' ), true ) ) {
			return;
		}

		if ( apply_filters( 'woocommerce_gzd_skip_order_item_split_tax_calculation', false, $item ) ) {
			return;
		}

		if ( is_a( $item, 'WC_Order_Item_Fee' ) ) {
			$fee_props = (object) array(
				'id'        => '',
				'name'      => '',
				'tax_class' => '',
				'taxable'   => false,
				'amount'    => 0,
				'total'     => 0,
			);

			$fee_props->name      = $item->get_name();
			$fee_props->tax_class = $item->get_tax_class();
			$fee_props->taxable   = 'taxable' === $item->get_tax_status();
			$fee_props->amount    = $item->get_amount();
			$fee_props->id        = $item->get_meta( '_voucher_id' ) ? sanitize_title( $item->get_meta( '_voucher_id' ) ) : sanitize_title( $fee_props->name );
			$fee_props->object    = $fee_props;

			if ( ! apply_filters( 'woocommerce_gzd_force_fee_tax_calculation', true, $fee_props ) ) {
				return;
			}
		}

		if ( $order = $item->get_order() ) {
			$item->delete_meta_data( '_split_taxes' );
			$item->delete_meta_data( '_tax_shares' );

			$order->delete_meta_data( '_has_split_tax' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service_tax_class' );
			$order->delete_meta_data( '_additional_costs_taxed_based_on_main_service_by' );

			$calculate_tax_for = empty( $calculate_tax_for ) ? $this->get_order_taxable_location( $order ) : $calculate_tax_for;
			$tax_type          = 'shipping' === $item->get_type() ? 'shipping' : 'fee';

			if ( wc_gzd_enable_additional_costs_split_tax_calculation() ) {
				// Calculate tax shares
				$tax_share = apply_filters( "woocommerce_gzd_{$tax_type}_order_tax_shares", $this->get_order_tax_share( $order, $tax_type ), $item );

				// Do only adjust taxes if tax share contains more than one tax rate
				if ( $tax_share && ! empty( $tax_share ) ) {
					$taxes           = array();
					$item_total      = $this->get_item_total( $item, $this->get_order_item_before_calculate_totals( $item ) );
					$taxable_amounts = array();

					foreach ( $tax_share as $tax_class => $class ) {
						if ( isset( $calculate_tax_for['country'] ) ) {
							$calculate_tax_for['tax_class'] = $tax_class;
							$tax_rates                      = \WC_Tax::find_rates( $calculate_tax_for );
						} else {
							$tax_rates = \WC_Tax::get_rates_from_location( $tax_class, $calculate_tax_for );
						}

						$taxable_amount  = $item_total * $class['share'];
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

					$item->set_taxes( array( 'total' => $taxes ) );
					$item->update_meta_data( '_split_taxes', $taxable_amounts );
					$item->update_meta_data( '_tax_shares', $tax_share );

					// The new net total equals old gross total minus new tax totals
					if ( wc_gzd_additional_costs_include_tax() ) {
						$item->set_total( $item_total - $item->get_total_tax() );
					}

					$order->update_meta_data( '_has_split_tax', 'yes' );
				} else {
					$item->delete_meta_data( '_split_taxes' );
					$item->delete_meta_data( '_tax_shares' );

					$order->delete_meta_data( '_has_split_tax' );
				}
			} elseif ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ) {
				$taxes          = array();
				$main_tax_class = self::instance()->get_order_main_service_tax_class( $order, $tax_type );

				if ( false !== $main_tax_class ) {
					$item_total = $this->get_item_total( $item, $this->get_order_item_before_calculate_totals( $item ) );

					if ( isset( $calculate_tax_for['country'] ) ) {
						$calculate_tax_for['tax_class'] = $main_tax_class;
						$tax_rates                      = \WC_Tax::find_rates( $calculate_tax_for );
					} else {
						$tax_rates = \WC_Tax::get_rates_from_location( $main_tax_class, $calculate_tax_for );
					}

					$taxable_amount  = $item_total;
					$tax_class_taxes = WC_Tax::calc_tax( $taxable_amount, $tax_rates, wc_gzd_additional_costs_include_tax() );
					$taxes           = $taxes + $tax_class_taxes;

					$item->set_taxes( array( 'total' => $taxes ) );

					if ( is_callable( array( $item, 'set_tax_class' ) ) ) {
						$item->set_tax_class( $main_tax_class );
					}

					// The new net total equals old gross total minus new tax totals
					if ( wc_gzd_additional_costs_include_tax() ) {
						$item->set_total( $item_total - $item->get_total_tax() );
					}

					$order->update_meta_data( '_additional_costs_taxed_based_on_main_service', 'yes' );
					$order->update_meta_data( '_additional_costs_taxed_based_on_main_service_by', wc_gzd_additional_costs_taxes_detect_main_service_by() );
					$order->update_meta_data( '_additional_costs_taxed_based_on_main_service_tax_class', $main_tax_class );
				}
			}

			$order->update_meta_data( '_additional_costs_include_tax', wc_bool_to_string( wc_gzd_additional_costs_include_tax() ) );

			/**
			 * Need to manually call the order save method to make sure
			 * meta data is persisted as $item->get_order() constructs a fresh order instance which will be lost
			 * during global save event.
			 */
			$order->save();
		}
	}

	public function maybe_remove_northern_ireland_taxes( $order_item, $calculate_tax_for ) {
		if ( is_a( $order_item, 'WC_Order_Item_Product' ) ) {
			if ( \Vendidero\EUTaxHelper\Helper::is_northern_ireland( $calculate_tax_for['country'], $calculate_tax_for['postcode'] ) ) {
				if ( $product = $order_item->get_product() ) {
					if ( wc_gzd_get_gzd_product( $product )->is_other_service() ) {
						$order_item->set_taxes( false );
					}
				}
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return boolean
	 */
	public function get_order_main_service_tax_class( $order, $type = 'shipping' ) {
		$main_tax_class = false;
		$max_total      = 0.0;
		$detect_by      = wc_gzd_additional_costs_taxes_detect_main_service_by();

		foreach ( $order->get_items( 'line_item' ) as $key => $item ) {
			if ( wc_gzd_item_is_tax_share_exempt( $item, $type, $key ) ) {
				continue;
			}

			$tax_class  = $item->get_tax_class();
			$item_total = 0.0;

			if ( 'highest_net_amount' === $detect_by ) {
				$item_total = (float) $item->get_total();
			} elseif ( 'highest_tax_rate' === $detect_by ) {
				$taxes = $item->get_taxes();

				if ( isset( $taxes['total'] ) ) {
					$main_tax_rate_id = 0;

					foreach ( $taxes['total'] as $tax_rate_id => $tax_total ) {
						if ( empty( $tax_total ) ) {
							continue;
						}

						$main_tax_rate_id = $tax_rate_id;
						break;
					}

					if ( ! empty( $main_tax_rate_id ) ) {
						$item_total = wc_gzd_get_order_tax_rate_percentage( $main_tax_rate_id, $order );
					}
				}
			}

			if ( false === $main_tax_class || $item_total > $max_total ) {
				$main_tax_class = $tax_class;
				$max_total      = $item_total;
			}
		}

		return apply_filters( 'woocommerce_gzd_order_main_service_tax_class', $main_tax_class );
	}

	public function create_refund_with_items( $order_id ) {
		$order      = wc_get_order( $order_id );
		$max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() );

		if ( ! $max_refund ) {
			return;
		}

		$items_to_refund = array();

		foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item ) {
			$refunded_total = (float) $order->get_total_refunded_for_item( $item->get_id(), $item->get_type() );
			$total          = (float) $item->get_total();
			$refunded_qty   = abs( $order->get_qty_refunded_for_item( $item->get_id() ) );

			if ( wc_format_decimal( $refunded_total, '' ) >= wc_format_decimal( $total, '' ) ) {
				continue;
			}

			$refund_taxes = array();
			$item_taxes   = $item->get_taxes();

			foreach ( $item_taxes['total'] as $tax_id => $tax_total ) {
				$refunded_tax_total = (float) $order->get_tax_refunded_for_item( $item->get_id(), $tax_id, $item->get_type() );

				if ( wc_format_decimal( $refunded_tax_total, '' ) >= wc_format_decimal( $tax_total, '' ) ) {
					continue;
				}

				$refund_taxes[ $tax_id ] = (float) $tax_total - $refunded_tax_total;
			}

			$items_to_refund[ $item->get_id() ] = array(
				'qty'          => $refunded_qty >= $item->get_quantity() ? 1 : ( (int) $item->get_quantity() - $refunded_qty ),
				'refund_total' => wc_format_decimal( $total - $refunded_total ),
				'refund_tax'   => $refund_taxes,
			);
		}

		if ( ! empty( $items_to_refund ) ) {
			// Create the refund object.
			wc_switch_to_site_locale();
			wc_create_refund(
				array(
					'amount'     => $max_refund,
					'line_items' => $items_to_refund,
					'reason'     => __( 'Order fully refunded.', 'woocommerce-germanized' ),
					'order_id'   => $order_id,
				)
			);
			wc_restore_locale();

			$order->add_order_note( __( 'Order status set to refunded. To return funds to the customer you will need to issue a refund through your payment gateway.', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @param $order_id
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function on_create_order( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( ! $order->get_meta( '_gzd_version' ) ) {
				$order->update_meta_data( '_gzd_version', WC_germanized()->version );
				$order->save();
			}
		}
	}

	/**
	 * @param WC_Abstract_Order $order
	 *
	 * @return void
	 */
	public function set_order_version( $order ) {
		if ( ! $order->get_id() ) {
			$order->update_meta_data( '_gzd_version', WC_germanized()->version );
		}
	}

	public function get_order_version( $order ) {
		$version = '1.0.0';

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order ) {
			$version = $order->get_meta( '_gzd_version', true );

			if ( ! $version ) {
				$version = '1.0.0';
			}
		}

		return $version;
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
				case 'cancel_order':
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

		return esc_url_raw( $return );
	}

	/**
	 * @param WC_Order_Item $item
	 */
	public function on_order_item_update( $item ) {
		/**
		 * Refresh item data in case product id changes or it is a new item.
		 */
		if ( $item->get_id() <= 0 || in_array( 'product_id', $item->get_changes(), true ) ) {
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
		array_push( $metas, '_defect_description' );
		array_push( $metas, '_deposit_type' );
		array_push( $metas, '_deposit_amount' );
		array_push( $metas, '_deposit_net_amount' );
		array_push( $metas, '_deposit_quantity' );
		array_push( $metas, '_deposit_amount_per_unit' );
		array_push( $metas, '_deposit_net_amount_per_unit' );
		array_push( $metas, '_deposit_packaging_type' );

		return $metas;
	}

	public function refresh_item_data( $item ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) && ( $gzd_item = wc_gzd_get_order_item( $item ) ) ) {
			/**
			 * Before adding order item meta.
			 *
			 * Fires before Germanized added order item meta.
			 *
			 * @param WC_Order_Item $item The order item.
			 * @param WC_Order $order The order.
			 * @param WC_GZD_Order_Item $gzd_item The order item object.
			 *
			 * @since 3.11.4
			 */
			do_action( 'woocommerce_gzd_before_add_order_item_meta', $item, $item->get_order(), $gzd_item );

			if ( $product = $item->get_product() ) {
				$gzd_product = wc_gzd_get_product( $product );

				$gzd_item->set_unit( $gzd_product->get_unit_name() );
				$gzd_item->set_unit_base( $gzd_product->get_unit_base() );
				$gzd_item->set_unit_product( $gzd_product->get_unit_product() );

				$gzd_item->recalculate_unit_price();

				$gzd_item->set_cart_description( $gzd_product->get_formatted_cart_description() );
				$gzd_item->set_defect_description( $gzd_product->get_formatted_defect_description() );
				$gzd_item->set_delivery_time( $gzd_product->get_delivery_time_html() );
				$gzd_item->set_min_age( $gzd_product->get_min_age() );

				if ( $gzd_product->is_food() ) {
					$gzd_item->set_deposit_type( $gzd_product->get_deposit_type() );
					$gzd_item->set_deposit_amount_per_unit( $gzd_product->get_deposit_amount_per_unit( 'view', 'incl' ) );
					$gzd_item->set_deposit_net_amount_per_unit( $gzd_product->get_deposit_amount_per_unit( 'view', 'excl' ) );

					$gzd_item->set_deposit_quantity( $gzd_product->get_deposit_quantity() );
					$gzd_item->set_deposit_amount( $gzd_product->get_deposit_amount( 'view', 'incl' ) );
					$gzd_item->set_deposit_net_amount( $gzd_product->get_deposit_amount( 'view', 'excl' ) );

					$gzd_item->set_deposit_packaging_type( $gzd_product->get_deposit_packaging_type() );
				}

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

			/**
			 * After adding order item meta.
			 *
			 * Fires after Germanized added order item meta.
			 *
			 * @param WC_Order_Item $item The order item.
			 * @param WC_Order $order The order.
			 * @param WC_GZD_Order_Item $gzd_item The order item object.
			 *
			 * @since 3.11.4
			 */
			do_action( 'woocommerce_gzd_after_add_order_item_meta', $item, $item->get_order(), $gzd_item );
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

						/**
						 * Prevent showing taxes twice
						 */
						if ( wc_gzd_show_taxes_before_total( 'order' ) ) {
							unset( $order_totals[ $key ] );
						}
					}
				}
			}
		} else {
			$tax_array = array();

			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $order->get_tax_totals() as $code => $tax ) {
					$tax->rate = wc_gzd_get_order_tax_rate_percentage( $tax->rate_id, $order );
					$rate_key  = (string) $tax->rate;

					if ( ! isset( $tax_array[ $rate_key ] ) ) {
						$tax_array[ $rate_key ] = array(
							'tax'      => $tax,
							'amount'   => $tax->amount,
							'contains' => array( $tax ),
						);
					} else {
						array_push( $tax_array[ $rate_key ]['contains'], $tax );
						$tax_array[ $rate_key ]['amount'] += $tax->amount;
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
