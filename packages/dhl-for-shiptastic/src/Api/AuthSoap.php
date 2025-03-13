<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\DHL\Api;

use Vendidero\Shiptastic\API\Auth\Auth;
use Vendidero\Shiptastic\DHL\Package;
use Exception;
use SoapClient;
use SoapHeader;

defined( 'ABSPATH' ) || exit;

class AuthSoap extends Auth {

	/**
	 * define Auth API endpoint
	 */
	const PR_DHL_HEADER_LINK = 'http://dhl.de/webservice/cisbase';

	/**
	 * @var string
	 */
	private $wsdl_link;

	/**
	 * constructor.
	 */
	public function __construct( $wsdl_link, $api ) {
		parent::__construct( $api );

		$this->wsdl_link = Package::get_wsdl_file( $wsdl_link );
	}

	protected function get_cig_login() {
		return Package::get_cig_user( $this->get_api()->is_sandbox() );
	}

	protected function get_cig_password() {
		return Package::get_cig_password( $this->get_api()->is_sandbox() );
	}

	protected function get_client_id() {
		return Package::get_gk_api_user( $this->get_api()->is_sandbox() );
	}

	protected function get_client_password() {
		return Package::get_gk_api_signature( $this->get_api()->is_sandbox() );
	}

	public function get_client() {
		try {
			$args = array(
				'login'              => $this->get_cig_login(),
				'password'           => $this->get_cig_password(),
				'location'           => $this->get_url(),
				'soap_version'       => SOAP_1_1,
				'trace'              => true,
				'connection_timeout' => 10,
			);

			if ( Package::is_debug_mode() || $this->get_api()->is_sandbox() ) {
				$args['cache_wsdl'] = WSDL_CACHE_NONE;
			}

			$soap_client = new SoapClient(
				$this->wsdl_link,
				$args
			);
		} catch ( Exception $e ) {
			throw $e;
		}

		if ( $this->get_client_id() && $this->get_client_password() ) {
			$soap_authentication = array(
				'user'      => $this->get_client_id(),
				'signature' => $this->get_client_password(),
				'type'      => 0,
			);

			$soap_auth_header = new SoapHeader( self::PR_DHL_HEADER_LINK, 'Authentification', $soap_authentication );

			$soap_client->__setSoapHeaders( $soap_auth_header );
		}

		return $soap_client;
	}

	public function get_type() {
		return 'dhl_auth_soap';
	}

	public function get_url() {
		return $this->get_api()->is_sandbox() ? 'https://cig.dhl.de/services/sandbox/soap' : 'https://cig.dhl.de/services/production/soap';
	}

	public function auth() {}

	public function has_auth() {
		return true;
	}

	public function is_connected() {
		return ! empty( $this->get_client_id() ) && ! empty( $this->get_client_password() );
	}
}
