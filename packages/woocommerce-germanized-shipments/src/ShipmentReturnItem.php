<?php

namespace Vendidero\Germanized\Shipments;

use WC_Data;
use WC_Data_Store;
use Exception;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Order item class.
 */
class ShipmentReturnItem extends ShipmentItem {

	protected $extra_data = array(
		'return_reason_code' => '',
	);

	public function get_type() {
		return 'return';
	}

	public function get_return_reason_code( $context = 'view' ) {
		return $this->get_prop( 'return_reason_code', $context );
	}

	public function set_return_reason_code( $code ) {
		$this->set_prop( 'return_reason_code', $code );
	}
}
