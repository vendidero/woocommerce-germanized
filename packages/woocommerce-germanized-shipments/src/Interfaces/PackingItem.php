<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use DVDoug\BoxPacker\ConstrainedPlacementItem;
use DVDoug\BoxPacker\Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PackingItem extends Item, ConstrainedPlacementItem {

	public function get_product();

	public function get_reference();

	public function get_id();

	public function get_total();

	public function get_subtotal();
}
