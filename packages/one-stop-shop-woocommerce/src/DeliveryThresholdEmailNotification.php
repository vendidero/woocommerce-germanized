<?php

namespace Vendidero\OneStopShop;

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Email', false ) ) {
	require_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

defined( 'ABSPATH' ) || exit;

class DeliveryThresholdEmailNotification extends \WC_Email {

	public function __construct() {
		$this->template_base  = Package::get_path() . '/templates/';
		$this->id             = 'oss_delivery_threshold_email_notification';
		$this->title          = _x( 'OSS Delivery Threshold Notification', 'oss', 'woocommerce-germanized' );
		$this->description    = _x( 'This email notifies shop owners in case the delivery threshold (OSS) is close to being reached.', 'oss', 'woocommerce-germanized' );
		$this->template_html  = 'emails/admin-delivery-threshold.php';
		$this->template_plain = 'emails/plain/admin-delivery-threshold.php';
		$this->customer_email = false;

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
		return _x( '[{site_title}]: OSS delivery threshold reached', 'oss', 'woocommerce-germanized' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return _x( 'OSS delivery threshold reached', 'oss', 'woocommerce-germanized' );
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
				'report'             => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
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
				'report'             => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param Report $report
	 */
	public function trigger( $report ) {
		$this->object = $report;

		$success = $this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

		if ( $success ) {
			update_option( 'oss_woocommerce_notification_sent_' . $report->get_date_start()->format( 'Y' ), 'yes' );
		}
	}
}
