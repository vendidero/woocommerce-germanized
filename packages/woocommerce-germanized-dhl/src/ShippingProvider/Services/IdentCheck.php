<?php

namespace Vendidero\Germanized\DHL\ShippingProvider\Services;

use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;
use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class IdentCheck extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'          => 'IdentCheck',
			'label'       => _x( 'Ident-Check', 'dhl', 'woocommerce-germanized' ),
			'description' => _x( 'Use the DHL Ident-Check service to make sure your parcels are only released to the recipient in person.', 'dhl', 'woocommerce-germanized' ),
			'products'    => array( 'V01PAK' ),
			'countries'   => array( 'DE' ),
			'zones'       => array( 'dom' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return array[]
	 */
	protected function get_additional_setting_fields( $configuration_set ) {
		$base_setting_id = $this->get_setting_id( $configuration_set );
		$setting_id      = $this->get_setting_id( $configuration_set, 'min_age' );
		$value           = $configuration_set->get_service_meta( $this->get_id(), 'min_age', '0' );

		return array(
			array(
				'title'             => _x( 'Minimum age', 'dhl', 'woocommerce-germanized' ),
				'id'                => $setting_id,
				'type'              => 'select',
				'default'           => '0',
				'value'             => $value,
				'options'           => wc_gzd_dhl_get_ident_min_ages(),
				'custom_attributes' => array( "data-show_if_{$base_setting_id}" => '' ),
				'desc_tip'          => _x( 'Choose this option if you want to let DHL check your customer\'s identity and age.', 'dhl', 'woocommerce-germanized' ),
			),
		);
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'date_of_birth' === $suffix ) {
			$default_value = '';
		} elseif ( 'min_age' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			$dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() );

			if ( $dhl_order && $dhl_order->needs_age_verification() && 'yes' === $this->get_shipping_provider()->get_setting( 'label_auto_age_check_ident_sync' ) ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields  = parent::get_additional_label_fields( $shipment );
		$dhl_order     = wc_gzd_dhl_get_order( $shipment->get_order() );
		$min_age       = $this->get_value( $shipment, 'min_age' );
		$date_of_birth = $this->get_value( $shipment, 'date_of_birth' );

		if ( $dhl_order && $dhl_order->needs_age_verification() && 'yes' === $this->get_shipping_provider()->get_setting( 'label_auto_age_check_ident_sync' ) ) {
			$min_age = $dhl_order->get_min_age();
		}

		if ( $dhl_order ) {
			$date_of_birth = $dhl_order->get_date_of_birth();
		}

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'   => '',
					'type' => 'columns',
				),
				array(
					'id'                => $this->get_label_field_id( 'date_of_birth' ),
					'label'             => _x( 'Date of Birth', 'dhl', 'woocommerce-germanized' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => $date_of_birth,
					'custom_attributes' => array(
						'pattern'                         => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'maxlength'                       => 10,
						'data-show-if-service_IdentCheck' => '',
					),
					'class'             => 'short date-picker',
					'wrapper_class'     => 'column col-6',
					'type'              => 'text',
				),
				array(
					'id'                => $this->get_label_field_id( 'min_age' ),
					'label'             => _x( 'Minimum age', 'dhl', 'woocommerce-germanized' ),
					'description'       => '',
					'type'              => 'select',
					'value'             => $min_age,
					'options'           => wc_gzd_dhl_get_ident_min_ages(),
					'custom_attributes' => array( 'data-show-if-service_IdentCheck' => '' ),
					'wrapper_class'     => 'column col-6',
				),
				array(
					'id'   => '',
					'type' => 'columns_end',
				),
			)
		);

		return $label_fields;
	}

	public function validate_label_request( $props, $shipment ) {
		$error         = new ShipmentError();
		$date_of_birth = isset( $props[ $this->get_label_field_id( 'date_of_birth' ) ] ) ? $props[ $this->get_label_field_id( 'date_of_birth' ) ] : '';
		$min_age       = isset( $props[ $this->get_label_field_id( 'min_age' ) ] ) ? $props[ $this->get_label_field_id( 'min_age' ) ] : '';

		if ( ! empty( $date_of_birth ) && ! wc_gzd_dhl_is_valid_datetime( $date_of_birth, 'Y-m-d' ) ) {
			$error->add( 500, _x( 'There was an error parsing the date of birth for the identity check.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( ! empty( $min_age ) && ! wc_gzd_dhl_is_valid_ident_min_age( $min_age ) ) {
			$error->add( 500, _x( 'The minimum age (IdentCheck) supplied is invalid.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( empty( $date_of_birth ) && empty( $min_age ) ) {
			$error->add( 500, _x( 'Either a minimum age or a date of birth is need for the ident check.', 'dhl', 'woocommerce-germanized' ) );
		}

		return wc_gzd_shipment_wp_error_has_errors( $error ) ? $error : true;
	}
}
