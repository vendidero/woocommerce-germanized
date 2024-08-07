<?php

namespace Vendidero\Germanized\DHL\Api;

use baltpeter\Internetmarke\PartnerInformation;
use baltpeter\Internetmarke\User;
use Vendidero\Germanized\DHL\Package;

class ImRefundSoap extends \SoapClient {

	/**
	 * Service constructor.
	 *
	 * @param $partner_information PartnerInformation
	 * @param array $options A array of config values for `SoapClient` (see PHP docs)
	 * @param string $wsdl The wsdl file to use (defaults to 'https://internetmarke.deutschepost.de/OneClickForAppV3?wsdl')
	 */
	public function __construct( $partner_information, $options = array(), $wsdl = null ) {
		$this->partner_information = $partner_information;
		$options                   = array_merge( array( 'features' => SOAP_SINGLE_ELEMENT_ARRAYS ), $options );

		if ( null === $wsdl ) {
			$wsdl = Package::get_internetmarke_refund_url();
		}

		parent::__construct( $wsdl, $options );

		$this->__setSoapHeaders( $this->partner_information->soapHeaderArray() );
	}

	/**
	 * Authenticate user request.
	 *
	 * @return User
	 */
	public function authenticateUser( $username, $password ) {
		$result = $this->__soapCall(
			'authenticateUser',
			array(
				'AuthenticateUserRequest' => array(
					'username' => $username,
					'password' => $password,
				),
			)
		);

		return User::fromStdObject( $result );
	}

	/**
	 * Return retoure id.
	 *
	 * @return int
	 */
	public function createRetoureId() {
		$result = $this->__soapCall(
			'createRetoureId',
			array(
				'CreateRetoureIdRequest' => '',
			)
		);

		return $result->shopRetoureId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Retoure/refund selected shipping labels.
	 *
	 * @param $user_token
	 * @param $shop_retoure_id
	 * @param $shop_retoure_id
	 * @param $voucher_set
	 *
	 * @return int
	 */
	public function retoureVouchers( $user_token, $shop_retoure_id, $shop_order_id, $voucher_set = array() ) {
		$data = array(
			'RetoureVouchersRequest' => array(
				'userToken'     => $user_token,
				'shopRetoureId' => $shop_retoure_id,
				'shoppingCart'  => array(
					'shopOrderId' => $shop_order_id,
				),
			),
		);

		if ( ! empty( $voucher_set ) ) {
			$data['RetoureVouchersRequest']['shoppingCart']['voucherSet'] = array();

			foreach ( $voucher_set as $voucher_no ) {
				$data['RetoureVouchersRequest']['shoppingCart']['voucherSet']['voucherNo'] = $voucher_no;
			}
		}

		$result = $this->__soapCall( 'retoureVouchers', $data );

		return $result->retoureTransactionId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
