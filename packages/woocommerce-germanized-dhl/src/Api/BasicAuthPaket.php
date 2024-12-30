<?php
namespace Vendidero\Germanized\DHL\Api;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\API\Auth\Basic;
use Vendidero\Germanized\Shipments\API\Auth\OAuth;
use Vendidero\Germanized\Shipments\DataStores\Shipment;
use Vendidero\Germanized\Shipments\SecretBox;
use Vendidero\Germanized\Shipments\ShipmentError;

defined( 'ABSPATH' ) || exit;

class BasicAuthPaket extends Basic {

	public function get_username() {
		return Package::is_debug_mode() ? 'user-valid' : Package::get_gk_api_user();
	}

	public function get_password() {
		return Package::is_debug_mode() ? 'SandboxPasswort2023!' : Package::get_gk_api_signature();
	}

	public function get_headers() {
		$headers                = parent::get_headers();
		$headers['dhl-api-key'] = Package::get_dhl_com_api_key();

		return $headers;
	}
}
