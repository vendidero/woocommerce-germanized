<?php

namespace Vendidero\Germanized\Blocks;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_action( 'woocommerce_blocks_checkout_block_registration', function( $integration_registry ) {
			$integration_registry->register( new Integration() );
		}, 10, 1 );
	}
}