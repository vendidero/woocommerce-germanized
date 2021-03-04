<?php

namespace Vendidero\Germanized\DHL;
use Vendidero\Germanized\Shipments\Interfaces\ShipmentReturnLabel;

defined( 'ABSPATH' ) || exit;

/**
 * DHL ReturnLabel class.
 */
class DeutschePostReturnLabel extends DeutschePostLabel implements ShipmentReturnLabel {

	protected function get_hook_prefix() {
		return 'woocommerce_gzd_deutsche_post_return_label_get_';
	}

	public function get_type() {
		return 'deutsche_post_return';
	}
}
