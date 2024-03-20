<?php
/**
 * Class WC_GZD_Email_New_Return_Shipment file.
 *
 * @package Vendidero/Germanized/Shipments/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ReturnShipment;

if ( ! class_exists( 'WC_GZD_Email_New_Return_Shipment_Request', false ) ) :

	/**
	 * Admin return shipment notification.
	 *
	 * @class    WC_GZD_Email_New_Return_Shipment_Request
	 * @version  1.0.0
	 * @package  Vendidero/Germanized/Shipments/Emails
	 * @extends  WC_Email
	 */
	class WC_GZD_Email_New_Return_Shipment_Request extends WC_Email {

		/**
		 * Shipment.
		 *
		 * @var ReturnShipment|bool
		 */
		public $shipment;

		public $helper = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'new_return_shipment_request';
			$this->title       = _x( 'New order return request', 'shipments', 'woocommerce-germanized' );
			$this->description = _x( 'New order return request emails are sent to chosen recipient(s) when a new return is requested.', 'shipments', 'woocommerce-germanized' );

			$this->template_html  = 'emails/admin-new-return-shipment-request.php';
			$this->template_plain = 'emails/plain/admin-new-return-shipment-request.php';
			$this->template_base  = Package::get_path() . '/templates/';

			$this->placeholders = array(
				'{site_title}'      => $this->get_blogname(),
				'{shipment_number}' => '',
				'{order_number}'    => '',
				'{order_date}'      => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_gzd_new_customer_return_shipment_request', array( $this, 'trigger' ), 10 );

			// Call parent constructor.
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return _x( '[{site_title}]: New return request to #{order_number}', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return _x( 'New return request to: #{order_number}', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * Trigger.
		 *
		 * @param int|ReturnShipment $shipment_id Shipment ID.
		 */
		public function trigger( $shipment_id ) {
			$this->setup_locale();

			if ( $this->shipment = wc_gzd_get_shipment( $shipment_id ) ) {

				if ( 'return' !== $this->shipment->get_type() ) {
					return;
				}

				$this->placeholders['{shipment_number}'] = $this->shipment->get_shipment_number();

				if ( $order_shipment = wc_gzd_get_shipment_order( $this->shipment->get_order() ) ) {
					$this->object                         = $this->shipment->get_order();
					$this->placeholders['{order_date}']   = wc_format_datetime( $order_shipment->get_order()->get_date_created() );
					$this->placeholders['{order_number}'] = $order_shipment->get_order()->get_order_number();
				}
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
		 * @since 2.0.4
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
					'shipment'           => $this->shipment,
					'order'              => $this->object,
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
					'shipment'           => $this->shipment,
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		public function get_attachments() {
			$attachments = array();

			if ( $this->shipment->has_label() ) {
				$label = $this->shipment->get_label();

				if ( $file = $label->get_file() ) {
					$attachments[] = $file;
				}
			}

			return apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $this->object, $this );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 1.0.1
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
						'title'       => _x( 'Recipient(s)', 'shipments', 'woocommerce-germanized' ),
						'type'        => 'text',
						/* translators: %s: WP admin email */
						'description' => sprintf( _x( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'shipments', 'woocommerce-germanized' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
						'placeholder' => '',
						'default'     => '',
						'desc_tip'    => true,
					),
				)
			);
		}
	}

endif;

return new WC_GZD_Email_New_Return_Shipment_Request();
