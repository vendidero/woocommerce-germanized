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
			'order_withdrawal_request_supports_partial',
			'confirm_withdrawal',
			'reject_withdrawal',
			'delete_withdrawal',
			'json_search_orders',
			'save_withdrawal_order',
		);

		$ajax_nopriv_events = array(
			'order_withdrawal_request',
			'order_withdrawal_request_select_order',
			'order_withdrawal_request_supports_partial',
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

	public static function save_withdrawal_order() {
		check_ajax_referer( 'eu_owb_woocommerce_save_order', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$order_id = absint( isset( $_POST['order_id'] ) ? wp_unslash( $_POST['order_id'] ) : 0 );
		$props    = (array) wc_clean( isset( $_POST['props'] ) ? wp_unslash( $_POST['props'] ) : array() );
		$action   = wc_clean( isset( $_POST['inline_action'] ) ? wp_unslash( $_POST['inline_action'] ) : 'edit' );

		if ( ! empty( $order_id ) ) {
			$order = eu_owb_get_withdrawal( $order_id );

			if ( ! $order ) {
				wp_die();
			}

			if ( 'reject' === $action ) {
				$props = wp_parse_args(
					$props,
					array(
						'rejection_reason' => '',
					)
				);

				eu_owb_order_reject_withdrawal_request( $order, $props['rejection_reason'] );
			} elseif ( ! empty( $props ) ) {
				$order->set_props( $props );
				$order->save();
			}

			wp_send_json_success(
				array(
					'success' => true,
				)
			);
		}

		wp_send_json_error();
	}

	public static function json_search_orders() {
		check_ajax_referer( 'eu_owb_woocommerce_search_orders', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$term  = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';
		$limit = 0;

		if ( empty( $term ) ) {
			wp_die();
		}

		if ( Package::is_hpos_enabled() ) {
			$ids = wc_get_orders( array( 's' => $term ) );
		} elseif ( ! is_numeric( $term ) ) {
			$ids = wc_get_orders( array( 's' => $term ) );
		} else {
			global $wpdb;

			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT p1.ID FROM {$wpdb->posts} p1 WHERE p1.ID LIKE %s AND post_type = 'shop_order'", // @codingStandardsIgnoreLine
					$wpdb->esc_like( wc_clean( $term ) ) . '%'
				)
			);
		}

		$excluded     = array();
		$found_orders = array();

		if ( ! empty( $_GET['exclude'] ) ) {
			$excluded = array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) );
		}

		foreach ( $ids as $id ) {
			if ( $order = wc_get_order( $id ) ) {
				if ( in_array( absint( $order->get_id() ), $excluded, true ) ) {
					continue;
				}

				if ( ! eu_owb_order_is_withdrawable( $order ) ) {
					continue;
				}

				$found_orders[ $order->get_id() ] = sprintf(
					esc_html_x( 'Order #%s', 'owb', 'woocommerce-germanized' ),
					$order->get_order_number()
				);
			}
		}

		wp_send_json( $found_orders );
	}

	public static function delete_withdrawal() {
		check_ajax_referer( 'eu_owb_woocommerce_delete_withdrawal' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die();
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;

		if ( current_user_can( 'edit_shop_orders' ) ) {
			if ( $withdrawal = eu_owb_get_withdrawal( $order_id ) ) {
				$withdrawal->delete();
			}
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) ) );
		die();
	}

	public static function confirm_withdrawal() {
		check_ajax_referer( 'eu_owb_woocommerce_confirm_withdrawal' );

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;

		if ( current_user_can( 'edit_shop_orders' ) ) {
			if ( $withdrawal = eu_owb_get_withdrawal( $order_id ) ) {
				eu_owb_order_confirm_withdrawal_request( $withdrawal );
			}
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) ) );
		die();
	}

	public static function reject_withdrawal() {
		check_ajax_referer( 'eu_owb_woocommerce_reject_withdrawal' );

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		$reason   = isset( $_GET['reason'] ) ? sanitize_textarea_field( wp_unslash( $_GET['reason'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( current_user_can( 'edit_shop_orders' ) ) {
			if ( $withdrawal = eu_owb_get_withdrawal( $order_id ) ) {
				eu_owb_order_reject_withdrawal_request( $withdrawal, $reason );
			}
		}

		wp_safe_redirect( esc_url_raw( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) ) );
		die();
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return boolean
	 */
	protected static function current_request_can_view_order( $order ) {
		$order_key         = ! empty( $_POST['order_key'] ) ? wp_unslash( $_POST['order_key'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$original_order_id = ! empty( $_POST['original_order_id'] ) ? absint( wp_unslash( $_POST['original_order_id'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$is_valid_request  = false;

		if ( ! $order ) {
			return false;
		}

		if ( is_user_logged_in() && current_user_can( 'view_order', $order->get_id() ) ) {
			$is_valid_request = true;
		} elseif ( ! is_user_logged_in() && $original_order_id && ! empty( $order_key ) ) {
			if ( $original_order = wc_get_order( $original_order_id ) ) {
				if ( $original_order->get_id() === $order->get_id() && ! empty( $order->get_order_key() ) && hash_equals( $order->get_order_key(), $order_key ) ) {
					$is_valid_request = true;
				} elseif ( $original_order->get_id() === $original_order_id && ! empty( $original_order->get_order_key() ) && hash_equals( $original_order->get_order_key(), $order_key ) ) {
					$orders = eu_owb_find_orders(
						array(
							'email'       => $original_order->get_billing_email(),
							'customer_id' => $original_order->get_customer_id(),
							'status'      => eu_owb_get_withdrawable_order_statuses(),
						)
					);

					if ( in_array( $order->get_id(), $orders, true ) ) {
						$is_valid_request = true;
					}
				}
			}
		}

		return $is_valid_request;
	}

	public static function order_withdrawal_request_select_order() {
		check_ajax_referer( 'eu_owb_woocommerce_order_withdrawal_request' );

		$error            = new \WP_Error();
		$order_id         = ! empty( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
		$order            = $order_id > 0 ? wc_get_order( $order_id ) : null;
		$is_valid_request = self::current_request_can_view_order( $order );

		if ( ! $is_valid_request ) {
			$error->add( 'request_not_allowed', _x( 'Sorry, no permission to view that order.', 'owb', 'woocommerce-germanized' ) );
			wp_send_json_error( $error, 401 );
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

	public static function order_withdrawal_request_supports_partial() {
		check_ajax_referer( 'eu_owb_woocommerce_order_withdrawal_request' );

		$email        = ! empty( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$order_number = ! empty( $_POST['order_number'] ) ? wc_clean( wp_unslash( $_POST['order_number'] ) ) : '';

		if ( ! empty( $order_number ) && ! empty( $email ) ) {
			$orders = eu_owb_find_orders(
				array(
					'email'    => $email,
					'order_id' => $order_number,
				)
			);

			if ( 1 === count( $orders ) ) {
				$order_id = $orders[0];

				if ( $order = wc_get_order( $order_id ) ) {
					if ( eu_owb_custom_email_matches_order_email( $order, $email ) && eu_owb_order_supports_partial_withdrawal( $order_id ) ) {
						wp_send_json_success(
							array(
								'supports_partial_withdrawal' => true,
							)
						);
					}
				}
			}
		}

		wp_send_json_error( '', 500 );
	}

	public static function order_withdrawal_request() {
		check_ajax_referer( 'eu_owb_woocommerce_order_withdrawal_request' );

		$order            = false;
		$items            = array();
		$error            = new \WP_Error();
		$is_valid_request = false;
		$was_guest        = true;
		$meta             = array();
		$customer_id      = 0;
		$start            = ! empty( $_POST['start_timestamp'] ) ? absint( $_POST['start_timestamp'] ) : 0;
		$end              = ! empty( $_POST['end_timestamp'] ) ? absint( $_POST['end_timestamp'] ) : 0;
		$order_key        = ! empty( $_POST['order_key'] ) ? wp_unslash( $_POST['order_key'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email            = ! empty( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$email_repeat     = ! empty( $_POST['email_repeat'] ) ? wp_unslash( $_POST['email_repeat'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$first_name       = ! empty( $_POST['first_name'] ) ? wc_clean( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name        = ! empty( $_POST['last_name'] ) ? wc_clean( wp_unslash( $_POST['last_name'] ) ) : '';

		if ( -1 !== Package::get_form_field_maxlength( 'first_name' ) ) {
			$first_name = Package::substr( $first_name, 0, Package::get_form_field_maxlength( 'first_name' ) );
		}

		if ( -1 !== Package::get_form_field_maxlength( 'last_name' ) ) {
			$last_name = Package::substr( $last_name, 0, Package::get_form_field_maxlength( 'last_name' ) );
		}

		do_action( 'eu_owb_woocommerce_before_process_order_withdrawal_request' );

		if ( eu_owb_wp_error_has_errors( $error ) ) {
			self::send_json_error( $error, 400 );
		}

		$is_direct_post = ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'];

		/**
		 * Show a success message as spam protection in case honeypot field or non-direct post was submitted.
		 */
		if ( ! empty( $email_repeat ) || $is_direct_post ) {
			self::send_json_error( _x( 'Thank you. We\'ve received your withdrawal request. You\'ll receive a confirmation of your request by email.', 'owb', 'woocommerce-germanized' ) );
		}

		$duration = apply_filters( 'eu_owb_woocommerce_form_submit_spam_min_duration_in_secs', 2 );

		// If the form was submitted too quickly, add an error.
		if ( ( $end - $start ) < $duration || 0 === $start ) {
			$error->add( 'too_quick', _x( 'Please wait a little longer before submitting. We’re running a quick security check.', 'owb', 'woocommerce-germanized' ) );
			self::send_json_error( $error, 400 );
		}

		if ( Package::get_form_field_required( 'first_name' ) && empty( $first_name ) ) {
			$error->add( 'missing_field_first_name', _x( 'Please enter your first name.', 'owb', 'woocommerce-germanized' ), array( 'field' => 'first_name' ) );
		}

		if ( Package::get_form_field_required( 'last_name' ) && empty( $last_name ) ) {
			$error->add( 'missing_field_last_name', _x( 'Please enter your last name.', 'owb', 'woocommerce-germanized' ), array( 'field' => 'last_name' ) );
		}

		if ( empty( $email ) ) {
			$error->add( 'missing_field_email', _x( 'Please check your email address.', 'owb', 'woocommerce-germanized' ), array( 'field' => 'email' ) );
		}

		if ( is_user_logged_in() || ! empty( $order_key ) ) {
			$order_id          = ! empty( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
			$original_order_id = ! empty( $_POST['original_order_id'] ) ? absint( wp_unslash( $_POST['original_order_id'] ) ) : false;
			$select_items      = isset( $_POST['manually_select_items'] ) ? true : false;
			$item_ids          = $select_items && ! empty( $_POST['items'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['items'] ) ) : array();
			$item_data         = $select_items && ! empty( $_POST['item'] ) ? wc_clean( (array) wp_unslash( $_POST['item'] ) ) : array();
			$order             = wc_get_order( $order_id );
			$original_order    = $original_order_id ? wc_get_order( $original_order_id ) : null;
			$was_guest         = false;
			$customer_id       = is_user_logged_in() ? get_current_user_id() : 0;

			if ( $order ) {
				$delete_original  = isset( $_POST['delete_original_request'] ) && $original_order->get_id() !== $order->get_id() ? true : false;
				$is_valid_request = self::current_request_can_view_order( $order );

				if ( $is_valid_request && $original_order && $delete_original ) {
					if ( eu_owb_get_withdrawal_request( $original_order ) ) {
						$meta['original_request_order_id'] = $original_order->get_id();
						eu_owb_order_delete_withdrawal_request( $original_order, true );
					}
				}

				if ( $is_valid_request && eu_owb_order_supports_partial_withdrawal( $order ) ) {
					if ( $select_items && empty( $item_ids ) ) {
						$error->add( 'invalid_items', _x( 'Please select one or more items to withdraw.', 'owb', 'woocommerce-germanized' ) );
						self::send_json_error( $error, 400 );
					} elseif ( ! empty( $item_ids ) ) {
						$items_available = eu_owb_get_withdrawable_order_items( $order );

						foreach ( $item_ids as $item_id ) {
							$quantity = isset( $item_data[ $item_id ]['quantity'] ) ? (float) wc_format_decimal( $item_data[ $item_id ]['quantity'] ) : 0;

							if ( $quantity <= 0 ) {
								continue;
							}

							if ( ! array_key_exists( $item_id, $items_available ) ) {
								$error->add( 'invalid_items', _x( 'One ore more of the item(s) you\'ve selected cannot be withdrawn. Please try again.', 'owb', 'woocommerce-germanized' ) );
								self::send_json_error( $error, 400 );
							}

							$quantity = min( $quantity, $items_available[ $item_id ]['quantity'] );

							$items[ $item_id ] = array(
								'quantity' => $quantity,
							);
						}
					}
				}

				do_action( 'eu_owb_woocommerce_process_order_withdrawal_customer_request', $order, $items, $error );

				if ( eu_owb_wp_error_has_errors( $error ) ) {
					self::send_json_error( $error, 400 );
				}
			}
		} else {
			$order_number           = ! empty( $_POST['order_number'] ) ? wc_clean( wp_unslash( $_POST['order_number'] ) ) : '';
			$additional_information = eu_owb_enable_additional_information_field() && ! empty( $_POST['additional_information'] ) ? wc_sanitize_textarea( wp_unslash( $_POST['additional_information'] ) ) : '';
			$select_items           = isset( $_POST['manually_select_items'] ) ? true : false;

			if ( -1 !== Package::get_form_field_maxlength( 'order_number' ) ) {
				$order_number = Package::substr( $order_number, 0, Package::get_form_field_maxlength( 'order_number' ) );
			}

			if ( -1 !== Package::get_form_field_maxlength( 'additional_information' ) ) {
				$additional_information = Package::substr( $additional_information, 0, Package::get_form_field_maxlength( 'additional_information' ) );
			}

			if ( Package::get_form_field_required( 'order_number' ) && empty( $order_number ) ) {
				$error->add( 'missing_field_order_number', _x( 'Please enter your order number.', 'owb', 'woocommerce-germanized' ), array( 'field' => 'order_number' ) );
			}

			if ( eu_owb_enable_additional_information_field() && Package::get_form_field_required( 'additional_information' ) && empty( $additional_information ) ) {
				$error->add( 'missing_field_additional_information', _x( 'Please enter additional information.', 'owb', 'woocommerce-germanized' ), array( 'field' => 'additional_information' ) );
			}

			if ( eu_owb_wp_error_has_errors( $error ) ) {
				self::send_json_error( $error, 400 );
			}

			$meta['order_number'] = $order_number;

			if ( ! empty( $additional_information ) ) {
				$meta['additional_information'] = $additional_information;
			}

			$orders = eu_owb_find_orders(
				array(
					'email'    => $email,
					'order_id' => $order_number,
				)
			);

			if ( ! empty( $orders ) ) {
				if ( count( $orders ) > 1 ) {
					$orders_withdrawable = eu_owb_get_withdrawable_orders( $orders, true );

					if ( empty( $orders_withdrawable ) ) {
						$error->add( 'not_found', sprintf( _x( 'Sorry, we were unable to find an order based on the information you provided. Please try again - if the issue persists, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ), array( 'field' => 'order_number' ) );
						self::send_json_error( $error, 404 );
					}

					if ( count( $orders_withdrawable ) > 1 ) {
						$meta['has_multiple_matching_orders'] = 'yes';
					}

					$order_id = $orders_withdrawable[0];
				} else {
					$order_id = $orders[0];
				}

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					$error->add( 'not_found', sprintf( _x( 'Sorry, we were unable to find an order based on the information you provided. Please try again - if the issue persists, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ), array( 'field' => 'order_number' ) );
					self::send_json_error( $error, 404 );
				}

				/**
				 * Prevent non-authorized users from overriding pending withdrawals.
				 */
				if ( $original_request = eu_owb_get_withdrawal_request( $order ) ) {
					if ( ! eu_owb_email_can_override_withdrawal_request( $email, $original_request ) ) {
						$order = false;
					}
				}
			}

			$is_valid_request = true;

			$meta['requested_partial'] = wc_bool_to_string( true === $select_items );

			do_action( 'eu_owb_woocommerce_process_order_withdrawal_guest_request', $order, $error, $select_items );
		}

		if ( ! empty( $first_name ) ) {
			$meta['first_name'] = $first_name;
		}

		if ( ! empty( $last_name ) ) {
			$meta['last_name'] = $last_name;
		}

		$meta['customer_id'] = $customer_id;

		do_action( 'eu_owb_woocommerce_process_order_withdrawal_request', $order, $error, $items, $meta, $was_guest );

		if ( eu_owb_wp_error_has_errors( $error ) ) {
			self::send_json_error( $error, 400 );
		}

		if ( ! $is_valid_request ) {
			$error->add( 'not_found', sprintf( _x( 'Sorry, we were unable to find an order based on the information you provided. Please try again - if the issue persists, please <a href="%s">contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
			self::send_json_error( $error, 404 );
		}

		if ( $order && ! eu_owb_order_is_withdrawable( $order ) ) {
			$error->add( 'not_withdrawable', sprintf( _x( 'Sorry, but this order cannot be withdrawn. <a href="%s">Contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
			self::send_json_error( $error, 400 );
		}

		$existing_request = false;

		if ( $order ) {
			$existing_request = eu_owb_get_withdrawal_request( $order );
		} elseif ( ! empty( $meta['order_number'] ) ) {
			$existing_request = eu_owb_get_withdrawal_request_by_order_number( $meta['order_number'] );
		}

		/**
		 * Let's see whether the new request has any changes.
		 */
		if ( $existing_request ) {
			$tmp_request = clone $existing_request;

			$tmp_request->set_email( $email );
			$tmp_request->set_first_name( $first_name );
			$tmp_request->set_last_name( $last_name );
			$tmp_request->set_additional_information( $additional_information );
			$tmp_request->update_items( $items );

			if ( ! $tmp_request->has_changes() ) {
				$error->add( 'not_withdrawable', _x( 'You\'ve already submitted an identical withdrawal request for this order.', 'owb', 'woocommerce-germanized' ) );
				self::send_json_error( $error, 400 );
			}
		}

		/**
		 * By default, allow max 5 unverified requests per IP/day.
		 */
		if ( ! eu_owb_custom_email_matches_order_email( $order, $email ) ) {
			$max_tries_per_day = apply_filters( 'eu_owb_woocommerce_max_unverified_requests', current_user_can( 'manage_woocommerce' ) ? -1 : 5 );

			if ( -1 !== (int) $max_tries_per_day ) {
				$ip_address = \WC_Geolocation::get_ip_address();

				if ( ! empty( $ip_address ) && '::1' !== $ip_address && '127.0.0.1' !== $ip_address ) {
					$transient_key = 'eu_owb_unverified_requests_' . md5( $ip_address );
					$current_tries = get_transient( $transient_key );

					if ( false === $current_tries ) {
						$current_tries = 0;
					}

					$current_tries = absint( $current_tries ) + 1;

					if ( $current_tries > $max_tries_per_day ) {
						$error->add( 'unverified-request-error', sprintf( _x( 'You\'ve submitted too many different unverified withdrawal requests. <a href="%s">Contact support</a> for help.', 'owb', 'woocommerce-germanized' ), esc_url( eu_owb_get_contact_support_url() ) ) );
					} else {
						set_transient( $transient_key, $current_tries, apply_filters( 'eu_owb_woocommerce_max_unverified_requests_interval', DAY_IN_SECONDS ) );
					}
				}
			}
		}

		if ( eu_owb_wp_error_has_errors( $error ) ) {
			self::send_json_error( $error, 400 );
		}

		$meta   = apply_filters( 'eu_owb_woocommerce_order_withdrawal_request_additional_meta', $meta, $order, $email, $items, $was_guest );
		$result = eu_owb_create_order_withdrawal_request( $email, $order, $items, $was_guest, $meta );

		if ( is_wp_error( $result ) ) {
			self::send_json_error( $error, 500 );
		} else {
			wp_send_json_success( _x( 'Thank you. We\'ve received your withdrawal request. You\'ll receive a confirmation of your request by email.', 'owb', 'woocommerce-germanized' ) );
		}
	}

	/**
	 * @param \WP_Error $value
	 * @param $status_code
	 * @param $flags
	 *
	 * @return void
	 */
	protected static function send_json_error( $value = null, $status_code = null, $flags = 0 ) {
		$response = array( 'success' => false );

		if ( isset( $value ) ) {
			if ( is_wp_error( $value ) ) {
				$result = array();
				foreach ( $value->errors as $code => $messages ) {
					$error_data = (array) $value->get_error_data( $code );

					foreach ( $messages as $k => $message ) {
						$result[] = array(
							'code'    => $code,
							'message' => $message,
							'field'   => isset( $error_data['field'] ) ? $error_data['field'] : '',
						);
					}
				}

				$response['data'] = $result;
			} else {
				$response['data'] = $value;
			}
		}

		wp_send_json( $response, $status_code, $flags );
	}
}
