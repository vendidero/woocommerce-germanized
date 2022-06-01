<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Email_Customer_Revocation' ) ) :

	/**
	 * Revocation conformation Email
	 *
	 * Email is being sent if a customer fills out the revocation form (insert via [revocation_form] shortcut)
	 *
	 * @class        WC_GZD_Email_Customer_Revocation
	 * @version        1.0.0
	 * @author        Vendidero
	 */
	class WC_GZD_Email_Customer_Revocation extends WC_Email {

		public $user_email = '';

		public $helper;

		/**
		 * Constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->id          = 'customer_revocation';
			$this->title       = __( 'Withdrawal', 'woocommerce-germanized' );
			$this->description = __( 'Email being sent if a customer fills out the form of withdrawal.', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-revocation.php';
			$this->template_plain = 'emails/plain/customer-revocation.php';
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
			return __( 'Your revocation', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading() {
			return __( 'Your revocation', 'woocommerce-germanized' );
		}

		/**
		 * trigger function.
		 *
		 * @access public
		 * @return void
		 */
		public function trigger( $user_data = array() ) {
			$this->helper->setup_locale();

			$this->object     = $user_data;
			$this->user_email = $user_data['address_mail'];

			if ( ! empty( $user_data['send_to_admin'] ) && $user_data['send_to_admin'] ) {
				$this->customer_email = false;
				$this->user_email     = $this->get_admin_email();
			}

			$this->recipient = $this->user_email;

			$this->helper->setup_email_locale();

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->helper->restore_email_locale();
			$this->helper->restore_locale();
		}

		/**
		 * Get email heading.
		 *
		 * @access public
		 * @return string
		 */
		public function get_admin_email() {
			/**
			 * Filter the revocation admin email receiver address.
			 *
			 * @param string $email The email address.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'wc_gzd_revocation_admin_mail', $this->get_option( 'admin_email', get_bloginfo( 'admin_email' ) ) );
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
					'user'               => $this->object,
					'email_heading'      => $this->get_heading(),
					'blogname'           => $this->get_blogname(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
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
					'user'               => $this->object,
					'email_heading'      => $this->get_heading(),
					'blogname'           => $this->get_blogname(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
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
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {

			parent::init_form_fields();

			$this->form_fields = array_merge(
				$this->form_fields,
				array(
					'admin_email' => array(
						'title'       => __( 'Admin email', 'woocommerce-germanized' ),
						'type'        => 'text',
						'desc_tip'    => true,
						'description' => __( 'Insert the email address of your shop manager here. A copy of the withdrawal confirmation email is being sent to this address.', 'woocommerce-germanized' ),
						'placeholder' => '',
						'default'     => get_bloginfo( 'admin_email' ),
					),
				)
			);
		}
	}

endif;

return new WC_GZD_Email_Customer_Revocation();
