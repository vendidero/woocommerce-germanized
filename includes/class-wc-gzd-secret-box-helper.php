<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_GZD_Secret_Box_Helper' ) && function_exists( 'sodium_crypto_secretbox_keygen' ) && defined( 'SODIUM_CRYPTO_PWHASH_SALTBYTES' ) ) {

	class WC_GZD_Secret_Box_Helper {

		public static function get_encryption_key_notice( $encryption_type = '', $explanation = '' ) {
			$notice = '';

			if ( ! self::has_valid_encryption_key( $encryption_type ) ) {
				$constant = self::get_encryption_key_constant( $encryption_type );
				$new_key  = self::get_random_encryption_key();

				if ( is_wp_error( $new_key ) ) {
					return '';
				}

				if ( empty( $explanation ) ) {
					if ( empty( $encryption_type ) ) {
						$explanation = __( 'General purpose encryption, e.g. application password stored within settings', 'woocommerce-germanized' );
					} else {
						$explanation = sprintf( __( 'Encryption of type %s', 'woocommerce-germanized' ), $encryption_type );
					}
				}

				$notice  = '<p>' . sprintf( __( 'Attention! The <em>%1$s</em> (%2$s) constant is missing. Germanized uses a derived key based on the <em>LOGGED_IN_KEY</em> constant instead. This constant might change under certain circumstances. To prevent data losses, please insert the following snippet within your <a href="%3$s" target="_blank">wp-config.php</a> file:', 'woocommerce-germanized' ), $constant, $explanation, 'https://wordpress.org/support/article/editing-wp-config-php/' ) . '</p>';
				$notice .= '<p style="overflow: scroll">' . "<code>define( '" . $constant . "', '" . $new_key . "' );</code></p>";
			}

			return $notice;
		}

		/**
		 * @return string|WP_Error
		 */
		public static function get_random_encryption_key() {
			try {
				return sodium_bin2hex( sodium_crypto_secretbox_keygen() );
			} catch ( \Exception $e ) {
				return self::log_error( new WP_Error( 'encrypt-key-error', sprintf( 'Error while creating new encryption key: %s', wc_print_r( $e, true ) ) ) );
			}
		}

		public static function get_encryption_key_constant( $encryption_type = '' ) {
			return apply_filters( 'woocommerce_gzd_encryption_key_constant', 'WC_GZD_ENCRYPTION_KEY', $encryption_type );
		}

		/**
		 * @param string $salt
		 * @param string $encryption_type
		 *
		 * @return array|WP_Error
		 */
		public static function get_encryption_key_data( $salt = '', $encryption_type = '', $force_fallback = false ) {
			$result = array(
				'key'  => '',
				'salt' => ! empty( $salt ) ? $salt : random_bytes( SODIUM_CRYPTO_PWHASH_SALTBYTES ),
			);

			if ( self::has_valid_encryption_key( $encryption_type ) && ! $force_fallback ) {
				$result['key'] = sodium_hex2bin( constant( self::get_encryption_key_constant( $encryption_type ) ) );
			} else {
				try {
					$pw            = LOGGED_IN_KEY;
					$result['key'] = sodium_crypto_pwhash(
						SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
						$pw,
						$result['salt'],
						SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
						SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
					);

					self::memzero( $pw );
				} catch ( \Exception $e ) {
					return self::log_error( new WP_Error( 'encrypt-key-error', sprintf( 'Error while retrieving encryption key: %s', wc_print_r( $e, true ) ) ) );
				}
			}

			return $result;
		}

		/**
		 * The sodium_compat does not support zeroing memory and throws an exception:
		 * https://github.com/paragonie/sodium_compat/blob/master/src/Compat.php#L3301
		 *
		 * @param $pw
		 */
		protected static function memzero( $pw ) {
			try {
				sodium_memzero( $pw );
			} catch ( \SodiumException $e ) {
				return;
			}
		}

		public static function has_valid_encryption_key( $encryption_type = '' ) {
			return defined( self::get_encryption_key_constant( $encryption_type ) );
		}

		/**
		 * @param $message
		 * @param string $encryption_type
		 *
		 * @return string|WP_Error
		 */
		public static function encrypt( $message, $encryption_type = '' ) {
			try {
				$key_data = self::get_encryption_key_data( $encryption_type );
				$nonce    = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

				if ( is_wp_error( $key_data ) ) {
					return $key_data;
				}

				return base64_encode( $key_data['salt'] . $nonce . sodium_crypto_secretbox( $message, $nonce, $key_data['key'] ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			} catch ( \Exception $e ) {
				return self::log_error( new WP_Error( 'encrypt-error', sprintf( 'Error while encrypting data: %s', wc_print_r( $e, true ) ) ) );
			}
		}

		/**
		 * Decrypts a message of a certain type.
		 *
		 * @param $cipher
		 * @param string $encryption_type
		 *
		 * @return WP_Error|mixed
		 */
		public static function decrypt( $cipher, $encryption_type = '' ) {
			if ( is_null( $cipher ) ) {
				return null;
			}

			$decoded = base64_decode( $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$error   = new \WP_Error();

			if ( false === $decoded ) {
				$error->add( 'decrypt-decode', 'Error while decoding the encrypted message.' );
				return self::log_error( $error );
			}

			try {
				if ( mb_strlen( $decoded, '8bit' ) < ( SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) ) {
					$error->add( 'decrypt-truncate', 'Message was truncated.' );
					return self::log_error( $error );
				}

				$salt     = mb_substr( $decoded, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit' );
				$key_data = self::get_encryption_key_data( $salt, $encryption_type );

				if ( is_wp_error( $key_data ) ) {
					return $key_data;
				}

				$key        = $key_data['key'];
				$nonce      = mb_substr( $decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
				$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );
				$plain      = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

				/**
				 * Try the fallback key.
				 */
				if ( false === $plain ) {
					$key_data = self::get_encryption_key_data( $salt, $encryption_type, true );

					if ( is_wp_error( $key_data ) ) {
						return $key_data;
					}

					$key   = $key_data['key'];
					$plain = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
				}

				if ( false === $plain ) {
					$error->add( 'decrypt', 'Message could not be decrypted.' );
					return self::log_error( $error );
				}

				self::memzero( $ciphertext );
				self::memzero( $key );

				return $plain;
			} catch ( \Exception $e ) {
				$error->add( 'decrypt-error', sprintf( 'Error while decrypting data: %s', wc_print_r( $e, true ) ) );
				return self::log_error( $error );
			}
		}

		/**
		 * Checks whether this installation supports auto-inserting the encryption key to the wp-config.php file.
		 *
		 * @return bool
		 */
		public static function supports_auto_insert() {
			$supports = false;
			/**
			 * Determine the path to wp-config.php to check whether auto-inserting the encryption key is possible or not.
			 * Plugin review team: This path is NOT used to include the wp-config.php file.
			 */
			$path_to_wp_config = ABSPATH . '/wp-config.php'; // phpcs:ignore

			if ( @file_exists( $path_to_wp_config ) && @is_writeable( $path_to_wp_config ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$supports = true;
			}

			return $supports;
		}

		/**
		 * Try to insert the encryption key (e.g. for securely storing API credentials) in the wp-config.php file.
		 *
		 * @param $encryption_type
		 *
		 * @return bool
		 */
		public static function maybe_insert_missing_key( $encryption_type = '' ) {
			$updated = false;

			if ( ! self::has_valid_encryption_key( $encryption_type ) ) {
				$constant  = self::get_encryption_key_constant( $encryption_type );
				$key_value = self::get_random_encryption_key();

				if ( is_wp_error( $key_value ) ) {
					return false;
				}

				/**
				 * Determine the path to wp-config.php to auto-insert the encryption key.
				 * Plugin review team: This path is NOT used to include the wp-config.php file.
				 */
				$path_to_wp_config = ABSPATH . '/wp-config.php'; // phpcs:ignore

				if ( @file_exists( $path_to_wp_config ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					error_reporting( 0 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting,WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting

					// Load file data
					$config_file      = file( $path_to_wp_config );
					$last_define_line = false;
					$stop_line        = false;
					$path_line        = false;
					$to_insert        = "define( '" . $constant . "', '" . addcslashes( $key_value, "\\'" ) . "' );\r\n";
					$exists           = false;

					if ( ! $config_file ) {
						return false;
					}

					foreach ( $config_file as $line_num => $line ) {
						if ( strstr( $line, 'stop editing! Happy publishing' ) ) {
							$stop_line = $line_num;
							continue;
						}

						if ( strstr( $line, $constant ) ) {
							$exists = true;
							break;
						}

						if ( strstr( $line, 'Absolute path to the WordPress directory' ) ) {
							$path_line = $line_num;
						}

						if ( ! preg_match( '/^define\(\s*\'([A-Z_]+)\',([ ]+)/', $line, $match ) ) {
							continue;
						}

						$last_define_line = $line_num;
					}

					if ( ! $exists ) {
						if ( $stop_line ) {
							array_splice( $config_file, $stop_line, 0, $to_insert );
						} elseif ( $path_line ) {
							array_splice( $config_file, $path_line, 0, $to_insert );
						} elseif ( $last_define_line ) {
							array_splice( $config_file, $last_define_line + 1, 0, $to_insert );
						}

						$handle = fopen( $path_to_wp_config, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

						if ( $handle ) {
							foreach ( $config_file as $line ) {
								fwrite( $handle, $line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
							}

							fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
							$updated = true;
						}
					}
				}
			}

			return $updated;
		}

		/**
		 * @param \WP_Error $error
		 */
		protected static function log_error( $error ) {
			update_option( 'woocommerce_gzd_has_encryption_error', 'yes' );

			if ( apply_filters( 'woocommerce_gzd_encryption_enable_logging', false ) && ( $logger = wc_get_logger() ) ) {
				foreach ( $error->get_error_messages() as $message ) {
					$logger->error( $message, array( 'source' => apply_filters( 'woocommerce_gzd_encryption_log_context', 'wc-gzd-encryption' ) ) );
				}
			}

			return $error;
		}

		public static function has_errors() {
			return 'yes' === get_option( 'woocommerce_gzd_has_encryption_error', 'no' );
		}
	}
}
