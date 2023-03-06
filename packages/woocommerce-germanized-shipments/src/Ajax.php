<?php

namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\Admin\Admin;
use Vendidero\Germanized\Shipments\Admin\MetaBox;
use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use Vendidero\Germanized\Shipments\ShippingProvider\Helper;

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
			'get_available_shipment_items',
			'get_available_return_shipment_items',
			'add_shipment_item',
			'add_return_shipment',
			'add_shipment',
			'remove_shipment',
			'remove_shipment_item',
			'refresh_shipment_packaging',
			'limit_shipment_item_quantity',
			'save_shipments',
			'sync_shipment_items',
			'validate_shipment_item_quantities',
			'json_search_orders',
			'json_search_shipping_provider',
			'update_shipment_status',
			'shipments_bulk_action_handle',
			'remove_shipping_provider',
			'sort_shipping_provider',
			'edit_shipping_provider_status',
			'create_shipment_label_form',
			'create_shipment_label',
			'remove_shipment_label',
			'send_return_shipment_notification_email',
			'confirm_return_request',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.display_errors_Blacklisted
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	public static function send_return_shipment_notification_email() {
		$success = false;

		if ( current_user_can( 'edit_shop_orders' ) && isset( $_REQUEST['shipment_id'] ) ) {

			if ( isset( $_GET['shipment_id'] ) ) {
				$referrer = check_admin_referer( 'send-return-shipment-notification' );
			} else {
				$referrer = check_ajax_referer( 'send-return-shipment-notification', 'security' );
			}

			if ( $referrer ) {
				$shipment_id = absint( wp_unslash( $_REQUEST['shipment_id'] ) );

				if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

					if ( 'return' === $shipment->get_type() ) {
						WC()->mailer()->emails['WC_GZD_Email_Customer_Return_Shipment']->trigger( $shipment_id );
						$success = true;
					}
				}
			}

			if ( isset( $_GET['shipment_id'] ) ) {
				wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-return-shipments' ) );
				exit;
			} else {
				if ( $success ) {
					wp_send_json(
						array(
							'success'  => true,
							'messages' => array(
								_x( 'Notification successfully sent to customer.', 'shipments', 'woocommerce-germanized' ),
							),
						)
					);
				} else {
					wp_send_json(
						array(
							'success'  => false,
							'messages' => array(
								_x( 'There was an error while sending the notification.', 'shipments', 'woocommerce-germanized' ),
							),
						)
					);
				}
			}
		}
	}

	public static function confirm_return_request() {
		$success = false;

		if ( current_user_can( 'edit_shop_orders' ) && isset( $_REQUEST['shipment_id'] ) ) {

			if ( isset( $_GET['shipment_id'] ) ) {
				$referrer = check_admin_referer( 'confirm-return-request' );
			} else {
				$referrer = check_ajax_referer( 'confirm-return-request', 'security' );
			}

			if ( $referrer ) {
				$shipment_id = absint( wp_unslash( $_REQUEST['shipment_id'] ) );

				if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
					if ( 'return' === $shipment->get_type() ) {

						if ( $shipment->confirm_customer_request() ) {
							$success = true;
						}
					}
				}
			}

			if ( isset( $_GET['shipment_id'] ) ) {
				wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-return-shipments' ) );
				exit;
			} else {
				if ( $success ) {
					wp_send_json(
						array(
							'success'       => true,
							'messages'      => array(
								_x( 'Return request confirmed successfully.', 'shipments', 'woocommerce-germanized' ),
							),
							'shipment_id'   => $shipment->get_id(),
							'needs_refresh' => true,
							'fragments'     => array(
								'div#shipment-' . $shipment_id => self::get_shipment_html( $shipment ),
							),
						)
					);
				} else {
					wp_send_json(
						array(
							'success'  => false,
							'messages' => array(
								_x( 'There was an error while confirming the request.', 'shipments', 'woocommerce-germanized' ),
							),
						)
					);
				}
			}
		}
	}

	public static function create_shipment_label_form() {
		check_ajax_referer( 'create-shipment-label-form', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$shipment_id    = absint( $_POST['shipment_id'] );
		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error creating the label.', 'shipments', 'woocommerce-germanized' ),
			),
		);

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( $shipment->supports_label() && $shipment->needs_label() ) {
			$html = $shipment->get_label_settings_html();
		}

		$response = array(
			'fragments'   => array(
				'.wc-gzd-shipment-create-label' => '<div class="wc-gzd-shipment-create-label">' . $html . '</div>',
			),
			'shipment_id' => $shipment_id,
			'success'     => true,
		);

		wp_send_json( $response );
	}

	public static function remove_shipment_label() {
		check_ajax_referer( 'remove-shipment-label', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error deleting the label.', 'shipments', 'woocommerce-germanized' ),
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $label = $shipment->get_label() ) {
			wp_send_json( $response_error );
		}

		if ( $shipment->delete_label( true ) ) {
			$response = array(
				'success'       => true,
				'shipment_id'   => $shipment->get_id(),
				'needs_refresh' => true,
				'fragments'     => array(
					'div#shipment-' . $shipment_id => self::get_shipment_html( $shipment ),
				),
			);
		} else {
			wp_send_json( $response_error );
		}

		wp_send_json( $response );
	}

	public static function create_shipment_label() {
		check_ajax_referer( 'create-shipment-label', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error processing the label.', 'shipments', 'woocommerce-germanized' ),
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );
		$result      = false;

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( $shipment->supports_label() && $shipment->needs_label() ) {
			$data = array();

			foreach ( $_POST as $key => $value ) {
				if ( in_array( $key, array( 'action', 'security' ), true ) ) {
					continue;
				}

				$data[ $key ] = wc_clean( wp_unslash( $value ) );
			}

			$result = $shipment->create_label( $data );
		}

		if ( is_wp_error( $result ) ) {
			$result = wc_gzd_get_shipment_error( $result );
		}

		if ( is_wp_error( $result ) && ! $result->is_soft_error() ) {
			$response = array(
				'success'  => false,
				'messages' => $result->get_error_messages_by_type(),
			);
		} elseif ( $label = $shipment->get_label() ) {
			$order_shipment    = wc_gzd_get_shipment_order( $shipment->get_order() );
			$order_status_html = $order_shipment ? self::get_global_order_status_html( $order_shipment->get_order() ) : array();

			$response = array(
				'success'       => true,
				'label_id'      => $label->get_id(),
				'shipment_id'   => $shipment_id,
				'messages'      => is_wp_error( $result ) ? $result->get_error_messages_by_type() : array(),
				'needs_refresh' => true,
				'fragments'     => array(
					'div#shipment-' . $shipment_id         => self::get_shipment_html( $shipment ),
					'.order-shipping-status'               => $order_shipment ? self::get_order_status_html( $order_shipment ) : '',
					'.order-return-status'                 => $order_shipment ? self::get_order_return_status_html( $order_shipment ) : '',
					'.order_data_column p.wc-order-status' => ! empty( $order_status_html ) ? $order_status_html['status'] : '',
					'input[name=post_status]'              => ! empty( $order_status_html ) ? $order_status_html['input'] : '',
					'tr#shipment-' . $shipment_id . ' td.actions .wc-gzd-shipment-action-button-generate-label' => self::label_download_button_html( $label ),
				),
			);

			if ( empty( $response['fragments']['.order_data_column p.wc-order-status'] ) ) {
				unset( $response['fragments']['.order_data_column p.wc-order-status'] );
			}
		} else {
			$response = $response_error;
		}

		wp_send_json( $response );
	}

	protected static function get_shipment_html( $p_shipment, $p_is_active = true ) {
		$is_active = $p_is_active;
		$shipment  = $p_shipment;

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment.php';
		$html = ob_get_clean();

		return $html;
	}

	protected static function get_label_html( $p_shipment, $p_label = false ) {
		$shipment = $p_shipment;

		if ( $p_label ) {
			$label = $p_label;
		}

		ob_start();
		include Package::get_path() . '/includes/admin/views/label/html-shipment-label.php';
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * @param ShipmentLabel $label
	 *
	 * @return string
	 */
	protected static function label_download_button_html( $label ) {
		return '<a class="button wc-gzd-shipment-action-button wc-gzd-shipment-action-button-download-label download" href="' . esc_url( $label->get_download_url() ) . '" target="_blank" title="' . _x( 'Download label', 'shipments', 'woocommerce-germanized' ) . '">' . _x( 'Download label', 'shipments', 'woocommerce-germanized' ) . '</a>';
	}

	public static function edit_shipping_provider_status() {
		check_ajax_referer( 'edit-shipping-providers', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['provider'] ) || ! isset( $_POST['enable'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error while trying to save the shipping provider status.', 'shipments', 'woocommerce-germanized' ),
		);

		$provider = sanitize_key( wc_clean( wp_unslash( $_POST['provider'] ) ) );
		$enable   = wc_clean( wp_unslash( $_POST['enable'] ) );
		$helper   = Helper::instance();
		$response = array(
			'success'  => true,
			'provider' => $provider,
			'message'  => '',
		);

		$helper->load_shipping_providers();

		if ( $shipping_provider = $helper->get_shipping_provider( $provider ) ) {
			if ( 'yes' === $enable ) {
				$response['activated'] = 'yes';
				$shipping_provider->activate();
			} else {
				$response['activated'] = 'no';
				$shipping_provider->deactivate();
			}

			wp_send_json( $response );
		} else {
			wp_send_json( $response_error );
		}
	}

	public static function remove_shipping_provider() {
		check_ajax_referer( 'remove-shipping-provider', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['provider'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error while trying to delete the shipping provider.', 'shipments', 'woocommerce-germanized' ),
		);

		$provider = sanitize_key( wc_clean( wp_unslash( $_POST['provider'] ) ) );
		$helper   = Helper::instance();
		$response = array(
			'success'  => true,
			'provider' => $provider,
			'message'  => '',
		);

		$helper->load_shipping_providers();

		if ( $shipping_provider = $helper->get_shipping_provider( $provider ) ) {
			$shipping_provider->delete();
			wp_send_json( $response );
		} else {
			wp_send_json( $response_error );
		}
	}

	public static function sort_shipping_provider() {
		check_ajax_referer( 'sort-shipping-provider', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order'] ) ) {
			wp_die( -1 );
		}

		$order       = wc_clean( wp_unslash( $_POST['order'] ) );
		$order_count = 0;
		$helper      = Helper::instance();
		$response    = array(
			'success' => true,
			'message' => '',
		);

		$helper->load_shipping_providers();

		foreach ( $order as $shipping_provider_name ) {
			if ( $shipping_provider = $helper->get_shipping_provider( $shipping_provider_name ) ) {
				$shipping_provider->set_order( ++$order_count );
				$shipping_provider->save();
			}
		}

		wp_send_json( $response );
	}

	public static function shipments_bulk_action_handle() {
		$action = isset( $_POST['bulk_action'] ) ? wc_clean( wp_unslash( $_POST['bulk_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type   = isset( $_POST['type'] ) ? wc_clean( wp_unslash( $_POST['type'] ) ) : 'simple'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		check_ajax_referer( "woocommerce_gzd_shipments_{$action}", 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['step'] ) || ! isset( $_POST['ids'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error while bulk processing shipments.', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
		);

		$handlers = Admin::get_bulk_action_handlers();

		if ( ! array_key_exists( $action, $handlers ) ) {
			wp_send_json( $response_error );
		}

		$ids  = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();
		$step = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

		$handler = $handlers[ $action ];

		if ( 1 === $step ) {
			$handler->reset( true );
		}

		$handler->set_step( $step );
		$handler->set_ids( $ids );
		$handler->set_shipment_type( $type );

		$handler->handle();

		if ( $handler->get_percent_complete() >= 100 ) {
			$errors = $handler->get_notices( 'error' );

			if ( empty( $errors ) ) {
				$handler->add_notice( $handler->get_success_message(), 'success' );
				$handler->update_notices();
			}

			wp_send_json_success(
				array(
					'step'       => 'done',
					'percentage' => 100,
					'url'        => $handler->get_success_redirect_url(),
					'type'       => $handler->get_shipment_type(),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'step'       => ++$step,
					'percentage' => $handler->get_percent_complete(),
					'ids'        => $handler->get_ids(),
					'type'       => $handler->get_shipment_type(),
				)
			);
		}
	}

	/**
	 * @param Order $order
	 */
	private static function refresh_shipments( &$order ) {
		MetaBox::refresh_shipments( $order );
	}

	/**
	 * @param Order $order
	 * @param bool $shipment
	 */
	private static function refresh_shipment_items( &$order, &$shipment = false ) {
		MetaBox::refresh_shipment_items( $order, $shipment );
	}

	/**
	 * @param Order $order
	 */
	private static function refresh_status( &$order ) {
		MetaBox::refresh_status( $order );
	}

	public static function update_shipment_status() {
		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'update-shipment-status' ) && isset( $_GET['status'], $_GET['shipment_id'] ) ) {
			$status   = sanitize_text_field( wp_unslash( $_GET['status'] ) );
			$shipment = wc_gzd_get_shipment( absint( wp_unslash( $_GET['shipment_id'] ) ) );

			if ( wc_gzd_is_shipment_status( 'gzd-' . $status ) && $shipment ) {
				$shipment->update_status( $status, true );
				/**
				 * Action to indicate Shipment status change via WP Admin.
				 *
				 * @param integer $shipment_id The shipment id.
				 * @param string  $status The status to be switched to.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( 'woocommerce_gzd_updated_shipment_status', $shipment->get_id(), $status );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-shipments' ) );
		exit;
	}

	private static function get_shipment_ids( $shipments ) {
		return array_values(
			array_map(
				function( $s ) {
					return $s->get_id();
				},
				$shipments
			)
		);
	}

	public static function remove_shipment() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order() ) ) {
			wp_send_json( $response_error );
		}

		$shipment_ids = self::get_shipment_ids( $order_shipment->get_shipments() );

		if ( $shipment->delete( true ) ) {
			$order_shipment->remove_shipment( $shipment_id );

			if ( 'return' === $shipment->get_type() ) {
				$order_shipment->validate_shipments();
			}

			/*
			 * Check which shipments have been deleted (e.g. multiple in case a return has been removed)
			 */
			$shipments_removed       = array_values( array_diff( $shipment_ids, self::get_shipment_ids( $order_shipment->get_shipments() ) ) );
			$response['shipment_id'] = $shipments_removed;

			$response['fragments'] = array(
				'.order-shipping-status' => self::get_order_status_html( $order_shipment ),
				'.order-return-status'   => self::get_order_return_status_html( $order_shipment ),
			);

			self::send_json_success( $response, $order_shipment );
		} else {
			wp_send_json( $response_error );
		}
	}

	public static function add_shipment() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error while adding the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
		);

		$order_id = absint( $_POST['order_id'] );

		if ( ! $order = wc_get_order( $order_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		self::refresh_shipment_items( $order_shipment );

		if ( ! $order_shipment->needs_shipping() ) {
			$response_error['message'] = _x( 'This order contains enough shipments already.', 'shipments', 'woocommerce-germanized' );
			wp_send_json( $response_error );
		}

		$shipment = wc_gzd_create_shipment( $order_shipment );

		if ( is_wp_error( $shipment ) ) {
			wp_send_json( $response_error );
		}

		$order_shipment->add_shipment( $shipment );

		// Mark as active
		$is_active = true;

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment.php';
		$html = ob_get_clean();

		$response['new_shipment']      = $html;
		$response['new_shipment_type'] = $shipment->get_type();
		$response['fragments']         = array(
			'.order-shipping-status' => self::get_order_status_html( $order_shipment ),
			'.order-return-status'   => self::get_order_return_status_html( $order_shipment ),
		);

		self::send_json_success( $response, $order_shipment );
	}

	public static function add_return_shipment() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success'      => true,
			'message'      => '',
			'new_shipment' => '',
		);

		$order_id = absint( $_POST['order_id'] );
		$items    = isset( $_POST['return_item'] ) ? (array) wc_clean( wp_unslash( $_POST['return_item'] ) ) : array();

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
			wp_send_json( $response_error );
		}

		self::refresh_shipment_items( $order_shipment );

		if ( ! $order_shipment->needs_return() ) {
			$response_error['message'] = _x( 'This order contains enough returns already.', 'shipments', 'woocommerce-germanized' );
			wp_send_json( $response_error );
		}

		$shipment = wc_gzd_create_return_shipment( $order_shipment, array( 'items' => $items ) );

		if ( is_wp_error( $shipment ) ) {
			wp_send_json( $response_error );
		}

		$order_shipment->add_shipment( $shipment );

		// Mark as active
		$is_active = true;

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment.php';
		$html = ob_get_clean();

		$response['new_shipment']      = $html;
		$response['new_shipment_type'] = $shipment->get_type();
		$response['fragments']         = array(
			'.order-shipping-status' => self::get_order_status_html( $order_shipment ),
			'.order-return-status'   => self::get_order_return_status_html( $order_shipment ),
		);

		self::send_json_success( $response, $order_shipment );
	}

	public static function validate_shipment_item_quantities() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
		);

		$order_id = absint( $_POST['order_id'] );
		$active   = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

		if ( ! $order = wc_get_order( $order_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		static::refresh_shipments( $order_shipment );

		$order_shipment->validate_shipments();

		$response['fragments'] = self::get_shipments_html( $order_shipment, $active );

		self::send_json_success( $response, $order_shipment );
	}

	public static function sync_shipment_items() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order = $shipment->get_order() ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		$shipment = $order_shipment->get_shipment( $shipment_id );

		static::refresh_shipment_items( $order_shipment );

		if ( $shipment->is_editable() ) {
			$shipment = $order_shipment->get_shipment( $shipment_id );

			// Make sure we are working based on the current instance.
			$shipment->set_order_shipment( $order_shipment );
			$shipment->sync_items();
			$shipment->save();
		}

		ob_start();
		foreach ( $shipment->get_items() as $item ) {
			include Package::get_path() . '/includes/admin/views/html-order-shipment-item.php';
		}
		$html = ob_get_clean();

		$response['fragments'] = array(
			'#shipment-' . $shipment->get_id() . ' .shipment-item-list:first' => '<div class="shipment-item-list">' . $html . '</div>',
			'#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
		);

		self::send_json_success( $response, $order_shipment, $shipment );
	}

	public static function json_search_shipping_provider() {
		ob_start();

		check_ajax_referer( 'search-shipping-provider', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$term            = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';
		$found_providers = array();

		if ( empty( $term ) ) {
			wp_die();
		}

		global $wpdb;

		$names = $wpdb->get_col(
			$wpdb->prepare(
			    "SELECT DISTINCT p1.shipping_provider_name FROM {$wpdb->gzd_shipping_provider} p1 WHERE p1.shipping_provider_title LIKE %s AND p1.shipping_provider_activated = 1", // @codingStandardsIgnoreLine
				$wpdb->esc_like( wc_clean( $term ) ) . '%'
			)
		);

		foreach ( $names as $name ) {
			if ( $shipping_provider = wc_gzd_get_shipping_provider( $name ) ) {
				$found_providers[ $name ] = esc_html( $shipping_provider->get_title() );
			}
		}

		/**
		 * Filter to adjust found shipping providers to filter Shipments.
		 *
		 * @param array $result The shipping provider search result.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		wp_send_json( apply_filters( 'woocommerce_gzd_json_search_found_shipment_shipping_providers', $found_providers ) );
	}

	public static function json_search_orders() {
		ob_start();

		check_ajax_referer( 'search-orders', 'security' );

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
		} else {
			if ( ! is_numeric( $term ) ) {
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
		}

		$excluded = array();

		if ( ! empty( $_GET['exclude'] ) ) {
			$excluded = array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) );
		}

		foreach ( $ids as $id ) {
			if ( $order = wc_get_order( $id ) ) {
				if ( in_array( absint( $order->get_id() ), $excluded, true ) ) {
					continue;
				}

				$found_orders[ $order->get_id() ] = sprintf(
					esc_html_x( 'Order #%s', 'shipments', 'woocommerce-germanized' ),
					$order->get_order_number()
				);
			}
		}

		/**
		 * Filter to adjust found orders to filter Shipments.
		 *
		 * @param array $result The order search result.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		wp_send_json( apply_filters( 'woocommerce_gzd_json_search_found_shipment_orders', $found_orders ) );
	}

	private static function get_order_status_html( $order_shipment ) {
		$status_html = '<span class="order-shipping-status status-' . esc_attr( $order_shipment->get_shipping_status() ) . '">' . wc_gzd_get_shipment_order_shipping_status_name( $order_shipment->get_shipping_status() ) . '</span>';

		return $status_html;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return string[]
	 */
	private static function get_global_order_status_html( $order ) {
		$old_status = $order->get_status();
		$result     = array(
			'status' => '',
			'input'  => '',
		);

		/**
		 * Load a clean instance to make sure order status updates are reflected.
		 */
		$order = wc_get_order( $order->get_id() );

		/**
		 * In case the current request has not changed the status do not return html
		 */
		if ( ! $order || $old_status === $order->get_status() ) {
			return $result;
		}
		ob_start();
		?>
		<p class="form-field form-field-wide wc-order-status">
			<label for="order_status">
				<?php
				echo esc_html_x( 'Status:', 'shipments', 'woocommerce-germanized' );
				if ( $order->needs_payment() ) {
					printf(
						'<a href="%s">%s</a>',
						esc_url( $order->get_checkout_payment_url() ),
						wp_kses_post( _x( 'Customer payment page &rarr;', 'shipments', 'woocommerce-germanized' ) )
					);
				}
				?>
			</label>
			<select id="order_status" name="order_status" class="wc-enhanced-select">
				<?php
				$statuses = wc_get_order_statuses();
				foreach ( $statuses as $status => $status_name ) {
					echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'wc-' . $order->get_status( 'edit' ), false ) . '>' . esc_html( $status_name ) . '</option>';
				}
				?>
			</select>
		</p>
		<?php
		$html = ob_get_clean();

		$result['status'] = $html;
		$result['input']  = '<input name="post_status" type="hidden" value="' . esc_attr( 'wc-' . $order->get_status( 'edit' ) ) . '" />';

		return $result;
	}

	private static function get_order_return_status_html( $order_shipment ) {
		$status_html = '<span class="order-return-status status-' . esc_attr( $order_shipment->get_return_status() ) . '">' . wc_gzd_get_shipment_order_return_status_name( $order_shipment->get_return_status() ) . '</span>';

		return $status_html;
	}

	public static function refresh_shipment_packaging() {
		check_ajax_referer( 'refresh-shipment-packaging', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		$response = array(
			'success'                 => true,
			'message'                 => '',
			'shipment_id'             => $shipment_id,
			'needs_packaging_refresh' => true,
		);

		$data = array(
			'packaging_id' => isset( $_POST['shipment_packaging_id'][ $shipment_id ] ) ? absint( wc_clean( wp_unslash( $_POST['shipment_packaging_id'][ $shipment_id ] ) ) ) : '',
		);

		$shipment->set_props( $data );

		$response['fragments'] = array(
			'#shipment-' . $shipment->get_id() . ' .shipment-packaging-select' => self::get_packaging_select_html( $shipment ),
		);

		wp_send_json( $response );
	}

	protected static function get_packaging_select_html( $shipment ) {
		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment-packaging-select.php';
		$html = ob_get_clean();

		return $html;
	}

	public static function save_shipments() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
		);

		$order_id = absint( $_POST['order_id'] );
		$active   = isset( $_POST['active'] ) ? absint( $_POST['active'] ) : 0;

		if ( ! $order = wc_get_order( $order_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		// Refresh data
		self::refresh_shipments( $order_shipment );

		// Make sure that we are not applying more
		$order_shipment->validate_shipment_item_quantities();

		// Refresh statuses after adjusting quantities
		self::refresh_status( $order_shipment );

		$order_shipment->save();

		$response['fragments'] = self::get_shipments_html( $order_shipment, $active );

		self::send_json_success( $response, $order_shipment );
	}

	/**
	 * @param Order $order_shipment
	 * @param int $active
	 *
	 * @return array
	 */
	private static function get_shipments_html( $p_order_shipment, $p_active = 0 ) {
		$order_shipment  = $p_order_shipment;
		$active_shipment = $p_active;

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment-list.php';
		$html = ob_get_clean();

		$order_status_html = self::get_global_order_status_html( $order_shipment->get_order() );

		$fragments = array(
			'#order-shipments-list'                => $html,
			'.order-shipping-status'               => self::get_order_status_html( $order_shipment ),
			'.order-return-status'                 => self::get_order_return_status_html( $order_shipment ),
			'.order_data_column p.wc-order-status' => $order_status_html['status'],
			'input[name="post_status"]'            => $order_status_html['input'],
		);

		if ( empty( $fragments['.order_data_column p.wc-order-status'] ) ) {
			unset( $fragments['.order_data_column p.wc-order-status'] );
			unset( $fragments['input[name="post_status"]'] );
		}

		return $fragments;
	}

	public static function get_available_return_shipment_items() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
			'html'    => '',
		);

		$order_id = absint( $_POST['order_id'] );

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) {
			wp_send_json( $response_error );
		}

		static::refresh_shipments( $order_shipment );

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-add-return-shipment-items.php';
		$response['html'] = ob_get_clean();

		self::send_json_success( $response, $order_shipment );
	}

	public static function get_available_shipment_items() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
			'items'   => array(),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order = $shipment->get_order() ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		static::refresh_shipments( $order_shipment );

		if ( 'return' === $shipment->get_type() ) {
			$response['items'] = $order_shipment->get_available_items_for_return(
				array(
					'shipment_id'        => $shipment->get_id(),
					'disable_duplicates' => true,
				)
			);
		} else {
			$response['items'] = $order_shipment->get_available_items_for_shipment(
				array(
					'shipment_id'        => $shipment_id,
					'disable_duplicates' => true,
				)
			);
		}

		self::send_json_success( $response, $order_shipment );
	}

	public static function add_shipment_item() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success'  => true,
			'message'  => '',
			'new_item' => '',
		);

		$shipment_id      = absint( $_POST['shipment_id'] );
		$original_item_id = isset( $_POST['original_item_id'] ) ? absint( $_POST['original_item_id'] ) : 0;
		$item_quantity    = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : false;

		if ( false !== $item_quantity && 0 === $item_quantity ) {
			$item_quantity = 1;
		}

		if ( empty( $original_item_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order = $shipment->get_order() ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		static::refresh_shipments( $order_shipment );

		// Make sure we are working with the shipment from the order
		$shipment = $order_shipment->get_shipment( $shipment_id );

		if ( 'return' === $shipment->get_type() ) {
			$item = self::add_shipment_return_item( $order_shipment, $shipment, $original_item_id, $item_quantity );
		} else {
			$item = self::add_shipment_order_item( $order_shipment, $shipment, $original_item_id, $item_quantity );
		}

		if ( ! $item ) {
			wp_send_json( $response_error );
		}

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment-item.php';
		$response['new_item'] = ob_get_clean();

		$response['fragments'] = array(
			'#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
		);

		self::send_json_success( $response, $order_shipment, $shipment );
	}

	/**
	 * @param Order $order_shipment
	 * @param ReturnShipment $shipment
	 * @param integer $parent_item_id
	 * @param integer $quantity
	 */
	private static function add_shipment_return_item( $order_shipment, $shipment, $order_item_id, $quantity ) {
		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		if ( ! $shipment_item = $order_shipment->get_simple_shipment_item( $order_item_id ) ) {
			wp_send_json( $response_error );
		}

		// No duplicates allowed
		if ( $shipment->get_item_by_order_item_id( $order_item_id ) ) {
			wp_send_json( $response_error );
		}

		// Check max quantity
		$quantity_left = $order_shipment->get_item_quantity_left_for_returning( $shipment_item->get_order_item_id() );

		if ( $quantity ) {
			if ( $quantity > $quantity_left ) {
				$quantity = $quantity_left;
			}
		} else {
			$quantity = $quantity_left;
		}

		if ( $item = wc_gzd_create_return_shipment_item( $shipment, $shipment_item, array( 'quantity' => $quantity ) ) ) {
			$shipment->add_item( $item );
			$shipment->save();
		}

		return $item;
	}

	/**
	 * @param Order $order_shipment
	 * @param SimpleShipment $shipment
	 * @param integer $order_item_id
	 * @param integer $quantity
	 */
	private static function add_shipment_order_item( $order_shipment, $shipment, $order_item_id, $quantity ) {

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$order = $order_shipment->get_order();

		if ( ! $order_item = $order->get_item( $order_item_id ) ) {
			wp_send_json( $response_error );
		}

		// No duplicates allowed
		if ( $shipment->get_item_by_order_item_id( $order_item_id ) ) {
			wp_send_json( $response_error );
		}

		// Check max quantity
		$quantity_left = $order_shipment->get_item_quantity_left_for_shipping( $order_item );

		if ( $quantity ) {
			if ( $quantity > $quantity_left ) {
				$quantity = $quantity_left;
			}
		} else {
			$quantity = $quantity_left;
		}

		if ( $item = wc_gzd_create_shipment_item( $shipment, $order_item, array( 'quantity' => $quantity ) ) ) {
			$shipment->add_item( $item );
			$shipment->save();
		}

		return $item;
	}

	private static function get_item_count_html( $p_shipment, $p_order_shipment ) {
		$shipment = $p_shipment;

		// Refresh the instance to make sure we are working with the same object
		$shipment->set_order_shipment( $p_order_shipment );

		ob_start();
		include Package::get_path() . '/includes/admin/views/html-order-shipment-item-count.php';
		$html = ob_get_clean();

		return $html;
	}

	public static function remove_shipment_item() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['item_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success' => true,
			'message' => '',
			'item_id' => '',
		);

		$shipment_id = absint( $_POST['shipment_id'] );
		$item_id     = absint( $_POST['item_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $item = $shipment->get_item( $item_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order_id() ) ) {
			wp_send_json( $response_error );
		}

		$shipment->remove_item( $item_id );
		$shipment->save();

		$response['item_id']   = $item_id;
		$response['fragments'] = array(
			'#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
		);

		self::send_json_success( $response, $order_shipment, $shipment );
	}

	public static function limit_shipment_item_quantity() {
		check_ajax_referer( 'edit-shipments', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['item_id'] ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success' => false,
			'message' => _x( 'There was an error processing the shipment', 'shipments', 'woocommerce-germanized' ),
		);

		$response = array(
			'success'      => true,
			'message'      => '',
			'max_quantity' => '',
			'item_id'      => '',
		);

		$shipment_id = absint( $_POST['shipment_id'] );
		$item_id     = absint( $_POST['item_id'] );
		$quantity    = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1;
		$quantity    = $quantity <= 0 ? 1 : $quantity;

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order = $shipment->get_order() ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
			wp_send_json( $response_error );
		}

		// Make sure the shipment order gets notified about changes
		if ( ! $shipment = $order_shipment->get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $item = $shipment->get_item( $item_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $order_item = $order->get_item( $item->get_order_item_id() ) ) {
			wp_send_json( $response_error );
		}

		static::refresh_shipments( $order_shipment );

		$quantity_max = 0;

		if ( 'return' === $shipment->get_type() ) {
			$quantity_max = $order_shipment->get_item_quantity_left_for_returning(
				$item->get_order_item_id(),
				array(
					'exclude_current_shipment' => true,
					'shipment_id'              => $shipment->get_id(),
				)
			);
		} else {
			$quantity_max = $order_shipment->get_item_quantity_left_for_shipping(
				$order_item,
				array(
					'exclude_current_shipment' => true,
					'shipment_id'              => $shipment->get_id(),
				)
			);
		}

		$response['item_id']      = $item_id;
		$response['max_quantity'] = $quantity_max;

		if ( $quantity > $quantity_max ) {
			$quantity = $quantity_max;
		}

		$shipment->update_item_quantity( $item_id, $quantity );

		$response['fragments'] = array(
			'#shipment-' . $shipment->get_id() . ' .item-count:first' => self::get_item_count_html( $shipment, $order_shipment ),
		);

		self::send_json_success( $response, $order_shipment, $shipment );
	}

	/**
	 * @param $response
	 * @param Order $order_shipment
	 * @param Shipment|bool $shipment
	 */
	private static function send_json_success( $response, $order_shipment, $current_shipment = false ) {

		$available_items       = $order_shipment->get_available_items_for_shipment();
		$response['shipments'] = array();

		foreach ( $order_shipment->get_shipments() as $shipment ) {
			$shipment->set_order_shipment( $order_shipment );

			$response['shipments'][ $shipment->get_id() ] = array(
				'is_editable'  => $shipment->is_editable(),
				'needs_items'  => $shipment->needs_items( array_keys( $available_items ) ),
				'weight'       => wc_format_localized_decimal( $shipment->get_content_weight() ),
				'length'       => wc_format_localized_decimal( $shipment->get_content_length() ),
				'width'        => wc_format_localized_decimal( $shipment->get_content_width() ),
				'height'       => wc_format_localized_decimal( $shipment->get_content_height() ),
				'total_weight' => wc_format_localized_decimal( $shipment->get_total_weight() ),
			);
		}

		$response['order_needs_new_shipments'] = $order_shipment->needs_shipping();
		$response['order_needs_new_returns']   = $order_shipment->needs_return();

		if ( $current_shipment ) {
			if ( ! isset( $response['fragments'] ) ) {
				$response['fragments'] = array();
			}

			$response['needs_packaging_refresh'] = true;
			$response['shipment_id']             = $current_shipment->get_id();
			$response['fragments'][ '#shipment-' . $current_shipment->get_id() . ' .shipment-packaging-select' ] = self::get_packaging_select_html( $current_shipment );
		}

		wp_send_json( $response );
	}
}
