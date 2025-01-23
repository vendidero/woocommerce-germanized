<?php

namespace Vendidero\Germanized\DHL\ShippingProvider\Services;

use Vendidero\Germanized\Shipments\ShipmentError;
use Vendidero\Germanized\Shipments\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class PreferredNeighbour extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'                 => 'PreferredNeighbour',
			'label'              => _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
			'description'        => _x( 'Enable delivery to a neighbor.', 'dhl', 'woocommerce-germanized' ),
			'long_description'   => '<div class="wc-gzd-shipments-additional-desc">' . _x( 'Enabling this option will display options for the user to deliver to their preferred neighbor during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
			'setting_id'         => 'PreferredNeighbour_enable',
			'products'           => array( 'V01PAK', 'V62WP', 'V62KP' ),
			'countries'          => array( 'DE' ),
			'zones'              => array( 'dom' ),
			'excluded_locations' => array( 'settings' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$dhl_order    = wc_gzd_dhl_get_order( $shipment->get_order() );
		$value        = '';

		if ( $dhl_order && $dhl_order->has_preferred_neighbor() ) {
			$value = $dhl_order->get_preferred_neighbor_formatted_address();
		}

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'neighbor' ),
					'label'             => _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => $value,
					'custom_attributes' => array(
						'maxlength' => '80',
						'data-show-if-service_PreferredNeighbour' => '',
					),
					'type'              => 'text',
					'is_required'       => true,
				),
			)
		);

		return $label_fields;
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			$dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() );

			if ( $dhl_order && $dhl_order->has_preferred_neighbor() ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}
}
