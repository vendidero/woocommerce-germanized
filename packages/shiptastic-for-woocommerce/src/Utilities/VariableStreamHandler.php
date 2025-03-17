<?php

namespace Vendidero\Shiptastic\Utilities;

class VariableStreamHandler {

	public $context;
	private $varname;
	private $position;

	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$url           = wp_parse_url( $path );
		$this->varname = $url['host'];
		if ( ! isset( $GLOBALS[ $this->varname ] ) ) {
			return false;
		}
		$this->position = 0;
		return true;
	}

	public function stream_read( $count ) {
		$ret             = substr( $GLOBALS[ $this->varname ], $this->position, $count );
		$this->position += strlen( $ret );
		return $ret;
	}

	public function stream_eof() {
		return $this->position >= strlen( $GLOBALS[ $this->varname ] );
	}

	public function stream_tell() {
		return $this->position;
	}

	public function stream_seek( $offset, $whence ) {
		if ( SEEK_SET === $whence ) {
			$this->position = $offset;
			return true;
		}
		return false;
	}

	public function stream_stat() {
		return array();
	}
}
