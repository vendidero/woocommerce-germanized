<?php
/**
 * Core Functions
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Given an element name, returns a class name.
 *
 * If the WP-related function is not defined, return empty string.
 *
 * @param string $element The name of the element.
 *
 * @return string
 */
function eu_owb_wp_theme_get_element_class_name( $element ) {
	if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
		return wc_wp_theme_get_element_class_name( $element );
	} elseif ( function_exists( 'wp_theme_get_element_class_name' ) ) {
		return wp_theme_get_element_class_name( $element );
	}

	return '';
}

function eu_owb_get_withdrawable_order_statuses( $prefixed = true ) {
	$order_statuses = array_diff_key(
		wc_get_order_statuses(),
		array(
			'wc-cancelled' => '',
			'wc-refunded'  => '',
			'wc-failed'    => '',
			'wc-withdrawn' => '',
		)
	);

	$order_statuses = apply_filters( 'eu_owb_woocommerce_withdrawable_order_statuses', array_keys( $order_statuses ) );

	if ( ! $prefixed ) {
		$order_statuses = array_map(
			function ( $status ) {
				if ( strpos( $status, 'wc-' ) === 0 ) {
					$status = substr( $status, 3 );
				}

				return $status;
			},
			$order_statuses
		);
	}

	return $order_statuses;
}

/**
 * @param WC_Order|integer $order
 *
 * @return boolean
 */
function eu_owb_order_is_withdrawable( $order ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return false;
	}

	$is_withdrawable = true;

	if ( ! $order->has_status( eu_owb_get_withdrawable_order_statuses( false ) ) ) {
		$is_withdrawable = false;
	}

	$items = eu_owb_get_withdrawable_order_items( $order );

	if ( empty( $items ) ) {
		$is_withdrawable = false;
	}

	if ( $date_delivered = eu_owb_order_get_date_delivered( $order ) ) {
		/**
		 * Calculate day diff in local timezone
		 */
		$datetime = new WC_DateTime( 'now', new DateTimeZone( 'UTC' ) );

		if ( get_option( 'timezone_string' ) ) {
			$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
		} else {
			$datetime->set_utc_offset( wc_timezone_offset() );
		}

		$diff = $date_delivered->diff( $datetime );

		if ( $diff->days > eu_owb_get_number_of_days_to_withdraw() ) {
			$is_withdrawable = false;
		}
	}

	return apply_filters( 'eu_owb_woocommerce_order_is_withdrawable', $is_withdrawable, $order );
}

function eu_owb_get_number_of_days_to_withdraw() {
	return absint( \Vendidero\OrderWithdrawalButton\Package::get_setting( 'number_of_days_to_withdraw', 14 ) );
}

/**
 * @param WC_Order|integer $order
 *
 * @return null|WC_DateTime
 */
function eu_owb_order_get_date_delivered( $order ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return null;
	}

	$date_delivered = apply_filters( 'eu_owb_woocommerce_order_date_delivered_raw', $order->get_date_completed(), $order );

	if ( ! $date_delivered && ( is_callable( array( $order, 'needs_shipping' ) ) && ! $order->needs_shipping() ) ) {
		$date_delivered = $order->get_date_paid();
	}

	/**
	 * Allow cancellation requests until next day at 00:00:01.
	 */
	if ( $date_delivered ) {
		$date_delivered->modify( 'midnight' );
		$date_delivered->modify( '+1 second' );
		$date_delivered->modify( '+1 day' );
	}

	return apply_filters( 'eu_owb_woocommerce_get_order_date_delivered', $date_delivered, $order );
}

/**
 * @param WC_Order|integer $order
 * @param boolean $include_non_withdrawable
 *
 * @return bool
 */
function eu_owb_order_supports_partial_withdrawal( $order, $include_non_withdrawable = false ) {
	$cancelable_items = eu_owb_get_withdrawable_order_items( $order, array( 'include_non_withdrawable' => $include_non_withdrawable ) );
	$supports         = false;

	if ( \Vendidero\OrderWithdrawalButton\Package::enable_partial_withdrawals() ) {
		if ( count( $cancelable_items ) > 1 || array_values( $cancelable_items )[0]['quantity'] > 1 ) {
			$supports = true;
		}
	}

	return apply_filters( 'eu_owb_woocommerce_order_supports_partial_withdrawal', $supports, $order );
}

function eu_owb_get_edit_withdrawal_url( $order ) {
	$url = eu_owb_get_withdrawal_page_permalink();

	if ( ! empty( $url ) ) {
		$url = add_query_arg(
			array(
				'order_id'              => $order->get_id(),
				'order_key'             => $order->get_order_key(),
				'manually_select_items' => 'yes',
			),
			$url
		);
	}

	return apply_filters( 'eu_owb_woocommerce_edit_withdrawal_url', $url, $order );
}

function eu_owb_get_withdrawal_page_permalink() {
	$page_id = eu_owb_get_withdrawal_page_id();
	$link    = ( $page_id > 0 ) ? get_permalink( $page_id ) : '';

	return apply_filters( 'eu_owb_woocommerce_withdrawal_page_permalink', $link );
}

function eu_owb_get_withdrawal_button_text() {
	$text = _x( 'Withdraw from contract', 'owb', 'woocommerce-germanized' );

	return apply_filters( 'eu_owb_woocommerce_withdrawal_button_text', $text );
}

function eu_owb_get_withdrawal_page_id() {
	return apply_filters( 'eu_owb_woocommerce_withdrawal_page_id', wc_get_page_id( 'withdraw_from_contract' ) );
}

function eu_owb_get_element_class_name( $element ) {
	if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
		return wc_wp_theme_get_element_class_name( $element );
	} elseif ( function_exists( 'wp_theme_get_element_class_name' ) ) {
		return wp_theme_get_element_class_name( $element );
	}

	return '';
}

/**
 * @param WP_Error $error
 *
 * @return bool
 */
function eu_owb_wp_error_has_errors( $error ) {
	if ( is_callable( array( $error, 'has_errors' ) ) ) {
		return $error->has_errors();
	} else {
		$errors = $error->errors;

		return ( ! empty( $errors ) ? true : false );
	}
}

function eu_owb_has_public_withdrawal_page() {
	$is_public = eu_owb_get_withdrawal_page_is_published() || ( eu_owb_get_withdrawal_page_id() <= 0 && eu_owb_get_withdrawal_page_permalink() );

	return apply_filters( 'eu_owb_woocommerce_withdrawal_page_is_public', $is_public );
}

function eu_owb_get_withdrawal_page_is_published() {
	$page_id      = eu_owb_get_withdrawal_page_id();
	$is_published = false;

	if ( $page_id > 0 ) {
		$is_published = 'publish' === get_post_status( $page_id );
	}

	return $is_published;
}

function eu_owb_get_contact_support_url() {
	$email = get_option( 'admin_email' );

	if ( $from = WC_Emails::instance()->get_from_address() ) {
		$email = $from;
	}

	if ( $from = \Vendidero\OrderWithdrawalButton\Package::get_setting( 'contact_email_address' ) ) {
		$email = sanitize_email( $from );
	}

	$business_email = apply_filters( 'eu_owb_get_contact_support_email', $email );

	return apply_filters( 'eu_owb_get_contact_support_url', "mailto:{$business_email}" );
}

/**
 * @param WC_Order|integer $order
 * @param boolean $include_non_withdrawable
 *
 * @return WC_Order_Item_Product[]
 */
function eu_owb_get_withdrawable_order_items( $order, $args = array() ) {
	if ( is_bool( $args ) ) {
		$include_non_withdrawable = $args;

		$args = array(
			'include_non_withdrawable' => $include_non_withdrawable,
		);
	}

	$args = wp_parse_args(
		$args,
		array(
			'include_non_withdrawable' => false,
			'include_requested'        => false,
			'include_withdrawal'       => null,
		)
	);

	if ( ! is_a( $order, 'WC_Order' ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return null;
	}

	$items_to_withdraw = array();

	foreach ( $order->get_items() as $item ) {
		$total_qty_left = eu_owb_get_order_item_quantity_left_to_withdraw( $item, $order, $args );

		if ( $total_qty_left <= 0 ) {
			continue;
		}

		$items_to_withdraw[ $item->get_id() ] = array(
			'item'     => $item,
			'quantity' => $total_qty_left,
		);
	}

	return apply_filters( 'eu_owb_woocommerce_withdrawable_order_items', $items_to_withdraw, $order );
}

function eu_owb_get_stock_amount( $quantity ) {
	return function_exists( 'wc_stock_amount' ) ? wc_stock_amount( $quantity ) : (float) wc_format_decimal( $quantity );
}

/**
 * @param WC_Order_Item_Product $item
 * @param WC_Order|null $order
 * @param array $args
 *
 * @return mixed
 */
function eu_owb_get_order_item_quantity_left_to_withdraw( $item, $order = null, $args = array() ) {
	if ( is_bool( $args ) ) {
		$include_non_withdrawable = $args;

		$args = array(
			'include_non_withdrawable' => $include_non_withdrawable,
		);
	}

	$args = wp_parse_args(
		$args,
		array(
			'include_non_withdrawable' => false,
			'include_requested'        => false,
			'include_withdrawal'       => null,
		)
	);

	$order = ! $order ? $item->get_order() : $order;

	if ( ! $order ) {
		return 0;
	}

	$statuses = array( 'confirmed', 'rejected' );

	if ( $args['include_requested'] ) {
		$statuses[] = 'requested';
	}

	$total_qty    = $item->get_quantity();
	$withdrawals  = eu_owb_get_order_withdrawals( $order, array( 'status' => $statuses ) );
	$refunded_qty = $order->get_qty_refunded_for_item( $item->get_id() );

	if ( is_a( $args['include_withdrawal'], '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
		$withdrawals[] = $args['include_withdrawal'];
	}

	if ( $refunded_qty < 0 ) {
		$refunded_qty *= -1;
	}

	$total_qty      = $total_qty - $refunded_qty;
	$withdrawal_ids = array();

	foreach ( $withdrawals as $withdrawal ) {
		if ( ! in_array( $withdrawal->get_id(), $withdrawal_ids, true ) ) {
			$withdrawal_ids[] = $withdrawal->get_id();

			foreach ( $withdrawal->get_items() as $withdrawal_item ) {
				if ( $withdrawal_item->get_parent_id() === $item->get_id() ) {
					$total_qty -= $withdrawal_item->get_quantity();
				}
			}
		}
	}

	if ( $total_qty <= 0 ) {
		$total_qty = 0;
	}

	if ( ! $args['include_non_withdrawable'] && ! eu_owb_order_item_is_withdrawable( $item, $order ) ) {
		$total_qty = 0;
	}

	return apply_filters( 'eu_owb_woocommerce_order_item_quantity_left_to_withdraw', eu_owb_get_stock_amount( $total_qty ), $item, $order );
}

/**
 * @param WC_Order $order
 *
 * @return bool
 */
function eu_owb_order_has_withdrawal_request( $order ) {
	return eu_owb_get_withdrawal_request( $order ) ? true : false;
}

/**
 * @param WC_Order $order
 *
 * @return bool
 */
function eu_owb_order_has_withdrawal_or_request( $order ) {
	return eu_owb_order_has_withdrawal_request( $order ) || eu_owb_order_is_withdrawn( $order );
}

/**
 * @param WC_Order $order
 *
 * @return bool
 */
function eu_owb_order_has_partial_withdrawal_request( $order ) {
	if ( $pending = eu_owb_get_withdrawal_request( $order ) ) {
		return wc_string_to_bool( $pending['is_partial'] );
	}

	return false;
}

/**
 * @param WC_Order $order
 *
 * @return bool
 */
function eu_owb_order_is_withdrawal_request_update( $order ) {
	if ( $pending = eu_owb_get_withdrawal_request( $order ) ) {
		return wc_string_to_bool( $pending['is_update'] );
	}

	return false;
}

/**
 * @param WC_Order $order
 *
 * @return bool
 */
function eu_owb_order_is_guest_withdrawal_request( $order ) {
	if ( $pending = eu_owb_get_withdrawal_request( $order ) ) {
		return wc_string_to_bool( $pending['is_guest'] );
	}

	return false;
}

/**
 * @param WC_Order $order
 * @param string $id
 *
 * @return bool
 */
function eu_owb_order_is_withdrawn( $order ) {
	return $order->has_status( 'withdrawn' ) || eu_owb_order_has_withdrawals( $order );
}

function eu_owb_order_has_confirmed_withdrawals( $order ) {
	$withdrawals = eu_owb_get_order_withdrawals( $order, array( 'status' => 'confirmed' ) );

	return ! empty( $withdrawals ) ? true : false;
}

/**
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $request
 *
 * @return bool
 */
function eu_owb_order_withdrawal_request_has_multiple_orders( $request ) {
	$has_multiple = $request->get_meta( '_has_multiple_matching_orders' ) ? wc_string_to_bool( $request->get_meta( '_has_multiple_matching_orders' ) ) : false;

	return $has_multiple;
}

/**
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $request
 *
 * @return int
 */
function eu_owb_order_withdrawal_request_get_original_order_id( $request ) {
	$order_id = $request->get_meta( '_original_request_order_id' ) ? absint( $request->get_meta( '_original_request_order_id' ) ) : 0;

	return $order_id;
}

function eu_owb_get_order_withdrawal_email( $order ) {
	$email = '';

	if ( $withdrawal = eu_owb_get_withdrawal_or_request( $order ) ) {
		$email = $withdrawal->get_email();
	}

	return $email;
}

function eu_owb_timestamp_to_datetime( $timestamp ) {
	$date = null;

	if ( ! empty( $timestamp ) ) {
		$date = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

		// Set local timezone or offset.
		if ( get_option( 'timezone_string' ) ) {
			$date->setTimezone( new DateTimeZone( wc_timezone_string() ) );
		} else {
			$date->set_utc_offset( wc_timezone_offset() );
		}
	}

	return $date;
}

/**
 * @param $order
 * @param string $id,
 *
 * @return WC_DateTime|null
 */
function eu_owb_get_order_withdrawal_date_received( $order, $id = '' ) {
	$date = null;

	if ( $withdrawal = eu_owb_get_withdrawal_or_request( $order, $id ) ) {
		$date = $withdrawal->get_date_received();
	}

	return $date;
}

function eu_owb_order_withdrawal_email_is_verified( $order, $id = '' ) {
	$is_verified = false;

	if ( $withdrawal = eu_owb_get_withdrawal_or_request( $order, $id ) ) {
		$is_verified = $withdrawal->has_verified_email();
	}

	return $is_verified;
}

/**
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder|WC_Order $order
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder|null
 * @param bool $placeholder
 *
 * @return string
 */
function eu_owb_get_order_withdrawal_full_name( $order, $withdrawal = null, $placeholder = false ) {
	if ( ! is_null( $withdrawal ) ) {
		$order = $withdrawal;
	}

	$full_name = $order->get_formatted_full_name( $placeholder );

	return apply_filters( 'eu_owb_woocommerce_order_withdrawal_full_name', $full_name, $order );
}

/**
 * @param $order
 * @param string $id,
 *
 * @return WC_DateTime|null
 */
function eu_owb_get_order_withdrawal_date_confirmed( $order, $id = '' ) {
	$date = null;

	if ( $withdrawal = eu_owb_get_order_withdrawal( $order, $id ) ) {
		if ( $withdrawal->has_status( 'confirmed' ) ) {
			$date = $withdrawal->get_date_confirmed();
		}
	}

	return $date;
}

/**
 * @param $order
 * @param string $id,
 *
 * @return WC_DateTime|null
 */
function eu_owb_get_order_withdrawal_date_rejected( $order, $id = '' ) {
	$date = null;

	if ( $withdrawal = eu_owb_get_order_withdrawal( $order, $id ) ) {
		if ( $withdrawal->has_status( 'rejected' ) ) {
			$date = $withdrawal->get_date_rejected();
		}
	}

	return $date;
}

function eu_owb_get_last_order_withdrawal( $order ) {
	$withdrawals = eu_owb_get_order_withdrawals( $order, array( 'confirmed', 'rejected' ) );
	$withdrawal  = false;

	if ( ! empty( $withdrawals ) ) {
		$withdrawal = $withdrawals[ count( $withdrawals ) - 1 ];
	}

	return $withdrawal;
}

/**
 * @param WC_Order|integer $order
 * @param string|array $id
 *
 * @return \Vendidero\OrderWithdrawalButton\WithdrawalOrder|false
 */
function eu_owb_get_withdrawal_or_request( $order, $id = '' ) {
	$withdrawal = false;

	if ( is_a( $order, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
		$withdrawal = $order;
	} elseif ( is_a( $order, 'WC_Order' ) && ! empty( $id ) ) {
		if ( is_a( $id, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
			$withdrawal = $id;
		} else {
			$withdrawal = eu_owb_get_order_withdrawal_by_id( $order, $id );
		}
	} elseif ( is_a( $order, 'WC_Order' ) ) {
		$withdrawal = eu_owb_get_withdrawal_request( $order );

		if ( ! $withdrawal ) {
			$withdrawal = eu_owb_get_last_order_withdrawal( $order );
		}
	}

	return $withdrawal;
}

function eu_owb_get_order_withdrawal( $order, $id = '' ) {
	$withdrawal = false;

	if ( is_a( $order, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
		$withdrawal = $order;
	} elseif ( is_a( $order, 'WC_Order' ) && ! empty( $id ) ) {
		$withdrawal = eu_owb_get_order_withdrawal_by_id( $order, $id );
	} elseif ( is_a( $order, 'WC_Order' ) ) {
		$withdrawal = eu_owb_get_last_order_withdrawal( $order );
	}

	return $withdrawal;
}

/**
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder|integer $order
 *
 * @return array
 */
function eu_owb_get_withdrawal_order_items( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return array();
	}

	$items = array();

	foreach ( $order->get_items() as $item_id => $item ) {
		$items[ $item_id ] = array(
			'item'     => $item,
			'quantity' => $item->get_quantity(),
		);
	}

	return $items;
}

/**
 * @param $withdrawal
 *
 * @return \Vendidero\OrderWithdrawalButton\WithdrawalOrder|null
 */
function eu_owb_get_withdrawal( $withdrawal ) {
	$order = wc_get_order( $withdrawal );

	if ( ! is_a( $order, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
		$order = null;
	}

	return $order;
}

/**
 * @param WC_Order $order
 * @param array $query
 *
 * @return \Vendidero\OrderWithdrawalButton\WithdrawalOrder[]
 */
function eu_owb_get_order_withdrawals( $order, $query = array() ) {
	$order_id    = is_numeric( $order ) ? $order : $order->get_id();
	$cache_key   = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'withdrawals' . $order_id;
	$withdrawals = wp_cache_get( $cache_key, 'orders' );

	if ( false === $withdrawals ) {
		$withdrawals = wc_get_orders(
			array(
				'type'   => 'shop_order_withdraw',
				'status' => array_keys( \Vendidero\OrderWithdrawalButton\Package::get_withdrawal_statuses() ),
				'parent' => $order_id,
				'limit'  => -1,
			)
		);
		wp_cache_set( $cache_key, $withdrawals, 'orders' );
	}

	$args = array(
		'status' => array(),
	);

	$args           = array_replace_recursive( $args, $query );
	$args['status'] = (array) $args['status'];

	$results = array();

	if ( ! empty( $withdrawals ) && is_array( $withdrawals ) ) {
		foreach ( $withdrawals as $withdrawal ) {
			if ( $withdrawal instanceof \Vendidero\OrderWithdrawalButton\WithdrawalOrder ) {
				if ( ! empty( $args['status'] ) ) {
					if ( ! $withdrawal->has_status( $args['status'] ) ) {
						continue;
					}
				}

				if ( ! empty( $args['withdrawal_number'] ) ) {
					if ( $args['withdrawal_number'] !== $withdrawal->get_withdrawal_number() ) {
						continue;
					}
				}

				$results[] = $withdrawal;
			}
		}
	}

	return $results;
}

function eu_owb_order_has_withdrawals( $order ) {
	$withdrawals = eu_owb_get_order_withdrawals( $order );

	return ! empty( $withdrawals ) ? true : false;
}

function eu_owb_get_order_withdrawal_by_id( $order, $id ) {
	$withdrawals = eu_owb_get_order_withdrawals(
		$order,
		array(
			'withdrawal_number' => $id,
			'limit'             => 1,
		)
	);

	if ( ! empty( $withdrawals ) ) {
		return $withdrawals[ count( $withdrawals ) - 1 ];
	}

	return false;
}

function eu_owb_get_order_withdrawal_default_args() {
	return array(
		'id'                 => md5( uniqid( '', true ) ),
		'date_received'      => time(),
		'date_confirmed'     => null,
		'date_rejected'      => null,
		'request_email'      => '',
		'original_status'    => '',
		'items'              => array(),
		'meta'               => array(),
		'rejection_reason'   => '',
		'is_partial'         => 'no',
		'has_verified_email' => 'yes',
		'is_update'          => 'no',
		'is_guest'           => 'yes',
		'has_refund'         => 'no',
		'refund_id'          => 0,
	);
}

function eu_owb_get_withdrawal_request_by_order_number( $order_number ) {
	$custom_query_cpt_cb = function ( $query, $query_vars ) {
		if ( ! empty( $query_vars['order_number'] ) ) {
			$query['meta_query'][] = array(
				'key'     => '_order_number',
				'value'   => wc_clean( $query_vars['order_number'] ),
				'compare' => '=',
			);
		}

		return $query;
	};

	add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $custom_query_cpt_cb, 10, 2 );

	$args = array(
		'type'         => 'shop_order_withdraw',
		'status'       => array( 'wc-owb-requested' ),
		'limit'        => 1,
		'orderby'      => 'date_created',
		'order_number' => $order_number,
	);

	/**
	 * HPOS supports meta query
	 */
	if ( \Vendidero\OrderWithdrawalButton\Package::is_hpos_enabled() ) {
		unset( $args['order_number'] );

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_order_number',
				'value'   => wc_clean( $order_number ),
				'compare' => '=',
			),
		);
	}

	$requests     = wc_get_orders( $args );
	$last_request = false;

	if ( ! empty( $requests ) ) {
		$last_request = $requests[ count( $requests ) - 1 ];

		if ( $last_request->get_order_number() !== $order_number ) {
			$last_request = false;
		}
	}

	remove_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $custom_query_cpt_cb, 10, 2 );

	return $last_request;
}

/**
 * @param WC_Order|integer $order
 *
 * @return \Vendidero\OrderWithdrawalButton\WithdrawalOrder|null
 */
function eu_owb_get_withdrawal_request( $order ) {
	$order = wc_get_order( $order );

	if ( is_a( $order, 'WC_Order' ) ) {
		$withdrawals = eu_owb_get_order_withdrawals(
			$order,
			array(
				'status' => 'requested',
				'limit'  => 1,
			)
		);

		if ( ! empty( $withdrawals ) ) {
			return $withdrawals[ count( $withdrawals ) - 1 ];
		}
	} elseif ( is_a( $order, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
		return $order;
	}

	return null;
}

/**
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $request
 *
 * @return bool
 */
function eu_owb_withdrawal_request_needs_manual_verification( $request ) {
	$needs_verification = false;

	if ( 'yes' === \Vendidero\OrderWithdrawalButton\Package::get_setting( 'separately_store_unverified_withdrawal_requests', 'yes' ) ) {
		$needs_verification = ! $request->get_has_verified_email() || ! $request->has_parent();
	}

	return $needs_verification;
}

/**
 * @param $email
 * @param WC_Order|integer|false $order
 * @param $items
 * @param boolean $as_guest
 *
 * @return WP_Error|true
 */
function eu_owb_create_order_withdrawal_request( $email, $order = false, $items = array(), $as_guest = true, $meta = array() ) {
	$error = new \WP_Error();

	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	$is_full_withdrawal = true;
	$item_desc          = array();
	$withdrawal         = new \Vendidero\OrderWithdrawalButton\WithdrawalOrder();
	$original_status    = '';

	if ( $order ) {
		if ( $existing_withdrawal = eu_owb_get_withdrawal_request( $order ) ) {
			$withdrawal      = $existing_withdrawal;
			$original_status = $withdrawal->get_original_status();
		}
	}

	$withdrawal->set_email( $email );
	$withdrawal->set_is_partial( ! $is_full_withdrawal );
	$withdrawal->set_has_verified_email( false );
	$withdrawal->set_is_guest( $as_guest );
	$withdrawal->update_parent( $order, $items );

	if ( apply_filters( 'eu_owb_woocommerce_store_withdrawal_request_ip', false ) ) {
		$withdrawal->set_customer_ip_address( WC_Geolocation::get_ip_address() );
		$withdrawal->set_customer_user_agent( wc_get_user_agent() );
	}

	/**
	 * Withdrawal update
	 */
	if ( $withdrawal->get_id() > 0 ) {
		$withdrawal->set_is_update( true );

		if ( ! empty( $original_status ) ) {
			$withdrawal->set_original_status( $original_status );
		}
	}

	foreach ( $meta as $meta_key => $value ) {
		$setter = "set_{$meta_key}";

		if ( is_callable( array( $withdrawal, $setter ) ) ) {
			$withdrawal->$setter( $value );
		} else {
			$withdrawal->update_meta_data( "_{$meta_key}", $value );
		}
	}

	foreach ( $withdrawal->get_items() as $item ) {
		$item_desc[] = $item->get_name() . ' &times; ' . $item->get_quantity();
	}

	$withdrawal_request_id = $withdrawal->save();

	if ( ! $withdrawal_request_id ) {
		$error->add( 'invalid_request', _x( 'Error while saving the withdrawal request.', 'owb', 'woocommerce-germanized' ) );
		return $error;
	}

	$new_order_status = '';

	if ( ! eu_owb_withdrawal_request_needs_manual_verification( $withdrawal ) ) {
		$new_order_status = 'pending-wdraw';
	}

	$order_note = sprintf( _x( 'A new %1$s has been submitted to this order', 'owb', 'woocommerce-germanized' ), $withdrawal->is_partial() ? _x( 'Partial withdrawal request', 'owb', 'woocommerce-germanized' ) : _x( 'Full withdrawal request', 'owb', 'woocommerce-germanized' ) );

	if ( ! empty( $item_desc ) ) {
		$order_note .= ': ' . implode( ', ', $item_desc ) . '.';
	} else {
		$order_note .= '.';
	}

	if ( $order ) {
		if ( empty( $new_order_status ) || $order->has_status( $new_order_status ) ) {
			$order->add_order_note( $order_note );
			$order->save();
		} else {
			$order->update_status( $new_order_status, $order_note );
		}
	}

	do_action( 'eu_owb_woocommerce_withdrawal_request_created', $order, $withdrawal );

	WC()->mailer()->emails['EU_OWB_Email_Customer_Withdrawal_Request_Received']->trigger( $withdrawal->get_id(), $withdrawal );
	WC()->mailer()->emails['EU_OWB_Email_New_Withdrawal_Request']->trigger( $withdrawal->get_id(), $withdrawal );

	return true;
}

function eu_owb_order_confirm_withdrawal_request( $request_or_order ) {
	$request = eu_owb_get_withdrawal_request( $request_or_order );

	if ( ! $request ) {
		return false;
	}

	if ( ! $request->has_status( 'requested' ) ) {
		return false;
	}

	$order          = $request->get_parent();
	$default_status = $request->get_original_status();

	if ( $order ) {
		$items_left = eu_owb_get_withdrawable_order_items( $order, array( 'include_withdrawal' => $request ) );

		if ( empty( $items_left ) ) {
			$default_status = 'wc-withdrawn';

			$request->set_is_partial( false );
		} else {
			$request->set_is_partial( true );
		}
	}

	$default_status = apply_filters( 'eu_owb_woocommerce_order_withdrawal_status_confirmed', $default_status, $request );
	$order_note     = _x( 'A withdrawal request has been confirmed.', 'owb', 'woocommerce-germanized' );

	if ( $order ) {
		if ( empty( $default_status ) || $order->has_status( $default_status ) ) {
			$order->add_order_note( $order_note );
			$order->save();
		} else {
			if ( 'wc-withdrawn' !== $default_status ) {
				eu_owb_prevent_order_status_change_notifications( $order );
			}

			$order->update_status( $default_status, $order_note );
		}
	}

	$request->update_status( 'confirmed', true );

	do_action( 'eu_owb_woocommerce_withdrawal_request_confirmed', $order, $request );

	WC()->mailer()->emails['EU_OWB_Email_Customer_Withdrawal_Request_Confirmed']->trigger( $request->get_id(), $request );

	return true;
}

function eu_owb_prevent_order_status_change_notifications( $order ) {
	/**
	 * Prevent notifications and other actions from firing when resetting the order status.
	 */
	add_action(
		'woocommerce_after_order_object_save',
		function ( $the_order ) use ( $order ) {
			if ( $the_order->get_id() === $order->get_id() ) {
				$status = $order->get_status();

				remove_all_actions( 'woocommerce_order_status_' . $status );
				remove_all_actions( 'woocommerce_order_status_changed' );
				remove_all_actions( 'woocommerce_order_payment_status_changed' );
			}
		},
		999999
	);

	do_action( 'eu_owb_woocommerce_prevent_order_status_change_notifications', $order );
}

function eu_owb_order_delete_withdrawal_request( $request_or_order, $by_customer = false ) {
	$request = eu_owb_get_withdrawal_request( $request_or_order );

	if ( ! $request ) {
		return false;
	}

	$last_known_status = $request->get_original_status();
	$order             = $request->get_parent();

	$default_status = apply_filters( 'eu_owb_woocommerce_withdrawal_request_deleted_status', $last_known_status, $request );
	$order_note     = _x( 'A withdrawal request has been deleted.', 'owb', 'woocommerce-germanized' );

	if ( $by_customer ) {
		$order_note = _x( 'A withdrawal request has been deleted by the customer.', 'owb', 'woocommerce-germanized' );
	}

	$request->delete( $by_customer );

	if ( $order ) {
		if ( empty( $default_status ) || $order->has_status( $default_status ) ) {
			$order->add_order_note( $order_note );
			$order->save();
		} else {
			eu_owb_prevent_order_status_change_notifications( $order );

			$order->update_status( $default_status, $order_note );
		}
	}

	wc_get_logger()->info( 'Withdrawal request deleted.', array( 'source' => 'eu-owb-woocommerce' ) );
	wc_get_logger()->info( wc_print_r( $request->get_data(), true ), array( 'source' => 'eu-owb-woocommerce' ) );

	do_action( 'eu_owb_woocommerce_withdrawal_request_deleted', $request, $by_customer );

	if ( $by_customer ) {
		WC()->mailer()->emails['EU_OWB_Email_Deleted_Withdrawal_Request']->trigger( $request->get_id(), $request );
	}

	return true;
}

function eu_owb_order_reject_withdrawal_request( $request_or_order, $reason = '' ) {
	$request = eu_owb_get_withdrawal_request( $request_or_order );

	if ( ! $request ) {
		return false;
	}

	if ( ! $request->has_status( 'requested' ) ) {
		return false;
	}

	$last_known_status = $request->get_original_status();
	$order             = $request->get_parent();

	$request->set_rejection_reason( $reason );

	if ( $order ) {
		$default_status = apply_filters( 'eu_owb_woocommerce_withdrawal_request_rejected_status', $last_known_status, $order );
		$order_note     = sprintf( _x( 'A withdrawal request has been rejected: %1$s', 'owb', 'woocommerce-germanized' ), $reason );

		if ( empty( $default_status ) || $order->has_status( $default_status ) ) {
			$order->add_order_note( $order_note );
			$order->save();
		} else {
			eu_owb_prevent_order_status_change_notifications( $order );
			$order->update_status( $default_status, $order_note );
		}
	}

	$request->update_status( 'rejected', true );

	do_action( 'eu_owb_woocommerce_withdrawal_request_rejected', $order, $request, $reason );

	WC()->mailer()->emails['EU_OWB_Email_Customer_Withdrawal_Request_Rejected']->trigger( $request->get_id(), $request );

	return true;
}

/**
 * @param WC_Order_Item_Product $order_item
 *
 * @return boolean
 */
function eu_owb_order_item_is_withdrawable( $order_item, $order = null ) {
	$is_withdrawable = true;
	$excluded_types  = array_filter( (array) \Vendidero\OrderWithdrawalButton\Package::get_setting( 'excluded_product_types', array( 'virtual' ) ) );

	if ( ! empty( $excluded_types ) && ( $product = $order_item->get_product() ) ) {
		$is_withdrawable = ! eu_owb_product_matches_type( $product, $excluded_types );
	}

	return apply_filters( 'eu_owb_woocommerce_order_item_is_withdrawable', $is_withdrawable, $order_item, $order );
}

/**
 * @param WC_Product $product
 *
 * @return boolean
 */
function eu_owb_product_matches_type( $product, $types ) {
	$matches_type = false;

	if ( in_array( $product->get_type(), $types, true ) ) {
		$matches_type = true;
	} else {
		foreach ( $types as $type ) {
			$getter = 'is_' . $type;
			try {
				if ( is_callable( array( $product, $getter ) ) ) {
					$reflection = new ReflectionMethod( $product, $getter );

					if ( $reflection->isPublic() ) {
						$matches_type = $product->{$getter}() === true;
					}
				} else {
					$meta_key = "_{$type}";

					if ( $product->meta_exists( $meta_key ) ) {
						$matches_type = wc_string_to_bool( $product->get_meta( $meta_key ) );
					}
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}

			if ( true === $matches_type ) {
				break;
			}
		}
	}

	return apply_filters( 'eu_owb_woocommerce_product_matches_type', $matches_type, $product, $types );
}

function eu_owb_get_withdrawable_orders_for_user( $user_id = 0 ) {
	$orders = eu_owb_get_orders_for_user( $user_id );

	return eu_owb_get_withdrawable_orders( $orders );
}

function eu_owb_get_orders_for_user( $user_id = 0, $as_id = false ) {
	$user_id          = 0 === $user_id ? get_current_user_id() : $user_id;
	$min_date_created = strtotime( '-12 months' );

	if ( empty( $user_id ) ) {
		return array();
	}

	$orders = wc_get_orders(
		array(
			'customer_id'  => $user_id,
			'limit'        => -1,
			'orderby'      => 'date_created',
			'date_created' => '>' . $min_date_created,
			'return'       => $as_id ? 'ids' : 'objects',
			'status'       => eu_owb_get_withdrawable_order_statuses(),
		)
	);

	return $orders;
}

/**
 * Parses a string and finds the longest, contiguous number which is assumed to be the order id.
 *
 * @param $order_id_str
 *
 * @return string
 */
function eu_owb_get_order_id_from_string( $order_id_str ) {
	$order_id_parsed = trim( preg_replace( '/[^0-9]/', '_', $order_id_str ) );
	$order_id_comp   = explode( '_', $order_id_parsed );

	usort(
		$order_id_comp,
		function ( $a, $b ) {
			if ( strlen( $a ) === strlen( $b ) ) {
				return 0;
			}

			return ( strlen( $a ) < strlen( $b ) ) ? 1 : -1;
		}
	);

	// Prefer longer, contiguous order numbers
	$order_id = reset( $order_id_comp );

	return apply_filters( 'eu_owb_woocommerce_get_order_id_from_string', $order_id, $order_id_str );
}

function eu_owb_custom_email_matches_order_email( $order, $email ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return false;
	}

	$matches = false;

	if ( $email === $order->get_billing_email() ) {
		$matches = true;
	} elseif ( $order->get_customer_id() > 0 ) {
		try {
			if ( $customer = new WC_Customer( $order->get_customer_id() ) ) {
				if ( $customer->get_billing_email() === $email ) {
					$matches = true;
				} elseif ( $customer->get_email() === $email ) {
					$matches = true;
				}
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	return apply_filters( 'eu_owb_woocommerce_withdrawal_request_has_matching_email', $matches, $order, $email );
}

function eu_owb_get_withdrawable_orders( $order_ids, $as_ids = false ) {
	$orders_withdrawable = array();

	foreach ( $order_ids as $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( eu_owb_order_is_withdrawable( $order ) ) {
				$orders_withdrawable[] = $as_ids ? $order_id : $order;
			}
		}
	}

	return $orders_withdrawable;
}

/**
 * @param $order_id
 * @param $email
 *
 * @return false|integer
 */
function eu_owb_find_order( $order_id, $email ) {
	$db_order_id = false;
	$orders      = eu_owb_find_orders(
		array(
			'order_id' => $order_id,
			'email'    => $email,
		)
	);

	if ( ! empty( $orders ) ) {
		$db_order_id = $orders[0];
	}

	return apply_filters( 'eu_owb_woocommerce_find_order', $db_order_id, $order_id, $email );
}

function eu_owb_find_orders_by_custom_order_number( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'order_id'    => '',
			'email'       => '',
			'customer_id' => '',
			'return'      => 'ids',
		)
	);

	$args['email']       = sanitize_email( $args['email'] );
	$args['customer_id'] = absint( $args['customer_id'] );
	$args['order_id']    = sanitize_text_field( $args['order_id'] );
	$meta_field_name     = apply_filters( 'eu_owb_woocommerce_customer_order_number_meta_key', '_order_number' );

	$custom_query_cpt_cb = function ( $query, $query_vars ) use ( $meta_field_name ) {
		if ( ! empty( $query_vars['order_number'] ) ) {
			$query['meta_query'][] = array(
				'key'     => $meta_field_name,
				'value'   => wc_clean( $query_vars['order_number'] ),
				'compare' => '=',
			);
		}

		return $query;
	};

	add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $custom_query_cpt_cb, 10, 2 );

	$query_args_custom = array(
		'order_number' => $args['order_id'],
		'limit'        => 10,
		'return'       => $args['return'],
		'status'       => eu_owb_get_withdrawable_order_statuses(),
	);

	/**
	 * HPOS supports meta query
	 */
	if ( \Vendidero\OrderWithdrawalButton\Package::is_hpos_enabled() ) {
		unset( $query_args_custom['order_number'] );

		$query_args_custom['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => $meta_field_name,
				'value'   => $args['order_id'],
				'compare' => '=',
			),
		);
	}

	if ( ! empty( $args['email'] ) ) {
		$query_args_custom['billing_email'] = $args['email'];
	} elseif ( ! empty( $args['customer_id'] ) ) {
		$query_args_custom['customer_id'] = $args['customer_id'];
	}

	$orders = wc_get_orders( apply_filters( 'eu_owb_woocommerce_find_order_alternate_order_query_args', $query_args_custom ) );

	remove_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $custom_query_cpt_cb, 10 );

	return apply_filters( 'eu_owb_woocommerce_find_orders_by_custom_order_number', $orders, $args );
}

/**
 * @param $order_id
 * @param $email
 *
 * @return WC_Order[]
 */
function eu_owb_find_orders( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'order_id'    => '',
			'email'       => '',
			'customer_id' => '',
			'return'      => 'ids',
		)
	);

	$args['order_id']    = sanitize_text_field( $args['order_id'] );
	$args['email']       = sanitize_email( $args['email'] );
	$args['customer_id'] = absint( $args['customer_id'] );

	if ( ! empty( $args['email'] ) && empty( $args['customer_id'] ) ) {
		if ( $user = get_user_by( 'email', $args['email'] ) ) {
			$args['customer_id'] = absint( $user->ID );
		}
	}

	$orders          = array();
	$order_id_parsed = eu_owb_get_order_id_from_string( $args['order_id'] );

	/**
	 * Need to find all possible orders here (no matter which status) to prevent
	 * withdrawal requests for orders that have already been processed (confirmed, rejected).
	 */
	$main_query_args = array(
		'limit'   => 10,
		'return'  => $args['return'],
		'orderby' => 'date_created',
		'status'  => array_keys( wc_get_order_statuses() ),
	);

	if ( empty( $order_id_parsed ) && empty( $args['email'] ) && empty( $args['customer_id'] ) ) {
		return $orders;
	}

	if ( ! empty( $order_id_parsed ) ) {
		$main_query_args['post__in'] = array( $order_id_parsed );
		$main_query_args['limit']    = 1;
	}

	if ( ! empty( $args['email'] ) ) {
		$main_query_args['billing_email'] = $args['email'];
	} elseif ( ! empty( $args['customer_id'] ) ) {
		$main_query_args['customer_id'] = $args['customer_id'];
	}

	/**
	 * First, find orders by order id (if available) and/or billing email (if available).
	 */
	if ( ! empty( $order_id_parsed ) || ! empty( $args['email'] ) ) {
		$orders = wc_get_orders( apply_filters( 'eu_owb_woocommerce_find_order_query_args', $main_query_args ) );
	}

	// Now lets try to find the order by a custom order number field
	if ( empty( $orders ) && ! empty( $order_id_parsed ) ) {
		$orders = eu_owb_find_orders_by_custom_order_number( $args );
	}

	/**
	 * If no order id has been set, query orders where billing email differs from customer email
	 */
	if ( ( empty( $orders ) || empty( $args['order_id'] ) ) && ! empty( $args['customer_id'] ) ) {
		$user_query_args = array(
			'limit'       => 10,
			'return'      => $args['return'],
			'customer_id' => $args['customer_id'],
			'status'      => array_keys( wc_get_order_statuses() ),
		);

		$orders = array_unique( array_merge( $orders, wc_get_orders( apply_filters( 'eu_owb_woocommerce_find_order_customer_query_args', $user_query_args ) ) ) );

		if ( ! empty( $args['order_id'] ) ) {
			if ( in_array( $order_id_parsed, $orders ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$orders = array_intersect( $orders, array( $order_id_parsed ) );
			} else {
				$orders = array();
			}
		}

		// Sort DESC by ID
		rsort( $orders );
	}

	/**
	 * As a fallback query by order id only
	 */
	if ( empty( $orders ) && ! empty( $args['order_id'] ) && ( ! empty( $args['email'] ) || ! empty( $args['customer_id'] ) ) ) {
		return eu_owb_find_orders(
			array(
				'order_id' => $args['order_id'],
				'return'   => $args['return'],
			)
		);
	}

	return apply_filters( 'eu_owb_woocommerce_find_orders', $orders, $args );
}

/**
 * Get HTML for the order items to be shown in emails.
 *
 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal Order object.
 * @param array    $args Arguments.
 *
 * @since 3.0.0
 * @return string
 */
function eu_owb_get_email_withdrawal_items( $withdrawal, $args = array() ) {
	ob_start();

	$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();
	$image_size                 = $email_improvements_enabled ? 48 : 32;

	/**
	 * Backwards compatibility
	 */
	if ( is_a( $withdrawal, 'WC_Order' ) ) {
		$withdrawal = eu_owb_get_withdrawal_or_request( $withdrawal );

		if ( isset( $args['withdrawal'] ) ) {
			$withdrawal = $args['withdrawal'];
		}

		if ( ! $withdrawal ) {
			return '';
		}
	}

	$defaults = array(
		'show_sku'      => false,
		'show_image'    => $email_improvements_enabled,
		'image_size'    => array( $image_size, $image_size ),
		'plain_text'    => false,
		'sent_to_admin' => false,
		'withdrawal'    => $withdrawal,
	);

	$args     = wp_parse_args( $args, $defaults );
	$template = $args['plain_text'] ? 'emails/plain/email-withdrawal-items.php' : 'emails/email-withdrawal-items.php';

	wc_get_template(
		$template,
		apply_filters(
			'eu_owb_woocommerce_email_withdrawal_items_args',
			array(
				'order'         => $args['withdrawal']->get_parent() ? $args['withdrawal']->get_parent() : $args['withdrawal'],
				'items'         => eu_owb_get_withdrawal_order_items( $args['withdrawal'] ),
				'show_sku'      => $args['show_sku'],
				'show_image'    => $args['show_image'],
				'image_size'    => $args['image_size'],
				'plain_text'    => $args['plain_text'],
				'sent_to_admin' => $args['sent_to_admin'],
				'withdrawal'    => $args['withdrawal'],
			)
		)
	);

	$html = ob_get_clean();

	return apply_filters( 'eu_owb_woocommerce_email_withdrawal_items_table', $html, $withdrawal );
}
