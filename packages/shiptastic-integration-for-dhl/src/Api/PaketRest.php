<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\Label;
use Vendidero\Shiptastic\DHL\ParcelLocator;
use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\Labels\Factory;
use Vendidero\Shiptastic\PDFMerger;
use Vendidero\Shiptastic\PDFSplitter;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

abstract class PaketRest extends \Vendidero\Shiptastic\API\REST {

	protected function get_auth_instance() {
		if ( apply_filters( 'woocommerce_shiptastic_dhl_paket_rest_api_use_oauth', true ) ) {
			return new OAuthPaket( $this );
		} else {
			return new BasicAuthPaket( $this );
		}
	}

	/**
	 * @param Response $response
	 *
	 * @return Response
	 */
	protected function parse_error( $response ) {
		$response_code       = $response->get_code();
		$response_body       = $response->get_body();
		$error               = new ShipmentError();
		$error_messages      = array();
		$soft_error_messages = array();

		if ( 401 === $response_code ) {
			$error_messages[] = sprintf( _x( 'Your DHL <a href="%s">API credentials</a> seem to be invalid.', 'dhl', 'woocommerce-germanized' ), esc_url( Package::get_dhl_shipping_provider()->get_edit_link() ) );
		} elseif ( isset( $response_body['items'] ) && isset( $response_body['items'][0]['validationMessages'] ) ) {
			if ( ! empty( $response_body['items'][0]['validationMessages'] ) ) {
				foreach ( $response_body['items'][0]['validationMessages'] as $message ) {
					$message = wp_parse_args(
						$message,
						array(
							'validationMessage' => '',
							'validationState'   => 'Error',
						)
					);

					if ( 'Error' === $message['validationState'] ) {
						if ( ! in_array( $message['validationMessage'], $error_messages, true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$error_messages[] = $message['validationMessage']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						}
					} elseif ( ! in_array( $message['validationMessage'], $soft_error_messages, true ) ) {
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$soft_error_messages[] = $message['validationMessage']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
				}
			} elseif ( ! empty( $response_body['items'][0]['sstatus'] ) ) {
				if ( ! in_array( $response_body['items'][0]['sstatus']['title'], $error_messages, true ) ) {
					$error_messages[] = $response_body['items'][0]['sstatus']['title'];
				}
			}
		} elseif ( isset( $response_body['items'] ) && isset( $response_body['items'][0]['message'] ) ) {
			foreach ( $response_body['items'] as $message ) {
				$property_path = isset( $message['propertyPath'] ) ? $message['propertyPath'] : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$property_path = str_replace( 'shipments[0].', '', $property_path );

				$error_message = ( ! empty( $error_message ) ? "\n" : '' ) . ( ! empty( $property_path ) ? $property_path . ': ' : '' ) . $message['message'];

				if ( ! in_array( $error_message, $error_messages, true ) ) {
					$error_messages[] = $error_message;
				}
			}
		} elseif ( empty( $response_body['status']['detail'] ) && empty( $response_body['detail'] ) ) {
			$error_message = _x( 'POST error or timeout occurred. Please try again later.', 'dhl', 'woocommerce-germanized' );

			if ( ! in_array( $error_message, $error_messages, true ) ) {
				$error_messages[] = $error_message;
			}
		} else {
			$error_message = ! empty( $response_body['status']['detail'] ) ? $response_body['status']['detail'] : $response_body['detail'];

			if ( ! in_array( $error_message, $error_messages, true ) ) {
				$error_messages[] = $error_message;
			}
		}

		foreach ( $error_messages as $error_message ) {
			$error->add( 'dhl-api-error', $error_message );
		}

		foreach ( $soft_error_messages as $error_message ) {
			$error->add_soft_error( 'dhl-api-soft-error', $error_message );
		}

		$response->set_error( $error );

		return $response;
	}
}
