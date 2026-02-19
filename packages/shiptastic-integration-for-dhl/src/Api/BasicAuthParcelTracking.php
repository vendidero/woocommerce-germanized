<?php

namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\API\Auth\Basic;
use Vendidero\Shiptastic\DHL\Package;

class BasicAuthParcelTracking extends Basic {

	public function get_type() {
		return 'de_parcel_tracking';
	}

	public function get_xml_username() {
		return $this->get_api()->is_sandbox() ? 'zt12345' : Package::get_gk_api_user();
	}

	public function get_xml_password() {
		return $this->get_api()->is_sandbox() ? 'geheim' : Package::get_gk_api_signature();
	}

	protected function get_username() {
		return Package::get_dhl_com_api_key();
	}

	protected function get_password() {
		return Package::get_dhl_com_api_secret();
	}
}
