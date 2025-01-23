<?php

namespace Vendidero\Germanized\DHL\Api;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\Label;
use Vendidero\Germanized\DHL\ParcelLocator;
use Vendidero\Germanized\Shipments\Admin\Settings;
use Vendidero\Germanized\Shipments\API\Response;
use Vendidero\Germanized\Shipments\Labels\Factory;
use Vendidero\Germanized\Shipments\PDFMerger;
use Vendidero\Germanized\Shipments\PDFSplitter;
use Vendidero\Germanized\Shipments\ShipmentError;

defined( 'ABSPATH' ) || exit;

abstract class PaketRest extends \Vendidero\Germanized\Shipments\API\REST {

	protected function get_auth_instance() {
		if ( apply_filters( 'woocommerce_gzd_dhl_paket_rest_api_use_oauth', true ) ) {
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
		$response_code  = $response->get_code();
		$response_body  = $response->get_body();
		$error          = new ShipmentError();
		$error_messages = array();

		if ( isset( $response_body['items'] ) && isset( $response_body['items'][0]['validationMessages'] ) ) {
			if ( ! empty( $response_body['items'][0]['validationMessages'] ) ) {
				foreach ( $response_body['items'][0]['validationMessages'] as $message ) {
					if ( ! in_array( $message['validationMessage'], $error_messages, true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$error_messages[] = $message['validationMessage']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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

		Package::log( 'POST Error: ' . $response_code . ' - ' . wc_print_r( $error_messages, true ) );

		foreach ( $error_messages as $error_message ) {
			$error->add( 'dhl-api-error', $error_message );
		}

		$response->set_error( $error );

		return $response;
	}
}
