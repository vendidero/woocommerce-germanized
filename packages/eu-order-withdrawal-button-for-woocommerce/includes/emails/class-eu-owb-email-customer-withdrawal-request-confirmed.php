<?php
/**
 * Class EU_OWB_Email_Customer_Withdrawal_Request_Confirmed file.
 *
 * @package Vendidero/OrderWithdrawalButton/Emails
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed', false ) ) :

	/**
	 * Customer withdrawal request confirmed.
	 *
	 * Confirm the withdrawal request to the customer.
	 *
	 * @class    EU_OWB_Email_Customer_Withdrawal_Request_Confirmed
	 * @version  1.0.0
	 * @extends  WC_Email
	 */
	class EU_OWB_Email_Customer_Withdrawal_Request_Confirmed extends WC_Email {

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
			$this->id             = 'customer_withdrawal_request_confirmed';
			$this->title          = _x( 'Withdrawal request confirmed', 'owb', 'woocommerce-germanized' );
			$this->description    = _x( 'Confirms the withdrawal request to the customer.', 'owb', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-withdrawal-request-confirmed.php';
			$this->template_plain = 'emails/plain/customer-withdrawal-request-confirmed.php';
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
			return _x( 'Your withdrawal request for order #{order_number} has been confirmed.', 'owb', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			return _x( 'Your withdrawal request has been confirmed.', 'owb', 'woocommerce-germanized' );
		}

		/**
		 * Trigger.
		 *
		 * @param int $withdrawal_id Withdrawal order ID.
		 * @param \Vendidero\OrderWithdrawalButton\WithdrawalOrder|false $withdrawal
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

				$this->placeholders['{order_number}']    = $this->withdrawal->get_order_number();
				$this->placeholders['{order_date}']      = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{withdrawal_date}'] = wc_format_datetime( $this->withdrawal->get_date_received() );
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

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
					'withdrawal'         => $this->withdrawal,
					'partial_withdrawal' => $this->partial_withdrawal,
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

return new EU_OWB_Email_Customer_Withdrawal_Request_Confirmed();
