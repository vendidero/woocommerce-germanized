<?php

namespace Vendidero\Germanized\DHL\ShippingProvider\Services;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class PreferredDay extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'                 => 'PreferredDay',
			'label'              => _x( 'Delivery day', 'dhl', 'woocommerce-germanized' ),
			'description'        => _x( 'Enable delivery day delivery.', 'dhl', 'woocommerce-germanized' ),
			'long_description'   => '<div class="wc-gzd-shipments-additional-desc">' . _x( 'Enabling this option will display options for the user to select their delivery day of delivery during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
			'setting_id'         => 'PreferredDay_enable',
			'products'           => array( 'V01PAK' ),
			'countries'          => array( 'DE' ),
			'zones'              => array( 'dom' ),
			'excluded_locations' => array( 'settings' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields   = parent::get_additional_label_fields( $shipment );
		$preferred_days = $this->get_preferred_day_options( $shipment->get_postcode() );
		$dhl_order      = wc_gzd_dhl_get_order( $shipment->get_order() );
		$value          = '';

		if ( $dhl_order && $dhl_order->has_preferred_day() ) {
			$value = $dhl_order->get_preferred_day()->format( 'Y-m-d' );
		}

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'day' ),
					'label'             => _x( 'Delivery day', 'dhl', 'woocommerce-germanized' ),
					'description'       => '',
					'value'             => $value,
					'options'           => wc_gzd_dhl_get_preferred_days_select_options( $preferred_days, '' ),
					'custom_attributes' => array( 'data-show-if-service_PreferredDay' => '' ),
					'type'              => 'select',
				),
			)
		);

		return $label_fields;
	}

	protected function get_preferred_day_options( $postcode ) {
		$preferred_days = array();

		try {
			$preferred_day_options = Package::get_api()->get_preferred_available_days( $postcode );

			if ( $preferred_day_options ) {
				$preferred_days = $preferred_day_options;
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $preferred_days;
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			$dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() );

			if ( $dhl_order && $dhl_order->has_preferred_day() ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	public function validate_label_request( $props, $shipment ) {
		$error          = new ShipmentError();
		$preferred_day  = isset( $props[ $this->get_label_field_id( 'day' ) ] ) ? $props[ $this->get_label_field_id( 'day' ) ] : '';
		$preferred_days = $this->get_preferred_day_options( $shipment->get_postcode() );

		if ( empty( $preferred_day ) || ! array_key_exists( $preferred_day, $preferred_days ) ) {
			$error->add( 500, _x( 'Please choose a valid preferred delivery day.', 'dhl', 'woocommerce-germanized' ) );
		}

		return wc_gzd_shipment_wp_error_has_errors( $error ) ? $error : true;
	}
}
