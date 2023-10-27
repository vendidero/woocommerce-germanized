<?php

namespace Vendidero\Germanized\Shipments;

use \Exception;
use WC_Order;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class FormHandler {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'add_return_shipment' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'return_request_success_message' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_return_request' ), 20 );
	}

	public static function return_request_success_message() {
		if ( isset( $_GET['return-request-success'], $_GET['needs-confirmation'] ) && 'yes' === $_GET['return-request-success'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wc_add_notice( self::get_return_request_success_message( wc_string_to_bool( wc_clean( wp_unslash( $_GET['needs-confirmation'] ) ) ) ), 'success' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	protected static function get_return_request_success_message( $needs_manual_confirmation = false ) {
		if ( $needs_manual_confirmation ) {
			$default_message = _x( 'Your return request was submitted successfully. We will now review your request and get in contact with you as soon as possible.', 'shipments', 'woocommerce-germanized' );
		} else {
			$default_message = _x( 'Your return request was submitted successfully. You\'ll receive an email with further instructions in a few minutes.', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * This filter may be used to adjust the default success message returned
		 * to the customer after successfully adding a return shipment.
		 *
		 * @param string  $message  The success message.
		 * @param boolean $needs_manual_confirmation Whether the request needs manual confirmation or not.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$success_message = apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_success_message', $default_message, $needs_manual_confirmation );

		return $success_message;
	}

	/**
	 * Parses a string and finds the longest, contiguous number which is assumed to be the order id.
	 *
	 * @param $order_id_str
	 *
	 * @return string
	 */
	public static function get_order_id_from_string( $order_id_str ) {
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

		return apply_filters( 'woocommerce_gzd_return_request_order_id_from_string', $order_id, $order_id_str );
	}

	public static function process_return_request() {
		$nonce_value = isset( $_REQUEST['woocommerce-gzd-return-request-nonce'] ) ? $_REQUEST['woocommerce-gzd-return-request-nonce'] : ''; // @codingStandardsIgnoreLine.

		if ( isset( $_POST['return_request'], $_POST['email'], $_POST['order_id'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-gzd-return-request' ) ) {
			try {
				$email       = sanitize_email( wp_unslash( $_POST['email'] ) );
				$order_id    = wc_clean( wp_unslash( $_POST['order_id'] ) );
				$db_order_id = self::find_order( $order_id, $email );

				if ( ! $db_order_id || ( ! $order = wc_get_order( $db_order_id ) ) ) {
					throw new Exception( '<strong>' . _x( 'Error:', 'shipments', 'woocommerce-germanized' ) . '</strong> ' . _x( 'We were not able to find a matching order.', 'shipments', 'woocommerce-germanized' ) );
				}

				if ( ! wc_gzd_order_is_customer_returnable( $order ) ) {
					throw new Exception( '<strong>' . _x( 'Error:', 'shipments', 'woocommerce-germanized' ) . '</strong> ' . _x( 'This order is currently not eligible for returns. Please contact us for further details.', 'shipments', 'woocommerce-germanized' ) );
				}

				$key = 'wc_gzd_order_return_request_' . wp_generate_password( 13, false );

				$order->update_meta_data( '_return_request_key', $key );
				$order->save();

				// Send email to customer
				wc_add_notice( _x( 'Thank you. You\'ll receive an email containing a link to create a new return to your order.', 'shipments', 'woocommerce-germanized' ), 'success' );

				WC()->mailer()->emails['WC_GZD_Email_Customer_Guest_Return_Shipment_Request']->trigger( $order );

				do_action( 'woocommerce_gzd_return_request_successful', $order );

			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
				do_action( 'woocommerce_gzd_return_request_failed' );
			}
		}
	}

	/**
	 * @param $order_id
	 * @param $email
	 *
	 * @return false|integer
	 */
	public static function find_order( $order_id, $email ) {
		$order_id_parsed = self::get_order_id_from_string( $order_id );
		$db_order_id     = false;
		$orders          = wc_get_orders(
			apply_filters(
				'woocommerce_gzd_return_request_order_query_args',
				array(
					'billing_email' => $email,
					'post__in'      => array( $order_id_parsed ),
					'limit'         => 1,
					'return'        => 'ids',
				)
			)
		);

		// Now lets try to find the order by a custom order number field
		if ( empty( $orders ) ) {
			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( __CLASS__, 'filter_query_by_order_number' ), 10, 2 );

			$orders = wc_get_orders(
				apply_filters(
					'woocommerce_gzd_return_request_alternate_order_query_args',
					array(
						'billing_email' => $email,
						'order_number'  => $order_id,
						'limit'         => 1,
						'return'        => 'ids',
					)
				)
			);

			remove_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( __CLASS__, 'filter_query_by_order_number' ), 10 );
		}

		if ( ! empty( $orders ) ) {
			$db_order_id = $orders[0];
		}

		return apply_filters( 'woocommerce_gzd_shipments_valid_order_for_return_request', $db_order_id, $order_id, $email );
	}

	public static function filter_query_by_order_number( $query, $query_vars ) {
		$meta_field_name = apply_filters( 'woocommerce_gzd_return_request_customer_order_number_meta_key', '_order_number' );

		if ( ! empty( $query_vars['order_number'] ) ) {
			$query['meta_query'][] = array(
				'key'     => $meta_field_name,
				'value'   => esc_attr( wc_clean( $query_vars['order_number'] ) ),
				'compare' => '=',
			);
		}

		return $query;
	}

	/**
	 * Save the password/account details and redirect back to the my account page.
	 */
	public static function add_return_shipment() {
		$nonce_value = isset( $_REQUEST['add-return-shipment-nonce'] ) ? wp_unslash( $_REQUEST['add-return-shipment-nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! wp_verify_nonce( $nonce_value, 'add_return_shipment' ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'gzd_add_return_shipment' !== $_POST['action'] ) {
			return;
		}

		wc_nocache_headers();

		$order_id  = ! empty( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : false;
		$items     = ! empty( $_POST['items'] ) ? wc_clean( wp_unslash( $_POST['items'] ) ) : array();
		$item_data = ! empty( $_POST['item'] ) ? wc_clean( wp_unslash( $_POST['item'] ) ) : array();

		if ( ! ( $order = wc_get_order( $order_id ) ) || ( ! wc_gzd_customer_can_add_return_shipment( $order_id ) ) ) {
			wc_add_notice( _x( 'You are not allowed to add returns to that order.', 'shipments', 'woocommerce-germanized' ), 'error' );
			return;
		}

		if ( ! wc_gzd_order_is_customer_returnable( $order ) ) {
			wc_add_notice( _x( 'Sorry, but this order does not support returns any longer.', 'shipments', 'woocommerce-germanized' ), 'error' );
			return;
		}

		if ( empty( $items ) ) {
			wc_add_notice( _x( 'Please choose one or more items from the list.', 'shipments', 'woocommerce-germanized' ), 'error' );
			return;
		}

		$return_items   = array();
		$shipment_order = wc_gzd_get_shipment_order( $order );

		foreach ( $items as $order_item_id ) {
			if ( $item = $shipment_order->get_simple_shipment_item( $order_item_id ) ) {
				$quantity            = isset( $item_data[ $order_item_id ]['quantity'] ) ? absint( $item_data[ $order_item_id ]['quantity'] ) : 0;
				$quantity_returnable = $shipment_order->get_item_quantity_left_for_returning( $order_item_id );
				$reason              = isset( $item_data[ $order_item_id ]['reason'] ) ? wc_clean( $item_data[ $order_item_id ]['reason'] ) : '';

				if ( ! empty( $reason ) && ! wc_gzd_return_shipment_reason_exists( $reason ) ) {
					wc_add_notice( _x( 'The return reason you have chosen does not exist.', 'shipments', 'woocommerce-germanized' ), 'error' );
					return;
				} elseif ( empty( $reason ) && ! wc_gzd_allow_customer_return_empty_return_reason( $order ) ) {
					wc_add_notice( _x( 'Please choose a return reason from the list.', 'shipments', 'woocommerce-germanized' ), 'error' );
					return;
				}

				if ( $quantity > $quantity_returnable ) {
					wc_add_notice( _x( 'Please check your item quantities. Quantities must not exceed maximum quantities.', 'shipments', 'woocommerce-germanized' ), 'error' );
					return;
				} else {
					$return_items[ $order_item_id ] = array(
						'quantity'           => $quantity,
						'return_reason_code' => $reason,
					);
				}
			}
		}

		if ( empty( $return_items ) ) {
			wc_add_notice( _x( 'Please choose one or more items from the list.', 'shipments', 'woocommerce-germanized' ), 'error' );
		}

		if ( wc_notice_count( 'error' ) > 0 ) {
			return;
		}

		$needs_manual_confirmation = wc_gzd_customer_return_needs_manual_confirmation( $order );

		if ( $needs_manual_confirmation ) {
			$default_status = 'requested';
		} else {
			$default_status = 'processing';
		}

		// Add return shipment
		$return_shipment = wc_gzd_create_return_shipment(
			$shipment_order,
			array(
				'items' => $return_items,
				'props' => array(
					/**
					 * This filter may be used to adjust the default status of a return shipment
					 * added by a customer.
					 *
					 * @param string    $status The default status.
					 * @param WC_Order $order The order object.
					 *
					 * @since 3.1.0
					 * @package Vendidero/Germanized/Shipments
					 */
					'status'                => apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_status', $default_status, $order ),
					'is_customer_requested' => true,
				),
			)
		);

		if ( is_wp_error( $return_shipment ) ) {
			wc_add_notice( _x( 'There was an error while creating the return. Please contact us for further information.', 'shipments', 'woocommerce-germanized' ), 'error' );
			return;
		} else {
			// Delete return request key if available
			$shipment_order->delete_order_return_request_key();

			$success_message = self::get_return_request_success_message( $needs_manual_confirmation );

			// Do not add success message for guest returns
			if ( $order->get_customer_id() > 0 ) {
				wc_add_notice( $success_message );
			}

			/**
			 * This hook is fired after a customer has added a new return request
			 * for a specific shipment. The return shipment object has been added successfully.
			 *
			 * @param ReturnShipment $shipment The return shipment object.
			 * @param WC_Order      $order The order object.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			do_action( 'woocommerce_gzd_new_customer_return_shipment_request', $return_shipment, $order );

			if ( $needs_manual_confirmation ) {
				$return_url = $order->get_view_order_url();
			} else {
				$return_url = $return_shipment->get_view_shipment_url();
			}

			if ( $order->get_customer_id() <= 0 ) {
				$return_url = add_query_arg(
					array(
						'return-request-success' => 'yes',
						'needs-confirmation'     => wc_bool_to_string( $needs_manual_confirmation ),
					),
					wc_get_page_permalink( 'myaccount' )
				);
			}

			/**
			 * This filter may be used to adjust the redirect of a customer
			 * after adding a new return shipment. In case the return request needs manual confirmation
			 * the customer will be redirected to the parent shipment.
			 *
			 * @param string         $url  The redirect URL.
			 * @param ReturnShipment $shipment The return shipment object.
			 * @param boolean        $needs_manual_confirmation Whether the request needs manual confirmation or not.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$redirect = apply_filters( 'woocommerce_gzd_customer_new_return_shipment_request_redirect', $return_url, $return_shipment, $needs_manual_confirmation );

			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit;
		}
	}
}
