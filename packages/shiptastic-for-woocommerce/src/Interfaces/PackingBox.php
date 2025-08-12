<?php
namespace Vendidero\Shiptastic\Interfaces;

use DVDoug\BoxPacker\LimitedSupplyBox;
use Vendidero\Shiptastic\Packaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PackingBox extends LimitedSupplyBox {

	/**
	 * @return Packaging
	 */
	public function get_packaging();
}
