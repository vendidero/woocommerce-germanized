<?php

namespace Vendidero\Germanized\DHL\Api;

use Vendidero\Germanized\DHL\Package;
use WsdlToPhp\WsSecurity\WsSecurity;

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
		return WsSecurity::createWsSecuritySoapHeader( Package::get_internetmarke_product_username(), Package::get_internetmarke_product_password() );
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
