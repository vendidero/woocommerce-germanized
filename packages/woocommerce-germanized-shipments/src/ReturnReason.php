<?php

namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

/**
 * Return reason
 *
 * @class       ReturnReason
 * @version     1.0.0
 * @author      Vendidero
 */
class ReturnReason {

	protected $args = array();

	public function __construct( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'code'   => '',
				'reason' => '',
				'order'  => 0,
			)
		);

		$this->args = $args;
	}

	public function get_reason() {
		return $this->args['reason'];
	}

	public function get_code() {
		return $this->args['code'];
	}

	public function get_name() {
		return $this->get_code();
	}

	public function get_order() {
		return absint( $this->args['order'] );
	}
}
