<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Email_Customer_Paid_For_Order' ) ) :

	/**
	 * eKomi Review Reminder Email
	 *
	 * This Email is being sent after the order has been marked as completed to transfer the eKomi Rating Link to the customer.
	 *
	 * @class        WC_GZD_Email_Customer_Ekomi
	 * @version        1.0.0
	 * @author        Vendidero
	 */
	class WC_GZD_Email_Customer_Paid_For_Order extends WC_Email {

		public $helper;

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->id             = 'customer_paid_for_order';
			$this->customer_email = true;
			$this->title          = __( 'Paid for order', 'woocommerce-germanized' );
			$this->description    = __( 'This E-Mail is being sent to a customer after the order has been paid.', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-paid-for-order.php';
			$this->template_plain = 'emails/plain/customer-paid-for-order.php';
			$this->helper         = wc_gzd_get_email_helper( $this );

			// Triggers for this email
			add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 30 );

			$this->placeholders = array(
				'{site_title}'   => $this->get_blogname(),
				'{order_number}' => '',
			);

			// Call parent constuctor
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_subject() {
			return __( 'Payment received for order {order_number}', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading() {
			return __( 'Payment received', 'woocommerce-germanized' );
		}

		/**
		 * trigger function.
		 *
		 * @access public
		 * @return void
		 */
		public function trigger( $order_id ) {
			$this->helper->setup_locale();

			if ( $order_id ) {
				$this->object    = wc_get_order( $order_id );
				$this->recipient = $this->object->get_billing_email();

				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			$this->helper->setup_email_locale();

			if ( $this->is_enabled() && $this->get_recipient() ) {

				// Make sure gateways do not insert data here
				remove_all_actions( 'woocommerce_email_before_order_table' );

				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->helper->restore_email_locale();
			$this->helper->restore_locale();
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
		 * Get content html.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
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
		 * @access public
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

	}

endif;

return new WC_GZD_Email_Customer_Paid_For_Order();
