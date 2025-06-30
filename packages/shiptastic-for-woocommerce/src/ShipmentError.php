<?php

namespace Vendidero\Shiptastic;

class ShipmentError extends \WP_Error {

	protected $is_soft_error = false;

	public function __construct( $code = '', $message = '', $data = '' ) {
		parent::__construct( $code, $message, $data );

		$this->set_is_soft_error();
	}

	public function add( $code, $message, $data = '' ) {
		parent::add( $code, $message, $data );

		$this->set_is_soft_error();
	}

	public function get_error_messages_by_type() {
		$errors = $this->get_error_messages();
		$soft   = $this->get_soft_error_messages();

		return array(
			'error' => array_diff( $errors, $soft ),
			'soft'  => $soft,
		);
	}

	public function get_soft_error_messages( $code = '' ) {
		// Return all messages if no code specified.
		if ( empty( $code ) ) {
			$all_messages = array();
			foreach ( (array) $this->errors as $code => $messages ) {
				$data = $this->get_error_data( $code );

				if ( is_string( $data ) && 'soft' === $data ) {
					$all_messages = array_merge( $all_messages, $messages );
				}
			}

			return $all_messages;
		}

		if ( isset( $this->errors[ $code ] ) ) {
			$data = $this->get_error_data( $code );

			if ( is_string( $data ) && 'soft' === $data ) {
				return $this->errors[ $code ];
			}

			return array();
		} else {
			return array();
		}
	}

	public function add_soft_error( $code, $message ) {
		parent::add( $code, $message, 'soft' );
	}

	public function is_soft_error() {
		return $this->is_soft_error;
	}

	protected function set_is_soft_error() {
		$is_soft_error = true;

		foreach ( $this->get_error_codes() as $code ) {
			$error_data = $this->get_error_data( $code );

			if ( ! is_string( $error_data ) || 'soft' !== $error_data ) {
				$is_soft_error = false;
				break;
			}
		}

		$this->is_soft_error = $is_soft_error;
	}

	public static function from_wp_error( \WP_Error $from ) {
		$error = new self();
		$error->merge_from( $from );

		return $error;
	}

	/**
	 * Merges the errors in the given error object into this one.
	 *
	 *
	 * @param \WP_Error|ShipmentError $error Error object to merge.
	 */
	public function merge_from( $error ) {
		static::copy_errors( $error, $this );
	}

	/**
	 * Copies errors from one WP_Error instance to another.
	 *
	 *
	 * @param \WP_Error $from The WP_Error to copy from.
	 * @param \WP_Error $to   The WP_Error to copy to.
	 */
	protected static function copy_errors( $from, $to ) {
		foreach ( $from->get_error_codes() as $code ) {
			foreach ( $from->get_error_messages( $code ) as $error_message ) {
				$to->add( $code, $error_message );
			}

			foreach ( $from->get_all_error_data( $code ) as $data ) {
				$to->add_data( $data, $code );
			}
		}
	}

	/**
	 * Verifies if the instance contains errors.
	 *
	 * @return bool If the instance contains errors.
	 */
	public function has_errors() {
		if ( ! empty( $this->errors ) ) {
			return true;
		}
		return false;
	}
}
