<?php

namespace Vendidero\Germanized\DHL\Label;

use Vendidero\Germanized\DHL\Legacy\DownloadHandler;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

abstract class Label extends \Vendidero\Germanized\Shipments\Labels\Label {

	protected $legacy = false;

	public function __construct( $data = 0, $legacy = false ) {
		$label_id     = false;
		$this->legacy = $legacy;

		if ( $this->legacy ) {
			$this->data['dhl_product']          = '';
			$this->data['default_path']         = '';
			$this->data['export_path']          = '';
			$this->data['preferred_time_start'] = '';
			$this->data['preferred_time_end']   = '';
		}

		if ( $data instanceof Label ) {
			$label_id = $data->get_id();
		} elseif ( is_numeric( $data ) ) {
			$label_id = $data;
		}

		parent::__construct( $data );

		/**
		 * Legacy object support
		 */
		if ( $this->legacy && $this->get_id() <= 0 && $label_id > 0 ) {
			$data_store = WC_Data_Store::load( 'dhl-legacy-label' );

			// If we have an ID, load the user from the DB.
			try {
				$this->set_id( $label_id );
				$data_store->read( $this );

				$this->data_store_name = 'dhl-legacy-label';
				$this->data_store      = $data_store;
				$this->object_type     = 'dhl_label';
				$this->cache_group     = 'dhl-labels';
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		}
	}

	public function get_dhl_product( $context = 'view' ) {
		return $this->get_product_id( $context );
	}

	/**
	 * Returns linked children labels.
	 *
	 * @return ShipmentLabel[]
	 */
	public function get_children() {
		if ( ! $this->legacy ) {
			return parent::get_children();
		} else {
			return wc_gzd_dhl_get_labels(
				array(
					'parent_id' => $this->get_id(),
				)
			);
		}
	}

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

	public function is_legacy() {
		return $this->legacy;
	}

	public function get_product_id( $context = 'view' ) {
		if ( $this->legacy ) {
			return $this->get_prop( 'dhl_product', $context );
		}

		return parent::get_product_id();
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

	protected function get_file_by_path( $file ) {
		if ( $this->legacy ) {
			// If the file is relative, prepend upload dir.
			if ( $file && 0 !== strpos( $file, '/' ) && ( ( $uploads = Package::get_upload_dir() ) && false === $uploads['error'] ) ) {
				$file = $uploads['basedir'] . "/$file";

				return $file;
			} else {
				return false;
			}
		} else {
			return parent::get_file_by_path( $file );
		}
	}
}
