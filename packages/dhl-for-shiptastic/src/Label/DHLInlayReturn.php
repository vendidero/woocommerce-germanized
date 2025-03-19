<?php

namespace Vendidero\Shiptastic\DHL\Label;

defined( 'ABSPATH' ) || exit;

/**
 * DHL ReturnLabel class.
 */
class DHLInlayReturn extends DHLReturn {

	public function get_type() {
		return 'inlay_return';
	}
}
