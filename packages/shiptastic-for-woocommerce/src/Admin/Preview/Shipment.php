<?php

namespace Vendidero\Shiptastic\Admin\Preview;

use Vendidero\Shiptastic\ShipmentItem;
use Vendidero\Shiptastic\ShippingProvider\Helper;
use Vendidero\Shiptastic\SimpleShipment;

defined( 'ABSPATH' ) || exit;

class Shipment extends SimpleShipment {

	public function __construct( $data = 0 ) {
		parent::__construct( 0 );

		$this->set_tracking_id( '12345678' );
		$this->set_id( 1234 );

		$this->set_address(
			array(
				'first_name' => _x( 'John', 'shipments-email-preview-name', 'woocommerce-germanized' ),
				'last_name'  => _x( 'Doe', 'shipments-email-preview-name', 'woocommerce-germanized' ),
				'address_1'  => _x( '123 Sample Street', 'shipments-email-preview-address', 'woocommerce-germanized' ),
				'city'       => _x( 'Los Angeles', 'shipments-email-preview-city', 'woocommerce-germanized' ),
				'postcode'   => _x( '12345', 'shipments-email-preview-postcode', 'woocommerce-germanized' ),
				'country'    => _x( 'US', 'shipments-email-preview-country', 'woocommerce-germanized' ),
				'state'      => _x( 'CA', 'shipments-email-preview-state', 'woocommerce-germanized' ),
				'email'      => _x( 'john@company.com', 'shipments-email-preview-email', 'woocommerce-germanized' ),
			)
		);

		$item = new ShipmentItem( 0 );
		$item->set_name( _x( 'Sample item', 'shipments-email-preview-item', 'woocommerce-germanized' ) );
		$item->set_weight( 5 );
		$item->set_quantity( 2 );
		$item->set_height( 10 );
		$item->set_length( 10 );
		$item->set_width( 10 );
		$item->set_total( 15 );

		$this->add_item( $item );

		$available_providers = Helper::instance()->get_available_shipping_providers();

		if ( ! empty( $available_providers ) ) {
			$provider = array_values( $available_providers )[0];

			$this->set_shipping_provider( $provider->get_name() );
		}
	}
}
