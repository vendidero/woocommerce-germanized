<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZD_Gateway_Direct_Debit_Encryption_Helper {

	protected static $_instance = null;

	const LEGACY_HEADER_VERSION_SIZE = 4;

	const LEGACY_SALT_BYTE_SIZE = 32;

	const LEGACY_MAC_BYTE_SIZE = 32;

	const LEGACY_BLOCK_BYTE_SIZE = 16;

	const LEGACY_SERIALIZE_HEADER_BYTES = 4;

	const LEGACY_CHECKSUM_BYTE_SIZE = 32;

	const LEGACY_HASH_FUNCTION_NAME = 'sha256';

	const LEGACY_ENCRYPTION_INFO_STRING = 'DefusePHP|V2|KeyForEncryption';

	const LEGACY_KEY_BYTE_SIZE = 32;

	const LEGACY_CIPHER_METHOD = 'aes-256-ctr';

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
		return WC_GZD_Secret_Box_Helper::get_random_encryption_key();
	}

	public function is_configured() {
		return WC_GZD_Secret_Box_Helper::has_valid_encryption_key();
	}

	public function encrypt( $string ) {
		if ( empty( $string ) ) {
			return $string;
		}

		return WC_GZD_Secret_Box_Helper::encrypt( $string );
	}

	/**
	 * @param $string
	 * @param $is_legacy
	 *
	 * @return mixed|WP_Error
	 */
	public function decrypt( $string, $is_legacy = false ) {
		if ( empty( $string ) ) {
			return $string;
		}

		if ( $is_legacy ) {
			try {
				$decoded   = sodium_hex2bin( $string );
				$salt      = mb_substr( $decoded, self::LEGACY_HEADER_VERSION_SIZE, self::LEGACY_SALT_BYTE_SIZE, '8bit' );
				$iv        = mb_substr( $decoded, self::LEGACY_HEADER_VERSION_SIZE + self::LEGACY_SALT_BYTE_SIZE, self::LEGACY_BLOCK_BYTE_SIZE, '8bit' );
				$encrypted = mb_substr(
					$decoded,
					self::LEGACY_HEADER_VERSION_SIZE + self::LEGACY_SALT_BYTE_SIZE + self::LEGACY_BLOCK_BYTE_SIZE,
					mb_strlen( $decoded, '8bit' ) - self::LEGACY_MAC_BYTE_SIZE - self::LEGACY_SALT_BYTE_SIZE - self::LEGACY_BLOCK_BYTE_SIZE - self::LEGACY_HEADER_VERSION_SIZE,
					'8bit'
				);

				$key = sodium_hex2bin( WC_GZD_DIRECT_DEBIT_KEY );
				$key = mb_substr(
					$key,
					self::LEGACY_SERIALIZE_HEADER_BYTES,
					mb_strlen( $key, '8bit' ) - self::LEGACY_SERIALIZE_HEADER_BYTES - self::LEGACY_CHECKSUM_BYTE_SIZE,
					'8bit'
				);

				$e_key = $this->hkdf(
					self::LEGACY_HASH_FUNCTION_NAME,
					$key,
					self::LEGACY_KEY_BYTE_SIZE,
					self::LEGACY_ENCRYPTION_INFO_STRING,
					$salt
				);

				$decrypted = \openssl_decrypt(
					$encrypted,
					self::LEGACY_CIPHER_METHOD,
					$e_key,
					OPENSSL_RAW_DATA,
					$iv
				);

				if ( false === $decrypted ) {
					return new WP_Error( 'decrypt-decode', 'Error while decoding the encrypted message.' );
				}

				return $decrypted;
			} catch ( Exception $e ) {
				return new WP_Error( 'decrypt-decode', sprintf( 'Error while decoding the encrypted message: %s', $e->getMessage() ) );
			}
		} else {
			return WC_GZD_Secret_Box_Helper::decrypt( $string );
		}
	}

	/**
	 * Internal legacy method for PHP < 7 which might not include hash_hkdf.
	 * Only for use of legacy decrypting defuse/php-encryption encrypted data.
	 *
	 * @param $hash
	 * @param $ikm
	 * @param $length
	 * @param $info
	 * @param $salt
	 *
	 * @return string
	 * @throws Exception
	 */
	private function hkdf( $hash, $ikm, $length, $info = '', $salt = null ) {
		static $native_hkdf = null;

		if ( null === $native_hkdf ) {
			$native_hkdf = is_callable( '\\hash_hkdf' );
		}

		if ( $native_hkdf ) {
			if ( \is_null( $salt ) ) {
				$salt = '';
			}

			return \hash_hkdf( $hash, $ikm, $length, $info, $salt );
		}

		$digest_length = mb_strlen( \hash_hmac( $hash, '', '', true ), '8bit' );

		// Sanity-check the desired output length.
		$this->ensure_true(
			! empty( $length ) && \is_int( $length ) && $length >= 0 && $length <= 255 * $digest_length,
			'Bad output length requested of HDKF.'
		);

		// "if [salt] not provided, is set to a string of HashLen zeroes."
		if ( \is_null( $salt ) ) {
			$salt = \str_repeat( "\x00", $digest_length );
		}

		// HKDF-Extract:
		// PRK = HMAC-Hash(salt, IKM)
		// The salt is the HMAC key.
		$prk = \hash_hmac( $hash, $ikm, $salt, true );

		// HKDF-Expand:

		// This check is useless, but it serves as a reminder to the spec.
		$this->ensure_true( mb_strlen( $prk, '8bit' ) >= $digest_length );

		$t          = '';
		$last_block = '';
		$t_len      = mb_strlen( $t, '8bit' );

		for ( $block_index = 1; $t_len < $length; ++$block_index ) {
			// T(i) = HMAC-Hash(PRK, T(i-1) | info | 0x??)
			$last_block = \hash_hmac(
				$hash,
				$last_block . $info . \chr( $block_index ),
				$prk,
				true
			);
			$t         .= $last_block;
		}

		// ORM = first L octets of T
		/** @var string $orm */
		$orm = mb_substr( $t, 0, $length, '8bit' );
		$this->ensure_true( \is_string( $orm ) );
		return $orm;
	}

	private function ensure_true( $condition, $message = '' ) {
		if ( ! $condition ) {
			throw new Exception( $message );
		}
	}
}
