<?php

namespace Vendidero\Germanized\DHL\ShippingProvider\Services;

use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class AdditionalInsurance extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'          => 'AdditionalInsurance',
			'label'       => _x( 'Additional Insurance', 'dhl', 'woocommerce-germanized' ),
			'description' => _x( 'Add an additional insurance to labels.', 'dhl', 'woocommerce-germanized' ),
			'products'    => array( 'V01PAK', 'V53WPAK', 'V54EPAK' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'insurance_amount' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$value        = $shipment->get_total();

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'insurance_amount' ),
					'class'             => 'wc_input_decimal',
					'data_type'         => 'price',
					'label'             => _x( 'Value of Goods', 'dhl', 'woocommerce-germanized' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => wc_format_localized_decimal( $value ),
					'type'              => 'text',
					'custom_attributes' => array( 'data-show-if-service_AdditionalInsurance' => '' ),
					'is_required'       => true,
				),
			)
		);

		return $label_fields;
	}
}
