<?php

namespace Vendidero\Shiptastic\DHL\ShippingProvider\Services;

use Vendidero\Shiptastic\ShipmentError;
use Vendidero\Shiptastic\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class CashOnDelivery extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'                 => 'CashOnDelivery',
			'label'              => _x( 'Cash on Delivery', 'dhl', 'woocommerce-germanized' ),
			'products'           => array( 'V01PAK', 'V53WPAK' ),
			'excluded_locations' => array( 'settings' ),
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

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			$dhl_order = wc_stc_dhl_get_order( $shipment->get_order() );

			if ( $dhl_order && $dhl_order->has_cod_payment() ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$value        = $shipment->get_total() + round( $shipment->get_additional_total(), wc_get_price_decimals() );

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'cod_total' ),
					'class'             => 'wc_input_decimal',
					'data_type'         => 'price',
					'label'             => _x( 'COD Amount', 'dhl', 'woocommerce-germanized' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => wc_format_localized_decimal( $value ),
					'type'              => 'text',
					'custom_attributes' => array( 'data-show-if-service_CashOnDelivery' => '' ),
					'is_required'       => true,
				),
			)
		);

		return $label_fields;
	}
}
