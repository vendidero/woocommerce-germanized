<?php

namespace Vendidero\Shiptastic;

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
		$reason_code = $this->get_prop( 'return_reason_code', $context );

		if ( 'view' === $context && ( $parent = $this->get_parent() ) ) {
			$reason_code = $parent->get_return_reason_code();
		}

		return $reason_code;
	}

	public function set_return_reason_code( $code ) {
		$this->set_prop( 'return_reason_code', $code );
	}
}
