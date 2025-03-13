<?php

namespace Vendidero\Shiptastic\API\Auth;

abstract class Basic extends RESTAuth {

	public function get_type() {
		return 'basic';
	}

	public function auth() {
		return true;
	}

	public function get_url() {
		return '';
	}

	abstract protected function get_username();

	abstract protected function get_password();

	public function is_connected() {
		return ! empty( $this->get_username() );
	}

	public function get_headers() {
		$headers = array();

		if ( $this->has_auth() ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( $this->get_username() . ':' . $this->get_password() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return $headers;
	}

	public function has_auth() {
		return ! empty( $this->get_username() ) && ! empty( $this->get_password() );
	}

	public function revoke() {
	}
}
