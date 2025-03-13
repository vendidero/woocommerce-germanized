<?php
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\API\Auth\Basic;
use Vendidero\Shiptastic\API\Auth\OAuth;
use Vendidero\Shiptastic\DataStores\Shipment;
use Vendidero\Shiptastic\SecretBox;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class BasicAuthPaket extends Basic {

	public function get_username() {
		return Package::get_gk_api_user( $this->get_api()->is_sandbox() );
	}

	public function get_password() {
		return Package::get_gk_api_signature( $this->get_api()->is_sandbox() );
	}

	public function get_headers() {
		$headers                = parent::get_headers();
		$headers['dhl-api-key'] = Package::get_dhl_com_api_key();

		return $headers;
	}
}
