<?php

namespace Vendidero\Shiptastic\DHL\Label;

use Vendidero\Shiptastic\DHL\Order;
use Vendidero\Shiptastic\DHL\Package;

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
		'default_path'          => '',
		'export_path'           => '',
		'codeable_address_only' => 'no',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'dhl';
	}

	public function get_return_address( $context = 'view' ) {
		$return_address = $this->get_service_prop( 'dhlRetoure', 'return_address', null, $context );

		if ( is_null( $return_address ) && $this->get_meta( '_return_address', true, $context ) ) {
			$return_address = $this->get_meta( '_return_address', true, $context );
		}

		return (array) $return_address;
	}

	public function return_has_go_green_plus( $context = 'view' ) {
		return wc_string_to_bool( $this->get_service_prop( 'dhlRetoure', 'gogreenplus', null, $context ) );
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
		$return_address = $this->get_return_address( $context );

		// Load from settings
		if ( ! array_key_exists( $prop, $return_address ) ) {
			$value = Package::get_setting( 'return_' . $prop );
		} else {
			$value = $return_address[ $prop ];
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
		$cod_total = $this->get_service_prop( 'CashOnDelivery', 'cod_total', null, $context );

		if ( is_null( $cod_total ) && $this->get_meta( '_cod_total', true, $context ) ) {
			$cod_total = $this->get_meta( '_cod_total', true, $context );
		}

		$cod_total = null === $cod_total ? 0.0 : (float) wc_format_decimal( $cod_total );

		return $cod_total;
	}

	public function get_cod_includes_additional_total( $context = 'view' ) {
		return $this->get_meta( '_cod_includes_additional_total', true, $context );
	}

	public function get_insurance_amount( $context = 'view' ) {
		$insurance_amount = $this->get_service_prop( 'AdditionalInsurance', 'insurance_amount', null, $context );

		if ( null === $insurance_amount ) {
			$insurance_amount = 0.0;

			if ( $this->has_service( 'AdditionalInsurance' ) ) {
				$insurance_amount = $this->get_shipment() ? $this->get_shipment()->get_total() : 0.0;
			}
		}

		return (float) wc_format_decimal( $insurance_amount );
	}

	public function cod_includes_additional_total( $context = 'view' ) {
		return $this->get_cod_includes_additional_total() ? true : false;
	}

	public function get_duties( $context = 'view' ) {
		return $this->get_prop( 'duties', $context );
	}

	public function get_endorsement( $context = 'view' ) {
		$type = $this->get_meta( 'service_Endorsement', true, $context );

		if ( ! $type && $this->get_meta( '_endorsement', true, $context ) ) {
			$type = $this->get_meta( '_endorsement', true, $context );
		}

		if ( 'view' === $context && empty( $type ) ) {
			$type = 'return';
		}

		return $type;
	}

	public function get_preferred_day( $context = 'view' ) {
		$preferred_day = $this->get_service_prop( 'PreferredDay', 'day', null, $context );

		if ( is_null( $preferred_day ) && $this->get_meta( '_preferred_day', true, $context ) ) {
			$preferred_day = $this->get_meta( '_preferred_day', true, $context );
		}

		return $preferred_day;
	}

	public function get_preferred_delivery_type( $context = 'view' ) {
		return $this->get_prop( 'preferred_delivery_type', $context );
	}

	public function get_preferred_location( $context = 'view' ) {
		$preferred_location = $this->get_service_prop( 'PreferredLocation', 'location', null, $context );

		if ( is_null( $preferred_location ) && $this->get_meta( '_preferred_location', true, $context ) ) {
			$preferred_location = $this->get_meta( '_preferred_location', true, $context );
		}

		return $preferred_location;
	}

	public function get_preferred_neighbor( $context = 'view' ) {
		$preferred_neighbor = $this->get_service_prop( 'PreferredNeighbour', 'neighbor', null, $context );

		if ( is_null( $preferred_neighbor ) && $this->get_meta( '_preferred_neighbor', true, $context ) ) {
			$preferred_neighbor = $this->get_meta( '_preferred_neighbor', true, $context );
		}

		return $preferred_neighbor;
	}

	public function get_ident_date_of_birth( $context = 'view' ) {
		$date_of_birth = $this->get_service_prop( 'IdentCheck', 'date_of_birth', null, $context );

		if ( is_null( $date_of_birth ) && $this->get_meta( '_ident_date_of_birth', true, $context ) ) {
			$date_of_birth = $this->get_meta( '_ident_date_of_birth', true, $context );
		}

		return $date_of_birth;
	}

	public function get_ident_min_age( $context = 'view' ) {
		$min_age = $this->get_service_prop( 'IdentCheck', 'min_age', null, $context );

		if ( is_null( $min_age ) && $this->get_meta( '_ident_min_age', true, $context ) ) {
			$min_age = $this->get_meta( '_ident_min_age', true, $context );
		}

		return $min_age;
	}

	public function get_visual_min_age( $context = 'view' ) {
		$min_age = $this->get_service_prop( 'VisualCheckOfAge', 'min_age', null, $context );

		if ( is_null( $min_age ) && $this->get_meta( '_visual_min_age', true, $context ) ) {
			$min_age = $this->get_meta( '_visual_min_age', true, $context );
		}

		return $min_age;
	}

	public function has_email_notification() {
		return ( true === $this->supports_third_party_email_notification() );
	}

	public function has_inlay_return() {
		$has_inlay_return = false;

		if ( $this->has_service( 'dhlRetoure' ) ) {
			$has_inlay_return = true;
		} elseif ( $this->get_meta( '_has_inlay_return' ) ) {
			$has_inlay = wc_string_to_bool( $this->get_meta( '_has_inlay_return' ) );

			if ( true === $has_inlay ) {
				if ( $provider = $this->get_shipping_provider_instance() ) {
					if ( $service = $provider->get_service( 'dhlRetoure' ) ) {
						if ( $service->supports_product( $this->get_product_id() ) ) {
							$has_inlay_return = true;
						}
					}
				}
			}
		}

		return $has_inlay_return;
	}

	/**
	 * Returns a directly linked return label.
	 *
	 * @return bool|DHLInlayReturn
	 */
	public function get_inlay_return_label() {
		$children = $this->get_children();

		if ( ! empty( $children ) && is_a( $children[0], '\Vendidero\Shiptastic\DHL\Label\DHLInlayReturn' ) ) {
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

	public function set_codeable_address_only( $codeable_address_only ) {
		$this->set_prop( 'codeable_address_only', wc_string_to_bool( $codeable_address_only ) );
	}

	public function codeable_address_only() {
		return ( true === $this->get_codeable_address_only() );
	}

	/**
	 * @return \WP_Error|true
	 */
	public function fetch() {
		$result = new \WP_Error();

		try {
			$label_result = Package::get_api()->get_label( $this );

			if ( is_wp_error( $label_result ) ) {
				$result = $label_result;
			}
		} catch ( \Exception $e ) {
			$errors = explode( PHP_EOL, $e->getMessage() );

			foreach ( $errors as $error ) {
				$result->add( $e->getCode() ? $e->getCode() : 'dhl-api-error', $error );
			}
		}

		if ( wc_stc_shipment_wp_error_has_errors( $result ) ) {
			return $result;
		} else {
			return true;
		}
	}

	public function delete( $force_delete = false ) {
		if ( $api = Package::get_api() ) {
			try {
				$api->delete_label( $this );
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

	public function get_plain_path( $context = 'view' ) {
		return $this->get_path( $context, 'default' );
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

	public function set_plain_path( $path ) {
		$this->set_path( $path, 'default' );
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
