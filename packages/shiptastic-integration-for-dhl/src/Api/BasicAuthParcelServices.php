<?php
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\API\Auth\Basic;
use Vendidero\Shiptastic\API\Auth\OAuth;
use Vendidero\Shiptastic\DataStores\Shipment;
use Vendidero\Shiptastic\SecretBox;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class BasicAuthParcelServices extends Basic {

	public function get_username() {
		return Package::get_cig_user( $this->get_api()->is_sandbox() );
	}

	public function get_password() {
		return Package::get_cig_password( $this->get_api()->is_sandbox() );
	}

	protected function get_account_number() {
		if ( $this->get_api()->is_sandbox() ) {
			return '2222222222';
		} else {
			return Package::get_account_number();
		}
	}

	public function get_headers() {
		$headers          = parent::get_headers();
		$headers['X-EKP'] = $this->get_account_number();

		return $headers;
	}
}
