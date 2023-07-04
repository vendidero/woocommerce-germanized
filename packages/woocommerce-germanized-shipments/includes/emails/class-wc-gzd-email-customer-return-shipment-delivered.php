<?php
/**
 * Class WC_GZD_Email_Customer_Return_Shipment_Delivered file.
 *
 * @package Vendidero/Germanized/Shipments/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ReturnShipment;

if ( ! class_exists( 'WC_GZD_Email_Customer_Return_Shipment_Delivered', false ) ) :

	/**
	 * Customer return shipment delivered notification.
	 *
	 * This notification is being sent to the customer to inform him that his return was delivered successfully.
	 *
	 * @class    WC_GZD_Email_Customer_Return_Shipment_Delivered
	 * @version  1.0.0
	 * @package  Vendidero/Germanized/Shipments/Emails
	 * @extends  WC_Email
	 */
	class WC_GZD_Email_Customer_Return_Shipment_Delivered extends WC_Email {

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
			$this->customer_email = true;
			$this->id             = 'customer_return_shipment_delivered';
			$this->title          = _x( 'Order return delivered', 'shipments', 'woocommerce-germanized' );
			$this->description    = _x( 'Order return notifications are sent to the customer after a return shipment has been returned (delivered) successfully.', 'shipments', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-return-shipment-delivered.php';
			$this->template_plain = 'emails/plain/customer-return-shipment-delivered.php';
			$this->template_base  = Package::get_path() . '/templates/';
			$this->helper         = function_exists( 'wc_gzd_get_email_helper' ) ? wc_gzd_get_email_helper( $this ) : false;

			$this->placeholders = array(
				'{site_title}'      => $this->get_blogname(),
				'{shipment_number}' => '',
				'{order_number}'    => '',
				'{order_date}'      => '',
				'{date_sent}'       => '',
			);

			// Triggers for this email.
			add_action( 'woocommerce_gzd_return_shipment_status_processing_to_delivered_notification', array( $this, 'trigger' ), 10 );
			add_action( 'woocommerce_gzd_return_shipment_status_shipped_to_delivered_notification', array( $this, 'trigger' ), 10 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return _x( 'Return to your order {order_number} has been received', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return _x( 'Received return to your order: {order_number}', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * Switch Woo and Germanized locale
		 */
		public function setup_locale() {

			if ( $this->is_customer_email() && function_exists( 'wc_gzd_switch_to_site_locale' ) && apply_filters( 'woocommerce_email_setup_locale', true ) ) {
				wc_gzd_switch_to_site_locale();
			}

			parent::setup_locale();
		}

		/**
		 * Restore Woo and Germanized locale
		 */
		public function restore_locale() {

			if ( $this->is_customer_email() && function_exists( 'wc_gzd_restore_locale' ) && apply_filters( 'woocommerce_email_restore_locale', true ) ) {
				wc_gzd_restore_locale();
			}

			parent::restore_locale();
		}

		/**
		 * Trigger.
		 *
		 * @param int $shipment_id Shipment ID.
		 * @param bool $is_confirmation
		 */
		public function trigger( $shipment_id ) {
			if ( $this->helper ) {
				$this->helper->setup_locale();
			} else {
				$this->setup_locale();
			}

			if ( $this->shipment = wc_gzd_get_shipment( $shipment_id ) ) {

				if ( 'return' !== $this->shipment->get_type() ) {
					return;
				}

				$this->placeholders['{shipment_number}'] = $this->shipment->get_shipment_number();

				if ( $order_shipment = wc_gzd_get_shipment_order( $this->shipment->get_order() ) ) {

					$this->object                         = $this->shipment->get_order();
					$this->recipient                      = $order_shipment->get_order()->get_billing_email();
					$this->placeholders['{order_date}']   = wc_format_datetime( $order_shipment->get_order()->get_date_created() );
					$this->placeholders['{order_number}'] = $order_shipment->get_order()->get_order_number();

					if ( $this->shipment->get_date_sent() ) {
						$this->placeholders['{date_sent}'] = wc_format_datetime( $this->shipment->get_date_sent() );
					}
				}
			}

			if ( $this->helper ) {
				$this->helper->setup_email_locale();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			if ( $this->helper ) {
				$this->helper->restore_email_locale();
			}

			if ( $this->helper ) {
				$this->helper->restore_locale();
			} else {
				$this->restore_locale();
			}
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
		 * @since 1.0.1
		 * @return string
		 */
		public function get_default_additional_content() {
			return '';
		}
	}

endif;

return new WC_GZD_Email_Customer_Return_Shipment_Delivered();
