<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Email_Customer_New_Account_Activation' ) ) :

	/**
	 * Customer New Account
	 *
	 * An email sent to the customer when they create an account.
	 *
	 * @class        WC_Email_Customer_New_Account
	 * @version        2.3.0
	 * @package        WooCommerce/Classes/Emails
	 * @author        WooThemes
	 * @extends    WC_Email
	 */
	class WC_GZD_Email_Customer_New_Account_Activation extends WC_Email {

		public $user_login;
		public $user_email;
		public $user_activation;
		public $user_activation_url;
		public $user_pass;
		public $password_generated;
		public $set_password_url;

		public $helper = null;

		/**
		 * Constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->id          = 'customer_new_account_activation';
			$this->title       = __( 'New account activation', 'woocommerce-germanized' );
			$this->description = __( 'Customer "new account activation" emails are sent to the customer when a customer signs up via checkout or account pages. This mail is being used as double opt in for new customer accounts.', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-new-account-activation.php';
			$this->template_plain = 'emails/plain/customer-new-account-activation.php';
			$this->helper         = wc_gzd_get_email_helper( $this );

			// Call parent constuctor
			parent::__construct();

			$this->customer_email = true;
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_subject() {
			return __( 'Activate your account on {site_title}', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading() {
			return __( 'Account activation {site_title}', 'woocommerce-germanized' );
		}

		/**
		 * Return content from the additional_content field.
		 *
		 * Displayed above the footer.
		 *
		 * @since 3.0.4
		 * @return string
		 */
		public function get_additional_content() {
			if ( is_callable( 'parent::get_additional_content' ) ) {
				return parent::get_additional_content();
			}

			return '';
		}

		/**
		 * Generate set password URL link for a new user.
		 *
		 * See also Automattic\WooCommerce\Blocks\Domain\Services\Email\CustomerNewAccount and wp_new_user_notification.
		 *
		 * @since 6.0.0
		 * @return string
		 */
		protected function generate_set_password_url() {
			// Generate a magic link so user can set initial password.
			$key = get_password_reset_key( $this->object );

			if ( ! is_wp_error( $key ) ) {
				$action = 'newaccount';
				return wc_get_account_endpoint_url( 'lost-password' ) . "?action=$action&key=$key&login=" . rawurlencode( $this->object->user_login );
			} else {
				// Something went wrong while getting the key for new password URL, send customer to the generic password reset.
				return wc_get_account_endpoint_url( 'lost-password' );
			}
		}

		/**
		 * trigger function.
		 *
		 * @access public
		 * @return void
		 */
		public function trigger( $user_id, $user_activation, $user_activation_url, $user_pass = '', $password_generated = false ) {
			$this->helper->setup_locale();

			if ( $user_id ) {
				$this->object              = new WP_User( $user_id );
				$this->user_activation     = $user_activation;
				$this->user_activation_url = $user_activation_url;
				$this->user_login          = stripslashes( $this->object->user_login );
				$this->user_email          = stripslashes( $this->object->user_email );
				$this->recipient           = $this->user_email;
				$this->user_pass           = $user_pass;
				$this->password_generated  = $password_generated;
				$this->set_password_url    = '';

				/**
				 * Newer versions of Woo send (and are force-generating) a reset password link.
				 * Do not include a reset password link within the activation mail as this link would get
				 * invalidated after sending the new customer mail notification.
				 *
				 * @see WC_Email_Customer_New_Account::trigger()
				 */
				if ( WC_GZD_Customer_Helper::instance()->send_password_reset_link_instead_of_passwords() ) {
					$this->password_generated = false;
				} else {
					$this->set_password_url = $this->generate_set_password_url();
				}
			}

			$this->helper->setup_email_locale();

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			$this->helper->restore_email_locale();
			$this->helper->restore_locale();
		}

		/**
		 * get_content_html function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'email_heading'       => $this->get_heading(),
					'user_login'          => $this->user_login,
					'user_activation'     => $this->user_activation,
					'user_activation_url' => $this->user_activation_url,
					'user_pass'           => $this->user_pass,
					'password_generated'  => $this->password_generated,
					'set_password_url'    => $this->set_password_url,
					'blogname'            => $this->get_blogname(),
					'additional_content'  => $this->get_additional_content(),
					'sent_to_admin'       => false,
					'plain_text'          => false,
					'email'               => $this,
				)
			);
		}

		/**
		 * get_content_plain function.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'email_heading'       => $this->get_heading(),
					'user_login'          => $this->user_login,
					'user_activation'     => $this->user_activation,
					'user_activation_url' => $this->user_activation_url,
					'user_pass'           => $this->user_pass,
					'password_generated'  => $this->password_generated,
					'set_password_url'    => $this->set_password_url,
					'blogname'            => $this->get_blogname(),
					'additional_content'  => $this->get_additional_content(),
					'sent_to_admin'       => false,
					'plain_text'          => true,
					'email'               => $this,
				)
			);
		}
	}

endif;

return new WC_GZD_Email_Customer_New_Account_Activation();
