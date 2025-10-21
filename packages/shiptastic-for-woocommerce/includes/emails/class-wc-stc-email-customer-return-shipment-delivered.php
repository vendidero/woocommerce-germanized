<?php
/**
 * Class WC_STC_Email_Customer_Return_Shipment_Delivered file.
 *
 * @package Vendidero/Shiptastic/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\ReturnShipment;

if ( ! class_exists( 'WC_STC_Email_Customer_Return_Shipment_Delivered', false ) ) :

	/**
	 * Customer return shipment delivered notification.
	 *
	 * This notification is being sent to the customer to inform him that his return was delivered successfully.
	 *
	 * @class    WC_STC_Email_Customer_Return_Shipment_Delivered
	 * @version  1.0.0
	 * @package  Vendidero/Shiptastic/Emails
	 * @extends  WC_Email
	 */
	class WC_STC_Email_Customer_Return_Shipment_Delivered extends WC_Email {

		/**
		 * Shipment.
		 *
		 * @var ReturnShipment|bool
		 */
		public $shipment;

		/**
		 * @var \Vendidero\Shiptastic\EmailLocale
		 */
		public $helper = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->customer_email = true;
			$this->id             = 'customer_return_shipment_delivered';
			$this->title          = _x( 'Order return delivered', 'shipments', 'woocommerce-germanized' );
			$this->description    = _x( 'Order return notifications are sent to the customer after a return shipment has been returned (delivered) successfully.', 'shipments', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-return-shipment-delivered.php';
			$this->template_plain = 'emails/plain/customer-return-shipment-delivered.php';
			$this->template_base  = Package::get_path() . '/templates/';
			$this->helper         = wc_stc_get_email_locale_helper( $this );

			$this->placeholders = array(
				'{site_title}'      => $this->get_blogname(),
				'{shipment_number}' => '',
				'{order_number}'    => '',
				'{order_date}'      => '',
				'{date_sent}'       => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_shiptastic_return_shipment_status_processing_to_delivered_notification', array( $this, 'trigger' ), 10 );
			add_action( 'woocommerce_shiptastic_return_shipment_status_shipped_to_delivered_notification', array( $this, 'trigger' ), 10 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return _x( 'Return to your order {order_number} has been received', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return _x( 'Received return to your order: {order_number}', 'shipments', 'woocommerce-germanized' );
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
		 * @param int $shipment_id Shipment ID.
		 * @param bool $is_confirmation
		 */
		public function trigger( $shipment_id ) {
			$this->setup_locale();

			if ( $this->shipment = wc_stc_get_shipment( $shipment_id ) ) {
				if ( 'return' !== $this->shipment->get_type() ) {
					return;
				}

				$this->placeholders['{shipment_number}'] = $this->shipment->get_shipment_number();

				if ( $order_shipment = wc_stc_get_shipment_order( $this->shipment->get_order() ) ) {
					$this->object    = $this->shipment->get_order();
					$this->recipient = $order_shipment->get_order()->get_billing_email();

					$this->helper->setup_email_locale();

					$this->placeholders['{order_date}']   = wc_format_datetime( $order_shipment->get_order()->get_date_created() );
					$this->placeholders['{order_number}'] = $order_shipment->get_order()->get_order_number();

					if ( $this->shipment->get_date_sent() ) {
						$this->placeholders['{date_sent}'] = wc_format_datetime( $this->shipment->get_date_sent() );
					}
				}
			}

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

return new WC_STC_Email_Customer_Return_Shipment_Delivered();
