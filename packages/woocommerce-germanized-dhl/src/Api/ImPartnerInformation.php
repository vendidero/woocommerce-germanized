<?php

namespace Vendidero\Germanized\DHL\Api;

class ImPartnerInformation extends \baltpeter\Internetmarke\PartnerInformation {

	/**
	 * @return string The signature to be appended to the request header
	 */
	protected function calculateSignature( $date = false ) {
		if ( ! $date ) {
			$date = new \DateTime( 'now', new \DateTimeZone( 'Europe/Berlin' ) );
		}

		return substr( md5( $this->partnerId . '::' . $date->format( 'dmY-His' ) . '::' . $this->keyPhase . '::' . $this->schlusselDpwnMeinmarktplatz ), 0, 8 ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @return array An array of SOAP headers to authenticate the request with the Internetmarke server. Valid for four minutes from `REQUEST_TIMESTAMP`
	 */
	public function soapHeaderArray() {
		$date = new \DateTime( 'now', new \DateTimeZone( 'Europe/Berlin' ) );

		return array(
			new \SoapHeader( 'https://internetmarke.deutschepost.de', 'PARTNER_ID', $this->partnerId ),  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			new \SoapHeader( 'https://internetmarke.deutschepost.de', 'REQUEST_TIMESTAMP', $date->format( 'dmY-His' ) ),
			new \SoapHeader( 'https://internetmarke.deutschepost.de', 'KEY_PHASE', $this->keyPhase ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			new \SoapHeader( 'https://internetmarke.deutschepost.de', 'PARTNER_SIGNATURE', $this->calculateSignature( $date ) ),
		);
	}
}
