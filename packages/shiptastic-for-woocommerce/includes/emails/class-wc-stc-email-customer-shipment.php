<?php
/**
 * Class WC_STC_Email_Customer_Shipment file.
 *
 * @package Vendidero/Shiptastic/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Shipment;

if ( ! class_exists( 'WC_STC_Email_Customer_Shipment', false ) ) :

	/**
	 * Customer Shipment notification.
	 *
	 * Shipment notification are sent as soon as a shipment is marked as shipped.
	 *
	 * @class    WC_STC_Email_Customer_Shipment
	 * @version  1.0.0
	 * @package  Vendidero/Shiptastic/Emails
	 * @extends  WC_Email
	 */
	class WC_STC_Email_Customer_Shipment extends WC_Email {

		/**
		 * Shipment.
		 *
		 * @var Shipment|bool
		 */
		public $shipment;

		/**
		 * Is the order partial shipped?
		 *
		 * @var bool
		 */
		public $partial_shipment;

		/**
		 * @var \Vendidero\Shiptastic\EmailLocale
		 */
		public $helper = null;

		public $total_shipments = 1;

		public $cur_position = 1;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->customer_email = true;
			$this->id             = 'customer_shipment';
			$this->title          = _x( 'Order shipped', 'shipments', 'woocommerce-germanized' );
			$this->description    = _x( 'Shipment notifications are sent to the customer when a shipment gets shipped.', 'shipments', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-shipment.php';
			$this->template_plain = 'emails/plain/customer-shipment.php';
			$this->template_base  = Package::get_path() . '/templates/';
			$this->helper         = wc_stc_get_email_locale_helper( $this );

			$this->placeholders = array(
				'{site_title}'           => $this->get_blogname(),
				'{shipment_number}'      => '',
				'{order_number}'         => '',
				'{order_date}'           => '',
				'{date_sent}'            => '',
				'{current_shipment_num}' => '',
				'{total_shipments}'      => '',
			);

			// Triggers for this email.
			if ( 'yes' === Package::get_setting( 'notify_enable' ) ) {
				add_action( 'woocommerce_shiptastic_shipment_status_draft_to_shipped_notification', array( $this, 'trigger' ), 10 );
				add_action( 'woocommerce_shiptastic_shipment_status_processing_to_shipped_notification', array( $this, 'trigger' ), 10 );
				add_action( 'woocommerce_shiptastic_shipment_status_ready-for-shipping_to_shipped_notification', array( $this, 'trigger' ), 10 );
			}

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
				return _x( 'Your {site_title} order #{order_number} has been partially shipped ({current_shipment_num}/{total_shipments})', 'shipments', 'woocommerce-germanized' );
			} else {
				return _x( 'Your {site_title} order #{order_number} has been shipped ({current_shipment_num}/{total_shipments})', 'shipments', 'woocommerce-germanized' );
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
				return _x( 'Partial shipment to your order: {order_number}', 'shipments', 'woocommerce-germanized' );
			} else {
				return _x( 'Shipment to your order: {order_number}', 'shipments', 'woocommerce-germanized' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->partial_shipment ) {
				$subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
			} else {
				$subject = $this->get_option( 'subject_full', $this->get_default_subject() );
			}

			/**
			 * Filter to adjust the email subject for a shipped Shipment.
			 *
			 * @param string                         $subject The subject.
			 * @param WC_STC_Email_Customer_Shipment $email The email instance.
			 *
			 * @package Vendidero/Shiptastic
			 */
			return apply_filters( 'woocommerce_email_subject_customer_shipment', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->partial_shipment ) {
				$heading = $this->get_option( 'heading_partial', $this->get_default_heading( true ) );
			} else {
				$heading = $this->get_option( 'heading_full', $this->get_default_heading() );
			}

			/**
			 * Filter to adjust the email heading for a shipped Shipment.
			 *
			 * @param string                         $heading The heading.
			 * @param WC_STC_Email_Customer_Shipment $email The email instance.
			 *
			 * @package Vendidero/Shiptastic
			 */
			return apply_filters( 'woocommerce_email_heading_customer_shipment', $this->format_string( $heading ), $this->object, $this );
		}

		public function get_shipped_position_number( $shipment_id ) {
			$shipped_count = 1;

			if ( ! $this->object || ( ! $order = wc_stc_get_shipment_order( $this->object ) ) ) {
				return $shipped_count;
			}

			if ( is_a( $shipment_id, '\Vendidero\Shiptastic\Shipment' ) ) {
				$shipment_id = $shipment_id->get_id();
			}

			if ( ! is_numeric( $shipment_id ) ) {
				return $shipped_count;
			}

			foreach ( $order->get_simple_shipments() as $key => $shipment ) {
				if ( $shipment->is_shipped() ) {
					if ( (int) $shipment->get_id() !== (int) $shipment_id ) {
						++$shipped_count;
					}
				}
			}

			return $shipped_count;
		}

		public function setup_locale() {
			parent::setup_locale();

			$this->helper->setup_locale();
		}

		public function restore_locale() {
			parent::restore_locale();

			$this->helper->restore_locale();
		}

		/**
		 * Trigger.
		 *
		 * @param int  $shipment_id Shipment ID.
		 */
		public function trigger( $shipment_id ) {
			$this->setup_locale();

			$this->partial_shipment = false;

			if ( $this->shipment = wc_stc_get_shipment( $shipment_id ) ) {
				$this->placeholders['{shipment_number}'] = $this->shipment->get_shipment_number();

				if ( $order_shipment = wc_stc_get_shipment_order( $this->shipment->get_order() ) ) {
					$this->object          = $this->shipment->get_order();
					$this->total_shipments = count( $order_shipment->get_simple_shipments() );
					$this->cur_position    = $this->get_shipped_position_number( $shipment_id );

					if ( $order_shipment->needs_shipping() || $this->total_shipments > 1 ) {
						if ( $order_shipment->needs_shipping() || ( $this->cur_position < $this->total_shipments ) ) {
							$this->partial_shipment = true;
						}
					}

					$this->helper->setup_email_locale();

					$this->recipient                              = $order_shipment->get_order()->get_billing_email();
					$this->placeholders['{order_date}']           = wc_format_datetime( $order_shipment->get_order()->get_date_created() );
					$this->placeholders['{order_number}']         = $order_shipment->get_order()->get_order_number();
					$this->placeholders['{total_shipments}']      = $this->total_shipments;
					$this->placeholders['{current_shipment_num}'] = $this->cur_position;

					if ( $this->shipment->get_date_sent() ) {
						$this->placeholders['{date_sent}'] = wc_format_datetime( $this->shipment->get_date_sent() );
					}
				}
			}

			$this->id = $this->partial_shipment ? 'customer_partial_shipment' : 'customer_shipment';

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->helper->restore_email_locale();
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
					'shipment'           => $this->shipment,
					'order'              => $this->object,
					'partial_shipment'   => $this->partial_shipment,
					'cur_position'       => $this->cur_position,
					'total_shipments'    => $this->total_shipments,
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
					'shipment'           => $this->shipment,
					'order'              => $this->object,
					'partial_shipment'   => $this->partial_shipment,
					'cur_position'       => $this->cur_position,
					'total_shipments'    => $this->total_shipments,
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

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text = sprintf( _x( 'Available placeholders: %s', 'shipments', 'woocommerce-germanized' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

			$this->form_fields = array(
				'enabled'            => array(
					'title'   => _x( 'Enable/Disable', 'shipments', 'woocommerce-germanized' ),
					'type'    => 'checkbox',
					'label'   => _x( 'Enable this email notification', 'shipments', 'woocommerce-germanized' ),
					'default' => 'yes',
				),
				'subject_full'       => array(
					'title'       => _x( 'Full shipment subject', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'subject_partial'    => array(
					'title'       => _x( 'Partial shipment subject', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_full'       => array(
					'title'       => _x( 'Full shipment email heading', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'heading_partial'    => array(
					'title'       => _x( 'Partial shipment email heading', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading( true ),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => _x( 'Additional content', 'shipments', 'woocommerce-germanized' ),
					'description' => _x( 'Text to appear below the main email content.', 'shipments', 'woocommerce-germanized' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => _x( 'N/A', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => _x( 'Email type', 'shipments', 'woocommerce-germanized' ),
					'type'        => 'select',
					'description' => _x( 'Choose which format of email to send.', 'shipments', 'woocommerce-germanized' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

endif;

return new WC_STC_Email_Customer_Shipment();
