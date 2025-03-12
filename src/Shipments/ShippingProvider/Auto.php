<?php
namespace Vendidero\Germanized\Shipments\ShippingProvider;

defined( 'ABSPATH' ) || exit;

abstract class Auto extends \Vendidero\Shiptastic\ShippingProvider\Auto {

	protected function fetch_pickup_location( $location_code, $address ) {
		return null;
	}
}
