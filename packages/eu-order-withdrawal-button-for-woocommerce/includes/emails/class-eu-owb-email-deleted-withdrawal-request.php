<?php

/**
 * Class EU_OWB_Email_New_Withdrawal_Request file.
 *
 * @package Vendidero/OrderWithdrawalButton/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EU_OWB_Email_Deleted_Withdrawal_Request', false ) ) :

	/**
	 * Admin withdrawal request notification.
	 *
	 * @class    EU_OWB_Email_New_Withdrawal_Request
	 * @version  1.0.0
	 * @extends  WC_Email
	 */
	class EU_OWB_Email_Deleted_Withdrawal_Request extends WC_Email {

		public $withdrawal_email = '';

		public $withdrawal;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'deleted_withdrawal_request';
			$this->title       = _x( 'Withdrawal request deleted', 'owb', 'woocommerce-germanized' );
			$this->description = _x( 'Withdrawal request deleted emails are sent to chosen recipient(s) when a customer deletes a withdrawal request.', 'owb', 'woocommerce-germanized' );

			$this->template_html  = 'emails/admin-deleted-withdrawal-request.php';
			$this->template_plain = 'emails/plain/admin-deleted-withdrawal-request.php';
			$this->template_base  = \Vendidero\OrderWithdrawalButton\Package::get_path() . '/templates/';

			$this->placeholders = array(
				'{site_title}'       => $this->get_blogname(),
				'{order_number}'     => '',
				'{order_date}'       => '',
				'{withdrawal_date}'  => '',
				'{withdrawal_email}' => '',
			);

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return _x( '[{site_title}]: Withdrawal request for #{order_number} deleted', 'owb', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return _x( 'Withdrawal request deleted: #{order_number}', 'owb', 'woocommerce-germanized' );
		}

		/**
		 * Trigger.
		 *
		 * @param int|WC_Order $order_id Order ID.
		 * @param array $request
		 */
		public function trigger( $order_id, $request, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( $order ) {
				$this->object           = $order;
				$this->withdrawal       = $request;
				$this->withdrawal_email = eu_owb_get_order_withdrawal_email( $this->object, $this->withdrawal );

				$this->placeholders['{order_number}']     = $this->object->get_order_number();
				$this->placeholders['{order_date}']       = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{withdrawal_date}']  = eu_owb_get_order_withdrawal_date_received( $this->object, $this->withdrawal ) ? wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $this->object, $this->withdrawal ) ) : '';
				$this->placeholders['{withdrawal_email}'] = $this->withdrawal_email;
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
					'withdrawal_email'   => $this->withdrawal_email,
					'withdrawal'         => $this->withdrawal,
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

			$this->form_fields = array_merge(
				$this->form_fields,
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

return new EU_OWB_Email_Deleted_Withdrawal_Request();
