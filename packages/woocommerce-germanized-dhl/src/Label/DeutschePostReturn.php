<?php

namespace Vendidero\Germanized\DHL\Label;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentReturnLabel;

defined( 'ABSPATH' ) || exit;

/**
 * DHL ReturnLabel class.
 */
class DeutschePostReturn extends DeutschePost implements ShipmentReturnLabel {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'page_format'    => '',
		'position_x'     => 0,
		'position_y'     => 0,
		'shop_order_id'  => '',
		'stamp_total'    => 0,
		'voucher_id'     => '',
		'original_url'   => '',
		'manifest_url'   => '',
		'sender_address' => array(),
	);

	protected function get_hook_prefix() {
		return 'woocommerce_gzd_deutsche_post_return_label_get_';
	}

	public function get_type() {
		return 'return';
	}
}
