<?php
/**
 * Class EU_OWB_Email_Customer_Withdrawal_Request_Rejected file.
 *
 * @package Vendidero/OrderWithdrawalButton/Emails
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EU_OWB_Email_Customer_Withdrawal_Request_Rejected', false ) ) :

	/**
	 * Customer withdrawal request confirmed.
	 *
	 * Confirm the withdrawal request to the customer.
	 *
	 * @class    EU_OWB_Email_Customer_Withdrawal_Request_Rejected
	 * @version  1.0.0
	 * @extends  WC_Email
	 */
	class EU_OWB_Email_Customer_Withdrawal_Request_Rejected extends WC_Email {

		use \Vendidero\OrderWithdrawalButton\EmailTranslationHelper;

		/**
		 * Is this a partial withdrawal request?
		 *
		 * @var bool
		 */
		public $partial_withdrawal;

		/**
		 * @var \Vendidero\OrderWithdrawalButton\WithdrawalOrder
		 */
		public $withdrawal;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->customer_email = true;
			$this->id             = 'customer_withdrawal_request_rejected';
			$this->title          = _x( 'Withdrawal request rejected', 'owb', 'woocommerce-germanized' );
			$this->description    = _x( 'Informs the customer that their withdrawal request has been rejected.', 'owb', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-withdrawal-request-rejected.php';
			$this->template_plain = 'emails/plain/customer-withdrawal-request-rejected.php';
			$this->template_base  = \Vendidero\OrderWithdrawalButton\Package::get_path() . '/templates/';

			$this->placeholders = array(
				'{site_title}'      => $this->get_blogname(),
				'{order_number}'    => '',
				'{order_date}'      => '',
				'{withdrawal_date}' => '',
			);

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return _x( 'Your withdrawal request has been rejected.', 'owb', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			return _x( 'Your withdrawal request has been rejected.', 'owb', 'woocommerce-germanized' );
		}

		/**
		 * Trigger.
		 *
		 * @param int $withdrawal_id Withdrawal order ID.
		 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder|false $withdrawal
		 * @param string $reason
		 */
		public function trigger( $withdrawal_id, $withdrawal = false ) {
			$this->setup_locale();

			if ( $withdrawal_id && ! is_a( $withdrawal, '\Vendidero\OrderWithdrawalButton\WithdrawalOrder' ) ) {
				$withdrawal = wc_get_order( $withdrawal_id );
			}

			if ( $withdrawal ) {
				$this->withdrawal         = $withdrawal;
				$this->object             = $withdrawal;
				$this->recipient          = $this->withdrawal->get_email();
				$this->partial_withdrawal = $this->withdrawal->is_partial();

				$this->setup_email_locale();

				$this->placeholders['{order_number}']    = $this->object->get_order_number();
				$this->placeholders['{order_date}']      = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{withdrawal_date}'] = wc_format_datetime( $this->withdrawal->get_date_received() );
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_email_locale();
			$this->restore_locale();
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
					'partial_withdrawal' => $this->partial_withdrawal,
					'withdrawal'         => $this->withdrawal,
					'reason'             => $this->withdrawal->get_rejection_reason(),
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
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
					'partial_withdrawal' => $this->partial_withdrawal,
					'withdrawal'         => $this->withdrawal,
					'reason'             => $this->withdrawal->get_rejection_reason(),
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
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
	}

endif;

return new EU_OWB_Email_Customer_Withdrawal_Request_Rejected();
