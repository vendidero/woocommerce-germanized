<?php

namespace Vendidero\Germanized\DHL;
use DateTimeZone;
use Vendidero\Germanized\DHL\Admin\DownloadHandler;
use Vendidero\Germanized\Shipments\PDFMerger;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * DHL Shipment class.
 */
abstract class Label extends WC_Data implements ShipmentLabel {

    /**
     * This is the name of this object type.
     *
     * @since 3.0.0
     * @var string
     */
    protected $object_type = 'dhl_label';

    /**
     * Contains the data store name.
     *
     * @var string
     */
    protected $data_store_name = 'dhl-label';

    /**
     * Stores meta in cache for future reads.
     * A group must be set to to enable caching.
     *
     * @since 3.0.0
     * @var string
     */
    protected $cache_group = 'dhl-labels';

    /**
     * @var Shipment
     */
    private $shipment = null;

    /**
     * Stores shipment data.
     *
     * @var array
     */
    protected $data = array(
        'date_created'               => null,
        'shipment_id'                => 0,
        'number'                     => '',
        'weight'                     => '',
        'path'                       => '',
        'default_path'               => '',
        'export_path'                => '',
        'created_via'                => '',
        'dhl_product'                => '',
        'services'                   => array(),
    );

    public function __construct( $data = 0 ) {
        parent::__construct( $data );

        if ( $data instanceof Label ) {
            $this->set_id( absint( $data->get_id() ) );
        } elseif ( is_numeric( $data ) ) {
            $this->set_id( $data );
        }

        $this->data_store = WC_Data_Store::load( $this->data_store_name );

        // If we have an ID, load the user from the DB.
        if ( $this->get_id() ) {
            try {
                $this->data_store->read( $this );
            } catch ( Exception $e ) {
                $this->set_id( 0 );
                $this->set_object_read( true );
            }
        } else {
            $this->set_object_read( true );
        }
    }

    public function get_type() {
    	return 'label';
    }

    /**
     * Merge changes with data and clear.
     * Overrides WC_Data::apply_changes.
     * array_replace_recursive does not work well for license because it merges domains registered instead
     * of replacing them.
     *
     * @since 3.2.0
     */
    public function apply_changes() {
        if ( function_exists( 'array_replace' ) ) {
            $this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
        } else { // PHP 5.2 compatibility.
            foreach ( $this->changes as $key => $change ) {
                $this->data[ $key ] = $change;
            }
        }
        $this->changes = array();
    }

    /**
     * Prefix for action and filter hooks on data.
     *
     * @since  3.0.0
     * @return string
     */
    protected function get_hook_prefix() {
        return 'woocommerce_gzd_dhl_label_get_';
    }

    /**
     * Return the date this license was created.
     *
     * @since  3.0.0
     * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return WC_DateTime|null object if the date is set or null if there is no date.
     */
    public function get_date_created( $context = 'view' ) {
        return $this->get_prop( 'date_created', $context );
    }

    public function get_shipment_id( $context = 'view' ) {
        return $this->get_prop( 'shipment_id', $context );
    }

	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

    public function get_dhl_product( $context = 'view' ) {
        return $this->get_prop( 'dhl_product', $context );
    }

    public function get_number( $context = 'view' ) {
        return $this->get_prop( 'number', $context );
    }

    public function has_number() {
	    $number = $this->get_number();

	    return empty( $number ) ? false : true;
    }

	public function get_weight( $context = 'view' ) {
    	return $this->get_prop( 'weight', $context );
    }

    public function get_tracking_url() {

    	if ( $shipment = $this->get_shipment() ) {
    		return $shipment->get_tracking_url();
	    }

    	return '';
     }

    public function get_path( $context = 'view' ) {
        return $this->get_prop( 'path', $context );
    }

	public function get_default_path( $context = 'view' ) {
		return $this->get_prop( 'default_path', $context );
	}

    public function get_export_path( $context = 'view' ) {
        return $this->get_prop( 'export_path', $context );
    }

	/**
	 * Gets a prop for a getter method.
	 *
	 * @since  3.0.0
	 * @param  string $prop Name of prop to get.
	 * @param  string $address billing or shipping.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_address_prop( $prop, $address = 'sender_address', $context = 'view' ) {
		$value = null;

		if ( isset( $this->changes[ $address ][ $prop ] ) || isset( $this->data[ $address ][ $prop ] ) ) {
			$value = isset( $this->changes[ $address ][ $prop ] ) ? $this->changes[ $address ][ $prop ] : $this->data[ $address ][ $prop ];

			if ( 'view' === $context ) {
				/**
				 * Filter to adjust a specific address property for a DHL label.
				 *
				 * The dynamic portion of the hook name, `$this->get_hook_prefix()` constructs an individual
				 * hook name which uses `woocommerce_gzd_dhl_label_get_` as a prefix. Additionally
				 * `$address` contains the current address type e.g. sender_address and `$prop` contains the actual
				 * property e.g. street.
				 *
				 * Example hook name: `woocommerce_gzd_dhl_return_label_get_sender_address_street`
				 *
				 * @param string                          $value The address property value.
				 * @param Label $label The label object.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/DHL
				 */
				$value = apply_filters( "{$this->get_hook_prefix()}{$address}_{$prop}", $value, $this );
			}
		}

		return $value;
	}

    public function get_services( $context = 'view' ) {
        return $this->get_prop( 'services', $context );
    }

    public function has_service( $service ) {
        return ( in_array( $service, $this->get_services() ) );
    }

    public function get_shipment() {
        if ( is_null( $this->shipment ) ) {
            $this->shipment = ( $this->get_shipment_id() > 0 ? wc_gzd_get_shipment( $this->get_shipment_id() ) : false );
        }

        return $this->shipment;
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */

    /**
     * Set the date this license was last updated.
     *
     * @since  1.0.0
     * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
     */
    public function set_date_created( $date = null ) {
        $this->set_date_prop( 'date_created', $date );
    }

    public function set_number( $number ) {
        $this->set_prop( 'number', $number );
    }

	public function set_created_via( $created_via ) {
		$this->set_prop( 'created_via', $created_via );
	}

	public function set_weight( $weight ) {
		$this->set_prop( 'weight','' !== $weight ? wc_format_decimal( $weight ) : '' );
	}

    public function set_path( $path ) {
        $this->set_prop( 'path', $path );
    }

	public function set_default_path( $path ) {
		$this->set_prop( 'default_path', $path );
	}

    public function set_export_path( $path ) {
        $this->set_prop( 'export_path', $path );
    }

    public function set_services( $services ) {
        $this->set_prop( 'services', empty( $services ) ? array() : (array) $services );
    }

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

    public function add_service( $service ) {
        $services = (array) $this->get_services();

        if ( ! in_array( $service, $services ) && in_array( $service, wc_gzd_dhl_get_services() ) ) {
            $services[] = $service;

            $this->set_services( $services );
            return true;
        }

        return false;
    }

    public function remove_service( $service ) {
	    $services = (array) $this->get_services();

	    if ( in_array( $service, $services ) ) {
		    $services = array_diff( $services, array( $service ) );

		    $this->set_services( $services );
		    return true;
	    }

	    return false;
    }

    public function get_file() {
        if ( ! $path = $this->get_path() ) {
            return false;
        }

        return $this->get_file_by_path( $path );
    }

    public function get_filename() {
	    if ( ! $path = $this->get_path() ) {
		    return false;
	    }

	    return basename( $path );
    }

	public function get_default_file() {
		if ( ! $path = $this->get_default_path() ) {
			return false;
		}

		return $this->get_file_by_path( $path );
	}

	public function get_default_filename() {
		if ( ! $path = $this->get_default_path() ) {
			return false;
		}

		return basename( $path );
	}

    protected function get_file_by_path( $file ) {
        // If the file is relative, prepend upload dir.
        if ( $file && 0 !== strpos( $file, '/' ) && ( ( $uploads = Package::get_upload_dir() ) && false === $uploads['error'] ) ) {
            $file = $uploads['basedir'] . "/$file";

            return $file;
        } else {
            return false;
        }
    }

    public function get_export_file() {
        if ( ! $path = $this->get_export_path() ) {
            return false;
        }

        return $this->get_file_by_path( $path );
    }

	public function get_export_filename() {
		if ( ! $path = $this->get_export_path() ) {
			return false;
		}

		return basename( $path );
	}

    public function set_shipment_id( $shipment_id ) {
        // Reset order object
        $this->shipment = null;

        $this->set_prop( 'shipment_id', absint( $shipment_id ) );
    }

	/**
	 * @param Shipment $shipment
	 */
	public function set_shipment( &$shipment ) {
		$this->shipment = $shipment;

		$this->set_prop( 'shipment_id', absint( $shipment->get_id() ) );
	}

	public function download( $args = array() ) {
		DownloadHandler::download_label( $this->get_id(), $args );
	}
}
