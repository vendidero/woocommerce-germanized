<?php
/**
 * Class WC_GZD_DHL_Email_Customer_Return_Shipment_Label file.
 *
 * @package Vendidero/Germanized/DHL/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ReturnLabel;
use Vendidero\Germanized\Shipments\ReturnShipment;

if ( ! class_exists( 'WC_GZD_DHL_Email_Customer_Return_Shipment_Label', false ) ) :

	/**
	 * Customer Shipment return label notification.
	 *
	 * This notification is used to send the return label to the customer.
	 *
	 * @class    WC_GZD_DHL_Email_Customer_Return_Shipment_Label
	 * @version  1.0.0
	 * @package  Vendidero/Germanized/DHL/Emails
	 * @extends  WC_Email
	 */
	class WC_GZD_DHL_Email_Customer_Return_Shipment_Label extends WC_Email {

		/**
		 * Label.
		 *
		 * @var ReturnLabel|bool
		 */
		public $label;

		/**
		 * Shipment.
		 *
		 * @var ReturnShipment|bool
		 */
		public $shipment;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->customer_email = true;
			$this->id             = 'customer_dhl_return_shipment_label';
			$this->title          = _x( 'DHL Return Label', 'dhl', 'woocommerce-germanized' );
			$this->description    = _x( 'This email is being used to send the DHL return label to the customer.', 'dhl', 'woocommerce-germanized' );

			$this->template_html  = 'emails/customer-dhl-return-shipment-label.php';
			$this->template_plain = 'emails/plain/customer-dhl-return-shipment-label.php';
			$this->template_base  = Package::get_path() . '/templates/';

			$this->placeholders   = array(
				'{site_title}'      => $this->get_blogname(),
				'{shipment_number}' => '',
				'{tracking_number}' => '',
				'{order_number}'    => '',
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
			return _x( 'New DHL label for your return #{shipment_number} to your order #{order_number}', 'dhl', 'woocommerce-germanized' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			return _x( 'DHL label for your order: #{order_number}', 'dhl', 'woocommerce-germanized' );
		}

		/**
		 * Trigger.
		 *
		 * @param int  $label_id Label ID.
		 */
		public function trigger( $label_id ) {
			$this->setup_locale();

			if ( $this->label = wc_gzd_dhl_get_label( $label_id ) ) {

				// Actual PDF label is missing
				if ( ! $this->label->get_file() ) {
					return;
				}

				$this->placeholders['{tracking_number}'] = $this->label->get_number();

				if ( $this->shipment = $this->label->get_shipment() ) {

					$this->placeholders['{shipment_number}'] = $this->shipment->get_shipment_number();

					if ( $order_shipment = wc_gzd_get_shipment_order( $this->shipment->get_order() ) ) {

						$this->object                         = $this->shipment->get_order();
						$this->recipient                      = $order_shipment->get_order()->get_billing_email();
						$this->placeholders['{order_number}'] = $order_shipment->get_order()->get_order_number();
					}
				}
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		public function get_attachments() {
			$attachments = array();

			if ( $label = $this->label ) {
				if ( $file = $label->get_file() ) {
					$attachments[] = $file;
				}
			}

			return apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $this->object, $this );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html, array(
					'shipment'           => $this->shipment,
					'order'              => $this->object,
					'label'              => $this->label,
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
				$this->template_plain, array(
					'shipment'           => $this->shipment,
					'order'              => $this->object,
					'label'              => $this->label,
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

return new WC_GZD_DHL_Email_Customer_Return_Shipment_Label();
