<?php
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\API\Auth\RESTAuth;
use Vendidero\Shiptastic\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ApiKeyAuth extends RESTAuth {

	public function get_api_key() {
		if ( $this->get_api()->is_sandbox() ) {
			return 'demo-key';
		} else {
			return defined( 'WC_STC_DHL_LOCATION_FINDER_API_KEY' ) ? WC_STC_DHL_LOCATION_FINDER_API_KEY : Package::get_dhl_com_api_key();
		}
	}

	public function get_headers() {
		$headers['DHL-API-Key'] = $this->get_api_key();

		return $headers;
	}

	public function get_type() {
		return 'dhl_api_key_auth';
	}

	public function auth() {
		return true;
	}

	public function has_auth() {
		return true;
	}

	public function get_url() {
		return '';
	}

	public function revoke() {
		return true;
	}

	public function is_connected() {
		return true;
	}
}
