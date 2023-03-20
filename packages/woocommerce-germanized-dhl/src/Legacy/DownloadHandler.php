<?php

namespace Vendidero\Germanized\DHL\Legacy;

use WC_Download_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class DownloadHandler {

	protected static function parse_args( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'force'             => false,
				'file_type'         => '',
				'check_permissions' => true,
			)
		);

		$args['force'] = wc_string_to_bool( $args['force'] );

		return $args;
	}

	public static function download_label( $label_id, $args = array() ) {
		$args           = self::parse_args( $args );
		$has_permission = current_user_can( 'edit_shop_orders' );

		if ( ! $args['check_permissions'] ) {
			$has_permission = true;
		}

		if ( $has_permission ) {
			if ( $label = wc_gzd_get_shipment_label( $label_id ) ) {
				$file     = $label->get_file( $args['file_type'] );
				$filename = $label->get_filename( $args['file_type'] );

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
