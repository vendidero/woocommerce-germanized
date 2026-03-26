<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Ajax class.
 */
class Ajax {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'order_withdrawal_request',
			'order_withdrawal_request_select_order',
			'confirm_withdrawal_request',
		);

		$ajax_nopriv_events = array(
			'order_withdrawal_request',
			'order_withdrawal_request_select_order',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_eu_owb_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( in_array( $ajax_event, $ajax_nopriv_events, true ) ) {
				add_action( 'wp_ajax_nopriv_eu_owb_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				add_action( 'wc_ajax_eu_owb_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}

		add_action( 'admin_post_eu_owb_woocommerce_delete_withdrawal', array( __CLASS__, 'delete_withdrawal' ) );
	}

	public static function delete_withdrawal() {
		check_ajax_referer( 'eu_owb_woocommerce_delete_withdrawal' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die();
		}

		$order_id      = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		$withdrawal_id = isset( $_GET['withdrawal_id'] ) ? wc_clean( wp_unslash( $_GET['withdrawal_id'] ) ) : '';

		if ( $order = wc_get_order( $order_id ) ) {
			if ( $withdrawal = eu_owb_get_order_withdrawal( $order_id, $withdrawal_id ) ) {
				$withdrawals = eu_owb_get_order_withdrawals( $order_id );
				$has_deleted = false;

				foreach ( $withdrawals as $k => $org_withdrawal ) {
					if ( $withdrawal_id === $org_withdrawal['id'] ) {
						unset( $withdrawals[ $k ] );
						$has_deleted = true;
						break;
					}
				}

				if ( $has_deleted ) {
					foreach ( $withdrawal['items'] as $item_id => $quantity ) {
						if ( $item = $order->get_item( $item_id ) ) {
							$quantities = array_filter( (array) $item->get_meta( '_withdrawn_quantities', true ) );

							if ( array_key_exists( $withdrawal_id, $quantities ) ) {
								unset( $quantities[ $withdrawal_id ] );
								$total_quantity = array_sum( $quantities );

								$item->update_meta_data( '_withdrawn_quantities', $quantities );
								$item->update_meta_data( '_withdrawn_quantity', $total_quantity );
								$item->save();
							}
						}
					}

					$order->update_meta_data( '_is_full_withdrawal', 'no' );
					$order->update_meta_data( '_withdrawals', $withdrawals );
					$order->save();

					do_action( 'eu_owb_woocommerce_deleted_withdrawal', $withdrawal, $order );
				}
			}

			wp_safe_redirect( esc_url_raw( $order->get_edit_order_url() ) );
			exit;
		}
	}

	public static function confirm_withdrawal_request() {
		check_ajax_referer( 'eu_owb_woocommerce_confirm_withdrawal_request' );

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;

		if ( current_user_can( 'edit_shop_orders' ) ) {
			if ( $order = wc_get_order( $order_id ) ) {
				eu_owb_order_confirm_withdrawal_request( $order );
			}
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) ) );
		die();
	}

	public static function order_withdrawal_request_select_order() {
		check_ajax_referer( 'eu_owb_woocommerce_order_withdrawal_request' );

		$error    = new \WP_Error();
		$order_id = ! empty( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
		$order    = $order_id > 0 ? wc_get_order( $order_id ) : null;

		if ( ! is_user_logged_in() || ! $order || ! current_user_can( 'view_order', $order->get_id() ) ) {
			$error->add( 'request_not_allowed', _x( 'Sorry, no permission to view that order.', 'owb', 'woocommerce-germanized' ) );
			wp_send_json_error( $error, 500 );
		}

		$html = wc_get_template_html(
			'forms/order-withdrawal-request-item-select.php',
			array(
				'order'                 => $order,
				'manually_select_items' => false,
			)
		);

		wp_send_json(
			array(
				'html' => $html,
			)
		);
	}

	public static function order_withdrawal_request() {
		check_ajax_referer( 'eu_owb_woocommerce_order_withdrawal_request' );

		$order            = false;
		$items            = array();
		$error            = new \WP_Error();
		$is_valid_request = false;
		$email            = '';
		$was_guest        = true;
		$meta             = array();

		if ( is_user_logged_in() || ! empty( $order_key ) ) {
			$order_key    = ! empty( $_POST['order_key'] ) ? wp_unslash( $_POST['order_key'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order_id     = ! empty( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
			$select_items = isset( $_POST['manually_select_items'] ) ? true : false;
			$item_ids     = $select_items && ! empty( $_POST['items'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['items'] ) ) : array();
			$item_data    = $select_items && ! empty( $_POST['item'] ) ? wc_clean( (array) wp_unslash( $_POST['item'] ) ) : array();
			$order        = wc_get_order( $order_id );
			$was_guest    = false;

			if ( $order ) {
				if ( $order->get_id() === $order_id && ! empty( $order->get_order_key() ) && hash_equals( $order->get_order_key(), $order_key ) ) {
					$is_valid_request = true;
				} elseif ( is_user_logged_in() && current_user_can( 'view_order', $order->get_id() ) ) {
					$is_valid_request = true;
				}

				if ( $is_valid_request && eu_owb_order_supports_partial_withdrawal( $order ) ) {
					if ( $select_items && empty( $item_ids ) ) {
						$error->add( 'invalid_items', _x( 'Please select one or more items to withdraw.', 'owb', 'woocommerce-germanized' ) );
						wp_send_json_error( $error, 500 );
					} elseif ( ! empty( $item_ids ) ) {
						$items_available = eu_owb_get_withdrawable_order_items( $order );

						foreach ( $item_ids as $item_id ) {
							$quantity = isset( $item_data[ $item_id ]['quantity'] ) ? (float) wc_format_decimal( $item_data[ $item_id ]['quantity'] ) : 0;

							if ( $quantity <= 0 ) {
								continue;
							}

							if ( ! array_key_exists( $item_id, $items_available ) ) {
								$error->add( 'invalid_items', _x( 'One ore more of the item(s) you\'ve selected cannot be withdrawn. Please try again.', 'owb', 'woocommerce-germanized' ) );
								wp_send_json_error( $error, 500 );
							}

							$quantity = min( $quantity, $items_available[ $item_id ]['quantity'] );

							$items[ $item_id ] = array(
								'quantity' => $quantity,
							);
						}
					}

					do_action( 'eu_owb_woocommerce_process_order_withdrawal_customer_request', $order, $items, $error );

					if ( eu_owb_wp_error_has_errors( $error ) ) {
						wp_send_json_error( $error, 500 );
					}
				}
			}
		} else {
			$email        = ! empty( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			$order_number = ! empty( $_POST['order_number'] ) ? wc_clean( wp_unslash( $_POST['order_number'] ) ) : '';

			if ( empty( $order_number ) || empty( $email ) ) {
				if ( ! empty( $_POST['email'] ) ) {
					$error->add( 'missing_fields', _x( 'Please check your email address.', 'owb', 'woocommerce-germanized' ) );
				} else {
					$error->add( 'missing_fields', _x( 'Please fill out all required fields.', 'owb', 'woocommerce-germanized' ) );
				}

				wp_send_json_error( $error, 500 );
			}

			// Search by order_id/number
			$order_id = eu_owb_find_order( $order_number, $email );

			if ( empty( $order_id ) ) {
				$error->add( 'not_found', sprintf( _x( 'Sorry, we were unable to find an order based on the information you provided. Please try again - if the issue persists, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
				wp_send_json_error( $error, 500 );
			}

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				$error->add( 'not_found', sprintf( _x( 'Sorry, we were unable to find an order based on the information you provided. Please try again - if the issue persists, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
				wp_send_json_error( $error, 500 );
			}

			$is_valid_request = true;

			do_action( 'eu_owb_woocommerce_process_order_withdrawal_guest_request', $order, $error );

			if ( eu_owb_wp_error_has_errors( $error ) ) {
				wp_send_json_error( $error, 500 );
			}
		}

		if ( ! $is_valid_request ) {
			$error->add( 'not_found', sprintf( _x( 'Sorry, we were unable to find an order based on the information you provided. Please try again - if the issue persists, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
			wp_send_json_error( $error, 500 );
		}

		if ( ! eu_owb_order_is_withdrawable( $order ) ) {
			$error->add( 'not_withdrawable', sprintf( _x( 'Sorry, but this order cannot be withdrawn. <a href="%s">Contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
			wp_send_json_error( $error, 500 );
		}

		$meta   = apply_filters( 'eu_owb_woocommerce_order_withdrawal_request_additional_meta', $meta, $order, $email, $items, $was_guest );
		$result = eu_owb_create_order_withdrawal_request( $order, $email, $items, $was_guest, $meta );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $error, 500 );
		} else {
			wp_send_json_success( _x( 'Thank you. We\'ve received your withdrawal request. You\'ll receive a confirmation of your request by email.', 'owb', 'woocommerce-germanized' ) );
		}
	}
}
