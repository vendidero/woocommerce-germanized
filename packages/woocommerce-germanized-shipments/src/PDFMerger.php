<?php

namespace Vendidero\Germanized\Shipments;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PdfReaderException;

class PDFMerger {

	/**
	 * Fpdi pdf instance
	 *
	 * @var null|Fpdi
	 */
	protected $_pdf = null;

	/**
	 * Pdf constructor
	 *
	 */
	public function __construct() {
		$this->_pdf = new Fpdi();
	}

	/**
	 * Add file to this pdf
	 *
	 * @param string $filename Filename of the source file
	 * @param mixed $pages Range of files (if not set, all pages where imported)
	 */
	public function add( $filename, $pages = array(), $width = 210 ) {
		if ( file_exists( $filename ) ) {
			$page_count = $this->_pdf->setSourceFile( $filename );

			for ( $i = 1; $i <= $page_count; $i ++ ) {
				if ( $this->_isPageInRange( $i, $pages ) ) {
					$this->_addPage( $i, $width );
				}
			}
		}

		return $this;
	}

	/**
	 * Output merged pdf
	 *
	 * @param string $type
	 */
	public function output( $filename, $type = 'I' ) {
		return $this->_pdf->Output( $type, $filename );
	}

	/**
	 * Force download merged pdf as file
	 *
	 * @param $filename
	 *
	 * @return string
	 */
	public function download( $filename ) {
		return $this->output( $filename, 'D' );
	}

	/**
	 * Save merged pdf
	 *
	 * @param $filename
	 *
	 * @return string
	 */
	public function save( $filename ) {
		return $this->output( $filename, 'F' );
	}

	/**
	 * Add single page
	 *
	 * @param $page_number
	 *
	 * @throws PdfReaderException
	 */
	private function _addPage( $page_number, $width = 210 ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$page_id = $this->_pdf->importPage( $page_number );
		$size    = $this->_pdf->getTemplateSize( $page_id );

		$orientation = isset( $size['orientation'] ) ? $size['orientation'] : '';

		$this->_pdf->addPage( $orientation, $size );

		if ( ! isset( $size['width'] ) || empty( $size['width'] ) ) {
			$this->_pdf->useImportedPage( $page_id, 0, 0, $width, null, true );
		} else {
			$this->_pdf->useImportedPage( $page_id );
		}
	}


	/**
	 * Check if a specific page should be merged.
	 * If pages are empty, all pages will be merged
	 *
	 * @return bool
	 */
	private function _isPageInRange( $page_number, $pages = array() ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		if ( empty( $pages ) ) {
			return true;
		}

		foreach ( $pages as $range ) {
			if ( in_array( $page_number, $this->_getRange( $range ), true ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Get range by given value
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	private function _getRange( $value = null ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$value = preg_replace( '/[^0-9\-.]/is', '', $value );

		if ( '' === $value ) {
			return false;
		}

		$value = explode( '-', $value );

		if ( 1 === count( $value ) ) {
			return $value;
		}

		return range( $value[0] > $value[1] ? $value[1] : $value[0], $value[0] > $value[1] ? $value[0] : $value[1] );
	}
}
