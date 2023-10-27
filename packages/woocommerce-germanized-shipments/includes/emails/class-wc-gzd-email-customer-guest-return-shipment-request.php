<?php
/**
 * Class WC_GZD_Email_Customer_Return_Shipment_Request file.
 *
 * @package Vendidero/Germanized/Shipments/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ReturnShipment;

if ( ! class_exists( 'WC_GZD_Email_Customer_Guest_Return_Shipment_Request', false ) ) :

	/**
	 * Customer return shipment notification.
	 *
	 * Return shipment request notifications are sent to the customer (guest) after submitting a new return request
	 * via the return request form.
	 *
	 * @class    WC_GZD_Email_Customer_Return_Shipment_Request
	 * @version  1.0.0
	 * @package  Vendidero/Germanized/Shipments/Emails
	 * @extends  WC_Email
	 */
	class WC_GZD_Email_Customer_Guest_Return_Shipment_Request extends WC_Email {

		public $request_url = '';

		public $helper = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->customer_email = true;
			$this->id             = 'customer_guest_return_shipment_request';
			$this->title          = _x( 'Order guest return request', 'shipments', 'woocommerce-germanized' );
			$this->description    = _x( 'Order guest return request are sent to the customer after submitting a new return request as a guest.', 'shipments', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-guest-return-shipment-request.php';
			$this->template_plain = 'emails/plain/customer-guest-return-shipment-request.php';
			$this->template_base  = Package::get_path() . '/templates/';
			$this->helper         = function_exists( 'wc_gzd_get_email_helper' ) ? wc_gzd_get_email_helper( $this ) : false;

			$this->placeholders = array(
				'{site_title}'   => $this->get_blogname(),
				'{order_number}' => '',
				'{order_date}'   => '',
			);

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
			return _x( 'Your return request to your order {order_number}', 'shipments', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return _x( 'Return request to your order: {order_number}', 'shipments', 'woocommerce-germanized' );
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
		 * @param int $order_id order ID.
		 */
		public function trigger( $order_id ) {
			if ( $this->helper ) {
				$this->helper->setup_locale();
			} else {
				$this->setup_locale();
			}

			if ( $this->object = wc_get_order( $order_id ) ) {

				$this->placeholders['{order_number}'] = $this->object->get_order_number();

				if ( ( $order_shipment = wc_gzd_get_shipment_order( $this->object ) ) && ( $this->request_url = wc_gzd_get_order_customer_add_return_url( $this->object ) ) ) {
					$this->recipient                      = $this->object->get_billing_email();
					$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
					$this->placeholders['{order_number}'] = $this->object->get_order_number();
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
					'order'                  => $this->object,
					'add_return_request_url' => $this->request_url,
					'email_heading'          => $this->get_heading(),
					'additional_content'     => $this->get_additional_content(),
					'sent_to_admin'          => false,
					'plain_text'             => false,
					'email'                  => $this,
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
					'order'                  => $this->object,
					'add_return_request_url' => $this->request_url,
					'email_heading'          => $this->get_heading(),
					'additional_content'     => $this->get_additional_content(),
					'sent_to_admin'          => false,
					'plain_text'             => true,
					'email'                  => $this,
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

return new WC_GZD_Email_Customer_Guest_Return_Shipment_Request();
