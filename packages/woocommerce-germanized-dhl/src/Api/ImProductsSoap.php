<?php

namespace Vendidero\Germanized\DHL\Api;

use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ImProductsSoap extends \SoapClient {

	public function __construct( $options = array(), $wsdl = null ) {
		$options = array_merge( array( 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ), $options );

		if ( null === $wsdl ) {
			$wsdl = Package::get_internetmarke_products_url();
		}

		parent::__construct( $wsdl, $options );

		$this->__setSoapHeaders( $this->get_headers() );
	}

	protected function get_headers() {
		$username = Package::get_internetmarke_product_username();
		$password = Package::get_internetmarke_product_password();
		$nonce    = base64_encode( pack( 'H*', wp_rand() ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$xml = '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
		<wsse:UsernameToken>
			<wsse:Username>' . esc_html( $username ) . '</wsse:Username>
			<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . esc_html( $password ) . '</wsse:Password>
			<wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . esc_html( $nonce ) . '</wsse:Nonce>
		</wsse:UsernameToken>
		</wsse:Security>';

		return new \SoapHeader( 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', new \SoapVar( $xml, XSD_ANYXML ), false );
	}

	public function get_products( $dedicated_products = 1 ) {
		return $this->__soapCall(
			'getProductList',
			array(
				'getProductListRequest' => array(
					'mandantID'         => Package::get_internetmarke_product_mandant_id(),
					'dedicatedProducts' => $dedicated_products,
					'responseMode'      => 0,
				),
			)
		);
	}
}
