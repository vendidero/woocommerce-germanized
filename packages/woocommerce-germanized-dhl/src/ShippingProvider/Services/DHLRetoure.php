<?php

namespace Vendidero\Germanized\DHL\ShippingProvider\Services;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class DHLRetoure extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'                 => 'dhlRetoure',
			'label'              => _x( 'Inlay Return Label', 'dhl', 'woocommerce-germanized' ),
			'description'        => _x( 'Additionally create inlay return labels for shipments that support returns.', 'dhl', 'woocommerce-germanized' ),
			'products'           => array( 'V01PAK', 'V62WP', 'V62KP' ),
			'countries'          => array( 'DE' ),
			'zones'              => array( 'dom' ),
			'excluded_locations' => array( 'label_services' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'cod_total' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	public function get_label_fields( $shipment, $location = '' ) {
		$label_fields = parent::get_label_fields( $shipment, $location );

		if ( count( $label_fields ) > 0 ) {
			$label_fields[0]['class']             = 'show-if-trigger';
			$label_fields[0]['custom_attributes'] = array( 'data-show-if' => '.show-if-has-return' );
		}

		return $label_fields;
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields   = parent::get_additional_label_fields( $shipment );
		$field_prefix   = $this->get_label_field_id( 'return_address' );
		$return_address = array(
			'name'          => Package::get_dhl_shipping_provider()->get_return_name(),
			'company'       => Package::get_dhl_shipping_provider()->get_return_company(),
			'street'        => Package::get_dhl_shipping_provider()->get_return_street(),
			'street_number' => Package::get_dhl_shipping_provider()->get_return_street_number(),
			'postcode'      => Package::get_dhl_shipping_provider()->get_return_postcode(),
			'city'          => Package::get_dhl_shipping_provider()->get_return_city(),
			'phone'         => Package::get_dhl_shipping_provider()->get_return_phone(),
			'email'         => Package::get_dhl_shipping_provider()->get_return_email(),
		);

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'            => $field_prefix . '[name]',
					'label'         => _x( 'Name', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'value'         => $return_address['name'],
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return',
				),
				array(
					'id'            => $field_prefix . '[company]',
					'label'         => _x( 'Company', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'wrapper_class' => 'show-if-has-return',
					'type'          => 'text',
					'value'         => $return_address['company'],
				),
				array(
					'id'   => '',
					'type' => 'columns',
				),
				array(
					'id'            => $field_prefix . '[street]',
					'label'         => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return column col-9',
					'value'         => $return_address['street'],
				),
				array(
					'id'            => $field_prefix . '[street_number]',
					'label'         => _x( 'Street No', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return column col-3',
					'value'         => $return_address['street_number'],
				),
				array(
					'id'   => '',
					'type' => 'columns_end',
				),
				array(
					'id'   => '',
					'type' => 'columns',
				),
				array(
					'id'            => $field_prefix . '[postcode]',
					'label'         => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return column col-6',
					'value'         => $return_address['postcode'],
				),
				array(
					'id'            => $field_prefix . '[city]',
					'label'         => _x( 'City', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return column col-6',
					'value'         => $return_address['city'],
				),
				array(
					'id'   => '',
					'type' => 'columns_end',
				),
				array(
					'id'   => '',
					'type' => 'columns',
				),
				array(
					'id'            => $field_prefix . '[phone]',
					'label'         => _x( 'Phone', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return column col-6',
					'value'         => $return_address['phone'],
				),
				array(
					'id'            => $field_prefix . '[email]',
					'label'         => _x( 'Email', 'dhl', 'woocommerce-germanized' ),
					'placeholder'   => '',
					'description'   => '',
					'type'          => 'text',
					'wrapper_class' => 'show-if-has-return column col-6',
					'value'         => $return_address['email'],
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
		$error                = new ShipmentError();
		$return_address_field = $this->get_label_field_id( 'return_address' );
		$return_address       = wp_parse_args(
			isset( $props[ $return_address_field ] ) ? (array) $props[ $return_address_field ] : array(),
			array(
				'name'          => '',
				'company'       => '',
				'street'        => '',
				'street_number' => '',
				'postcode'      => '',
				'city'          => '',
				'phone'         => '',
				'email'         => '',
			)
		);

		$mandatory = array(
			'street'   => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
			'postcode' => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
			'city'     => _x( 'City', 'dhl', 'woocommerce-germanized' ),
		);

		foreach ( $mandatory as $mand => $title ) {
			if ( empty( $return_address[ $mand ] ) ) {
				$error->add( 500, sprintf( _x( '%s of the return address is a mandatory field.', 'dhl', 'woocommerce-germanized' ), $title ) );
			}
		}

		if ( empty( $return_address['name'] ) && empty( $return_address['company'] ) ) {
			$error->add( 500, _x( 'Please either add a return company or name.', 'dhl', 'woocommerce-germanized' ) );
		}

		return wc_gzd_shipment_wp_error_has_errors( $error ) ? $error : true;
	}
}
