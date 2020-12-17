<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;
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

	public static function download_export( $args = array() ) {
		$args = self::parse_args( $args );

		if ( current_user_can( 'edit_shop_orders' ) ) {
			$handler = new BulkLabel();

			if ( $path = $handler->get_file() ) {
				$filename = $handler->get_filename();

				if ( $args['force'] ) {
					WC_Download_Handler::download_file_force( $path, $filename );
				} else {
					self::embed( $path, $filename );
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
