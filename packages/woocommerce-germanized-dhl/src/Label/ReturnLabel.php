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

abstract class ReturnLabel extends \Vendidero\Germanized\Shipments\Labels\ReturnLabel {

	protected $legacy = false;

	public function __construct( $data = 0, $legacy = false ) {
		$label_id     = false;
		$this->legacy = $legacy;

		if ( $this->legacy ) {
			$this->data['dhl_product'] = '';
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

	public function is_legacy() {
		return $this->legacy;
	}

	public function get_product_id( $context = 'view' ) {
		if ( $this->legacy ) {
			return $this->get_prop( 'dhl_product', $context );
		}

		return parent::get_product_id();
	}

	public function get_dhl_product( $context = 'view' ) {
		return $this->get_product_id( $context );
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
