<?php

use \Defuse\Crypto;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Gateway_Direct_Debit_Encryption_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		// Make sure that random_int exists
		if ( ! function_exists( 'random_int' ) ) {
			require_once ABSPATH . WPINC . '/random_compat/random.php';
		}
	}

	public function get_random_key() {
		$key = Crypto\Key::createNewRandomKey();

		return $key->saveToAsciiSafeString();
	}

	public function is_configured() {
		return ( $this->get_key() ? true : false );
	}

	public function encrypt( $string ) {
		if ( empty( $string ) ) {
			return $string;
		}

		return Crypto\Crypto::encrypt( $string, $this->get_key() );
	}

	public function decrypt( $string ) {
		if ( empty( $string ) ) {
			return $string;
		}

		$secret_data = $string;

		try {
			$secret_data = Crypto\Crypto::decrypt( $string, $this->get_key() );
		} catch ( Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $secret_data;
	}

	private function get_key() {
		if ( defined( 'WC_GZD_DIRECT_DEBIT_KEY' ) ) {
			try {
				return Crypto\Key::loadFromAsciiSafeString( WC_GZD_DIRECT_DEBIT_KEY );
			} catch ( \Exception $e ) {
				return false;
			}
		}

		return false;
	}

}
