<?php

namespace Vendidero\Germanized\DHL\Admin;
use Vendidero\Germanized\DHL\Package;
use WC_Download_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class DownloadHandler {

	protected static function parse_args( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'force' => false,
			'path'  => '',
		) );

		$args['force'] = wc_string_to_bool( $args['force'] );

		return $args;
	}

	public static function download_label( $label_id, $args = array() ) {
		$args = self::parse_args( $args );

		if ( current_user_can( 'edit_shop_orders' ) ) {
			if ( $label = wc_gzd_dhl_get_label( $label_id ) ) {

				if ( 'export' === $args['path'] ) {
					$file     = $label->get_export_file();
					$filename = $label->get_export_filename();
				} elseif( 'default' === $args['path'] ) {
					$file     = $label->get_default_file();
					$filename = $label->get_default_filename();
				} else {
					$file     = $label->get_file();
					$filename = $label->get_filename();
				}

				if ( file_exists( $file ) ) {
					if ( $args['force'] ) {
						WC_Download_Handler::download_file_force( $file, $filename );
					} else {
						self::embed( $file, $filename );
					}
				}
			}
		}
	}

	public static function download_legacy_label( $order_id, $args = array() ) {
		$args = self::parse_args( $args );

		if ( current_user_can( 'edit_shop_orders' ) ) {
			if ( $order = wc_get_order( $order_id ) ) {
				$meta = (array) $order->get_meta( '_pr_shipment_dhl_label_tracking' );

				if ( ! empty( $meta ) ) {
					$path = $meta['label_path'];

					if ( file_exists( $path ) ) {
						$filename = basename( $path );

						if ( $args['force'] ) {
							WC_Download_Handler::download_file_force( $path, $filename );
						} else {
							self::embed( $path, $filename );
						}
					}
				}
			}
		}
	}

	private static function embed( $file_path, $filename ) {
		if ( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			}
		} else {
			@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		}

		wc_nocache_headers();

		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Content-type: application/pdf' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: inline; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		$file_size = @filesize( $file_path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

		if ( ! $file_size ) {
			return;
		}

		header( 'Content-Length: ' . $file_size );

		@readfile( $file_path );
		exit();
	}
}
