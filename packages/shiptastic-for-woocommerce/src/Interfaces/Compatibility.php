<?php

namespace Vendidero\Shiptastic\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Compatibility {

	public static function is_active();

	public static function init();
}
