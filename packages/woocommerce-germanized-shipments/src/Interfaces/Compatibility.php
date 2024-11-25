<?php

namespace Vendidero\Germanized\Shipments\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface Compatibility {

	public static function is_active();

	public static function init();
}
