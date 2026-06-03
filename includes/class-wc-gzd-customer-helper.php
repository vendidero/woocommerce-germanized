<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Customer_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		// Send customer account notification
		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 50, 1 );

		// Add Title to user profile
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'profile_field_title' ), 10, 1 );
		add_filter( 'woocommerce_ajax_get_customer_details', array( $this, 'load_customer_fields' ), 10, 3 );

		// Add Title to formatted my account address
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'set_user_address' ), 10, 3 );

		if ( $this->is_double_opt_in_enabled() ) {
			// Check for customer activation
			add_action( 'template_redirect', array( $this, 'customer_account_activation_check' ) );

			// Maybe allow resending the activation notification
			add_action( 'template_redirect', array( $this, 'resend_activation_check' ) );

			// Cronjob to delete unactivated users
			add_action( 'woocommerce_gzd_customer_cleanup', array( $this, 'account_cleanup' ) );

			// Add user notice in case the account has not yet been activated
			add_action( 'template_redirect', array( $this, 'maybe_add_activation_notice' ), 30 );

			// Set doi meta while creating Woo customer
			add_action( 'woocommerce_created_customer', array( $this, 'set_doi' ), 1, 1 );

			// Remove session data
			add_action( 'wp_login', array( $this, 'delete_doi_session' ) );
			add_action( 'wp_logout', array( $this, 'delete_doi_session' ) );

			/**
			 * Prevent users from logging in before the account has been activated.
			 * Uses some tweaks to prevent auto-logins during (classic, block) checkout.
			 */
			if ( $this->is_double_opt_in_login_enabled() ) {
				add_filter( 'wp_authenticate_user', array( $this, 'login_restriction' ), 10, 2 );
				add_filter( 'send_auth_cookies', array( $this, 'maybe_disable_auth_cookies' ), 10, 6 );
				add_action( 'set_current_user', array( $this, 'maybe_block_login_after_account_creation' ), 10 );

				add_filter( 'woocommerce_checkout_customer_id', array( $this, 'checkout_customer_id' ), 10 );
				add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'maybe_logout_during_checkout' ), 9999 );
				add_action( 'woocommerce_checkout_create_order', array( $this, 'on_create_order' ), 9999 );

				add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'on_update_api_order' ), 1 );
				add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'on_update_api_order' ), 1 );
				add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'on_create_order' ), 9999 );
				add_filter( 'render_block', array( $this, 'replace_order_confirmation_account_success' ), 1000, 2 );

				// Disable auto login after registration
				add_filter( 'woocommerce_registration_auth_new_customer', array( $this, 'disable_registration_auto_login' ), 9999, 2 );

				// WC Social Login comp
				add_filter( 'wc_social_login_set_auth_cookie', array( $this, 'social_login_activation_check' ), 10, 2 );
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function on_create_order( $order ) {
		if ( ! empty( $order->get_customer_id() ) ) {
			$user_set = $this->maybe_set_current_doi_user( $order->get_customer_id(), false );

			if ( $user_set && is_user_logged_in() ) {
				wp_set_current_user( 0 );
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function on_update_api_order( $order ) {
		if ( ! is_user_logged_in() && empty( $order->get_customer_id() ) ) {
			$user_set = $this->maybe_set_current_doi_user();

			if ( $user_set ) {
				$order->set_customer_id( $user_set );
			}
		}
	}

	public function maybe_block_login_after_account_creation() {
		if ( get_current_user_id() > 0 && WC()->session && true === WC()->session->get( 'doi_disable_login_after_account_creation' ) ) {
			$user_id = get_current_user_id();

			if ( $this->enable_double_opt_in_for_user( $user_id ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
				wp_set_current_user( 0 );
			}
		}
	}

	public function maybe_logout_during_checkout( $user_id ) {
		$user_set = $this->maybe_set_current_doi_user( $user_id, false );

		if ( $user_set && is_user_logged_in() ) {
			wp_set_current_user( 0 );
		}
	}

	/**
	 * @return int|null
	 */
	protected function maybe_set_current_doi_user( $customer_id = null, $login = true ) {
		$customer_id = is_null( $customer_id ) ? ( ( WC()->session && WC()->session->get( 'doi_user_id' ) ) ? absint( WC()->session->get( 'doi_user_id' ) ) : 0 ) : absint( $customer_id );
		$user_set    = null;

		if ( ! empty( $customer_id ) && $this->enable_double_opt_in_for_user( $customer_id ) && ! wc_gzd_is_customer_activated( $customer_id ) ) {
			$user_set = $customer_id;

			WC()->session->set( 'doi_user_id', $user_set );

			if ( $login ) {
				wp_set_current_user( $user_set );

				/**
				 * Reset user fallback during errors
				 */
				add_action(
					'shutdown',
					function () {
						wp_set_current_user( 0 );
					}
				);
			}
		} else {
			WC()->session->set( 'doi_user_id', null );
		}

		return $user_set;
	}

	public function checkout_customer_id( $user_id ) {
		if ( empty( $user_id ) ) {
			$user_set = $this->maybe_set_current_doi_user();

			if ( $user_set ) {
				$user_id = $user_set;
			}
		}

		return $user_id;
	}

	/**
	 * Use a tweak to prevent the session cookie from actually being set when Woo logs in the user
	 * after account creation automatically, e.g. via wc_set_customer_auth_cookie.
	 *
	 * @see \Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\CreateAccount::process_form_post()
	 */
	public function maybe_disable_auth_cookies( $send_auth, $expire, $expiration, $user_id, $scheme, $token ) {
		if ( $this->enable_double_opt_in_for_user( $user_id ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
			if ( WC()->session && true === WC()->session->get( 'doi_disable_login_after_account_creation' ) ) {
				$send_auth = false;

				WC()->session->set( 'doi_disable_login_after_account_creation', null );
				WC()->session->set( 'doi_user_id', $user_id );
			}
		}

		return $send_auth;
	}

	/**
	 *
	 *
	 * @param string $content
	 * @param $block
	 *
	 * @return string
	 */
	public function replace_order_confirmation_account_success( $content, $block ) {
		if ( 'woocommerce/order-confirmation-create-account' === $block['blockName'] ) {
			if ( $dom = wc_gzd_get_dom_document( $content ) ) {
				$finder       = new DomXPath( $dom );
				$new_selector = 'wc-block-order-confirmation-create-account-success';
				$nodes        = $finder->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' $new_selector ')]" );
				$nodes        = is_array( $nodes ) || is_a( $nodes, 'DOMNodeList' ) ? $nodes : array( $nodes );

				if ( count( $nodes ) > 0 ) {
					$success_node = $nodes[0];
					$fragment     = $dom->createDocumentFragment();
					$fragment->appendXML( '<h3>' . esc_html__( 'Your account has been created and is waiting for your activation', 'woocommerce-germanized' ) . '</h3><p>' . wp_kses_post( sprintf( __( 'Please check your e-mails and activate your account. Did not receive an email? <a href="%s">Try again</a>.', 'woocommerce-germanized' ), esc_url( $this->get_resend_activation_url() ) ) ) . '</p>' );

					while ( $success_node->hasChildNodes() ) {
						$success_node->removeChild( $success_node->firstChild ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}

					$success_node->appendChild( $fragment );
					$new_content = wc_gzd_get_dom_document_html( $dom );

					if ( false !== $new_content ) {
						$content = $new_content;
					}
				}
			}
		}

		return $content;
	}

	public function set_doi( $user_id ) {
		if ( $this->enable_double_opt_in_for_user( $user_id ) ) {
			$this->set_doi_session( $user_id );
			$this->get_customer_activation_meta( $user_id, true );
		}
	}

	public function set_doi_session( $user_id ) {
		if ( ! is_null( WC()->session ) && $this->enable_double_opt_in_for_user( $user_id ) ) {
			/**
			 * Force initializing Woo session
			 */
			do_action( 'woocommerce_set_cart_cookies', true );

			if ( $this->is_double_opt_in_login_enabled() ) {
				WC()->session->set( 'doi_disable_login_after_account_creation', true );
			}

			WC()->session->set( 'doi_user_id', $user_id );
		}
	}

	public function delete_doi_session() {
		if ( function_exists( 'WC' ) && ! is_null( WC()->session ) && WC()->session->get( 'doi_user_id' ) ) {
			WC()->session->set( 'doi_user_id', null );
		}
	}

	public function resend_activation_check() {
		if ( is_account_page() ) {
			if (
				isset( $_GET['action'], $_GET['_wpnonce'] ) &&
				'wc-gzd-resend-activation' === $_GET['action'] &&
				wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'wc-gzd-resend-activation' ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			) {
				$user_id = ( ! is_null( WC()->session ) && WC()->session->get( 'doi_user_id' ) ) ? absint( WC()->session->get( 'doi_user_id' ) ) : false;

				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
				}

				if ( ! $user_id || ( ! $user = get_user_by( 'ID', $user_id ) ) || wc_gzd_is_customer_activated( $user_id ) || ! $this->enable_double_opt_in_for_user( $user_id ) ) {
					return;
				}

				$time_sent = get_user_meta( $user_id, '_gzd_activation_email_sent', true );
				$time_sent = empty( $time_sent ) ? 0 : absint( $time_sent );

				if ( ! $time_sent || $time_sent <= 5 ) {
					/**
					 * Do only allow the user to (re)send the activation mail for 5 times.
					 */
					update_user_meta( $user_id, '_gzd_activation_email_sent', ( $time_sent + 1 ) );

					$this->resend_customer_activation_email( $user_id, is_user_logged_in() ? false : true );

					$url = add_query_arg( array( 'wc-gzd-resent' => 'yes' ) );
					$url = remove_query_arg( array( 'action', '_wpnonce' ), $url );

					/**
					 * Filters the URL after resending the DOI activation email.
					 *
					 * @param string $url The URL to redirect to.
					 * @param integer $user_id The user id.
					 *
					 * @since 3.3.2
					 */
					wp_safe_redirect( esc_url_raw( apply_filters( 'woocommerce_gzd_double_opt_resent_activation_redirect', $url, $user_id ) ) );
					exit();
				}
			} elseif ( isset( $_GET['wc-gzd-resent'] ) ) {
				wc_add_notice( __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' ), 'notice' );
			}
		}
	}

	public function maybe_add_activation_notice() {
		$session_user_id = ! is_null( WC()->session ) ? WC()->session->get( 'doi_user_id' ) : false;

		if ( is_user_logged_in() ) {
			$session_user_id = get_current_user_id();
		}

		if ( $session_user_id && $session_user_id > 0 && ! is_cart() && ! is_checkout() && $this->enable_double_opt_in_for_user( $session_user_id ) && ! wc_gzd_is_customer_activated( $session_user_id ) && ( ! isset( $_GET['wc-gzd-resent'] ) && ! isset( $_GET['activated'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$notice_text = sprintf( __( 'Did not receive the activation email? <a href="%s">Try again</a>.', 'woocommerce-germanized' ), esc_url( $this->get_resend_activation_url() ) );

			if ( ! wc_has_notice( $notice_text, 'notice' ) ) {
				wc_add_notice( $notice_text, 'notice' );
			}
		}
	}

	protected function get_resend_activation_url() {
		$url = add_query_arg( array( 'action' => 'wc-gzd-resend-activation' ), wc_gzd_get_page_permalink( 'myaccount' ) );
		$url = wp_nonce_url( $url, 'wc-gzd-resend-activation' );

		return esc_url_raw( $url );
	}

	public function is_customer_title_enabled() {
		return 'yes' === get_option( 'woocommerce_gzd_checkout_address_field' );
	}

	public function set_user_address( $address, $customer_id, $type ) {
		if ( ! $this->is_customer_title_enabled() ) {
			return $address;
		}

		$address['title'] = wc_gzd_get_customer_title( get_user_meta( $customer_id, $type . '_title', true ) );

		return $address;
	}

	public function load_customer_fields( $data, $customer, $user_id ) {
		$fields = WC_GZD_Checkout::instance()->custom_fields_admin;

		if ( is_array( $fields ) ) {
			foreach ( $fields as $key => $field ) {

				$types = array( 'shipping', 'billing' );

				if ( isset( $field['address_type'] ) ) {
					$types = array( $field['address_type'] );
				}

				foreach ( $types as $type ) {
					if ( ! isset( $data[ $type ] ) ) {
						continue;
					}

					$data[ $type ][ $key ] = get_user_meta( $user_id, $type . '_' . $key, true );
				}
			}
		}

		return $data;
	}

	public function social_login_activation_check( $message, $user ) {
		$user_id = $user->ID;

		if ( $this->enable_double_opt_in_for_user( $user ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
			return __( 'Please activate your account through clicking on the activation link received via email.', 'woocommerce-germanized' );
		}

		return $message;
	}

	public function profile_field_title( $fields ) {
		if ( ! $this->is_customer_title_enabled() ) {
			return $fields;
		}

		$fields['billing']['fields']['billing_title'] = array(
			'label'       => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'select',
			'options'     => wc_gzd_get_customer_title_options(),
			'description' => '',
			'class'       => '',
		);

		$fields['shipping']['fields']['shipping_title'] = array(
			'label'       => __( 'Title', 'woocommerce-germanized' ),
			'type'        => 'select',
			'options'     => wc_gzd_get_customer_title_options(),
			'description' => '',
			'class'       => '',
		);

		return $fields;
	}

	public function is_double_opt_in_enabled() {
		return get_option( 'woocommerce_gzd_customer_activation' ) === 'yes';
	}

	public function is_double_opt_in_login_enabled() {
		return get_option( 'woocommerce_gzd_customer_activation_login_disabled' ) === 'yes';
	}

	public function delete_checkout_signup_cookie() {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::delete_checkout_signup_cookie', '4.1.0' );
	}

	public function maybe_disable_signups( $enable_signups ) {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::maybe_disable_signups', '4.1.0' );

		return $enable_signups;
	}

	public function disable_signup_template() {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::disable_signup_template', '4.1.0' );
	}

	public function login_redirect( $redirect, $user ) {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::login_redirect', '4.1.0' );

		return $redirect;
	}

	public function disable_checkout() {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::disable_checkout', '4.1.0' );
	}

	public function show_disabled_checkout_notice() {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::show_disabled_checkout_notice', '4.1.0' );
	}

	protected function registration_redirect( $query_args = array() ) {
		wc_deprecated_function( 'WC_GZD_Customer_Helper::registration_redirect', '4.1.0' );

		$query_args = wp_parse_args(
			$query_args,
			array(
				'show_checkout_notice' => 'yes',
			)
		);

		/**
		 * Filter URL which serves as redirection if a customer has not yet activated it's account and
		 * wants to access checkout.
		 *
		 * @param string $url The redirection URL.
		 * @param array[string] $query_args Arguments passed to the URL.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'woocommerce_gzd_customer_registration_redirect', esc_url_raw( add_query_arg( $query_args, wc_gzd_get_page_permalink( 'myaccount' ) ) ), $query_args );
	}

	public function disable_registration_auto_login( $result, $user_id ) {
		if ( is_a( $user_id, 'WP_User' ) ) {
			$user_id = $user_id->ID;
		}

		// Has not been activated yet
		if ( $this->enable_double_opt_in_for_user( $user_id ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
			$result = false;
		}

		return $result;
	}

	public function get_double_opt_in_user_roles() {
		/**
		 * Filters supported DOI user roles. By default only the WooCommerce customer role is supported.
		 *
		 * ```php
		 * function ex_add_doi_roles( $roles ) {
		 *      $roles[] = 'my_custom_role';
		 *
		 *      return $roles;
		 * }
		 * add_filter( 'woocommerce_gzd_customer_double_opt_in_supported_user_roles', 'ex_add_doi_roles', 10, 1 );
		 * ```
		 *
		 * @param array $roles Array of roles to be supported.
		 *
		 * @since 1.0.0
		 *
		 */
		$roles = apply_filters( 'woocommerce_gzd_customer_double_opt_in_supported_user_roles', array( 'customer' ) );

		return array_unique( $roles );
	}

	public function enable_double_opt_in_for_user( $user = false ) {
		if ( ! $user ) {
			$user = get_current_user_id();
		}

		if ( is_numeric( $user ) ) {
			$user = get_user_by( 'id', absint( $user ) );
		}

		if ( ! $user ) {
			return false;
		}

		$supported_roles        = $this->get_double_opt_in_user_roles();
		$supports_double_opt_in = false;
		$user_roles             = ( isset( $user->roles ) ? (array) $user->roles : array() );

		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $supported_roles, true ) ) {
				$supports_double_opt_in = true;
				break;
			}
		}

		/**
		 * Filter whether the DOI is supported for a certain user.
		 *
		 * @param bool $supports_double_opt_in Whether the user is supported or not.
		 * @param WP_User $user The user instance.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_customer_supports_double_opt_in', $supports_double_opt_in, $user );
	}

	public function login_restriction( $user, $password ) {
		// Has not been activated yet
		if ( $this->enable_double_opt_in_for_user( $user ) && ! wc_gzd_is_customer_activated( $user->ID ) ) {
			$this->set_doi_session( $user->ID );

			return new WP_Error( 'woocommerce_gzd_login', sprintf( __( 'Please activate your account through clicking on the activation link received via email. Did not receive the email? <a href="%s">Try again</a>.', 'woocommerce-germanized' ), esc_url( $this->get_resend_activation_url() ) ) );
		}

		return $user;
	}

	public function get_customer_id_from_activation_code( $activation_code ) {
		$parts = explode( ':', $activation_code, 3 );

		if ( count( $parts ) > 2 && ! empty( $parts[2] ) ) {
			$customer_id = absint( base64_decode( $parts[2] ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( ! empty( $customer_id ) ) {
				return $customer_id;
			}
		}

		return null;
	}

	/**
	 * Check for activation codes on my account page
	 */
	public function customer_account_activation_check() {
		if ( is_account_page() ) {
			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$activation_code = wc_clean( urldecode( wp_unslash( $_GET['activate'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( ! empty( $activation_code ) ) {
					$result      = $this->customer_account_activate( $activation_code, true );
					$customer_id = $this->get_customer_id_from_activation_code( $activation_code );

					if ( true === $result || ( ! empty( $customer_id ) && wc_gzd_is_customer_activated( $customer_id ) ) ) {
						$url = add_query_arg( true === $result ? array( 'activated' => 'yes' ) : array() );
						$url = remove_query_arg( 'activate', $url );
						$url = remove_query_arg( 'suffix', $url );

						/**
						 * Filters the URL after a successful DOI.
						 *
						 * @param string $url The URL to redirect to.
						 *
						 * @since 1.0.0
						 */
						wp_safe_redirect( esc_url_raw( apply_filters( 'woocommerce_gzd_double_opt_in_successful_redirect', $url ) ) );
						exit();
					} elseif ( is_wp_error( $result ) && 'expired_key' === $result->get_error_code() ) {
						wc_add_notice( __( 'This activation code has expired. We have sent you a new activation code via e-mail.', 'woocommerce-germanized' ), 'error' );
					} else {
						wc_add_notice( __( 'Sorry, but this activation code cannot be found.', 'woocommerce-germanized' ), 'error' );
					}
				}
			} elseif ( isset( $_GET['activated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wc_clear_notices();
				wc_add_notice( __( 'Thank you. You have successfully activated your account.', 'woocommerce-germanized' ), 'notice' );
			}
		}
	}

	/**
	 * Check for customer that didn't activate their accounts within a couple of time and delete them
	 */
	public function account_cleanup() {
		$cleanup_interval = get_option( 'woocommerce_gzd_customer_cleanup_interval' );

		if ( ! $this->is_double_opt_in_enabled() || ! $cleanup_interval || empty( $cleanup_interval ) ) {
			return;
		}

		$roles             = array_map( 'ucfirst', $this->get_double_opt_in_user_roles() );
		$cleanup_days      = (int) $cleanup_interval;
		$registered_before = gmdate( 'Y-m-d H:i:s', strtotime( "-{$cleanup_days} days" ) );

		$user_query = new WP_User_Query(
			array(
				'role'         => $roles,
				'role__not_in' => $this->get_account_cleanup_user_role_exclusions(),
				'date_query'   => array(
					array(
						'before'    => $registered_before,
						'inclusive' => true,
					),
				),
				'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_woocommerce_activation',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_woocommerce_activation',
						'compare' => '!=',
						'value'   => '',
					),
				),
			)
		);

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$activation_meta = get_user_meta( $user->ID, '_woocommerce_activation', true );

				if ( ! empty( $activation_meta ) ) {
					/**
					 * Filters whether a certain user which has not yet been activated
					 * should be deleted.
					 *
					 * @param bool $delete Whether to delete the unactivated customer or not.
					 * @param WP_User $user The user instance.
					 *
					 * @since 1.0.0
					 */
					if ( apply_filters( 'woocommerce_gzd_delete_unactivated_customer', true, $user ) ) {
						require_once ABSPATH . 'wp-admin/includes/user.php';
						wp_delete_user( $user->ID );
					}
				}
			}
		}
	}

	public function send_password_reset_link_instead_of_passwords() {
		return ( version_compare( WC()->version, '6.0.0', '>=' ) );
	}

	/**
	 * Activate customer account based on activation code
	 *
	 * @param string $activation_code hashed activation code
	 *
	 * @return boolean|WP_Error
	 */
	public function customer_account_activate( $activation_code, $login = false ) {
		$activation_code = urldecode( $activation_code );
		$roles           = array_map( 'ucfirst', $this->get_double_opt_in_user_roles() );

		/**
		 * Filter to adjust arguments for the customer activation user query.
		 *
		 * @param array $args Arguments being passed to `WP_User_Query`.
		 * @param string $activation_code The activation code.
		 * @param bool $login Whether the customer should be authenticated after activation or not.
		 *
		 * @since 1.0.0
		 *
		 */
		$user_query = new WP_User_Query(
			apply_filters(
				'woocommerce_gzd_customer_account_activation_query',
				array(
					'role__in'   => $roles,
					'number'     => 1,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => '_woocommerce_activation',
							'value'   => $activation_code,
							'compare' => '=',
						),
					),
				),
				$activation_code,
				$login
			)
		);

		/**
		 * Filters the expiration time of customer activation keys.
		 *
		 * @param int $expiration The expiration time in seconds.
		 *
		 * @since 1.0.0
		 *
		 */
		$expiration_duration = apply_filters( 'woocommerce_germanized_account_activation_expiration', DAY_IN_SECONDS );

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$expiration_time = false;

				if ( false !== strpos( $activation_code, ':' ) ) {
					list( $activation_request_time, $activation_key ) = explode( ':', $activation_code, 2 );
					$expiration_time                                  = $activation_request_time + $expiration_duration;
				}

				if ( $expiration_time && time() < $expiration_time ) {
					/**
					 * Customer has opted-in.
					 *
					 * Fires whenever a customer has opted-in (DOI).
					 * Triggers before the confirmation e-mail has been sent and the user meta has been deleted.
					 *
					 * @param WP_User $user The user instance.
					 *
					 * @since 2.0.3
					 *
					 */
					do_action( 'woocommerce_gzd_customer_opted_in', $user );
					delete_user_meta( $user->ID, '_woocommerce_activation' );

					/**
					 * Make sure email hooks are loaded before removing the disabled mail filter.
					 */
					$mailer = WC()->mailer();

					remove_filter( 'woocommerce_email_enabled_customer_new_account', array( $this, 'disable_new_account_mail_callback' ), 50 );

					if ( $this->send_password_reset_link_instead_of_passwords() ) {
						$mailer->customer_new_account( $user->ID, array(), true );
					} else {
						$mailer->customer_new_account( $user->ID );
					}

					add_filter( 'woocommerce_email_enabled_customer_new_account', array( $this, 'disable_new_account_mail_callback' ), 50 );

					/**
					 * Filter to optionally disable automatically authenticate activated customers.
					 *
					 * @param bool $login Whether to authenticate the customer or not.
					 * @param WP_User $user The user instance.
					 *
					 * @since 1.0.0
					 *
					 */
					if ( apply_filters( 'woocommerce_gzd_user_activation_auto_login', $login, $user ) && ! is_user_logged_in() ) {
						wc_set_customer_auth_cookie( $user->ID );
					}

					/**
					 * Customer opt-in finished.
					 *
					 * Fires after a customer has been marked as opted-in and received the e-mail confirmation.
					 * Customer may already be authenticated at this point.
					 *
					 * @param WP_User $user The user instance.
					 *
					 * @since 2.0.3
					 *
					 */
					do_action( 'woocommerce_gzd_customer_opt_in_finished', $user );

					return true;
				} else {

					/**
					 * Customer activation code expired.
					 *
					 * Hook fires whenever a customer tries to activate his account but the activation key
					 * has already expired.
					 *
					 * @param WP_User $user The user instance.
					 *
					 * @since 2.0.3
					 *
					 */
					do_action( 'woocommerce_gzd_customer_activation_expired', $user );

					$this->resend_customer_activation_email( $user->ID );

					return new WP_Error( 'expired_key', __( 'Expired activation key', 'woocommerce-germanized' ) );
				}
			}
		}

		return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'woocommerce-germanized' ) );
	}

	protected function resend_customer_activation_email( $user_id, $maybe_generate_new_password = false ) {
		if ( wc_gzd_is_customer_activated( $user_id ) || ! $this->enable_double_opt_in_for_user( $user_id ) ) {
			return false;
		}

		$password           = '';
		$password_generated = false;

		/**
		 * Maybe generate a new password for the user (which has not logged in yet).
		 */
		if ( $maybe_generate_new_password && $this->is_double_opt_in_login_enabled() && 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$password           = wp_generate_password();
			$password_generated = true;
		}

		delete_user_meta( $user_id, '_woocommerce_activation' );

		$activation_code     = $this->get_customer_activation_meta( $user_id, true );
		$user_activation_url = $this->get_customer_activation_url( $activation_code );

		if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) ) {
			$email->trigger( $user_id, $activation_code, $user_activation_url, $password, $password_generated );

			return true;
		}

		return false;
	}

	public function email_hooks( $mailer ) {
		// Add new customer activation
		if ( $this->is_double_opt_in_enabled() ) {
			add_action( 'woocommerce_created_customer_notification', array( $this, 'customer_new_account_activation' ), 9, 3 );

			/**
			 * The Woo Blocks New Account implementation uses a custom logic
			 * to send a custom activation email which prevents our remove_action logic to work.
			 * Add a filter to woocommerce_created_customer_notification and disable the whole email via filter instead.
			 *
			 * @see Automattic\WooCommerce\Blocks\Domain\Services\CreateAccount
			 */
			add_action(
				'woocommerce_created_customer_notification',
				function () {
					add_filter( 'woocommerce_email_enabled_customer_new_account', array( $this, 'disable_new_account_mail_callback' ), 50 );
				},
				1
			);

			remove_action( 'woocommerce_created_customer_notification', array( $mailer, 'customer_new_account' ), 10 );
		}
	}

	public function disable_new_account_mail_callback() {
		return false;
	}

	/**
	 * Customer new account activation email.
	 *
	 * @param int $customer_id
	 * @param array $new_customer_data
	 */
	public function customer_new_account_activation( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		if ( ! $customer_id ) {
			return;
		}

		if ( ! $this->enable_double_opt_in_for_user( $customer_id ) ) {
			return;
		}

		if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) ) {
			if ( $email->is_enabled() ) {
				$user_pass           = ! empty( $new_customer_data['user_pass'] ) ? $new_customer_data['user_pass'] : '';
				$user_activation     = $this->get_customer_activation_meta( $customer_id );
				$user_activation_url = $this->get_customer_activation_url( $user_activation );

				$email->trigger( $customer_id, $user_activation, $user_activation_url, $user_pass, $password_generated );
			}
		}
	}

	public function get_customer_activation_url( $key ) {
		/**
		 * Filter the customer activation URL.
		 * Added a custom suffix to prevent email clients from stripping points as last chars.
		 *
		 * @param string $url The activation URL.
		 *
		 * @since 1.0.0
		 */
		return apply_filters(
			'woocommerce_gzd_customer_activation_url',
			esc_url_raw(
				add_query_arg(
					array(
						'activate' => rawurlencode( $key ),
						'suffix'   => 'yes',
					),
					wc_gzd_get_page_permalink( 'myaccount' )
				)
			)
		);
	}

	public function get_customer_activation_meta( $customer_id, $force_new = false ) {
		global $wp_hasher;

		if ( ! $customer_id ) {
			return '';
		}

		if ( ! $this->enable_double_opt_in_for_user( $customer_id ) ) {
			return '';
		}

		// If meta does already exist - return activation code
		if ( ! $force_new && ( $activation = get_user_meta( $customer_id, '_woocommerce_activation', true ) ) ) {
			return $activation;
		}

		// Generate something random for a password reset key.
		$key = wp_generate_password( 20, false );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$user_activation = time() . ':' . $wp_hasher->HashPassword( $key ) . ':' . base64_encode( $customer_id ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		update_user_meta( $customer_id, '_woocommerce_activation', $user_activation );

		return $user_activation;
	}

	public function get_account_cleanup_user_role_exclusions() {
		/**
		 * Filter user roles excluded during account cleanup.
		 * By default user roles `administrator`, `editor`, `author` and `shop_manager` are excluded.
		 *
		 * @param array $roles Array of roles to be excluded from account cleanup.
		 *
		 * @since 3.3.0
		 */
		return apply_filters( 'woocommerce_gzd_customer_account_cleanup_excluded_user_roles', array( 'administrator', 'editor', 'author', 'shop_manager' ) );
	}
}

WC_GZD_Customer_Helper::instance();
