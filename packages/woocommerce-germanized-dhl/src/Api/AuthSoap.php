<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL\Api;

use Vendidero\Germanized\DHL\Package;
use Exception;
use SoapClient;
use SoapHeader;

defined( 'ABSPATH' ) || exit;

// Singleton API connection class
class AuthSoap {

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
	public function __construct( $wsdl_link ) {
		$this->wsdl_link = Package::get_wsdl_file( $wsdl_link );
	}

	public function get_access_token( $client_id = '', $client_secret = '' ) {
		try {
			$soap_client = new SoapClient(
				$this->wsdl_link,
				array(
					'login'        => Package::get_cig_user(),
					'password'     => Package::get_cig_password(),
					'location'     => Package::get_cig_url(),
					'soap_version' => SOAP_1_1,
					'trace'        => true,
				)
			);
		} catch ( Exception $e ) {
			throw $e;
		}

		if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
			$soap_authentication = array(
				'user'      => $client_id,
				'signature' => $client_secret,
				'type'      => 0,
			);

			$soap_auth_header = new SoapHeader( self::PR_DHL_HEADER_LINK, 'Authentification', $soap_authentication );

			$soap_client->__setSoapHeaders( $soap_auth_header );
		}

		return $soap_client;
	}
}
