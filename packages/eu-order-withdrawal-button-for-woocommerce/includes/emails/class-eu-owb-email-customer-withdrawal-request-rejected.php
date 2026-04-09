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

		/**
		 * Is this a partial withdrawal request?
		 *
		 * @var bool
		 */
		public $partial_withdrawal;

		public $withdrawal;

		/**
		 * The reason for rejection.
		 *
		 * @var string
		 */
		public $reason = '';

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
			return _x( 'Your withdrawal request for order #{order_number} has been rejected.', 'owb', 'woocommerce-germanized' );
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
		 * @param int $order_id Order ID.
		 * @param WC_Order|false $order
		 * @param string|array $id
		 * @param string $reason
		 */
		public function trigger( $order_id, $order = false, $id = '', $reason = '' ) {
			$this->setup_locale();

			$this->reason = $reason;

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( $order ) {
				$this->object             = $order;
				$this->withdrawal         = eu_owb_get_order_withdrawal( $this->object, $id );
				$this->recipient          = eu_owb_get_order_withdrawal_email( $this->object, $this->withdrawal );
				$this->partial_withdrawal = wc_string_to_bool( $this->withdrawal['is_partial'] );

				$this->placeholders['{order_number}']    = $this->object->get_order_number();
				$this->placeholders['{order_date}']      = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{withdrawal_date}'] = eu_owb_get_order_withdrawal_date_received( $this->object, $this->withdrawal ) ? wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $this->object, $this->withdrawal ) ) : '';
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
					'partial_withdrawal' => $this->partial_withdrawal,
					'withdrawal'         => $this->withdrawal,
					'reason'             => $this->reason,
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
					'reason'             => $this->reason,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
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
