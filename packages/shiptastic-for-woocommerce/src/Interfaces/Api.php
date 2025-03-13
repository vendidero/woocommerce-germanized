<?php
namespace Vendidero\Shiptastic\Interfaces;

use Vendidero\Shiptastic\API\Auth\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Api {

	public function is_sandbox();

	public function set_is_sandbox( $is_sandbox );

	public function get_name();

	public function get_title();

	public function get_setting_name();

	/**
	 * @return false|Auth
	 */
	public function get_auth_api();

	public function get_url();
}
