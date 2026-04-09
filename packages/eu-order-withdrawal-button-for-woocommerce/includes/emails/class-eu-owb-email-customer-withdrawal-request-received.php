<?php
/**
 * Class EU_OWB_Email_Customer_Withdrawal_Request_Received file.
 *
 * @package Vendidero/OrderWithdrawalButton/Emails
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EU_OWB_Email_Customer_Withdrawal_Request_Received', false ) ) :

	/**
	 * Customer withdrawal request received.
	 *
	 * Confirm the withdrawal request to the customer.
	 *
	 * @class    EU_OWB_Email_Customer_Withdrawal_Request_Received
	 * @version  1.0.0
	 * @extends  WC_Email
	 */
	class EU_OWB_Email_Customer_Withdrawal_Request_Received extends WC_Email {

		/**
		 * Is this a partial withdrawal request?
		 *
		 * @var bool
		 */
		public $partial_withdrawal;

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
			$this->customer_email = true;
			$this->id             = 'customer_withdrawal_request_received';
			$this->title          = _x( 'Withdrawal request received', 'owb', 'woocommerce-germanized' );
			$this->description    = _x( 'Confirm that the withdrawal request has been received.', 'owb', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-withdrawal-request-received.php';
			$this->template_plain = 'emails/plain/customer-withdrawal-request-received.php';
			$this->template_base  = \Vendidero\OrderWithdrawalButton\Package::get_path() . '/templates/';

			$this->placeholders = array(
				'{site_title}'      => $this->get_blogname(),
				'{withdrawal_date}' => '',
				'{order_number}'    => '',
				'{order_date}'      => '',
			);

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @return string
		 */
		public function get_default_subject( $partial = false ) {
			if ( $partial ) {
				return _x( 'Your partial withdrawal request for order #{order_number} has been received.', 'owb', 'woocommerce-germanized' );
			} else {
				return _x( 'Your withdrawal request for order #{order_number} has been received.', 'owb', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			if ( $partial ) {
				return _x( 'We received your partial withdrawal request', 'owb', 'woocommerce-germanized' );
			} else {
				return _x( 'We received your withdrawal request', 'owb', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->partial_withdrawal ) {
				$subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
			} else {
				$subject = $this->get_option( 'subject_full', $this->get_default_subject() );
			}

			/**
			 * Filter to adjust the email subject.
			 *
			 * @param string                                            $subject The subject.
			 * @param EU_OWB_Email_Customer_Withdrawal_Request_Received $email The email instance.
			 */
			return apply_filters( 'woocommerce_email_subject_customer_withdrawal_request_received', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->partial_withdrawal ) {
				$heading = $this->get_option( 'heading_partial', $this->get_default_heading( true ) );
			} else {
				$heading = $this->get_option( 'heading_full', $this->get_default_heading() );
			}

			/**
			 * Filter to adjust the email heading.
			 *
			 * @param string                                            $heading The heading.
			 * @param EU_OWB_Email_Customer_Withdrawal_Request_Received $email The email instance.
			 */
			return apply_filters( 'woocommerce_email_heading_customer_withdrawal_request_received', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Trigger.
		 *
		 * @param int $order_id Order ID.
		 */
		public function trigger( $order_id, $order = false, $recipient = '', $is_partial_withdrawal = null, $is_update = null ) {
			$this->setup_locale();

			$this->recipient = $recipient;

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( $order ) {
				$this->object             = $order;
				$this->recipient          = eu_owb_get_order_withdrawal_email( $this->object );
				$this->withdrawal         = eu_owb_get_withdrawal_request( $this->object );
				$this->partial_withdrawal = is_bool( $is_partial_withdrawal ) ? $is_partial_withdrawal : eu_owb_order_has_partial_withdrawal_request( $this->object );
				$this->is_update          = is_bool( $is_update ) ? $is_update : eu_owb_order_is_withdrawal_request_update( $this->object );

				$this->placeholders['{order_number}']    = $this->object->get_order_number();
				$this->placeholders['{order_date}']      = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{withdrawal_date}'] = eu_owb_get_order_withdrawal_date_received( $this->object ) ? wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $this->object ) ) : '';
			}

			$this->id = $this->partial_withdrawal ? 'customer_partial_withdrawal_request_received' : 'customer_withdrawal_request_received';

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
					'is_update'          => $this->is_update,
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
					'is_update'          => $this->is_update,
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

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text = sprintf( _x( 'Available placeholders: %s', 'owb', 'woocommerce-germanized' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

			$this->form_fields = array(
				'enabled'            => array(
					'title'   => _x( 'Enable/Disable', 'owb', 'woocommerce-germanized' ),
					'type'    => 'checkbox',
					'label'   => _x( 'Enable this email notification', 'owb', 'woocommerce-germanized' ),
					'default' => 'yes',
				),
				'subject_full'       => array(
					'title'       => _x( 'Full withdrawal subject', 'owb', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'subject_partial'    => array(
					'title'       => _x( 'Partial withdrawal subject', 'owb', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_full'       => array(
					'title'       => _x( 'Full withdrawal email heading', 'owb', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'heading_partial'    => array(
					'title'       => _x( 'Partial withdrawal email heading', 'owb', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading( true ),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => _x( 'Additional content', 'owb', 'woocommerce-germanized' ),
					'description' => _x( 'Text to appear below the main email content.', 'owb', 'woocommerce-germanized' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => _x( 'N/A', 'owb', 'woocommerce-germanized' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => _x( 'Email type', 'owb', 'woocommerce-germanized' ),
					'type'        => 'select',
					'description' => _x( 'Choose which format of email to send.', 'owb', 'woocommerce-germanized' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

endif;

return new EU_OWB_Email_Customer_Withdrawal_Request_Received();
