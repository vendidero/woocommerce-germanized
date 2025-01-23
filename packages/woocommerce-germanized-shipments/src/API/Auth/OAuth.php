<?php

namespace Vendidero\Germanized\Shipments\API\Auth;

abstract class OAuth extends Auth {

	public function get_type() {
		return 'oauth';
	}

	abstract protected function get_access_token();

	abstract public function get_url();

	public function get_headers() {
		$headers = array();

		if ( $this->has_auth() ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_access_token();
		}

		return $headers;
	}

	public function has_auth() {
		return $this->get_access_token() ? true : false;
	}
}
