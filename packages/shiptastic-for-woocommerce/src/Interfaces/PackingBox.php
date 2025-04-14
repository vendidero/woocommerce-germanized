<?php
namespace Vendidero\Shiptastic\Interfaces;

use DVDoug\BoxPacker\Box;
use Vendidero\Shiptastic\Packaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PackingBox extends Box {

	/**
	 * @return Packaging
	 */
	public function get_packaging();
}
