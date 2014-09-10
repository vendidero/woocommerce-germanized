<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * AJAX Handler
 *
 * @class 		WC_GZD_AJAX
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_AJAX {

	/**
	 * Hook in methods
	 */
	public static function init() {
		$ajax_events = array(
			'gzd_revocation' => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Checks revocation form and sends Email to customer and Admin
	 */
	public static function gzd_revocation() {

		check_ajax_referer( 'woocommerce-revocation', 'security' );
		wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-revocation' );

		$data = array();
		$fields = WC_GZD_Revocation::get_fields();

		if ( !empty( $fields ) ) {
			foreach ( $fields as $key => $field ) {
				if ( $key != 'sep' ) {
					if ( $key == 'address_mail' ) {
						if ( !is_email( $_POST[ $key ] ) )
							wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not a valid email address.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
					} elseif ( $key == 'address_postal' ) {
						if ( ! WC_Validation::is_postcode( $_POST[ $key ], $_POST[ 'address_country' ] ) || empty( $_POST[ $key ] ) )
							wc_add_notice( _x( 'Please enter a valid postcode/ZIP', 'revocation-form', 'woocommerce-germanized' ), 'error' );
					} else {
						if ( isset( $field[ 'required' ] ) && empty( $_POST[ $key ] ) )
							wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not valid.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
					}
					if ( !empty( $_POST[ $key ] ) ) {
						if ( $field['type'] == 'country' )
							$data[ $key ] = WC()->countries->get_countries()[sanitize_text_field( $_POST[ $key ] )];
						else
							$data[ $key ] = sanitize_text_field( $_POST[ $key ] );
					}
				}
			}
		}
		$error = false;
		if ( wc_notice_count( 'error' ) == 0 ) {
			wc_add_notice( _x( 'Thank you. We have received your Revocation Request. You will receive a conformation email within a few minutes.', 'revocation-form', 'woocommerce-germanized' ), 'success' );
			// Send Mail
			$mails = WC()->mailer()->get_emails();
			if ( !empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( $mail->id == 'customer_revocation' ) {
						$mail->trigger( $data );
						// Send to Admin
						$data[ 'mail' ] = get_bloginfo('admin_email');
						$mail->trigger( $data );
					}
				}
			}
		}
		else
			$error = true;

		ob_start();
		wc_print_notices();
		$messages = ob_get_clean();

		if ( $error ) {
			echo '<!--WC_START-->' . json_encode(
				array(
					'result' => 'failure',
					'messages'  => isset( $messages ) ? $messages : '',
				)
			) . '<!--WC_END-->';
		} else {
			if ( is_ajax() ) {
				echo '<!--WC_START-->' . json_encode(
					array(
						'result'  => 'success',
						'messages' => isset ( $messages ) ? $messages : '',
					)
				) . '<!--WC_END-->';
			}
		}
		exit();
	}

}

WC_GZD_AJAX::init();
