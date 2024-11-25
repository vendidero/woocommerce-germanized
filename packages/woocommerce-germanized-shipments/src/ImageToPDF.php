<?php

namespace Vendidero\Germanized\Shipments;

class ImageToPDF extends \FPDF {

	protected $image_rotation = 0;

	public function __construct( $orientation = 'P', $size = array( 0, 0 ) ) {
		parent::__construct( $orientation, 'mm', $size );

		$this->SetMargins( 0, 0 );

		stream_wrapper_register( 'var', '\Vendidero\Germanized\Shipments\Utilities\VariableStreamHandler' );
	}

	public function set_rotation( $rotation ) {
		$this->image_rotation = $rotation;
	}

	protected function convert_px_to_mm( $px, $dpi = 72 ) {
		$pixel = 25.4 / $dpi;

		return $pixel * $px;
	}

	public function import_image( $stream_or_file, $x = 0, $y = 0, $dpi = 72 ) {
		if ( is_file( $stream_or_file ) ) {
			$image_meta = wp_getimagesize( $stream_or_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( ! $image_meta ) {
				$this->Error( 'Invalid image data' );
			}

			$width  = $this->convert_px_to_mm( $image_meta[0], $dpi );
			$height = $this->convert_px_to_mm( $image_meta[1], $dpi );

			$this->AddPage( $this->DefOrientation, array( $width, $height ), $this->image_rotation ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$this->Image( $stream_or_file, $x, $y, $width, $height );
		} else {
			// Display the image contained in $data
			$image_stream             = 'img' . md5( $stream_or_file );
			$GLOBALS[ $image_stream ] = $stream_or_file;
			$image_meta               = wp_getimagesize( 'var://' . $image_stream ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( ! $image_meta ) {
				$this->Error( 'Invalid image data' );
			}

			$width  = $this->convert_px_to_mm( $image_meta[0], $dpi );
			$height = $this->convert_px_to_mm( $image_meta[1], $dpi );

			$this->AddPage( $this->DefOrientation, array( $width, $height ), $this->image_rotation ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$type = substr( strstr( $image_meta['mime'], '/' ), 1 );
			$this->Image( 'var://' . $image_stream, 0, 0, $width, $height, $type );

			unset( $GLOBALS[ $image_stream ] );
		}
	}
}
