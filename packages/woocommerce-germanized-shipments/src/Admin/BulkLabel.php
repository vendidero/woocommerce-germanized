<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Exception;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\PDFMerger;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class       WC_GZD_Shipment_Order
 * @version     1.0.0
 * @author      Vendidero
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
		$file = get_user_meta( get_current_user_id(), $this->get_file_option_name(), true );

		if ( $file ) {
			$uploads = Package::get_upload_dir();
			$path    = trailingslashit( $uploads['basedir'] ) . $file;

			return $path;
		}

		return '';
	}

	protected function update_file( $path ) {
		update_user_meta( get_current_user_id(), $this->get_file_option_name(), $path );
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
			delete_user_meta( get_current_user_id(), $this->get_file_option_name() );
			delete_user_meta( get_current_user_id(), $this->get_files_option_name() );
		}
	}

	protected function get_download_button() {
		$download_button = '';

		if ( ( $path = $this->get_file() ) && file_exists( $path ) ) {

			$download_url = add_query_arg(
				array(
					'action' => 'wc-gzd-download-export-shipment-label',
					'force'  => 'no',
				),
				wp_nonce_url( admin_url(), 'download-export-shipment-label' )
			);

			$download_button = '<a class="button button-primary bulk-download-button" style="margin-left: 1em;" href="' . esc_url( $download_url ) . '" target="_blank">' . esc_html_x( 'Download labels', 'shipments', 'woocommerce-germanized' ) . '</a>';
		}

		return $download_button;
	}

	public function get_success_message() {
		$download_button = $this->get_download_button();

		if ( empty( $download_button ) ) {
			return sprintf( _x( 'The chosen shipments were not suitable for automatic label creation. Please check the shipping provider option of the corresponding shipments.', 'shipments', 'woocommerce-germanized' ), $download_button );
		} else {
			return sprintf( _x( 'Successfully generated labels. %s', 'shipments', 'woocommerce-germanized' ), $download_button );
		}
	}

	public function admin_after_error() {
		$download_button = $this->get_download_button();

		if ( ! empty( $download_button ) ) {
			echo '<div class="notice"><p>' . sprintf( esc_html_x( 'Labels partially generated. %s', 'shipments', 'woocommerce-germanized' ), $download_button ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	protected function get_files_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_files";
	}

	protected function get_files() {
		$files = get_user_meta( get_current_user_id(), $this->get_files_option_name(), true );

		if ( empty( $files ) || ! is_array( $files ) ) {
			$files = array();
		}

		return $files;
	}

	protected function add_file( $path ) {
		$files = $this->get_files();

		if ( ! in_array( $path, $files, true ) ) {
			$files[] = $path;
			update_user_meta( get_current_user_id(), $this->get_files_option_name(), $files );
		}
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach ( $current as $shipment_id ) {
				$label = false;

				if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
					if ( $shipment->supports_label() ) {
						if ( $shipment->needs_label() ) {
							$result = $shipment->create_label();

							if ( is_wp_error( $result ) ) {
								$result = wc_gzd_get_shipment_error( $result );
							}

							if ( is_wp_error( $result ) ) {
								foreach ( $result->get_error_messages_by_type() as $type => $messages ) {
									foreach ( $messages as $message ) {
										if ( 'soft' === $type ) {
											$this->add_notice( sprintf( _x( 'Notice while creating label for %1$s: %2$s', 'shipments', 'woocommerce-germanized' ), '<a href="' . esc_url( $shipment->get_edit_shipment_url() ) . '" target="_blank">' . sprintf( _x( 'shipment #%d', 'shipments', 'woocommerce-germanized' ), $shipment_id ) . '</a>', $message ), 'info' );
										} else {
											$this->add_notice( sprintf( _x( 'Error while creating label for %1$s: %2$s', 'shipments', 'woocommerce-germanized' ), '<a href="' . esc_url( $shipment->get_edit_shipment_url() ) . '" target="_blank">' . sprintf( _x( 'shipment #%d', 'shipments', 'woocommerce-germanized' ), $shipment_id ) . '</a>', $message ), 'error' );
										}
									}
								}
							}

							if ( $shipment->has_label() ) {
								$label = $shipment->get_label();
							}
						} else {
							$label = $shipment->get_label();
						}
					}
				}

				if ( $label ) {
					$this->add_file( $label->get_file() );
				}
			}
		}

		if ( $this->is_last_step() ) {
			try {
				$files = $this->get_files();
				$pdf   = new PDFMerger();

				if ( ! empty( $files ) ) {
					foreach ( $files as $file ) {
						if ( ! file_exists( $file ) ) {
							continue;
						}

						$pdf->add( $file );
					}

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
					$file     = $pdf->output( $filename, 'S' );

					if ( $path = wc_gzd_shipments_upload_data( $filename, $file ) ) {
						$this->update_file( $path );
					}
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		$this->update_notices();
	}
}
