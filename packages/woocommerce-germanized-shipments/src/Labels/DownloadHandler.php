<?php

namespace Vendidero\Germanized\Shipments\Labels;

use Vendidero\Germanized\Shipments\Admin\BulkLabel;
use WC_Download_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class DownloadHandler {

	public static function init() {
		if ( isset( $_GET['action'], $_GET['shipment_id'], $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action( 'init', array( __CLASS__, 'download_label' ) );
		}

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'download_bulk_export' ) );
		}
	}

	public static function download_bulk_export() {
		if ( isset( $_GET['action'] ) && 'wc-gzd-download-export-shipment-label' === $_GET['action'] && isset( $_REQUEST['_wpnonce'] ) ) {
			if ( wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'download-export-shipment-label' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$args = array(
					'force' => isset( $_GET['force'] ) ? wc_clean( wp_unslash( $_GET['force'] ) ) : 'no', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'path'  => isset( $_GET['print'] ) ? wc_clean( wp_unslash( $_GET['print'] ) ) : 'no', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				);

				$args = self::parse_args( $args );

				if ( current_user_can( 'edit_shop_orders' ) ) {
					$handler = new BulkLabel();

					if ( $path = $handler->get_file() ) {
						$filename = $handler->get_filename();

						self::download( $path, $filename, $args['force'] );
					}
				}
			}
		}
	}

	public static function download_label() {
		if ( 'wc-gzd-download-shipment-label' === $_GET['action'] && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'download-shipment-label' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$shipment_id    = absint( $_GET['shipment_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$has_permission = current_user_can( 'edit_shop_orders' );

			$args = self::parse_args(
				array(
					'force' => wc_string_to_bool( isset( $_GET['force'] ) ? wc_clean( wp_unslash( $_GET['force'] ) ) : false ),
				)
			);

			if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
				if ( 'return' === $shipment->get_type() && current_user_can( 'view_order', $shipment->get_order_id() ) && $shipment->has_label() ) {
					$has_permission = true;
				}

				if ( $has_permission ) {
					if ( $label = $shipment->get_label() ) {
						$file     = $label->get_file( $args['path'] );
						$filename = $label->get_filename( $args['path'] );

						if ( file_exists( $file ) ) {
							self::download( $file, $filename, $args['force'] );
						}
					}
				}
			}
		}
	}

	public static function parse_args( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'force' => false,
				'path'  => '',
			)
		);

		$args['force'] = wc_string_to_bool( $args['force'] );

		return $args;
	}

	public static function download( $path, $filename, $force = false ) {
		if ( $force ) {
			self::force( $path, $filename );
		} else {
			self::embed( $path, $filename );
		}
	}

	private static function force( $path, $filename ) {
		WC_Download_Handler::download_file_force( $path, $filename );
	}

	private static function embed( $file_path, $filename ) {
		if ( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		} else {
			@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		wc_nocache_headers();

		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Content-type: application/pdf' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: inline; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		$file_size = @filesize( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! $file_size ) {
			return;
		}

		header( 'Content-Length: ' . $file_size );

		@readfile( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit();
	}
}
