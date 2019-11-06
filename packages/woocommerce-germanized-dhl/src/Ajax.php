<?php

namespace Vendidero\Germanized\DHL;

use Exception;
use Vendidero\Germanized\DHL\Admin\MetaBox;
use Vendidero\Germanized\DHL\Package;
use WP_Error;

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
			'create_dhl_label',
			'remove_dhl_label',
			'dhl_create_label_form',
			'dhl_email_return_label'
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_woocommerce_gzd_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function dhl_email_return_label() {
		$success = false;

		if ( current_user_can( 'edit_shop_orders' ) && isset( $_REQUEST['label_id'] ) ) {

			if ( isset( $_GET['label_id'] ) ) {
				$referrer = check_admin_referer( 'email-dhl-label' );
			} else {
				$referrer = check_ajax_referer( 'email-dhl-label', 'security' );
			}

			if ( $referrer ) {
				$label = wc_gzd_dhl_get_label( absint( wp_unslash( $_REQUEST['label_id'] ) ) );

				if ( 'return' === $label->get_type() ) {
					if ( $label->send_to_customer( true ) ) {
						$success = true;
					}
				}
			}
		}

		if ( isset( $_GET['label_id'] ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-return-shipments' ) );
			exit;
		} else {
			if ( $success ) {
				wp_send_json( array(
					'success'  => true,
					'messages' => array(
						_x( 'Label successfully sent to customer.', 'dhl', 'woocommerce-germanized' )
					),
				) );
			} else {
				wp_send_json( array(
					'success'  => false,
					'messages' => array(
						_x( 'There was an error while sending the label.', 'dhl', 'woocommerce-germanized' )
					),
				) );
			}
		}
	}

	public static function dhl_create_label_form() {
		check_ajax_referer( 'create-dhl-label-form', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$shipment_id    = absint( $_POST['shipment_id'] );
		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error creating the label.', 'dhl', 'woocommerce-germanized' )
			),
		);

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() ) ) {
			wp_send_json( $response_error );
		}

		$path = Package::get_path() . '/includes/admin/views/html-shipment-label-backbone-form.php';

		if ( 'return' === $shipment->get_type() ) {
			$path = Package::get_path() . '/includes/admin/views/html-shipment-return-label-backbone-form.php';
		}

		ob_start();
		include $path;
		$html = ob_get_clean();

		$response = array(
			'fragments' => array(
				'.wc-gzd-dhl-create-label' => '<div class="wc-gzd-dhl-create-label">' . $html . '</div>',
			),
			'shipment_id' => $shipment_id,
			'success'     => true,
		);

		wp_send_json( $response );
	}

	public static function remove_dhl_label() {
		check_ajax_referer( 'remove-dhl-label', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) || ! isset( $_POST['label_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error deleting the label.', 'dhl', 'woocommerce-germanized' )
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );
		$label_id    = absint( $_POST['label_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		if ( ! $label = wc_gzd_dhl_get_label( $label_id ) ) {
			wp_send_json( $response_error );
		}

		if ( (int) $label->get_shipment_id() !== $shipment_id ) {
			wp_send_json( $response_error );
		}

		if ( $label->delete( true ) ) {
			$response = array(
				'success'   => true,
				'label_id'  => $label->get_id(),
				'fragments' => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-dhl-label:first' => self::refresh_label_html( $shipment )
				),
			);
		} else {
			wp_send_json( $response_error );
		}

		wp_send_json( $response );
	}

	public static function create_dhl_label() {
		check_ajax_referer( 'create-dhl-label', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				_x( 'There was an error processing the label.', 'dhl', 'woocommerce-germanized' )
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		$services = array();
		$props    = array(
			'has_inlay_return'     => 'no',
			'codeable_address_only' => 'no',
		);

		if ( 'return' === $shipment->get_type() ) {
			$props = array();
		}

		foreach( $_POST as $key => $value ) {
			if ( substr( $key, 0, strlen( 'dhl_label_service_' ) ) === 'dhl_label_service_' ) {
				$new_key              = substr( $key, ( strlen( 'dhl_label_service_' ) ) );

				if ( 'yes' === $value && in_array( $new_key, wc_gzd_dhl_get_services() ) ) {
					$services[] = $new_key;
				}

			} elseif ( substr( $key, 0, strlen( 'dhl_label_' ) ) === 'dhl_label_' ) {
				$new_key           = substr( $key, ( strlen( 'dhl_label_' ) ) );
				$props[ $new_key ] = wc_clean( wp_unslash( $value ) );
			}
		}

		if ( isset( $props['preferred_time'] ) && ! empty( $props['preferred_time'] ) ) {
			$preferred_time = explode( '-', wc_clean( wp_unslash( $props['preferred_time'] ) ) );

			if ( sizeof( $preferred_time ) === 2 ) {
				$props['preferred_time_start'] = $preferred_time[0];
				$props['preferred_time_end']   = $preferred_time[1];
			}

			unset( $props['preferred_time'] );
		}

		$props['services'] = $services;

		if ( $label = wc_gzd_dhl_get_shipment_label( $shipment ) ) {
			$label = wc_gzd_dhl_update_label( $label, $props );
		} else {
			$label = wc_gzd_dhl_create_label( $shipment, $props );
		}

		if ( is_wp_error( $label ) ) {
			$response = array(
				'success'  => false,
				'messages' => $label->get_error_messages(),
			);
		} else {

			$response = array(
				'success'   => true,
				'label_id'  => $label->get_id(),
				'fragments' => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-dhl-label:first'                               => self::refresh_label_html( $shipment, $label ),
					'tr#shipment-' . $shipment_id . ' td.actions .wc-gzd-shipment-action-button-generate-dhl-label' => self::label_download_button_html( $label ),
				),
			);

			if ( 'simple' === $shipment->get_type() && 'yes' === Package::get_setting( 'label_auto_shipment_status_shipped' ) ) {

				$is_active = true;

				ob_start();
				include( \Vendidero\Germanized\Shipments\Package::get_path() . '/includes/admin/views/html-order-shipment.php' );
				$html = ob_get_clean();

				// Needs refresh
				$response['fragments']['div#shipment-' . $shipment_id] = $html;

				if ( $order_shipment = wc_gzd_get_shipment_order( $shipment->get_order() ) ) {
					$response['fragments']['.order-shipping-status'] = '<span class="order-shipping-status status-' . esc_attr( $order_shipment->get_shipping_status() ) . '">' . wc_gzd_get_shipment_order_shipping_status_name( $order_shipment->get_shipping_status() ) . '</span>';
				}
			}
		}

		wp_send_json( $response );
	}

	/**
	 * @param Label $label
	 *
	 * @return string
	 */
	protected static function label_download_button_html( $label ) {
		return '<a class="button wc-gzd-shipment-action-button wc-gzd-shipment-action-button-download-dhl-label download" href="' . $label->get_download_url() .'" target="_blank" title="' . _x( 'Download DHL label', 'dhl', 'woocommerce-germanized' ) . '">' . _x(  'Download label', 'dhl', 'woocommerce-germanized' ) . '</a>';
	}

	protected static function refresh_label_html( $p_shipment, $p_label = false ) {
		$shipment = $p_shipment;

		if ( $p_label ) {
			$dhl_label = $p_label;
		}

		ob_start();
		MetaBox::output( $shipment, $dhl_label );
		$html = ob_get_clean();

		return $html;
	}
}
