<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

defined( 'ABSPATH' ) || exit;

class ServiceList implements \Countable, \ArrayAccess, \Iterator {

	/**
	 * @var Service[]
	 */
	protected $list = array();

	protected $internal_id_map = array();

	/**
	 * @param Service[] $services
	 */
	public function __construct( $services = array() ) {
		foreach ( $services as $service ) {
			$this->add( $service );
		}
	}

	/**
	 * @param Service $service
	 *
	 * @return void
	 */
	public function add( $service ) {
		$this->list[ $service->get_id() ]                     = $service;
		$this->internal_id_map[ $service->get_internal_id() ] = $service->get_id();
	}

	/**
	 * @param $service_id
	 *
	 * @return bool
	 */
	public function remove( $service_id ) {
		if ( $service = $this->get( $service_id ) ) {
			unset( $this->internal_id_map[ $service->get_internal_id() ] );
			unset( $this->list[ $service->get_id() ] );

			return true;
		}

		return false;
	}

	/**
	 * @param $service_id
	 *
	 * @return Service|false|Service[]
	 */
	public function get( $service_id = null ) {
		if ( $service_id ) {
			if ( array_key_exists( $service_id, $this->list ) ) {
				return $this->list[ $service_id ];
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
	 * @return false|Service
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
	 * @return false|Service
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
	 * @return ServiceList
	 */
	public function filter( $filter_args = array() ) {
		$services = new ServiceList();

		foreach ( $this->list as $service_id => $service ) {
			$include_service = $service->supports( $filter_args );

			if ( $include_service ) {
				$services->add( $service );
			}
		}

		return $services;
	}

	public function as_options() {
		$options = array();

		foreach ( $this->list as $service_id => $service ) {
			$options[ $service_id ] = $service->get_label();
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
	 * @param Service $value
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
