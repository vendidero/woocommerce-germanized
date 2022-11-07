<?php

namespace Vendidero\Germanized\DHL\Label;

use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

/**
 * DHL ReturnLabel class.
 */
class DHL extends Label {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'default_path'                  => '',
		'export_path'                   => '',
		'preferred_day'                 => '',
		'preferred_location'            => '',
		'preferred_neighbor'            => '',
		'preferred_delivery_type'       => '',
		'ident_date_of_birth'           => '',
		'ident_min_age'                 => '',
		'visual_min_age'                => '',
		'email_notification'            => 'no',
		'has_inlay_return'              => 'no',
		'codeable_address_only'         => 'no',
		'duties'                        => '',
		'return_address'                => array(),
		'cod_total'                     => 0,
		'cod_includes_additional_total' => 'no',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'dhl';
	}

	public function get_return_address( $context = 'view' ) {
		return $this->get_prop( 'return_address', $context );
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
	protected function get_return_address_prop( $prop, $context = 'view' ) {
		$value = $this->get_address_prop( $prop, 'return_address', $context );

		// Load from settings
		if ( is_null( $value ) ) {
			$value = Package::get_setting( 'return_' . $prop );
		}

		return $value;
	}

	public function get_return_street( $context = 'view' ) {
		return $this->get_return_address_prop( 'street', $context );
	}

	public function get_return_street_number( $context = 'view' ) {
		return $this->get_return_address_prop( 'street_number', $context );
	}

	public function get_return_company( $context = 'view' ) {
		return $this->get_return_address_prop( 'company', $context );
	}

	public function get_return_name( $context = 'view' ) {
		return $this->get_return_address_prop( 'name', $context );
	}

	public function get_return_formatted_full_name() {
		return sprintf( _x( '%1$s', 'dhl full name', 'woocommerce-germanized' ), $this->get_return_name() ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
	}

	public function get_return_postcode( $context = 'view' ) {
		return $this->get_return_address_prop( 'postcode', $context );
	}

	public function get_return_city( $context = 'view' ) {
		return $this->get_return_address_prop( 'city', $context );
	}

	public function get_return_state( $context = 'view' ) {
		return $this->get_return_address_prop( 'state', $context );
	}

	public function get_return_country( $context = 'view' ) {
		return $this->get_return_address_prop( 'country', $context );
	}

	public function get_return_phone( $context = 'view' ) {
		return $this->get_return_address_prop( 'phone', $context );
	}

	public function get_return_email( $context = 'view' ) {
		return $this->get_return_address_prop( 'email', $context );
	}

	public function get_cod_total( $context = 'view' ) {
		return $this->get_prop( 'cod_total', $context );
	}

	public function get_cod_includes_additional_total( $context = 'view' ) {
		return $this->get_prop( 'cod_includes_additional_total', $context );
	}

	public function cod_includes_additional_total( $context = 'view' ) {
		return $this->get_cod_includes_additional_total() ? true : false;
	}

	public function get_duties( $context = 'view' ) {
		return $this->get_prop( 'duties', $context );
	}

	public function get_preferred_day( $context = 'view' ) {
		return $this->get_prop( 'preferred_day', $context );
	}

	public function get_preferred_delivery_type( $context = 'view' ) {
		return $this->get_prop( 'preferred_delivery_type', $context );
	}

	public function get_preferred_location( $context = 'view' ) {
		return $this->get_prop( 'preferred_location', $context );
	}

	public function get_preferred_neighbor( $context = 'view' ) {
		return $this->get_prop( 'preferred_neighbor', $context );
	}

	public function get_ident_date_of_birth( $context = 'view' ) {
		return $this->get_prop( 'ident_date_of_birth', $context );
	}

	public function get_ident_min_age( $context = 'view' ) {
		return $this->get_prop( 'ident_min_age', $context );
	}

	public function get_visual_min_age( $context = 'view' ) {
		return $this->get_prop( 'visual_min_age', $context );
	}

	public function get_email_notification( $context = 'view' ) {
		return $this->get_prop( 'email_notification', $context );
	}

	public function has_email_notification() {
		return ( true === $this->get_email_notification() );
	}

	public function get_has_inlay_return( $context = 'view' ) {
		return $this->get_prop( 'has_inlay_return', $context );
	}

	public function has_inlay_return() {
		$products = wc_gzd_dhl_get_inlay_return_products();

		return ( true === $this->get_has_inlay_return() && in_array( $this->get_product_id(), $products ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
	}

	/**
	 * Returns a directly linked return label.
	 *
	 * @return bool|DHLInlayReturn
	 */
	public function get_inlay_return_label() {
		$children = $this->get_children();

		if ( ! empty( $children ) && is_a( $children[0], '\Vendidero\Germanized\DHL\Label\DHLInlayReturn' ) ) {
			return $children[0];
		}

		return false;
	}

	/**
	 * Checks whether the label has a directly linked return label.
	 *
	 * @return bool
	 */
	public function has_inlay_return_label() {
		$label = $this->get_inlay_return_label();

		return $label ? true : false;
	}

	public function get_codeable_address_only( $context = 'view' ) {
		return $this->get_prop( 'codeable_address_only', $context );
	}

	public function codeable_address_only() {
		return ( true === $this->get_codeable_address_only() );
	}

	public function set_return_address( $value ) {
		$this->set_prop( 'return_address', empty( $value ) ? array() : (array) $value );
	}

	public function set_cod_total( $value ) {
		$value = wc_format_decimal( $value );

		if ( ! is_numeric( $value ) ) {
			$value = 0;
		}

		$this->set_prop( 'cod_total', $value );
	}

	public function set_cod_includes_additional_total( $value ) {
		$this->set_prop( 'cod_includes_additional_total', wc_string_to_bool( $value ) );
	}

	public function set_duties( $duties ) {
		$this->set_prop( 'duties', $duties );
	}

	public function set_preferred_day( $day ) {
		$this->set_date_prop( 'preferred_day', $day );
	}

	public function set_preferred_delivery_type( $delivery_type ) {
		$this->set_date_prop( 'preferred_delivery_type', $delivery_type );
	}

	public function set_preferred_location( $location ) {
		$this->set_prop( 'preferred_location', $location );
	}

	public function set_preferred_neighbor( $neighbor ) {
		$this->set_prop( 'preferred_neighbor', $neighbor );
	}

	public function set_email_notification( $value ) {
		$this->set_prop( 'email_notification', wc_string_to_bool( $value ) );
	}

	public function set_has_inlay_return( $value ) {
		$this->set_prop( 'has_inlay_return', wc_string_to_bool( $value ) );
	}

	public function set_codeable_address_only( $value ) {
		$this->set_prop( 'codeable_address_only', wc_string_to_bool( $value ) );
	}

	public function set_ident_date_of_birth( $date ) {
		$this->set_date_prop( 'ident_date_of_birth', $date );
	}

	public function set_ident_min_age( $age ) {
		$this->set_prop( 'ident_min_age', $age );
	}

	public function set_visual_min_age( $age ) {
		$this->set_prop( 'visual_min_age', $age );
	}

	/**
	 * @return \WP_Error|true
	 */
	public function fetch() {
		$result = new \WP_Error();

		try {
			Package::get_api()->get_label( $this );
		} catch ( \Exception $e ) {
			$errors = explode( PHP_EOL, $e->getMessage() );

			foreach ( $errors as $error ) {
				$result->add( 'dhl-api-error', $error );
			}
		}

		if ( wc_gzd_dhl_wp_error_has_errors( $result ) ) {
			return $result;
		} else {
			return true;
		}
	}

	public function delete( $force_delete = false ) {
		if ( $api = Package::get_api() ) {
			try {
				$api->get_label_api()->delete_label( $this );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		return parent::delete( $force_delete );
	}

	public function get_additional_file_types() {
		return array(
			'default',
			'export',
		);
	}

	public function get_filename( $file_type = '' ) {
		if ( 'default' === $file_type ) {
			return $this->get_default_filename();
		} elseif ( 'export' === $file_type ) {
			return $this->get_export_filename();
		} else {
			return parent::get_filename( $file_type );
		}
	}

	public function get_file( $file_type = '' ) {
		if ( 'default' === $file_type ) {
			return $this->get_default_file();
		} elseif ( 'export' === $file_type ) {
			return $this->get_export_file();
		} else {
			return parent::get_file( $file_type );
		}
	}

	public function get_path( $context = 'view', $file_type = '' ) {
		if ( 'default' === $file_type ) {
			return $this->get_default_path( $context );
		} elseif ( 'export' === $file_type ) {
			return $this->get_export_path( $context );
		} else {
			return parent::get_path( $context, $file_type );
		}
	}

	public function set_path( $path, $file_type = '' ) {
		if ( 'default' === $file_type ) {
			$this->set_default_path( $path );
		} elseif ( 'export' === $file_type ) {
			$this->set_export_path( $path );
		} else {
			parent::set_path( $path, $file_type );
		}
	}

	public function get_default_file() {
		if ( ! $path = $this->get_default_path() ) {
			return false;
		}

		return $this->get_file_by_path( $path );
	}

	public function get_default_filename() {
		if ( ! $path = $this->get_default_path() ) {
			return $this->get_new_filename( 'default' );
		}

		return basename( $path );
	}

	public function get_export_file() {
		if ( ! $path = $this->get_export_path() ) {
			return false;
		}

		return $this->get_file_by_path( $path );
	}

	public function get_export_filename() {
		if ( ! $path = $this->get_export_path() ) {
			return $this->get_new_filename( 'export' );
		}

		return basename( $path );
	}

	public function set_default_path( $path ) {
		$this->set_prop( 'default_path', $path );
	}

	public function set_export_path( $path ) {
		$this->set_prop( 'export_path', $path );
	}

	public function get_default_path( $context = 'view' ) {
		return $this->get_prop( 'default_path', $context );
	}

	public function get_export_path( $context = 'view' ) {
		return $this->get_prop( 'export_path', $context );
	}

	public function is_trackable() {
		$is_trackable = true;

		/**
		 * WaPo International without premium does not support tracking
		 */
		if ( 'V66WPI' === $this->get_product_id() && ! in_array( 'Premium', $this->get_services(), true ) ) {
			$is_trackable = false;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}is_trackable", $is_trackable, $this );
	}
}
