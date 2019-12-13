<?php

namespace Vendidero\Germanized\DHL;

use Exception;
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
}
