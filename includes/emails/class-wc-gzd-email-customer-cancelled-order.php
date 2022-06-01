<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Email_Customer_Cancelled_Order' ) ) :

	/**
	 * Cancelled, failed order notification for customers
	 *
	 * @class        WC_GZD_Email_Customer_Cancelled_Order
	 * @version      1.0.0
	 * @author       vendidero
	 */
	class WC_GZD_Email_Customer_Cancelled_Order extends WC_Email {

		public $helper;

		protected $has_failed = false;

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->id             = 'customer_cancelled_order';
			$this->customer_email = true;
			$this->title          = __( 'Cancelled order', 'woocommerce-germanized' );
			$this->description    = __( 'This E-Mail is being sent to a customer in case the order was cancelled and/or has failed.', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-cancelled-order.php';
			$this->template_plain = 'emails/plain/customer-cancelled-order.php';
			$this->helper         = wc_gzd_get_email_helper( $this );

			// Triggers for this email.
			add_action( 'woocommerce_order_status_pending_to_failed_notification', array( $this, 'trigger_failed' ), 10, 1 );
			add_action( 'woocommerce_order_status_on-hold_to_failed_notification', array( $this, 'trigger_failed' ), 10, 1 );

			add_action( 'woocommerce_order_status_pending_to_cancelled_notification', array( $this, 'trigger' ), 10, 1 );
			add_action( 'woocommerce_order_status_processing_to_cancelled_notification', array( $this, 'trigger' ), 10, 1 );
			add_action( 'woocommerce_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ), 10, 1 );

			$this->placeholders = array(
				'{order_date}'   => '',
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
		public function get_default_subject( $failed = false ) {
			if ( $failed ) {
				return __( 'Your {site_title} order #{order_number} has failed', 'woocommerce-germanized' );
			} else {
				return __( 'Your {site_title} order #{order_number} has been cancelled', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading( $failed = false ) {
			if ( $failed ) {
				return __( 'Failed order: {order_number}', 'woocommerce-germanized' );
			} else {
				return __( 'Cancelled order: {order_number}', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->has_failed ) {
				$subject = $this->get_option( 'subject_failed', $this->get_default_subject( true ) );
			} else {
				$subject = $this->get_option( 'subject_full', $this->get_default_subject() );
			}

			return apply_filters( 'woocommerce_email_subject_customer_cancelled_order', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->has_failed ) {
				$heading = $this->get_option( 'heading_failed', $this->get_default_heading( true ) );
			} else {
				$heading = $this->get_option( 'heading_full', $this->get_default_heading() );
			}

			return apply_filters( 'woocommerce_email_heading_customer_cancelled_order', $this->format_string( $heading ), $this->object, $this );
		}

		public function trigger_failed( $order_id ) {
			if ( 'yes' === $this->get_option( 'failed_enabled' ) ) {
				$this->trigger( $order_id, true );
			}
		}

		/**
		 * trigger function.
		 *
		 * @access public
		 * @return void
		 */
		public function trigger( $order_id, $has_failed = false ) {
			$this->helper->setup_locale();

			$this->has_failed = $has_failed;
			$this->id         = $this->has_failed ? 'customer_failed_order' : 'customer_cancelled_order';

			if ( $order_id ) {
				$this->object                         = wc_get_order( $order_id );
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			$this->helper->setup_email_locale();

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
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'has_failed'         => $this->has_failed,
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
					'has_failed'         => $this->has_failed,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-germanized' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'woocommerce-germanized' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce-germanized' ),
					'default' => wc_gzd_send_instant_order_confirmation() ? 'yes' : 'no',
				),
				'failed_enabled'     => array(
					'title'   => __( 'Enable failed', 'woocommerce-germanized' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable failed order customer notification', 'woocommerce-germanized' ),
					'default' => 'no',
				),
				'subject_full'       => array(
					'title'       => __( 'Cancelled email subject', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'subject_partial'    => array(
					'title'       => __( 'Failed email subject', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_full'       => array(
					'title'       => __( 'Cancelled email heading', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'heading_partial'    => array(
					'title'       => __( 'Failed email heading', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading( true ),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'woocommerce-germanized' ),
					'description' => __( 'Text to appear below the main email content.', 'woocommerce-germanized' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'woocommerce-germanized' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'woocommerce-germanized' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce-germanized' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}

	}

endif;

return new WC_GZD_Email_Customer_Cancelled_Order();
