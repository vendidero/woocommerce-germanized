<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class ProductList implements \Countable, \ArrayAccess, \Iterator {

	/**
	 * @var Product[]
	 */
	protected $list = array();

	protected $internal_id_map = array();

	/**
	 * @param Product[] $products
	 */
	public function __construct( $products = array() ) {
		foreach ( $products as $product ) {
			$this->add( $product );
		}
	}

	/**
	 * @param Product $product
	 *
	 * @return void
	 */
	public function add( $product ) {
		$this->list[ $product->get_id() ]                     = $product;
		$this->internal_id_map[ $product->get_internal_id() ] = $product->get_id();
	}

	/**
	 * @param $product_id
	 *
	 * @return bool
	 */
	public function remove( $product_id ) {
		if ( $product = $this->get( $product_id ) ) {
			unset( $this->internal_id_map[ $product->get_internal_id() ] );
			unset( $this->list[ $product->get_id() ] );

			return true;
		}

		return false;
	}

	/**
	 * @param $product_id
	 *
	 * @return Product|false|Product[]
	 */
	public function get( $product_id = null ) {
		if ( $product_id ) {
			if ( array_key_exists( $product_id, $this->list ) ) {
				return $this->list[ $product_id ];
			} else {
				return false;
			}
		} else {
			return $this->list;
		}
	}

	/**
	 * @param $internal_id
	 *
	 * @return false|Product
	 */
	public function get_by_internal_id( $internal_id ) {
		if ( array_key_exists( $internal_id, $this->internal_id_map ) ) {
			return $this->get( $this->internal_id_map[ $internal_id ] );
		} else {
			return false;
		}
	}

	/**
	 * @param integer $index
	 *
	 * @return false|Product
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
	 * @return ProductList
	 */
	public function filter( $filter_args = array() ) {
		$products = new ProductList();

		foreach ( $this->list as $product_id => $product ) {
			$include_product = $product->supports( $filter_args );

			if ( $include_product ) {
				$products->add( $product );
			}
		}

		return $products;
	}

	public function as_options() {
		$options = array();

		foreach ( $this->list as $product_id => $product ) {
			$options[ $product_id ] = $product->get_label();
		}

		return $options;
	}

	#[\ReturnTypeWillChange]
	public function count(): int {
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
	 * @param Product $value
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
