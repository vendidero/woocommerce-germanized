<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Exception;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\PDFMerger;

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
		return _x( 'Generating labels...', 'shipments', 'woocommerce-germanized' );
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
		$download_button = '';

		if ( ( $path = $this->get_file() ) && file_exists( $path ) ) {

			$download_url = add_query_arg( array(
				'action'   => 'wc-gzd-download-export-shipment-label',
				'force'    => 'no'
			), wp_nonce_url( admin_url(), 'download-export-shipment-label' ) );

			$download_button = '<a class="button button-primary" style="margin-left: 1em;" href="' . $download_url . '" target="_blank">' . _x( 'Download labels', 'shipments', 'woocommerce-germanized' ) . '</a>';
		}

		return sprintf( _x( 'Successfully generated labels. %s', 'shipments', 'woocommerce-germanized' ), $download_button );
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach( $current as $shipment_id ) {
				$label = false;

				if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

					if ( $shipment->supports_label() ) {

						if ( $shipment->needs_label() ) {
							$result = $shipment->create_label();

							if ( is_wp_error( $result ) ) {
								$this->add_notice( sprintf( _x( 'Error while creating label for %s: %s', 'shipments', 'woocommerce-germanized' ), '<a href="' . $shipment->get_edit_shipment_url() .'" target="_blank">' . sprintf( _x(  'shipment #%d', 'shipments', 'woocommerce-germanized' ), $shipment_id ) . '</a>', $result->get_error_message() ), 'error' );
							} else {
								$label = $shipment->get_label();
							}
						} else {
							$label = $shipment->get_label();
						}
					}
				}

				// Merge to bulk print/download
				if ( $label ) {

					try {
						$path     = $this->get_file();
						$filename = $this->get_filename();
						$pdf      = new PDFMerger();

						if ( $path ) {
							$pdf->add( $path );
						}

						$label->merge( $pdf );

						if ( ! $path ) {
							/**
							 * Filter to adjust the default filename chosen for bulk exporting shipment labels.
							 *
							 * @param string    $filename The filename.
							 * @param BulkLabel $this The `BulkLabel instance.
							 *
							 * @since 3.0.0
							 * @package Vendidero/Germanized/shipments
							 */
							$filename = apply_filters( 'woocommerce_gzd_shipment_labels_bulk_filename', 'export.pdf', $this );
						}

						$file = $pdf->output( $filename, 'S' );

						if ( $path = wc_gzd_shipments_upload_data( $filename, $file ) ) {
							$this->update_file( $path );
						}
					} catch( Exception $e ) {}
				}
			}
		}

		$this->update_notices();
	}
}
