<?php

namespace Vendidero\Germanized\DHL\Admin;
use Exception;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\PDFMerger;
use Vendidero\Germanized\Shipments\Admin\BulkActionHandler;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
class BulkLabel extends BulkActionHandler {

	protected $path = '';

	public function get_action() {
		return 'labels';
	}

	public function get_limit() {
		return 1;
	}

	public function get_title() {
		return _x( 'Generating labels...', 'dhl', 'woocommerce-germanized' );
	}

	public function get_file() {
		$file = get_user_option( $this->get_file_option_name() );

		if ( $file ) {
			$uploads  = Package::get_upload_dir();
			$path     = trailingslashit( $uploads['basedir'] ) . $file;

			return $path;
		}

		return '';
	}

	protected function update_file( $path ) {
		update_user_option( get_current_user_id(), $this->get_file_option_name(), $path );
	}

	protected function get_file_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_path";
	}

	public function get_filename() {
		if ( $file = $this->get_file() ) {
			return basename( $file );
		}

		return '';
	}

	public function reset( $is_new = false ) {
		parent::reset( $is_new );

		if ( $is_new ) {
			delete_user_option( get_current_user_id(), $this->get_file_option_name() );
		}
	}

	public function get_success_message() {
		return _x( 'Successfully generated labels.', 'dhl', 'woocommerce-germanized' );
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach( $current as $shipment_id ) {
				$label = wc_gzd_dhl_get_shipment_label( $shipment_id );

				if ( ! $label ) {
					if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

						// Do only generate label for shipments that support DHL
						if ( wc_gzd_dhl_shipment_needs_label( $shipment ) ) {
							$response = wc_gzd_dhl_create_label( $shipment );

							if ( is_wp_error( $response ) ) {
								$this->add_notice( sprintf( _x( 'Error while creating label for %s: %s', 'dhl', 'woocommerce-germanized' ), '<a href="' . $shipment->get_edit_shipment_url() .'" target="_blank">' . sprintf( _x(  'shipment #%d', 'dhl', 'woocommerce-germanized' ), $shipment_id ) . '</a>', $response->get_error_message() ), 'error' );
							} else {
								$label = $response;
							}
						}
					}
				}

				// Merge to bulk print/download
				if ( $label ) {
					try {
						$path     = $this->get_file();
						$filename = $this->get_filename();
						$pdf      = new PDFMerger();
						$file     = $label->get_file();

						if ( ! $file || ! file_exists( $file ) ) {
							continue;
						}

						if ( $path ) {
							$pdf->add( $path );
						}

						$pdf->add( $file );

						if ( ! $path ) {
							/**
							 * Filter to adjust the default filename chosen for bulk exporting DHL labels.
							 *
							 * @param string                                    $filename The filename.
							 * @param BulkLabel $this The `BulkLabel instance.
							 *
							 * @since 3.0.0
							 * @package Vendidero/Germanized/DHL
							 */
							$filename = apply_filters( 'woocommerce_gzd_dhl_label_bulk_filename', 'export.pdf', $this );
						}

						$file = $pdf->output( $filename, 'S' );

						if ( $path = wc_gzd_dhl_upload_data( $filename, $file ) ) {
							$this->update_file( $path );
						}
					} catch( Exception $e ) {}
				}
			}
		}

		$this->update_notices();
	}
}
