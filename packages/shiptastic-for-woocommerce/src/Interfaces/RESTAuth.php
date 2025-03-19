<?php
namespace Vendidero\Shiptastic\Interfaces;

use Vendidero\Shiptastic\API\Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RESTAuth extends ApiAuth {

	public function is_unauthenticated_response( $code );
	/**
	 * @return array
	 */
	public function get_headers();

	public function revoke();

	public function get_url();
}
