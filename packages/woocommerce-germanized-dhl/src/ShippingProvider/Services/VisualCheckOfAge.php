<?php

namespace Vendidero\Germanized\DHL\ShippingProvider\Services;

use Vendidero\Germanized\Shipments\Labels\ConfigurationSet;
use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class VisualCheckOfAge extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'          => 'VisualCheckOfAge',
			'label'       => _x( 'Visual Age check', 'dhl', 'woocommerce-germanized' ),
			'description' => _x( 'Let DHL handle the age check for you at the point of delivery.', 'dhl', 'woocommerce-germanized' ),
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

		if ( 'min_age' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$dhl_order    = wc_gzd_dhl_get_order( $shipment->get_order() );
		$min_age      = $this->get_value( $shipment, 'min_age' );

		if ( $dhl_order && $dhl_order->needs_age_verification() && 'yes' === $this->get_shipping_provider()->get_setting( 'label_auto_age_check_sync' ) ) {
			$min_age = $dhl_order->get_min_age();
		}

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'min_age' ),
					'label'             => _x( 'Minimum Age', 'dhl', 'woocommerce-germanized' ),
					'description'       => '',
					'type'              => 'select',
					'value'             => $min_age,
					'options'           => wc_gzd_dhl_get_visual_min_ages(),
					'custom_attributes' => array( 'data-show-if-service_VisualCheckOfAge' => '' ),
				),
			)
		);

		return $label_fields;
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			$dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() );

			if ( $dhl_order && $dhl_order->needs_age_verification() && 'yes' === $this->get_shipping_provider()->get_setting( 'label_auto_age_check_sync' ) ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	public function validate_label_request( $props, $shipment ) {
		$error   = new ShipmentError();
		$min_age = isset( $props[ $this->get_label_field_id( 'min_age' ) ] ) ? $props[ $this->get_label_field_id( 'min_age' ) ] : '';

		if ( empty( $min_age ) || ! wc_gzd_dhl_is_valid_visual_min_age( $min_age ) ) {
			$error->add( 500, _x( 'The minimum age (VisualCheckOfAge) supplied is invalid.', 'dhl', 'woocommerce-germanized' ) );
		}

		return wc_gzd_shipment_wp_error_has_errors( $error ) ? $error : true;
	}
}
