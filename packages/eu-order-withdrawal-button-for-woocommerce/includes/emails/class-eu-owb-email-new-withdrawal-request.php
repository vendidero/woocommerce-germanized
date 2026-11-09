<?php
/**
 * Class EU_OWB_Email_New_Withdrawal_Request file.
 *
 * @package Vendidero/OrderWithdrawalButton/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EU_OWB_Email_New_Withdrawal_Request', false ) ) :

	/**
	 * Admin withdrawal request notification.
	 *
	 * @class    EU_OWB_Email_New_Withdrawal_Request
	 * @version  1.0.0
	 * @extends  WC_Email
	 */
	class EU_OWB_Email_New_Withdrawal_Request extends WC_Email {

		use \Vendidero\OrderWithdrawalButton\EmailTranslationHelper;

		public $withdrawal_email = '';

		/**
		 * @var \Vendidero\OrderWithdrawalButton\WithdrawalOrder
		 */
		public $withdrawal;

		/**
		 * Is this an update to a withdrawal request?
		 *
		 * @var bool
		 */
		public $is_update = false;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'new_withdrawal_request';
			$this->title       = _x( 'New withdrawal request', 'owb', 'woocommerce-germanized' );
			$this->description = _x( 'New withdrawal request emails are sent to chosen recipient(s) when a customer submits a withdrawal request.', 'owb', 'woocommerce-germanized' );

			$this->template_html  = 'emails/admin-new-withdrawal-request.php';
			$this->template_plain = 'emails/plain/admin-new-withdrawal-request.php';
			$this->template_base  = \Vendidero\OrderWithdrawalButton\Package::get_path() . '/templates/';

			$this->placeholders = array(
				'{site_title}'              => $this->get_blogname(),
				'{order_number}'            => '',
				'{contract_identification}' => '',
				'{order_date}'              => '',
				'{withdrawal_date}'         => '',
				'{withdrawal_email}'        => '',
			);

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $update Whether it is an update to an existing withdrawal request.
		 * @return string
		 */
		public function get_default_subject( $update = false ) {
			if ( $update ) {
				return _x( '[{site_title}]: Updated withdrawal request to {order_number}', 'owb', 'woocommerce-germanized' );
			} else {
				return _x( '[{site_title}]: New withdrawal request to {order_number}', 'owb', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $update Whether it is an update to an existing withdrawal request.
		 * @return string
		 */
		public function get_default_heading( $update = false ) {
			if ( $update ) {
				return _x( 'Updated withdrawal request: {order_number}', 'owb', 'woocommerce-germanized' );
			} else {
				return _x( 'New withdrawal request: {order_number}', 'owb', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->is_update ) {
				$subject = $this->get_option( 'subject_update', $this->get_default_subject( true ) );
			} else {
				$subject = $this->get_option( 'subject', $this->get_default_subject() );
			}

			/**
			 * Filter to adjust the email subject.
			 *
			 * @param string                              $subject The subject.
			 * @param EU_OWB_Email_New_Withdrawal_Request $email The email instance.
			 */
			return apply_filters( 'woocommerce_email_subject_new_withdrawal_request', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->is_update ) {
				$heading = $this->get_option( 'heading_update', $this->get_default_heading( true ) );
			} else {
				$heading = $this->get_option( 'heading', $this->get_default_heading() );
			}

			/**
			 * Filter to adjust the email heading.
			 *
			 * @param string                              $heading The heading.
			 * @param EU_OWB_Email_New_Withdrawal_Request $email The email instance.
			 */
			return apply_filters( 'woocommerce_email_heading_new_withdrawal_request', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Trigger.
		 *
		 * @param int|\Vendidero\OrderWithdrawalButton\WithdrawalOrder $withdrawal_id Withdrawal order ID.
		 */
		public function trigger( $withdrawal_id, $withdrawal = false ) {
			$this->setup_locale();

			if ( $withdrawal_id && ! is_a( $withdrawal, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
				$withdrawal = wc_get_order( $withdrawal_id );
			}

			if ( $withdrawal ) {
				$this->withdrawal       = $withdrawal;
				$this->object           = $withdrawal;
				$this->withdrawal_email = $this->withdrawal->get_email();
				$this->is_update        = $this->withdrawal->is_update();

				$this->setup_email_locale();

				$this->placeholders['{order_number}']            = $this->withdrawal->get_order_number();
				$this->placeholders['{contract_identification}'] = $this->withdrawal->get_contract_identification();
				$this->placeholders['{order_date}']              = wc_format_datetime( $this->withdrawal->get_date_created() );
				$this->placeholders['{withdrawal_date}']         = wc_format_datetime( $this->withdrawal->get_date_received() );
				$this->placeholders['{withdrawal_email}']        = $this->withdrawal_email;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_email_locale();
			$this->restore_locale();
		}

		public function get_recipient() {
			$recipients = parent::get_recipient();

			if ( $email = \Vendidero\OrderWithdrawalButton\Package::get_additional_admin_notification_recipient() ) {
				if ( ! empty( $email ) && ! strstr( $recipients, $email ) ) {
					$recipients .= ', ' . $email;
				}
			}

			return $recipients;
		}

		/**
		 * Return content from the additional_content field.
		 *
		 * Displayed above the footer.
		 *
		 * @return string
		 */
		public function get_additional_content() {
			if ( method_exists( get_parent_class( $this ), 'get_additional_content' ) ) {
				return parent::get_additional_content();
			}

			return '';
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'withdrawal_email'   => $this->withdrawal_email,
					'withdrawal'         => $this->withdrawal,
					'is_update'          => $this->is_update,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'withdrawal_email'   => $this->withdrawal_email,
					'withdrawal'         => $this->withdrawal,
					'is_update'          => $this->is_update,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return '';
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			parent::init_form_fields();

			/* translators: %s: list of placeholders */
			$placeholder_text = sprintf( _x( 'Available placeholders: %s', 'owb', 'woocommerce-germanized' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$form_fields      = array();

			foreach ( $this->form_fields as $key => $field ) {
				$form_fields[ $key ] = $field;

				if ( 'subject' === $key ) {
					$form_fields['subject_update'] = array(
						'title'       => _x( 'Updated withdrawal subject', 'owb', 'woocommerce-germanized' ),
						'type'        => 'text',
						'desc_tip'    => true,
						'description' => $placeholder_text,
						'placeholder' => $this->get_default_subject( true ),
						'default'     => '',
					);
				} elseif ( 'heading' === $key ) {
					$form_fields['heading_update'] = array(
						'title'       => _x( 'Updated withdrawal email heading', 'owb', 'woocommerce-germanized' ),
						'type'        => 'text',
						'desc_tip'    => true,
						'description' => $placeholder_text,
						'placeholder' => $this->get_default_heading( true ),
						'default'     => '',
					);
				}
			}

			$this->form_fields = array_merge(
				$form_fields,
				array(
					'recipient' => array(
						'title'       => _x( 'Recipient(s)', 'owb', 'woocommerce-germanized' ),
						'type'        => 'text',
						/* translators: %s: WP admin email */
						'description' => sprintf( _x( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'owb', 'woocommerce-germanized' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
						'placeholder' => '',
						'default'     => '',
						'desc_tip'    => true,
					),
				)
			);
		}
	}

endif;

return new EU_OWB_Email_New_Withdrawal_Request();
