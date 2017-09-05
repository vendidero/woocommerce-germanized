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
			'gzd_json_search_delivery_time' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// WC AJAX can be used for frontend ajax requests.
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function gzd_json_search_delivery_time() {
		ob_start();

		check_ajax_referer( 'search-products', 'security' );
		$term = (string) wc_clean( stripslashes( $_GET['term'] ) );
		$terms = array();

		if ( empty( $term ) )
			die();

		$args = array(
			'hide_empty' => false,
		);

		if ( is_numeric( $term ) ) {
			$args[ 'include' ] = array( absint( $term ) ); 
		} else {
			$args[ 'name__like' ] = (string) $term;
 		}

 		$query = get_terms( 'product_delivery_time', $args );
 		if ( ! empty( $query ) ) {
 			foreach ( $query as $term ) {
 				$terms[ $term->term_id ] = rawurldecode( $term->name );
 			}
 		} else {
 			$terms[ rawurldecode( $term ) ] = rawurldecode( sprintf( __( "%s [new]", "woocommerce-germanized" ), $term ) );
 		}
 		wp_send_json( $terms );
	}

	/**
	 * Checks revocation form and sends Email to customer and Admin
	 */
	public static function gzd_revocation() {

		check_ajax_referer( 'woocommerce-revocation', 'security' );

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-revocation' ) ) {
			wp_send_json( array( 'result' => 'failure' ) );
		}

		$data = array();
		$fields = WC_GZD_Revocation::get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $key => $field ) {

				if ( 'sep' !== $key ) {

					if ( isset( $field[ 'required' ] ) && true === $field[ 'required' ] ) {
						if ( $key == 'address_mail' ) {
							if ( ! is_email( $_POST[ $key ] ) )
								wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not a valid email address.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
						} elseif ( $key == 'address_postal' ) {
							if ( ! WC_Validation::is_postcode( $_POST[ $key ], $_POST[ 'address_country' ] ) || empty( $_POST[ $key ] ) )
								wc_add_notice( _x( 'Please enter a valid postcode/ZIP', 'revocation-form', 'woocommerce-germanized' ), 'error' );
						} else {
							if ( isset( $field[ 'required' ] ) && empty( $_POST[ $key ] ) )
								wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not valid.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
						}
					}

					if ( isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ) {
						if ( $field['type'] == 'country' ) {
							$countries = WC()->countries->get_countries();
							$country = wc_clean( $_POST[ $key ] );
							$data[ $key ] = ( isset( $countries[ $country ] ) ? $countries[ $country ] : '' );
						} else {
							$data[ $key ] = wc_clean( $_POST[ $key ] );
						}
					}
				}
			}
		}
		
		$error = false;
		if ( wc_notice_count( 'error' ) == 0 ) {
			
			wc_add_notice( _x( 'Thank you. We have received your Revocation Request. You will receive a conformation email within a few minutes.', 'revocation-form', 'woocommerce-germanized' ), 'success' );
			
			// Send Mail
			if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_revocation' ) ) {

				$mail->trigger( $data );
				
				// Send to Admin
				$data[ 'mail' ] = apply_filters( 'wc_gzd_revocation_admin_mail', get_bloginfo('admin_email') );
				$mail->trigger( $data );
			}
	
		} else {
			$error = true;
		}

		ob_start();
		wc_print_notices();
		$messages = ob_get_clean();

		$data = array(
			'messages' => isset( $messages ) ? $messages : '',
			'result' => ( $error ? 'failure' : 'success' ),
		);

		wp_send_json( $data );
	}

}

WC_GZD_AJAX::init();
