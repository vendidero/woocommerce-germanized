<?php

namespace Vendidero\Shiptastic\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class PrintFormatList implements \Countable, \ArrayAccess, \Iterator {

	/**
	 * @var PrintFormat[]
	 */
	protected $list = array();

	protected $internal_id_map = array();

	/**
	 * @param PrintFormat[] $print_formats
	 */
	public function __construct( $print_formats = array() ) {
		foreach ( $print_formats as $print_format ) {
			$this->add( $print_format );
		}
	}

	/**
	 * @param PrintFormat $print_format
	 *
	 * @return void
	 */
	public function add( $print_format ) {
		$this->list[ $print_format->get_id() ] = $print_format;
	}

	/**
	 * @param $print_format_id
	 *
	 * @return bool
	 */
	public function remove( $print_format_id ) {
		if ( $print_format = $this->get( $print_format_id ) ) {
			unset( $this->list[ $print_format->get_id() ] );

			return true;
		}

		return false;
	}

	/**
	 * @param $print_format_id
	 *
	 * @return PrintFormat|false|PrintFormat[]
	 */
	public function get( $print_format_id = null ) {
		if ( $print_format_id ) {
			if ( array_key_exists( $print_format_id, $this->list ) ) {
				return $this->list[ $print_format_id ];
			} else {
				return false;
			}
		} else {
			return $this->list;
		}
	}

	/**
	 * @param integer $index
	 *
	 * @return false|PrintFormat
	 */
	public function get_by_index( $index ) {
		$data = array_values( $this->list );

		if ( array_key_exists( $index, $data ) ) {
			return $data[ $index ];
		}

		return false;
	}

	/**
	 * @param $filter_args
	 *
	 * @return PrintFormatList
	 */
	public function filter( $filter_args = array() ) {
		$print_formats = new PrintFormatList();

		foreach ( $this->list as $print_format_id => $print_format ) {
			$include_print_format = $print_format->supports( $filter_args );

			if ( $include_print_format ) {
				$print_formats->add( $print_format );
			}
		}

		return $print_formats;
	}

	public function as_options() {
		$options = array();

		foreach ( $this->list as $print_format_id => $print_format ) {
			$options[ $print_format_id ] = $print_format->get_label();
		}

		return $options;
	}

	#[\ReturnTypeWillChange]
	public function count() {
		return count( $this->list );
	}

	public function empty() {
		return 0 === $this->count();
	}

	public function __isset( $name ) {
		return array_key_exists( $name, $this->list );
	}

	/**
	 * @param $index
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $index ) {
		if ( $this->offsetExists( $index ) ) {
			unset( $this->list[ $index ] );
		}
	}

	/**
	 * @param $index
	 * @param PrintFormat $value
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $index, $value ) {
		if ( $this->offsetExists( $index ) ) {
			$this->remove( $index );
			$this->add( $value );
		}
	}

	#[\ReturnTypeWillChange]
	public function offsetGet( $index ) {
		if ( $this->offsetExists( $index ) ) {
			return $this->list[ $index ];
		}

		return null;
	}

	#[\ReturnTypeWillChange]
	public function offsetExists( $index ) {
		if ( isset( $this->list[ $index ] ) ) {
			return true;
		}

		return false;
	}

	public function __get( $name ) {
		return $this->get( $name );
	}

	#[\ReturnTypeWillChange]
	public function rewind() {
		reset( $this->list );
	}

	#[\ReturnTypeWillChange]
	public function current() {
		return current( $this->list );
	}

	#[\ReturnTypeWillChange]
	public function key() {
		return key( $this->list );
	}

	#[\ReturnTypeWillChange]
	public function next(): void {
		next( $this->list );
	}

	#[\ReturnTypeWillChange]
	public function valid(): bool {
		return key( $this->list ) !== null;
	}
}
