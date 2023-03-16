<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Exception;
use WC_Order;
use WC_Customer;
use WC_DateTime;
use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class       WC_GZD_Shipment_Order
 * @version     1.0.0
 * @author      Vendidero
 */
class MethodPlaceholder extends Method {
	public function __construct( $id ) {
		parent::__construct( $id, true );
	}
}
