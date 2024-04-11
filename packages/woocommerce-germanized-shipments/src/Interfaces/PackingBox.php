<?php
namespace Vendidero\Germanized\Shipments\Interfaces;

use DVDoug\BoxPacker\Box;
use Vendidero\Germanized\Shipments\Packaging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PackingBox extends Box {

	/**
	 * @return Packaging
	 */
	public function get_packaging();
}
