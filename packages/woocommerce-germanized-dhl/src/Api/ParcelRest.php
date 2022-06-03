<?php

namespace Vendidero\Germanized\DHL\Api;

use Exception;
use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

class ParcelRest extends Rest {

	protected $account_num = '';

	public function __construct() {}

	public function get_services( $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'postcode'    => '',
				'account_num' => Package::get_setting( 'account_number' ),
				'start_date'  => '',
			)
		);

		if ( empty( $args['postcode'] ) ) {
			throw new Exception( _x( 'Please provide the receiver postnumber.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( empty( $args['account_num'] ) && ! Package::is_debug_mode() ) {
			throw new Exception( _x( 'Please set an account in the DHL shipping settings.', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( empty( $args['start_date'] ) ) {
			throw new Exception( _x( 'Please provide the shipment start date.', 'dhl', 'woocommerce-germanized' ) );
		}

		$this->account_num = $args['account_num'];

		return $this->get_request( '/checkout/' . $args['postcode'] . '/availableServices', array( 'startDate' => $args['start_date'] ) );
	}

	protected function set_header( $authorization = '', $request_type = 'GET', $endpoint = '' ) {
		parent::set_header();

		if ( ! empty( $authorization ) ) {
			$this->remote_header['Authorization'] = $authorization;
		}

		$this->remote_header['X-EKP'] = $this->account_num;
	}
}
