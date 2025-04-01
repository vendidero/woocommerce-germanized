<?php
namespace Vendidero\Shiptastic\Interfaces;

use Vendidero\Shiptastic\API\Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ApiAuth {

	public function get_type();

	/**
	 * @return Api
	 */
	public function get_api();

	public function get_url();

	/**
	 * @return Response|true
	 */
	public function auth();

	/**
	 * @return bool
	 */
	public function has_auth();

	public function is_connected();
}
