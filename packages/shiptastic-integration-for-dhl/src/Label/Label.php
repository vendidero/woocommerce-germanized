<?php

namespace Vendidero\Shiptastic\DHL\Label;

use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

abstract class Label extends \Vendidero\Shiptastic\Labels\Label {

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/
	protected function set_time_prop( $prop, $value ) {
		try {

			if ( empty( $value ) ) {
				$this->set_prop( $prop, null );
				return;
			}

			if ( is_a( $value, 'WC_DateTime' ) ) {
				$datetime = $value;
			} elseif ( is_numeric( $value ) ) {
				$datetime = new WC_DateTime( "@{$value}" );
			} else {
				$timestamp = wc_string_to_timestamp( $value );
				$datetime  = new WC_DateTime( "@{$timestamp}" );
			}

			$this->set_prop( $prop, $datetime );
		} catch ( Exception $e ) {} // @codingStandardsIgnoreLine.
	}

	public function get_preferred_time() {
		$start = $this->get_preferred_time_start();
		$end   = $this->get_preferred_time_end();

		if ( $start && $end ) {
			return $start->date( 'H:i' ) . '-' . $end->date( 'H:i' );
		}

		return null;
	}

	public function get_preferred_time_start( $context = 'view' ) {
		return $this->get_prop( 'preferred_time_start', $context );
	}

	public function get_preferred_time_end( $context = 'view' ) {
		return $this->get_prop( 'preferred_time_end', $context );
	}

	public function get_preferred_formatted_time() {
		$start = $this->get_preferred_time_start();
		$end   = $this->get_preferred_time_end();

		if ( $start && $end ) {
			return sprintf( _x( '%1$s-%2$s', 'dhl time-span', 'woocommerce-germanized' ), $start->date( 'H' ), $end->date( 'H' ) );
		}

		return null;
	}

	public function set_preferred_time_start( $time ) {
		$this->set_time_prop( 'preferred_time_start', $time );
	}

	public function set_preferred_time_end( $time ) {
		$this->set_time_prop( 'preferred_time_end', $time );
	}
}
